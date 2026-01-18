<?php
/**
 * reject_reset.php
 * 목적: rejected 거래를 "재진행(Reset)" 처리하여 다시 진행할 수 있게 상태를 되돌린다.
 * 기준: tx_id (user_transactions.id)
 *
 * 처리(트랜잭션):
 *  1) user_transactions: reject 관련 컬럼 NULL, settle_chk=0
 *  2) {region}_ready_trading: status='ready', reject_* NULL, updated_at=NOW()
 *  3) {region}_progressing: notes='Regenerated', reject_reason=NULL, updated_at=NOW()
 *     (korea_progressing에는 status 컬럼이 없으므로 notes/reject_reason만 정리)
 *
 * 완료 후: profit_share.php 로 이동 (회원 페이지)
 */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
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


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php'; // $conn

$region = $_POST['region'] ?? ($_GET['region'] ?? 'korea');
$region = strtolower(trim($region));
$region = preg_replace('/[^a-z0-9_]/', '', $region);

$table_ready = $region . '_ready_trading';
$table_prog  = $region . '_progressing';

// tx_id 추출
$tx_id = 0;
foreach (['tx_id','id'] as $k) {
    if (isset($_POST[$k])) { $tx_id = (int)$_POST[$k]; break; }
}
if ($tx_id <= 0) {
    foreach (['tx_id','id'] as $k) {
        if (isset($_GET[$k])) { $tx_id = (int)$_GET[$k]; break; }
    }
}

// 레거시: ready_id로 넘어오는 경우 tx_id 역조회
$ready_row_id = 0;
if (isset($_POST['ready_id'])) $ready_row_id = (int)$_POST['ready_id'];
elseif (isset($_GET['ready_id'])) $ready_row_id = (int)$_GET['ready_id'];

try {
    if ($tx_id <= 0 && $ready_row_id > 0) {
        $stmt = $conn->prepare("SELECT tx_id FROM `$table_ready` WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $ready_row_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $tx_id = (int)($row['tx_id'] ?? 0);
        }
        $stmt->close();
    }

    if ($tx_id <= 0) {
        throw new Exception(t('error.tx_id_empty','tx_id is empty.'));
    }

    $conn->begin_transaction();

    // 1) user_transactions: Reject 관련 값 초기화 + 정산 재처리 가능하도록 settle_chk=0
    $stmt1 = $conn->prepare("
        UPDATE user_transactions
           SET settle_chk = 0,
               reject_reason = NULL,
               reject_by = NULL,
               reject_date = NULL
         WHERE id = ?
         LIMIT 1
    ");
    $stmt1->bind_param('i', $tx_id);
    $stmt1->execute();
    $stmt1->close();

    // 2) ready_trading: rejected -> ready
    $stmt2 = $conn->prepare("
        UPDATE `$table_ready`
           SET status = 'ready',
               reject_reason = NULL,
               reject_by = NULL,
               reject_date = NULL,
               updated_at = NOW()
         WHERE tx_id = ?
    ");
    $stmt2->bind_param('i', $tx_id);
    $stmt2->execute();
    $affected = $stmt2->affected_rows;
    $stmt2->close();

    // 레거시: tx_id가 없던 row는 ready_row_id로 되돌리면서 tx_id 채움
    if ($affected <= 0 && $ready_row_id > 0) {
        $stmt2b = $conn->prepare("
            UPDATE `$table_ready`
               SET status = 'ready',
                   reject_reason = NULL,
                   reject_by = NULL,
                   reject_date = NULL,
                   updated_at = NOW(),
                   tx_id = ?
             WHERE id = ?
             LIMIT 1
        ");
        $stmt2b->bind_param('ii', $tx_id, $ready_row_id);
        $stmt2b->execute();
        $stmt2b->close();
    }

    // 3) progressing: Rejected 흔적 제거 + 리젠 완료 표기 (status 컬럼 없음 → notes 사용)
    $stmt3 = $conn->prepare("
        UPDATE `$table_prog`
           SET notes = 'Regenerated',
               reject_reason = NULL,
               updated_at = NOW()
         WHERE tx_id = ?
    ");
    $stmt3->bind_param('i', $tx_id);
    $stmt3->execute();
    $stmt3->close();

    $conn->commit();

    header('Location: profit_share.php');
    exit;

} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try { $conn->rollback(); } catch (Throwable $t) {}
    }
    http_response_code(500);
    $prefix = function_exists("t") ? t("err.reject_reset_failed_prefix", "Reject Reset failed: ") : "Reject Reset failed: ";
    echo $prefix . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8");
    exit;
}
