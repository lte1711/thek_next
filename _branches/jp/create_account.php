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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/db_connect.php'; // $conn

// ===== AJAX: 추천인 검색 (레퍼럴 코드) =====
if (isset($_GET['action']) && $_GET['action'] === 'search_referrer') {
    header('Content-Type: application/json; charset=UTF-8');
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    if ($q === '' || mb_strlen($q, 'UTF-8') < 1) {
        echo json_encode([]);
        exit;
    }

    $like = '%' . $q . '%';

    // referral_code 기반 검색 (부분 일치)
    $sql = "SELECT id, username, name, referral_code
            FROM users
            WHERE role = 'investor'
              AND referral_code LIKE ?
            ORDER BY id DESC
            LIMIT 10";
    $st = $conn->prepare($sql);
    $st->bind_param("s", $like);

$st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();

    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];
$current_role = strtolower(trim((string)$_SESSION['role']));

function allowed_child_roles(string $role): array {
    $role = strtolower(trim($role));
    if ($role === 'gm') return ['gm','admin','master','agent','investor'];
    if ($role === 'admin') return ['master'];
    if ($role === 'master') return ['agent'];
    if ($role === 'agent') return ['investor'];
    return [];
}

function required_sponsor_role(string $new_role): ?string {
    $new_role = strtolower(trim($new_role));
    return match ($new_role) {
        'admin' => null,
        'master' => 'admin',
        'agent' => 'master',
        'investor' => 'agent',
        'gm' => null,
        default => null,
    };
}

function safe_post(string $k): string {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : '';
}

