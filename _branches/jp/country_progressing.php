<?php
session_start();

// Force English only - no i18n
if (!function_exists('t')) {
    function t($key, $fallback = null) {
        return $fallback ?? $key;
    }
    function current_lang() {
        return 'en';
    }
}

// Prevent layout.php from loading i18n again
define('I18N_LOADED', true);

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
if (!($username === 'Zayne' || $role === 'superadmin')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

include 'db_connect.php';

$region = $_GET['region'] ?? 'korea';
$allowed_regions = ['korea', 'japan'];
if (!in_array($region, $allowed_regions, true)) $region = 'korea';

$table_progress = $region . "_progressing";
$table_ready = $region . "_ready_trading";

// ✅ 자동 동기화 (Ready → Progressing 행 생성) : 기존 로직 유지
$sync_sql = "
  INSERT INTO {$table_progress}
    (user_id, tx_date, pair, deposit_status, withdrawal_status, profit_loss, notes, created_at, settled_by, settled_date, reject_reason)
  SELECT
    t.user_id,
    DATE(t.tx_date) AS tx_date,
    'xm,ultima' AS pair,
    (COALESCE(t.xm_value,0) + COALESCE(t.ultima_value,0)) AS deposit_status,
    (COALESCE(t.xm_total,0) + COALESCE(t.ultima_total,0)) AS withdrawal_status,
    ((COALESCE(t.xm_total,0) + COALESCE(t.ultima_total,0)) - (COALESCE(t.xm_value,0) + COALESCE(t.ultima_value,0))) AS profit_loss,
    NULL AS notes,
    COALESCE(t.settled_date, t.created_at) AS created_at,
    t.settled_by,
    t.settled_date,
    t.reject_reason
  FROM user_transactions t
  INNER JOIN {$table_ready} r ON r.tx_id = t.id AND r.user_id = t.user_id
  WHERE COALESCE(t.withdrawal_chk,0) = 0
        AND NOT EXISTS (
      SELECT 1
      FROM {$table_progress} p
      WHERE p.user_id = t.user_id
        AND p.tx_date = DATE(t.tx_date)
        AND p.pair = 'xm,ultima'
    )
";

$sync_res = mysqli_query($conn, $sync_sql);
if (!$sync_res) {
    error_log("country_progressing.php Sync INSERT Error: " . mysqli_error($conn));
}

$progress_per_page = 50;
$progress_page = isset($_GET['progress_page']) ? max(1, (int)$_GET['progress_page']) : 1;
$progress_offset = ($progress_page - 1) * $progress_per_page;

$sql_progress = "
  SELECT
    p.id,
    p.user_id,
    p.tx_date,
    u.username,
    p.pair,
    p.deposit_status,
    p.withdrawal_status,
    p.profit_loss,
    p.notes,
    p.created_at,
    p.settled_by,
    p.settled_date,
    COALESCE(t.withdrawal_chk, 0) AS withdrawal_chk,
    COALESCE(t.dividend_chk, 0)   AS dividend_chk,
    COALESCE(t.settle_chk, 0)     AS settle_chk
  FROM {$table_progress} p
  JOIN users u ON u.id = p.user_id
  LEFT JOIN (
    SELECT user_id, DATE(tx_date) AS tx_date, MAX(id) AS max_id
    FROM user_transactions
    GROUP BY user_id, DATE(tx_date)
  ) m ON m.user_id = p.user_id AND m.tx_date = p.tx_date
  LEFT JOIN user_transactions t ON t.id = m.max_id
  WHERE NOT (
    (
      COALESCE(t.deposit_chk,0) = 1
      AND COALESCE(t.withdrawal_chk,0) = 1
      AND COALESCE(t.settle_chk,0) = 1
      AND COALESCE(t.dividend_chk,0) = 1
    )
    OR (
      COALESCE(p.deposit_status,'') = 'V'
      AND COALESCE(p.withdrawal_status,'') = 'V'
      AND COALESCE(p.profit_loss,'') = 'V'
    )
  )
    AND COALESCE(t.settle_chk,0) <> 2

  ORDER BY p.tx_date DESC
  LIMIT {$progress_per_page} OFFSET {$progress_offset}
";

$result_progress = mysqli_query($conn, $sql_progress);
if (!$result_progress) {
    error_log("country_progressing.php Progress SQL Error: " . mysqli_error($conn));
}

$is_country_page = true;
$page_title = "";
$page_css = 'korea.css';
$content_file = __DIR__ . "/country_progressing_content.php";
include "layout.php";
