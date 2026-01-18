<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

$page_title = '집계/정산 대시보드';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// 대시보드 → 거래 목록(드릴다운) 링크 생성기
function tx_list_link(array $overrides = []): string {
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($q[$k]);
        else $q[$k] = $v;
    }
    // 거래 목록 페이지는 preset/from/to/filter/q/per/page/export 파라미터를 사용
    return 'settlement_transactions.php?' . http_build_query($q);
}

// ---------------- 기간 필터 (기본: 이번달)
$preset = isset($_GET['preset']) ? (string)$_GET['preset'] : 'month';
$from   = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
$to     = isset($_GET['to']) ? trim((string)$_GET['to']) : '';

// preset 적용
$today = new DateTime('now');
if ($from === '' || $to === '') {
    if ($preset === 'today') {
        $from = $today->format('Y-m-d');
        $to   = $today->format('Y-m-d');
    } elseif ($preset === '7days') {
        $d = (clone $today)->modify('-6 day');
        $from = $d->format('Y-m-d');
        $to   = $today->format('Y-m-d');
    } else {
        // month
        $from = $today->format('Y-m-01');
        $to   = $today->format('Y-m-d');
        $preset = 'month';
    }
}

// YYYY-MM-DD validation (fail-safe)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today->format('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today->format('Y-m-d');

$from_dt = $from . ' 00:00:00';
$to_dt   = $to   . ' 23:59:59';

// ---------------- DB helpers
function table_exists(mysqli $conn, string $table): bool {
    $sql = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res && $res->num_rows > 0);
    $stmt->close();
    return $ok;
}

// ---------------- 1) 거래/상태 요약 (user_transactions)
$tx = [
    'count' => 0,
    'deposit_done' => 0,
    'withdrawal_done' => 0,
    'dividend_done' => 0,
    'settle_done' => 0,
    'settle_pending' => 0,
    'xm_sum' => 0.0,
    'ultima_sum' => 0.0,
    'dividend_sum' => 0.0,
];

try {
    $sql = "SELECT
                COUNT(*) AS c,
                SUM(CASE WHEN deposit_chk=1 THEN 1 ELSE 0 END) AS deposit_done,
                SUM(CASE WHEN withdrawal_chk=1 THEN 1 ELSE 0 END) AS withdrawal_done,
                SUM(CASE WHEN dividend_chk=1 THEN 1 ELSE 0 END) AS dividend_done,
                SUM(CASE WHEN settle_chk=1 THEN 1 ELSE 0 END) AS settle_done,
                SUM(CASE WHEN settle_chk=0 THEN 1 ELSE 0 END) AS settle_pending,
                COALESCE(SUM(COALESCE(xm_value,0)),0) AS xm_sum,
                COALESCE(SUM(COALESCE(ultima_value,0)),0) AS ultima_sum,
                COALESCE(SUM(COALESCE(dividend_amount,0)),0) AS dividend_sum
            FROM user_transactions
            WHERE tx_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $from_dt, $to_dt);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            $tx['count'] = (int)($r['c'] ?? 0);
            $tx['deposit_done'] = (int)($r['deposit_done'] ?? 0);
            $tx['withdrawal_done'] = (int)($r['withdrawal_done'] ?? 0);
            $tx['dividend_done'] = (int)($r['dividend_done'] ?? 0);
            $tx['settle_done'] = (int)($r['settle_done'] ?? 0);
            $tx['settle_pending'] = (int)($r['settle_pending'] ?? 0);
            $tx['xm_sum'] = (float)($r['xm_sum'] ?? 0);
            $tx['ultima_sum'] = (float)($r['ultima_sum'] ?? 0);
            $tx['dividend_sum'] = (float)($r['dividend_sum'] ?? 0);
        }
        $stmt->close();
    }
} catch (Throwable $e) {
    // ignore
}

