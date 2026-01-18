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


// 에러 표시 (문제 발생 시 바로 확인용, 운영 시 제거 가능)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB 연결
include 'db_connect.php';

// === language helper (NO t() here; keep logic unchanged) ===
$__aal_lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ko');
$__aal_lang = strtolower(trim((string)$__aal_lang));
if (!in_array($__aal_lang, ['ko','ja','en'], true)) $__aal_lang = 'ko';
function __aal_tr(string $ko, string $ja, string $en): string {
    global $__aal_lang;
    if ($__aal_lang === 'ja') return $ja;
    if ($__aal_lang === 'en') return $en;
    return $ko;
}

$login_user_id = (int)$_SESSION['user_id'];

/**
 * --------------------------------------------------
 * 로그인한 회원 하위(downline) ID 수집
 * 기준 컬럼 : users.sponsor_id
 * --------------------------------------------------
 */
$ids = [];
$queue = [$login_user_id];

while (!empty($queue)) {
    $current = array_shift($queue);

    $q = mysqli_query(
        $conn,
        "SELECT id FROM users WHERE sponsor_id=" . (int)$current
    );

    if (!$q) {
        die("Downline query failed: " . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($q)) {
        $child_id = (int)$row['id'];
        if (!isset($ids[$child_id])) {
            $ids[$child_id] = true;
            $queue[] = $child_id;
        }
    }
}

$downline_ids = array_keys($ids);

/**
 * --------------------------------------------------
 * agent 목록 조회 (하위만)
 * --------------------------------------------------
 */
if (count($downline_ids) === 0) {
    // 하위 회원이 없을 때
    $sql = "SELECT id, username, email, role, phone, country FROM users WHERE 1=0";
} else {
    $in = implode(',', array_map('intval', $downline_ids));

    $sql = "
        SELECT id, username, email, role, phone, country
        FROM users
        WHERE role = 'agent'
          AND id IN ($in)
        ORDER BY id DESC
    ";
}

$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Agent list query failed: " . mysqli_error($conn));
}

/**
 * --------------------------------------------------
 * 화면 출력
 * --------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page_title = __aal_tr("AGENT 회원 리스트", "エージェント会員リスト", "Agent Member List");
    $content_file = __DIR__ . "/a_agent_list_content.php";
    include "layout.php";
}