function get_client_ip(): string {
    $candidates = [];
    foreach (['CF-Connecting-IP','X-Forwarded-For','X-Real-IP'] as $h) {
        $hh = 'HTTP_' . strtoupper(str_replace('-', '_', $h));
        if (!empty($_SERVER[$hh])) $candidates[] = $_SERVER[$hh];
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) $candidates[] = $_SERVER['REMOTE_ADDR'];

    foreach ($candidates as $cand) {
        $parts = array_map('trim', explode(',', (string)$cand));
        foreach ($parts as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function detect_country_code(): string {
    $cf = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
    if (is_string($cf)) {
        $cf = strtoupper(trim($cf));
        if (preg_match('/^[A-Z]{2}$/', $cf)) return $cf;
    }

    $ip = get_client_ip();
    if (function_exists('geoip_country_code_by_name')) {
        $cc = @geoip_country_code_by_name($ip);
        if (is_string($cc)) {
            $cc = strtoupper(trim($cc));
            if (preg_match('/^[A-Z]{2}$/', $cc)) return $cc;
        }
    }

    return 'KR';
}

function generate_referral_code(): string {
    return 'REF_' . date('Ymd') . '_' . strval(random_int(100000, 999999));
}

function normalize_referral_code(string $v): string {
    $v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
    $v = str_replace('\\', '', $v);
    $v = trim($v, "\"' \t\r\n");
    return $v;
}

/**
 * ✅ redirect 처리 (오픈리다이렉트 방지)
 * - investor_list.php 같은 특정 목록 페이지로 돌아가기 용도
 */
$redirect = isset($_GET['redirect']) ? (string)$_GET['redirect'] : '';
$redirect = $redirect !== '' ? basename($redirect) : '';
$allowed_redirects = [
    'investor_list.php', 'agent_list.php', 'master_list.php', 'admin_list.php', 'gm_list.php',
    'create_account.php'
];
$redirect_url = in_array($redirect, $allowed_redirects, true) ? $redirect : 'create_account.php';

// step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 1 && $step !== 2) $step = 1;

// 자동 값
$auto_country = detect_country_code();
$auto_referral_code = generate_referral_code();

// Step1 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (safe_post('_action') === 'save_step1')) {
    $post_mode = strtolower(safe_post('_mode'));
    $is_edit_post = ($post_mode === 'edit');
    $edit_id = (int)safe_post('id');
    $is_self_edit = ($is_edit_post && $edit_id > 0 && $edit_id === $current_user_id);

    $data = [
        'username' => safe_post('username'),
        'password' => safe_post('password'),
        'name' => safe_post('name'),
        'email' => safe_post('email'),
        'phone' => safe_post('phone'),
        'country' => safe_post('country'),
        'wallet_address' => safe_post('wallet_address'),
        'codepay_address' => strtoupper(safe_post('codepay_address')),
        'referral_code' => safe_post('referral_code'),
    ];

    $errors = [];
    if ($data['username'] === '') $errors[] = t('err.username_required','Username is required.');
    // ✅ 생성(create)일 때만 비밀번호 필수
    if (!$is_edit_post && $data['password'] === '') $errors[] = t('err.password_required','Password is required.');
    if ($data['name'] === '') $errors[] = t('err.name_required','Name is required.');
    if ($data['email'] === '') $errors[] = t('err.email_required','Email is required.');
    if ($data['wallet_address'] === '') $errors[] = t('err.wallet_required','USDT wallet address is required.');

    // ✅ 비밀번호 변경 권한: 본인 수정 시에만
    if ($is_edit_post && $data['password'] !== '' && !$is_self_edit) {
        $errors[] = t('err.password_change_self_only','Password can only be changed by the user on self-edit.');
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('err.invalid_email_format');
    }

    if (!empty($errors)) {
        $msg = t('err.input_error_title') . "\n- " . implode("\n- ", $errors);
        echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
        exit;
    }

    // 중복 체크
    if (!$is_edit_post) {
        $dup_sql = "SELECT COUNT(*) AS cnt FROM users WHERE username = ? OR email = ?";
        $dup_stmt = $conn->prepare($dup_sql);
        $dup_stmt->bind_param("ss", $data['username'], $data['email']);
        $dup_stmt->execute();
        $dup = $dup_stmt->get_result()->fetch_assoc();
        $dup_stmt->close();

        if ((int)$dup['cnt'] > 0) {
            echo "<script>alert(" . json_encode(t('err.duplicate_id_email')) . "); window.history.back();</script>";
            exit;
        }
    } else {
        if ($edit_id <= 0) {
        echo "<script>alert(" . json_encode('❌ ' . t('err.edit_target_missing','Edit target not found.')) . "); window.history.back();</script>";
            exit;
        }
        $dup_sql = "SELECT COUNT(*) AS cnt FROM users WHERE (username = ? OR email = ?) AND id <> ?";
        $dup_stmt = $conn->prepare($dup_sql);
        $dup_stmt->bind_param("ssi", $data['username'], $data['email'], $edit_id);
        $dup_stmt->execute();
        $dup = $dup_stmt->get_result()->fetch_assoc();
        $dup_stmt->close();

        if ((int)$dup['cnt'] > 0) {
            echo "<script>alert(" . json_encode(t('err.duplicate_id_email')) . "); window.history.back();</script>";
            exit;
        }
    }

    // ✅ country/referral_code는 서버 강제
    $data['country'] = detect_country_code();

    $rc = normalize_referral_code($data['referral_code'] ?? '');
    if (!preg_match('/^REF_\d{8}_[0-9]{6}$/', $rc)) {
        $rc = generate_referral_code();
    }
    $data['referral_code'] = $rc;

    // ✅ edit 모드: 비밀번호는 본인 수정 + 입력 시에만 유지
    if ($is_edit_post) {
        if (!$is_self_edit) {
            $data['password'] = '';
        }
        $_SESSION['edit_account_step1'] = $data + ['id' => $edit_id];

        // ✅ redirect 유지해서 step2로
        $q = "step=2&mode=edit&id=" . urlencode((string)$edit_id);
        if ($redirect !== '') $q .= "&redirect=" . urlencode($redirect);
        header("Location: create_account.php?$q");
    } else {
        $_SESSION['create_account_step1'] = $data;

        // ✅ redirect 유지(필요 시)
        $q = "step=2";
        if ($redirect !== '') $q .= "&redirect=" . urlencode($redirect);
        header("Location: create_account.php?$q");
    }
    exit;
}

// Step2 최종 생성
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (safe_post('_action') === 'create_user')) {
    if (!isset($_SESSION['create_account_step1'])) {
        echo "<script>alert(" . json_encode('❌ ' . t('err.step1_missing','Step 1 data is missing. Please try again.')) . "); window.location.href=" . json_encode($redirect_url) . ";</script>";
        exit;
    }
    $s1 = $_SESSION['create_account_step1'];

    $new_role = strtolower(safe_post('role'));
    if ($new_role === 'gm' && $target_role !== 'gm') {
        echo "<script>alert(" . json_encode(t('err.gm_role_not_selectable')) . "); window.history.back();</script>";
        exit;
    }
    $xm_id = safe_post('xm_id');
    $xm_pw = safe_post('xm_pw');
    $xm_server = safe_post('xm_server');
    $ultima_id = safe_post('ultima_id');
    $ultima_pw = safe_post('ultima_pw');
    $ultima_server = safe_post('ultima_server');
    $sponsor_id_raw = safe_post('sponsor_id');
    $referrer_id_raw = safe_post('referrer_id');
    $referrer_id = null;
    $allowed = allowed_child_roles($current_role);
    if (!in_array($new_role, $allowed, true)) {
        echo "<script>alert(" . json_encode(t('msg.no_permission')) . "); window.history.back();</script>";
        exit;
    }

    $req_role = required_sponsor_role($new_role);
    if ($new_role === 'admin') { $sponsor_id = null; $req_role = null; }
    $sponsor_id = null;

    if ($req_role !== null && $new_role !== 'admin') {
        // GM 수정: 상위(소속) 선택은 선택 사항(비어있으면 기존 sponsor_id 유지)
        if ($sponsor_id_raw === '' || !ctype_digit($sponsor_id_raw)) {
            $sponsor_id = $existing_sponsor_id;
        } else {
            $sid = (int)$sponsor_id_raw;
            $st = $conn->prepare("SELECT id, role FROM users WHERE id = ?");
            $st->bind_param("i", $sid);
            $st->execute();
            $sr = $st->get_result()->fetch_assoc();
            $st->close();

            if (!$sr) {
            echo "<script>alert(" . json_encode('❌ ' . t('err.parent_not_found','Selected parent user not found.')) . "); window.history.back();</script>";
                exit;
            }
            if (strtolower($sr['role']) !== $req_role) {
            $msg = '❌ ' . str_replace('{req_role}', (string)$req_role, t('err.invalid_parent_role','Invalid parent role. (Required: {req_role})'));
            echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
                exit;
            }
            $sponsor_id = $sid;
        }
    }

    // 추천인(선택): 투자자일 때만 처리 (검색 선택 결과 referrer_id 사용)
    if ($new_role === 'investor' && $referrer_id_raw !== '') {
        if (!ctype_digit($referrer_id_raw)) {
            echo "<script>alert(" . json_encode('❌ ' . t('err.invalid_referrer','Invalid referrer selection.')) . "); window.history.back();</script>";
            exit;
        }
        $rid = (int)$referrer_id_raw;
$st = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'investor'");
        $st->bind_param("i", $rid);
        $st->execute();
        $rr = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$rr) {
            echo "<script>alert(" . json_encode('❌ ' . t('err.referrer_not_found','Selected referrer not found.')) . "); window.history.back();</script>";
            exit;
        }
        $referrer_id = $rid;
    }

    $conn->begin_transaction();
    try {
        $pw_hash = password_hash($s1['password'], PASSWORD_DEFAULT);

        $sql_users = "INSERT INTO users
            (username, name, email, country, role, password_hash, phone, referral_code, referrer_id, sponsor_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql_users);
        $ref_i = $referrer_id;
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

        // 2) user_details insert (✅ XM/ULTIMA 포함)
        $sql_details = "INSERT INTO user_details
            (user_id, wallet_address, codepay_address, referral_code,
            xm_id, xm_pw, xm_server,
            ultima_id, ultima_pw, ultima_server,
            created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt2 = $conn->prepare($sql_details);
        $stmt2->bind_param(
            "isssssssss",
            $new_user_id,
            $s1['wallet_address'],
            $s1['codepay_address'],
            $s1['referral_code'],
            $xm_id,
            $xm_pw,
            $xm_server,
            $ultima_id,
            $ultima_pw,
            $ultima_server
        );
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
        unset($_SESSION['create_account_step1']);

        echo "<script>alert(" . json_encode('✅ ' . t('msg.account_created','Account created successfully!')) . "); window.location.href=" . json_encode($redirect_url) . ";</script>";
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $msg = t('err.processing_error_prefix') . $e->getMessage();
        echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
        exit;
    }
}

// Step2 최종 수정
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (safe_post('_action') === 'update_user')) {
    if (!isset($_SESSION['edit_account_step1']) || !is_array($_SESSION['edit_account_step1'])) {
        echo "<script>alert(" . json_encode('❌ ' . t('err.step1_missing','Step 1 data is missing. Please try again.')) . "); window.location.href=" . json_encode($redirect_url) . ";</script>";
        exit;
    }
    $s1 = $_SESSION['edit_account_step1'];
    $target_id = (int)($s1['id'] ?? 0);
    if ($target_id <= 0) {
        echo "<script>alert(" . json_encode('❌ ' . t('err.edit_target_missing','Edit target not found.')) . "); window.history.back();</script>";
        exit;
    }

    
    // ✅ 대상 계정의 현재 role/sponsor_id 확보(검증/폴백 용)
    $target_role = strtolower((string)($s1['role'] ?? ''));
    $existing_sponsor_id = null;
    if ($target_role === '' || !array_key_exists('sponsor_id', $s1)) {
        $st = $conn->prepare("SELECT role, sponsor_id FROM users WHERE id = ?");
        $st->bind_param("i", $target_id);
        $st->execute();
        $tr = $st->get_result()->fetch_assoc();
        $st->close();
        if ($tr) {
            if ($target_role === '') $target_role = strtolower((string)($tr['role'] ?? ''));
            $existing_sponsor_id = $tr['sponsor_id'];
        }
    } else {
        $existing_sponsor_id = $s1['sponsor_id'] ?? null;
    }

$is_gm = ($current_role === 'gm');
    $is_self = ($target_id === $current_user_id);

    if (!$is_gm && !$is_self) {
        echo "<script>alert(" . json_encode(t('msg.no_permission')) . "); window.history.back();</script>";
        exit;
    }

	    // ✅ 역할(role)은 수정 화면에서는 변경 불가 (표시만)
	    //    - POST로 role을 변조해도 기존 role을 유지
	    $new_role = $target_role;

    $sponsor_id_raw = safe_post('sponsor_id');
    $referrer_id_raw = safe_post('referrer_id');
    $xm_id = safe_post('xm_id');
    $xm_pw = safe_post('xm_pw');
    $xm_server = safe_post('xm_server');
    $ultima_id = safe_post('ultima_id');
    $ultima_pw = safe_post('ultima_pw');
    $ultima_server = safe_post('ultima_server');

    $referrer_id = null;

    // ✅ 수정 화면: 상위(소속)는 변경 불가(모든 역할/권한 공통)
    // - GM이 하위 리스트에서 수정하더라도 sponsor_id는 기존 값을 유지
    // - 따라서 sponsor_id 검증/변경 로직을 수정 모드에서는 수행하지 않음
    $sponsor_id = $existing_sponsor_id;
    if ($new_role === 'admin') { $sponsor_id = null; }

    // 추천인(선택): 투자자일 때만 처리 (검색 선택 결과 referrer_id 사용)
    if ($new_role === 'investor' && $referrer_id_raw !== '') {
        if (!ctype_digit($referrer_id_raw)) {
            echo "<script>alert(" . json_encode('❌ ' . t('err.invalid_referrer','Invalid referrer selection.')) . "); window.history.back();</script>";
            exit;
        }
        $rid = (int)$referrer_id_raw;
        $st = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'investor'");
        $st->bind_param("i", $rid);
        $st->execute();
        $rr = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$rr) {
            echo "<script>alert(" . json_encode('❌ ' . t('err.referrer_not_found','Selected referrer not found.')) . "); window.history.back();</script>";
            exit;
        }
        $referrer_id = $rid;
    } else {
        $referrer_id = null;
    }


    $conn->begin_transaction();
    try {
        // 1) users 업데이트
        if ($is_gm) {
            $sql = "UPDATE users SET username=?, name=?, email=?, country=?, role=?, phone=?, referral_code=?, referrer_id=?, sponsor_id=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $ref_i = $referrer_id;
            $spo_i = $sponsor_id;
            // ✅ 타입 문자열과 바인드 변수 개수는 반드시 일치해야 함
            // username,name,email,country,role,phone,referral_code (7개 string)
            // referrer_id,sponsor_id,id (3개 int) => 총 10개
            $stmt->bind_param(
                "sssssssiii",
                $s1['username'],
                $s1['name'],
                $s1['email'],
                $s1['country'],
                $new_role,
                $s1['phone'],
                $s1['referral_code'],
                $ref_i,
                $spo_i,
                $target_id
            );
            $stmt->execute();
            $stmt->close();
        } else {
            // 본인은 기본정보만 수정 (아이디/이름/역할/소속/추천 변경 불가)
            $sql = "UPDATE users SET email=?, country=?, phone=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $s1['email'], $s1['country'], $s1['phone'], $target_id);
            $stmt->execute();
            $stmt->close();
        }

            // 2) user_details upsert (✅ XM/ULTIMA 포함)
            $sql_details = "INSERT INTO user_details
                (user_id, wallet_address, codepay_address, referral_code,
                xm_id, xm_pw, xm_server,
                ultima_id, ultima_pw, ultima_server,
                created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                wallet_address=VALUES(wallet_address),
                codepay_address=VALUES(codepay_address),
                referral_code=VALUES(referral_code),
                xm_id=VALUES(xm_id),
                xm_pw=VALUES(xm_pw),
                xm_server=VALUES(xm_server),
                ultima_id=VALUES(ultima_id),
                ultima_pw=VALUES(ultima_pw),
                ultima_server=VALUES(ultima_server)";

            $stmt2 = $conn->prepare($sql_details);
            $stmt2->bind_param(
                "isssssssss",
                $target_id,
                $s1['wallet_address'],
                $s1['codepay_address'],
                $s1['referral_code'],
                $xm_id,
                $xm_pw,
                $xm_server,
                $ultima_id,
                $ultima_pw,
                $ultima_server
            );
            $stmt2->execute();
            $stmt2->close();
        // 3) 비밀번호 업데이트: 본인 수정 + 입력 시에만
        if ($is_self && isset($s1['password']) && $s1['password'] !== '') {
            $pw_hash = password_hash($s1['password'], PASSWORD_DEFAULT);
            $stmt3 = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $stmt3->bind_param("si", $pw_hash, $target_id);
            $stmt3->execute();
            $stmt3->close();
        }

        $conn->commit();
        unset($_SESSION['edit_account_step1']);

        echo "<script>alert(" . json_encode(t('msg.update_success')) . "); window.location.href=" . json_encode($redirect_url) . ";</script>";
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        $msg = t('err.processing_error_prefix') . $e->getMessage();
        echo "<script>alert(" . json_encode($msg) . "); window.history.back();</script>";
        exit;
    }
}

