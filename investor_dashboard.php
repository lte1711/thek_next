<?php

session_start();
// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// 역할 확인: investor만 접근 가능 (직접 접근/권한 우회 방지)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'investor') {
    header("Location: login.php");
    exit;
}
include 'db_connect.php';

// 페이지 타이틀 및 레이아웃 적용
$page_title   = "Investor Dashboard";
$content_file = __DIR__ . "/investor_dashboard_content.php";
$menu_type    = "investor";

include __DIR__ . "/layout.php";