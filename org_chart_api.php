<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/db_connect.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$session_role = strtolower((string)$_SESSION['role']);
if ($session_role !== 'gm') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * 역할 서열(확정)
 * gm -> admin -> master -> agent -> investor -> referrer(=추천인 버튼은 investor 생성 + referrer_id)
 */
$role_rank = [
    'gm'       => 6,
    'admin'    => 5,
    'master'   => 4,
    'agent'    => 3,
    'investor' => 2,
    'referrer' => 1,
];

function next_role(string $role): string {
    $map = [
        'admin'  => 'master',
        'master' => 'agent',
        'agent'  => 'investor',
    ];
    $role = strtolower($role);
    return $map[$role] ?? '';
}

/**
 * 공통: 하위 존재 체크 (users.sponsor_id + user_details.sponsor_user_id 둘 다 호환)
 */
function has_children(mysqli $conn, int $id): int {
    $sql = "
        SELECT 1
        FROM users c
        LEFT JOIN user_details cd ON cd.user_id = c.id
        WHERE (c.sponsor_id = ? OR cd.sponsor_user_id = ?)
        LIMIT 1
    ";
    $st = $conn->prepare($sql);
    $st->bind_param("ii", $id, $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ? 1 : 0;
}

/**
 * 0) 루트 조회
 * - GM 화면에서는 admin들을 루트로 노출 (요청사항)
 */
if ($action === 'get_root') {
    $sql = "
        SELECT
            u.id, u.username, u.name, u.role,
            EXISTS(
                SELECT 1
                FROM users c
                LEFT JOIN user_details cd ON cd.user_id = c.id
                WHERE (c.sponsor_id = u.id OR cd.sponsor_user_id = u.id)
                LIMIT 1
            ) AS has_children
        FROM users u
        WHERE u.role = 'admin'
        ORDER BY u.id
    ";
    $res = $conn->query($sql);
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

/**
 * 1) 직속 하위 조회 (트리 펼침)
 * - users.sponsor_id 우선 + user_details.sponsor_user_id 레거시 호환
 */
if ($action === 'get_children') {
    $parent_id = (int)($_GET['parent_id'] ?? 0);
    if ($parent_id <= 0) {
        echo json_encode([]);
        exit;
    }

    $sql = "
        SELECT DISTINCT
            u.id,
            u.username,
            u.name,
            u.role,
            EXISTS (
                SELECT 1
                FROM users c
                LEFT JOIN user_details cd ON cd.user_id = c.id
                WHERE (c.sponsor_id = u.id OR cd.sponsor_user_id = u.id)
                LIMIT 1
            ) AS has_children
        FROM users u
        LEFT JOIN user_details d ON d.user_id = u.id
        WHERE (u.sponsor_id = ? OR d.sponsor_user_id = ?)
        ORDER BY
          CASE u.role
            WHEN 'admin' THEN 1
            WHEN 'master' THEN 2
            WHEN 'agent' THEN 3
            WHEN 'investor' THEN 4
            WHEN 'referrer' THEN 5
            ELSE 99
          END,
          u.username
    ";
    $st = $conn->prepare($sql);
    $st->bind_param("ii", $parent_id, $parent_id);
    $st->execute();
    echo json_encode($st->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

/**
 * 2) 직계 하위 생성 (new 버튼)
 * - parent_id를 기준으로 "바로 아래 role"만 생성시키는 모드
 * - 프론트에서 role을 보내도 검증해서 next role로 강제함
 */
if ($action === 'create_child') {
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $username  = trim($_POST['username'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if ($parent_id <= 0 || $username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => '필수 값 누락(parent_id/username/password)']);
        exit;
    }

    // 부모 role
    $st = $conn->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    $st->bind_param("i", $parent_id);
    $st->execute();
    $parent = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$parent) {
        http_response_code(400);
        echo json_encode(['error' => '상위 사용자를 찾을 수 없습니다.']);
        exit;
    }

    $parent_role = strtolower($parent['role']);
    $new_role = next_role($parent_role);
    if ($new_role === '') {
        http_response_code(400);
        echo json_encode(['error' => '이 ROLE에서는 new(직계하위 생성)을 지원하지 않습니다.']);
        exit;
    }

    // username 중복
    $st = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $st->bind_param("s", $username);
    $st->execute();
    if ($st->get_result()->fetch_assoc()) {
        $st->close();
        http_response_code(409);
        echo json_encode(['error' => '이미 존재하는 아이디입니다.']);
        exit;
    }
    $st->close();

    $conn->begin_transaction();
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $email = $username . '@noemail.local';
        $ref_code = 'REF_' . date('Ymd') . '_' . random_int(100000, 999999);

        // users insert (DB 스키마 맞춤)
        $st1 = $conn->prepare("
            INSERT INTO users
              (name, username, email, role, password_hash, phone, referral_code, sponsor_id, created_at)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $st1->bind_param("sssssssi", $name, $username, $email, $new_role, $hash, $phone, $ref_code, $parent_id);
        $st1->execute();
        $new_user_id = $st1->insert_id;
        $st1->close();

        // user_details도 함께 생성 (레거시 sponsor_user_id 유지)
        $st2 = $conn->prepare("
            INSERT INTO user_details (user_id, sponsor_user_id, referral_code, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $st2->bind_param("iis", $new_user_id, $parent_id, $ref_code);
        $st2->execute();
        $st2->close();

        $conn->commit();
        echo json_encode(['success' => true, 'new_user_id' => $new_user_id]);
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => '계정 생성 실패', 'message' => $e->getMessage()]);
        exit;
    }
}

/**
 * 3) 추천인 생성 (referrer new 버튼)
 * - investor 노드에서만 사용
 * - 새 계정 role은 investor로 생성
 * - referrer_id = 현재 investor
 * - sponsor_id = 현재 investor의 sponsor_id(=agent)
 */
if ($action === 'create_referral') {
    $investor_id = (int)($_POST['investor_id'] ?? 0);
    $username  = trim($_POST['username'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $name      = trim($_POST['name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if ($investor_id <= 0 || $username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => '필수 값 누락(investor_id/username/password)']);
        exit;
    }

    // investor 정보(상위 sponsor_id 필요)
    $st = $conn->prepare("SELECT id, role, sponsor_id FROM users WHERE id = ? LIMIT 1");
    $st->bind_param("i", $investor_id);
    $st->execute();
    $inv = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$inv || strtolower($inv['role']) !== 'investor') {
        http_response_code(400);
        echo json_encode(['error' => '추천인 생성은 investor에서만 가능합니다.']);
        exit;
    }

    $agent_id = (int)($inv['sponsor_id'] ?? 0);
    if ($agent_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '상위(agent) 연결이 없어 추천인 생성 불가']);
        exit;
    }

    // username 중복
    $st = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $st->bind_param("s", $username);
    $st->execute();
    if ($st->get_result()->fetch_assoc()) {
        $st->close();
        http_response_code(409);
        echo json_encode(['error' => '이미 존재하는 아이디입니다.']);
        exit;
    }
    $st->close();

    $conn->begin_transaction();
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $email = $username . '@noemail.local';
        $ref_code = 'REF_' . date('Ymd') . '_' . random_int(100000, 999999);

        // 새 investor 생성 + referrer_id 설정 + sponsor는 agent 유지
        $st1 = $conn->prepare("
            INSERT INTO users
              (name, username, email, role, password_hash, phone, referral_code, referrer_id, sponsor_id, created_at)
            VALUES
              (?, ?, ?, 'investor', ?, ?, ?, ?, ?, NOW())
        ");
        $st1->bind_param("ssssssii", $name, $username, $email, $hash, $phone, $ref_code, $investor_id, $agent_id);
        $st1->execute();
        $new_user_id = $st1->insert_id;
        $st1->close();

        // user_details sponsor_user_id도 agent로
        $st2 = $conn->prepare("
            INSERT INTO user_details (user_id, sponsor_user_id, referral_code, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $st2->bind_param("iis", $new_user_id, $agent_id, $ref_code);
        $st2->execute();
        $st2->close();

        $conn->commit();
        echo json_encode(['success' => true, 'new_user_id' => $new_user_id]);
        exit;

    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => '추천인 생성 실패', 'message' => $e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
exit;
