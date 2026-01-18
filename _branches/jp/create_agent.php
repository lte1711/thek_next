<?php
ob_start();
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

// i18n (safe include)
if (!function_exists('t')) {
    $candidates = [
        __DIR__ . '/includes/i18n.php',
        __DIR__ . '/includes/i18n.php',
        dirname(__DIR__) . '/includes/i18n.php',
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) { require_once $p; break; }
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ✅ 통합 수정: 기존 create_*.php?id=... 수정 진입은 create_account.php (2-step)로 이동
$id = $_GET['id'] ?? null;
$redirect = $_GET['redirect'] ?? null;
if ($id !== null && $id !== '' && ctype_digit((string)$id)) {
    $qs = "mode=edit&id=" . urlencode((string)$id);
    if ($redirect) $qs .= "&redirect=" . urlencode((string)$redirect);
    header("Location: create_account.php?$qs");
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/db_connect.php'; // $conn

// ===== helpers =====
function safe_post(string $key): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

function allowed_child_roles(string $role): array {
    $role = strtolower(trim($role));
    if ($role === 'gm') return ['gm','admin','master','agent','investor'];
    if ($role === 'admin') return ['master'];
    if ($role === 'master') return ['agent'];
    if ($role === 'agent') return ['investor'];
    return [];
}

function normalize_referral_code(string $v): string {
    $v = strtoupper(trim($v));
    $v = preg_replace('/\s+/', '', $v);
    return $v ?? '';
}

function generate_referral_code(): string {
    // REF_YYYYMMDD_HHMMSS (6자리) 형태
    $d = date('Ymd');
    $t = date('His');
    return "REF_{$d}_{$t}";
}

function generate_unique_referral_code(mysqli $conn): string {
    for ($i=0; $i<20; $i++) {
        $rc = generate_referral_code();
        $st = $conn->prepare("SELECT 1 FROM users WHERE referral_code = ? LIMIT 1");
        $st->bind_param('s', $rc);
        $st->execute();
        $hit = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$hit) return $rc;
        usleep(20000); // 20ms
    }
    return generate_referral_code() . '_' . random_int(10, 99);
}

// ===== context =====
$current_user_id = (int)$_SESSION['user_id'];
$current_role    = strtolower((string)$_SESSION['role']);

$auto_country = 'KR';
$auto_referral_code = generate_unique_referral_code($conn);

// 현재 로그인 사용자 표시용
$current_user = ['id'=>$current_user_id,'username'=>'','name'=>'','role'=>$current_role];
try {
    $st = $conn->prepare("SELECT id, username, name, role FROM users WHERE id=? LIMIT 1");
    $st->bind_param("i", $current_user_id);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    if ($r) $current_user = $r;
} catch (Throwable $e) {
    // 표시용이므로 실패해도 진행
}

// ===== step routing =====
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 1 && $step !== 2) $step = 1;

