<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
if (!($username === 'Zayne' || $role === 'superadmin')) {
    http_response_code(403);
    echo "접근 권한이 없습니다.";
    exit;
}

include 'db_connect.php';

$region = $_GET['region'] ?? 'korea';
if ($region !== 'korea') $region = 'korea';

// ✅ (P / S) Partner Daily Settlement 위젯: 기존 country.php 로직 재사용
$partner_date = $_GET['partner_date'] ?? date('Y-m-d', strtotime('-1 day'));

// GM1~GM3 이름 매핑 (users.role='gm' id 오름차순 기준)
$gm_names = ['GM1' => '-', 'GM2' => '-', 'GM3' => '-'];
$gm_list = [];
$rs = mysqli_query($conn, "SELECT id, username, name FROM users WHERE role='gm' ORDER BY id ASC LIMIT 3");
if ($rs) {
    while ($u = mysqli_fetch_assoc($rs)) {
        $gm_list[] = $u;
    }
}
for ($i = 0; $i < 3; $i++) {
    $label = 'GM' . ($i + 1);
    if (!empty($gm_list[$i])) {
        $gm_names[$label] = $gm_list[$i]['name'] ?: $gm_list[$i]['username'] ?: '-';
    }
}

// ✅ 선택 날짜의 dividend 데이터를 "행 단위"로 조회
// - 같은 날짜에 여러 건이 있으면 각각 1행으로 출력
// - Total Revenue: 해당 행의 배당금 전체 합 (gm1~gm3 + admin/master/agent/investor/referral)
// - profit(20%): Total Revenue의 20%

$dividend_rows = [];
$total_revenue_sum = 0.0;
$profit20_sum = 0.0;

$sql_rows = "
    SELECT
        id, tx_date,
        gm1_username, gm1_amount,
        gm2_username, gm2_amount,
        gm3_username, gm3_amount,
        admin_amount, mastr_amount, agent_amount, investor_amount, referral_amount
    FROM dividend
    WHERE DATE(tx_date)=?
    ORDER BY tx_date ASC, id ASC
";

$st_rows = $conn->prepare($sql_rows);
$st_rows->bind_param('s', $partner_date);
$st_rows->execute();
$rs_rows = $st_rows->get_result();
while ($r = $rs_rows->fetch_assoc()) {
    $row_total =
        (float)$r['gm1_amount'] + (float)$r['gm2_amount'] + (float)$r['gm3_amount'] +
        (float)$r['admin_amount'] + (float)$r['mastr_amount'] + (float)$r['agent_amount'] +
        (float)$r['investor_amount'] + (float)$r['referral_amount'];

    $row_profit20 = $row_total * 0.20;

    $r['row_total_revenue'] = $row_total;
    $r['row_profit20'] = $row_profit20;

    $dividend_rows[] = $r;
    $total_revenue_sum += $row_total;
    $profit20_sum += $row_profit20;
}
$st_rows->close();

// 정산 완료 여부 (gm_sales_daily)
$partner_is_settled = false;
$partner_settled_at = null;
$st_s = $conn->prepare("SELECT COUNT(*) AS cnt, MAX(settled_at) AS settled_at FROM gm_sales_daily WHERE (sales_date=? OR DATE(sales_date)=?) AND settled=1");
$st_s->bind_param('ss', $partner_date, $partner_date);
$st_s->execute();
$sr_s = $st_s->get_result()->fetch_assoc();
$st_s->close();
if (!empty($sr_s) && (int)($sr_s['cnt'] ?? 0) > 0) {
    $partner_is_settled = true;
    $partner_settled_at = $sr_s['settled_at'] ?? null;
}

$is_country_page = true;
$page_title = "";
$page_css = 'korea.css';
$content_file = __DIR__ . "/country_profit_share_content.php";
include "layout.php";
