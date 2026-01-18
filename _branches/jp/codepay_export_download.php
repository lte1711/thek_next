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


// i18n
if (!function_exists('t')) {
    $i18n = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n)) require_once $i18n;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    exit(t('error.unauthorized', 'Unauthorized'));
}
if (strtolower((string)$_SESSION['role']) !== 'gm') {
    http_response_code(403);
    exit(t('error.forbidden', 'Forbidden'));
}

require_once __DIR__ . '/db_connect.php'; // $conn

error_reporting(E_ALL);
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');

/**
 * ✅ 다운로드 후 버튼 사라지게(자동)
 * - status='pending' 항목만 CSV로 출력
 * - 출력된 항목은 즉시 status='sent'로 UPDATE (트랜잭션)
 *
 * ✅ 입력 호환
 * - 개별:  ?batch_id=123  (GET)
 * - 다중:  batch_ids[]=1&batch_ids[]=2 (POST/GET)
 * - 다중:  batch_ids=1,2,3 (GET)
 */

$batch_ids = [];

// batch_ids 우선 (POST/GET)
if (isset($_REQUEST['batch_ids'])) {
    $raw = $_REQUEST['batch_ids'];
    if (is_array($raw)) {
        $batch_ids = $raw;
    } else {
        $batch_ids = explode(',', (string)$raw);
    }
}
// 개별 batch_id 지원
elseif (isset($_REQUEST['batch_id'])) {
    $batch_ids = [(int)$_REQUEST['batch_id']];
}

$batch_ids = array_values(array_filter(array_map('intval', $batch_ids)));

if (count($batch_ids) === 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo t('error.invalid_batch_id', 'Invalid batch_id');
    exit;
}

// 테이블 존재 확인
$chkSql = "
    SELECT 1
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'codepay_payout_items'
    LIMIT 1
";
$chk = $conn->query($chkSql);
if (!$chk || $chk->num_rows === 0) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo t('err.codepay_table_missing', 'codepay_payout_items table not found.');
    exit;
}

// IN 절 placeholder 생성
$in = implode(',', array_fill(0, count($batch_ids), '?'));
$types = str_repeat('i', count($batch_ids));

// 1) pending 대상 먼저 조회 (CSV 출력 대상)
$sqlSelect = "
    SELECT
        i.id,
        i.batch_id,
        i.codepay_address_snapshot AS codepay_address,
        i.amount,
        u.username,
        u.phone,
        i.role,
        i.user_id,
        i.dividend_id
    FROM codepay_payout_items i
    JOIN users u ON u.id = i.user_id
    WHERE i.batch_id IN ($in)
      AND i.status = 'pending'
    ORDER BY i.batch_id ASC, i.amount DESC, i.id ASC
";

$stmt = $conn->prepare($sqlSelect);
$stmt->bind_param($types, ...$batch_ids);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$item_ids = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
    $item_ids[] = (int)$row['id'];
}
$stmt->close();

if (count($rows) === 0) {
    http_response_code(200);
    header('Content-Type: text/plain; charset=UTF-8');
    echo t('msg.no_pending_items_to_export', 'No pending items to export.');
    exit;
}

try {
    $conn->begin_transaction();

    // 2) 조회된 id만 sent로 업데이트
    $in2 = implode(',', array_fill(0, count($item_ids), '?'));
    $types2 = str_repeat('i', count($item_ids));

    $sqlUpdate = "UPDATE codepay_payout_items SET status='sent' WHERE id IN ($in2)";
    $stmtU = $conn->prepare($sqlUpdate);
    $stmtU->bind_param($types2, ...$item_ids);
    $stmtU->execute();
    $stmtU->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Export failed: " . htmlspecialchars($e->getMessage());
    exit;
}

// 3) CSV 출력
$filename = "codepay_export_" . date('Ymd_His') . ".csv";
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

// Excel 한글 깨짐 방지 BOM
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// 헤더(원하시면 순서/컬럼 최소화 가능)
fputcsv($out, ['batch_id','codepay_address','amount','username','phone','role','user_id','dividend_id','status']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['batch_id'],
        $r['codepay_address'],
        $r['amount'],
        $r['username'],
        $r['phone'],
        $r['role'],
        $r['user_id'],
        $r['dividend_id'],
        'sent',
    ]);
}

fclose($out);
$conn->close();
exit;
