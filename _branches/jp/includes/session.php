<?php
/**
 * 세션 관리 유틸리티
 */

// 설정 파일 로드
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

/**
 * 안전한 세션 시작
 */
function secure_session_start() {
    // 이미 세션이 시작되었는지 확인
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    
    // 세션 설정
    $session_name = defined('SESSION_NAME') ? SESSION_NAME : 'THEK_SESSION';
    $secure = defined('SECURE_COOKIE') ? SECURE_COOKIE : false;
    $httponly = defined('HTTPONLY_COOKIE') ? HTTPONLY_COOKIE : true;
    
    // 세션 쿠키 매개변수 설정
    session_name($session_name);
    
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Lax'
    ]);
    
    // 세션 시작
    session_start();
    
    // 세션 하이재킹 방지: IP와 User Agent 확인
    if (!isset($_SESSION['_initialized'])) {
        session_regenerate_id(true);
        $_SESSION['_initialized'] = true;
        $_SESSION['_user_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['_created_at'] = time();
    }
    
    // 세션 검증
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (isset($_SESSION['_user_ip']) && $_SESSION['_user_ip'] !== $current_ip) {
        // IP가 변경되면 세션 파괴 (보안)
        session_unset();
        session_destroy();
        session_start();
        return false;
    }
    
    // 세션 타임아웃 체크
    if (isset($_SESSION['_last_activity'])) {
        $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200;
        if (time() - $_SESSION['_last_activity'] > $lifetime) {
            // 세션 만료
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['_last_activity'] = time();
    
    return true;
}

/**
 * 로그인 확인
 */
function is_logged_in() {
    secure_session_start();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * 로그인 필요 체크
 */
function require_login($redirect_to = 'login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * 역할 확인
 */
function has_role($required_roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? '';
    
    if (is_array($required_roles)) {
        return in_array($user_role, $required_roles, true);
    }
    
    return $user_role === $required_roles;
}

/**
 * 역할 체크 및 리다이렉트
 */
function require_role($required_roles, $redirect_to = 'login.php') {
    if (!has_role($required_roles)) {
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * 현재 사용자 ID 가져오기
 */
function get_user_id() {
    secure_session_start();
    return $_SESSION['user_id'] ?? null;
}

/**
 * 현재 사용자 역할 가져오기
 */
function get_user_role() {
    secure_session_start();
    return $_SESSION['role'] ?? null;
}

/**
 * 안전한 로그아웃
 */
function secure_logout($redirect_to = 'login.php') {
    secure_session_start();
    
    // 세션 변수 모두 해제
    $_SESSION = [];
    
    // 세션 쿠키 삭제
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // 세션 파괴
    session_destroy();
    
    // 리다이렉트
    header("Location: $redirect_to");
    exit;
}

/**
 * CSRF 토큰 생성
 */
function generate_csrf_token() {
    secure_session_start();
    
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['_csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function verify_csrf_token($token) {
    secure_session_start();
    
    if (!isset($_SESSION['_csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['_csrf_token'], $token);
}
