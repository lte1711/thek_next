<?php
// admin_layout.php
function admin_nav_items(): array {
    return [
        '대시보드' => [
            ['label' => '전체 대시보드', 'href' => 'index.php', 'icon' => '🏠'],
            ['label' => '회원 대시보드', 'href' => 'members_dashboard.php', 'icon' => '📌'],
            ['label' => '집계/정산 대시보드', 'href' => 'settlement_dashboard.php', 'icon' => '🧮'],
        ],
        '회원' => [
            ['label' => '회원보기', 'href' => 'member_tree.php', 'icon' => '🌳'],
            ['label' => 'users', 'href' => 'users.php', 'icon' => '👤'],
            ['label' => 'user_details', 'href' => 'user_details.php', 'icon' => '🧾'],
            ['label' => 'user_transactions', 'href' => 'user_transactions.php', 'icon' => '💳'],
            ['label' => 'user_rejects', 'href' => 'user_rejects.php', 'icon' => '⛔'],
            ['label' => 'investor_agent_map', 'href' => 'investor_agent_map.php', 'icon' => '🧩'],
        ],
        '집계/정산' => [
            ['label' => '거래 목록', 'href' => 'settlement_transactions.php', 'icon' => '📚'],
            ['label' => '집계/정산 대조', 'href' => 'settlement_reconcile.php', 'icon' => '🧾'],
            ['label' => 'gm_deposits', 'href' => 'gm_deposits.php', 'icon' => '💰'],
            ['label' => 'gm_sales_daily', 'href' => 'gm_sales_daily.php', 'icon' => '📈'],
            ['label' => 'admin_deposits_daily', 'href' => 'admin_deposits_daily.php', 'icon' => '🏦'],
            ['label' => 'admin_sales_daily', 'href' => 'admin_sales_daily.php', 'icon' => '📊'],
            ['label' => 'partner_deposits', 'href' => 'partner_deposits.php', 'icon' => '🤝'],
        ],
        '국가 운영' => [
            ['label' => 'korea_progressing', 'href' => 'korea_progressing.php', 'icon' => '🇰🇷'],
            ['label' => 'japan_progressing', 'href' => 'japan_progressing.php', 'icon' => '🇯🇵'],
            ['label' => 'usa_progressing', 'href' => 'usa_progressing.php', 'icon' => '🇺🇸'],
            ['label' => 'vietnam_progressing', 'href' => 'vietnam_progressing.php', 'icon' => '🇻🇳'],
            ['label' => 'cambodia_progressing', 'href' => 'cambodia_progressing.php', 'icon' => '🇰🇭'],
        ],
        '보안/로그' => [
            ['label' => 'audit_logs', 'href' => 'audit_logs.php', 'icon' => '🧾'],
        ],
    ];
}

