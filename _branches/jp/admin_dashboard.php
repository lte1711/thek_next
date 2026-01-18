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


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 🔒 language-only title control (NO t(), NO logic change)
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ko');
if (!in_array($lang, ['ko','ja','en'], true)) $lang = 'ko';

switch ($lang) {
    case 'ja':
        $page_title = t('title.dashboard.admin','Admin Dashboard');
        break;
    case 'en':
        $page_title = 'Admin Main Screen';
        break;
    default:
        $page_title = t('title.admin.main','Admin Main');
}

$content_file = __DIR__ . "/admin_dashboard_content.php";
include "layout.php";
