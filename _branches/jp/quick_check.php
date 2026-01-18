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
 * 서버 적용 후 빠른 체크 스크립트
 * 
 * 이 파일을 서버에 업로드하고 브라우저에서 실행하세요.
 * URL: http://your-domain.com/quick_check.php
 * 
 * 사용 후 반드시 삭제하세요! (보안상 중요)
 */

// 보안을 위해 IP 제한 (선택)
$allowed_ips = ['127.0.0.1', 'YOUR_IP_HERE'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Access Denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>시스템 체크</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .check-item { margin: 15px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .check-title { font-weight: bold; margin-bottom: 5px; }
        .check-detail { font-size: 14px; color: #666; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
        .status-ok { background: #28a745; color: white; }
        .status-error { background: #dc3545; color: white; }
        .status-warning { background: #ffc107; color: black; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 시스템 적용 체크</h1>
        <p>서버에 적용된 개선사항을 확인합니다.</p>

<?php

$checks = [];

// 1. 파일 존재 확인
echo "<h2>1. 필수 파일 확인</h2>";

$required_files = [
    'includes/auth_middleware.php' => '인증 미들웨어',
    'includes/Database.php' => '데이터베이스 래퍼',
    'includes/i18n.php' => '다국어 지원',
    'db_connect.php' => '데이터베이스 연결',
];

foreach ($required_files as $file => $name) {
    $exists = file_exists($file);
    $class = $exists ? 'success' : 'error';
    $status = $exists ? 'OK' : 'MISSING';
    $status_class = $exists ? 'status-ok' : 'status-error';
    
    echo "<div class='check-item $class'>";
    echo "<div class='check-title'>$name <span class='status $status_class'>$status</span></div>";
    echo "<div class='check-detail'><code>$file</code></div>";
    echo "</div>";
    
    $checks['files'][$file] = $exists;
}

// 2. PHP 함수 확인
echo "<h2>2. 함수 및 클래스 확인</h2>";

if (file_exists('includes/auth_middleware.php')) {
    require_once 'includes/auth_middleware.php';
}

if (file_exists('includes/Database.php')) {
    require_once 'includes/Database.php';
}

$required_functions = [
    'require_login' => '로그인 체크 함수',
    'require_role' => '권한 체크 함수',
    'generate_csrf_token' => 'CSRF 토큰 생성',
    'verify_csrf_token' => 'CSRF 토큰 검증',
];

foreach ($required_functions as $func => $name) {
    $exists = function_exists($func);
    $class = $exists ? 'success' : 'warning';
    $status = $exists ? 'OK' : 'NOT FOUND';
    $status_class = $exists ? 'status-ok' : 'status-warning';
    
    echo "<div class='check-item $class'>";
    echo "<div class='check-title'>$name <span class='status $status_class'>$status</span></div>";
    echo "<div class='check-detail'><code>$func()</code></div>";
    echo "</div>";
    
    $checks['functions'][$func] = $exists;
}

// Database 클래스 확인
$db_class_exists = class_exists('Database');
$class = $db_class_exists ? 'success' : 'warning';
$status = $db_class_exists ? 'OK' : 'NOT FOUND';
$status_class = $db_class_exists ? 'status-ok' : 'status-warning';

echo "<div class='check-item $class'>";
echo "<div class='check-title'>Database 클래스 <span class='status $status_class'>$status</span></div>";
echo "<div class='check-detail'><code>class Database</code></div>";
echo "</div>";

$checks['classes']['Database'] = $db_class_exists;

// 3. 데이터베이스 연결 확인
echo "<h2>3. 데이터베이스 연결</h2>";

if (file_exists('db_connect.php')) {
    try {
        include 'db_connect.php';
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<div class='check-item error'>";
                echo "<div class='check-title'>DB 연결 <span class='status status-error'>ERROR</span></div>";
                echo "<div class='check-detail'>연결 실패: " . $conn->connect_error . "</div>";
                echo "</div>";
                $checks['database']['connection'] = false;
            } else {
                echo "<div class='check-item success'>";
                echo "<div class='check-title'>DB 연결 <span class='status status-ok'>OK</span></div>";
                echo "<div class='check-detail'>정상적으로 연결되었습니다.</div>";
                echo "</div>";
                $checks['database']['connection'] = true;
                
                // Database 클래스 테스트
                if ($db_class_exists) {
                    try {
                        $db = new Database($conn);
                        
                        // 간단한 쿼리 테스트
                        $result = $db->query("SELECT 1 as test");
                        $row = $result->fetch_assoc();
                        
                        if ($row['test'] == 1) {
                            echo "<div class='check-item success'>";
                            echo "<div class='check-title'>Database 클래스 동작 <span class='status status-ok'>OK</span></div>";
                            echo "<div class='check-detail'>쿼리 실행이 정상적으로 작동합니다.</div>";
                            echo "</div>";
                            $checks['database']['class_works'] = true;
                        }
                    } catch (Exception $e) {
                        echo "<div class='check-item error'>";
                        echo "<div class='check-title'>Database 클래스 동작 <span class='status status-error'>ERROR</span></div>";
                        echo "<div class='check-detail'>에러: " . $e->getMessage() . "</div>";
                        echo "</div>";
                        $checks['database']['class_works'] = false;
                    }
                }
                
                // 테이블 존재 확인
                $tables = ['users', 'user_details', 'user_transactions', 'dividend'];
                $missing_tables = [];
                
                foreach ($tables as $table) {
                    $result = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($result->num_rows == 0) {
                        $missing_tables[] = $table;
                    }
                }
                
                if (empty($missing_tables)) {
                    echo "<div class='check-item success'>";
                    echo "<div class='check-title'>필수 테이블 <span class='status status-ok'>OK</span></div>";
                    echo "<div class='check-detail'>모든 필수 테이블이 존재합니다.</div>";
                    echo "</div>";
                    $checks['database']['tables'] = true;
                } else {
                    echo "<div class='check-item warning'>";
                    echo "<div class='check-title'>필수 테이블 <span class='status status-warning'>WARNING</span></div>";
                    echo "<div class='check-detail'>누락된 테이블: " . implode(', ', $missing_tables) . "</div>";
                    echo "</div>";
                    $checks['database']['tables'] = false;
                }
            }
        } else {
            echo "<div class='check-item error'>";
            echo "<div class='check-title'>DB 연결 <span class='status status-error'>ERROR</span></div>";
            echo "<div class='check-detail'>\$conn 객체가 올바르지 않습니다.</div>";
            echo "</div>";
            $checks['database']['connection'] = false;
        }
    } catch (Exception $e) {
        echo "<div class='check-item error'>";
        echo "<div class='check-title'>DB 연결 <span class='status status-error'>ERROR</span></div>";
        echo "<div class='check-detail'>예외 발생: " . $e->getMessage() . "</div>";
        echo "</div>";
        $checks['database']['connection'] = false;
    }
} else {
    echo "<div class='check-item error'>";
    echo "<div class='check-title'>db_connect.php <span class='status status-error'>MISSING</span></div>";
    echo "<div class='check-detail'>파일을 찾을 수 없습니다.</div>";
    echo "</div>";
    $checks['database']['connection'] = false;
}

// 4. 디렉토리 권한 확인
echo "<h2>4. 디렉토리 및 권한</h2>";

$required_dirs = [
    'cache/login_attempts' => '로그인 시도 캐시',
    'logs' => '로그 파일',
];

foreach ($required_dirs as $dir => $name) {
    $exists = is_dir($dir);
    $writable = $exists && is_writable($dir);
    
    if ($exists && $writable) {
        $class = 'success';
        $status = 'OK';
        $status_class = 'status-ok';
        $detail = '존재하고 쓰기 가능합니다.';
    } elseif ($exists && !$writable) {
        $class = 'warning';
        $status = 'NOT WRITABLE';
        $status_class = 'status-warning';
        $detail = '존재하지만 쓰기 권한이 없습니다. <code>chmod 755 ' . $dir . '</code> 실행 필요';
    } else {
        $class = 'warning';
        $status = 'NOT FOUND';
        $status_class = 'status-warning';
        $detail = '디렉토리가 없습니다. <code>mkdir -p ' . $dir . '</code> 실행 필요';
    }
    
    echo "<div class='check-item $class'>";
    echo "<div class='check-title'>$name <span class='status $status_class'>$status</span></div>";
    echo "<div class='check-detail'>$detail</div>";
    echo "</div>";
    
    $checks['directories'][$dir] = $exists && $writable;
}

// 5. PHP 설정 확인
echo "<h2>5. PHP 설정</h2>";

$php_settings = [
    'session.cookie_httponly' => ['권장값' => '1', '현재값' => ini_get('session.cookie_httponly')],
    'session.use_strict_mode' => ['권장값' => '1', '현재값' => ini_get('session.use_strict_mode')],
    'display_errors' => ['권장값' => '0 (프로덕션)', '현재값' => ini_get('display_errors')],
];

foreach ($php_settings as $setting => $values) {
    $current = $values['현재값'];
    $recommended = $values['권장값'];
    
    echo "<div class='check-item info'>";
    echo "<div class='check-title'>$setting</div>";
    echo "<div class='check-detail'>권장: <code>$recommended</code>, 현재: <code>$current</code></div>";
    echo "</div>";
}

// 6. 종합 결과
echo "<h2>📊 종합 결과</h2>";

$total_checks = 0;
$passed_checks = 0;

foreach ($checks as $category => $items) {
    foreach ($items as $result) {
        $total_checks++;
        if ($result) $passed_checks++;
    }
}

$pass_rate = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;

if ($pass_rate >= 90) {
    $result_class = 'success';
    $result_message = '✅ 모든 검사를 통과했습니다!';
} elseif ($pass_rate >= 70) {
    $result_class = 'warning';
    $result_message = '⚠️ 일부 항목에 주의가 필요합니다.';
} else {
    $result_class = 'error';
    $result_message = '❌ 여러 문제가 발견되었습니다. 즉시 수정이 필요합니다.';
}

echo "<div class='check-item $result_class'>";
echo "<div class='check-title' style='font-size: 18px;'>$result_message</div>";
echo "<div class='check-detail'>통과율: $passed_checks / $total_checks ($pass_rate%)</div>";
echo "</div>";

// 다음 단계 안내
echo "<h2>🎯 다음 단계</h2>";

if ($pass_rate >= 90) {
    echo "<div class='check-item info'>";
    echo "<div class='check-title'>권장 작업</div>";
    echo "<div class='check-detail'>";
    echo "<ol>";
    echo "<li>테스트 페이지에서 로그인 기능 테스트</li>";
    echo "<li>권한 체크 테스트 (GM, Admin, Investor 등)</li>";
    echo "<li>데이터베이스 CRUD 작업 테스트</li>";
    echo "<li>이 체크 스크립트 삭제 (보안)</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='check-item warning'>";
    echo "<div class='check-title'>즉시 조치 필요</div>";
    echo "<div class='check-detail'>";
    echo "<ol>";
    echo "<li>위의 빨간색/노란색 항목들을 먼저 해결하세요</li>";
    echo "<li>필요한 파일이 누락되었다면 다시 업로드하세요</li>";
    echo "<li>디렉토리 권한 문제는 SSH에서 수정하세요</li>";
    echo "<li>모든 문제 해결 후 이 페이지를 다시 새로고침하세요</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
}

echo "<div class='check-item error'>";
echo "<div class='check-title'>⚠️ 중요: 보안 안내</div>";
echo "<div class='check-detail'>";
echo "이 체크 스크립트는 <strong>테스트 완료 후 반드시 삭제</strong>해야 합니다!<br>";
echo "시스템 정보가 노출될 수 있습니다.<br><br>";
echo "삭제 명령어: <code>rm quick_check.php</code>";
echo "</div>";
echo "</div>";

?>

        <p style="text-align: center; margin-top: 30px; color: #666;">
            생성 시간: <?= date('Y-m-d H:i:s') ?>
        </p>
    </div>
</body>
</html>
