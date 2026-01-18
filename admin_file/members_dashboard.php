<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

$page_title = '회원 대시보드';

// --------- helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --------- 1) small tree: company -> admin -> master
$admins = [];
$masters_by_admin = [];
try {
    $sqlA = "SELECT id, name, username, email FROM users WHERE LOWER(role)='admin' ORDER BY id ASC";
    $resA = $conn->query($sqlA);
    while ($resA && ($r = $resA->fetch_assoc())) {
        $aid = (int)$r['id'];
        $label = trim(($r['name'] ?? '') ?: ($r['username'] ?? '') ?: ($r['email'] ?? ''));
        if ($label === '') $label = 'ID '.$aid;
        $admins[$aid] = ['id'=>$aid,'label'=>$label,'email'=>$r['email'] ?? ''];
        $masters_by_admin[$aid] = [];
    }

    if ($admins) {
        $ids = implode(',', array_map('intval', array_keys($admins)));
        // Masters directly under admins
        $sqlM = "SELECT id, name, username, email, sponsor_id FROM users
                 WHERE LOWER(role)='master' AND sponsor_id IN ($ids)
                 ORDER BY sponsor_id ASC, id ASC";
        $resM = $conn->query($sqlM);
        while ($resM && ($r = $resM->fetch_assoc())) {
            $mid = (int)$r['id'];
            $aid = (int)$r['sponsor_id'];
            $label = trim(($r['name'] ?? '') ?: ($r['username'] ?? '') ?: ($r['email'] ?? ''));
            if ($label === '') $label = 'ID '.$mid;
            if (!isset($masters_by_admin[$aid])) $masters_by_admin[$aid] = [];
            $masters_by_admin[$aid][] = ['id'=>$mid,'label'=>$label,'email'=>$r['email'] ?? ''];
        }
    }
} catch (Throwable $e) {
    // ignore, show empty
}

// --------- 2) role counts chart
$role_counts = [];
try {
    $sql = "SELECT role, COUNT(*) AS c
            FROM users
            WHERE LOWER(role) NOT IN ('gm','superadmin','specialadmin')
            GROUP BY role
            ORDER BY c DESC";
    $res = $conn->query($sql);
    while ($res && ($r = $res->fetch_assoc())) {
        $role_counts[] = ['role'=>$r['role'], 'count'=>(int)$r['c']];
    }
} catch (Throwable $e) {}

// --------- 3) new users by admin (last 7 days)
$new_by_admin = [];
try {
    // Find nearest admin ancestor in sponsor chain using recursive CTE (MySQL 8+)
    $sql = "WITH RECURSIVE chain AS (
                SELECT u.id AS user_id, u.sponsor_id, u.id AS origin_id
                FROM users u
                WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND LOWER(u.role) NOT IN ('gm','superadmin','specialadmin')
            UNION ALL
                SELECT c.user_id, p.sponsor_id, c.origin_id
                FROM chain c
                JOIN users p ON p.id = c.sponsor_id
                WHERE c.sponsor_id IS NOT NULL
                  AND LOWER(p.role) NOT IN ('gm','superadmin','specialadmin')
                  AND LOWER(p.role) <> 'admin'
            ),
            admin_hit AS (
                SELECT c.origin_id, p.id AS admin_id
                FROM chain c
                JOIN users p ON p.id = c.sponsor_id
                WHERE LOWER(p.role) = 'admin'
            )
            SELECT a.id AS admin_id,
                   COALESCE(NULLIF(TRIM(a.name),''), NULLIF(TRIM(a.username),''), a.email, CONCAT('ID ',a.id)) AS admin_name,
                   COUNT(*) AS c
            FROM admin_hit ah
            JOIN users a ON a.id = ah.admin_id
            GROUP BY a.id, admin_name
            ORDER BY c DESC, a.id ASC";
    $res = $conn->query($sql);
    while ($res && ($r = $res->fetch_assoc())) {
        $new_by_admin[] = ['admin'=>$r['admin_name'], 'count'=>(int)$r['c']];
    }
} catch (Throwable $e) {
    // fallback: masters directly under admin in last 7 days
    try {
        $sql = "SELECT a.id AS admin_id,
                       COALESCE(NULLIF(TRIM(a.name),''), NULLIF(TRIM(a.username),''), a.email, CONCAT('ID ',a.id)) AS admin_name,
                       COUNT(u.id) AS c
                FROM users u
                JOIN users a ON a.id = u.sponsor_id
                WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND LOWER(a.role)='admin'
                  AND LOWER(u.role) NOT IN ('gm','superadmin','specialadmin')
                GROUP BY a.id, admin_name
                ORDER BY c DESC";
        $res = $conn->query($sql);
        while ($res && ($r = $res->fetch_assoc())) {
            $new_by_admin[] = ['admin'=>$r['admin_name'], 'count'=>(int)$r['c']];
        }
    } catch (Throwable $e2) {}
}

