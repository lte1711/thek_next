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

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (strtolower((string)$_SESSION['role']) !== 'gm') {
    http_response_code(403);
    exit(t('error.forbidden', 'Forbidden'));
}

$root_id = (int)$_SESSION['user_id'];

$page_title = t('title.org_chart', '조직도 관리');
$content_file = __DIR__ . '/org_chart_content.php';

include __DIR__ . '/layout.php';
