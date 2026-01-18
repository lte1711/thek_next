<?php
/**
 * 보안 유틸리티 함수
 */

/**
 * 안전한 출력 (XSS 방지)
 */
function safe_output($text, $encoding = 'UTF-8') {
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_HTML5, $encoding);
}

/**
 * 짧은 버전
 */
function h($text) {
    return safe_output($text);
}

/**
 * 안전한 정수 변환
 */
function safe_int($value, $default = 0) {
    $int = filter_var($value, FILTER_VALIDATE_INT);
    return $int !== false ? $int : $default;
}

/**
 * 안전한 문자열 변환
 */
function safe_string($value, $default = '') {
    if ($value === null) {
        return $default;
    }
    return trim((string)$value);
}

/**
 * 안전한 이메일 검증
 */
function safe_email($value) {
    return filter_var($value, FILTER_VALIDATE_EMAIL);
}

/**
 * SQL Injection 방지를 위한 파라미터 정제
 * (주의: Prepared Statement 사용이 더 안전함)
 */
function sanitize_sql_string($conn, $value) {
    return $conn->real_escape_string(trim($value));
}

/**
 * 안전한 리다이렉트
 * (오픈 리다이렉트 취약점 방지)
 */
function safe_redirect($url, $allowed_domains = []) {
    // 상대 경로는 허용
    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        header("Location: $url");
        exit;
    }
    
    // 절대 URL인 경우 도메인 검증
    $parsed = parse_url($url);
    if (isset($parsed['host'])) {
        $host = $parsed['host'];
        
        // 허용된 도메인 목록 확인
        if (!empty($allowed_domains) && !in_array($host, $allowed_domains, true)) {
            // 허용되지 않은 도메인이면 홈으로
            header("Location: /");
            exit;
        }
        
        // 현재 도메인과 같은지 확인
        $current_host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== $current_host) {
            header("Location: /");
            exit;
        }
    }
    
    header("Location: $url");
    exit;
}

/**
 * 비밀번호 강도 검사
 */
function check_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = '비밀번호는 최소 8자 이상이어야 합니다.';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = '비밀번호에 대문자가 포함되어야 합니다.';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = '비밀번호에 소문자가 포함되어야 합니다.';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = '비밀번호에 숫자가 포함되어야 합니다.';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = '비밀번호에 특수문자가 포함되어야 합니다.';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * 안전한 파일명 생성
 */
function safe_filename($filename) {
    // 위험한 문자 제거
    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);
    
    // 연속된 점 제거 (디렉토리 탐색 방지)
    $filename = preg_replace('/\.+/', '.', $filename);
    
    // 파일명이 점으로 시작하지 않도록
    $filename = ltrim($filename, '.');
    
    return $filename;
}

/**
 * IP 주소 가져오기 (프록시 고려)
 */
function get_client_ip() {
    $ip_keys = [
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * 로그인 시도 제한 (무차별 대입 공격 방지)
 */
function check_login_attempts($identifier) {
    if (!isset($_SESSION['_login_attempts'])) {
        $_SESSION['_login_attempts'] = [];
    }
    
    $max_attempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
    $timeout = defined('LOGIN_ATTEMPT_TIMEOUT') ? LOGIN_ATTEMPT_TIMEOUT : 900;
    
    $now = time();
    
    // 오래된 시도 기록 제거
    if (isset($_SESSION['_login_attempts'][$identifier])) {
        $attempts = $_SESSION['_login_attempts'][$identifier];
        $attempts = array_filter($attempts, function($timestamp) use ($now, $timeout) {
            return ($now - $timestamp) < $timeout;
        });
        $_SESSION['_login_attempts'][$identifier] = $attempts;
    }
    
    // 시도 횟수 확인
    $attempt_count = isset($_SESSION['_login_attempts'][$identifier]) 
        ? count($_SESSION['_login_attempts'][$identifier]) 
        : 0;
    
    if ($attempt_count >= $max_attempts) {
        return [
            'allowed' => false,
            'remaining' => 0,
            'wait_time' => $timeout - ($now - min($_SESSION['_login_attempts'][$identifier]))
        ];
    }
    
    return [
        'allowed' => true,
        'remaining' => $max_attempts - $attempt_count,
        'wait_time' => 0
    ];
}

/**
 * 로그인 시도 기록
 */
function record_login_attempt($identifier) {
    if (!isset($_SESSION['_login_attempts'])) {
        $_SESSION['_login_attempts'] = [];
    }
    
    if (!isset($_SESSION['_login_attempts'][$identifier])) {
        $_SESSION['_login_attempts'][$identifier] = [];
    }
    
    $_SESSION['_login_attempts'][$identifier][] = time();
}

/**
 * 로그인 시도 초기화
 */
function reset_login_attempts($identifier) {
    if (isset($_SESSION['_login_attempts'][$identifier])) {
        unset($_SESSION['_login_attempts'][$identifier]);
    }
}

/**
 * JSON 안전 출력
 */
function safe_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * 에러 로깅 (안전)
 */
function log_error($message, $context = []) {
    if (!defined('ENABLE_ERROR_LOG') || !ENABLE_ERROR_LOG) {
        return;
    }
    
    $log_message = date('[Y-m-d H:i:s] ') . $message;
    
    if (!empty($context)) {
        $log_message .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $log_message .= ' | IP: ' . get_client_ip();
    $log_message .= ' | URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A');
    
    error_log($log_message . PHP_EOL);
}
