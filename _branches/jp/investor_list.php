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


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// Member query (only users with role = investor)
$sql = "SELECT id, username, email, role, phone FROM users WHERE role='investor'";
$result = mysqli_query($conn, $sql);

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title   = "title.list.investor";
    $content_file = __DIR__ . "/investor_list_content.php";
    include "layout.php";
}
