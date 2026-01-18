<?php
/**
 * settle_confirm.php - Partner_accounts_v2 전용
 * ------------------------------------------------------
 * ✅ GM 정산 처리
 * - 데이터: dividend 테이블 (gm1/gm2/gm3)
 * - 정산 상태: admin_sales_daily 테이블 (settled 0/1)
 */

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

/**
 * ✅ 메시지를 alert으로 보여주고 리다이렉트하는 함수
 */
function show_message_and_redirect($message, $redirect_url = '') {
    // redirect URL이 없으면 기본값 사용
    if (empty($redirect_url)) {
        $redirect_url = 'Partner_accounts_v2.php';
    }
    
    // HTML 출력
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>알림</title>
</head>
<body>
<script>
alert(' . json_encode($message) . ');
window.location.href = ' . json_encode($redirect_url) . ';
</script>
</body>
</html>';
    exit;
}

// POST 파라미터
$action      = $_POST['action'] ?? '';
$settle_date = $_POST['settle_date'] ?? '';
$level       = $_POST['level'] ?? 'admin';
$redirect    = $_POST['redirect'] ?? 'Partner_accounts_v2.php';

// ✅ 감시 로그 수집 정보
$actor_user_id = (int)($_SESSION['user_id'] ?? 0);
$actor_role = $_SESSION['role'] ?? 'unknown';
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
$request_id = uniqid('REQ_', true);

include 'db_connect.php';

// ✅ GM만 허용
$is_gm = false;
if (isset($_SESSION['role'])) {
    $is_gm = ($_SESSION['role'] === 'gm');
} else {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $is_gm = (isset($r['role']) && $r['role'] === 'gm');
}
if (!$is_gm) {
    show_message_and_redirect(t('err.gm_only_settle', 'GM 권한만 정산할 수 있습니다.'), $redirect);
}

// ✅ 날짜 유효성 검사
$dt = DateTime::createFromFormat('Y-m-d', $settle_date);
if (!$dt || $dt->format('Y-m-d') !== $settle_date) {
    show_message_and_redirect(t('err.invalid_date_format', '잘못된 날짜 형식입니다. (YYYY-MM-DD)'), $redirect);
}

// ✅ action 검사
if ($action !== 'confirm_sent') {
    show_message_and_redirect(t('err.invalid_request', '잘못된 요청입니다.'), $redirect);
}

