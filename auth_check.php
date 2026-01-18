<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 현재 로그인한 사용자 정보 가져오기
$user_id = $_SESSION['user_id'];
$sql = "SELECT email, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// 조건 확인: 이메일과 role
if ($user['email'] !== 'lte1711@gmail.com' || $user['role'] !== 'superadmin') {
    // 조건 불일치 → 로그인 페이지로 이동
    header("Location: ../login.php");
    exit;
}
