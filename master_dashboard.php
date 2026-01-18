<?php
session_start();
include 'db_connect.php';

// 페이지 타이틀 및 콘텐츠 파일 지정
$page_title   = "마스터 대시보드";
$content_file = __DIR__ . "/master_dashboard_content.php";

include __DIR__ . "/layout.php";