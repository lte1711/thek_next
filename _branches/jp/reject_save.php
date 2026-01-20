<?php
session_start();
error_log("[REJECT_SAVE] HIT " . date('c') . " REQUEST_METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'NONE'));

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

include 'db_connect.php';

// ✅ JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');

// ✅ 권한: Zayne + superadmin 고정
$username = $_SESSION['username'] ?? '';
$role     = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !($username === 'Zayne' || $role === 'superadmin')) {
    echo json_encode(['ok'=>false,'msg'=>'Forbidden']);
    exit;
}

/**
 * Reject 처리 (tx_id 기준으로 Ready/Reject/Completed 흐름 통일)
 * - user_transactions: id(tx_id)로 UPDATE
 * - {region}_ready_trading: tx_id로 UPSERT(INSERT ... ON DUPLICATE KEY UPDATE)
 * - {region}_progressing: 기존 UNIQUE(user_id, tx_date, pair) 구조 유지하되 REPLACE로 갱신
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'msg'=>'Method Not Allowed']);
    exit;
}

$user_id  = (int)($_POST['user_id'] ?? 0);
$ready_id = (int)($_POST['ready_id'] ?? 0); // optional
$tx_id    = (int)($_POST['tx_id'] ?? 0);    // required (or resolvable via ready_id)
$reason   = trim($_POST['reason'] ?? '');
$region   = $_POST['region'] ?? 'korea';
$allowed_regions = ['korea', 'japan'];
if (!in_array($region, $allowed_regions, true)) $region = 'korea';

$table_ready    = $region . "_ready_trading";
$table_progress = $region . "_progressing";

$reject_by_user_id = (int)($_SESSION['user_id'] ?? 0);
$reject_by_name    = $_SESSION['username'] ?? (string)$reject_by_user_id;

// ✅ 디버그 로그: POST 파라미터 확인
error_log("[REJECT_SAVE] POST=" . json_encode($_POST));

if ($user_id <= 0 || $reason === '' || ($tx_id <= 0 && $ready_id <= 0)) {
    echo json_encode(['ok'=>false,'msg'=>'Invalid input']);
    exit;
}

try {
    $conn->begin_transaction();

    // -------------------------------------------------
    // 1) tx_id 확정 (ready_id가 있으면 우선 ready_trading에서 tx_id 얻기)
    // -------------------------------------------------
    if ($tx_id <= 0 && $ready_id > 0) {
        $sql_get = "SELECT tx_id, tx_date FROM {$table_ready} WHERE id=? AND user_id=? LIMIT 1";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->bind_param("ii", $ready_id, $user_id);
        $stmt_get->execute();
        $row = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$row) {
            throw new Exception("Ready record not found");
        }

        $tx_id_from_ready = (int)($row['tx_id'] ?? 0);
        $tx_date_from_ready = $row['tx_date'] ?? null;

        if ($tx_id_from_ready > 0) {
            $tx_id = $tx_id_from_ready;
        } else {
            // legacy: ready_trading에 tx_id가 비어있으면, 동일 user/date 거래 1건 매칭(최근)
            if (!$tx_date_from_ready) {
                throw new Exception("Ready tx_date not found");
            }
            $sql_find = "SELECT id FROM user_transactions WHERE user_id=? AND tx_date=? ORDER BY id DESC LIMIT 1";
            $stmt_find = $conn->prepare($sql_find);
            $stmt_find->bind_param("is", $user_id, $tx_date_from_ready);
            $stmt_find->execute();
            $row_tx = $stmt_find->get_result()->fetch_assoc();
            $stmt_find->close();
            if (!$row_tx) {
                throw new Exception("User transaction not found");
            }
            $tx_id = (int)$row_tx['id'];
        }
    }

    // -------------------------------------------------
    // 2) 거래 존재/소유 검증 + tx_date 확보
    // -------------------------------------------------
    $sql_tx = "SELECT id, tx_date FROM user_transactions WHERE id=? AND user_id=? LIMIT 1";
    $stmt_tx = $conn->prepare($sql_tx);
    $stmt_tx->bind_param("ii", $tx_id, $user_id);
    $stmt_tx->execute();
    $tx_row = $stmt_tx->get_result()->fetch_assoc();
    $stmt_tx->close();
    if (!$tx_row) {
        throw new Exception("User transaction not found");
    }
    $tx_date = $tx_row['tx_date'];

    // -------------------------------------------------
    // 3) user_rejects 기록 (기존 스키마 유지)
    // -------------------------------------------------
    $sql1 = "INSERT INTO user_rejects (user_id, reason, created_at) VALUES (?, ?, NOW())";
    $stmt1 = $conn->prepare($sql1);
    if (!$stmt1) {
      error_log("[REJECT_SAVE] user_rejects PREPARE FAIL: " . mysqli_error($conn));
      http_response_code(500);
      echo json_encode(['ok'=>false,'msg'=>'user_rejects prepare failed: ' . mysqli_error($conn)]);
      exit;
    }
    $stmt1->bind_param("is", $user_id, $reason);
    if (!$stmt1->execute()) {
      error_log("[REJECT_SAVE] user_rejects EXECUTE FAIL: " . $stmt1->error);
      http_response_code(500);
      echo json_encode(['ok'=>false,'msg'=>'user_rejects execute failed: ' . $stmt1->error]);
      exit;
    }
    $affected_rejects = $stmt1->affected_rows;
    $stmt1->close();

    error_log("[REJECT_SAVE] user_rejects affected_rows={$affected_rejects}");

    // -------------------------------------------------
    // 4) user_transactions 업데이트 (tx_id 기준)
    // -------------------------------------------------
    $sql2 = "UPDATE user_transactions
                SET settle_chk=2,
                    external_done_chk=0,
                    reject_reason=?,
                    reject_by=?,
                    reject_date=NOW()
              WHERE id=? AND user_id=?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("ssii", $reason, $reject_by_name, $tx_id, $user_id);
    if (!$stmt2->execute()) throw new Exception("user_transactions error: " . $stmt2->error);
    $affected_tx = $stmt2->affected_rows;
    $stmt2->close();

    error_log("[REJECT_SAVE] user_transactions affected_rows={$affected_tx}");

    // -------------------------------------------------
    // 5) ready_trading UPSERT (tx_id UNIQUE 기준)
    //    - 신규/중복/재시도 모두 안전
    // -------------------------------------------------
    $sql3 = "INSERT INTO {$table_ready}
                (user_id, tx_id, tx_date, status, reject_reason, reject_by, reject_date, updated_at)
             VALUES
                (?, ?, ?, 'rejected', ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                user_id=VALUES(user_id),
                tx_date=VALUES(tx_date),
                status='rejected',
                reject_reason=VALUES(reject_reason),
                reject_by=VALUES(reject_by),
                reject_date=NOW(),
                updated_at=NOW()";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("iisss", $user_id, $tx_id, $tx_date, $reason, $reject_by_name);
    if (!$stmt3->execute()) throw new Exception("ready_trading error: " . $stmt3->error);
    $affected_ready = $stmt3->affected_rows;
    $stmt3->close();

    error_log("[REJECT_SAVE] ready_trading affected_rows={$affected_ready}");

    // -------------------------------------------------
    // 6) progressing 갱신 (기존 UNIQUE(user_id, tx_date, pair) 구조 유지)
    //    - 중복이면 REPLACE로 교체
    // -------------------------------------------------
    $sql4 = "INSERT INTO {$table_progress}
                (tx_id, user_id, tx_date, pair, deposit_status, withdrawal_status,
                 profit_loss, notes, settled_by, settled_date, created_at, reject_reason)
             SELECT t.id AS tx_id,
                    t.user_id,
                    DATE(t.tx_date),
                    COALESCE(d.selected_broker,''),
                    (t.xm_value+t.ultima_value),
                    (t.xm_total+t.ultima_total),
                    (t.xm_total+t.ultima_total)-(t.xm_value+t.ultima_value),
                    'Rejected transaction',
                    ?,
                    NOW(),
                    NOW(),
                    ?
               FROM user_transactions t
               JOIN user_details d ON t.user_id=d.user_id
              WHERE t.id=?
             ON DUPLICATE KEY UPDATE
                    user_id=VALUES(user_id),
                    tx_date=VALUES(tx_date),
                    pair=VALUES(pair),
                    withdrawal_status=VALUES(withdrawal_status),
                    profit_loss=VALUES(profit_loss),
                    notes=VALUES(notes),
                    settled_by=VALUES(settled_by),
                    settled_date=NOW(),
                    reject_reason=VALUES(reject_reason)";

    $stmt4 = $conn->prepare($sql4);
    $stmt4->bind_param("ssi", $reject_by_name, $reason, $tx_id);
    if (!$stmt4->execute()) throw new Exception("progressing error: " . $stmt4->error);
    $affected_prog = $stmt4->affected_rows;
    $stmt4->close();

    error_log("[REJECT_SAVE] progressing affected_rows={$affected_prog}");
    error_log("[REJECT_SAVE] SQL_ERR=" . mysqli_error($conn));

    $conn->commit();
    http_response_code(200);
    error_log("[REJECT_SAVE] SUCCESS tx_id={$tx_id} user_id={$user_id}");
    echo json_encode(['ok'=>true,'msg'=>'Reject completed']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("[REJECT_SAVE] EXCEPTION: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}

$conn->close();
