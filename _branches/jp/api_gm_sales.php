<?php
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
