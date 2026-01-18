<?php
ob_start();
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


$page_title = "GM Dashboard";
$content_file = "gm_dashboard_content.php";
include "layout.php";