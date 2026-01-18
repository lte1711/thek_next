<?php
// 모든 에러 표시
error_reporting(E_ALL);
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');

ob_start();
if (session_status() === PHP_SESSION_NONE) {
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

}

// role 값 확인
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['role'];
$username  = $_SESSION['username'] ?? null;

// ✅ username이 Zayne이면 바로 korea 화면으로 이동
if ($username === 'Zayne') {
    header("Location: country.php?region=korea");
    exit;
}

$is_country_page = $is_country_page ?? false;
$page_title = $page_title ?? ($is_country_page ? "" : t('title.dashboard','Dashboard'));

// 본문 파일 지정
if (!isset($content_file) && !$is_country_page) {
    switch ($user_role) {
        case 'agent':
            $content_file = __DIR__ . "/agent_dividend_chart.php";
            $page_title = "title.agent.dashboard";
            break;
        // 다른 role은 기존대로 유지
        case 'gm':
            $content_file = __DIR__ . "/gm_dashboard_content.php";
            $page_title = "title.gm.dashboard";
            break;
        case 'admin':
            $content_file = __DIR__ . "/admin_dashboard.php";
            $page_title = "admin.dashboard";
            break;
        case 'master':
            $content_file = __DIR__ . "/master_dashboard.php";
            $page_title = "master.dashboard";
            break;
        case 'investor':
        default:
            $content_file = __DIR__ . "/investor_dashboard.php";
            $page_title = "investor.dashboard";
            break;
    }
}

include __DIR__ . "/layout.php"; // ✅ 레이아웃 파일 불러오기