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
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>권한 확인</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .box {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<h1>🔍 권한 확인 도구</h1>

<?php if (!isset($_SESSION['user_id'])): ?>
    
    <div class="box error">
        <h2>❌ 로그인되어 있지 않습니다</h2>
        <p>먼저 로그인해주세요.</p>
        <a href="login.php" class="button">로그인 페이지로 이동</a>
    </div>

<?php else: ?>

    <div class="box">
        <h2>📋 현재 세션 정보</h2>
        <table>
            <tr>
                <th style="width: 200px;">항목</th>
                <th>값</th>
            </tr>
            <tr>
                <td>User ID</td>
                <td><?= htmlspecialchars($_SESSION['user_id'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Username</td>
                <td><?= htmlspecialchars($_SESSION['username'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Role (세션)</td>
                <td><strong><?= htmlspecialchars($_SESSION['role'] ?? 'N/A') ?></strong></td>
            </tr>
        </table>
    </div>

    <?php
    include 'db_connect.php';
    
    $stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    ?>

    <div class="box">
        <h2>💾 데이터베이스 정보</h2>
        <table>
            <tr>
                <th style="width: 200px;">항목</th>
                <th>값</th>
            </tr>
            <tr>
                <td>ID</td>
                <td><?= htmlspecialchars($user['id']) ?></td>
            </tr>
            <tr>
                <td>Username</td>
                <td><?= htmlspecialchars($user['username']) ?></td>
            </tr>
            <tr>
                <td>Role (DB)</td>
                <td><strong><?= htmlspecialchars($user['role']) ?></strong></td>
            </tr>
            <tr>
                <td>가입일</td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
            </tr>
        </table>
    </div>

    <?php if ($user['role'] === 'gm'): ?>
        
        <div class="box success">
            <h2>✅ GM 권한 있음</h2>
            <p style="font-size: 18px; margin: 20px 0;">
                <strong>Partner_accounts_v2.php 접근 가능합니다!</strong>
            </p>
            <a href="Partner_accounts_v2.php" class="button">Partner Accounts 보기</a>
        </div>

    <?php else: ?>
        
        <div class="box error">
            <h2>❌ GM 권한 없음</h2>
            <p>현재 역할: <strong><?= htmlspecialchars($user['role']) ?></strong></p>
            <p>Partner_accounts_v2.php는 GM 권한이 필요합니다.</p>
            
            <h3 style="margin-top: 30px;">해결 방법:</h3>
            <ol>
                <li>
                    <strong>관리자에게 GM 권한 요청</strong>
                    <p>시스템 관리자에게 연락하여 GM 권한을 요청하세요.</p>
                </li>
                <li>
                    <strong>MySQL에서 직접 수정</strong> (관리자만)
                    <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;">
mysql -u thek_db_admin -p thek_next_db

UPDATE users SET role = 'gm' WHERE id = <?= htmlspecialchars($user['id']) ?>;

SELECT id, username, role FROM users WHERE id = <?= htmlspecialchars($user['id']) ?>;

exit;</pre>
                    <p>수정 후 로그아웃 → 재로그인 필요</p>
                </li>
                <li>
                    <strong>GM 계정으로 로그인</strong>
                    <p>GM 권한을 가진 다른 계정으로 로그인하세요.</p>
                </li>
            </ol>
        </div>

    <?php endif; ?>

    <div class="box">
        <h2>🎭 사용 가능한 역할</h2>
        <table>
            <tr>
                <th style="width: 150px;">Role</th>
                <th>설명</th>
                <th style="width: 100px;">현재</th>
            </tr>
            <tr>
                <td><code>gm</code></td>
                <td>Global Master - Partner_accounts_v2.php 접근 가능</td>
                <td><?= $user['role'] === 'gm' ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td><code>admin</code></td>
                <td>Administrator - 관리자 기능</td>
                <td><?= $user['role'] === 'admin' ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td><code>master</code></td>
                <td>Master - 마스터 기능</td>
                <td><?= $user['role'] === 'master' ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td><code>agent</code></td>
                <td>Agent - 에이전트 기능</td>
                <td><?= $user['role'] === 'agent' ? '✅' : '❌' ?></td>
            </tr>
            <tr>
                <td><code>investor</code></td>
                <td>Investor - 투자자 기능</td>
                <td><?= $user['role'] === 'investor' ? '✅' : '❌' ?></td>
            </tr>
        </table>
    </div>

<?php endif; ?>

<div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
    <h3>💡 도움말</h3>
    <ul>
        <li>이 페이지는 현재 로그인 계정의 권한을 확인합니다.</li>
        <li>GM 권한이 없으면 Partner_accounts_v2.php에 접근할 수 없습니다.</li>
        <li>권한 변경 후에는 반드시 로그아웃 → 재로그인 해야 합니다.</li>
        <li>문제가 계속되면 시스템 관리자에게 문의하세요.</li>
    </ul>
</div>

</body>
</html>