// ✅ Step1 저장: 필수값 검증 후 세션 저장 → step=2 이동
if ($_SERVER['REQUEST_METHOD'] === 'POST' && safe_post('_action') === 'save_step1') {
    $data = [
        'username'        => safe_post('username'),
        'password'        => safe_post('password'),
        'name'            => safe_post('name'),
        'email'           => safe_post('email'),
        'phone'           => safe_post('phone'),
        'country'         => $auto_country, // 서버 강제
        'wallet_address'  => safe_post('wallet_address'),
        'codepay_address' => strtoupper(safe_post('codepay_address')), // 서버 강제 대문자
        'referral_code'   => normalize_referral_code(safe_post('referral_code')),
        // 고정값
        'role'            => 'agent',
        'sponsor_id'      => $current_user_id,
        'referrer_id'     => null,
    ];

    $errors = [];
    if ($data['username'] === '') $errors[] = t('err.username_required');
    if ($data['password'] === '') $errors[] = t('err.password_required');
    if ($data['name'] === '') $errors[] = t('err.name_required');
    if ($data['email'] === '') $errors[] = t('err.email_required');
    if ($data['wallet_address'] === '') $errors[] = t('err.wallet_required');
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = t('err.check_email');

    // 권한 체크: 현재 권한에서 master 생성 가능한지
    $allowed = allowed_child_roles($current_role);
    if (!in_array('agent', $allowed, true)) {
        $errors[] = t('create_agent.msg.no_permission');
    }

    // referral_code 형식/중복 체크. 형식이 안맞으면 서버에서 생성
    if (!preg_match('/^REF_\d{8}_[0-9]{6}(?:_\d{2})?$/', $data['referral_code'])) {
        $data['referral_code'] = $auto_referral_code;
    } else {
        $st = $conn->prepare("SELECT 1 FROM users WHERE referral_code=? LIMIT 1");
        $st->bind_param("s", $data['referral_code']);
        $st->execute();
        $hit = $st->get_result()->fetch_assoc();
        $st->close();
        if ($hit) $data['referral_code'] = $auto_referral_code;
    }

    if (!empty($errors)) {
        $msg = "" . t('err.input_error_title') . " " . implode("\n- ", $errors);
        echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
        exit;
    }

    // ✅ 중복 체크 (username/email)
    $dup_sql = "SELECT COUNT(*) AS cnt FROM users WHERE username = ? OR email = ?";
    $dup_stmt = $conn->prepare($dup_sql);
    $dup_stmt->bind_param('ss', $data['username'], $data['email']);
    $dup_stmt->execute();
    $dup = $dup_stmt->get_result()->fetch_assoc();
    $dup_stmt->close();
    if ((int)($dup['cnt'] ?? 0) > 0) {
        echo "<script>alert(" . json_encode("❌ " . t('create_agent.msg.exists')) . "); window.history.back();</script>";
        exit;
    }

    $_SESSION['create_agent_step1'] = $data;
    header("Location: create_agent.php?step=2");
    exit;
}

// ✅ Step2 생성 확정: DB INSERT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && safe_post('_action') === 'create_user') {
    if (!isset($_SESSION['create_agent_step1'])) {
        echo "<script>alert(" . json_encode("❌ " . t('create_agent.err.no_step1')) . "); window.location.href='create_agent.php';</script>";
        exit;
    }
    $s1 = $_SESSION['create_agent_step1'];

    // 서버 강제(우회 방지)
    $new_role   = 'agent';
    $sponsor_id = $current_user_id;

    // 권한 재검증
    $allowed = allowed_child_roles($current_role);
    if (!in_array('agent', $allowed, true)) {
        echo "<script>alert(" . json_encode("❌ " . t('create_agent.msg.no_permission')) . "); window.history.back();</script>";
        exit;
    }

    $conn->begin_transaction();
    try {
        $pw_hash = password_hash($s1['password'], PASSWORD_DEFAULT);

        $sql_users = "INSERT INTO users
            (username, name, email, country, role, password_hash, phone, referral_code, referrer_id, sponsor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_users);

        $referrer_id = null;
        $ref_i = $referrer_id; // NULL 허용
        $spo_i = $sponsor_id;

        $stmt->bind_param(
            "ssssssssi" . "i",
            $s1['username'],
            $s1['name'],
            $s1['email'],
            $s1['country'],
            $new_role,
            $pw_hash,
            $s1['phone'],
            $s1['referral_code'],
            $ref_i,
            $spo_i
        );
        $stmt->execute();
        $new_user_id = $stmt->insert_id;
        $stmt->close();

        // ✅ 플랫폼(XM/ULTIMA) 저장 제거: user_details 최소 필드만 저장
        $sql_details = "INSERT INTO user_details
            (user_id, wallet_address, codepay_address, referral_code, created_at)
            VALUES (?, ?, ?, ?, NOW())";
        $stmt2 = $conn->prepare($sql_details);
        $stmt2->bind_param(
            "isss",
            $new_user_id,
            $s1['wallet_address'],
            $s1['codepay_address'],
            $s1['referral_code']
        );
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();

        unset($_SESSION['create_agent_step1']);
        echo "<script>alert(" . json_encode("✅ " . t('create_agent.msg.success')) . "); window.location.href='a_agent_list.php';</script>";
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $msg = t('err.processing_error_prefix') . ' ' . $e->getMessage();
        echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
        exit;
    }
}

// ===== GET: render =====
$page_title   = "Create Agent";
$content_file = __DIR__ . "/create_agent_content.php";
include "layout.php";