// 최근 거래 15건
$recent_tx = [];
try {
    $sql = "SELECT t.id, t.user_id,
                   COALESCE(NULLIF(TRIM(u.name),''), NULLIF(TRIM(u.username),''), u.email, CONCAT('ID ',u.id)) AS user_label,
                   t.tx_date, t.xm_value, t.ultima_value, t.dividend_amount,
                   t.deposit_chk, t.withdrawal_chk, t.dividend_chk, t.settle_chk
            FROM user_transactions t
            LEFT JOIN users u ON u.id = t.user_id
            WHERE t.tx_date BETWEEN ? AND ?
            ORDER BY t.tx_date DESC, t.id DESC
            LIMIT 15";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $from_dt, $to_dt);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) $recent_tx[] = $row;
        $stmt->close();
    }
} catch (Throwable $e) {}


// ---------------- 1-2) 일자별 집계 (차트용)
$daily = []; // ['date'=>YYYY-MM-DD, 'count'=>int, 'amount'=>float, 'dividend'=>float, 'settled'=>int, 'pending'=>int]
try {
    $sql = "SELECT
                DATE(tx_date) AS d,
                COUNT(*) AS c,
                COALESCE(SUM(COALESCE(xm_value,0) + COALESCE(ultima_value,0)),0) AS amt,
                COALESCE(SUM(COALESCE(dividend_amount,0)),0) AS div_sum,
                SUM(CASE WHEN settle_chk=1 THEN 1 ELSE 0 END) AS settled,
                SUM(CASE WHEN settle_chk=0 THEN 1 ELSE 0 END) AS pending
            FROM user_transactions
            WHERE tx_date BETWEEN ? AND ?
            GROUP BY DATE(tx_date)
            ORDER BY d ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $from_dt, $to_dt);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $daily[] = [
                'date' => (string)($row['d'] ?? ''),
                'count' => (int)($row['c'] ?? 0),
                'amount' => (float)($row['amt'] ?? 0),
                'dividend' => (float)($row['div_sum'] ?? 0),
                'settled' => (int)($row['settled'] ?? 0),
                'pending' => (int)($row['pending'] ?? 0),
            ];
        }
        $stmt->close();
    }
} catch (Throwable $e) {}


// ---------------- 2) 국가별 진행 테이블 요약
$countries = [
    ['key'=>'korea', 'label'=>'KR', 'flag'=>'🇰🇷'],
    ['key'=>'japan', 'label'=>'JP', 'flag'=>'🇯🇵'],
    ['key'=>'usa', 'label'=>'US', 'flag'=>'🇺🇸'],
    ['key'=>'vietnam', 'label'=>'VN', 'flag'=>'🇻🇳'],
    ['key'=>'cambodia', 'label'=>'KH', 'flag'=>'🇰🇭'],
];

$progressing_summary = [];
foreach ($countries as $c) {
    $tbl = $c['key'] . '_progressing';
    if (!table_exists($conn, $tbl)) continue;
    try {
        $sql = "SELECT
                    COUNT(*) AS c,
                    SUM(CASE WHEN deposit_status=1 THEN 1 ELSE 0 END) AS deposit_done,
                    SUM(CASE WHEN withdrawal_status=1 THEN 1 ELSE 0 END) AS withdrawal_done
                FROM {$tbl}";
        $res = $conn->query($sql);
        $r = $res ? $res->fetch_assoc() : null;
        $progressing_summary[] = [
            'flag' => $c['flag'],
            'name' => $c['label'],
            'table' => $tbl,
            'count' => (int)($r['c'] ?? 0),
            'deposit_done' => (int)($r['deposit_done'] ?? 0),
            'withdrawal_done' => (int)($r['withdrawal_done'] ?? 0),
            'href' => $c['key'] . '_progressing.php',
        ];
    } catch (Throwable $e) {}
}

// ---------------- 3) 관리자 입력(정산 기록) 테이블 현황(최근 7일 입력 건수)
$ledgers = [
    ['label'=>'GM Deposits', 'table'=>'gm_deposits', 'href'=>'gm_deposits.php'],
    ['label'=>'GM Sales Daily', 'table'=>'gm_sales_daily', 'href'=>'gm_sales_daily.php'],
    ['label'=>'Admin Deposits Daily', 'table'=>'admin_deposits_daily', 'href'=>'admin_deposits_daily.php'],
    ['label'=>'Admin Sales Daily', 'table'=>'admin_sales_daily', 'href'=>'admin_sales_daily.php'],
    ['label'=>'Partner Deposits', 'table'=>'partner_deposits', 'href'=>'partner_deposits.php'],
];

