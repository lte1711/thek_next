<?php
session_start();

// ✅ 통합 수정: 기존 create_*.php?id=... 수정 진입은 create_account.php (2-step)로 이동
$id = $_GET['id'] ?? null;
$redirect = $_GET['redirect'] ?? null;
if ($id !== null && $id !== '' && ctype_digit((string)$id)) {
    $qs = "mode=edit&id=" . urlencode((string)$id);
    if ($redirect) $qs .= "&redirect=" . urlencode((string)$redirect);
    header("Location: create_account.php?$qs");
    exit;
}


// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 1. 회원 ID 결정: POST → GET → SESSION 순서로 확인
$id = $_POST['id'] ?? ($_GET['id'] ?? ($_SESSION['user_id'] ?? null));

if (!$id) {
    die("회원 ID가 지정되지 않았습니다.");
}

// 2. 회원 정보 조회
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if (!$member) {
    die("해당 회원을 찾을 수 없습니다.");
}

$message = "";
$error   = "";

// 3. 수정 처리 (POST로 넘어온 값이 있을 때만 실행)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone']; // 전화번호만 수정 가능

    $update_sql = "UPDATE users SET username=?, email=?, phone=? WHERE id=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $username, $email, $phone, $id);

    if ($update_stmt->execute()) {
        $message = "회원 정보가 성공적으로 수정되었습니다.";
        // 수정 후 다시 조회
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "수정 중 오류가 발생했습니다.";
    }
}

// 4. 폼에 표시할 값: DB에서 조회한 값 사용
$form_username = $member['username'];
$form_email    = $member['email'];
$form_phone    = $member['phone'] ?? '';
$form_country  = $member['country']; // 읽기 전용
$form_role     = $member['role'];    // 읽기 전용
$form_referral_code= $member['referral_code']; // ✅ 레퍼럴 코드 읽기 전용


// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET" || $_SERVER["REQUEST_METHOD"] === "POST") {
    $page_title   = "회원 정보 수정";
    $content_file = __DIR__ . "/edit_agent_content.php";
    include "layout.php";
}