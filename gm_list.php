<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB 연결
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $page_title = "CREATE ACCOUNT";
    $content_file = __DIR__ . "/gm_list_content.php";
    include "layout.php";
}