$ledger_summary = [];
foreach ($ledgers as $l) {
    if (!table_exists($conn, $l['table'])) continue;
    // 어떤 컬럼이 date인지 몰라서, 대표 컬럼 후보로 탐색 (date / tx_date / created_at)
    $date_col = null;
    try {
        $cols = [];
        $res = $conn->query("SHOW COLUMNS FROM `{$l['table']}`");
        while ($res && ($r = $res->fetch_assoc())) $cols[] = strtolower((string)$r['Field']);
        foreach (['date','tx_date','created_at','day','record_date'] as $cand) {
            if (in_array($cand, $cols, true)) { $date_col = $cand; break; }
        }
    } catch (Throwable $e) {}

    try {
        if ($date_col) {
            $sql = "SELECT COUNT(*) AS c FROM `{$l['table']}` WHERE `{$date_col}` >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } else {
            $sql = "SELECT COUNT(*) AS c FROM `{$l['table']}`";
        }
        $res = $conn->query($sql);
        $r = $res ? $res->fetch_assoc() : null;
        $ledger_summary[] = [
            'label' => $l['label'],
            'table' => $l['table'],
            'count' => (int)($r['c'] ?? 0),
            'href'  => $l['href'],
            'date_col' => $date_col,
        ];
    } catch (Throwable $e) {}
}

admin_render_header($page_title);
?>

