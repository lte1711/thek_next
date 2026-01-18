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

include 'db_connect.php';

// === language helper (NO t() here; keep logic unchanged) ===
$__md_lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ko');
$__md_lang = strtolower(trim((string)$__md_lang));
if (!in_array($__md_lang, ['ko','ja','en'], true)) $__md_lang = 'ko';
function __md_tr(string $ko, string $ja, string $en): string {
    global $__md_lang;
    if ($__md_lang === 'ja') return $ja;
    if ($__md_lang === 'en') return $en;
    return $ko;
}

// 페이지 타이틀 및 콘텐츠 파일 지정
$page_title = __md_tr("마스터 대시보드", "マスターダッシュボード", "Master Dashboard");
$content_file = __DIR__ . "/master_dashboard_content.php";

include __DIR__ . "/layout.php";