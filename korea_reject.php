<?php
session_start();
include 'db_connect.php';

// 🔒 담당자 전용 접근 제한 (필요 시 주석 해제)
// if ($_SESSION['role'] !== 'gm' || $_SESSION['username'] !== 'Zayne') {
//     header("Location: login.php");
//     exit();
// }

// ✅ 입력값 검증
$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$reason = trim($_POST['reason'] ?? '');

if (!$id || $reason === '') {
    die("잘못된 요청입니다.");
}

$sql = "UPDATE korea_ready_trading 
        SET reject_reason = ?, reject_by = ?, reject_date = NOW(), status = 'rejected' 
        WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssi", $reason, $_SESSION['username'], $id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // ✅ 성공 후 리다이렉트 (파일명은 프로젝트 구조에 맞게 조정)
        header("Location: country.php?region=korea&user_id=" . $_SESSION['user_id']);
        exit;
    } else {
        error_log("Reject 업데이트 실패: " . $stmt->error);
        echo "<h3>업데이트 중 오류가 발생했습니다.</h3>";
    }
} else {
    error_log("쿼리 준비 실패: " . $conn->error);
    echo "<h3>쿼리 준비 중 오류가 발생했습니다.</h3>";
}
?>