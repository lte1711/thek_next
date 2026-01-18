<?php
// reject_restore.php
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

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

include 'db_connect.php';

try {
    $ready_id = isset($_POST['ready_id']) ? (int)$_POST['ready_id'] : 0;
    $region   = isset($_POST['region']) ? trim($_POST['region']) : 'korea';

    if ($ready_id <= 0) {
        throw new Exception('Invalid ready_id');
    }

    $allowed_regions = ['korea'];
    if (!in_array($region, $allowed_regions, true)) {
        throw new Exception('Invalid region');
    }

    $table_ready = $region . '_ready_trading';
    $table_prog  = $region . '_progressing';

    $stmt = $conn->prepare("SELECT user_id, tx_id, tx_date FROM {$table_ready} WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $ready_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('Ready record not found');
    }

    $user_id = (int)$row['user_id'];
    $tx_date = $row['tx_date'];

    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE {$table_ready} SET status='ready', reject_reason=NULL, reject_by=NULL, reject_date=NULL WHERE id=?");
    $stmt->bind_param("i", $ready_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE {$table_prog} SET notes=NULL WHERE user_id=? AND tx_date=?");
    $stmt->bind_param("is", $user_id, $tx_date);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => t('msg.reject_restore_done','Reject restore completed')]);
} catch (Throwable $e) {
    if (isset($conn)) { try { $conn->rollback(); } catch (Throwable $ignore) {} }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
