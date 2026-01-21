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


// ✅ 로그인 안 된 경우
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ 투자자(investor)가 아니면 강제 로그아웃 + 로그인페이지 이동
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'investor') {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"] ?? '/',
            $params["domain"] ?? '',
            !empty($params["secure"]),
            !empty($params["httponly"])
        );
    }

    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once "db_connect.php";
if($conn->connect_error) die(t('msg.db_connect_fail') . " " . $conn->connect_error);

$user_id = (int)$_SESSION['user_id'];
$base_tx_id = (int)($_SESSION['base_tx_id'] ?? 0);

/**
 * =====================================================
 * 1. 출금 대상 목록 조회 (본인 것만)
 *    - 입금완료
 *    - 출금미완료
 *    - ✅ 최신 레코드도 포함 (입금 차단 정책과 연동)
 * =====================================================
 */
$sql = "
SELECT id, tx_date, created_at,
       COALESCE(xm_value,0) AS xm_value,
       COALESCE(ultima_value,0) AS ultima_value
FROM user_transactions
WHERE user_id=?
  AND COALESCE(deposit_chk,0)=1
  -- ✅ GM OK 승인(approved) 된 거래만 출금 가능
  AND EXISTS (
      SELECT 1
      FROM korea_ready_trading r
      WHERE r.user_id = user_transactions.user_id
        AND r.tx_id   = user_transactions.id
        AND r.status  = 'approved'
  )
  AND COALESCE(external_done_chk,0)=1
  AND COALESCE(withdrawal_chk,0)=0
      AND (withdrawal_status IS NULL OR withdrawal_status='' OR withdrawal_status='0')
  AND (withdrawal_status IS NULL OR withdrawal_status='' OR withdrawal_status='0')
ORDER BY id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$all_rows = [];
while ($row = $res->fetch_assoc()) {
    $all_rows[] = $row;
}
$stmt->close();

$pending_codes = $all_rows; // ✅ 최신 포함: 출금 미완료면 모두 처리 대상
/**
 * =====================================================
 * 2. 출금 저장 처리
 * =====================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'withdraw') {

    $xm_total     = max(0, (float)str_replace(',', '', $_POST['xm_total'] ?? 0));
    $ultima_total = max(0, (float)str_replace(',', '', $_POST['ultima_total'] ?? 0));
    $id           = (int)($_POST['id'] ?? 0);

    if ($id > 0) {

        /* 안전검증: 이 ID가 실제 허용된 출금 대상인지 */
        $allowed = false;
        foreach ($pending_codes as $p) {
            if ((int)$p['id'] === $id) {
                $allowed = true;
                break;
            }
        }

        if ($allowed) {

            /* 입금 금액 조회 */
            $q = "SELECT xm_value, ultima_value FROM user_transactions WHERE id=? AND user_id=? LIMIT 1";
            $s = $conn->prepare($q);
            $s->bind_param("ii", $id, $user_id);
            $s->execute();
            $in = $s->get_result()->fetch_assoc();
            $s->close();

            $deposit_total  = (float)$in['xm_value'] + (float)$in['ultima_value'];
            $withdraw_total = $xm_total + $ultima_total;

            $profit   = $withdraw_total - $deposit_total;
            $dividend = $profit > 0 ? $profit : 0;

            $total_out = $withdraw_total > 0 ? $withdraw_total : 1;
            $xm_dividend     = $dividend * ($xm_total / $total_out);
            $ultima_dividend = $dividend * ($ultima_total / $total_out);

            /* 출금 저장 - withdrawal_chk=0 조건 추가 (동시성/중복 방지) */
$u = "
UPDATE user_transactions
SET xm_total=?, ultima_total=?,
    dividend_amount=?, xm_dividend=?, ultima_dividend=?,
    withdrawal_chk=1
WHERE id=? AND user_id=? AND withdrawal_chk=0
LIMIT 1
";
$us = $conn->prepare($u);
$us->bind_param(
    "dddddii",
    $xm_total, $ultima_total,
    $dividend, $xm_dividend, $ultima_dividend,
    $id, $user_id
);
$us->execute();
$affected = $us->affected_rows;
$us->close();
// affected_rows=1: 정상처리, 0: 이미 처리됨(중복 방지)
        }
    }

    /**
     * =====================================================
     * 3. 출금 후 남은 대상 재확인
     * =====================================================
     */
    $check = "
    SELECT id
    FROM user_transactions
    WHERE user_id=?
      AND COALESCE(deposit_chk,0)=1
      AND EXISTS (
          SELECT 1
          FROM korea_ready_trading r
          WHERE r.user_id = user_transactions.user_id
            AND r.tx_id   = user_transactions.id
            AND r.status  = 'approved'
      )
      AND COALESCE(external_done_chk,0)=1
      AND COALESCE(withdrawal_chk,0)=0
      AND (withdrawal_status IS NULL OR withdrawal_status='' OR withdrawal_status='0')
    ORDER BY id DESC
    ";
    $cs = $conn->prepare($check);
    $cs->bind_param("i", $user_id);
    $cs->execute();
    $cr = $cs->get_result();

    $remain = [];
    while ($r = $cr->fetch_assoc()) {
        $remain[] = $r;
    }
    $cs->close();

    // ✅ 최신 포함: 남은 출금 미완료가 있으면 계속 출금 페이지 유지
$conn->close();

    /* ✅ 남은 출금 대상 없으면 Profit Share 이동 */
    if (count($remain) === 0) {
        header("Location: investor_profit_share.php");
    } else {
        header("Location: investor_withdrawal.php");
    }
    exit;
}

/* =====================================================
 * 4. 화면 출력
 * ===================================================== */
$page_title   = t('page.investor_withdrawal');
$content_file = __DIR__ . "/investor_withdrawal_content.php";
$menu_type    = "investor";
include __DIR__ . "/layout.php";