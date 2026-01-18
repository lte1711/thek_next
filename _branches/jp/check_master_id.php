<?php
// 운영(프로덕션)에서는 점검/유틸 스크립트 접근을 차단합니다.
// 필요 시 config.php에서 DEBUG_MODE=true로 설정한 뒤 사용하세요.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    http_response_code(404);
    exit;
}


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

echo ($result['cnt'] > 0) ? t('err.username_exists','ID is already in use.') : t('msg.username_available','ID is available.');
?>