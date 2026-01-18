<?php
/**
 * settle_confirm_v2.php
 * ------------------------------------------------------
 * ✅ 조직정산 V2 (CodePay 기준) - 안전장치 적용
 * - 대상: codepay_payout_items (현재 level의 role)
 * - 1) download_only  : CSV 다운로드만 (status 변경 없음)
 * - 2) confirm_sent   : (confirm) pending → sent 변경
 *
 * 🔒 기존 settle_confirm.php(admin_sales_daily 기반)는 그대로 유지합니다.
 */

session_start();

$action    = $_POST['action'] ?? '';

// ✅ 돌아갈 URL (UI에서 전달)
$redirect = $_POST['redirect'] ?? '';

// ✅ 현재 화면(테이블)과 동일 범위로 다운로드하기 위한 파라미터
// - level: admin/master/agent/investor/referrer(=referral)
// - target: 상위 선택값(예: admin을 선택한 후 master 레벨로 내려가면 target=admin_username)
$level  = $_POST['level'] ?? 'admin';
$target = $_POST['target'] ?? '';

// 기본 응답은 텍스트 (CSV 다운로드 시에는 아래에서 덮어씀)
header('Content-Type: text/plain; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "로그인이 필요합니다.";
    exit;
}

include 'db_connect.php';

// ✅ GM만 허용 (세션 role 없으면 DB에서 확인)
$is_gm = false;
if (isset($_SESSION['role'])) {
    $is_gm = ($_SESSION['role'] === 'gm');
} else {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $is_gm = (isset($r['role']) && $r['role'] === 'gm');
}
if (!$is_gm) {
    http_response_code(403);
    echo "GM만 정산 가능합니다.";
    exit;
}

// ✅ 입력
$settle_date = $_POST['settle_date'] ?? '';
$dt = DateTime::createFromFormat('Y-m-d', $settle_date);
if (!$dt || $dt->format('Y-m-d') !== $settle_date) {
    http_response_code(400);
    echo "잘못된 날짜 형식입니다. (YYYY-MM-DD)";
    exit;
}

if ($action !== 'download_only' && $action !== 'confirm_sent') {
    http_response_code(400);
    echo "잘못된 요청입니다.";
    exit;
}

// ✅ level → payout role 매핑 (DB: codepay_payout_items.role)
$level_to_role = [
    'admin'    => 'admin',
    'master'   => 'master',
    'agent'    => 'agent',
    'investor' => 'investor',
    // UI에서는 referrer로 부르지만 DB는 referral
    'referrer' => 'referral',
    'referral' => 'referral',
];

$wanted_role = $level_to_role[$level] ?? null;
if ($wanted_role === null) {
    http_response_code(400);
    echo "잘못된 level 값입니다.";
    exit;
}

// ✅ 상위 필터 컬럼 (dividend 테이블 기준) - 화면의 롤다운과 동일
$parent_filter_col = null;
if ($level === 'master')   $parent_filter_col = 'admin_username';
if ($level === 'agent')    $parent_filter_col = 'mastr_username';
if ($level === 'investor') $parent_filter_col = 'agent_username';
if ($level === 'referrer' || $level === 'referral') $parent_filter_col = 'investor_username';

// ✅ 현재 레벨의 이름 컬럼 (dividend 기준) - users 조인 없이도 테이블과 동일한 "name"을 만들기 위함
$name_col = 'admin_username';
if ($level === 'master')   $name_col = 'mastr_username';
if ($level === 'agent')    $name_col = 'agent_username';
if ($level === 'investor') $name_col = 'investor_username';
if ($level === 'referrer' || $level === 'referral') $name_col = 'referral_username';


// 상위 필터가 필요한 레벨인데 target이 비어있으면(=화면에서 상위 선택 안함)
if ($parent_filter_col !== null && $target === '') {
    http_response_code(400);
    echo "상위 선택(target)이 필요합니다.";
    exit;
}

