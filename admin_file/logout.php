<?php
require_once 'admin_bootstrap.php'; // CSRF 검증 + superadmin 체크 포함

// POST로만 로그아웃 처리 (CSRF 보호)
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    // 간단한 확인 화면
    require_once 'admin_layout.php';
    admin_render_header('로그아웃');
    ?>
    <div class="card" style="max-width:520px">
        <h2 style="margin-top:0">로그아웃 하시겠습니까?</h2>
        <form method="POST" action="logout.php">
            <?= csrf_input() ?>
            <button class="btnlink" type="submit" style="background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.35)">로그아웃</button>
            <a class="btnlink" href="index.php" style="margin-left:8px">취소</a>
        </form>
    </div>
    <?php
    admin_render_footer();
    exit;
}

// 여기 도착하면 admin_bootstrap에서 CSRF 검증이 이미 끝난 상태
// 감사 로그
audit_log($conn, 'logout');

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: edit_Thek_pm.php?logged_out=1");
exit;
?>