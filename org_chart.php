<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if (strtolower((string)$_SESSION['role']) !== 'gm') {
    http_response_code(403);
    exit('Forbidden');
}

$root_id = (int)$_SESSION['user_id'];

$page_title = '조직도 관리';
$content_file = __DIR__ . '/org_chart_content.php';

include __DIR__ . '/layout.php';
