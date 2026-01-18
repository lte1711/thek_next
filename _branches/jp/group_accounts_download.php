<?php
// 조직 데일리 정산 - 목록 다운로드 (선택 날짜 기준, Admin~Referral 전체)
session_start();

// Safe initialization
if (!function_exists('t')) {
    $i18n_path = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n_path)) {
        require_once $i18n_path;
    } else {
        function t($key, $fallback = null) {
            return $fallback ?? $key;
        }
        function current_lang() {
            return 'ko';
        }
    }
}

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// GM만 접근
$is_gm = false;
if (isset($_SESSION['role'])) {
    $is_gm = ($_SESSION['role'] === 'gm');
} else {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $is_gm = (isset($res['role']) && $res['role'] === 'gm');
}

if (!$is_gm) {
    http_response_code(403);
    exit(t('err.gm_only_access', 'GM only can access.'));
}

$date = $_GET['date'] ?? '';
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    exit('유효한 date 파라미터가 필요합니다. (YYYY-MM-DD)');
}

/**
 * 선택 날짜 기준으로 Admin~Referral 전체 합산을 한 번에 뽑음.
 * - dividend 테이블은 username 컬럼 기반
 * - users / user_details는 username / user_id로 join
 */
$sql = "
    SELECT 'Administrator' AS role, u.id, u.username,
           ud.wallet_address, ud.codepay_address,
           COALESCE(SUM(d.admin_amount),0) AS amount
      FROM dividend d
      JOIN users u ON u.username = d.admin_username
      LEFT JOIN user_details ud ON ud.user_id = u.id
     WHERE DATE(d.tx_date) = ? AND u.role='admin'
     GROUP BY u.id, u.username, ud.wallet_address, ud.codepay_address

    UNION ALL

    SELECT 'Master' AS role, u.id, u.username,
           ud.wallet_address, ud.codepay_address,
           COALESCE(SUM(d.mastr_amount),0) AS amount
      FROM dividend d
      JOIN users u ON u.username = d.mastr_username
      LEFT JOIN user_details ud ON ud.user_id = u.id
     WHERE DATE(d.tx_date) = ? AND u.role='master'
     GROUP BY u.id, u.username, ud.wallet_address, ud.codepay_address

    UNION ALL

    SELECT 'Agent' AS role, u.id, u.username,
           ud.wallet_address, ud.codepay_address,
           COALESCE(SUM(d.agent_amount),0) AS amount
      FROM dividend d
      JOIN users u ON u.username = d.agent_username
      LEFT JOIN user_details ud ON ud.user_id = u.id
     WHERE DATE(d.tx_date) = ? AND u.role='agent'
     GROUP BY u.id, u.username, ud.wallet_address, ud.codepay_address

    UNION ALL

    SELECT 'Investor' AS role, u.id, u.username,
           ud.wallet_address, ud.codepay_address,
           COALESCE(SUM(d.investor_amount),0) AS amount
      FROM dividend d
      JOIN users u ON u.username = d.investor_username
      LEFT JOIN user_details ud ON ud.user_id = u.id
     WHERE DATE(d.tx_date) = ? AND u.role='investor'
     GROUP BY u.id, u.username, ud.wallet_address, ud.codepay_address

    UNION ALL

    SELECT 'Referral' AS role, u.id, u.username,
           ud.wallet_address, ud.codepay_address,
           COALESCE(SUM(d.referral_amount),0) AS amount
      FROM dividend d
      JOIN users u ON u.username = d.referral_username
      LEFT JOIN user_details ud ON ud.user_id = u.id
     WHERE DATE(d.tx_date) = ? AND u.role='referral'
     GROUP BY u.id, u.username, ud.wallet_address, ud.codepay_address

    ORDER BY role ASC, amount DESC, username ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit('쿼리 준비 실패: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param('sssss', $date, $date, $date, $date, $date);
$stmt->execute();
$result = $stmt->get_result();

// CSV (Excel 호환) 출력
$filename = "group_accounts_" . $date . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// UTF-8 BOM (엑셀 한글 깨짐 방지)
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Role', 'User ID', 'Username', 'Wallet Address', 'Codepay Address', 'Amount']);

while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        $date,
        $row['role'] ?? '',
        $row['id'] ?? '',
        $row['username'] ?? '',
        $row['wallet_address'] ?? '',
        $row['codepay_address'] ?? '',
        $row['amount'] ?? '0.00',
    ]);
}

fclose($out);
$stmt->close();
exit;
