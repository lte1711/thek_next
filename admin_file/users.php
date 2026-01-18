<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';
$flash_messages = [];

/**
 * 삭제 미리보기(요약) - 관련 데이터 건수 집계
 * (주의) 표시용 집계이며, 실제 삭제 로직은 delete_user_safely()가 수행한다.
 */
function preview_user_delete(mysqli $conn, int $user_id): array {
    // 하위(스폰서) 회원 수
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE sponsor_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $children = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // transaction_distribution: user_id 직접 참조 + tx_id(거래) 참조를 UNION으로 중복 제거
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM (
            SELECT td.id FROM transaction_distribution td WHERE td.user_id = ?
            UNION
            SELECT td.id FROM transaction_distribution td
              JOIN user_transactions ut ON td.tx_id = ut.id
             WHERE ut.user_id = ?
        ) x"
    );
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $dist_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // 일별/집계
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM admin_deposits_daily WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $admin_dep_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM admin_sales_daily WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $admin_sales_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM gm_sales_daily WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $gm_sales_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // 상세/거절/거래
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_details WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $details_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_rejects WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rejects_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_transactions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $tx_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    // investor_agent_map
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM investor_agent_map WHERE investor_id = ? OR agent_id = ?");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $map_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $total_related = $dist_cnt + $admin_dep_cnt + $admin_sales_cnt + $gm_sales_cnt + $details_cnt + $rejects_cnt + $tx_cnt + $map_cnt;

    $warnings = [];
    if ($children > 0) {
        $warnings[] = "하위(스폰서) 회원 {$children}명 존재";
    }
    if ($dist_cnt > 0) {
        $warnings[] = "분배(transaction_distribution) {$dist_cnt}건";
    }
    if ($tx_cnt > 0) {
        $warnings[] = "거래(user_transactions) {$tx_cnt}건";
    }

    return [
        'user_id' => $user_id,
        'children' => $children,
        'counts' => [
            'transaction_distribution' => $dist_cnt,
            'admin_deposits_daily' => $admin_dep_cnt,
            'admin_sales_daily' => $admin_sales_cnt,
            'gm_sales_daily' => $gm_sales_cnt,
            'user_details' => $details_cnt,
            'user_rejects' => $rejects_cnt,
            'user_transactions' => $tx_cnt,
            'investor_agent_map' => $map_cnt,
        ],
        'total_related' => $total_related,
        'warnings' => $warnings,
    ];
}

