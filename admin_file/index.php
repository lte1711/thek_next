<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

/**
 * 작은 헬퍼들
 */
function has_column(mysqli $conn, string $table, string $col): bool {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->bind_param("s", $col);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = ($res && $res->num_rows > 0);
        $stmt->close();
        return $ok;
    } catch (Throwable $e) { return false; }
}

function safe_scalar(mysqli $conn, string $sql, string $types = "", array $params = []): ?float {
    try {
        $stmt = $conn->prepare($sql);
        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_row() : null;
        $stmt->close();
        if (!$row) return null;
        return is_numeric($row[0]) ? (float)$row[0] : null;
    } catch (Throwable $e) { return null; }
}

function safe_kv(mysqli $conn, string $sql, string $types = "", array $params = []): array {
    // 첫 번째 컬럼=key, 두 번째 컬럼=value 형태로 반환
    $out = [];
    try {
        $stmt = $conn->prepare($sql);
        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_row()) {
                if ($row && isset($row[0])) {
                    $out[(string)$row[0]] = isset($row[1]) && is_numeric($row[1]) ? (float)$row[1] : (float)($row[1] ?? 0);
                }
            }
        }
        $stmt->close();
    } catch (Throwable $e) {
        return [];
    }
    return $out;
}

function clamp_date(string $s, string $fallback): string {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
    return $fallback;
}

$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$range = $_GET['range'] ?? 'today'; // today | month | custom
$start = $today;
$end = $today;

if ($range === 'month') {
    $start = $month_start;
    $end = $month_end;
} elseif ($range === 'custom') {
    $start = clamp_date($_GET['start'] ?? $today, $today);
    $end   = clamp_date($_GET['end'] ?? $today, $today);
    if ($start > $end) { $tmp=$start; $start=$end; $end=$tmp; }
}

$queue = $_GET['queue'] ?? 'withdrawal_pending'; // withdrawal_pending | deposit_pending
if (!in_array($queue, ['withdrawal_pending','deposit_pending'], true)) $queue = 'withdrawal_pending';

admin_render_header('관리자 대시보드');
?>

<?php
// flash (퀵처리 결과)
if (!empty($_SESSION['flash_success'])) {
    echo '<div class="notice" style="border-left:4px solid #22c55e;margin-bottom:12px">'.htmlspecialchars($_SESSION['flash_success'],ENT_QUOTES,'UTF-8').'</div>';
    unset($_SESSION['flash_success']);
}
if (!empty($_SESSION['flash_error'])) {
    echo '<div class="notice" style="border-left:4px solid #ef4444;margin-bottom:12px">'.htmlspecialchars($_SESSION['flash_error'],ENT_QUOTES,'UTF-8').'</div>';
    unset($_SESSION['flash_error']);
}
?>

<?php
// =========================
// KPI/차트 데이터 준비 (출력 전에 먼저 계산)
// =========================
$total_users = safe_scalar($conn, "SELECT COUNT(*) FROM users") ?? 0;

$users_created = null;
if (has_column($conn, 'users', 'created_at')) {
    $users_created = safe_scalar(
        $conn,
        "SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?",
        "ss",
        [$start, $end]
    );
}

$tx_cnt = safe_scalar(
    $conn,
    "SELECT COUNT(*) FROM user_transactions WHERE tx_date BETWEEN ? AND ?",
    "ss",
    [$start, $end]
) ?? 0;

// 작업/경고 카운트
$withdrawal_pending_cnt = safe_scalar(
    $conn,
    "SELECT COUNT(*) FROM user_transactions
     WHERE tx_date BETWEEN ? AND ?
       AND deposit_status > 0
       AND withdrawal_status = 0",
    "ss",
    [$start, $end]
) ?? 0;

$deposit_pending_cnt = safe_scalar(
    $conn,
    "SELECT COUNT(*) FROM user_transactions
     WHERE tx_date BETWEEN ? AND ?
       AND (deposit_status IS NULL OR deposit_status = 0)",
    "ss",
    [$start, $end]
) ?? 0;

$alarm_total = (int)$withdrawal_pending_cnt + (int)$deposit_pending_cnt;

// 1) 입금/매출 추이 (최근 8주, 주간 단위)
$week_labels = [];
$week_keys = [];

// 이번 주 월요일(ISO 주 시작)
$wk_end = new DateTime($today);
$wk_end->modify('monday this week');

