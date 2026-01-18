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

// Member query (only users with role = agent)
$sql = "SELECT id, username, email, role, phone FROM users WHERE role='agent'";
$result = mysqli_query($conn, $sql);

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Pass title key only
    $page_title   = "title.list.agent";
    $content_file = __DIR__ . "/agent_list_content.php";
    include "layout.php";
}