try {
    $conn->begin_transaction();

    // ✅ 공통: (화면 테이블과 동일) role=현재 level, (필요 시) 상위 target 필터

    $where_parent = '';
    $bind_extra_types = '';
    $bind_extra_vals = [];
    if ($parent_filter_col !== null) {
        $where_parent = " AND d.`{$parent_filter_col}` = ? ";
        $bind_extra_types = 's';
        $bind_extra_vals[] = $target;
    }

    $sql = "
        SELECT
            i.id,
            -- users가 없거나 user_id가 비정상이더라도, dividend 컬럼을 fallback으로 사용
            COALESCE(u.username, d.`{$name_col}`) AS username,
            COALESCE(NULLIF(i.codepay_address_snapshot,''), ud.codepay_address, '') AS codepay_address,
            i.amount
        FROM codepay_payout_items i
        JOIN dividend d ON d.id = i.dividend_id
        LEFT JOIN users u ON u.id = i.user_id
        LEFT JOIN user_details ud ON ud.user_id = u.id
        WHERE DATE(d.tx_date) = ?
          AND i.role = ?
          AND i.status = 'pending'
          $where_parent
          AND d.`{$name_col}` IS NOT NULL AND d.`{$name_col}` <> ''
        ORDER BY username ASC, i.id ASC
        FOR UPDATE
    ";

    // bind: date + role + (optional target)
    $stmt = $conn->prepare($sql);
    if ($parent_filter_col !== null) {
        $stmt->bind_param('sss', $settle_date, $wanted_role, $target);
    } else {
        $stmt->bind_param('ss', $settle_date, $wanted_role);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $raw_items = [];
    while ($row = $res->fetch_assoc()) {
        $raw_items[] = $row;
    }
    $stmt->close();

    if (empty($raw_items)) {
        $conn->rollback();
        http_response_code(409);
        echo "이미 정산 완료된 날짜입니다(pending 없음): {$settle_date}";
        exit;
    }

    // ✅ 화면 테이블처럼 username 기준 합산
    $agg = []; // key=username|code
    $idsToMark = [];
    foreach ($raw_items as $it) {
        $idsToMark[] = (int)$it['id'];
        $uname = (string)$it['username'];
        $code  = (string)$it['codepay_address'];
        $key = $uname . '|' . $code;
        if (!isset($agg[$key])) {
            $agg[$key] = ['username' => $uname, 'codepay_address' => $code, 'amount' => 0.0];
        }
        $agg[$key]['amount'] += (float)$it['amount'];
    }

    if ($action === 'download_only') {
        // ✅ 1) CSV 다운로드만 (status 변경 없음)
        $filename = "codepay_{$wanted_role}_pending_{$settle_date}.csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        // UTF-8 BOM (엑셀 호환)
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        // ✅ 컬럼: 이름, 코드페이 어드레스, 배당금
        fputcsv($out, ['name', 'codepay_address', 'dividend_amount']);
        foreach ($agg as $row) {
            fputcsv($out, [
                $row['username'],
                $row['codepay_address'],
                number_format((float)$row['amount'], 2, '.', ''),
            ]);
        }

        $conn->commit();
        fclose($out);
        exit;
    }

    // ✅ 2) SENT 확정: pending → sent 변경
    $ph2 = implode(',', array_fill(0, count($idsToMark), '?'));
    $types2 = str_repeat('i', count($idsToMark));
    $sqlUp = "UPDATE codepay_payout_items SET status='sent' WHERE id IN ($ph2)";
    $stmtUp = $conn->prepare($sqlUp);
    $stmtUp->bind_param($types2, ...$idsToMark);
    $stmtUp->execute();
    $stmtUp->close();

    $conn->commit();

    // redirect back
    if ($redirect !== '') {
        header('Location: ' . $redirect);
        exit;
    }
    echo "SENT 확정 완료";
    exit;

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo "정산 처리 실패: " . $e->getMessage();
    exit;
}
