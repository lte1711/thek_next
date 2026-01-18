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


// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ edit_agent.php는 더 이상 단독 수정 페이지로 사용하지 않고,
// ✅ 공통 수정 페이지(create_account.php)로 통일합니다.

// 대상 ID 결정: POST → GET → SESSION 순서
$target_id = $_POST['id'] ?? ($_GET['id'] ?? ($_SESSION['user_id'] ?? null));
$target_id = is_numeric($target_id) ? (int)$target_id : 0;

// role 기준으로 안전하게 보정
$user_role = strtolower(trim((string)($_SESSION['role'] ?? '')));

// agent는 반드시 본인만 수정하도록 강제
if ($user_role === 'agent') {
    $target_id = (int)$_SESSION['user_id'];
}

// redirect 파라미터 유지 (없으면 역할별 기본값)
$redirect = $_GET['redirect'] ?? '';
$redirect = $redirect !== '' ? basename((string)$redirect) : '';

if ($redirect === '') {
    if ($user_role === 'agent') {
        $redirect = 'c_investor_list.php';
    } elseif ($user_role === 'master') {
        $redirect = 'a_agent_list.php';
    } elseif ($user_role === 'admin') {
        $redirect = 'b_agent_list.php';
    } elseif ($user_role === 'gm') {
        $redirect = 'b_agent_list.php';
    } else {
        $redirect = 'create_account.php';
    }
}

if ($target_id <= 0) {
    header("Location: " . $redirect);
    exit;
}

// ✅ 공통 수정 화면으로 이동
$qs = "mode=edit&id=" . urlencode((string)$target_id);
if ($redirect !== '') {
    $qs .= "&redirect=" . urlencode($redirect);
}
header("Location: create_account.php?$qs");
exit;
