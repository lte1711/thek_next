<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);
/**
 * GM 접근 제한
 * - 세션에 role이 있으면 그걸 사용
 * - 없으면 users 테이블에서 user_id로 role 조회
 */
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
 * ✅ 달력 기준 월(YYYY-MM)
 */
$year_month = $_GET['year_month'] ?? date('Y-m');

// 유효성(간단)
if (!preg_match('/^\d{4}-\d{2}$/', $year_month)) {
    $year_month = date('Y-m');
}

/**
 * ✅ 선택 날짜(YYYY-MM-DD)
 * - 없으면: 기본값 = 월의 1일(달력만 볼 때)
 */
$settle_date = $_GET['settle_date'] ?? ($year_month . '-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $settle_date)) {
    $settle_date = $year_month . '-01';
}
// ✅ year_month와 settle_date가 다르면(월 이동 시) 선택일을 해당 월 1일로 보정
if (substr($settle_date, 0, 7) !== $year_month) {
    $settle_date = $year_month . '-01';
}
/**
 * ✅ 월 요약 데이터: 달력에 찍을 날짜별 Total
 * - dividend 테이블 기준
 */
$monthly_sql = "
    SELECT
        DATE(tx_date) AS sales_date,
        COALESCE(SUM(gm1_amount), 0) AS gm1,
        COALESCE(SUM(gm2_amount), 0) AS gm2,
        COALESCE(SUM(gm3_amount), 0) AS gm3,
        COALESCE(SUM(admin_amount), 0) AS admin,
        COALESCE(SUM(mastr_amount), 0) AS master,
        COALESCE(SUM(agent_amount), 0) AS agent,
        COALESCE(SUM(investor_amount), 0) AS investor,
        COALESCE(SUM(referral_amount), 0) AS referral
    FROM dividend
    WHERE DATE_FORMAT(tx_date, '%Y-%m') = ?
    GROUP BY sales_date
";
$stmt = $conn->prepare($monthly_sql);
$stmt->bind_param("s", $year_month);
$stmt->execute();
$monthly_result = $stmt->get_result();

$calendar_data = []; // ['YYYY-MM-DD' => total]
while ($row = $monthly_result->fetch_assoc()) {
    $date = $row['sales_date'];
    $total = (float)$row['gm1'] + (float)$row['gm2'] + (float)$row['gm3']
           + (float)$row['admin'] + (float)$row['master'] + (float)$row['agent']
           + (float)$row['investor'] + (float)$row['referral'];
    $calendar_data[$date] = $total;
}
$stmt->close();


/**
 * ✅ 정산 완료 날짜 목록 (달력 표시용)
 * admin_sales_daily: sales_date, settled(0/1)
 */
$settled_dates = [];
$is_settled = false;

$settled_sql = "
    SELECT DISTINCT sales_date
    FROM admin_sales_daily
    WHERE settled = 1
      AND DATE_FORMAT(sales_date, '%Y-%m') = ?
";

$stmt_settled = $conn->prepare($settled_sql);
$stmt_settled->bind_param("s", $year_month);
$stmt_settled->execute();
$res_settled = $stmt_settled->get_result();

while ($r = $res_settled->fetch_assoc()) {
    $settled_dates[$r['sales_date']] = true;
}
$stmt_settled->close();

// 선택 날짜가 정산 완료인지
$is_settled = isset($settled_dates[$settle_date]);



/**
 * ✅ 선택 날짜 GM 상세표 데이터 (아이디/지갑/금액)
 * - dividend에서 gm1/gm2/gm3 금액을 날짜로 합산
 * - username → users/user_details로 wallet 조회
 */
$gm_rows = [];
$gm_total = 0.0;

$day_sql = "
    SELECT
        MAX(gm1_username) AS gm1_username,
        MAX(gm2_username) AS gm2_username,
        MAX(gm3_username) AS gm3_username,
        COALESCE(SUM(gm1_amount), 0) AS gm1_amount,
        COALESCE(SUM(gm2_amount), 0) AS gm2_amount,
        COALESCE(SUM(gm3_amount), 0) AS gm3_amount
    FROM dividend
    WHERE DATE(tx_date) = ?
";
$stmt = $conn->prepare($day_sql);
$stmt->bind_param("s", $settle_date);
$stmt->execute();
$day = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($day) {
    $gm_usernames = array_values(array_filter([
        $day['gm1_username'] ?? null,
        $day['gm2_username'] ?? null,
        $day['gm3_username'] ?? null,
    ]));

    // username -> wallet map
    $wallet_map = [];
    if (count($gm_usernames) > 0) {
        $placeholders = implode(',', array_fill(0, count($gm_usernames), '?'));
        $types = str_repeat('s', count($gm_usernames));

        $sql = "
            SELECT u.username, ud.wallet_address
            FROM users u
            LEFT JOIN user_details ud ON ud.user_id = u.id
            WHERE u.username IN ($placeholders)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$gm_usernames);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $wallet_map[$r['username']] = $r['wallet_address'] ?? '';
        }
        $stmt->close();
    }

    // rows 구성
    $pairs = [
        [$day['gm1_username'] ?? '', (float)($day['gm1_amount'] ?? 0)],
        [$day['gm2_username'] ?? '', (float)($day['gm2_amount'] ?? 0)],
        [$day['gm3_username'] ?? '', (float)($day['gm3_amount'] ?? 0)],
    ];

    foreach ($pairs as [$uname, $amt]) {
        if ($uname === '') continue;
        $gm_rows[] = [
            'username' => $uname,
            'wallet'   => $wallet_map[$uname] ?? '',
            'amount'   => $amt,
        ];
        $gm_total += $amt;
    }
}

/**
 * ✅ 레이아웃 출력
 * - 기존 layout.php를 그대로 사용
 * - partner_accounts.css를 그대로 쓰고 싶어서 page_css 고정
 */
$page_title   = "파트너 정산 (GM) - ver2";
$page_css     = "partner_accounts.css"; // 기존 파트너정산 스타일 재사용
$content_file = __DIR__ . "/partner_accounts_v2_content.php";
include "layout.php";
