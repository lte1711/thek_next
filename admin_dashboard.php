<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'db_connect.php';

// 기준 날짜: 전일
$target_date = date('Y-m-d', strtotime('-1 day'));

// 1. 지역별 마스터 전일 매출 합계 (dividend 테이블 mastr_amount 사용)
$sql_master_sales = "
    SELECT u.country, SUM(d.mastr_amount) AS total_sales
    FROM dividend d
    JOIN users u ON d.user_id = u.id
    WHERE DATE(d.tx_date) = ? AND d.mastr_username IS NOT NULL
    GROUP BY u.country
";
$stmt = $conn->prepare($sql_master_sales);
$stmt->bind_param("s", $target_date);
$stmt->execute();
$result_master_sales = $stmt->get_result();

$country_sales = [];
while($row = $result_master_sales->fetch_assoc()) {
    $country_sales[$row['country']] = (float)$row['total_sales'];
}
$stmt->close();

// 2. 지역별 마스터 전일 매출 상세 내역
$sql_master_detail = "
    SELECT u.country, d.mastr_username AS username, SUM(d.mastr_amount) AS total_sales
    FROM dividend d
    JOIN users u ON d.user_id = u.id
    WHERE DATE(d.tx_date) = ? AND d.mastr_username IS NOT NULL
    GROUP BY u.country, d.mastr_username
    ORDER BY u.country
";
$stmt = $conn->prepare($sql_master_detail);
$stmt->bind_param("s", $target_date);
$stmt->execute();
$result_master_detail = $stmt->get_result();

$detail_data = [];
while($row = $result_master_detail->fetch_assoc()) {
    $detail_data[$row['country']][] = [
        'username' => $row['username'],
        'sales'    => (float)$row['total_sales']
    ];
}
$stmt->close();

// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title   = "관리자 메인 화면";
    $content_file = __DIR__ . "/admin_dashboard_content.php";
    include "layout.php";
}