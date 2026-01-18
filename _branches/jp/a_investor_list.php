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
    header("Location: login.php");
    exit;
}

// DB 연결
include 'db_connect.php';

// === language helper (NO t() here; keep logic unchanged) ===
$__ail_lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ko');
$__ail_lang = strtolower(trim((string)$__ail_lang));
if (!in_array($__ail_lang, ['ko','ja','en'], true)) $__ail_lang = 'ko';
function __ail_tr(string $ko, string $ja, string $en): string {
    global $__ail_lang;
    if ($__ail_lang === 'ja') return $ja;
    if ($__ail_lang === 'en') return $en;
    return $ko;
}

// 회원 조회 쿼리 (등급이 investor인 회원만)
$login_user_id = (int)$_SESSION['user_id'];

// ✅ sponsor_id 기준으로 하위 전체(downline) 수집
$ids = [];
$queue = [$login_user_id];

while (!empty($queue)) {
    $current = array_shift($queue);

    $q = mysqli_query($conn, "SELECT id FROM users WHERE sponsor_id=" . (int)$current);
    if (!$q) {
        die("Downline query failed: " . mysqli_error($conn));
    }

    while ($r = mysqli_fetch_assoc($q)) {
        $cid = (int)$r['id'];
        if (!isset($ids[$cid])) {
            $ids[$cid] = true;
            $queue[] = $cid;
        }
    }
}

$downline_ids = array_keys($ids);

// ✅ 하위가 없으면 빈 결과
if (count($downline_ids) === 0) {
    $sql = "SELECT id, username, email, role, phone, country FROM users WHERE 1=0";
} else {
    $in = implode(',', array_map('intval', $downline_ids));

    // ✅ 하위 중 investor만
    $sql = "
        SELECT id, username, email, role, phone, country
        FROM users
        WHERE role='investor'
          AND id IN ($in)
        ORDER BY id DESC
    ";
}

$result = mysqli_query($conn, $sql);
if (!$result) {
    die('Query failed: ' . mysqli_error($conn));
}

// GET 요청일 때만 레이아웃 출력
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $page_title = __ail_tr("INVESTOR 회원 리스트", "投資家会員リスト", "Investor Member List");
    $content_file = __DIR__ . "/a_investor_list_content.php";
    include "layout.php";
}