// 최근 8주(이번주 포함): 8개의 주 라벨 생성
$wk_start = (clone $wk_end)->modify('-7 week');

$iter = clone $wk_start;
for ($i=0; $i<8; $i++) {
    $week_key = $iter->format('o-\WW'); // 예: 2025-W52
    $week_keys[] = $week_key;

    $w_start = clone $iter;
    $w_end = (clone $iter)->modify('+6 day');
    $week_labels[] = $w_start->format('m/d') . '~' . $w_end->format('m/d');

    $iter->modify('+1 week');
}

$chart_start = $wk_start->format('Y-m-d');
$chart_end   = (clone $wk_end)->modify('+6 day')->format('Y-m-d');

// MySQL: ISO 주 키(%x-%v)로 그룹핑
$dep_map = safe_kv(
    $conn,
    "SELECT DATE_FORMAT(deposit_date, '%x-\\W%v') AS wk, SUM(deposit_amount)
     FROM admin_deposits_daily
     WHERE deposit_date BETWEEN ? AND ?
     GROUP BY wk",
    "ss",
    [$chart_start, $chart_end]
);

$admin_sales_map = safe_kv(
    $conn,
    "SELECT DATE_FORMAT(sales_date, '%x-\\W%v') AS wk, SUM(sales_amount)
     FROM admin_sales_daily
     WHERE sales_date BETWEEN ? AND ?
     GROUP BY wk",
    "ss",
    [$chart_start, $chart_end]
);

$gm_sales_map = safe_kv(
    $conn,
    "SELECT DATE_FORMAT(sales_date, '%x-\\W%v') AS wk, SUM(sales_amount)
     FROM gm_sales_daily
     WHERE sales_date BETWEEN ? AND ?
     GROUP BY wk",
    "ss",
    [$chart_start, $chart_end]
);

$chart_deposits = [];
$chart_admin_sales = [];
$chart_gm_sales = [];
foreach ($week_keys as $wk) {
    $chart_deposits[] = (float)($dep_map[$wk] ?? 0);
    $chart_admin_sales[] = (float)($admin_sales_map[$wk] ?? 0);
    $chart_gm_sales[] = (float)($gm_sales_map[$wk] ?? 0);
}
// 2) 국가별 진행 건수 (현재 선택한 기간 기준)
$country_tables = [
    'korea_progressing'    => '한국',
    'japan_progressing'    => '일본',
    'usa_progressing'      => '미국',
    'vietnam_progressing'  => '베트남',
    'cambodia_progressing' => '캄보디아',
];
$country_labels = [];
$country_counts = [];
foreach ($country_tables as $tbl => $label) {
    $country_labels[] = $label;
    $cnt = safe_scalar($conn, "SELECT COUNT(*) FROM {$tbl} WHERE tx_date BETWEEN ? AND ?", "ss", [$start, $end]);
    $country_counts[] = (int)($cnt ?? 0);
}

// 3) 회원 Role 분포
$role_map = safe_kv($conn, "SELECT role, COUNT(*) FROM users GROUP BY role");
$role_labels = array_keys($role_map);
$role_counts = array_map(fn($v) => (int)$v, array_values($role_map));
?>


