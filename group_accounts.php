<?php
session_start();

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
    exit('GM만 접근 가능합니다.');
}

/**
 * ✅ 핵심: 파라미터 분리 (한쪽 조회가 다른 테이블을 바꾸지 않게)
 * - 위(데일리 테이블): daily_date
 * - 아래(리스트 테이블): list_period
 * (기존 settle_date/period 파라미터도 들어올 수 있어 호환 처리)
 */
$settle_date = $_GET['daily_date'] ?? ($_GET['settle_date'] ?? date('Y-m-d', strtotime('-1 day')));
$period      = $_GET['list_period'] ?? ($_GET['period'] ?? 'daily');
// ✅ 선택 날짜 정산 완료 여부 (admin_sales_daily 기준)
$is_settled = false;
$settled_at = null;

$chk = $conn->prepare("
    SELECT COUNT(*) AS cnt, MIN(settled_at) AS settled_at
    FROM admin_sales_daily
    WHERE sales_date = ? AND settled = 1
");
$chk->bind_param("s", $settle_date);
$chk->execute();
$r = $chk->get_result()->fetch_assoc();
$chk->close();

if (!empty($r) && (int)$r['cnt'] > 0) {
    $is_settled = true;
    $settled_at = $r['settled_at'];
}
/**
 * ✅ GM 제외 합계(조직 합계):
 * admin_amount + mastr_amount + agent_amount + investor_amount + referral_amount
 */
$sum_ex_gm_expr = "(COALESCE(d.admin_amount,0) + COALESCE(d.mastr_amount,0) + COALESCE(d.agent_amount,0) + COALESCE(d.investor_amount,0) + COALESCE(d.referral_amount,0))";

// 1) 어드민 데일리 정산 (admin별: GM 제외 합계)
$sql = "SELECT 
            u.id,
            u.username,
            ud.wallet_address,
            ud.codepay_address,
            COALESCE(SUM($sum_ex_gm_expr), 0) AS total_sales
        FROM users u
        LEFT JOIN user_details ud
            ON ud.user_id = u.id
        LEFT JOIN dividend d 
            ON d.admin_username = u.username
           AND DATE(d.tx_date) = ?
        WHERE u.role = 'admin'
        GROUP BY u.id, u.username, ud.wallet_address, ud.codepay_address
        ORDER BY total_sales DESC, u.username ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $settle_date);
$stmt->execute();
$result = $stmt->get_result();

$admin_total = 0.0;
$admin_data  = [];
while($row = $result->fetch_assoc()) {
    $row['total_sales'] = (float)$row['total_sales'];
    $admin_data[] = $row;
    $admin_total += $row['total_sales'];
}
$stmt->close();

// 2) 어드민 정산 리스트 (일/주/월) - admin별: GM 제외 합계
if ($period == 'weekly') {
    // ISO 주차 고정(월요일 시작): YEARWEEK(date, 3)
    $group_sql = "SELECT YEARWEEK(d.tx_date, 3) AS period, u.id, u.username, SUM(
                    COALESCE(d.admin_amount,0) + COALESCE(d.mastr_amount,0) + COALESCE(d.agent_amount,0) + COALESCE(d.investor_amount,0) + COALESCE(d.referral_amount,0)
                  ) AS total_sales
                  FROM dividend d
                  JOIN users u ON d.admin_username = u.username
                  WHERE u.role='admin'
                  GROUP BY YEARWEEK(d.tx_date, 3), u.id, u.username
                  ORDER BY period DESC";
} elseif ($period == 'monthly') {
    $group_sql = "SELECT DATE_FORMAT(d.tx_date, '%Y-%m') AS period, u.id, u.username, SUM(
                    COALESCE(d.admin_amount,0) + COALESCE(d.mastr_amount,0) + COALESCE(d.agent_amount,0) + COALESCE(d.investor_amount,0) + COALESCE(d.referral_amount,0)
                  ) AS total_sales
                  FROM dividend d
                  JOIN users u ON d.admin_username = u.username
                  WHERE u.role='admin'
                  GROUP BY DATE_FORMAT(d.tx_date, '%Y-%m'), u.id, u.username
                  ORDER BY period DESC";
} else {
    $group_sql = "SELECT DATE(d.tx_date) AS period, u.id, u.username, SUM(
                    COALESCE(d.admin_amount,0) + COALESCE(d.mastr_amount,0) + COALESCE(d.agent_amount,0) + COALESCE(d.referral_amount,0) + COALESCE(d.investor_amount,0)
                  ) AS total_sales
                  FROM dividend d
                  JOIN users u ON d.admin_username = u.username
                  WHERE u.role='admin'
                  GROUP BY DATE(d.tx_date), u.id, u.username
                  ORDER BY period DESC";
}

$group_result = mysqli_query($conn, $group_sql);

$group_data = [];
if ($group_result) {
    while($row = mysqli_fetch_assoc($group_result)) {
        $row['total_sales'] = (float)$row['total_sales'];
        $group_data[$row['period']][] = $row;
    }
} else {
    $group_data = [];
}

// 레이아웃
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title   = "조직 정산 (GM 제외 합계)";
    $content_file = __DIR__ . "/group_accounts_content.php";
    include "layout.php";
}
