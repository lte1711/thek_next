<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
include 'db_connect.php';

// 페이지 타이틀 및 레이아웃 적용
$page_title   = "추천정산";
$content_file = __DIR__ . "/referral_settlement_content.php";
$menu_type    = "investor";

include __DIR__ . "/layout.php";
