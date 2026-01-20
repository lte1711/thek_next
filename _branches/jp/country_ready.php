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

// ✅ 권한: Zayne + superadmin 고정
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

$table_ready    = $region . "_ready_trading";
$table_progress = $region . "_progressing";

function columnExists(mysqli $conn, string $table, string $column): bool {
    $sql = "SELECT COUNT(*) AS cnt
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int)($row['cnt'] ?? 0) > 0);
}

$has_tx_id = columnExists($conn, $table_ready, 'tx_id');

$ready_per_page = 50;
$ready_page = isset($_GET['ready_page']) ? max(1, (int)$_GET['ready_page']) : 1;
$ready_offset = ($ready_page - 1) * $ready_per_page;

$ready_join = $has_tx_id
  ? "LEFT JOIN {$table_ready} r ON r.tx_id = t.id AND r.user_id = t.user_id"
  : "LEFT JOIN {$table_ready} r ON r.user_id = t.user_id AND r.tx_date = DATE(t.tx_date)";

$sql_ready = "
  SELECT
    t.id      AS tx_id,
    t.user_id AS user_id,
    DATE(t.tx_date) AS tx_date,

    r.id     AS ready_id,
    r.status AS status,

    u.username,
    d.xm_id, d.xm_pw, d.xm_server,
    d.ultima_id, d.ultima_pw, d.ultima_server,

    COALESCE(t.xm_value,0)     AS xm_value,
    COALESCE(t.ultima_value,0) AS ultima_value,
    COALESCE(t.settle_chk,0)   AS settle_chk,
    COALESCE(t.external_done_chk,0) AS external_done_chk,

    t.reject_reason, t.reject_by, t.reject_date,

    COALESCE(
      t.settled_date,
      (SELECT MAX(p.settled_date)
         FROM {$table_progress} p
        WHERE p.user_id = t.user_id AND p.tx_date = DATE(t.tx_date))
    ) AS settled_date

    ,
    -- ✅ progressing_id: OK 처리 시 무조건 이 ID로 업데이트 (중복 방지)
    (
      SELECT p2.id
      FROM {$table_progress} p2
      WHERE p2.user_id = t.user_id
        AND p2.tx_date = DATE(t.tx_date)
      ORDER BY (p2.pair='xm,ultima') DESC, p2.deposit_status DESC, p2.id DESC
      LIMIT 1
    ) AS progressing_id

  FROM user_transactions t
  {$ready_join}
  LEFT JOIN user_details d ON d.user_id = t.user_id
  LEFT JOIN users u ON u.id = t.user_id

  WHERE COALESCE(t.withdrawal_chk,0) = 0
    -- Ready only: never show completed/rejected rows (including settlement rejects)
    AND COALESCE(t.settle_chk,0) <> 2
    AND (COALESCE(t.reject_by,0) = 0)
    AND (r.status IS NULL OR r.status='ready')

  ORDER BY DATE(t.tx_date) DESC, t.id DESC
  LIMIT {$ready_per_page} OFFSET {$ready_offset}
";

$result_ready = mysqli_query($conn, $sql_ready);
if (!$result_ready) {
    error_log("country_ready.php Ready SQL Error: " . mysqli_error($conn));
}

$is_country_page = true;
$page_title = "";
$page_css = 'korea.css';
$content_file = __DIR__ . "/country_ready_content.php";
include "layout.php";
