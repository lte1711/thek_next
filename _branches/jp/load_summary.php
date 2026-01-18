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

// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');
error_reporting(E_ALL);

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit(json_encode(["error" => t('error.login_required', 'Login required.')]));
}

$session_user_id = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';

// 기본: 내 것
$user_id = $session_user_id;

// investor가 아닌 경우에만 GET user_id 허용
$req_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$allowed_roles = ['superadmin','gm','admin','master','agent'];

if ($req_user_id > 0 && in_array($role, $allowed_roles, true)) {
    $user_id = $req_user_id;
}

// DB 연결
require_once "db_connect.php";
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    exit(json_encode(["error" => "DB 연결 실패: ".$conn->connect_error]));
}

// GET 파라미터
$tx_date = $_GET['date'] ?? null;
$code    = $_GET['code'] ?? null;

if (!$tx_date || !$code) {
    header("HTTP/1.1 400 Bad Request");
    exit(json_encode(["error" => "필수 파라미터 누락"]));
}

// ✅ 날짜 포맷 문제 해결: DATE(tx_date)로 비교
$sql = "SELECT 
            COALESCE(SUM(xm_value),0) AS xm_in,
            COALESCE(SUM(ultima_value),0) AS ultima_in,
            COALESCE(SUM(xm_total),0) AS xm_out,
            COALESCE(SUM(ultima_total),0) AS ultima_out,
            COALESCE(SUM(dividend_amount),0) AS dividend_sum,
            MAX(dividend_chk) AS dividend_chk
        FROM user_transactions
        WHERE user_id=? AND DATE(tx_date)=? AND code_value=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $tx_date, $code);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("HTTP/1.1 404 Not Found");
    exit(json_encode(["error" => "거래 내역 없음"]));
}

// ✅ Summary 계산
$deposit    = $row['xm_in'] + $row['ultima_in'];
$withdrawal = $row['xm_out'] + $row['ultima_out'];
$profit     = $withdrawal - $deposit;
$share75    = $profit * 0.75;
$share25    = $profit * 0.25;

// ✅ JSON 반환 (숫자 그대로 반환 → JS에서 포맷팅)
echo json_encode([
  "deposit"      => round($deposit, 2),
  "withdrawal"   => round($withdrawal, 2),
  "profit"       => round($profit, 2),
  "share75"      => round($share75, 2),
  "share25"      => round($share25, 2),
  "dividend_chk" => (int)$row['dividend_chk']   // ✅ 정산 여부도 반환
]);

$conn->close();