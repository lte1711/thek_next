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


// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB 연결
require_once "db_connect.php";
if ($conn->connect_error) {
    die(t('error.db_connect_failed','DB connection failed') . ": " . $conn->connect_error);
}

// 로그인된 사용자 ID (GET 강제 적용)
$user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

// 본인 데이터 조회
$sql = "SELECT u.username, u.email, u.phone, u.country,
               d.wallet_address,
               d.xm_id, d.xm_pw, d.xm_server,
               d.ultima_id, d.ultima_pw, d.ultima_server,
               d.broker1_id, d.broker1_pw, d.broker1_server,
               d.broker2_id, d.broker2_pw, d.broker2_server,
               d.selected_broker,
               d.referral_code
        FROM users u
        LEFT JOIN user_details d ON u.id = d.user_id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// 수정 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email           = $_POST['email'];
    $phone           = $_POST['phone'];
    $wallet          = $_POST['wallet_address'];
    $xm_id           = $_POST['xm_id'];
    $xm_pw           = $_POST['xm_pw'];
    $xm_server       = $_POST['xm_server'];
    $ultima_id       = $_POST['ultima_id'];
    $ultima_pw       = $_POST['ultima_pw'];
    $ultima_server   = $_POST['ultima_server'];
    $broker1_id      = $_POST['broker1_id'];
    $broker1_pw      = $_POST['broker1_pw'];
    $broker1_server  = $_POST['broker1_server'];
    $broker2_id      = $_POST['broker2_id'];
    $broker2_pw      = $_POST['broker2_pw'];
    $broker2_server  = $_POST['broker2_server'];

    // 다중 선택 체크박스 처리
    $selected_broker = '';
    if (!empty($_POST['selected_broker'])) {
        $selected_broker = implode(',', $_POST['selected_broker']); 
    }

    $success = true;
    $error_msg = "";

    // users 테이블 업데이트 (Country 제외)
    $sql_update_user = "UPDATE users SET email=?, phone=? WHERE id=?";
    $stmt_user = $conn->prepare($sql_update_user);
    $stmt_user->bind_param("ssi", $email, $phone, $user_id);
    if (!$stmt_user->execute()) {
        $success = false;
        $error_msg .= t('err.user_basic_update_failed_prefix', 'User basic info update error:') . " " . $stmt_user->error . "\\n";
    }
    $stmt_user->close();

    // user_details 테이블 업데이트
    $sql_update_detail = "UPDATE user_details 
        SET wallet_address=?, xm_id=?, xm_pw=?, xm_server=?, 
            ultima_id=?, ultima_pw=?, ultima_server=?,
            broker1_id=?, broker1_pw=?, broker1_server=?,
            broker2_id=?, broker2_pw=?, broker2_server=?,
            selected_broker=?
        WHERE user_id=?";
    $stmt_detail = $conn->prepare($sql_update_detail);
    $stmt_detail->bind_param("ssssssssssssssi",
        $wallet, $xm_id, $xm_pw, $xm_server,
        $ultima_id, $ultima_pw, $ultima_server,
        $broker1_id, $broker1_pw, $broker1_server,
        $broker2_id, $broker2_pw, $broker2_server,
        $selected_broker, $user_id
    );
    if (!$stmt_detail->execute()) {
        $success = false;
        $error_msg .= "사용자 상세 정보 수정 오류: ".$stmt_detail->error."\\n";
    }
    $stmt_detail->close();

    if ($success) {
        echo "<script>alert(" . json_encode(t('msg.self_update_done')) . ");</script>";
    } else {
        echo "<script>alert(" . json_encode(t('error.update_failed_prefix') . "\n" . $error_msg) . ");</script>";
    }
}

$conn->close();

// 페이지 타이틀 및 레이아웃 적용
$page_title   = t('title.edit_account','Edit Account');
$content_file = __DIR__ . "/edit_account_content.php";
$menu_type    = "investor";
include __DIR__ . "/layout.php";