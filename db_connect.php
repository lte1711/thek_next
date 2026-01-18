<?php
$servername = "localhost";
$username = "thek_db_admin"; // MySQL 사용자 이름
$password = "thek_pw_admin!"; // 비밀번호
$dbname = "thek_next_db";

// MySQLi를 사용한 데이터베이스 연결
$conn = new mysqli($servername, $username, $password, $dbname);

// 연결 확인
if ($conn->connect_error) {
    // 연결 실패 시 스크립트 중단
    die("Connection failed: " . $conn->connect_error);
}
?>
