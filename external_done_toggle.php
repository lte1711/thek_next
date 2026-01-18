<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'investor') { 
    // 투자자 전용: 강제 로그아웃
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'] ?? '/', $p['domain'] ?? '', !empty($p['secure']), !empty($p['httponly']));
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

require_once "db_connect.php";

$user_id = (int)$_SESSION['user_id'];
$tx_id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$month   = isset($_POST['month']) ? preg_replace('/[^0-9]/', '', $_POST['month']) : '';
$region  = isset($_POST['region']) ? preg_replace('/[^a-z_]/', '', strtolower($_POST['region'])) : 'korea';

if ($tx_id <= 0) {
    header("Location: profit_share.php?user_id=".$user_id."&month=".$month."&region=".$region);
    exit;
}

// 본인 거래만 업데이트 + 이미 완료면 유지
$sql = "UPDATE user_transactions
        SET external_done_chk=1, external_done_date=COALESCE(external_done_date, CURDATE())
        WHERE id=? AND user_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $tx_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: profit_share.php?user_id=".$user_id."&month=".$month."&region=".$region."&ext_done=1");
exit;
