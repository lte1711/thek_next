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

// ✅ 페이지네이션 변수 통일 (20개 단위)
$per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// ✅ base_query: 필터 파라미터 유지
$qs = [];
$qs['region'] = $region;
if (!empty($_GET['from'])) $qs['from'] = $_GET['from'];
if (!empty($_GET['to'])) $qs['to'] = $_GET['to'];
if (!empty($_GET['q'])) $qs['q'] = $_GET['q'];
$base_query = http_build_query($qs);

// ✅ COUNT 쿼리 (Ready 테이블 메인)
$sql_count = "
  SELECT COUNT(*) AS cnt
  FROM {$table_ready} r
  JOIN users u ON u.id = r.user_id
  LEFT JOIN user_transactions t ON t.id = r.tx_id
  WHERE (r.status IS NULL OR r.status = 'ready')
    AND (COALESCE(t.withdrawal_chk,0) = 0 AND COALESCE(t.settle_chk,0) <> 2)
";

$total_count = 0;
$total_pages = 1;
$res_cnt = mysqli_query($conn, $sql_count);
if ($res_cnt) {
    $row_cnt = mysqli_fetch_assoc($res_cnt);
    $total_count = (int)($row_cnt['cnt'] ?? 0);
}
$total_pages = max(1, (int)ceil($total_count / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// ✅ Ready 목록 쿼리 (Ready 테이블 메인으로 리팩터링)
$sql_ready = "
  SELECT
    r.id     AS ready_id,
    r.user_id AS user_id,
    r.tx_id  AS tx_id,
    r.tx_date AS tx_date,
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
        WHERE p.user_id = r.user_id AND p.tx_date = r.tx_date)
    ) AS settled_date,

    -- ✅ progressing_id: OK 처리 시 무조건 이 ID로 업데이트 (중복 방지)
    (
      SELECT p2.id
      FROM {$table_progress} p2
      WHERE p2.user_id = r.user_id
        AND p2.tx_date = r.tx_date
      ORDER BY (p2.pair='xm,ultima') DESC, p2.deposit_status DESC, p2.id DESC
      LIMIT 1
    ) AS progressing_id

  FROM {$table_ready} r
  JOIN users u ON u.id = r.user_id
  LEFT JOIN user_transactions t ON t.id = r.tx_id
  LEFT JOIN user_details d ON d.user_id = r.user_id

  WHERE (r.status IS NULL OR r.status = 'ready')
    AND (COALESCE(t.withdrawal_chk,0) = 0 AND COALESCE(t.settle_chk,0) <> 2)

  ORDER BY r.tx_date DESC, r.id DESC
  LIMIT {$per_page} OFFSET {$offset}
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
