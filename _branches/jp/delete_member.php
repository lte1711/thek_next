<?php
session_start();

// Safe initialization
if (!function_exists('t')) {
    $i18n_path = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n_path)) {
        require_once $i18n_path;
    } else {
        function t($key, $fallback = null) {
            return $fallback ?? $key;
        }
        function current_lang() {
            return 'ko';
        }
    }
}

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// i18n
if (!function_exists('t')) {
    $i18n = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n)) require_once $i18n;
}


$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($user_id <= 0) { echo "<script>alert('" . addslashes(t('error.invalid_access')) . "'); window.history.back();</script>"; exit; }
error_reporting(E_ALL);
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB 연결 정보
$servername = "localhost";
$username   = "thek_db_admin";
$password   = "thek_pw_admin!";
$dbname     = "thek_next_db";

require_once "db_connect.php";
if ($conn->connect_error) {
    die(t('error.db_connect_failed','DB connection failed') . ": " . $conn->connect_error);
}

// 삭제할 회원 ID
$member_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 삭제 후 돌아갈 페이지 (어드민/마스터 구분 자동 처리)
$return_url = isset($_GET['return']) ? $_GET['return'] : 'member_list.php';

if ($member_id > 0) {

    // 0. sponsor_id 참조 해제 (FK 오류 방지)
    $sql_reset = "UPDATE users SET sponsor_id = NULL WHERE sponsor_id = ?";
    $stmt_reset = $conn->prepare($sql_reset);
    $stmt_reset->bind_param("i", $member_id);
    $stmt_reset->execute();
    $stmt_reset->close();

    // 1. user_details 삭제
    $sql_details = "DELETE FROM user_details WHERE user_id = ?";
    $stmt_details = $conn->prepare($sql_details);
    $stmt_details->bind_param("i", $member_id);
    $stmt_details->execute();
    $stmt_details->close();

    // 2. users 삭제
    $sql_user = "DELETE FROM users WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $member_id);

    if ($stmt_user->execute()) {
        $msg = addslashes(t('msg.deleted'));
        echo "<script>
                alert('{$msg}');
                window.location.href = '{$return_url}';
              </script>";
    } else {
        $msg = addslashes(t('error.delete_failed'));
        $err = addslashes($stmt_user->error);
        echo "<script>
                alert('{$msg}: {$err}');
                window.location.href = '{$return_url}';
              </script>";
    }

    $stmt_user->close();

} else {

    $msg = addslashes(t('error.invalid_access'));
    echo "<script>
            alert('{$msg}');
            window.location.href = '{$return_url}';
          </script>";

}

$conn->close();
?>// ===== 삭제 처리 (FK 안전) =====
$conn->begin_transaction();
try {
    // 1) 자식 테이블 먼저 삭제 (FK)
    $stmt = $conn->prepare("DELETE FROM user_rejects WHERE user_id = ?");
    if (!$stmt) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // 2) users 삭제
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if (!$stmt) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // 실제 삭제 여부 확인 (0이면 삭제 안 된 것)
    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new Exception("User not deleted (affected_rows=" . $conn->affected_rows . ")");
    }
    $stmt->close();

    $conn->commit();
    echo "<script>alert('" . addslashes(t('msg.deleted')) . "'); location.href='member_list.php';</script>";
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    // 운영 환경이면 아래 메시지를 사용자에게 노출하지 않도록 변경 가능
    echo "<script>alert('" . addslashes(t('error.delete_failed')) . ": " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    exit;
}
// ===== 삭제 처리 끝 =====
