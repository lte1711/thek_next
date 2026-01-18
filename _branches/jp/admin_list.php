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

// DB 연결
include 'db_connect.php';

// 회원 조회 쿼리 (등급이 admin인 회원만)
$sql = "SELECT id, username, email, role, phone FROM users WHERE role='admin'";
$result = mysqli_query($conn, $sql);

// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Pass title key only (layout.php will translate safely after i18n is loaded)
    $page_title   = "title.list.admin";
    $content_file = __DIR__ . "/admin_list_content.php";
    include "layout.php";
}
