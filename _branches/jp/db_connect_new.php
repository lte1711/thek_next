<?php
/**
 * 데이터베이스 연결 (개선 버전)
 */

// 설정 파일 로드
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
} else {
    // 설정 파일이 없으면 기본값 사용 (하위 호환성)
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_USER')) define('DB_USER', 'thek_db_admin');
    if (!defined('DB_PASS')) define('DB_PASS', 'thek_pw_admin!');
    if (!defined('DB_NAME')) define('DB_NAME', 'thek_next_db_branch_jp');
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
    if (!defined('TIMEZONE')) define('TIMEZONE', 'Asia/Seoul');
}

// 타임존 설정
date_default_timezone_set(TIMEZONE);

// 에러 보고 설정 (프로덕션 환경)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    if (defined('ERROR_LOG_PATH')) {
        ini_set('error_log', ERROR_LOG_PATH);
    }
}

// MySQLi 연결
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 연결 확인
if ($conn->connect_error) {
    // 에러 로깅
    if (defined('ENABLE_ERROR_LOG') && ENABLE_ERROR_LOG) {
        error_log("DB Connection failed: " . $conn->connect_error);
    }
    
    // 사용자에게는 간단한 메시지만 표시
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        die("데이터베이스 연결에 실패했습니다. 관리자에게 문의하세요.");
    }
}

// 문자셋 설정
$conn->set_charset(DB_CHARSET);

// 타임존 설정 (MySQL)
$conn->query("SET time_zone = '+09:00'"); // 한국 시간
