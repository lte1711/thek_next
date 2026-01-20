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


// i18n (for user-facing messages)


// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// DB 연결
require_once __DIR__ . '/db_connect.php';
if ($conn->connect_error) {
    die(t('msg.db_connect_fail') . " " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ✅ 대상 사용자 결정: 일반 투자자는 본인만, 관리자/GM은 ?user_id= 로 대상 지정 가능
$session_user_id = (int)($_SESSION['user_id'] ?? 0);
$session_role = $_SESSION['role'] ?? ($_SESSION['user_role'] ?? '');
$get_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$is_privileged = in_array($session_role, ['superadmin','admin','gm','master'], true);
$user_id = ($is_privileged && $get_user_id > 0) ? $get_user_id : $session_user_id;

// ✅ FK 오류 방지: users 테이블에 대상 user_id 존재 여부 확인
$chk = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
if ($chk) {
    $chk->bind_param('i', $user_id);
    $chk->execute();
    $r = $chk->get_result();
    $exists = ($r && $r->num_rows > 0);
    $chk->close();
    if (!$exists) {
        die(t('err.invalid_user') . ' (user_id=' . htmlspecialchars((string)$user_id) . ')');
    }
}

// 오늘 날짜 기본값
$today = date("Y-m-d");
$selected_date = $_POST['tx_date'] ?? $today;

/**
 * (안전용) 테이블에 특정 컬럼이 존재하는지 확인
 */
function columnExists(mysqli $conn, string $table, string $column): bool {
    $sql = "
        SELECT COUNT(*) AS cnt
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return ($row && (int)$row['cnt'] > 0);
}

/**
 * (요구사항) 첫 거래인지 확인 (기존 유지)
 * - INSERT 직전 COUNT=0 이면 "첫 거래"
 */
function isFirstTrade(mysqli $conn, int $user_id): bool {
    $sql = "SELECT COUNT(*) AS cnt FROM user_transactions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    $cnt = $row ? (int)$row['cnt'] : 0;
    return ($cnt === 0);
}

// ==============================
// POST 저장 처리 (기존 그대로 유지)
// ==============================
// ==============================
// POST 저장 처리
// - ✅ 자동 체크/초기화 없음 (합의사항)
// - ✅ 신규 입금은 항상 새 레코드 INSERT만 수행
// - ✅ Reject 모드: 입금액만 UPDATE
// ==============================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['xm_value'])) {

    // ✅ Reject 모드: 입금액만 UPDATE
    if (isset($_POST['reject_update_mode']) && $_POST['reject_update_mode'] === '1') {
        $tx_id_to_update = (int)($_POST['tx_id'] ?? 0);
        $xm_value     = (float)($_POST['xm_value'] ?? 0);
        $ultima_value = (float)($_POST['ultima_value'] ?? 0);

        if ($tx_id_to_update <= 0 || ($xm_value <= 0 && $ultima_value <= 0)) {
            die(t('err.invalid_reject_update', 'Invalid reject update parameters'));
        }

        // 보안: 해당 거래가 현재 사용자 소유이며 Reject 상태인지 재확인
        $sql_verify = "
            SELECT id FROM user_transactions
            WHERE id = ?
              AND user_id = ?
              AND deposit_chk = 1
              AND reject_by IS NOT NULL
              AND COALESCE(external_done_chk, 0) = 0
              AND settle_chk != 1
              AND dividend_chk != 1
            LIMIT 1
        ";
        $stmtV = $conn->prepare($sql_verify);
        if (!$stmtV) die(t('msg.db_error', 'Database error'));
        $stmtV->bind_param("ii", $tx_id_to_update, $user_id);
        $stmtV->execute();
        $resV = $stmtV->get_result();
        if (!$resV || $resV->num_rows === 0) {
            $stmtV->close();
            die(t('err.invalid_reject_transaction', 'Invalid reject transaction'));
        }
        $stmtV->close();

        // UPDATE: 입금액만 수정
        $sql_update = "
            UPDATE user_transactions
            SET xm_value = ?,
                ultima_value = ?
            WHERE id = ? AND user_id = ?
        ";
        $stmtU = $conn->prepare($sql_update);
        if (!$stmtU) die(t('msg.update_prepare_fail', 'Update prepare failed'));
        $stmtU->bind_param("ddii", $xm_value, $ultima_value, $tx_id_to_update, $user_id);
        if (!$stmtU->execute()) {
            $stmtU->close();
            die(t('msg.update_fail', 'Update failed'));
        }
        $stmtU->close();

        // korea_progressing도 deposit_status 업데이트
        $deposit_total = $xm_value + $ultima_value;
        $profit_loss = -1 * $deposit_total;
        $sql_kp_update = "
            UPDATE korea_progressing
            SET deposit_status = ?,
                profit_loss = ?
            WHERE user_id = ?
              AND tx_id = ?
        ";
        $stmtKP = $conn->prepare($sql_kp_update);
        if ($stmtKP) {
            $stmtKP->bind_param("ddii", $deposit_total, $profit_loss, $user_id, $tx_id_to_update);
            $stmtKP->execute();
            $stmtKP->close();
        }

        $conn->close();
        $redirect = "investor_deposit.php";
        if ($is_privileged) {
            $redirect .= "?user_id=" . urlencode((string)$user_id);
        }
        header("Location: " . $redirect);
        exit;
    }

    // 기존 INSERT 로직 (신규 입금)
    $selected_date = $_POST['tx_date'] ?? $today;
    $xm_value     = (float)($_POST['xm_value'] ?? 0);
    $ultima_value = (float)($_POST['ultima_value'] ?? 0);

    if ($xm_value <= 0 && $ultima_value <= 0) {
        die(t('err.deposit_amount_required'));
    }

    $code_value  = uniqid("CODE_");
    $_SESSION['code_value'] = $code_value;
    $deposit_chk = 1;

    $sql_insert = "
        INSERT INTO user_transactions
        (user_id, tx_date, xm_value, ultima_value, pair, code_value, deposit_chk, created_at)
        VALUES (?, ?, ?, ?, 'XM/Ultima', ?, ?, NOW())
    ";

    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) {
        die(t('msg.insert_prepare_fail') . " " . $conn->error);
    }

    $stmt->bind_param("isddsi", $user_id, $selected_date, $xm_value, $ultima_value, $code_value, $deposit_chk);
    if (!$stmt->execute()) {
        $stmt->close();
        die(t('msg.insert_fail') . " " . $conn->error);
    }
    $stmt->close();

    // ✅ 방금 INSERT된 user_transactions.id(tx_id) 확보 (커넥션 단위라 동시성 영향 없음)
    $tx_id = (int)$conn->insert_id;
    if ($tx_id <= 0) {
        // 보험: code_value로 재조회
        $qTid = $conn->prepare("SELECT id FROM user_transactions WHERE code_value = ? LIMIT 1");
        if ($qTid) {
            $qTid->bind_param("s", $code_value);
            $qTid->execute();
            $rTid = $qTid->get_result();
            if ($rTid && ($rowTid = $rTid->fetch_assoc())) {
                $tx_id = (int)$rowTid['id'];
            }
            $qTid->close();
        }
    }
    if ($tx_id <= 0) {
        die(t('msg.txid_create_fail'));
    }

    // ✅ (추가 요구사항) korea_progressing에도 반드시 등록
    // - UNIQUE: (user_id, tx_date, pair)
    // - 예시 기준: profit_loss = -deposit_status, withdrawal_status = 0
    $kp_pair_parts = [];
    if ($xm_value > 0)     $kp_pair_parts[] = 'xm';
    if ($ultima_value > 0) $kp_pair_parts[] = 'ultima';
    $kp_pair = implode(',', $kp_pair_parts); // 예: 'xm,ultima'
    $deposit_total = (float)$xm_value + (float)$ultima_value;
    $profit_loss = -1 * $deposit_total;
    $withdrawal_status = 0.00;

    // 안전: pair가 비어있으면(이론상 없음) 기본값
    if ($kp_pair === '') $kp_pair = 'xm,ultima';

    // tx_id 컬럼이 있으면 tx_id까지 같이 저장/업데이트 (없으면 기존 방식 유지)
    if (columnExists($conn, 'korea_progressing', 'tx_id')) {
        $sql_kp = "
            INSERT INTO korea_progressing
                (tx_id, user_id, tx_date, pair, deposit_status, withdrawal_status, profit_loss, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                tx_id = VALUES(tx_id),
                deposit_status = VALUES(deposit_status),
                withdrawal_status = VALUES(withdrawal_status),
                profit_loss = VALUES(profit_loss)
        ";
        $stmt_kp = $conn->prepare($sql_kp);
        if ($stmt_kp) {
            $stmt_kp->bind_param('iissddd', $tx_id, $user_id, $selected_date, $kp_pair, $deposit_total, $withdrawal_status, $profit_loss);
            // korea_progressing 실패는 메인 트랜잭션을 깨지 않도록 처리(입금 저장은 이미 완료)
            $stmt_kp->execute();
            $stmt_kp->close();
        }
    } else {
        // 레거시(컬럼 없음) 호환
        $sql_kp = "
            INSERT INTO korea_progressing
                (user_id, tx_date, pair, deposit_status, withdrawal_status, profit_loss, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                deposit_status = VALUES(deposit_status),
                withdrawal_status = VALUES(withdrawal_status),
                profit_loss = VALUES(profit_loss)
        ";
        $stmt_kp = $conn->prepare($sql_kp);
        if ($stmt_kp) {
            $stmt_kp->bind_param('issddd', $user_id, $selected_date, $kp_pair, $deposit_total, $withdrawal_status, $profit_loss);
            $stmt_kp->execute();
            $stmt_kp->close();
        }
    }

    // ✅ 저장 후 이동: 다음 단계(출금)로
    $conn->close();
    $redirect = "investor_withdrawal.php";
    if ($is_privileged) {
        $redirect .= "?user_id=" . urlencode((string)$user_id);
    }
    header("Location: " . $redirect);
    exit;
}

// 여기까지 오면 GET 화면 렌더

// ==============================
// ✅ Reject 거래 확인 (입금액만 수정 모드)
// ==============================
$reject_mode = false;
$reject_tx = null;

$sql_reject = "
    SELECT id, tx_date, xm_value, ultima_value, reject_reason, settle_chk, dividend_chk
    FROM user_transactions
    WHERE user_id = ?
      AND deposit_chk = 1
      AND reject_by IS NOT NULL
      AND reject_reason IS NOT NULL
      AND COALESCE(external_done_chk, 0) = 0
      AND settle_chk != 1
      AND dividend_chk != 1
    ORDER BY id DESC
    LIMIT 1
";
$stmtR = $conn->prepare($sql_reject);
if ($stmtR) {
    $stmtR->bind_param("i", $user_id);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    $rowR = $resR ? $resR->fetch_assoc() : null;
    $stmtR->close();
    
    if ($rowR) {
        $reject_mode = true;
        $reject_tx = $rowR;
    }
}

// ==============================
// ✅ 입금 불가 상태 판단 + 표시용 거래 정보 (레이아웃 유지)
// - 기준(핵심): "입금(deposit_chk=1)" 된 거래 중에서
//              출금/정산/배당이 하나라도 미완료(0)인 건이 존재하면
//              새 입금 불가
// - NOTE: external_done_chk 같은 부가 플래그에 의존하면 '입금은 등록됐는데
//         입금창이 다시 열리는' 롤백 증상이 발생할 수 있어, 기준에서 제외함.
// ==============================
$deposit_blocked = false;
$blocked_tx = null;            // ['id','tx_date','xm_value','ultima_value','withdrawal_chk','settle_chk','dividend_chk']
$blocked_reasons = [];
$blocked_action_url = "investor_withdrawal.php";

do {
    $sql = "
        SELECT id, tx_date, xm_value, ultima_value,
               deposit_chk, withdrawal_chk, settle_chk, dividend_chk
        FROM user_transactions
        WHERE user_id = ?
          AND deposit_chk = 1
          AND (withdrawal_chk = 0 OR settle_chk = 0 OR dividend_chk = 0)
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmtB = $conn->prepare($sql);
    if (!$stmtB) break;
    $stmtB->bind_param("i", $user_id);
    $stmtB->execute();
    $resB = $stmtB->get_result();
    $rowB = $resB ? $resB->fetch_assoc() : null;
    $stmtB->close();
    if (!$rowB) break;

    $w = (int)($rowB['withdrawal_chk'] ?? 0);
    $s = (int)($rowB['settle_chk'] ?? 0);
    $d = (int)($rowB['dividend_chk'] ?? 0);

    $deposit_blocked = true;
    $blocked_tx = $rowB;

    // 미완료 사유는 모두 표시
    if ($w === 0) $blocked_reasons[] = "deposit.blocked.reason.withdrawal";
    if ($s === 0) $blocked_reasons[] = "deposit.blocked.reason.settle";
    if ($d === 0) $blocked_reasons[] = "deposit.blocked.reason.dividend";

    // 이동 우선순위: 출금 → 정산 → 배당
    if ($w === 0) {
        $blocked_action_url = "investor_withdrawal.php";
    } elseif ($s === 0) {
        // 프로젝트 내 파일명이 다를 수 있어서, 존재하는 파일을 우선 사용
        if (file_exists(__DIR__ . '/investor_settle.php')) {
            $blocked_action_url = "investor_settle.php";
        } elseif (file_exists(__DIR__ . '/investor_settlement.php')) {
            $blocked_action_url = "investor_settlement.php";
        } else {
            $blocked_action_url = "investor_withdrawal.php";
        }
    } elseif ($d === 0) {
        if (file_exists(__DIR__ . '/investor_dividend.php')) {
            $blocked_action_url = "investor_dividend.php";
        } else {
            $blocked_action_url = "investor_withdrawal.php";
        }
    }
} while(false);


$conn->close();

// 레이아웃 적용(기존 그대로)
$page_title   = t('page.investor_deposit');
$content_file = __DIR__ . "/investor_transaction_content.php";
include "layout.php";