<?php
/**
 * settle_toggle.php
 * - 투자자 Profit Share List의 ON 버튼 처리
 * - 요구사항:
 *   1) user_transactions.settle_chk = 1 로만 업그레이드(정산 완료 표시)
 *   2) codepay_payout_items 에도 (가능하면) pending 아이템 등록
 *   3) 중복 등록 방지: 같은 batch_id + user_id + amount + codepay_address_snapshot 조합이 이미 있으면 INSERT 하지 않음
 */
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


// i18n (t()) 로드 - 없으면 fatal 방지
if (!function_exists('t')) {
    $i18n1 = __DIR__ . '/includes/i18n.php';
    $i18n2 = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n1)) { require_once $i18n1; }
    elseif (file_exists($i18n2)) { require_once $i18n2; }
}

include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ 입력값
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$tx_id   = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$region  = $_POST['region'] ?? 'korea';
$month   = $_POST['month'] ?? date('m');

if (!$user_id || !$tx_id) {
    header("Location: profit_share.php?user_id=" . urlencode((string)($_SESSION['user_id'] ?? '')));
    exit;
}

// ✅ 권한(최소): 본인만 처리 (필요하면 superadmin 예외 추가 가능)
$session_user_id = (int)$_SESSION['user_id'];
if ($session_user_id !== (int)$user_id) {
    // superadmin/관리자 예외가 필요하면 여기서 role 검사 후 허용
    // 지금은 안전하게 본인만 허용
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$conn->begin_transaction();

try {
    // 1) 거래 확인 (잠금)
    $stmt = $conn->prepare("
        SELECT id, user_id, tx_date, settle_chk,
               COALESCE(dividend_amount, 0) AS dividend_amount,
               COALESCE(xm_dividend, 0) AS xm_dividend,
               COALESCE(ultima_dividend, 0) AS ultima_dividend
        FROM user_transactions
        WHERE id = ? AND user_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $tx_id, $user_id);
    $stmt->execute();
    $tx = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tx) {
        throw new Exception(t('err.transaction_not_found'));
    }

    // 이미 settle_chk=1이면 그냥 리다이렉트 (idempotent)
    if ((int)$tx['settle_chk'] === 1) {
        $conn->commit();
        header("Location: profit_share.php?user_id={$user_id}&region=" . urlencode($region) . "&month=" . urlencode($month));
        exit;
    }

    // 2) settle_chk=1로 업데이트 (요구사항: 이것만 업그레이드)
    $settled_by = (string)($_SESSION['username'] ?? $_SESSION['user_id']); // username 세션 없으면 user_id로
        $upd = $conn->prepare("
            UPDATE user_transactions
            SET settle_chk = 1,
                settled_by = ?,
                settled_date = NOW()
            WHERE id = ? AND user_id = ? AND settle_chk = 0
        ");
        $upd->bind_param("sii", $settled_by, $tx_id, $user_id);
        if (!$upd->execute()) {
            throw new Exception(t('err.settlement_update_failed_prefix') . $upd->error);
        }
        $upd->close();

    // 3) codepay_payout_items 등록 (가능하면)
    // - batch_key: profitshare_{region}_{tx_date}
    // - amount: dividend_amount 우선, 없으면 xm_dividend+ultima_dividend
    $tx_date = $tx['tx_date'];

    $amount = (float)$tx['dividend_amount'];
    if ($amount <= 0) {
        $amount = (float)$tx['xm_dividend'] + (float)$tx['ultima_dividend'];
    }

    // codepay 주소/role 조회
    $stmt = $conn->prepare("
        SELECT u.role,
               ud.codepay_address
        FROM users u
        LEFT JOIN user_details ud ON ud.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $role = $u['role'] ?? null;
    $codepay_address = $u['codepay_address'] ?? null;

    // batch 확보 (없으면 생성)
    $batch_key = "profitshare_" . $region . "_" . $tx_date; // UNIQUE
    $batch_id = null;

    $stmt = $conn->prepare("SELECT id FROM codepay_payout_batches WHERE batch_key = ? LIMIT 1");
    $stmt->bind_param("s", $batch_key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $batch_id = (int)$row['id'];
    } else {
        $insb = $conn->prepare("
            INSERT INTO codepay_payout_batches (batch_key, dividend_id, period_start, period_end, created_by)
            VALUES (?, NULL, ?, ?, ?)
        ");
        $insb->bind_param("ssss", $batch_key, $tx_date, $tx_date, $settled_by);
        if (!$insb->execute()) {
            throw new Exception(t('err.batch_create_failed_prefix') . $insb->error);
        }
        $batch_id = (int)$conn->insert_id;
        $insb->close();
    }

    // payout item insert (중복 방지)
    // - 주소가 없거나 amount<=0이면 item은 만들지 않음(정산은 완료됨)
    if ($batch_id && $amount > 0 && $codepay_address !== null && trim($codepay_address) !== '') {
        $addr_snapshot = trim($codepay_address);

        $chk = $conn->prepare("
            SELECT id
            FROM codepay_payout_items
            WHERE batch_id = ? AND user_id = ? AND amount = ? AND codepay_address_snapshot = ?
            LIMIT 1
        ");
        $chk->bind_param("iids", $batch_id, $user_id, $amount, $addr_snapshot);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();

        if (!$exists) {
            $insi = $conn->prepare("
                INSERT INTO codepay_payout_items (batch_id, dividend_id, user_id, role, codepay_address_snapshot, amount, status)
                VALUES (?, NULL, ?, ?, ?, ?, 'pending')
            ");
            $insi->bind_param("iissd", $batch_id, $user_id, $role, $addr_snapshot, $amount);
            if (!$insi->execute()) {
                throw new Exception(t('err.payout_item_create_failed_prefix') . $insi->error);
            }
            $insi->close();
        }
    }

    // 4) alert용 settle_seq 계산 (같은 tx_date에서 settle_chk=1 된 순번)
    $seq = 1;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM user_transactions
        WHERE tx_date = ? AND settle_chk = 1
    ");
    $stmt->bind_param("s", $tx_date);
    $stmt->execute();
    $seq_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($seq_row && isset($seq_row['cnt'])) {
        $seq = (int)$seq_row['cnt'];
        if ($seq < 1) $seq = 1;
    }
    $seq_label = str_pad((int)$seq, 2, '0', STR_PAD_LEFT);

    $conn->commit();

    header("Location: profit_share.php?user_id={$user_id}&region=" . urlencode($region) . "&month=" . urlencode($month) . "&settle_seq={$seq_label}&settle_tx={$tx_id}");
    exit;

} catch (Exception $e) {
    try { $conn->rollback(); } catch (Throwable $t) {}
    error_log("settle_toggle error: " . $e->getMessage());
    echo "<h3>".t('error.processing_failed','An error occurred while processing.')."</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
} finally {
    if (isset($conn)) $conn->close();
}
