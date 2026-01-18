<?php
session_start();

// CSRF (login)
if (empty($_SESSION['login_csrf_token']) || !is_string($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}


// ✅ 관리자 로그인 체크 (고정된 이메일/비밀번호)
$admin_email    = "lte1711@gmail.com";
$admin_password = "Jsy0810lte!";

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'], $_POST['password'])) {
    $posted_token = $_POST['login_csrf_token'] ?? '';
    if (!is_string($posted_token) || !hash_equals($_SESSION['login_csrf_token'], $posted_token)) {
        $error = '잘못된 요청입니다. 새로고침 후 다시 시도하세요.';
    } else {
    if ($_POST['email'] === $admin_email && $_POST['password'] === $admin_password) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_role'] = 'superadmin';
        $_SESSION['admin_email'] = $admin_email;
        $_SESSION['last_activity'] = time();

        // 감사로그: 로그인 성공 (audit_logs 테이블이 없으면 자동 무시)
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli('localhost', 'thek_db_admin', 'thek_pw_admin!', 'thek_next_db');
            $conn->set_charset('utf8mb4');
            $stmt = $conn->prepare("INSERT INTO audit_logs (admin_email, admin_role, action, target_table, target_id, before_json, after_json, extra_json, ip, user_agent) VALUES (?, ?, 'login', NULL, NULL, NULL, NULL, NULL, ?, ?)");
            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            $stmt->bind_param('ssss', $admin_email, $_SESSION['admin_role'], $ip, $ua);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        } catch (Throwable $e) {
            // 무시
        }
        header("Location: index.php");
        exit;
    } else {
        $error = "접속 권한이 없습니다.";
    }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인</title>
    <style>
        :root{
            --bg:#0b1020;
            --text:#e5e7eb;
            --muted:#9ca3af;
            --border:rgba(255,255,255,.10);
            --accent:#7c3aed;
            --danger:#ef4444;
            --shadow:0 18px 45px rgba(0,0,0,.45);
        }
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,-apple-system,Segoe UI,Roboto,Apple SD Gothic Neo,Noto Sans KR,sans-serif;background:linear-gradient(180deg,var(--bg),#070a14);color:var(--text);}
        .wrap{width:min(460px,92vw);padding:22px;border:1px solid var(--border);border-radius:18px;background:rgba(255,255,255,.03);box-shadow:var(--shadow)}
        .brand{display:flex;gap:12px;align-items:center;margin-bottom:14px}
        .logo{width:44px;height:44px;border-radius:16px;display:grid;place-items:center;background:rgba(124,58,237,.22);border:1px solid rgba(124,58,237,.35)}
        h1{margin:0;font-size:18px}
        p{margin:6px 0 0;color:var(--muted);font-size:12px}
        label{display:block;margin:10px 0 6px;color:var(--muted);font-size:12px}
        input{width:100%;padding:12px 12px;border-radius:12px;border:1px solid var(--border);background:rgba(255,255,255,.03);color:var(--text);outline:none}
        input:focus{border-color:rgba(124,58,237,.55)}
        button{width:100%;margin-top:14px;padding:12px 12px;border-radius:12px;border:1px solid rgba(124,58,237,.45);background:rgba(124,58,237,.25);color:var(--text);cursor:pointer;font-weight:700}
        button:hover{background:rgba(124,58,237,.32)}
        .err{margin-top:12px;padding:10px 12px;border-radius:12px;border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.10);color:#fee2e2;font-size:13px}
        .foot{margin-top:12px;color:var(--muted);font-size:12px;text-align:center}
        .tag{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;border:1px solid var(--border);background:rgba(255,255,255,.03);font-size:12px;color:var(--muted)}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <div class="logo">🛡️</div>
            <div>
                <h1>THEK 관리자 로그인</h1>
                <p>권한이 있는 계정만 접속할 수 있습니다. <span class="tag">Admin</span></p>
            </div>
        </div>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="login_csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <label>이메일</label>
            <input type="text" name="email" required>

            <label>비밀번호</label>
            <input type="password" name="password" required>

            <button type="submit">로그인</button>

            <?php if (!empty($error)): ?>
                <div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </form>

        <div class="foot">© THEK Next Admin</div>
    </div>
</body>
</html>
