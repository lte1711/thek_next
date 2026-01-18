<?php
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 내가 등록한 회원들 조회 (referrer_id 기준)
$sql = "SELECT id, username, email, phone, role, created_at 
        FROM users 
        WHERE referrer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 페이지 타이틀
$page_title   = function_exists('t') ? t('page.referral_list', 'Referral List') : "Referral List";
$content_file = "referral_list_content.php"; // 본문 분리
$menu_type    = "investor"; // 투자자 메뉴 사용
include "layout.php";