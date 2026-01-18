<?php
ob_start();
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/db_connect.php'; // $conn

$current_user_id = (int)$_SESSION['user_id'];
$current_role = strtolower(trim((string)$_SESSION['role']));

function safe_post(string $k): string {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 1 && $step !== 2) $step = 1;

// ✅ 이 페이지는 "수정 전용" 페이지입니다.
$mode = 'edit';

// 대상 ID: 기본은 본인, GM만 id 지정 허용
$target_id = $current_user_id;
if ($current_role === 'gm' && isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $target_id = (int)$_GET['id'];
}

$is_gm = ($current_role === 'gm');
$is_self = ($target_id === $current_user_id);

// 권한: GM 또는 본인만
if (!$is_gm && !$is_self) {
    echo "<script>alert('❌ 권한이 없습니다.'); window.history.back();</script>";
    exit;
}

// redirect
$redirect = isset($_GET['redirect']) ? basename((string)$_GET['redirect']) : '';
$allowed_redirects = ['investor_dashboard.php','b_investor_list.php','c_investor_list.php','create_account.php'];
$redirect_url = in_array($redirect, $allowed_redirects, true) ? $redirect : 'investor_dashboard.php';

// ✅ 세션: 다른 id의 편집 세션이 남아있으면 폐기
if (isset($_SESSION['investor_edit_step1']) && is_array($_SESSION['investor_edit_step1'])) {
    $sid = (int)($_SESSION['investor_edit_step1']['id'] ?? 0);
    if ($sid > 0 && $sid !== $target_id) {
        unset($_SESSION['investor_edit_step1']);
    }
}

// Step1 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && safe_post('_action') === 'save_step1') {
    $email = safe_post('email');
    $phone = safe_post('phone');
    $wallet_address = safe_post('wallet_address');
    $codepay_address = strtoupper(safe_post('codepay_address'));
    $password = safe_post('password');

    $errors = [];
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "이메일을 확인해주세요.";
    if ($wallet_address === '') $errors[] = "USDT지갑주소는 필수입니다.";

    // ✅ 비밀번호는 본인일 때만 허용(입력 시에만 변경)
    if (!$is_self && $password !== '') {
        $errors[] = "비밀번호 변경은 회원 본인이 본인 수정 시에만 가능합니다.";
    }

    if ($errors) {
        echo "<script>alert(" . json_encode("❌ 입력 오류:\n- " . implode("\n- ", $errors)) . "); window.history.back();</script>";
        exit;
    }

    $_SESSION['investor_edit_step1'] = [
        'id' => $target_id,
        'email' => $email,
        'phone' => $phone,
        'wallet_address' => $wallet_address,
        'codepay_address' => $codepay_address,
        // 본인일 때만 저장
        'password' => $is_self ? $password : '',
    ];

    $qs = "step=2&id=" . urlencode((string)$target_id);
    if ($redirect !== '') $qs .= "&redirect=" . urlencode($redirect);
    header("Location: investor_edit_broker.php?$qs");
    exit;
}

