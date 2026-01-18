<?php
// country.php
// A안 분리 이후: 기존 진입 URL 유지용 리다이렉트

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$region = $_GET['region'] ?? 'korea';
if ($region !== 'korea') $region = 'korea';

header("Location: country_ready.php?region=" . urlencode($region));
exit;
