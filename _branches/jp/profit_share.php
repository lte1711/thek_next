<?php
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


// i18n loader (for t())
if (!function_exists('t')) {
    $i18n_candidates = [__DIR__ . '/includes/i18n.php', __DIR__ . '/includes/i18n.php'];
    foreach ($i18n_candidates as $p) {
        if (file_exists($p)) { require_once $p; break; }
    }
}
include 'db_connect.php';

// 로그인 확인
$user_id = $_GET['user_id'] ?? ($_SESSION['user_id'] ?? null);
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// region (기본 korea)
$region = $_GET['region'] ?? 'korea';
$allowed_regions = ['korea', 'japan'];
if (!in_array($region, $allowed_regions, true)) {
    http_response_code(400);
    die('invalid region');
}
$table_ready = $region . "_ready_trading";

// 현재 월/년도 (기본: 이번 달)
$year_month = $_GET['year_month'] ?? null;
if ($year_month && preg_match('/^\d{4}-\d{2}$/', $year_month)) {
    $current_year  = substr($year_month, 0, 4);
    $current_month = substr($year_month, 5, 2);
} else {
    $current_month = $_GET['month'] ?? date('m');
    $current_year  = $_GET['year'] ?? date('Y');
    $current_month = str_pad((string)(int)$current_month, 2, '0', STR_PAD_LEFT);
    $year_month    = $current_year . '-' . $current_month;
}

// 거래 내역 조회 (월별 필터링, 개별 id 포함) + 오늘 몇 번째(day_seq) 표시용 ready_trading 조인
$sql = "SELECT 
            t.id, t.tx_date, t.deposit_chk, t.external_done_chk, t.external_done_date, t.withdrawal_chk, t.dividend_chk, t.settle_chk,
            t.reject_reason, t.reject_by, t.settled_by, t.settled_date,
            r.day_seq, r.created_date
        FROM user_transactions t
        LEFT JOIN (
            SELECT rt.user_id, rt.tx_id, rt.day_seq, rt.created_date
            FROM {$table_ready} rt
            INNER JOIN (
                SELECT user_id, tx_id, MAX(id) AS max_id
                FROM {$table_ready}
                GROUP BY user_id, tx_id
            ) x ON x.max_id = rt.id
        ) r ON r.user_id = t.user_id AND r.tx_id = t.id
        WHERE t.user_id = ? AND MONTH(t.tx_date) = ? AND YEAR(t.tx_date) = ?
        ORDER BY t.tx_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $current_month, $current_year);
$stmt->execute();
$month_result = $stmt->get_result();
$stmt->close();

// 레이아웃 적용
$page_title   = "Transaction List";
$content_file = "profit_share_content.php"; // 본문 분리
$menu_type    = "investor"; // 투자자 메뉴 사용
include "layout.php";
