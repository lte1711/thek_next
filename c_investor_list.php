<?php
session_start();

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 회원 조회 쿼리 (등급이 investor인 회원만)
// 로그인한 회원 '하위(밑)' 투자자만 조회 (본인 제외)
$login_id = (int)$_SESSION['user_id'];

// sponsor_id 기반 하위 트리 추출 (MySQL 8+ recursive CTE)
$sql = "WITH RECURSIVE downline AS (
            SELECT id
            FROM users
            WHERE id = ?
            UNION ALL
            SELECT u.id
            FROM users u
            INNER JOIN downline d ON u.sponsor_id = d.id
        )
        SELECT u.id, u.username, u.email, u.role, u.phone, u.country
        FROM users u
        INNER JOIN downline d ON u.id = d.id
        WHERE u.role = 'investor'
          AND u.id <> ?
        ORDER BY u.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("쿼리 준비 오류: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt, "ii", $login_id, $login_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("쿼리 실행 오류: " . mysqli_error($conn));
}

if (!$result) {
    die("쿼리 실행 오류: " . mysqli_error($conn));
}

// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title   = "INVESTOR 회원 리스트";
    $content_file = __DIR__ . "/c_investor_list_content.php";
    include "layout.php";
}