<style>
  .grid{display:grid; grid-template-columns: repeat(12, 1fr); gap:14px}
  .span-12{grid-column: span 12}
  .span-8{grid-column: span 8}
  .span-6{grid-column: span 6}
  .span-4{grid-column: span 4}
  .span-3{grid-column: span 3}
  @media (max-width: 1100px){ .span-8,.span-6,.span-4,.span-3{grid-column: span 12} }

  .kpi{display:flex; align-items:flex-start; justify-content:space-between; gap:10px}
  .kpi .v{font-size:20px; font-weight:900; letter-spacing:-.02em}
  .kpi .t{color:var(--muted); font-size:12px; margin-top:2px}
  .kpi .tag{font-size:12px; color:var(--muted)}

  .toolbar{display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap}
  .toolbar .field{display:flex; flex-direction:column; gap:6px}
  .toolbar input, .toolbar select{
    padding:10px 10px; border:1px solid var(--border); border-radius:12px; background:#fff; color:var(--text);
  }
  .btn{
    padding:10px 12px; border-radius:12px; border:1px solid rgba(37,99,235,.22);
    background:rgba(37,99,235,.10); cursor:pointer; font-weight:800;
  }
  .btn:hover{background:rgba(37,99,235,.14)}
  .btn.secondary{border-color:rgba(17,24,39,.10); background:rgba(17,24,39,.04)}

  .tbl{width:100%; border-collapse:separate; border-spacing:0; font-size:13px}
  .tbl th,.tbl td{padding:10px 10px; border-bottom:1px solid var(--border); text-align:left; white-space:nowrap}
  .tbl th{position:sticky; top:0; background:#fff; z-index:1}
  .tbl tr:hover td{background:rgba(17,24,39,.03)}
  .sub{color:var(--muted); font-size:12px}
  .pills{display:flex; gap:8px; flex-wrap:wrap}
  .pill{display:inline-flex; gap:8px; align-items:center; padding:6px 10px; border-radius:999px; border:1px solid var(--border); background:#fff; text-decoration:none}
  .pill:hover{background:rgba(17,24,39,.03)}
  .mono{font-family:var(--mono)}
  .st{display:inline-flex; gap:6px; align-items:center; font-size:12px; padding:4px 8px; border-radius:999px; border:1px solid var(--border); color:var(--muted)}

  /* Charts */
  .chart-grid{display:grid; grid-template-columns: 1fr 1fr; gap:12px; align-items:stretch}
  @media (max-width: 1100px){ .chart-grid{grid-template-columns:1fr} }
  .chart-box{display:flex; gap:14px; align-items:center}
  .donut{width:140px; height:140px; flex:0 0 140px}
  .legend{display:flex; flex-direction:column; gap:8px; width:100%}
  .legend .row{display:flex; justify-content:space-between; gap:10px; font-size:13px}
  .legend .label{display:flex; align-items:center; gap:8px; color:var(--muted)}
  .dot{width:10px; height:10px; border-radius:999px; display:inline-block; background:var(--border)}
  .bar-spark{width:100%; height:160px; display:block}
  .mini-note{margin-top:6px; font-size:12px; color:var(--muted)}
</style>

<div class="grid">

  <div class="card span-12">
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <div style="font-weight:900; font-size:16px;">집계/정산 대시보드</div>
        <div class="sub" style="margin-top:4px;">기간 기준으로 <b>거래(user_transactions)</b> 요약 + <b>국가 진행(progressing)</b> + <b>정산 기록(관리 테이블)</b> 현황을 한 번에 봅니다.</div>
      </div>

      <form method="get" class="toolbar" style="margin-top:2px;">
        <div class="field">
          <div class="sub">빠른 기간</div>
          <select name="preset">
            <option value="today" <?= $preset==='today'?'selected':'' ?>>오늘</option>
            <option value="7days" <?= $preset==='7days'?'selected':'' ?>>최근 7일</option>
            <option value="month" <?= $preset==='month'?'selected':'' ?>>이번달</option>
          </select>
        </div>
        <div class="field">
          <div class="sub">From</div>
          <input type="date" name="from" value="<?= h($from) ?>" />
        </div>
        <div class="field">
          <div class="sub">To</div>
          <input type="date" name="to" value="<?= h($to) ?>" />
        </div>
        <button class="btn" type="submit">적용</button>
        <a class="btn secondary" href="settlement_dashboard.php?preset=month">초기화</a>
      </form>
    </div>
  </div>

  <!-- KPI cards -->
  <div class="card span-3">
    <a class="kpi kpi-link" href="<?= h(tx_list_link(['filter'=>'all','page'=>1])) ?>" style="text-decoration:none; color:inherit; display:block;">
      <div>
        <div class="v"><?= number_format($tx['count']) ?></div>
        <div class="t">거래 건수</div>
      </div>
      <div class="tag">기간: <span class="mono"><?= h($from) ?> ~ <?= h($to) ?></span></div>
    </a>
  </div>
  <div class="card span-3"><a class="kpi kpi-link" href="<?= h(tx_list_link(['filter'=>'deposit','page'=>1])) ?>" style="text-decoration:none; color:inherit; display:block;"><div><div class="v"><?= number_format($tx['deposit_done']) ?></div><div class="t">입금 완료</div></div><div class="st">deposit_chk=1</div></a></div>
  <div class="card span-3"><a class="kpi kpi-link" href="<?= h(tx_list_link(['filter'=>'withdrawal','page'=>1])) ?>" style="text-decoration:none; color:inherit; display:block;"><div><div class="v"><?= number_format($tx['withdrawal_done']) ?></div><div class="t">출금 완료</div></div><div class="st">withdrawal_chk=1</div></a></div>
  <div class="card span-3"><a class="kpi kpi-link" href="<?= h(tx_list_link(['filter'=>'dividend','page'=>1])) ?>" style="text-decoration:none; color:inherit; display:block;"><div><div class="v"><?= number_format($tx['dividend_done']) ?></div><div class="t">배당 완료</div></div><div class="st">dividend_chk=1</div></a></div>

  <div class="card span-3"><a class="kpi kpi-link" href="<?= h(tx_list_link(['filter'=>'settled','page'=>1])) ?>" style="text-decoration:none; color:inherit; display:block;"><div><div class="v"><?= number_format($tx['settle_done']) ?></div><div class="t">정산 완료</div></div><div class="st">settle_chk=1</div></a></div>
  <div class="card span-3"><a class="kpi kpi-link" href="<?= h(tx_list_link(['filter'=>'unsettled','page'=>1])) ?>" style="text-decoration:none; color:inherit; display:block;"><div><div class="v"><?= number_format($tx['settle_pending']) ?></div><div class="t">미정산</div></div><div class="st">settle_chk=0</div></a></div>
  <div class="card span-3"><div class="kpi"><div><div class="v"><?= number_format($tx['xm_sum'], 2) ?></div><div class="t">XM 합계</div></div><div class="st">xm_value</div></div></div>
  <div class="card span-3"><div class="kpi"><div><div class="v"><?= number_format($tx['ultima_sum'], 2) ?></div><div class="t">ULTIMA 합계</div></div><div class="st">ultima_value</div></div></div>

  <div class="card span-4">
    <div class="kpi">
      <div>
        <div class="v"><?= number_format($tx['dividend_sum'], 2) ?></div>
        <div class="t">배당 합계</div>
      </div>
      <div class="st">dividend_amount</div>
    </div>
    <div class="sub" style="margin-top:8px;">※ 합계는 집계 기간 내 <span class="mono">user_transactions</span> 기반입니다.</div>
  </div>

  
  <!-- Charts -->
  <div class="card span-6">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px;">
      <div>
        <div style="font-weight:900; font-size:15px;">정산 현황(완료/미정산)</div>
        <div class="sub" style="margin-top:4px;">기간 내 거래를 정산 상태로 한눈에</div>
      </div>
      <div class="mini-note">기준: settle_chk</div>
    </div>

    <?php
      $settled = (int)($tx['settle_done'] ?? 0);
      $pending = (int)($tx['settle_pending'] ?? 0);
      $total_s = max(1, $settled + $pending);
      $p_settled = $settled / $total_s;
      $p_pending = $pending / $total_s;

      $r = 54; // donut radius
      $circ = 2 * pi() * $r;
      $len_settled = $circ * $p_settled;
      $len_pending = $circ * $p_pending;
      $gap = 2.5;
    ?>

    <div class="chart-box" style="margin-top:12px;">
      <svg class="donut" viewBox="0 0 140 140" role="img" aria-label="정산 현황 도넛차트">
        <circle cx="70" cy="70" r="<?= $r ?>" fill="none" stroke="var(--border)" stroke-width="14"></circle>

        <!-- pending (click → 미정산 목록) -->
        <a href="<?= h(tx_list_link(['filter'=>'unsettled','page'=>1])) ?>">
          <circle cx="70" cy="70" r="<?= $r ?>" fill="none"
                  stroke="var(--muted)"
                  stroke-width="14"
                  stroke-linecap="round"
                  stroke-dasharray="<?= max(0, $len_pending - $gap) ?> <?= $circ ?>"
                  transform="rotate(-90 70 70)"></circle>
        </a>

        <!-- settled (click → 정산완료 목록) -->
        <a href="<?= h(tx_list_link(['filter'=>'settled','page'=>1])) ?>">
          <circle cx="70" cy="70" r="<?= $r ?>" fill="none"
                  stroke="var(--accent)"
                  stroke-width="14"
                  stroke-linecap="round"
                  stroke-dasharray="<?= max(0, $len_settled - $gap) ?> <?= $circ ?>"
                  stroke-dashoffset="<?= -$len_pending ?>"
                  transform="rotate(-90 70 70)"></circle>
        </a>

        <text x="70" y="68" text-anchor="middle" font-size="22" font-weight="900" fill="var(--text)">
          <?= number_format((int)round($p_settled * 100)) ?>%
        </text>
        <text x="70" y="90" text-anchor="middle" font-size="12" fill="var(--muted)">정산 완료</text>
      </svg>

      <div class="legend">
        <div class="row">
          <div class="label"><span class="dot" style="background:var(--accent)"></span><a href="<?= h(tx_list_link(['filter'=>'settled','page'=>1])) ?>" style="color:inherit; text-decoration:none;">정산 완료</a></div>
          <div class="mono"><a href="<?= h(tx_list_link(['filter'=>'settled','page'=>1])) ?>" style="color:inherit; text-decoration:none;">
            <?= number_format($settled) ?>
          </a></div>
        </div>
        <div class="row">
          <div class="label"><span class="dot" style="background:var(--muted)"></span><a href="<?= h(tx_list_link(['filter'=>'unsettled','page'=>1])) ?>" style="color:inherit; text-decoration:none;">미정산</a></div>
          <div class="mono"><a href="<?= h(tx_list_link(['filter'=>'unsettled','page'=>1])) ?>" style="color:inherit; text-decoration:none;">
            <?= number_format($pending) ?>
          </a></div>
        </div>
        <div class="row" style="margin-top:6px; padding-top:10px; border-top:1px dashed var(--border);">
          <div class="label">합계</div>
          <div class="mono"><?= number_format($settled + $pending) ?></div>
        </div>
        <div class="mini-note">* 한 거래가 여러 체크를 가질 수 있으므로, 이 차트는 “정산 완료 여부”만 분할합니다.</div>
      </div>
    </div>
  </div>

  <div class="card span-6">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px;">
      <div>
        <div style="font-weight:900; font-size:15px;">일자별 거래 규모</div>
        <div class="sub" style="margin-top:4px;">(XM + ULTIMA) 합계 기준</div>
      </div>
      <div class="mini-note">기간: <span class="mono"><?= h($from) ?> ~ <?= h($to) ?></span></div>
    </div>

    <?php
      $max_amt = 0.0;
      foreach ($daily as $d) { $max_amt = max($max_amt, (float)$d['amount']); }
      $max_amt = max(1.0, $max_amt);

      $n = count($daily);
      $pad = 10;
      $w = 560; $h = 160;
      $bar_w = ($n > 0) ? max(6, floor(($w - $pad*2) / $n) - 2) : 0;
      $gap = 2;
    ?>

    <?php if ($n === 0): ?>
      <div style="margin-top:12px; color:var(--muted);">해당 기간에 거래 데이터가 없습니다.</div>
    <?php else: ?>
      <svg class="bar-spark" viewBox="0 0 <?= $w ?> <?= $h ?>" preserveAspectRatio="none" role="img" aria-label="일자별 거래 규모 막대 차트">
        <!-- baseline -->
        <line x1="<?= $pad ?>" y1="<?= $h-18 ?>" x2="<?= $w-$pad ?>" y2="<?= $h-18 ?>" stroke="var(--border)" stroke-width="1" />
        <?php
          $i = 0;
          foreach ($daily as $d) {
            $val = (float)$d['amount'];
            $bh = (int)round(($h-32) * ($val / $max_amt));
            $x = $pad + ($i * ($bar_w + $gap));
            $y = ($h-18) - $bh;
            $title = $d['date'] . " | " . number_format($val, 2);
        ?>
          <a href="<?= h(tx_list_link(['preset'=>'today','from'=>$d['date'],'to'=>$d['date'],'filter'=>'all','page'=>1])) ?>">
            <rect x="<?= $x ?>" y="<?= $y ?>" width="<?= $bar_w ?>" height="<?= $bh ?>" rx="3"
                  fill="var(--accent)" opacity="0.85">
              <title><?= h($title) ?> (클릭: 해당 날짜 거래)</title>
            </rect>
          </a>
        <?php $i++; } ?>
      </svg>
      <div class="mini-note">막대에 마우스를 올리면 날짜/금액이 표시됩니다.</div>
    <?php endif; ?>
  </div>


<div class="card span-8">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
      <div>
        <div style="font-weight:900; font-size:15px;">바로가기</div>
        <div class="sub" style="margin-top:4px;">집계/정산 관련 관리 페이지로 빠르게 이동</div>
      </div>
      <div class="pills">
        <a class="pill" href="gm_deposits.php">💰 GM Deposits</a>
        <a class="pill" href="gm_sales_daily.php">📈 GM Sales Daily</a>
        <a class="pill" href="admin_deposits_daily.php">🏦 Admin Deposits Daily</a>
        <a class="pill" href="admin_sales_daily.php">📊 Admin Sales Daily</a>
        <a class="pill" href="partner_deposits.php">🤝 Partner Deposits</a>
      </div>
    </div>
  </div>

  <!-- Country progressing summary -->
  <div class="card span-6">
    <div style="font-weight:900; font-size:15px;">국가 진행 현황(progressing)</div>
    <div class="sub" style="margin-top:4px;">각 국가 테이블 기준 총 건수 / 입금완료 / 출금완료</div>
    <div style="overflow:auto; margin-top:10px;">
      <table class="tbl">
        <thead>
          <tr>
            <th>국가</th>
            <th>총 건수</th>
            <th>입금완료</th>
            <th>출금완료</th>
            <th>페이지</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$progressing_summary): ?>
            <tr><td colspan="5" class="sub">progressing 테이블이 없거나 조회할 데이터가 없습니다.</td></tr>
          <?php else: foreach ($progressing_summary as $p): ?>
            <tr>
              <td><?= h($p['flag'].' '.$p['name']) ?> <span class="sub mono">(<?= h($p['table']) ?>)</span></td>
              <td><?= number_format((int)$p['count']) ?></td>
              <td><?= number_format((int)$p['deposit_done']) ?></td>
              <td><?= number_format((int)$p['withdrawal_done']) ?></td>
              <td><a href="<?= h($p['href']) ?>">열기</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Ledger summary -->
  <div class="card span-6">
    <div style="font-weight:900; font-size:15px;">정산 기록(관리 테이블) 현황</div>
    <div class="sub" style="margin-top:4px;">최근 7일 입력 건수(가능한 경우) 또는 전체 건수</div>
    <div style="overflow:auto; margin-top:10px;">
      <table class="tbl">
        <thead>
          <tr>
            <th>구분</th>
            <th>테이블</th>
            <th>건수</th>
            <th>기준</th>
            <th>페이지</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$ledger_summary): ?>
            <tr><td colspan="5" class="sub">관련 테이블이 없거나 조회할 데이터가 없습니다.</td></tr>
          <?php else: foreach ($ledger_summary as $l): ?>
            <tr>
              <td><?= h($l['label']) ?></td>
              <td class="mono"><?= h($l['table']) ?></td>
              <td><?= number_format((int)$l['count']) ?></td>
              <td class="sub"><?= $l['date_col'] ? '최근 7일('.h($l['date_col']).')' : '전체' ?></td>
              <td><a href="<?= h($l['href']) ?>">열기</a></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent transactions -->
  <div class="card span-12">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:10px; flex-wrap:wrap;">
      <div>
        <div style="font-weight:900; font-size:15px;">최근 거래(집계 기간 내)</div>
        <div class="sub" style="margin-top:4px;">최근 15건을 표시합니다. (정산/완료 체크 상태 포함)</div>
      </div>
      <div class="sub">원천: <span class="mono">user_transactions</span></div>
    </div>

    <div style="overflow:auto; margin-top:10px;">
      <table class="tbl">
        <thead>
          <tr>
            <th>ID</th>
            <th>회원</th>
            <th>일시</th>
            <th>XM</th>
            <th>ULTIMA</th>
            <th>배당</th>
            <th>입금</th>
            <th>출금</th>
            <th>배당</th>
            <th>정산</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$recent_tx): ?>
            <tr><td colspan="10" class="sub">집계 기간 내 거래가 없습니다.</td></tr>
          <?php else: foreach ($recent_tx as $r): ?>
            <tr>
              <td class="mono">#<?= (int)$r['id'] ?></td>
              <td><?= h($r['user_label'] ?? ('ID '.(int)$r['user_id'])) ?> <span class="sub">(<?= (int)$r['user_id'] ?>)</span></td>
              <td class="mono"><?= h($r['tx_date']) ?></td>
              <td><?= number_format((float)$r['xm_value'], 2) ?></td>
              <td><?= number_format((float)$r['ultima_value'], 2) ?></td>
              <td><?= number_format((float)$r['dividend_amount'], 2) ?></td>
              <td><?= ((int)$r['deposit_chk']===1) ? '✅' : '—' ?></td>
              <td><?= ((int)$r['withdrawal_chk']===1) ? '✅' : '—' ?></td>
              <td><?= ((int)$r['dividend_chk']===1) ? '✅' : '—' ?></td>
              <td><?= ((int)$r['settle_chk']===1) ? '✅' : '—' ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php admin_render_footer(); ?>
