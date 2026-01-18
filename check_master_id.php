<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    // 로그인 페이지로 리디렉션
    header("Location: login.php");
    exit;
}

include 'db_connect.php';
$id = $_GET['master_id'];
$sql = "SELECT COUNT(*) AS cnt FROM masters WHERE master_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo ($result['cnt'] > 0) ? "이미 사용 중인 ID입니다." : "사용 가능한 ID입니다.";
?>