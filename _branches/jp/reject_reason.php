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

$user_id = $_POST['user_id'] ?? ($_GET['id'] ?? null);
$reason  = $_POST['reason'] ?? '';

// ----------------------------------------------------
// 1. user_id 값이 없으면 register_details.php로 이동
// ----------------------------------------------------
if (!$user_id) {
    header("Location: register_details.php");
    exit();
}

// ----------------------------------------------------
// 2. user_id와 reason 값이 모두 있으면 user_rejects에 저장
// ----------------------------------------------------
if ($reason) {
    $stmt = $conn->prepare("INSERT INTO user_rejects (user_id, reason) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $reason);
    $stmt->execute();
    $stmt->close();
}

// ----------------------------------------------------
// 3. user_details 테이블에 user_id 존재 여부 확인
// ----------------------------------------------------
$stmt = $conn->prepare("SELECT 1 FROM user_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // user_details에 없으면 register_details.php로 id값과 함께 이동
    header("Location: register_details.php?id=" . urlencode($user_id));
    exit();
}

// ----------------------------------------------------
// 4. 존재하면 users 테이블에서 role 조회 후 대시보드 이동
// ----------------------------------------------------
$stmt_role = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt_role->bind_param("i", $user_id);
$stmt_role->execute();
$result_role = $stmt_role->get_result();

if ($row = $result_role->fetch_assoc()) {
    $role = $row['role'];

    switch ($role) {
        case "gm":
            header("Location: gm_dashboard.php");
            break;
        case "admin":
            header("Location: admin_dashboard.php");
            break;
        case "master":
            header("Location: master_dashboard.php");
            break;
        case "agent":
            header("Location: agent_dashboard.php");
            break;
        case "investor":
            header("Location: investor_dashboard.php");
            break;
        default:
            header("Location: login.php"); // 정의되지 않은 role이면 로그인 페이지로
            break;
    }
    exit();
} else {
    echo t('error.user_not_found','User not found.');
}

$stmt_role->close();
$stmt->close();
$conn->close();
?>