// Step2 최종 수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && safe_post('_action') === 'update_user') {
    if (!isset($_SESSION['investor_edit_step1']) || !is_array($_SESSION['investor_edit_step1'])) {
        echo "<script>alert('❌ 1단계 정보가 없습니다. 다시 시도해주세요.'); window.location.href=" . json_encode("investor_edit_broker.php?id=".$target_id) . ";</script>";
        exit;
    }
    $s1 = $_SESSION['investor_edit_step1'];

    // 브로커/XM/ULTIMA 값
    $xm_id = safe_post('xm_id');
    $xm_pw = safe_post('xm_pw');
    $xm_server = safe_post('xm_server');

    $ultima_id = safe_post('ultima_id');
    $ultima_pw = safe_post('ultima_pw');
    $ultima_server = safe_post('ultima_server');

    $broker1_id = safe_post('broker1_id');
    $broker1_pw = safe_post('broker1_pw');
    $broker1_server = safe_post('broker1_server');

    $broker2_id = safe_post('broker2_id');
    $broker2_pw = safe_post('broker2_pw');
    $broker2_server = safe_post('broker2_server');

    $selected = $_POST['selected_broker'] ?? [];
    if (!is_array($selected)) $selected = [];
    $allowed_selected = ['xm','ultima','broker1','broker2'];
    $selected = array_values(array_unique(array_filter($selected, fn($v)=>in_array($v, $allowed_selected, true))));
    $selected_broker = implode(',', $selected);

    // (선택) 최소 필수값 체크 - 필요 없으면 주석 처리 가능
    // if ($xm_id==='' && $ultima_id==='' && $broker1_id==='' && $broker2_id==='') { ... }

    $conn->begin_transaction();
    try {
        // users: 본인은 email/phone만 변경 (GM도 동일하게 처리: 투자자 수정 전용 페이지이므로 최소 필드만)
        $stmt = $conn->prepare("UPDATE users SET email=?, phone=? WHERE id=?");
        $stmt->bind_param("ssi", $s1['email'], $s1['phone'], $target_id);
        $stmt->execute();
        $stmt->close();

        // user_details: upsert + 브로커 정보 포함
        $sql = "INSERT INTO user_details
                (user_id, wallet_address, codepay_address,
                 xm_id, xm_pw, xm_server,
                 ultima_id, ultima_pw, ultima_server,
                 broker1_id, broker1_pw, broker1_server,
                 broker2_id, broker2_pw, broker2_server,
                 selected_broker, created_at)
                VALUES
                (?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, NOW())
                ON DUPLICATE KEY UPDATE
                 wallet_address=VALUES(wallet_address),
                 codepay_address=VALUES(codepay_address),
                 xm_id=VALUES(xm_id), xm_pw=VALUES(xm_pw), xm_server=VALUES(xm_server),
                 ultima_id=VALUES(ultima_id), ultima_pw=VALUES(ultima_pw), ultima_server=VALUES(ultima_server),
                 broker1_id=VALUES(broker1_id), broker1_pw=VALUES(broker1_pw), broker1_server=VALUES(broker1_server),
                 broker2_id=VALUES(broker2_id), broker2_pw=VALUES(broker2_pw), broker2_server=VALUES(broker2_server),
                 selected_broker=VALUES(selected_broker)";
        $stmt2 = $conn->prepare($sql);
        $stmt2->bind_param(
            "isssssssssssssss",
            $target_id,
            $s1['wallet_address'],
            $s1['codepay_address'],
            $xm_id, $xm_pw, $xm_server,
            $ultima_id, $ultima_pw, $ultima_server,
            $broker1_id, $broker1_pw, $broker1_server,
            $broker2_id, $broker2_pw, $broker2_server,
            $selected_broker
        );
        $stmt2->execute();
        $stmt2->close();

        // password: 본인 + 입력 시에만
        if ($is_self && isset($s1['password']) && $s1['password'] !== '') {
            $pw_hash = password_hash($s1['password'], PASSWORD_DEFAULT);
            $stmt3 = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt3->bind_param("si", $pw_hash, $target_id);
            $stmt3->execute();
            $stmt3->close();
        }

        $conn->commit();
        unset($_SESSION['investor_edit_step1']);

        echo "<script>alert('✅ 수정 완료!'); window.location.href=" . json_encode($redirect_url) . ";</script>";
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        echo "<script>alert(" . json_encode("❌ 처리 중 오류: ".$e->getMessage()) . "); window.history.back();</script>";
        exit;
    }
}

// ===== Prefill (DB -> 화면) =====
$u = null;
$d = null;
$stmt = $conn->prepare("SELECT id, username, name, email, phone, country, role FROM users WHERE id=?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT wallet_address, codepay_address,
                               xm_id, xm_pw, xm_server,
                               ultima_id, ultima_pw, ultima_server,
                               broker1_id, broker1_pw, broker1_server,
                               broker2_id, broker2_pw, broker2_server,
                               selected_broker
                        FROM user_details WHERE user_id=?");
$stmt->bind_param("i", $target_id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();
$stmt->close();

$prefill = [
    'id' => (string)$target_id,
    'username' => (string)($u['username'] ?? ''),
    'name' => (string)($u['name'] ?? ''),
    'email' => (string)($u['email'] ?? ''),
    'phone' => (string)($u['phone'] ?? ''),
    'country' => (string)($u['country'] ?? ''),
    'wallet_address' => (string)($d['wallet_address'] ?? ''),
    'codepay_address' => (string)($d['codepay_address'] ?? ''),
    'xm_id' => (string)($d['xm_id'] ?? ''),
    'xm_pw' => (string)($d['xm_pw'] ?? ''),
    'xm_server' => (string)($d['xm_server'] ?? ''),
    'ultima_id' => (string)($d['ultima_id'] ?? ''),
    'ultima_pw' => (string)($d['ultima_pw'] ?? ''),
    'ultima_server' => (string)($d['ultima_server'] ?? ''),
    'broker1_id' => (string)($d['broker1_id'] ?? ''),
    'broker1_pw' => (string)($d['broker1_pw'] ?? ''),
    'broker1_server' => (string)($d['broker1_server'] ?? ''),
    'broker2_id' => (string)($d['broker2_id'] ?? ''),
    'broker2_pw' => (string)($d['broker2_pw'] ?? ''),
    'broker2_server' => (string)($d['broker2_server'] ?? ''),
    'selected_broker' => (string)($d['selected_broker'] ?? ''),
];

// Step1 세션이 있으면 세션값이 우선(재진입 편의)
if (isset($_SESSION['investor_edit_step1']) && is_array($_SESSION['investor_edit_step1'])) {
    $s1 = $_SESSION['investor_edit_step1'];
    foreach (['email','phone','wallet_address','codepay_address'] as $k) {
        if (isset($s1[$k])) $prefill[$k] = (string)$s1[$k];
    }
}

$page_title = "INVESTOR EDIT (2-STEP)";
$content_file = __DIR__ . "/investor_edit_broker_content.php";
include "layout.php";
