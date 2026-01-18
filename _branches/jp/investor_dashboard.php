<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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


include 'db_connect.php';

// 페이지 타이틀 및 레이아웃 적용
$page_title   = t('menu.investor.dashboard', 'Investor Dashboard');
$content_file = __DIR__ . "/investor_dashboard_content.php";
$menu_type    = "investor";

include __DIR__ . "/layout.php";