function admin_render_header(string $title): void {
    $active = basename($_SERVER['PHP_SELF'] ?? '');
    $nav = admin_nav_items();
    ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
/*
  ⚠️ 가독성 우선 "라이트 테마 고정" 버전
  - OS 다크모드(prefs-color-scheme: dark) 영향 제거
  - 배경/글자 대비를 강하게
  - 과한 그림자/여백 줄여서 덜 복잡하게
*/
:root{
  --bg:#f7f8fb;
  --card:#ffffff;
  --text:#111827;
  --muted:#6b7280;
  --border:rgba(17,24,39,.12);
  --shadow:0 4px 14px rgba(17,24,39,.06);
  --primary:#2563eb;
  --primary-ink:#ffffff;
  --danger:#ef4444;

  /* 사이드바도 밝게 */
  --sidebar:#ffffff;
  --sidebar-text:#111827;
  --sidebar-muted:#6b7280;
  --sidebar-active:rgba(37,99,235,.10);

  --radius:12px;
  --pad:14px;
  --font:system-ui,-apple-system,Segoe UI,Roboto,Apple SD Gothic Neo,Noto Sans KR,Helvetica,Arial,sans-serif;
  --mono:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
}

*{box-sizing:border-box}
html,body{height:100%}
body{
  margin:0;
  font-family:var(--font);
  background:var(--bg);
  color:var(--text);
  line-height:1.45;
  font-size:14px;
}
a{color:inherit}
.shell{
  min-height:100vh;
  display:grid;
  grid-template-columns: 264px 1fr;
}
.sidebar{
  background:var(--sidebar);
  color:var(--sidebar-text);
  padding:14px 12px;
  position:sticky;
  top:0;
  height:100vh;
  overflow:auto;
  border-right:1px solid var(--border);
}
.brand{
  display:flex;
  gap:10px;
  align-items:center;
  padding:10px 10px 14px;
}
.brand .logo{
  width:34px;height:34px;border-radius:10px;
  display:grid;place-items:center;
  background:rgba(37,99,235,.10);
  box-shadow: inset 0 0 0 1px rgba(37,99,235,.12);
  font-weight:800;
}
.brand .title{font-weight:800; letter-spacing:.2px}
.brand .sub{font-size:12px; color:var(--sidebar-muted); margin-top:2px}

.nav-group{margin:10px 0 14px}
.nav-group .group-title{
  font-size:11px;
  letter-spacing:.12em;
  text-transform:uppercase;
  color:var(--sidebar-muted);
  padding:10px 10px 6px;
}
.nav a{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:10px 10px;
  border-radius:12px;
  text-decoration:none;
  color:var(--sidebar-text);
  border:1px solid transparent;
}
.nav a:hover{background:rgba(17,24,39,.04); border-color:rgba(17,24,39,.06)}
.nav a.active{background:var(--sidebar-active); border-color:rgba(37,99,235,.22)}
.nav .left{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
}
.nav .icon{width:18px; text-align:center}
.nav .label{
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

/* Accordion menu */
.acc-section{margin:8px 0}
.acc-btn{
  width:100%;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:10px 10px;
  border-radius:12px;
  border:1px solid transparent;
  background:transparent;
  color:var(--sidebar-text);
  cursor:pointer;
  font-weight:800;
}
.acc-btn:hover{background:rgba(17,24,39,.04); border-color:rgba(17,24,39,.06)}
.acc-btn .chev{font-size:12px;color:var(--sidebar-muted)}
.acc-panel{padding:4px 0 0 0; display:none}
.acc-section.open .acc-panel{display:block}
.acc-section.open .acc-btn{background:rgba(37,99,235,.08); border-color:rgba(37,99,235,.18)}

.co{
  padding:18px 18px 40px;
}
.top{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
}
.h1{
  font-size:20px;
  font-weight:850;
  letter-spacing:-.02em;
  margin:0;
}
.meta{
  margin-top:6px;
  font-size:12px;
  color:var(--muted);
}
.actions{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap;
  justify-content:flex-end;
}
.pill{
  border:1px solid var(--border);
  background:#ffffff;
  padding:8px 10px;
  border-radius:999px;
  font-size:12px;
  color:var(--muted);
}

.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:var(--pad);
}
.card + .card{margin-top:14px}

hr.sep{
  border:0;
  border-top:1px solid var(--border);
  margin:14px 0;
}

/* Forms */
input,select,textarea{
  font:inherit;
  color:inherit;
  background:transparent;
  border:1px solid var(--border);
  border-radius:12px;
  padding:10px 12px;
  outline:none;
}
select{padding-right:34px}
input:focus,select:focus,textarea:focus{border-color:rgba(37,99,235,.55); box-shadow:0 0 0 3px rgba(37,99,235,.12)}
label{font-size:12px;color:var(--muted)}
.form-row{display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end}
.form-row > *{min-width:160px}

/* Buttons */
button,.btn{
  font:inherit;
  cursor:pointer;
  border-radius:12px;
  padding:10px 12px;
  border:1px solid var(--border);
  background:rgba(255,255,255,.65);
}
/* 라이트 고정: 다크모드 분기 제거 */
.btn-primary{
  background:var(--primary);
  border-color:rgba(37,99,235,.65);
  color:var(--primary-ink);
}
.btn-danger{
  background:rgba(239,68,68,.12);
  border-color:rgba(239,68,68,.35);
  color:var(--danger);
}

/* Tables (global) */
.table-wrap{overflow:auto; border-radius:14px; border:1px solid var(--border)}
table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  background:transparent;
}
thead th{
  position:sticky;
  top:0;
  background:var(--card);
  z-index:1;
  font-size:12px;
  color:var(--muted);
  text-align:left;
  padding:10px 12px;
  border-bottom:1px solid var(--border);
  white-space:nowrap;
}
tbody td{
  padding:10px 12px;
  border-bottom:1px solid var(--border);
  vertical-align:top;
}
tbody tr:nth-child(even){background:rgba(17,24,39,.02)}
tbody tr:hover{background:rgba(37,99,235,.06)}
td.num, th.num{text-align:right; font-variant-numeric:tabular-nums}
code, .mono{font-family:var(--mono); font-size:12px}

