<?php
// 모든 에러 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
$page_title = $page_title ?? ($is_country_page ? "" : "대시보드");

// 본문 파일 지정
if (!isset($content_file) && !$is_country_page) {
    switch ($user_role) {
        case 'agent':
            $content_file = __DIR__ . "/agent_dividend_chart.php"; // ✅ 차트 콘텐츠 파일 지정
            $page_title = "에이전트 대시보드";
            break;
        // 다른 role은 기존대로 유지
        case 'gm':
            $content_file = __DIR__ . "/gm_dashboard_content.php";
            $page_title = "글로벌 마스터 대시보드";
            break;
        case 'admin':
            $content_file = __DIR__ . "/admin_dashboard.php";
            $page_title = "관리자 대시보드";
            break;
        case 'master':
            $content_file = __DIR__ . "/master_dashboard.php";
            $page_title = "마스터 대시보드";
            break;
        case 'investor':
        default:
            $content_file = __DIR__ . "/investor_dashboard.php";
            $page_title = "투자자 대시보드";
            break;
    }
}

include __DIR__ . "/layout.php"; // ✅ 레이아웃 파일 불러오기