<!-- 대시보드: 오밀조밀(한눈에) 레이아웃 -->
<style>
  .dash-wrap{width:100%;max-width:none;margin:0}
  .dash-top{display:grid;grid-template-columns: 360px 1fr;gap:12px;align-items:start}
  @media(max-width:980px){.dash-top{grid-template-columns:1fr}}
  .dash-card{padding:12px}
  .dash-title{font-weight:850;font-size:13px;margin:0}
  .dash-sub{font-size:11px;color:var(--muted);margin-top:4px}
  .filter-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
  .filter-row > *{min-width:140px}
  .kpis{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin-top:10px}
  @media(max-width:980px){.kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
  .kpi{padding:10px;border:1px solid var(--border);border-radius:12px;background:var(--card)}
  .kpi .k{font-size:11px;color:var(--muted)}
  .kpi .v{font-size:16px;font-weight:850;margin-top:2px}
  .status{margin-top:10px}
  .quick{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
  .quick a{display:inline-flex;align-items:center;gap:6px;padding:8px 10px;border-radius:10px;border:1px solid var(--border);background:#fff;text-decoration:none;font-size:12px}
  .quick a:hover{background:rgba(17,24,39,.03)}
  .chart-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
  @media(max-width:1200px){.chart-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(max-width:680px){.chart-grid{grid-template-columns:1fr}}
  .chart-card{padding:10px}
  .chart-card .t{font-weight:850;font-size:12px}
  .chart-card .s{font-size:11px;color:var(--muted);margin-top:2px}
  .chart-box{height:150px;margin-top:6px}
</style>

<div class="dash-wrap">
<div class="card dash-card">
  <div class="dash-top">
    <div>
      <div class="dash-title">기간/필터</div>
      <div class="dash-sub"><?= htmlspecialchars($start,ENT_QUOTES,'UTF-8') ?> ~ <?= htmlspecialchars($end,ENT_QUOTES,'UTF-8') ?> (KPI/작업 큐 기준)</div>

      <form method="GET" class="filter-row">
        <input type="hidden" name="queue" value="<?= htmlspecialchars($queue, ENT_QUOTES, 'UTF-8') ?>">
        <select name="range" style="max-width:160px">
          <option value="today" <?= $range==='today'?'selected':'' ?>>오늘</option>
          <option value="month" <?= $range==='month'?'selected':'' ?>>이번달</option>
          <option value="custom" <?= $range==='custom'?'selected':'' ?>>사용자 지정</option>
        </select>
        <input type="date" name="start" value="<?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?>" style="max-width:160px">
        <input type="date" name="end" value="<?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?>" style="max-width:160px">
        <button class="btn btn-primary" type="submit" style="padding:10px 14px">적용</button>
        <a class="btn" href="index.php" style="text-decoration:none;padding:10px 14px">초기화</a>
      </form>

      <div class="dash-title" style="margin-top:12px">빠른 이동</div>
      <div class="quick">
        <a href="users.php">👤 회원</a>
        <a href="user_transactions.php">💳 거래</a>
        <a href="admin_deposits_daily.php">🏦 입금</a>
        <a href="admin_sales_daily.php">📊 매출</a>
        <a href="audit_logs.php">🧾 감사로그</a>
      </div>
    </div>

    <div>
      <div class="dash-title">핵심 지표</div>
      <div class="kpis">
        <div class="kpi"><div class="k">총 회원</div><div class="v"><?= number_format((int)$total_users) ?></div></div>
        <div class="kpi"><div class="k">거래(기간)</div><div class="v"><?= number_format((int)$tx_cnt) ?></div></div>
        <div class="kpi"><div class="k">미출금</div><div class="v"><?= number_format((int)$withdrawal_pending_cnt) ?></div></div>
        <div class="kpi"><div class="k">미입금</div><div class="v"><?= number_format((int)$deposit_pending_cnt) ?></div></div>
        <div class="kpi"><div class="k">신규회원</div><div class="v">
          <?php if ($users_created !== null): ?><?= number_format((int)$users_created) ?><?php else: ?><span style="color:var(--muted)">-</span><?php endif; ?>
        </div></div>
      </div>

      <div class="status">
        <?php if ($alarm_total > 0): ?>
          <div class="notice" style="border-left:4px solid #ef4444;margin:0">
            <b>처리 필요</b> : 미출금 <?= number_format((int)$withdrawal_pending_cnt) ?>건 / 미입금 <?= number_format((int)$deposit_pending_cnt) ?>건
          </div>
        <?php else: ?>
          <div class="notice" style="border-left:4px solid #22c55e;margin:0"><b>정상</b> : 선택한 기간에 처리 필요 건이 없습니다.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div style="height:10px"></div>

<div class="chart-grid">
  <div class="card chart-card">
    <div class="t">입금(최근 8주, 주간)</div>
    <div class="s"><?= htmlspecialchars($chart_start,ENT_QUOTES,'UTF-8') ?> ~ <?= htmlspecialchars($chart_end,ENT_QUOTES,'UTF-8') ?></div>
    <div class="chart-box"><canvas id="chartDeposits"></canvas></div>
  </div>

  <div class="card chart-card">
    <div class="t">매출(최근 8주, 주간) · Admin vs GM</div>
    <div class="s"><?= htmlspecialchars($chart_start,ENT_QUOTES,'UTF-8') ?> ~ <?= htmlspecialchars($chart_end,ENT_QUOTES,'UTF-8') ?></div>
    <div class="chart-box"><canvas id="chartSales"></canvas></div>
  </div>

  <div class="card chart-card">
    <div class="t">국가 진행(선택 기간)</div>
    <div class="s"><?= htmlspecialchars($start,ENT_QUOTES,'UTF-8') ?> ~ <?= htmlspecialchars($end,ENT_QUOTES,'UTF-8') ?></div>
    <div class="chart-box"><canvas id="chartCountries"></canvas></div>
  </div>

  <div class="card chart-card">
    <div class="t">Role 분포</div>
    <div class="s">총 <?= number_format((int)$total_users) ?>명</div>
    <div class="chart-box"><canvas id="chartRoles"></canvas></div>
  </div>
</div>

</div><!-- /.dash-wrap -->
<?php
// 작업 큐 목록
$queue_where = "";
if ($queue === 'withdrawal_pending') {
    $queue_where = "deposit_status > 0 AND withdrawal_status = 0";
} else {
    $queue_where = "(deposit_status IS NULL OR deposit_status = 0)";
}

$queue_sql = "
SELECT ut.id, ut.user_id, ut.tx_date, ut.pair, ut.deposit_status, ut.withdrawal_status, ut.profit_loss, ut.code_value,
       u.name AS user_name, u.username AS user_username
FROM user_transactions ut
LEFT JOIN users u ON u.id = ut.user_id
WHERE ut.tx_date BETWEEN ? AND ?
  AND {$queue_where}
ORDER BY ut.tx_date ASC, ut.id ASC
LIMIT 20
";

$queue_rows = [];
try {
    $stmt = $conn->prepare($queue_sql);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $queue_rows[] = $r;
    $stmt->close();
} catch (Throwable $e) {
    $queue_rows = [];
}
?>

<details class="card" style="padding:0" <?= ($alarm_total > 0) ? 'open' : '' ?>>
  <summary style="list-style:none;cursor:pointer;padding:14px">
  <div class="row" style="justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap">
    <div>
      <div class="h">작업 큐 <span class="pill" style="margin-left:8px"><?= number_format((int)count($queue_rows)) ?> / 20</span></div>
      <div class="small" style="color:var(--muted);margin-top:6px">
        “처리 필요” 건을 빠르게 모아보는 리스트 (최대 20건, 오래된 순)
      </div>
    </div>

    <form method="GET" class="row" style="flex-wrap:wrap">
    <?= csrf_input() ?>

      <input type="hidden" name="range" value="<?= htmlspecialchars($range, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="start" value="<?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="end" value="<?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?>">

      <select name="queue" class="input" style="max-width:210px" onchange="this.form.submit()">
        <option value="withdrawal_pending" <?= $queue==='withdrawal_pending'?'selected':'' ?>>미출금 작업</option>
        <option value="deposit_pending" <?= $queue==='deposit_pending'?'selected':'' ?>>미입금 작업</option>
      </select>

      <a class="btn ghost" href="user_transactions.php?filter=<?= $queue==='withdrawal_pending'?'withdrawal_pending':'deposit_pending' ?>&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>">
        전체 보기 →
      </a>
    </form>
  </div>

  </summary>

  <div style="padding:0 14px 14px 14px">
  <div style="height:8px"></div>

  <?php if (empty($queue_rows)): ?>
    <div class="notice ok"><b>좋아요.</b> 선택한 조건의 작업 큐가 비어 있습니다.</div>
  <?php else: ?>
    <div style="overflow:auto">
      <table class="table">
        <thead>
          <tr>
            <th style="min-width:70px">TX ID</th>
            <th style="min-width:90px">회원</th>
            <th style="min-width:110px">거래일</th>
            <th style="min-width:90px">Pair</th>
            <th style="min-width:90px">입금</th>
            <th style="min-width:90px">출금</th>
            <th style="min-width:110px">손익</th>
            <th style="min-width:140px">코드</th>
            <th style="min-width:90px">바로가기</th>
            <th style="min-width:140px">처리</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($queue_rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <div style="display:flex;flex-direction:column;gap:2px">
                  <b><?= htmlspecialchars(($r['user_name'] ?: 'User#'.$r['user_id']), ENT_QUOTES, 'UTF-8') ?></b>
                  <span class="small" style="color:var(--muted)">ID: <?= htmlspecialchars((string)$r['user_id'], ENT_QUOTES, 'UTF-8') ?> <?= $r['user_username'] ? '· @'.htmlspecialchars($r['user_username'],ENT_QUOTES,'UTF-8') : '' ?></span>
                </div>
              </td>
              <td><?= htmlspecialchars($r['tx_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($r['pair'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($r['deposit_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($r['withdrawal_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($r['profit_loss'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($r['code_value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <a class="btn ghost" href="user_transactions.php?focus_id=<?= urlencode((string)$r['id']) ?>&filter=<?= urlencode($queue === 'deposit_pending' ? 'deposit_pending' : 'withdrawal_pending') ?>&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>">
                  열기
                </a>
              </td>
              <td>
                <?php $confirmMsg = ($queue === 'deposit_pending') ? '입금완료' : '출금완료'; ?>
                <form method="POST" action="user_transactions.php" style="display:flex;gap:6px;flex-wrap:wrap"
                      onsubmit="return confirm('선택한 거래를 <?= $confirmMsg ?> 처리할까요?\n(안전모드: user_transactions만 업데이트됩니다)');">
                  <?= csrf_input() ?>
                  <input type="hidden" name="action" value="quick_mark">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="mark" value="<?= ($queue === 'deposit_pending') ? 'deposit_done' : 'withdrawal_done' ?>">
                  <input type="hidden" name="return" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
                  <?php if ($queue === 'deposit_pending'): ?>
                    <button type="submit" class="btn small">입금완료</button>
                  <?php else: ?>
                    <button type="submit" class="btn small">출금완료</button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
</details>

<!-- 하단 안내는 과밀해 보여서 제거 (필요하면 별도 페이지로 분리 가능) -->

<!-- 본문 끝 -->
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // PHP → JS 데이터
  const labelsW = <?= json_encode($week_labels, JSON_UNESCAPED_UNICODE) ?>;
  const depositsW = <?= json_encode($chart_deposits, JSON_UNESCAPED_UNICODE) ?>;
  const adminSalesW = <?= json_encode($chart_admin_sales, JSON_UNESCAPED_UNICODE) ?>;
  const gmSalesW = <?= json_encode($chart_gm_sales, JSON_UNESCAPED_UNICODE) ?>;

  const countryLabels = <?= json_encode($country_labels, JSON_UNESCAPED_UNICODE) ?>;
  const countryCounts = <?= json_encode($country_counts, JSON_UNESCAPED_UNICODE) ?>;

  const roleLabels = <?= json_encode($role_labels, JSON_UNESCAPED_UNICODE) ?>;
  const roleCounts = <?= json_encode($role_counts, JSON_UNESCAPED_UNICODE) ?>;

  // 1) 입금 추이
  const ctxDeposits = document.getElementById('chartDeposits');
  if (ctxDeposits) {
    new Chart(ctxDeposits, {
      type: 'line',
      data: {
        labels: labelsW,
        datasets: [{
          label: '총 입금(USDT)',
          data: depositsW,
          tension: 0.25
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: true } },
        scales: {
          x: { ticks: { maxTicksLimit: 8 } },
          y: { beginAtZero: true }
        }
      }
    });
  }

  // 2) 매출 추이 (Admin vs GM)
  const ctxSales = document.getElementById('chartSales');
  if (ctxSales) {
    new Chart(ctxSales, {
      type: 'line',
      data: {
        labels: labelsW,
        datasets: [
          { label: 'Admin 매출(USDT)', data: adminSalesW, tension: 0.25 },
          { label: 'GM 매출(USDT)', data: gmSalesW, tension: 0.25 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: true } },
        scales: {
          x: { ticks: { maxTicksLimit: 8 } },
          y: { beginAtZero: true }
        }
      }
    });
  }

  // 3) 국가별 진행 건수
  const ctxCountries = document.getElementById('chartCountries');
  if (ctxCountries) {
    new Chart(ctxCountries, {
      type: 'bar',
      data: {
        labels: countryLabels,
        datasets: [{ label: '진행 건수', data: countryCounts }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  // 4) Role 분포
  const ctxRoles = document.getElementById('chartRoles');
  if (ctxRoles) {
    new Chart(ctxRoles, {
      type: 'doughnut',
      data: {
        labels: roleLabels,
        datasets: [{ label: 'Role', data: roleCounts }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } }
      }
    });
  }
</script>

<?php admin_render_footer(); ?>
