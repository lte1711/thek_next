<?php
// 운영(프로덕션)에서는 점검/유틸 스크립트 접근을 차단합니다.
// 필요 시 config.php에서 DEBUG_MODE=true로 설정한 뒤 사용하세요.
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
    http_response_code(404);
    exit;
}
/**
 * PHP를 사용한 파일 수정 스크립트
 * sed보다 안전하게 처리
 */

$outputDir = 'modified_project_v3';
$finalZip = 'thek_next_secured_v3.zip';

// 디렉토리 초기화
if (is_dir($outputDir)) {
    exec("rm -rf $outputDir");
}
mkdir($outputDir);

$total = 0;
$modified = 0;
$copied = 0;
$special = 0;

// 제외 파일
$skipFiles = ['db_connect.php', 'config.php', 'layout.php', 'header.php', 'footer.php'];

// 특별 파일 (인증 불필요)
$specialFiles = ['login.php', 'login_new.php', 'logout.php', 'register.php', 'index.php'];

// 안전한 인증 코드 (파일 존재 확인)
$safeAuthCode = <<<'AUTH'

// Security: Authentication middleware (Safe mode)
$auth_file = __DIR__ . '/includes/auth_middleware.php';
if (file_exists($auth_file)) {
    require_once $auth_file;
    require_login();
} else {
    // Fallback: Manual authentication check
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
AUTH;

// 안전한 CSRF 코드 (로그인 페이지용)
$safeCSRFCode = <<<'CSRF'

// Security: CSRF Protection (Safe mode)
$auth_file = __DIR__ . '/includes/auth_middleware.php';
if (file_exists($auth_file)) {
    require_once $auth_file;
}
CSRF;

// PHP 파일 처리
$files = glob('*.php');
foreach ($files as $file) {
    $total++;
    $basename = basename($file);
    $dst = "$outputDir/$file";
    
    // 제외 파일
    if (in_array($basename, $skipFiles)) {
        copy($file, $dst);
        $copied++;
        echo "→ 복사: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // session_start() 없으면 그대로 복사

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

    if (strpos($content, 'session_start()') === false) {
        copy($file, $dst);
        $copied++;
        echo "→ 복사 (수정 불필요): $file\n";
        continue;
    }
    
    // 이미 적용됨
    if (strpos($content, 'auth_middleware.php') !== false) {
        copy($file, $dst);
        $copied++;
        echo "→ 복사 (이미 적용됨): $file\n";
        continue;
    }
    
    // session_start() 다음 줄에 코드 삽입
    $lines = explode("\n", $content);
    $newLines = [];
    $inserted = false;
    
    foreach ($lines as $line) {
        $newLines[] = $line;
        
        if (!$inserted && strpos($line, 'session_start()') !== false) {
            // 특별 파일 여부 확인
            if (in_array($basename, $specialFiles)) {
                $newLines[] = $safeCSRFCode;
                $special++;
                echo "⚡ 특별 처리 (안전 모드): $file\n";
            } else {
                $newLines[] = $safeAuthCode;
                $modified++;
                echo "✓ 수정 완료 (안전 모드): $file\n";
            }
            $inserted = true;
        }
    }
    
    file_put_contents($dst, implode("\n", $newLines));
}

// 디렉토리 복사
$dirs = ['includes', 'lang', 'css', 'js', 'assets'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        exec("cp -r $dir $outputDir/");
        echo "✓ $dir/ 복사 완료\n";
    }
}

// ZIP 생성
echo "\n압축 파일 생성 중...\n";
chdir($outputDir);
exec("zip -r ../$finalZip . -q");
chdir('..');

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "완료!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "📊 처리 결과:\n";
echo "   전체 파일:      $total개\n";
echo "   수정됨:         $modified개\n";
echo "   특별 처리:      $special개\n";
echo "   복사만:         $copied개\n\n";
echo "📦 출력 파일: $finalZip\n";
$size = filesize($finalZip);
echo "   크기: " . round($size/1024) . "KB\n\n";
