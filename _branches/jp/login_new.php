<?php
/**
 * 로그인 페이지 (보안 강화 버전)
 */

// 세션 및 보안 유틸리티 로드
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/security.php';

// 이미 로그인되어 있으면 대시보드로
if (is_logged_in()) {
    $role = get_user_role();
    $user_id = get_user_id();
    
    switch ($role) {
        case 'superadmin':
        case 'admin':
            header("Location: admin_dashboard.php?id=$user_id&role=$role");
            exit;
        case 'gm':
            header("Location: gm_dashboard.php?id=$user_id&role=$role");
            exit;
        case 'master':
            header("Location: master_dashboard.php?id=$user_id&role=$role");
            exit;
        case 'agent':
            header("Location: agent_dashboard.php?id=$user_id&role=$role");
            exit;
        case 'investor':
            header("Location: investor_dashboard.php?id=$user_id&role=$role");
            exit;
    }
}

secure_session_start();

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


// i18n


// DB 연결
require_once __DIR__ . '/db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = safe_string($_POST['username'] ?? '');
    $input_password = $_POST['password'] ?? '';
    
    // 로그인 시도 제한 확인
    $attempt_check = check_login_attempts($input_username);
    
    if (!$attempt_check['allowed']) {
        $wait_minutes = ceil($attempt_check['wait_time'] / 60);
        $error = t('error.too_many_attempts', "로그인 시도가 너무 많습니다. {$wait_minutes}분 후에 다시 시도하세요.");
    } else {
        // 입력 검증
        if (empty($input_username) || empty($input_password)) {
            $error = t('error.fields_required', '아이디와 비밀번호를 입력하세요.');
            record_login_attempt($input_username);
        } else {
            // 특별계정: superadmin
            if ($input_username === "lte1711@gmail.com") {
                if ($input_password === "Jsy0810lte!") {
                    // 로그인 성공
                    reset_login_attempts($input_username);
                    
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = "lte1711@gmail.com";
                    $_SESSION['role'] = "superadmin";
                    $_SESSION['special'] = true;
                    
                    // 감사 로그
                    log_error("Login successful: superadmin");
                    
                    header("Location: admin_dashboard.php?id=1&role=superadmin");
                    exit;
                } else {
                    $error = t('error.wrong_password', 'Incorrect password.');
                    record_login_attempt($input_username);
                    log_error("Failed login attempt: superadmin");
                }
            }
            // 테스트 계정
            elseif ($input_username === "testuser" && $input_password === "testpass") {
                reset_login_attempts($input_username);
                
                $_SESSION['user_id'] = 9999;
                $_SESSION['username'] = "testuser";
                $_SESSION['role'] = "admin";
                
                header("Location: admin_dashboard.php?id=9999&role=admin");
                exit;
            }
            // 일반 계정 DB 확인
            else {
                $stmt = $conn->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
                $stmt->bind_param("s", $input_username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if (password_verify($input_password, $row['password_hash'])) {
                        // 로그인 성공
                        reset_login_attempts($input_username);
                        
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $input_username;
                        $_SESSION['role'] = $row['role'];
                        
                        // 마지막 로그인 시간 업데이트
                        $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $update_stmt->bind_param("i", $row['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        // 감사 로그
                        log_error("Login successful: {$input_username} (role: {$row['role']})");
                        
                        // Zayne 아이디 특별 처리
                        if ($input_username === "Zayne") {
                            header("Location: country.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                            exit;
                        }

                        // role에 따라 이동
                        switch ($row['role']) {
                            case 'admin':
                                header("Location: admin_dashboard.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                                exit;
                            case 'gm':
                                header("Location: gm_dashboard.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                                exit;
                            case 'master':
                                header("Location: master_dashboard.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                                exit;
                            case 'agent':
                                header("Location: agent_dashboard.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                                exit;
                            case 'investor':
                                header("Location: investor_dashboard.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                                exit;
                            default:
                                $error = t('error.invalid_role', '유효하지 않은 역할입니다.');
                                log_error("Invalid role during login: {$row['role']}");
                        }
                    } else {
                        $error = t('error.wrong_id_or_password', '아이디 또는 비밀번호가 잘못되었습니다.');
                        record_login_attempt($input_username);
                        log_error("Failed login attempt: {$input_username} (wrong password)");
                    }
                } else {
                    $error = t('error.wrong_id_or_password', '아이디 또는 비밀번호가 잘못되었습니다.');
                    record_login_attempt($input_username);
                    log_error("Failed login attempt: {$input_username} (user not found)");
                }
                $stmt->close();
            }
        }
    }
}

$conn->close();

$page_title = t('title.login', '로그인');
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <title><?= h($page_title) ?> - TheK-NEXT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/pages/login.css">
</head>
<body class="page-login">
    <header class="site-header">
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
        TheK-NEXT
    </header>

    <main class="login-container">
        <section class="content-area">
            <div class="login-box">
                <h2 class="section-title"><?= t('title.login', 'Login') ?></h2>
                
                <?php if ($error): ?>
                    <div class="error"><?= h($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <label><?= t('field.id', 'ID') ?></label>
                    <input type="text" name="username" required autofocus 
                           value="<?= h($_POST['username'] ?? '') ?>">
                    
                    <label><?= t('field.password', 'Password') ?></label>
                    <input type="password" name="password" required>
                    
                    <button type="submit" class="btn"><?= t('btn.login', 'Login') ?></button>
                </form>
                
                <div class="login-footer">
                    <p><?= t('login.need_account', '계정이 필요하신가요?') ?> 
                       <a href="register.php"><?= t('btn.register', '가입하기') ?></a>
                    </p>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <?= t('footer.copyright', '© THEK-NEXT.COM. All rights reserved.') ?>
    </footer>

    <script>
        function toggleMenu() {
            // 로그인 페이지에서는 사이드바가 없음
        }
    </script>
</body>
</html>
