<?php
/**
 * 인증 미들웨어
 * 
 * 모든 보호된 페이지에서 사용
 * 세션 체크, 권한 체크, 보안 강화
 */

// 세션 설정 강화
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1'); // HTTPS 사용 시
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    
    session_start();
}

/**
 * 로그인 필수 체크
 */
function require_login() {
    // 세션 시작 확인
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 로그인 체크
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Ajax 요청인 경우
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => '로그인이 필요합니다.']);
            exit;
        }
        
        // 일반 요청인 경우
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /login.php");
        exit;
    }
    
    // 세션 하이재킹 방지
    if (!isset($_SESSION['user_agent'])) {
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // User-Agent 변경 감지
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        session_unset();
        session_destroy();
        header("Location: /login.php?error=session_invalid");
        exit;
    }
    
    // IP 변경 감지 (선택적 - 모바일 환경에서는 비활성화 권장)
    if (defined('CHECK_IP_CHANGE') && CHECK_IP_CHANGE) {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            session_unset();
            session_destroy();
            header("Location: /login.php?error=ip_changed");
            exit;
        }
    }
    
    // 세션 타임아웃 (30분)
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: /login.php?error=timeout");
        exit;
    }
    
    $_SESSION['last_activity'] = time();
    
    // 세션 재생성 (5분마다)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    if (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * 역할 기반 접근 제어
 * 
 * @param array|string $allowed_roles 허용된 역할 (배열 또는 단일 문자열)
 * @param mysqli $conn 데이터베이스 연결 (선택)
 */
function require_role($allowed_roles, $conn = null) {
    // 먼저 로그인 체크
    require_login();
    
    // 문자열을 배열로 변환
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    // 세션에 role이 있으면 사용
    $user_role = null;
    if (isset($_SESSION['role'])) {
        $user_role = $_SESSION['role'];
    } 
    // 없으면 DB에서 조회
    elseif ($conn !== null) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result) {
            $user_role = $result['role'];
            $_SESSION['role'] = $user_role; // 세션에 저장
        }
    }
    
    // 역할 체크
    if (!in_array($user_role, $allowed_roles, true)) {
        // Ajax 요청인 경우
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => '접근 권한이 없습니다.']);
            exit;
        }
        
        // 일반 요청인 경우
        http_response_code(403);
        echo "접근 권한이 없습니다.";
        exit;
    }
    
    return $user_role;
}

/**
 * CSRF 토큰 생성
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF 체크 미들웨어
 */
function require_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['error' => 'CSRF 토큰이 유효하지 않습니다.']);
            } else {
                echo "CSRF 토큰이 유효하지 않습니다.";
            }
            exit;
        }
    }
}

/**
 * 사용자 정보 가져오기 (캐싱)
 *
 * NOTE:
 * - PHP 내장 함수(get_current_user)와 이름이 충돌하여 Fatal error가 발생할 수 있어
 *   함수명을 auth_get_current_user()로 변경했습니다.
 */
function auth_get_current_user($conn = null) {
    static $user = null;
    
    if ($user !== null) {
        return $user;
    }
    
    require_login();
    
    if ($conn === null) {
        global $conn;
    }
    
    $stmt = $conn->prepare("
        SELECT u.*, ud.* 
        FROM users u
        LEFT JOIN user_details ud ON u.id = ud.user_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * 로그인 시도 제한 (Brute Force 방지)
 */
function check_login_attempts($username, $max_attempts = 5, $lockout_time = 900) {
    $cache_file = __DIR__ . '/../cache/login_attempts/' . md5($username) . '.lock';
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        
        // 잠금 시간 확인
        if ($data['locked_until'] > time()) {
            $remaining = $data['locked_until'] - time();
            return [
                'allowed' => false,
                'message' => "계정이 잠겼습니다. {$remaining}초 후 다시 시도하세요.",
                'remaining_time' => $remaining
            ];
        }
        
        // 잠금 시간이 지났으면 초기화
        if ($data['locked_until'] <= time()) {
            unlink($cache_file);
            return ['allowed' => true];
        }
        
        // 최대 시도 횟수 확인
        if ($data['attempts'] >= $max_attempts) {
            $data['locked_until'] = time() + $lockout_time;
            file_put_contents($cache_file, json_encode($data));
            
            return [
                'allowed' => false,
                'message' => "로그인 시도가 너무 많습니다. {$lockout_time}초 후 다시 시도하세요.",
                'remaining_time' => $lockout_time
            ];
        }
    }
    
    return ['allowed' => true];
}

/**
 * 로그인 실패 기록
 */
function record_login_failure($username) {
    $cache_dir = __DIR__ . '/../cache/login_attempts';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . '/' . md5($username) . '.lock';
    
    $data = ['attempts' => 1, 'locked_until' => 0];
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        $data['attempts']++;
    }
    
    file_put_contents($cache_file, json_encode($data));
}

/**
 * 로그인 성공 시 초기화
 */
function clear_login_attempts($username) {
    $cache_file = __DIR__ . '/../cache/login_attempts/' . md5($username) . '.lock';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}
?>
