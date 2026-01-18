<?php
session_start();

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


// i18n 로드 (경로 수정)
if (!function_exists('t')) {
    $i18n_path = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n_path)) {
        require_once $i18n_path;
    } else {
        // fallback: 함수 정의되지 않으면 더미 함수 제공
        function t($key, $fallback = null) {
            return $fallback ?? $key;
        }
        function current_lang() {
            return 'ko';
        }
    }
}


// DB 연결
$servername = "localhost";
$username   = "thek_db_admin";
$password   = "thek_pw_admin!";
$dbname     = "thek_next_db";

require_once "db_connect.php";
if ($conn->connect_error) {
    die(t('error.db_connect_failed','DB connection failed') . ": " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = trim($_POST['username']);
    $input_password = trim($_POST['password']);

    // ✅ 특별계정: lte1711@gmail.com → superadmin 권한 부여
    if ($input_username === "lte1711@gmail.com") {
        if ($input_password === "Jsy0810lte!") {
            $_SESSION['user_id']   = 1; // 특별계정 ID
            $_SESSION['username']  = "lte1711@gmail.com";
            $_SESSION['role']      = "superadmin";
            $_SESSION['special']   = true;

            header("Location: admin_file/index.php");
            exit;
        } else {
            $error = t('error.wrong_password', 'Incorrect password.');
        }
    }

    // ✅ 테스트용 로그인 계정
    elseif ($input_username === "testuser" && $input_password === "testpass") {
        $_SESSION['user_id']   = 9999;
        $_SESSION['username']  = "testuser";
        $_SESSION['role']      = "admin";

        header("Location: admin_dashboard.php?id=" . urlencode($_SESSION['user_id']) . "&role=" . urlencode($_SESSION['role']));
        exit;
    }

    // ✅ 일반 계정 DB 확인
    else {
        $stmt = $conn->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $input_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($input_password, $row['password_hash'])) {
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['username']  = $input_username;
                $_SESSION['role']      = $row['role'];

                // ✅ Zayne 아이디일 경우 country.php로 이동
                if ($input_username === "Zayne") {
                    header("Location: country.php?id=" . urlencode($row['id']) . "&role=" . urlencode($row['role']));
                    exit;
                }

                // ✅ role에 따라 이동
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
                        $msg = addslashes(t('error.invalid_role'));
                        echo "<script>
                                alert('{$msg}');
                                window.location.href = 'login.php';
                              </script>";
                        exit;
                }
            } else {
                $error = t('error.wrong_id_or_password', '아이디 또는 비밀번호가 잘못되었습니다.');
            }
        } else {
            $error = t('error.wrong_id_or_password', '아이디 또는 비밀번호가 잘못되었습니다.');
        }
        $stmt->close();
    }
}
$conn->close();

// 레이아웃 변수
$page_title = "로그인 페이지";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 반응형 -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/pages/login.css">
</head>
<body class="page-login">
    <!-- 헤더 -->
    <header class="site-header">
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
        TheK-NEXT
    </header>

    <!-- 메인 컨테이너 -->
    <main class="login-container">
        <section class="content-area">
            <div class="login-box">
                <h2 class="section-title"><?= t('title.login', 'Login') ?></h2>
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <label><?= t('field.id', 'ID') ?></label>
                    <input type="text" name="username" required>
                    <label><?= t('field.password', 'Password') ?></label>
                    <input type="password" name="password" required>
                    <button type="submit" class="btn"><?= t('btn.login', 'Login') ?></button>
                </form>
            </div>
        </section>
    </main>

    <!-- 푸터 -->
    <footer class="site-footer"><?= t('footer.copyright', '© THEK-NEXT.COM. All rights reserved.') ?></footer>

    <script>
        function toggleMenu() {
            // 로그인 페이지에서는 사이드바가 없으므로 아무 동작 없음
        }
    </script>
</body>
</html>