<?php
// country.php
// A안 분리 이후: 기존 진입 URL 유지용 리다이렉트

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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$region = $_GET['region'] ?? 'korea';
if ($region !== 'korea') $region = 'korea';

header("Location: country_ready.php?region=" . urlencode($region));
exit;