// ===== View variables =====
// mode: create / edit
$mode = (isset($_GET['mode']) && strtolower((string)$_GET['mode']) === 'edit') ? 'edit' : 'create';

// Allowed roles label map (display)
$allowed_roles_labels = [
    'gm' => 'Grand Master',
    'admin' => 'Admin',
    'master' => 'Master',
    'agent' => 'Agent',
    'investor' => 'Investor',
];

// Which roles can current user create
$allowed_roles = allowed_child_roles($current_role);

// Sponsor pool for step2 filtering
$pool = [
    'gm' => [],
    'admin' => [],
    'master' => [],
    'agent' => [],
];
try {
    $roles_for_pool = ['gm','admin','master','agent'];
    $in = implode(',', array_fill(0, count($roles_for_pool), '?'));
    $types = str_repeat('s', count($roles_for_pool));
    $sqlp = "SELECT id, username, name, phone, role FROM users WHERE role IN ($in) ORDER BY id ASC";
    $stp = $conn->prepare($sqlp);
    $stp->bind_param($types, ...$roles_for_pool);
    $stp->execute();
    $rows = $stp->get_result()->fetch_all(MYSQLI_ASSOC);
    $stp->close();
    foreach ($rows as $r) {
        $rr = strtolower((string)$r['role']);
        if (isset($pool[$rr])) $pool[$rr][] = $r;
    }
} catch (Throwable $e) {
    // ignore
}