// 감사로그용: 사용자 원본 행
function fetch_user_row(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// ✅ 삭제 미리보기 API (AJAX)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'preview_delete' && isset($_POST['id'])) {
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)$_POST['id'];
        try {
            $summary = preview_user_delete($conn, $id);
            echo json_encode(['ok' => true, 'type' => 'single', 'summary' => $summary], JSON_UNESCAPED_UNICODE);
        } catch (mysqli_sql_exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    if ($_POST['action'] === 'preview_bulk_delete' && !empty($_POST['ids']) && is_array($_POST['ids'])) {
        header('Content-Type: application/json; charset=utf-8');
        $ids = array_values(array_filter(array_map('intval', $_POST['ids']), fn($v) => $v > 0));
        $max_preview = 50; // 과도한 요청 방지
        if (count($ids) > $max_preview) {
            echo json_encode(['ok' => false, 'error' => "미리보기는 최대 {$max_preview}명까지 가능합니다."], JSON_UNESCAPED_UNICODE);
            exit;
        }
        try {
            $summaries = [];
            $totals = [
                'children' => 0,
                'transaction_distribution' => 0,
                'admin_deposits_daily' => 0,
                'admin_sales_daily' => 0,
                'gm_sales_daily' => 0,
                'user_details' => 0,
                'user_rejects' => 0,
                'user_transactions' => 0,
                'investor_agent_map' => 0,
                'total_related' => 0,
            ];
            foreach ($ids as $id) {
                $s = preview_user_delete($conn, $id);
                $summaries[] = $s;
                $totals['children'] += $s['children'];
                foreach ($s['counts'] as $k => $v) {
                    $totals[$k] += (int)$v;
                }
                $totals['total_related'] += (int)$s['total_related'];
            }
            echo json_encode(['ok' => true, 'type' => 'bulk', 'ids' => $ids, 'totals' => $totals, 'summaries' => $summaries], JSON_UNESCAPED_UNICODE);
        } catch (mysqli_sql_exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

/**
 * 회원 1명 삭제 (FK 안전처리)
 * - sponsor_id로 하위 회원이 존재하면 삭제를 막는다 (데이터 임의 변경 방지)
 * - transaction_distribution, admin_*_daily, gm_sales_daily 등 FK 참조 테이블을 먼저 삭제한다
 */
function delete_user_safely(mysqli $conn, int $user_id, bool $unlink_children = false): array {
    // 결과: [ok(bool), message(string)]
    try {
        $conn->begin_transaction();

        // 1) 하위(스폰서) 회원 처리 (users.sponsor_id FK)
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE sponsor_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $child_cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        if ($child_cnt > 0) {
            if ($unlink_children) {
                // 사용자가 명시적으로 선택한 경우에만 하위 스폰서를 NULL 처리
                $stmt = $conn->prepare("UPDATE users SET sponsor_id = NULL WHERE sponsor_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $conn->rollback();
                return [false, "삭제 실패: 이 회원을 스폰서로 가진 하위 회원이 {$child_cnt}명 존재합니다. (옵션) 하위 스폰서 해제 후 삭제를 체크하면 자동으로 sponsor_id를 NULL 처리한 뒤 삭제합니다."]; 
            }
        }

        // 2) transaction_distribution (FK: user_id, tx_id)
        // 2-1) 사용자 기준
        $stmt = $conn->prepare("DELETE FROM transaction_distribution WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        // 2-2) 거래(tx) 기준 (해당 유저의 거래를 참조하는 분배가 남아있을 수 있음)
        $stmt = $conn->prepare(
            "DELETE td FROM transaction_distribution td 
             JOIN user_transactions ut ON td.tx_id = ut.id 
             WHERE ut.user_id = ?"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 3) 일별 집계 테이블 (FK: user_id)
        $stmt = $conn->prepare("DELETE FROM admin_deposits_daily WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM admin_sales_daily WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM gm_sales_daily WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 4) user_rejects / user_details
        $stmt = $conn->prepare("DELETE FROM user_rejects WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM user_details WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 5) user_transactions (FK: user_transactions.user_id)
        $stmt = $conn->prepare("DELETE FROM user_transactions WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // 6) investor_agent_map 은 ON DELETE CASCADE로 안전하지만, 혹시 모를 구버전/환경 대비해 선제 삭제
        $stmt = $conn->prepare("DELETE FROM investor_agent_map WHERE investor_id = ? OR agent_id = ?");
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // 7) 마지막으로 users 삭제
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected < 1) {
            $conn->rollback();
            return [false, "삭제 실패: users 테이블에서 대상 회원을 찾지 못했습니다. (id={$user_id})"]; 
        }

        $conn->commit();
        return [true, "삭제 완료: 회원(id={$user_id}) 및 관련 데이터가 정상적으로 삭제되었습니다."];

    } catch (mysqli_sql_exception $e) {
        // FK 오류 등
        try { $conn->rollback(); } catch (Throwable $t) {}
        return [false, "삭제 실패 (DB 오류): " . $e->getMessage()];
    }
}

// ✅ 회원 등록/수정/삭제 처리
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === "create") {
            $name       = trim($_POST['name']);
            $username   = trim($_POST['username']);
            $email      = trim($_POST['email']);
            $country    = trim($_POST['country']);
            $role       = trim($_POST['role']);
            $phone      = trim($_POST['phone']);
            $referral   = trim($_POST['referral_code']);
            $referrer   = intval($_POST['referrer_id']);
            $sponsor    = intval($_POST['sponsor_id']);
            $password   = trim($_POST['password']);

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, username, email, country, role, password_hash, phone, referral_code, referrer_id, sponsor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssii", $name, $username, $email, $country, $role, $password_hash, $phone, $referral, $referrer, $sponsor);
            $stmt->execute();
            $new_id = (int)$conn->insert_id;
            $stmt->close();
            $flash_messages[] = "등록 완료: 회원이 생성되었습니다.";

            $after = fetch_user_row($conn, $new_id);
            if (is_array($after)) { unset($after['password_hash']); }
            audit_log($conn, 'create', 'users', $new_id, null, $after);

        } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
            $id         = intval($_POST['id']);
            $before = fetch_user_row($conn, $id);
            if (is_array($before)) { unset($before['password_hash']); }
            $name       = trim($_POST['name']);
            $username   = trim($_POST['username']);
            $email      = trim($_POST['email']);
            $country    = trim($_POST['country']);
            $role       = trim($_POST['role']);
            $phone      = trim($_POST['phone']);
            $referral   = trim($_POST['referral_code']);
            $referrer   = intval($_POST['referrer_id']);
            $sponsor    = intval($_POST['sponsor_id']);
            $password   = trim($_POST['password']);

            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, country=?, role=?, password_hash=?, phone=?, referral_code=?, referrer_id=?, sponsor_id=? WHERE id=?");
                $stmt->bind_param("ssssssssiii", $name, $username, $email, $country, $role, $password_hash, $phone, $referral, $referrer, $sponsor, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, username=?, email=?, country=?, role=?, phone=?, referral_code=?, referrer_id=?, sponsor_id=? WHERE id=?");
                $stmt->bind_param("ssssssiiii", $name, $username, $email, $country, $role, $phone, $referral, $referrer, $sponsor, $id);
            }
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "수정 완료: 회원 정보가 저장되었습니다. (id={$id})";

            $after = fetch_user_row($conn, $id);
            if (is_array($after)) { unset($after['password_hash']); }
            audit_log($conn, 'update', 'users', $id, $before, $after);

        } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $unlink_children = isset($_POST['unlink_children']) && $_POST['unlink_children'] === '1';
            $before = fetch_user_row($conn, $id);
            if (is_array($before)) { unset($before['password_hash']); }
            $summary = preview_user_delete($conn, $id);
            [$ok, $msg] = delete_user_safely($conn, $id, $unlink_children);
            $flash_messages[] = $msg;
            audit_log($conn, $ok ? 'delete' : 'delete_failed', 'users', $id, $before, null, ['unlink_children' => $unlink_children, 'summary' => $summary, 'result' => $msg]);

        } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids'])) {
            $unlink_children = isset($_POST['unlink_children']) && $_POST['unlink_children'] === '1';
            $ok_cnt = 0;
            $fail_cnt = 0;
            foreach ($_POST['ids'] as $id) {
                $id = intval($id);
                $before = fetch_user_row($conn, $id);
                if (is_array($before)) { unset($before['password_hash']); }
                $summary = preview_user_delete($conn, $id);
                [$ok, $msg] = delete_user_safely($conn, $id, $unlink_children);
                if ($ok) $ok_cnt++; else $fail_cnt++;
                $flash_messages[] = $msg;
                audit_log($conn, $ok ? 'delete' : 'delete_failed', 'users', $id, $before, null, ['bulk' => true, 'unlink_children' => $unlink_children, 'summary' => $summary, 'result' => $msg]);
            }
            $flash_messages[] = "일괄삭제 결과: 성공 {$ok_cnt}건 / 실패 {$fail_cnt}건";
            audit_log($conn, 'bulk_delete', 'users', null, null, null, ['unlink_children' => $unlink_children, 'ok' => $ok_cnt, 'fail' => $fail_cnt, 'ids' => array_map('intval', (array)$_POST['ids'])]);
        }

    } catch (mysqli_sql_exception $e) {
        $flash_messages[] = "처리 실패 (DB 오류): " . $e->getMessage();
    }
}

// ✅ 회원 목록 불러오기 (간단 필터/검색)
$q = trim((string)($_GET['q'] ?? ''));
$role_f = trim((string)($_GET['role'] ?? ''));
$limit = 200;

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    // id 숫자 검색도 허용
    $where[] = "(id = ? OR name LIKE ? OR username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $id_q = ctype_digit($q) ? (int)$q : -1;
    $like = '%' . $q . '%';
    $types .= 'issss';
    $params[] = $id_q;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($role_f !== '') {
    $where[] = "role = ?";
    $types .= 's';
    $params[] = $role_f;
}

$sql = "SELECT * FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY id DESC LIMIT " . (int)$limit;

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<?php admin_render_header('회원 관리 (users)'); ?>
<?php if (!empty($flash_messages)): ?>
        <div class="flash-box">
            <strong>처리 결과</strong>
            <ul>
                <?php foreach ($flash_messages as $m): ?>
                    <li><?= htmlspecialchars($m) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <style>
        /* users.php 전용 간결 UI */
        .u-toolbar{display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; margin:10px 0 12px;}
        .u-toolbar .left{display:flex; flex-wrap:wrap; gap:8px; align-items:center;}
        .u-toolbar input[type="text"], .u-toolbar select{padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
        .u-card{background:#fff; border:1px solid #eee; border-radius:14px; padding:12px;}
        .u-table{width:100%; border-collapse:collapse;}
        .u-table th{position:sticky; top:0; background:#fafafa; z-index:1; text-align:left; font-size:12px; color:#555; border-bottom:1px solid #eee; padding:10px 8px;}
        .u-table td{border-bottom:1px solid #f0f0f0; padding:10px 8px; vertical-align:top;}
        .u-table tr:hover td{background:#fcfcff;}
        .u-mini{font-size:12px; color:#666;}
        .u-actions{display:flex; gap:6px; align-items:center; justify-content:flex-end; flex-wrap:wrap;}
        .btn{border:1px solid #000000ff; background:#fff; padding:7px 10px; border-radius:10px; cursor:pointer;}
        .btn-primary{border-color:#000000ff; background:#f5f9ff;}
        .btn-danger{border-color:#ffd0d0; background:#fff5f5; color:#b00020;}
        .btn-link{border:none; background:transparent; padding:0; color:#1a73e8; cursor:pointer;}
        .u-editrow{background:#fafcff;}
        .u-editgrid{display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:8px;}
        .u-editgrid input{width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
        @media (max-width:1100px){ .u-editgrid{grid-template-columns:repeat(3, minmax(0,1fr));} }
        @media (max-width:640px){ .u-editgrid{grid-template-columns:repeat(2, minmax(0,1fr));} }
    </style>

    <div class="u-toolbar">
        <div class="left">
            <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="검색 (ID/이름/아이디/이메일/전화)" />
                <select name="role">
                    <option value="">전체 역할</option>
                    <?php
                        $roles = ['superadmin','admin','master','agent','investor','gm'];
                        foreach ($roles as $r) {
                            $sel = ($role_f === $r) ? 'selected' : '';
                            echo '<option value="'.htmlspecialchars($r).'" '.$sel.'>'.htmlspecialchars($r).'</option>';
                        }
                    ?>
                </select>
                <button class="btn" type="submit">조회</button>
                <a class="btn" href="users.php">초기화</a>
            </form>
            <span class="u-mini">* 최신 <?= (int)$limit ?>명 표시</span>
        </div>

        <details class="u-card" style="padding:10px 12px;">
            <summary style="cursor:pointer; font-weight:700;">+ 신규 회원 등록</summary>
            <div style="margin-top:10px;">
                <form method="POST">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="u-editgrid">
                        <input type="text" name="name" placeholder="이름" required>
                        <input type="text" name="username" placeholder="아이디" required>
                        <input type="email" name="email" placeholder="이메일" required>
                        <input type="text" name="phone" placeholder="전화번호">
                        <input type="text" name="role" placeholder="역할(role)" required>
                        <input type="text" name="country" placeholder="국가">
                        <input type="text" name="referral_code" placeholder="추천코드" required>
                        <input type="number" name="referrer_id" placeholder="추천인ID">
                        <input type="number" name="sponsor_id" placeholder="스폰서ID">
                        <input type="password" name="password" placeholder="비밀번호" required>
                    </div>
                    <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                        <button class="btn" type="submit">등록</button>
                    </div>
                </form>
            </div>
        </details>
    </div>

    <div class="u-card">
        <form method="POST" id="bulkDeleteForm" class="bulk-delete-form">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="bulk_delete">

            <div style="display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:10px;">
                <label style="display:inline-flex; gap:8px; align-items:center; cursor:pointer;" class="u-mini">
                    <input type="checkbox" name="unlink_children" value="1">
                    <span><strong>(옵션)</strong> 하위 회원 sponsor_id 해제 후 삭제</span>
                </label>
                <button class="btn btn-danger" type="submit">선택 삭제</button>
            </div>

            <div style="overflow-x:auto;">
                <table class="u-table">
                    <thead>
                        <tr>
                            <th style="width:44px;"><input type="checkbox" id="chkAll"></th>
                            <th style="width:70px;">ID</th>
                            <th>이름</th>
                            <th>아이디</th>
                            <th>이메일</th>
                            <th style="width:120px;">전화</th>
                            <th style="width:90px;">역할</th>
                            <th style="width:90px;">스폰서</th>
                            <th style="width:90px;">추천인</th>
                            <th style="width:140px;">가입일</th>
                            <th style="width:190px; text-align:right;">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $uid = (int)$row['id']; ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= $uid ?>" class="chkRow"></td>
                                <td><?= $uid ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td class="u-mini"><?= htmlspecialchars($row['username']) ?></td>
                                <td class="u-mini"><?= htmlspecialchars($row['email']) ?></td>
                                <td class="u-mini"><?= htmlspecialchars($row['phone']) ?></td>
                                <td><span class="u-mini" style="font-weight:700; color:#333;"><?= htmlspecialchars($row['role']) ?></span></td>
                                <td class="u-mini"><?= htmlspecialchars((string)$row['sponsor_id']) ?></td>
                                <td class="u-mini"><?= htmlspecialchars((string)$row['referrer_id']) ?></td>
                                <td class="u-mini"><?= htmlspecialchars((string)$row['created_at']) ?></td>
                                <td style="text-align:right;">
                                    <div class="u-actions">
                                        <button type="button" class="btn" data-toggle-edit="<?= $uid ?>">수정</button>
                                        <form method="POST" class="delete-form" style="margin:0;">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="id" value="<?= $uid ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="unlink_children" value="0" class="unlink-hidden">
                                            <button type="submit" class="btn btn-danger" data-del="<?= $uid ?>">삭제</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr class="u-editrow" id="editrow-<?= $uid ?>" style="display:none;">
                                <td colspan="11">
                                    <form method="POST" class="edit-form" style="margin:0;">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?= $uid ?>">

                                        <div class="u-editgrid">
                                            <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" placeholder="이름">
                                            <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" placeholder="아이디">
                                            <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" placeholder="이메일">
                                            <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" placeholder="전화">
                                            <input type="text" name="country" value="<?= htmlspecialchars($row['country']) ?>" placeholder="국가">
                                            <input type="text" name="role" value="<?= htmlspecialchars($row['role']) ?>" placeholder="역할(role)">

                                            <input type="text" name="referral_code" value="<?= htmlspecialchars($row['referral_code']) ?>" placeholder="추천코드">
                                            <input type="number" name="referrer_id" value="<?= htmlspecialchars((string)$row['referrer_id']) ?>" placeholder="추천인ID">
                                            <input type="number" name="sponsor_id" value="<?= htmlspecialchars((string)$row['sponsor_id']) ?>" placeholder="스폰서ID">
                                            <input type="password" name="password" placeholder="비밀번호 변경 시 입력">
                                        </div>

                                        <div style="display:flex; justify-content:space-between; gap:10px; margin-top:10px; align-items:center; flex-wrap:wrap;">
                                            <label class="u-mini" style="display:inline-flex; gap:8px; align-items:center; cursor:pointer;">
                                                <input type="checkbox" class="del-unlink" data-del-unlink="<?= $uid ?>"> 하위 스폰서 해제 후 삭제(삭제 시 적용)
                                            </label>
                                            <div class="u-actions">
                                                <button type="button" class="btn" data-toggle-edit="<?= $uid ?>">닫기</button>
                                                <button class="btn" type="submit">저장</button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <script>
    (function(){
        async function postPreview(formData){
            const res = await fetch(location.href, { method: 'POST', body: formData, credentials: 'same-origin' });
            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('미리보기 응답이 JSON이 아닙니다. (세션/리다이렉트 여부 확인)');
            }
            const json = await res.json();
            if (!json.ok) throw new Error(json.error || '미리보기 실패');
            return json;
        }

        function fmtSummarySingle(s){
            const c = s.counts || {};
            const lines = [];
            lines.push(`회원 ID: ${s.user_id}`);
            lines.push('');
            lines.push('[삭제될 관련 데이터]');
            lines.push(`- 하위(스폰서) 회원: ${s.children}명`);
            lines.push(`- 분배(transaction_distribution): ${c.transaction_distribution||0}건`);
            lines.push(`- 거래(user_transactions): ${c.user_transactions||0}건`);
            lines.push(`- 거절(user_rejects): ${c.user_rejects||0}건`);
            lines.push(`- 상세(user_details): ${c.user_details||0}건`);
            lines.push(`- admin_deposits_daily: ${c.admin_deposits_daily||0}건`);
            lines.push(`- admin_sales_daily: ${c.admin_sales_daily||0}건`);
            lines.push(`- gm_sales_daily: ${c.gm_sales_daily||0}건`);
            lines.push(`- investor_agent_map: ${c.investor_agent_map||0}건`);
            lines.push('');
            if (s.children > 0) {
                lines.push('⚠ 하위 회원이 존재합니다.');
                lines.push('- (옵션) "하위 스폰서 해제 후 삭제"를 체크하지 않으면 삭제가 차단됩니다.');
                lines.push('- 체크하면 하위 회원들의 sponsor_id를 NULL로 바꾼 뒤 삭제합니다.');
                lines.push('');
            }
            lines.push('정말 삭제하시겠습니까?');
            return lines.join('\n');
        }

        function fmtSummaryBulk(payload){
            const t = payload.totals || {};
            const lines = [];
            lines.push(`선택 회원: ${payload.ids.length}명`);
            lines.push('');
            lines.push('[삭제될 관련 데이터 합계]');
            lines.push(`- 하위(스폰서) 회원: 총 ${t.children||0}명`);
            lines.push(`- 분배(transaction_distribution): ${t.transaction_distribution||0}건`);
            lines.push(`- 거래(user_transactions): ${t.user_transactions||0}건`);
            lines.push(`- 거절(user_rejects): ${t.user_rejects||0}건`);
            lines.push(`- 상세(user_details): ${t.user_details||0}건`);
            lines.push(`- admin_deposits_daily: ${t.admin_deposits_daily||0}건`);
            lines.push(`- admin_sales_daily: ${t.admin_sales_daily||0}건`);
            lines.push(`- gm_sales_daily: ${t.gm_sales_daily||0}건`);
            lines.push(`- investor_agent_map: ${t.investor_agent_map||0}건`);
            lines.push('');

            const blocked = (payload.summaries || []).filter(s => (s.children||0) > 0);
            if (blocked.length > 0) {
                lines.push('⚠ 하위 회원이 존재하는 스폰서가 포함되어 있습니다.');
                lines.push(`- 해당 회원 수: ${blocked.length}명`);
                lines.push('- (옵션) "하위 스폰서 해제 후 삭제"를 체크하지 않으면, 그 회원들은 삭제가 실패합니다.');
                lines.push('');
            }
            lines.push('정말 일괄삭제 하시겠습니까?');
            return lines.join('\n');
        }

        // ✅ 수정 행 토글
        document.querySelectorAll('[data-toggle-edit]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const uid = btn.getAttribute('data-toggle-edit');
                const row = document.getElementById('editrow-' + uid);
                if (!row) return;
                const isOpen = row.style.display !== 'none';
                // 한 번에 하나만 열기
                document.querySelectorAll('.u-editrow').forEach(r => r.style.display = 'none');
                row.style.display = isOpen ? 'none' : '';
            });
        });

        // ✅ 전체 선택
        const chkAll = document.getElementById('chkAll');
        if (chkAll) {
            chkAll.addEventListener('change', () => {
                document.querySelectorAll('.chkRow').forEach(ch => ch.checked = chkAll.checked);
            });
        }

        // ✅ 삭제 옵션(하위 스폰서 해제) 상태를 삭제폼 hidden에 반영
        function syncUnlinkHidden(uid){
            const opt = document.querySelector('[data-del-unlink="' + uid + '"]');
            const hidden = document.querySelector('form.delete-form input[name="unlink_children"].unlink-hidden');
            // hidden은 각 row에 하나씩 있으므로 uid로 스코프
            const form = document.querySelector('form.delete-form input[name="id"][value="' + uid + '"]')?.closest('form');
            const h = form?.querySelector('input[name="unlink_children"].unlink-hidden');
            if (h) h.value = (opt && opt.checked) ? '1' : '0';
        }
        document.querySelectorAll('[data-del-unlink]').forEach((ch) => {
            ch.addEventListener('change', () => syncUnlinkHidden(ch.getAttribute('data-del-unlink')));
        });

        // 단일 삭제 미리보기
        document.querySelectorAll('form.delete-form').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                if (form.dataset.confirmed === '1') return; // 재진입 방지
                // 옵션 반영
                const uid = form.querySelector('input[name="id"]')?.value;
                if (uid) syncUnlinkHidden(uid);
                e.preventDefault();
                try {
                    const fd = new FormData(form);
                    fd.set('action', 'preview_delete');
                    const payload = await postPreview(fd);
                    const msg = fmtSummarySingle(payload.summary);
                    if (window.confirm(msg)) {
                        form.dataset.confirmed = '1';
                        // 원래 action(delete)로 되돌리기
                        form.querySelector('input[name="action"]').value = 'delete';
                        form.submit();
                    }
                } catch (err) {
                    alert('미리보기 실패: ' + (err?.message || err));
                }
            });
        });

        // 일괄삭제 미리보기
        const bulkForm = document.getElementById('bulkDeleteForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', async (e) => {
                if (bulkForm.dataset.confirmed === '1') return;
                e.preventDefault();

                const checked = bulkForm.querySelectorAll('input[name="ids[]"]:checked');
                if (!checked || checked.length === 0) {
                    alert('삭제할 회원을 선택하세요.');
                    return;
                }

                try {
                    const fd = new FormData(bulkForm);
                    fd.set('action', 'preview_bulk_delete');
                    const payload = await postPreview(fd);
                    const msg = fmtSummaryBulk(payload);
                    if (window.confirm(msg)) {
                        bulkForm.dataset.confirmed = '1';
                        bulkForm.querySelector('input[name="action"]').value = 'bulk_delete';
                        bulkForm.submit();
                    }
                } catch (err) {
                    alert('미리보기 실패: ' + (err?.message || err));
                }
            });
        }
    })();
    </script>
<?php admin_render_footer(); ?>