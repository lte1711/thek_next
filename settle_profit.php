<?php
session_start();
header('Content-Type: application/json');

// 로그인 여부 확인
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(["success"=>false,"message"=>"로그인이 필요합니다."]);
    exit;
}

// DB 연결
require_once "db_connect.php";
if ($conn->connect_error) {
    echo json_encode(["success"=>false,"message"=>"DB 연결 실패"]);
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
    echo json_encode(["success"=>false,"message"=>"이미 정산된 내역이 있습니다."]);
    $conn->close();
    exit;
}

// === 수익 계산 ===
// (출금합계 - 입금합계 + 배당금)
$sql = "SELECT 
            COALESCE(SUM(xm_total+ultima_total),0) 
            - COALESCE(SUM(xm_value+ultima_value),0) 
            + COALESCE(SUM(dividend_amount),0) AS profit
        FROM user_transactions 
        WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i",$user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profit = floatval($result['profit']);
if ($profit <= 0) {
    echo json_encode(["success"=>false,"message"=>"수익이 없습니다."]);
    $conn->close();
    exit;
}

// === 체크필드 업데이트 (업그레이드 형식) ===
$update_sql = "UPDATE user_transactions 
               SET settle_chk=1 
               WHERE user_id=?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i",$user_id);
$update_stmt->execute();
$update_stmt->close();

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
echo json_encode(["success"=>true,"message"=>"정산 완료"]);

// === code_value 유지 및 제거 ===
$code_value = $_SESSION['code_value'] ?? null;
// 모든 분배 및 업데이트 완료 후
unset($_SESSION['code_value']); // 정산 완료 시 세션에서 제거
?>