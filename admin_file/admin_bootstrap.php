<?php
// admin_bootstrap.php
// 공통: 세션/권한/DB/보안(CSRF, 자동 로그아웃)

if (session_status() === PHP_SESSION_NONE) {
    // 세션 쿠키 보안 옵션(가능한 범위)
    if (!headers_sent()) {
        ini_set('session.cookie_httponly', '1');
        // HTTPS 환경이면 1 권장(로컬/HTTP 환경에서는 접속 끊길 수 있어 자동 판별)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }
        // PHP 7.3+에서 지원되는 경우에만
        ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
}

// ====== 접근 제어: superadmin only ======
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: edit_Thek_pm.php");
    exit;
}
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    http_response_code(403);
    echo "<h2 style='font-family:system-ui'>접근 권한이 없습니다 (superadmin 전용)</h2>";
    echo "<p style='font-family:system-ui'>로그인 계정을 확인하세요.</p>";
    echo "<p><a href='edit_Thek_pm.php' style='font-family:system-ui'>로그인 페이지로</a></p>";
    exit;
}

// ====== 자동 로그아웃(무활동 30분) ======
$timeout_seconds = 30 * 60;
$now = time();
if (isset($_SESSION['last_activity']) && is_numeric($_SESSION['last_activity'])) {
    if (($now - (int)$_SESSION['last_activity']) > $timeout_seconds) {
        // 세션 만료
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header("Location: edit_Thek_pm.php?expired=1");
        exit;
    }
}
$_SESSION['last_activity'] = $now;

// ====== CSRF ======
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token(): string {
    return (string)($_SESSION['csrf_token'] ?? '');
}
function csrf_input(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="'.$t.'">';
}
function require_csrf(): void {
    $posted = $_POST['csrf_token'] ?? '';
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!is_string($posted) || !is_string($sess) || $posted === '' || !hash_equals($sess, $posted)) {
        http_response_code(403);
        echo "<h2 style='font-family:system-ui'>요청이 거부되었습니다 (CSRF)</h2>";
        echo "<p style='font-family:system-ui'>페이지를 새로고침 후 다시 시도하세요.</p>";
        exit;
    }
}
// 모든 POST는 CSRF 필수(특정 페이지에서 예외 필요 시 define('CSRF_EXEMPT', true);)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !defined('CSRF_EXEMPT')) {
    require_csrf();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ====== DB 연결 ======
$servername = "localhost";
$username   = "thek_db_admin";
$password   = "thek_pw_admin!";
$dbname     = "thek_next_db";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo "<h2 style='font-family:system-ui'>DB 연결 실패</h2>";
    echo "<pre>".htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')."</pre>";
    exit;
}

// ====== Audit Log (DB에 audit_logs 테이블이 없으면 자동 무시) ======
function audit_log(mysqli $conn, string $action, ?string $target_table = null, $target_id = null, $before = null, $after = null, array $extra = []): void {
    // audit_logs 테이블이 아직 없을 수 있으니, 실패해도 페이지 동작을 막지 않는다.
    try {
        $admin_email = (string)($_SESSION['admin_email'] ?? '');
        $admin_role  = (string)($_SESSION['admin_role'] ?? '');
        $admin_ip    = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent  = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

        $before_json = $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $after_json  = $after  === null ? null : json_encode($after,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $extra_json  = empty($extra) ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $conn->prepare(
            "INSERT INTO audit_logs (admin_email, admin_role, action, target_table, target_id, before_json, after_json, extra_json, ip, user_agent)\n".
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $target_id_str = ($target_id === null) ? null : (string)$target_id;
        $stmt->bind_param(
            "ssssssssss",
            $admin_email,
            $admin_role,
            $action,
            $target_table,
            $target_id_str,
            $before_json,
            $after_json,
            $extra_json,
            $admin_ip,
            $user_agent
        );
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        // 무시
    }
}
?>