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
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 로그인한 회원(Agent) 하위(직속 sponsor) 투자자만 조회
$login_id = (int)$_SESSION['user_id'];

$sql = "SELECT id, username, email, phone, country, role
        FROM users
        WHERE role = 'investor' AND sponsor_id = ?
        ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die(t('err.query_prepare_failed_prefix','Query prepare failed: ') . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "i", $login_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die(t('err.query_execute_failed_prefix','Query execute failed: ') . mysqli_error($conn));
}

// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title   = "investor_list.title"; // layout.php가 '.' 포함 문자열을 t()로 자동 번역
    $content_file = __DIR__ . "/c_investor_list_content.php";
    include "layout.php";
}