try {
    // ✅ 이미 정산 완료인지 확인 (admin_sales_daily)
    $check_sql = "SELECT settled FROM admin_sales_daily WHERE sales_date = ? LIMIT 1";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $settle_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $settled_row = $result->fetch_assoc();
    $stmt->close();
    
    if ($settled_row && $settled_row['settled'] == 1) {
        show_message_and_redirect(
            t('settlement.already_completed','해당 날짜는 이미 정산이 완료되었습니다.') . ' : ' . $settle_date,
            $redirect
        );
    }
    
    // ✅ dividend 테이블에서 해당 날짜의 GM 데이터 확인
    $data_check_sql = "
        SELECT COUNT(*) as cnt
        FROM dividend
        WHERE DATE(tx_date) = ?
          AND (gm1_amount > 0 OR gm2_amount > 0 OR gm3_amount > 0)
    ";
    $stmt = $conn->prepare($data_check_sql);
    $stmt->bind_param("s", $settle_date);
    $stmt->execute();
    $data_result = $stmt->get_result();
    $data_row = $data_result->fetch_assoc();
    $stmt->close();
    
    if (!$data_row || $data_row['cnt'] == 0) {
        show_message_and_redirect(
            t('msg.no_data_for_date','해당 날짜에 정산할 데이터가 없습니다.') . ' : ' . $settle_date,
            $redirect
        );
    }
    
    // ✅ 트랜잭션 시작
    $conn->begin_transaction();
    
    // ✅ 로그용: dividend 테이블에서 이 날짜의 모든 분배액 합계 계산
    // 모든 금액은 USDT 기준 (dividend 테이블 값은 USDT 단위, 소수 허용 가능)
    $log_calc_sql = "
        SELECT 
            COALESCE(SUM(gm1_amount + gm2_amount + gm3_amount + admin_amount + mastr_amount + agent_amount + investor_amount + referral_amount), 0) AS profit_total,
            COALESCE(SUM(admin_amount + mastr_amount + agent_amount + investor_amount + referral_amount), 0) AS partner_sum
        FROM dividend
        WHERE DATE(tx_date) = ?
    ";
    $stmt = $conn->prepare($log_calc_sql);
    $stmt->bind_param("s", $settle_date);
    $stmt->execute();
    $log_calc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $profit_total = (float)($log_calc['profit_total'] ?? 0);
    $partner_sum = (float)($log_calc['partner_sum'] ?? 0);
    $gm_total = $profit_total - $partner_sum;  // USDT: 실제 의미 = GM 합계
    $company_residual = 0;                      // C안 정책: Residual = 0 (회사 귀속분 없음)
    
    // ✅ admin_sales_daily에 레코드가 있는지 확인 (JP 스키마: user_id별 다중 행)
    $exist_check = "SELECT id, settled FROM admin_sales_daily WHERE sales_date = ? AND user_id = ? LIMIT 1";
    $stmt = $conn->prepare($exist_check);
    $stmt->bind_param("si", $settle_date, $actor_user_id);
    $stmt->execute();
    $exist_result = $stmt->get_result();
    $exists = $exist_result->fetch_assoc();
    $stmt->close();
    
    $status_before = $exists ? (($exists['settled'] == 1) ? 'settled' : 'pending') : 'not_found';
    $status_after  = 'settled';
    
    if ($exists) {
        // ✅ JP 스키마: 정산 완료 처리
        $update_sql = "UPDATE admin_sales_daily
                       SET settled = 1,
                           settled_at = NOW(),
                           updated_at = NOW()
                       WHERE sales_date = ? AND user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $settle_date, $actor_user_id);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new Exception(t('err.settlement_update_failed', '정산 처리 반영 실패'));
        }
        $stmt->close();
    } else {
        // ✅ JP 스키마: 최소 컬럼으로 INSERT (user_id=실행자)
        $insert_sql = "INSERT INTO admin_sales_daily
                       (user_id, sales_amount, sales_percentage, sales_date, settled, settled_at, created_at, updated_at)
                       VALUES (?, 0.00, 0.00, ?, 1, NOW(), NOW(), NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("is", $actor_user_id, $settle_date);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new Exception(t('err.settlement_insert_failed', '정산 레코드 생성 실패'));
        }
        $stmt->close();
    }
    
    // ✅ 감시 로그 INSERT (admin_audit_log)
    // 타입 문자열: s(event_code) s(settle_date) d(profit_total) d(partner_sum) d(company_residual) s(status_before) s(status_after) i(actor_user_id) s(actor_role) s(ip) s(user_agent) s(request_id)
    $log_sql = "
        INSERT INTO admin_audit_log
        (event_code, settle_date, profit_total, partner_sum, company_residual, company_user_id,
         status_before, status_after, actor_user_id, actor_role, ip, user_agent, request_id, created_at)
        VALUES (?, ?, ?, ?, ?, 5, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";
    if ($stmt = $conn->prepare($log_sql)) {
        $event_code = 'SETTLE_CONFIRM';
        $stmt->bind_param("ssddssisssss", 
            $event_code, $settle_date, $profit_total, $partner_sum, $company_residual,
            $status_before, $status_after, $actor_user_id, $actor_role, $ip, $user_agent, $request_id
        );
        $stmt->execute();
        $stmt->close();
    }
    
    // ✅ 커밋
    $conn->commit();
    
    // ✅ 성공 메시지
    show_message_and_redirect(
        t('settlement.sent_confirmed','정산이 완료되었습니다.') . "\n" . 
        t('settlement.date', '정산 날짜: ') . $settle_date,
        $redirect
    );

} catch (Exception $e) {
    // ✅ 롤백
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    show_message_and_redirect(
        t('err.settlement_failed_prefix','정산 처리 중 오류가 발생했습니다: ') . $e->getMessage(),
        $redirect
    );
}
?>
