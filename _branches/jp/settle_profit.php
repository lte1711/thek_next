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

header('Content-Type: application/json');

// 로그인 여부 확인
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(["success"=>false,"message"=>t('err.login_required','Login required.')]);
    exit;
}

// DB 연결
require_once "db_connect.php";
if ($conn->connect_error) {
    echo json_encode(["success"=>false,"message"=>t('error.db_connect_failed','DB connect failed')]);
    exit;
}

// === 이미 정산 여부 확인 ===
$check_sql = "SELECT COUNT(*) AS cnt FROM transaction_distribution WHERE user_id=?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i",$user_id);
$check_stmt->execute();
$cnt = $check_stmt->get_result()->fetch_assoc()['cnt'];
$check_stmt->close();

if ($cnt > 0) {
    echo json_encode(["success"=>false,"message"=>t('settlement.already_completed','Already settled.')]);
    $conn->close();
    exit;
}

// === 수익 계산 및 정산 대상 거래 조회 ===
// (출금합계 - 입금합계 + 배당금)
$sql = "SELECT 
            id,
            COALESCE(xm_total,0) + COALESCE(ultima_total,0) AS total_out,
            COALESCE(xm_value,0) + COALESCE(ultima_value,0) AS total_in,
            COALESCE(dividend_amount,0) AS dividend
        FROM user_transactions 
        WHERE user_id=? AND settle_chk=0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($transactions)) {
    echo json_encode(["success"=>false,"message"=>t('msg.no_profit','No profit.')]);
    $conn->close();
    exit;
}

// 총 수익 계산
$profit = 0;
foreach ($transactions as $tx) {
    $profit += ($tx['total_out'] - $tx['total_in'] + $tx['dividend']);
}

if ($profit <= 0) {
    echo json_encode(["success"=>false,"message"=>t('msg.no_profit','No profit.')]);
    $conn->close();
    exit;
}

// === 체크필드 업데이트 (id별 독립 트랜잭션) ===
$settled_count = 0;
foreach ($transactions as $tx) {
    $tx_id = (int)$tx['id'];
    
    // 각 거래별 독립 트랜잭션 시작
    $conn->begin_transaction();
    
    try {
        // FOR UPDATE로 잠금 및 settle_chk=0 확인
        $lock_sql = "SELECT id, settle_chk FROM user_transactions WHERE id=? AND settle_chk=0 FOR UPDATE";
        $lock_stmt = $conn->prepare($lock_sql);
        $lock_stmt->bind_param("i", $tx_id);
        $lock_stmt->execute();
        $locked = $lock_stmt->get_result()->fetch_assoc();
        $lock_stmt->close();
        
        // settle_chk=0이 아니면 스킵 (케이스 B)
        if (!$locked) {
            $conn->rollback();
            continue;
        }
        
        // settle_chk=1로 업데이트 (WHERE settle_chk=0 강제)
        $update_sql = "UPDATE user_transactions 
                       SET settle_chk=1 
                       WHERE id=? AND settle_chk=0";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $tx_id);
        $update_stmt->execute();
        $affected = $update_stmt->affected_rows;
        $update_stmt->close();
        
        // 업데이트 실패 시 롤백 (케이스 C)
        if ($affected === 0) {
            $conn->rollback();
            continue;
        }
        
        // 성공 시 커밋
        $conn->commit();
        $settled_count++;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("settle_profit error for tx_id={$tx_id}: " . $e->getMessage());
        continue;
    }
}

// === 분배 처리 함수 ===
function insertDistribution($conn, $user_id, $role, $amount) {
    $sql = "INSERT INTO transaction_distribution (user_id, role, amount, created_at) 
            VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $user_id, $role, $amount);
    $stmt->execute();
    $stmt->close();
}

// === 후원인/추천인 추적 함수 ===
function getReferrer($conn, $uid) {
    $sql = "SELECT referrer_id FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['referrer_id'] ?? null;
}
function getSponsor($conn, $uid) {
    $sql = "SELECT sponsor_id FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['sponsor_id'] ?? null;
}

// === 계층별 분배 ===
$referrer_id = getReferrer($conn, $user_id);
$agent_id    = getSponsor($conn, $user_id);
$master_id   = $agent_id ? getSponsor($conn, $agent_id) : null;
$admin_id    = $master_id ? getSponsor($conn, $master_id) : null;

if ($referrer_id) insertDistribution($conn, $referrer_id, 'referrer', $profit * 0.05);
if ($agent_id)    insertDistribution($conn, $agent_id, 'agent', $profit * 0.04);
if ($master_id)   insertDistribution($conn, $master_id, 'master', $profit * 0.03);
if ($admin_id)    insertDistribution($conn, $admin_id, 'admin', $profit * 0.03);

// === GM 분배 ===
$gm_sql = "SELECT gm_id, gm_percent FROM gm_share";
$gm_result = $conn->query($gm_sql);
while ($gm = $gm_result->fetch_assoc()) {
    $gm_share = $profit * ($gm['gm_percent'] / 100);
    insertDistribution($conn, $gm['gm_id'], 'gm', $gm_share);
}

$conn->close();
echo json_encode(["success"=>true,"message"=>t('settlement.completed','Settlement completed.')]);

// === code_value 유지 및 제거 ===
$code_value = $_SESSION['code_value'] ?? null;
// 모든 분배 및 업데이트 완료 후
unset($_SESSION['code_value']); // 정산 완료 시 세션에서 제거
?>