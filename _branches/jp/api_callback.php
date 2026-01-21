<?php
// TICKET-004: external_done_chk 멱등성 콜백 구현
// Honey 전용, 정책/설계 LOCK

require_once 'db_connect.php';

// 입력값 검증 (예: POST: tx_id)
$tx_id = isset($_POST['tx_id']) ? (int)$_POST['tx_id'] : 0;
if ($tx_id <= 0) {
    http_response_code(400);
    echo json_encode(["success"=>false, "message"=>"Invalid tx_id"]);
    exit;
}

$conn->begin_transaction();
try {
    // 1. external_done_chk=0인 경우만 FOR UPDATE로 잠금
    $stmt = $conn->prepare("SELECT id, external_done_chk FROM user_transactions WHERE id=? AND external_done_chk=0 FOR UPDATE");
    $stmt->bind_param("i", $tx_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        // 이미 1이거나 없는 경우: 멱등성 보장, 재처리 없음
        $conn->rollback();
        echo json_encode(["success"=>true, "message"=>"Already processed or not found"]);
        exit;
    }

    // 2. external_done_chk=1로 전이
    $upd = $conn->prepare("UPDATE user_transactions SET external_done_chk=1 WHERE id=? AND external_done_chk=0");
    $upd->bind_param("i", $tx_id);
    if (!$upd->execute()) {
        $conn->rollback();
        echo json_encode(["success"=>false, "message"=>"Update failed"]);
        exit;
    }
    $upd->close();

    $conn->commit();
    echo json_encode(["success"=>true, "message"=>"Processed"]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["success"=>false, "message"=>"Exception: ".$e->getMessage()]);
    exit;
} finally {
    if (isset($conn)) $conn->close();
}