.notice{
  border:1px solid rgba(37,99,235,.25);
  background:rgba(37,99,235,.06);
  border-radius:14px;
  padding:12px 14px;
  color:var(--text);
}
.notice small{color:var(--muted)}
@media (max-width: 980px){
  .shell{grid-template-columns:1fr}
  .sidebar{position:relative;height:auto}
}
</style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="logo">🛡️</div>
            <div>
                <div class="t1">THEK 관리자</div>
                <div class="t2">Admin Dashboard</div>
            </div>
        </div>

        
        <?php
          // 아코디언: 현재 페이지가 속한 그룹은 기본 open
          $open_map = [];
          foreach ($nav as $g => $items0) {
              foreach ($items0 as $it0) {
                  if ($active === $it0['href']) { $open_map[$g] = true; break; }
              }
          }
        ?>
        <nav class="nav" aria-label="sidebar">
        <?php foreach ($nav as $group => $items): 
              $isOpen = !empty($open_map[$group]);
              $gid = 'g_' . preg_replace('/[^a-z0-9_]+/i', '_', $group);
        ?>
          <div class="acc-section <?= $isOpen ? 'open' : '' ?>" data-group="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>">
            <button type="button" class="acc-btn">
              <span><?= htmlspecialchars($group, ENT_QUOTES, 'UTF-8') ?></span>
              <span class="chev">▾</span>
            </button>
            <div class="acc-panel">
              <?php foreach ($items as $it): 
                    $isActive = ($active === $it['href']);
              ?>
                <a class="<?= $isActive ? 'active' : '' ?>" href="<?= htmlspecialchars($it['href'], ENT_QUOTES, 'UTF-8') ?>">
                  <span class="left">
                    <span class="icon"><?= $it['icon'] ?></span>
                    <span class="label"><?= htmlspecialchars($it['label'], ENT_QUOTES, 'UTF-8') ?></span>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        </nav>

        <script>
        (function(){
          // 아코디언: 하나를 열면 나머지는 자동으로 닫힘(단일 오픈)
          const KEY='thek_admin_nav_open_single_v1';
          const getSaved = ()=>{ try{return localStorage.getItem(KEY)||''}catch(e){return ''} };
          const setSaved = (v)=>{ try{ localStorage.setItem(KEY, v||'') }catch(e){} };

          const sections = Array.from(document.querySelectorAll('.acc-section'));
          const closeAll = (except)=>{
            sections.forEach(s=>{
              if (s === except) return;
              s.classList.remove('open');
              const c = s.querySelector('.chev');
              if (c) c.textContent = '▸';
            });
          };

          // restore
          const savedGid = getSaved();
          if (savedGid) {
            const target = sections.find(s => s.getAttribute('data-group') === savedGid);
            if (target) {
              closeAll(target);
              target.classList.add('open');
            }
          }

          sections.forEach(sec=>{
            const gid = sec.getAttribute('data-group') || '';
            const btn = sec.querySelector('.acc-btn');
            const chev = sec.querySelector('.chev');
            if (chev) chev.textContent = sec.classList.contains('open') ? '▾' : '▸';

            btn && btn.addEventListener('click', ()=>{
              const willOpen = !sec.classList.contains('open');
              if (willOpen) {
                closeAll(sec);
                sec.classList.add('open');
                if (chev) chev.textContent = '▾';
                setSaved(gid);
              } else {
                sec.classList.remove('open');
                if (chev) chev.textContent = '▸';
                setSaved('');
              }
            });
          });
        })();
        </script>

        <div style="margin-top:16px" class="notice">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
                <span>세션 로그인 유지</span>
                <span class="tag">ON</span>
            </div>
            <div style="margin-top:6px;font-size:12px;color:var(--muted)">
                필요 시 <b>edit_Thek_pm.php</b>로 재로그인
            </div>
        </div>
    </aside>

    <main class="content">
        <div class="topbar">
            <div class="titlebox">
                <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <p>데이터 관리 · 일괄 작업은 삭제 요약 확인 후 진행하세요</p>
            </div>
            <div class="actions">
                <span class="pill"><?= date('Y-m-d H:i') ?></span>
                <a class="btnlink" href="index.php">대시보드</a>
                <form method="POST" action="logout.php" style="display:inline">
                    <?= csrf_input() ?>
                    <button type="submit" class="btnlink" style="background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.35)">로그아웃</button>
                </form>
            </div>
        </div>
<?php
}

function admin_render_footer(): void {
    ?>
    </main>
</div>
</body>
</html>
<?php } ?>
