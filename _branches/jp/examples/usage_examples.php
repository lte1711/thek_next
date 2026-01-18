<?php
/**
 * 개선된 코드 사용 예시 (문서용)
 *
 * 운영(프로덕션) 서버에는 배포하지 않거나, 반드시 접근을 차단하세요.
 * 본 파일은 DEBUG_MODE=true 인 경우에만 내용을 텍스트로 보여줍니다.
 */

// 운영(프로덕션)에서는 예시 파일 접근을 차단
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
readfile(__FILE__);
exit;
