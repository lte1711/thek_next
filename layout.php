<?php
// 모든 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// role 값 확인
if (!isset($_SESSION['role'])) {
    error_log("접속 오류: 로그인 필요.");
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['role'];
$username  = $_SESSION['username'] ?? null;

// ✅ 특별계정(superadmin)은 모든 페이지 접근 허용
if ($user_role === 'superadmin') {
    // superadmin은 모든 권한을 가짐
}

// ✅ username이 Zayne이면 바로 korea Ready 화면으로 이동 (country pages split)
if ($username === 'Zayne') {
    $self = basename($_SERVER['PHP_SELF']);
    $is_country_self = in_array($self, [
        'country.php',
        'country_ready.php',
        'country_progressing.php',
        'country_completed.php',
        'country_profit_share.php'
    ], true);

    if (!$is_country_self) {
        header("Location: country_ready.php?region=korea");
        exit;
    }
}

// 국가 페이지 여부 체크 (country.php에서 $is_country_page = true 설정)
$is_country_page = $is_country_page ?? false;

// 페이지 타이틀
$page_title = $page_title ?? ($is_country_page ? "" : "대시보드");

// 본문 파일 지정
if (!isset($content_file) && !$is_country_page) {
    switch ($user_role) {
        case 'gm':
            $content_file = __DIR__ . "/gm_dashboard_content.php";
            $page_title = "글로벌 마스터 대시보드";
            break;
        case 'admin':
            $content_file = __DIR__ . "/admin_dashboard.php";
            $page_title = "관리자 대시보드";
            break;
        case 'master':
            $content_file = __DIR__ . "/master_dashboard.php";
            $page_title = "마스터 대시보드";
            break;
        case 'agent':
            $content_file = __DIR__ . "/agent_dashboard.php";
            $page_title = "에이전트 대시보드";
            break;
        case 'investor':
        default:
            $content_file = __DIR__ . "/investor_dashboard.php";
            $page_title = "투자자 대시보드";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title ?: "FX Global Master UI") ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 반응형 -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
<?php
    // ✅ Page-specific CSS mapping (style only; no logic change)
    if (!isset($page_css) || $page_css === null || $page_css === '') {
        $page = basename($_SERVER['PHP_SELF']);

        // login handled in login.php
        if (strpos($page, 'dashboard') !== false) {
            $page_css = 'dashboard.css';
        } elseif ($page === 'country.php' || preg_match('/^country_(ready|progressing|completed|profit_share)\.php$/', $page)) {
            $page_css = 'korea.css';
        } elseif (preg_match('/(^|_)list\.php$/', $page)) {
            $page_css = 'gm_list.css';
        } elseif (in_array($page, ['Partner_accounts.php','partner_accounts.php','group_accounts.php','profit_share.php','investor_profit_share.php'], true)) {
            $page_css = 'partner_accounts.css';
        } elseif (preg_match('/^(create_|edit_).+\.php$/', $page) || in_array($page, ['c_create_account.php'], true)) {
            $page_css = 'master_form.css';
        }
    }
?>
<?php if (!empty($page_css)): ?>
    <link rel="stylesheet" href="css/pages/<?= htmlspecialchars($page_css) ?>">
<?php endif; ?>
</head>
<body>
    <!-- 헤더 -->
<header class="site-header" style="display:flex; align-items:center;">

    <!-- 왼쪽 영역 -->
    <div style="display:flex; align-items:center; gap:10px;">
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>

        <?php if (!$is_country_page): ?>
            <span><?= htmlspecialchars($page_title) ?></span>
        <?php endif; ?>
    </div>

    <!-- 오른쪽 영역 (강제 우측 정렬) -->
    <?php
    $user_role_safe = strtolower(trim($user_role ?? $_SESSION['role'] ?? ''));
    ?>
    <?php if ($user_role_safe === 'gm'): ?>
        <div style="display:flex; align-items:center; gap:10px; margin-left:auto;">
            <?php if (empty($is_country_page)): ?>
                <a href="gm_revenue_report.php"
                   style="background:none; border:none; cursor:pointer; font-size:20px; text-decoration:none;"
                   title="리포트 출력">🖨</a>
            <?php endif; ?>

            <a href="org_chart.php"
               style="background:none; border:none; cursor:pointer; font-size:20px; text-decoration:none;"
               title="조직도 관리">🌳</a>
        </div>
    <?php endif; ?>

</header>
    <!-- 메인 컨테이너 -->
    <main class="container">
        <!-- 사이드바 -->
        <nav class="menu-sidebar" id="sidebar">
            <?php if ($is_country_page): ?>
                <?php
                    $region = $_GET['region'] ?? 'korea';
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $is_active = fn(string $p) => ($current_page === $p) ? 'active' : '';
                ?>

                <!-- ✅ 국가 선택 메뉴 (한국만 남김) -->
                <ul class="menu-list">
                    <li><a class="btn <?= $is_active('country_ready.php') ?>" href="country_ready.php?region=<?= htmlspecialchars($region) ?>">KOREA</a></li>

                    <!-- ✅ Korea 하위 메뉴 (KOREA ~ Logout 사이) -->
	                    <li>
	                        <a class="country-submenu <?= $is_active('country_ready.php') ?>" href="country_ready.php?region=<?= htmlspecialchars($region) ?>">Ready</a>
	                    </li>
	                    <li>
	                        <a class="country-submenu <?= $is_active('country_progressing.php') ?>" href="country_progressing.php?region=<?= htmlspecialchars($region) ?>">Progreccing</a>
	                    </li>
	                    <li>
	                        <a class="country-submenu <?= $is_active('country_completed.php') ?>" href="country_completed.php?region=<?= htmlspecialchars($region) ?>">C / L</a>
	                    </li>
	                    <li>
	                        <a class="country-submenu <?= $is_active('country_profit_share.php') ?>" href="country_profit_share.php?region=<?= htmlspecialchars($region) ?>">P / S</a>
	                    </li>
                </ul>

	                <!-- ✅ Korea 하위 메뉴는 "파란 버튼" 없이 글자(텍스트) 버튼 형태로 표시 -->
	                <style>
	                    .menu-sidebar .country-submenu {
	                        display: block;
	                        padding: 8px 12px 8px 22px; /* 들여쓰기 */
	                        background: none !important;
	                        border: none !important;
	                        color: inherit;
	                        text-decoration: none;
	                        font-size: 14px;
	                        line-height: 1.2;
	                    }
	                    .menu-sidebar .country-submenu:hover {
	                        text-decoration: underline;
	                    }
	                    .menu-sidebar .country-submenu.active {
	                        font-weight: 700;
	                        text-decoration: underline;
	                    }
	                </style>

                <!-- ✅ 로그아웃 버튼 추가 -->
                <div class="logout-box">
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>


            <?php else: ?>
                <!-- 역할별 메뉴 -->
                <h1>
                    <?php if ($user_role === 'gm'): ?>
                        <a href="gm_dashboard.php">글로벌 마스터 메인 화면</a>
                    <?php elseif ($user_role === 'admin'): ?>
                        <a href="admin_dashboard.php">어드민 메인 화면</a>
                    <?php elseif ($user_role === 'master'): ?>
                        <a href="master_dashboard.php">마스터 메인 화면</a>
                    <?php elseif ($user_role === 'agent'): ?>
                        <a href="agent_dashboard.php">에이전트 메인 화면</a>
                    <?php elseif ($user_role === 'investor'): ?>
                        <a href="investor_dashboard.php">투자자 메인 화면</a>
                    <?php endif; ?>
                </h1>
                <ul class="menu-list">
                    <?php if ($user_role === 'gm'): ?>
                        <li><a href="create_account.php">모든 계정 생성</a></li>
                        <li><a href="gm_list.php">글로벌 마스터 목록</a></li>
                        <li><a href="admin_list.php">관리자 목록</a></li>
                        <li><a href="master_list.php">마스터 목록</a></li>
                        <li><a href="agent_list.php">에이전트 목록</a></li>
                        <li><a href="investor_list.php">투자자 목록</a></li>
                        <li><a href="Partner_accounts.php">파트너 정산</a></li>
                        <li><a href="group_accounts.php">조직 정산</a></li>
                    <?php elseif ($user_role === 'admin'): ?>
                        <li><a href="admin_profile.php">내 정보 수정</a></li>
                        <li><a href="create_master.php">마스터 생성</a></li>
                        <li><a href="a_master_list.php">마스터 목록</a></li>
                        <li><a href="a_agent_list.php">에이전트 목록</a></li>
                        <li><a href="a_investor_list.php">투자자 목록</a></li>
                    <?php elseif ($user_role === 'master'): ?>
                        <li><a href="master_profile.php">내 정보 수정</a></li>
                        <li><a href="create_agent.php">에이전트 생성</a></li>
                        <li><a href="b_agent_list.php">에이전트 목록</a></li>
                        <li><a href="b_investor_list.php">투자자 목록</a></li>
                    <?php elseif ($user_role === 'agent'): ?>
                        <li><a href="create_account.php?mode=edit&id=<?= (int)$_SESSION['user_id'] ?>&redirect=c_investor_list.php">내 정보 수정</a></li>
                        <li><a href="c_investor_list.php">투자자 목록</a></li>
                        <li><a href="c_create_account.php">투자자 등록</a></li>
                    <?php elseif ($user_role === 'investor'): ?>
                        <li><a href="investor_referral_copy.php">레퍼럴복사</a></li>
                        <li><a href="investor_edit_broker.php?redirect=investor_dashboard.php">내 정보 수정</a></li>
                        <li><a href="investor_deposit.php?user_id=<?= $_SESSION['user_id'] ?>">입금</a></li>
                        <li><a href="investor_withdrawal.php?user_id=<?= $_SESSION['user_id'] ?>">출금</a></li>
                        <li><a href="investor_profit_share.php?user_id=<?= $_SESSION['user_id'] ?>">수익 배분</a></li>
                        <li><a href="profit_share.php?user_id=<?= $_SESSION['user_id'] ?>">거래 내역</a></li>
                        <li><a href="referral_list.php?user_id=<?= $_SESSION['user_id'] ?>">추천인 목록</a></li>
                        <li><a href="referral_settlement.php?user_id=<?= $_SESSION['user_id'] ?>">추천정산</a></li>
                    <?php endif; ?>
                </ul>
                <!-- ✅ 로그아웃 버튼 (왼쪽 하단 고정) -->
                <div class="logout-box">
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- 콘텐츠 영역 -->
        <section class="content-area">
            <?php
            if (isset($content_file) && file_exists($content_file)) {
                include $content_file;
            } else {
                echo "<p>콘텐츠 파일이 없습니다. (" . htmlspecialchars($content_file) . ")</p>";
            }
            ?>
        </section>
    </main>

    <!-- 푸터 -->
    <footer class="site-footer">© THEK-NEXT.COM. 모든 권리 보유.</footer>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>