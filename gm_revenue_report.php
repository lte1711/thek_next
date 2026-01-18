<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// GM 권한만 접근 가능
if ($_SESSION['role'] !== 'gm') {
    echo "<script>alert('접근 권한이 없습니다.'); window.location.href='gm_dashboard.php';</script>";
    exit;
}

include 'db_connect.php';

// 날짜 및 조회 유형 파라미터 처리
$report_type   = $_GET['type'] ?? 'day';   // day, week, month, year
$report_date   = $_GET['date'] ?? date('Y-m-d');
$selected_role = $_GET['role'] ?? 'gm';    // gm, admin, master, agent, investor

// 페이지 타이틀과 본문 파일 지정
$page_title   = "글로벌 마스터 Revenue Report";
$content_file = __DIR__ . "/gm_revenue_report_content.php";

// 레이아웃 적용
include "layout.php";