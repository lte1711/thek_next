<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    // 로그인 페이지로 리디렉션
    header("Location: login.php");
    exit;
}

include 'db_connect.php';
header('Content-Type: application/json');

$sql = "SELECT region, sales_amount FROM gm_sales_daily WHERE sales_date = CURDATE() - INTERVAL 1 DAY";
$result = $conn->query($sql);

$data = ['labels' => [], 'sales' => []];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['region'];
        $data['sales'][] = (float)$row['sales_amount'];
    }
}

$conn->close();
echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
