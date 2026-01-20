<?php
// URL builder: keep current query and override keys
if (!function_exists('build_url')) {
    function build_url(array $override = []): string {
        $base = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
        $q = $_GET ?? [];
        foreach ($override as $k=>$v) { $q[$k] = $v; }
        return $base . (count($q) ? ('?' . http_build_query($q)) : '');
    }
}
// 모든 에러 표시 (디버깅용)
error_reporting(E_ALL);
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');

ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('I18N_LOADED')) {
    require_once __DIR__ . '/includes/i18n.php';
}
// role 값 확인
if (!isset($_SESSION['role'])) {
    error_log(t('error.login_required', 'Login required.'));
    header("Location: login.php");
    exit;
}
if (isset($page_title) && function_exists('t') && strpos($page_title, '.') !== false) {
    $page_title = t($page_title);
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
$page_title = $page_title ?? ($is_country_page ? "" : t('title.dashboard', 'Dashboard'));

// 본문 파일 지정
if (!isset($content_file) && !$is_country_page) {
    switch ($user_role) {
        case 'gm':
            $content_file = __DIR__ . "/gm_dashboard_content.php";
            $page_title = t('title.gm.dashboard','GM Dashboard');
            break;
        case 'admin':
            $content_file = __DIR__ . "/admin_dashboard.php";
            $page_title = t('title.admin.dashboard','Admin Dashboard');
            break;
        case 'master':
            $content_file = __DIR__ . "/master_dashboard.php";
            $page_title = t('title.master.dashboard','Master Dashboard');
            break;
        case 'agent':
            $content_file = __DIR__ . "/agent_dashboard.php";
            $page_title = t('title.agent.dashboard','Agent Dashboard');
            break;
        case 'investor':
        default:
            $content_file = __DIR__ . "/investor_dashboard.php";
            $page_title = t('title.investor.dashboard','Investor Dashboard');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(current_lang()) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title ?: "FX Global Master UI") ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- 반응형 -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
    <!-- Date picker (flatpickr) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <?php if (isset($is_country_page) && $is_country_page): ?>
    <!-- Country Accordion Menu CSS (with cache-busting version) -->
    <link rel="stylesheet" href="css/pages/country_menu.css?v=<?= filemtime(__DIR__ . '/css/pages/country_menu.css') ?>">
    <!-- Country Common Styles -->
    <link rel="stylesheet" href="css/pages/country_common.css?v=<?= filemtime(__DIR__ . '/css/pages/country_common.css') ?>">
    <!-- Country Filter Bar Styles -->
    <link rel="stylesheet" href="css/pages/country_filterbar.css?v=<?= filemtime(__DIR__ . '/css/pages/country_filterbar.css') ?>">
    <?php endif; ?>
    <style>
        /* JP standard: compact inline date input */
        input.date-picker{width:170px; padding:8px; border-radius:8px; border:1px solid #d6dbe3;}
    </style>
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
        } elseif (in_array($page, ['Partner_accounts_v2.php','partner_accounts_v2.php','group_accounts.php','profit_share.php','investor_profit_share.php'], true)) {
            $page_css = 'partner_accounts.css';
        } elseif (preg_match('/^(create_|edit_).+\.php$/', $page) || in_array($page, ['c_create_account.php'], true)) {
            $page_css = 'master_form.css';
        }
    }
?>
<?php if (!empty($page_css)): ?>
    <link rel="stylesheet" href="css/pages/<?= htmlspecialchars($page_css) ?>">
<?php endif; ?>
<style>
/* HEADER: title centered, right controls one-line */
.site-header{
  display:flex !important;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.site-header .header-left{flex:0 0 auto; display:flex; align-items:center; gap:10px; min-width:120px;}
.site-header .header-title{flex:1 1 auto; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:700;}
.site-header .header-right{flex:0 0 auto; display:flex; align-items:center; gap:10px; min-width:260px; justify-content:flex-end;}
/* Language dropdown */
.lang-dd{position:relative; display:inline-flex; align-items:center;}
.lang-dd-btn{
  display:inline-flex; align-items:center; gap:6px;
  padding:6px 10px; border-radius:10px;
  background:rgba(255,255,255,0.10);
  color:#fff; text-decoration:none; font-weight:600; font-size:13px;
  border:1px solid rgba(255,255,255,0.12);
  cursor:pointer;
}
.lang-dd-btn:hover{background:rgba(255,255,255,0.18);}
.lang-dd-menu{
  position:absolute; right:0; top:calc(100% + 8px);
  min-width:160px;
  background:#0f1b2e;
  border:1px solid rgba(255,255,255,0.12);
  border-radius:12px;
  box-shadow:0 10px 25px rgba(0,0,0,0.25);
  overflow:hidden;
  display:none;
  z-index:9999;
}
.lang-dd-menu a{
  display:flex; align-items:center; justify-content:space-between;
  padding:10px 12px;
  color:#fff; text-decoration:none; font-size:13px;
}
.lang-dd-menu a:hover{background:rgba(255,255,255,0.08);}
.lang-dd-menu a.active{background:rgba(255,255,255,0.14);}
.lang-dd.open .lang-dd-menu{display:block;}
/* Header icon buttons */
.header-icon{
  width:34px; height:34px;
  display:inline-flex; align-items:center; justify-content:center;
  border-radius:10px;
  background:rgba(255,255,255,0.10);
  border:1px solid rgba(255,255,255,0.12);
  color:#fff; text-decoration:none;
  font-size:16px;
}
.header-icon:hover{background:rgba(255,255,255,0.18);}
</style>
</head>
<body>
    <!-- 헤더 -->
<header class="site-header">
  <div class="header-left">
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
  </div>

  <div class="header-title">
    <span><?= htmlspecialchars($display_title ?? ($page_title ?? '')) ?></span>
  </div>

  <div class="header-right">
    <?php if (empty($is_country_page)): ?>
<div class="lang-dd" id="langDd">
  <?php $current_lang = ($lang ?? ($_GET['lang'] ?? 'ja')); ?>
  <button type="button" class="lang-dd-btn" onclick="toggleLangDd(event)">
    🌐 <span><?= $current_lang === 'ja' ? t('lang.ja') : ($current_lang === 'en' ? t('lang.en') : t('lang.ko')) ?></span>
    <span style="opacity:.8;">▾</span>
  </button>
  <div class="lang-dd-menu" role="menu" aria-label="Language">
    <a href="<?= htmlspecialchars(build_url(['lang'=>'ko'])) ?>" class="<?= $current_lang==='ko' ? 'active' : '' ?>"><?= t('lang.ko') ?> <span class="chk"><?= $current_lang==='ko' ? '✓' : '' ?></span></a>
    <a href="<?= htmlspecialchars(build_url(['lang'=>'ja'])) ?>" class="<?= $current_lang==='ja' ? 'active' : '' ?>"><?= t('lang.ja') ?> <span class="chk"><?= $current_lang==='ja' ? '✓' : '' ?></span></a>
    <a href="<?= htmlspecialchars(build_url(['lang'=>'en'])) ?>" class="<?= $current_lang==='en' ? 'active' : '' ?>"><?= t('lang.en') ?> <span class="chk"><?= $current_lang==='en' ? '✓' : '' ?></span></a>
  </div>
</div>
<script>
function toggleLangDd(e){
  e.stopPropagation();
  var dd=document.getElementById('langDd');
  if(!dd) return;
  dd.classList.toggle('open');
}
document.addEventListener('click', function(){
  var dd=document.getElementById('langDd');
  if(dd) dd.classList.remove('open');
});
document.addEventListener('keydown', function(e){
  if(e.key==='Escape'){
    var dd=document.getElementById('langDd');
    if(dd) dd.classList.remove('open');
  }
});
</script>
    <a class="header-icon" href="gm_revenue_report.php<?= isset($_GET['lang']) ? ('?lang=' . urlencode($_GET['lang'])) : '' ?>" title="Report">🖨</a>
<a class="header-icon" href="org_chart.php<?= isset($_GET['lang']) ? ('?lang=' . urlencode($_GET['lang'])) : '' ?>" title="Org Chart">🌳</a>
<?php endif; ?>
  </div>
</header>
    <!-- 메인 컨테이너 -->
    <main class="container">
        <!-- 사이드바 -->
        <nav class="menu-sidebar" id="sidebar">
            <?php if ($is_country_page): ?>
                <?php
                    // Country definitions (extensible)
                    $countries = [
                        'korea' => 'KOREA',
                        'japan' => 'JAPAN',
                        // Future additions:
                        // 'malaysia' => 'MALAYSIA',
                        // 'vietnam'  => 'VIETNAM',
                    ];

                    // Submenu definitions
                    $submenus = [
                        ['file' => 'country_ready.php',        'label' => 'Ready'],
                        ['file' => 'country_progressing.php',  'label' => 'Progressing'],
                        ['file' => 'country_completed.php',    'label' => 'C / L'],
                        ['file' => 'country_profit_share.php', 'label' => 'P / S'],
                    ];

                    // Region validation (whitelist)
                    $region = $_GET['region'] ?? 'korea';
                    $region = array_key_exists($region, $countries) ? $region : 'korea';
                    
                    $current_page = basename($_SERVER['PHP_SELF']);
                    $is_active = fn(string $page, string $target_region) => 
                        ($current_page === $page && $region === $target_region) ? 'active' : '';
                ?>

                <!-- Pass country regions to JavaScript -->
                <script>window.COUNTRY_REGIONS = <?= json_encode(array_keys($countries)) ?>;</script>

                <!-- Country Accordion Menu -->
                <ul class="menu-list">
                    <?php foreach ($countries as $code => $label): ?>
                    <li>
                        <div class="country-header" onclick="toggleCountry('<?= htmlspecialchars($code) ?>')">
                            <span id="icon-<?= htmlspecialchars($code) ?>"><?= $region === $code ? '▼' : '▶' ?></span> <?= htmlspecialchars($label) ?>
                        </div>
                        <ul class="country-submenu-list <?= $region === $code ? 'open' : '' ?>" id="submenu-<?= htmlspecialchars($code) ?>">
                            <?php foreach ($submenus as $menu): ?>
                            <li><a class="country-submenu <?= $is_active($menu['file'], $code) ?>" 
                                   href="<?= htmlspecialchars($menu['file']) ?>?region=<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($menu['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Logout Button -->
                <div class="logout-box">
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>


            <?php else: ?>
                <!-- 역할별 메뉴 -->
                <h1>
                    <?php
                    $home_map = [
                        'gm' => ['gm_dashboard.php', 'menu.gm.main_home', '글로벌 마스터 메인 화면'],
                        'admin' => ['admin_dashboard.php', 'menu.admin.main_home', '관리자 메인 화면'],
                        'master' => ['master_dashboard.php', 'menu.master.main_home', '마스터 메인 화면'],
                        'agent' => ['agent_dashboard.php', 'menu.agent.main_home', '에이전트 메인 화면'],
                        'investor' => ['investor_dashboard.php', 'menu.investor.main_home', '투자자 메인 화면'],
                    ];
                    if (isset($home_map[$user_role])) {
                        [$href, $key, $fallback] = $home_map[$user_role];
                        echo '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars(t($key, $fallback)) . '</a>';
                    }
                    ?>
                </h1>
                <ul class="menu-list">
                    <?php if ($user_role === 'gm'): ?>
                        <li><a href="create_account.php"><?= t('menu.gm.create_all', '모든 계정 생성') ?></a></li>
                        <li><a href="gm_list.php"><?= t('menu.gm.list', '글로벌 마스터 목록') ?></a></li>
                        <li><a href="admin_list.php"><?= t('menu.admin.list', '관리자 목록') ?></a></li>
                        <li><a href="master_list.php"><?= t('menu.master.list', '마스터 목록') ?></a></li>
                        <li><a href="agent_list.php"><?= t('menu.agent.list', '에이전트 목록') ?></a></li>
                        <li><a href="investor_list.php"><?= t('menu.investor.list', '투자자 목록') ?></a></li>
                        <li><a href="Partner_accounts_v2.php"><?= t('menu.partner_accounts', '파트너 정산') ?></a></li>
                        <li><a href="group_accounts.php"><?= t('menu.group_accounts', '조직 정산') ?></a></li>
                    <?php elseif ($user_role === 'admin'): ?>
                        <li><a href="admin_profile.php"><?= t('menu.profile', '내 정보 수정') ?></a></li>
                        <li><a href="create_master.php"><?= t('menu.admin.create_master', '마스터 생성') ?></a></li>
                        <li><a href="a_master_list.php"><?= t('menu.master.list', '마스터 목록') ?></a></li>
                        <li><a href="a_agent_list.php"><?= t('menu.agent.list', '에이전트 목록') ?></a></li>
                        <li><a href="a_investor_list.php"><?= t('menu.investor.list', '투자자 목록') ?></a></li>
                    <?php elseif ($user_role === 'master'): ?>
                        <li><a href="master_profile.php"><?= t('menu.profile', '내 정보 수정') ?></a></li>
                        <li><a href="create_agent.php"><?= t('menu.master.create_agent', '에이전트 생성') ?></a></li>
                        <li><a href="b_agent_list.php"><?= t('menu.agent.list', '에이전트 목록') ?></a></li>
                        <li><a href="b_investor_list.php"><?= t('menu.investor.list', '투자자 목록') ?></a></li>
                    <?php elseif ($user_role === 'agent'): ?>
                        <li><a href="create_account.php?mode=edit&id=<?= (int)$_SESSION['user_id'] ?>&redirect=c_investor_list.php"><?= t('menu.profile', '내 정보 수정') ?></a></li>
                        <li><a href="c_investor_list.php"><?= t('menu.investor.list', '투자자 목록') ?></a></li>
                        <li><a href="c_create_account.php"><?= t('menu.investor.register', '투자자 등록') ?></a></li>
                    <?php elseif ($user_role === 'investor'): ?>
                        <li><a href="investor_referral_copy.php"><?= t('menu.investor.referral_copy', '레퍼럴복사') ?></a></li>
                        <li><a href="investor_edit_broker.php?redirect=investor_dashboard.php"><?= t('menu.profile', '내 정보 수정') ?></a></li>
                        <li><a href="investor_deposit.php?user_id=<?= $_SESSION['user_id'] ?>"><?= t('menu.investor.deposit', '입금') ?></a></li>
                        <li><a href="investor_withdrawal.php?user_id=<?= $_SESSION['user_id'] ?>"><?= t('menu.investor.withdrawal', '출금') ?></a></li>
                        <li><a href="investor_profit_share.php?user_id=<?= $_SESSION['user_id'] ?>"><?= t('menu.investor.profit_share', '수익 배분') ?></a></li>
                        <li><a href="profit_share.php?user_id=<?= $_SESSION['user_id'] ?>"><?= t('menu.investor.trades', '거래 내역') ?></a></li>
                        <li><a href="referral_list.php?user_id=<?= $_SESSION['user_id'] ?>"><?= t('menu.investor.referrals', '추천인 목록') ?></a></li>
                        <li><a href="referral_settlement.php?user_id=<?= $_SESSION['user_id'] ?>"><?= t('menu.investor.referral_settle', '추천정산') ?></a></li>
                    <?php endif; ?>
                </ul>
                <!-- ✅ 로그아웃 버튼 (왼쪽 하단 고정) -->
                <div class="logout-box">
                    <a href="logout.php" class="logout-btn"><?= t('menu.logout', 'Logout') ?></a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- 콘텐츠 영역 -->
        <section class="content-area">
            <?php
            if (isset($content_file) && file_exists($content_file)) {
                include $content_file;
            } else {
                echo "<p><?= t('err.content_file_missing', '콘텐츠 파일이 없습니다.') ?> (" . htmlspecialchars($content_file) . ")</p>";
            }
            ?>
        </section>
    </main>

    <!-- 푸터 -->
    <footer class="site-footer"><?= t('footer.copyright', '© THEK-NEXT.COM. All rights reserved.') ?></footer>

    <!-- Date picker (flatpickr) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ko.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
    <script>
      (function(){
        const APP_LANG = "<?= htmlspecialchars(current_lang()) ?>";
        function initDatePickers(){
          if (typeof flatpickr !== 'function') return;
          document.querySelectorAll('input.date-picker').forEach(function(el){
            // Avoid double-init
            if (el._flatpickr) return;
            
            // Force English locale for country filter bar
            const isCountryFilter = el.closest('.country-filterbar');
            let localeObj = null;
            
            if (!isCountryFilter) {
              // Use language-specific locale for non-country pages
              if (APP_LANG === 'ko' && flatpickr.l10ns && flatpickr.l10ns.ko) localeObj = flatpickr.l10ns.ko;
              else if (APP_LANG === 'ja' && flatpickr.l10ns && flatpickr.l10ns.ja) localeObj = flatpickr.l10ns.ja;
            }
            // Country filter bar always uses English (default)
            
            flatpickr(el, {
              dateFormat: "Y-m-d",
              allowInput: true,
              locale: localeObj || "default"
            });
          });
        }
        document.addEventListener('DOMContentLoaded', initDatePickers);
      })();
    </script>
    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
    <?php if (isset($is_country_page) && $is_country_page): ?>
    <!-- Country Accordion Menu JS -->
    <script src="js/pages/country_menu.js"></script>
    <?php endif; ?>
</body>
</html>