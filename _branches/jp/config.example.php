<?php
/**
 * TheK-NEXT 설정 파일 예제
 * 
 * 이 파일을 config.php로 복사하고 실제 값으로 변경하세요.
 * config.php는 .gitignore에 추가하여 저장소에 커밋되지 않도록 하세요.
 */

// 데이터베이스 설정
define('DB_HOST', 'localhost');
define('DB_USER', 'thek_db_admin');
define('DB_PASS', 'thek_pw_admin!');
define('DB_NAME', 'thek_next_db_branch_jp');
define('DB_CHARSET', 'utf8mb4');

// 세션 설정
define('SESSION_LIFETIME', 3600 * 2); // 2시간 (초 단위)
define('SESSION_NAME', 'THEK_SESSION');

// 보안 설정
define('SECURE_COOKIE', false); // HTTPS 사용 시 true로 변경
define('HTTPONLY_COOKIE', true); // JavaScript에서 쿠키 접근 방지

// 언어 설정
define('DEFAULT_LANGUAGE', 'ko');
define('SUPPORTED_LANGUAGES', ['ko', 'ja', 'en']);

// 환경 설정
define('ENVIRONMENT', 'production'); // development, staging, production
define('DEBUG_MODE', false); // 개발 중에만 true

// 에러 로깅
define('ERROR_LOG_PATH', __DIR__ . '/logs/error.log');
define('ENABLE_ERROR_LOG', true);

// 타임존
define('TIMEZONE', 'Asia/Seoul');

// URL 설정 (선택사항)
define('BASE_URL', 'http://localhost');

// 보안 키 (JWT, 암호화 등에 사용)
define('SECRET_KEY', 'your-secret-key-here-change-this'); // 반드시 변경하세요!

// 기타 설정
define('MAX_LOGIN_ATTEMPTS', 5); // 최대 로그인 시도 횟수
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15분 (초 단위)
