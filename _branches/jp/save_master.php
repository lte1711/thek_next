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


// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    // 로그인 페이지로 리디렉션
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 공통 입력값
$username = $_POST['username'];
$password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
$name     = $_POST['name'];
$email    = $_POST['email'];
$country  = $_POST['country'];
$phone    = $_POST['phone'];

$user_id  = $_POST['id'] ?? null; // 수정 시 넘어오는 id

if ($user_id) {
    // ---------------------------
    // 수정 (UPDATE)
    // ---------------------------
    if ($password) {
        // 비밀번호도 수정
        $sql_user = "UPDATE users 
                     SET username=?, name=?, email=?, country=?, password_hash=?, phone=? 
                     WHERE id=?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ssssssi", $username, $name, $email, $country, $password, $phone, $user_id);
    } else {
        // 비밀번호는 그대로 두고 다른 정보만 수정
        $sql_user = "UPDATE users 
                     SET username=?, name=?, email=?, country=?, phone=? 
                     WHERE id=?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("sssssi", $username, $name, $email, $country, $phone, $user_id);
    }

    if ($stmt_user->execute()) {
        // user_details도 수정
        $wallet        = $_POST['wallet_address'];
        $broker_id     = $_POST['broker_id'];
        $broker_pw     = $_POST['broker_pw'];
        $xm_id         = $_POST['xm_id'];
        $xm_pw         = $_POST['xm_pw'];
        $xm_server     = $_POST['xm_server'];
        $ultima_id     = $_POST['ultima_id'];
        $ultima_pw     = $_POST['ultima_pw'];
        $ultima_server = $_POST['ultima_server'];
        $referral_code = $_POST['referral_code'];

        $sql_details = "UPDATE user_details 
                        SET wallet_address=?, broker_id=?, broker_pw=?, xm_id=?, xm_pw=?, xm_server=?, 
                            ultima_id=?, ultima_pw=?, ultima_server=?, referral_code=? 
                        WHERE user_id=?";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param(
            "ssssssssssi",
            $wallet,
            $broker_id,
            $broker_pw,
            $xm_id,
            $xm_pw,
            $xm_server,
            $ultima_id,
            $ultima_pw,
            $ultima_server,
            $referral_code,
            $user_id
        );

        if ($stmt_details->execute()) {
            echo "<script>alert('" . addslashes(t('msg.master_updated')) . "'); location.href='a_master_list.php';</script>";
        } else {
            echo "<script>alert('" . addslashes(t('error.detail_update_failed')) . ": " . addslashes($stmt_details->error) . "'); location.href='a_master_list.php';</script>";
        }
    } else {
        echo "<script>alert('" . addslashes(t('error.basic_update_failed')) . ": " . addslashes($stmt_user->error) . "'); location.href='a_master_list.php';</script>";
    }

} else {
    // ---------------------------
    // 신규 등록 (INSERT)
    // ---------------------------
    $sql_user = "INSERT INTO users (username, name, email, country, role, password_hash, phone)
                 VALUES (?, ?, ?, ?, 'master', ?, ?)";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("ssssss", $username, $name, $email, $country, $password, $phone);

    if ($stmt_user->execute()) {
        $user_id = $stmt_user->insert_id;

        // user_details 테이블에 상세 정보 저장
        $wallet        = $_POST['wallet_address'];
        $broker_id     = $_POST['broker_id'];
        $broker_pw     = $_POST['broker_pw'];
        $xm_id         = $_POST['xm_id'];
        $xm_pw         = $_POST['xm_pw'];
        $xm_server     = $_POST['xm_server'];
        $ultima_id     = $_POST['ultima_id'];
        $ultima_pw     = $_POST['ultima_pw'];
        $ultima_server = $_POST['ultima_server'];
        $referral_code = $_POST['referral_code'];

        $sql_details = "INSERT INTO user_details 
            (user_id, wallet_address, broker_id, broker_pw,
             xm_id, xm_pw, xm_server, ultima_id, ultima_pw, ultima_server, referral_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param(
            "issssssssss",
            $user_id,
            $wallet,
            $broker_id,
            $broker_pw,
            $xm_id,
            $xm_pw,
            $xm_server,
            $ultima_id,
            $ultima_pw,
            $ultima_server,
            $referral_code
        );

        if ($stmt_details->execute()) {
            echo "<script>alert('" . addslashes(t('msg.master_created')) . "'); location.href='a_master_list.php';</script>";
        } else {
            echo "<script>alert('" . addslashes(t('error.detail_save_failed')) . ": " . addslashes($stmt_details->error) . "'); location.href='a_master_list.php';</script>";
        }
    } else {
        echo "<script>alert('" . addslashes(t('error.basic_save_failed')) . ": " . addslashes($stmt_user->error) . "'); location.href='a_master_list.php';</script>";
    }
}
?>