// Prefill values
$prefill = [
    'id' => '',
    'username' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
    'country' => $auto_country,
    'wallet_address' => '',
    'codepay_address' => '',
    'referral_code' => $auto_referral_code,
    'role' => '',
    'sponsor_id' => '',
    'referrer_id' => '',
    'referrer_name' => '',   // 화면에 보여줄 라벨(검색창 value)로 재사용
    'xm_id' => '',
    'xm_pw' => '',
    'xm_server' => '',
    'ultima_id' => '',
    'ultima_pw' => '',
    'ultima_server' => '',
];

if ($mode === 'edit') {
    $edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    /**
     * ✅ 핵심 수정(버그픽스)
     * - 이전에 다른 id를 수정하던 edit 세션이 남아있으면,
     * - 새 id로 들어올 때 세션이 prefill을 덮어써서 엉뚱한 데이터가 보임
     * - 그래서 GET id와 세션 id가 다르면 세션 폐기
     */
    if (isset($_SESSION['edit_account_step1']) && is_array($_SESSION['edit_account_step1'])) {
        $sid = (int)($_SESSION['edit_account_step1']['id'] ?? 0);
        if ($edit_id > 0 && $sid > 0 && $sid !== $edit_id) {
            unset($_SESSION['edit_account_step1']);
        }
    }

    if ($edit_id > 0) {
        $prefill['id'] = (string)$edit_id;
        try {
            $st = $conn->prepare(
                "SELECT id, username, name, email, phone, country, role, referral_code, referrer_id, sponsor_id
                 FROM users WHERE id = ?"
            );
            $st->bind_param("i", $edit_id);
            $st->execute();
            $u = $st->get_result()->fetch_assoc();
            $st->close();

            if ($u) {
                $prefill['username'] = (string)($u['username'] ?? '');
                $prefill['name'] = (string)($u['name'] ?? '');
                $prefill['email'] = (string)($u['email'] ?? '');
                $prefill['phone'] = (string)($u['phone'] ?? '');
                $prefill['country'] = (string)($u['country'] ?? $auto_country);
                $prefill['role'] = strtolower((string)($u['role'] ?? ''));
                $prefill['referral_code'] = (string)($u['referral_code'] ?? $auto_referral_code);
                $prefill['sponsor_id'] = (string)($u['sponsor_id'] ?? '');
                $prefill['referrer_id'] = (string)($u['referrer_id'] ?? '');

                // ✅ 상위(소속) 표시용 라벨(수정 화면에서 선택 불가/표시만)
                // sponsor_id가 있으면 (이름 + 아이디 + ID) 형태로 보여줌
                if (!empty($prefill['sponsor_id']) && ctype_digit($prefill['sponsor_id'])) {
                    $sid = (int)$prefill['sponsor_id'];
                    $stS = $conn->prepare("SELECT id, username, name FROM users WHERE id = ?");
                    $stS->bind_param("i", $sid);
                    $stS->execute();
                    $su = $stS->get_result()->fetch_assoc();
                    $stS->close();
                    if ($su) {
                        $prefill['sponsor_label'] = (string)$su['name'] . " (#" . (int)$su['id'] . " " . (string)$su['username'] . ")";
                    } else {
                        $prefill['sponsor_label'] = (string)$prefill['sponsor_id'];
                    }
                } else {
                    $prefill['sponsor_label'] = '';
                }
            }
            // ✅ 편집 모드: 기존 추천인 표시용 라벨 세팅
            if (!empty($prefill['referrer_id']) && ctype_digit($prefill['referrer_id'])) {
                $rid = (int)$prefill['referrer_id'];
                $stR = $conn->prepare("SELECT id, username, name FROM users WHERE id = ?");
                $stR->bind_param("i", $rid);
                $stR->execute();
                $ru = $stR->get_result()->fetch_assoc();
                $stR->close();

                if ($ru) {
                    // 검색창에 보일 텍스트(사용자가 선택한 것처럼 표시)
                    $prefill['referrer_name'] = (string)$ru['name'] . " (#" . (int)$ru['id'] . " " . (string)$ru['username'] . ")";
                } else {
                    // 참조가 깨진 경우 방어
                    $prefill['referrer_id'] = '';
                    $prefill['referrer_name'] = '';
                }
            }

            // user_details 로딩 (✅ XM/ULTIMA 포함)
            $st2 = $conn->prepare(
                "SELECT wallet_address, codepay_address, referral_code,
                        xm_id, xm_pw, xm_server,
                        ultima_id, ultima_pw, ultima_server
                FROM user_details
                WHERE user_id = ?"
            );
            $st2->bind_param("i", $edit_id);
            $st2->execute();
            $d = $st2->get_result()->fetch_assoc();
            $st2->close();

            if ($d) {
                $prefill['wallet_address']  = (string)($d['wallet_address'] ?? '');
                $prefill['codepay_address'] = (string)($d['codepay_address'] ?? '');

                // 기존 로직 유지: user_details.referral_code가 있으면 우선
                if (!empty($d['referral_code'])) {
                    $prefill['referral_code'] = (string)$d['referral_code'];
                }

                // ✅ XM / ULTIMA
                $prefill['xm_id']        = (string)($d['xm_id'] ?? '');
                $prefill['xm_pw']        = (string)($d['xm_pw'] ?? '');
                $prefill['xm_server']    = (string)($d['xm_server'] ?? '');
                $prefill['ultima_id']    = (string)($d['ultima_id'] ?? '');
                $prefill['ultima_pw']    = (string)($d['ultima_pw'] ?? '');
                $prefill['ultima_server']= (string)($d['ultima_server'] ?? '');
            }
        } catch (Throwable $e) {
            // ignore load errors
        }
    }

    // 세션 값이 있을 때는 "같은 id"인 경우에만 merge됨 (위에서 다른 id면 unset 처리)
    if (isset($_SESSION['edit_account_step1']) && is_array($_SESSION['edit_account_step1'])) {
        $s1 = $_SESSION['edit_account_step1'];
        foreach (['id','username','name','email','phone','country','wallet_address','codepay_address','referral_code'] as $k) {
            if (isset($s1[$k])) $prefill[$k] = (string)$s1[$k];
        }
    }
} else {
    if (isset($_SESSION['create_account_step1']) && is_array($_SESSION['create_account_step1'])) {
        $s1 = $_SESSION['create_account_step1'];
        foreach (['username','name','email','phone','country','wallet_address','codepay_address','referral_code'] as $k) {
            if (isset($s1[$k])) $prefill[$k] = (string)$s1[$k];
        }
    }
}

// step=2 직진 방지
if ($step === 2 && empty($prefill['username'])) {
    if ($mode === 'edit' && !isset($_SESSION['edit_account_step1'])) $step = 1;
    if ($mode === 'create' && !isset($_SESSION['create_account_step1'])) $step = 1;
}

// layout
$page_title = t('account.create_2step_title');
$content_file = __DIR__ . "/create_account_content.php";
include "layout.php";