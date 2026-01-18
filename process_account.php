<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

require_once "db_connect.php";
if ($conn->connect_error) { die("DB 연결 실패: " . $conn->connect_error); }

function safe_input($k){ return isset($_POST[$k]) ? trim($_POST[$k]) : ''; }
function generate_referral_code(): string {
    return 'REF_' . date('Ymd') . '_' . str_pad(mt_rand(0,999999), 6, '0', STR_PAD_LEFT);
}
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return trim($ip);
}
function get_country_by_ip($ip) {
    $res = @file_get_contents("http://ip-api.com/json/{$ip}?fields=country");
    if ($res !== false) { $data = json_decode($res, true); return $data['country'] ?? "Unknown"; }
    return "Unknown";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 입력 수집
    $username = safe_input('username'); // 새 등록이면 필수
    $email    = safe_input('email');
    $phone    = safe_input('phone');
    $wallet   = safe_input('wallet_address');
    $country  = safe_input('country'); // 폼에서 readonly로 전달됨
    $posted_ref = safe_input('referral_code');

    // 브로커들
    $xm_id = safe_input('xm_id');        $xm_pw = safe_input('xm_pw');        $xm_server = safe_input('xm_server');
    $ultima_id = safe_input('ultima_id');$ultima_pw = safe_input('ultima_pw');$ultima_server = safe_input('ultima_server');
    $broker1_id = safe_input('broker1_id');$broker1_pw = safe_input('broker1_pw');$broker1_server = safe_input('broker1_server');
    $broker2_id = safe_input('broker2_id');$broker2_pw = safe_input('broker2_pw');$broker2_server = safe_input('broker2_server');
    $selected_broker = isset($_POST['selected_broker']) ? implode(',', $_POST['selected_broker']) : '';

    // 필드 검증
    $errors = [];
    if ($username === '') $errors[] = 'ID는 필수입니다.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = '이메일 형식이 올바르지 않습니다.';
    if (!empty($errors)) {
        echo "<script>alert(" . json_encode("❌ 입력 오류:\n- " . implode("\n- ", $errors)) . ");history.back();</script>";
        exit;
    }

    // 서버에서 최종 확정
    $sponsor_id = $_SESSION['user_id'];
    $role = "investor";
    // country가 비어있다면 서버에서 재계산
    if ($country === '') $country = get_country_by_ip(get_client_ip());
    // referral_code는 정책상 서버 생성 우선
    $referral_code = generate_referral_code();

    // 중복 확인 (username, email)
    $dup_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM users WHERE username = ? OR email = ?");
    $dup_stmt->bind_param("ss", $username, $email);
    $dup_stmt->execute();
    $dup_cnt = $dup_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $dup_stmt->close();
    if ($dup_cnt > 0) {
        echo "<script>alert('❌ 이미 존재하는 ID 또는 Email입니다. 다른 값을 사용해주세요.');history.back();</script>";
        exit;
    }

    // 트랜잭션
    $conn->begin_transaction();
    try {
        // 1) users 등록
        $stmt_users = $conn->prepare(
            "INSERT INTO users (username, email, phone, wallet_address, country, role, sponsor_id, referral_code, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt_users->bind_param("ssssssis", $username, $email, $phone, $wallet, $country, $role, $sponsor_id, $referral_code);
        if (!$stmt_users->execute()) throw new Exception("users 등록 오류: " . $stmt_users->error);
        $new_user_id = $stmt_users->insert_id;
        $stmt_users->close();

        // 2) user_details 등록
        $stmt_details = $conn->prepare(
            "INSERT INTO user_details
             (user_id, xm_id, xm_pw, xm_server,
              ultima_id, ultima_pw, ultima_server,
              broker1_id, broker1_pw, broker1_server,
              broker2_id, broker2_pw, broker2_server,
              selected_broker, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt_details->bind_param(
            "isssssssssssss",
            $new_user_id,
            $xm_id, $xm_pw, $xm_server,
            $ultima_id, $ultima_pw, $ultima_server,
            $broker1_id, $broker1_pw, $broker1_server,
            $broker2_id, $broker2_pw, $broker2_server,
            $selected_broker
        );
        if (!$stmt_details->execute()) throw new Exception("user_details 등록 오류: " . $stmt_details->error);
        $stmt_details->close();

        $conn->commit();
        echo "<script>alert('✅ 투자자 계정이 성공적으로 등록되었습니다.\\nReferral: {$referral_code}');location.href='c_create_account.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('❌ 등록 중 오류: " . $e->getMessage() . "');history.back();</script>";
    }
}

$conn->close();
