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

// i18n
if (!function_exists('t')) {
    $i18n = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n)) require_once $i18n;
}


// 로그인 여부 확인
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// GM 권한만 접근 가능
if ($_SESSION['role'] !== 'gm') {
    echo "<script>alert(t('common.no_permission','You do not have permission.')); window.location.href='gm_dashboard.php';</script>";
    exit;
}

include 'db_connect.php';

// 날짜 및 조회 유형 파라미터 처리
$report_type   = $_GET['type'] ?? 'day';   // day, week, month, year
$report_date   = $_GET['date'] ?? date('Y-m-d');
$selected_role = $_GET['role'] ?? 'gm';    // gm, admin, master, agent, investor

// 페이지 타이틀과 본문 파일 지정
$page_title   = "글로벌 마스터 Revenue Report";
$content_file = __DIR__ . "/gm_revenue_report_content.php";

// 레이아웃 적용
include "layout.php";