// --------- 4) compact user list
$users_list = [];
try {
    $sql = "SELECT id, name, username, email, phone, role, created_at
            FROM users
            WHERE LOWER(role) NOT IN ('gm','superadmin','specialadmin')
            ORDER BY created_at DESC, id DESC
            LIMIT 25";
    $res = $conn->query($sql);
    while ($res && ($r = $res->fetch_assoc())) {
        $users_list[] = $r;
    }
} catch (Throwable $e) {}

admin_render_header($page_title);
?>

<style>
  .dash-grid{display:grid; grid-template-columns: 420px 1fr; gap:14px; align-items:start}
  .mini-tree{max-height:520px; overflow:auto; padding-right:6px}
  .tree ul{margin:6px 0 6px 16px; padding:0}
  .tree li{margin:6px 0; list-style:disc}
  .tree .pill{display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; border:1px solid var(--border); background:#fff; color:var(--muted)}
  .tree a{color:inherit; text-decoration:none}
  .tree a:hover{text-decoration:underline}
  .charts{display:grid; grid-template-columns: 1fr 1fr; gap:14px}
  .chartbox{height:220px}
  .chartbox canvas{width:100% !important; height:100% !important;}
  .table-wrap{overflow:auto}
  table.tbl{width:100%; border-collapse:separate; border-spacing:0; font-size:13px}
  table.tbl th, table.tbl td{padding:10px 10px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap}
  table.tbl th{position:sticky; top:0; background:#fff; z-index:1}
  table.tbl tr:hover td{background:rgba(17,24,39,.03)}
  .sub{color:var(--muted); font-size:12px}
  @media (max-width: 1100px){ .dash-grid{grid-template-columns: 1fr;} .charts{grid-template-columns: 1fr;} }
</style>

<div class="dash-grid">

  <!-- Left: small org tree (company -> admin -> master) -->
  <div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
      <div>
        <div style="font-weight:900; font-size:15px;">회원 트리 (회사 → 마스터)</div>
        <div class="sub">회사 아래에서 <b>Admin</b>과 그 하위 <b>Master</b>까지만 요약 표시</div>
      </div>
      <a class="pill" href="member_tree.php" title="전체 트리 보기">전체 트리</a>
    </div>
    <div class="mini-tree tree" style="margin-top:10px;">
      <ul>
        <li>
          <span class="pill">🏢 회사</span>
          <ul>
            <?php if (!$admins): ?>
              <li class="sub">admin이 없습니다.</li>
            <?php else: foreach ($admins as $aid => $a): ?>
              <li>
                <span class="pill">🛡️ Admin</span>
                <a href="users.php?search=<?= urlencode((string)$a['email']) ?>" title="users에서 보기"><?= h($a['label']) ?></a>
                <span class="sub">(#<?= (int)$aid ?>)</span>
                <ul>
                  <?php $ms = $masters_by_admin[$aid] ?? []; if (!$ms): ?>
                    <li class="sub">(하위 master 없음)</li>
                  <?php else: foreach ($ms as $m): ?>
                    <li>
                      <span class="pill">👑 Master</span>
                      <a href="users.php?search=<?= urlencode((string)$m['email']) ?>"><?= h($m['label']) ?></a>
                      <span class="sub">(#<?= (int)$m['id'] ?>)</span>
                    </li>
                  <?php endforeach; endif; ?>
                </ul>
              </li>
            <?php endforeach; endif; ?>
          </ul>
        </li>
      </ul>
    </div>
  </div>

  <!-- Right: charts + list -->
  <div>
    <div class="charts">
      <div class="card">
        <div style="font-weight:900; font-size:15px;">파트별 회원수</div>
        <div class="sub" style="margin-top:4px;">role 기준 전체 분포</div>
        <div class="chartbox" style="margin-top:10px;"><canvas id="roleChart"></canvas></div>
      </div>

      <div class="card">
        <div style="font-weight:900; font-size:15px;">신규 회원 (Admin별 / 최근 7일)</div>
        <div class="sub" style="margin-top:4px;">스폰서 체인 기준 가장 가까운 admin에 귀속</div>
        <div class="chartbox" style="margin-top:10px;"><canvas id="newByAdminChart"></canvas></div>
      </div>
    </div>

    <div class="card" style="margin-top:14px;">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
        <div>
          <div style="font-weight:900; font-size:15px;">최근 가입 유저</div>
          <div class="sub">이름 / 이메일 / 전화번호만 간략 표시</div>
        </div>
        <a class="pill" href="users.php">전체 회원</a>
      </div>
      <div class="table-wrap" style="margin-top:10px;">
        <table class="tbl">
          <thead>
            <tr>
              <th>ID</th>
              <th>이름</th>
              <th>이메일</th>
              <th>전화번호</th>
              <th>Role</th>
              <th>가입일</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$users_list): ?>
              <tr><td colspan="6" class="sub">표시할 유저가 없습니다.</td></tr>
            <?php else: foreach ($users_list as $u):
                $label = trim(($u['name'] ?? '') ?: ($u['username'] ?? '') ?: ($u['email'] ?? ''));
                if ($label === '') $label = 'ID '.$u['id'];
            ?>
              <tr>
                <td>#<?= (int)$u['id'] ?></td>
                <td><?= h($label) ?></td>
                <td><?= h($u['email'] ?? '') ?></td>
                <td><?= h($u['phone'] ?? '') ?></td>
                <td><span class="pill" style="padding:4px 8px;"><?= h($u['role'] ?? '') ?></span></td>
                <td class="sub"><?= h($u['created_at'] ?? '') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const roleData = <?= json_encode($role_counts, JSON_UNESCAPED_UNICODE) ?>;
  const roleLabels = roleData.map(x => x.role);
  const roleCounts = roleData.map(x => x.count);

  const newByAdmin = <?= json_encode($new_by_admin, JSON_UNESCAPED_UNICODE) ?>;
  const newAdminLabels = newByAdmin.map(x => x.admin);
  const newAdminCounts = newByAdmin.map(x => x.count);

  // Role chart (doughnut)
  const rc = document.getElementById('roleChart');
  if (rc) {
    new Chart(rc, {
      type: 'doughnut',
      data: {
        labels: roleLabels,
        datasets: [{ data: roleCounts }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { enabled: true }
        }
      }
    });
  }

  // New users by admin (bar)
  const nc = document.getElementById('newByAdminChart');
  if (nc) {
    new Chart(nc, {
      type: 'bar',
      data: {
        labels: newAdminLabels,
        datasets: [{ label: '신규', data: newAdminCounts }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { ticks: { maxRotation: 0, autoSkip: true } }
        }
      }
    });
  }
</script>

<?php admin_render_footer(); ?>
