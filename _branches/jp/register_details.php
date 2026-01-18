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

$user_id = $_SESSION['user_id'];

// DB 연결
$servername = "localhost";
$username   = "thek_db_admin";
$password   = "thek_pw_admin!";
$dbname     = "thek_next_db";

require_once "db_connect.php";
if ($conn->connect_error) {
    die(t('error.db_connect_failed','DB connection failed') . ": " . $conn->connect_error);
}

$error = "";

// user_details 존재 여부 확인
$stmt_check = $conn->prepare("SELECT 1 FROM user_details WHERE user_id = ?");
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$has_details = $stmt_check->get_result()->num_rows > 0;
$stmt_check->close();

// 폼 제출 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wallet_address = $_POST['wallet_address'];
    $broker_id      = $_POST['broker_id'];
    $broker_pw      = $_POST['broker_pw'];
    $xm_id          = $_POST['xm_id'];
    $xm_pw          = $_POST['xm_pw'];
    $xm_server      = $_POST['xm_server'];
    $ultima_id      = $_POST['ultima_id'];
    $ultima_pw      = $_POST['ultima_pw'];
    $ultima_server  = $_POST['ultima_server'];
    $referral_code  = $_POST['referral_code'];

    if (!$has_details) {
        $stmt = $conn->prepare("INSERT INTO user_details 
            (user_id, wallet_address, broker_id, broker_pw, xm_id, xm_pw, xm_server, ultima_id, ultima_pw, ultima_server, referral_code, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssssssss", $user_id, $wallet_address, $broker_id, $broker_pw, $xm_id, $xm_pw, $xm_server, $ultima_id, $ultima_pw, $ultima_server, $referral_code);
        if ($stmt->execute()) {
            redirect_by_role($conn, $user_id);
        } else {
            $error = t('error.save_failed_prefix', 'Save error:') . " " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "이미 추가 정보를 입력하셨습니다.";
    }
}

// 이미 정보가 있으면 바로 role 기반 이동
if ($has_details) {
    redirect_by_role($conn, $user_id);
}

$conn->close();

// role 기반 이동 함수 (users 테이블에서 조회)
function redirect_by_role($conn, $user_id) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        switch ($row['role']) {
            case "gm":       header("Location: gm_dashboard.php"); break;
            case "admin":    header("Location: admin_dashboard.php"); break;
            case "master":   header("Location: master_dashboard.php"); break;
            case "agent":    header("Location: agent_dashboard.php"); break;
            case "investor": header("Location: investor_dashboard.php"); break;
            default:         header("Location: login.php"); break;
        }
        exit;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('title.register_details', 'Register Details') ?></title>
    <style>

.error { color: red; margin-bottom: 10px; }
        input { width: 100%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; }
        button { background-color: #4CAF50; color: white; padding: 14px; border: none; cursor: pointer; width: 100%; }
    
</style>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
<div class="container">
    <h2><?= t('title.register_details', 'Register Details') ?></h2>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="register_details.php">
        <label><?= t('field.wallet_address', 'Wallet Address') ?></label>
        <input type="text" name="wallet_address" required>
        <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
        ?>
        <label><?= t('field.broker_id', 'Broker ID') ?></label>
        <input type="text" name="broker_id" required>
        <label><?= t('field.broker_password', 'Broker Password') ?></label>
        <input type="password" name="broker_pw" required>
        <label>XM ID</label>
        <input type="text" name="xm_id" required>
        <label><?= t('field.xm_password', 'XM Password') ?></label>
        <input type="password" name="xm_pw" required>
        <label><?= t('field.xm_server', 'XM Server') ?></label>
        <input type="text" name="xm_server" required>
        <label>ULTIMA ID</label>
        <input type="text" name="ultima_id" required>
        <label><?= t('field.ultima_password', 'ULTIMA Password') ?></label>
        <input type="password" name="ultima_pw" required>
        <label><?= t('field.ultima_server', 'ULTIMA Server') ?></label>
        <input type="text" name="ultima_server" required>
        <label><?= t('field.referral_code', 'Referral Code') ?></label>
        <input type="text" name="referral_code" required>
        <button type="submit"><?= t('btn.save', 'Save') ?></button>
    </form>
</div>
</body>
</html>

