<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB 연결
include 'db_connect.php';

// 회원 조회 쿼리 (등급이 master인 회원만)
$sql = "SELECT id, username, email, role, phone FROM users WHERE role='master'";
$result = mysqli_query($conn, $sql);

// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title   = "MASTER 회원 리스트";
    $content_file = __DIR__ . "/master_list_content.php";
    include "layout.php";
}
