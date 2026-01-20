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

$join_ready = $has_tx_id
  ? "LEFT JOIN {$table_ready} r ON r.tx_id = t.id AND r.user_id = t.user_id"
  : "LEFT JOIN {$table_ready} r ON r.user_id = t.user_id AND r.tx_date = DATE(t.tx_date)";

// ✅ Completed: approved + rejected (C/L)
$sql_completed = "
  SELECT
    t.id      AS tx_id,
    t.user_id AS user_id,
    DATE(t.tx_date) AS tx_date,

    COALESCE(t.settle_chk,0) AS settle_chk,

    r.id     AS ready_id,
    r.status AS status,

    u.username,
    d.xm_id, d.xm_pw, d.xm_server,
    d.ultima_id, d.ultima_pw, d.ultima_server,

    COALESCE(t.xm_value,0)     AS xm_value,
    COALESCE(t.ultima_value,0) AS ultima_value,

    t.reject_reason, t.reject_by, t.reject_date,

    (
      SELECT MAX(p.settled_date)
      FROM {$table_progress} p
      WHERE p.user_id = t.user_id
        AND p.tx_date = DATE(t.tx_date)
    ) AS settled_date

  FROM user_transactions t
  {$join_ready}
  LEFT JOIN user_details d ON d.user_id = t.user_id
  LEFT JOIN users u ON u.id = t.user_id
  LEFT JOIN (
    SELECT user_id, tx_date, MAX(id) AS max_pid
    FROM {$table_progress}
    GROUP BY user_id, tx_date
  ) pm ON pm.user_id = t.user_id AND pm.tx_date = DATE(t.tx_date)
  LEFT JOIN {$table_progress} p ON p.id = pm.max_pid

	  WHERE (
      (
        COALESCE(t.deposit_chk,0) = 1
        AND COALESCE(t.withdrawal_chk,0) = 1
        AND COALESCE(t.settle_chk,0) = 1
        AND COALESCE(t.dividend_chk,0) = 1
      )
      OR r.status IN ('rejected','rejecting')
      OR COALESCE(t.settle_chk,0) = 2
    )

  ORDER BY DATE(t.tx_date) DESC, t.id DESC
  LIMIT 200
";

$result_completed = mysqli_query($conn, $sql_completed);
if (!$result_completed) {
    error_log("country_completed.php Completed SQL Error: " . mysqli_error($conn));
}

$is_country_page = true;
$page_title = "";
$page_css = 'korea.css';
$content_file = __DIR__ . "/country_completed_content.php";
include "layout.php";
