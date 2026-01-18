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
    die(t('error.invalid_request','Invalid request.'));
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
        error_log(t('err.reject_update_failed_prefix', 'Reject update failed:') . " " . $stmt->error);
        echo "<h3><?= t('error.update_failed','An error occurred while updating.') ?></h3>";
    }
} else {
    error_log("쿼리 준비 실패: " . $conn->error);
    echo "<h3><?= t('error.query_prepare_failed','An error occurred while preparing the query.') ?></h3>";
}
?>