<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

// ---- helpers ----
function normalize_date(string $s, string $fallback): string {
    $s = trim($s);
    if ($s === '') return $fallback;
    $ts = strtotime($s);
    return $ts ? date('Y-m-d', $ts) : $fallback;
}

function date_list(string $from, string $to): array {
    $d1 = new DateTime($from);
    $d2 = new DateTime($to);
    if ($d2 < $d1) { [$d1, $d2] = [$d2, $d1]; }
    $out = [];
    $cur = clone $d1;
    while ($cur <= $d2) {
        $out[] = $cur->format('Y-m-d');
        $cur->modify('+1 day');
        // 안전: 너무 긴 기간 방지
        if (count($out) > 370) break;
    }
    return $out;
}

function fetch_kv(mysqli $conn, string $sql, string $types, array $params): array {
    $out = [];
    try {
        $stmt = $conn->prepare($sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[$row['d']] = (float)$row['v'];
        }
        $stmt->close();
    } catch (Throwable $e) {
        // ignore
    }
    return $out;
}

function fetch_kv_count(mysqli $conn, string $sql, string $types, array $params): array {
    $out = [];
    try {
        $stmt = $conn->prepare($sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[$row['d']] = (int)$row['c'];
        }
        $stmt->close();
    } catch (Throwable $e) {
        // ignore
    }
    return $out;
}

function nf($n): string {
    return number_format((float)$n, 2);
}

// ---- filters ----
$preset = $_GET['preset'] ?? '7d';
$today = date('Y-m-d');

if ($preset === 'today') {
    $from_default = $today;
    $to_default = $today;
} elseif ($preset === 'month') {
    $from_default = date('Y-m-01');
    $to_default = $today;
} else { // 7d
    $from_default = date('Y-m-d', strtotime('-6 days'));
    $to_default = $today;
}

$from = normalize_date($_GET['from'] ?? '', $from_default);
$to   = normalize_date($_GET['to'] ?? '', $to_default);

$dates = date_list($from, $to);

// ---- data (auto: user_transactions) ----
$auto_deposit = fetch_kv(
    $conn,
    "SELECT tx_date AS d, SUM(CASE WHEN deposit_chk=1 THEN (COALESCE(xm_value,0)+COALESCE(ultima_value,0)) ELSE 0 END) AS v
     FROM user_transactions
     WHERE tx_date BETWEEN ? AND ?
     GROUP BY tx_date",
    'ss',
    [$from, $to]
);

$auto_dividend = fetch_kv(
    $conn,
    "SELECT tx_date AS d, SUM(CASE WHEN dividend_chk=1 THEN COALESCE(dividend_amount,0) ELSE 0 END) AS v
     FROM user_transactions
     WHERE tx_date BETWEEN ? AND ?
     GROUP BY tx_date",
    'ss',
    [$from, $to]
);

$auto_tx_cnt = fetch_kv_count(
    $conn,
    "SELECT tx_date AS d, COUNT(*) AS c
     FROM user_transactions
     WHERE tx_date BETWEEN ? AND ?
     GROUP BY tx_date",
    'ss',
    [$from, $to]
);

$auto_settled_cnt = fetch_kv_count(
    $conn,
    "SELECT tx_date AS d, SUM(CASE WHEN settle_chk=1 THEN 1 ELSE 0 END) AS c
     FROM user_transactions
     WHERE tx_date BETWEEN ? AND ?
     GROUP BY tx_date",
    'ss',
    [$from, $to]
);

// ---- data (manual ledgers) ----
$admin_dep = fetch_kv(
    $conn,
    "SELECT deposit_date AS d, SUM(deposit_amount) AS v
     FROM admin_deposits_daily
     WHERE deposit_date BETWEEN ? AND ?
     GROUP BY deposit_date",
    'ss',
    [$from, $to]
);

$gm_dep = fetch_kv(
    $conn,
    "SELECT settle_date AS d, SUM(deposit_amount) AS v
     FROM gm_deposits
     WHERE settle_date BETWEEN ? AND ?
     GROUP BY settle_date",
    'ss',
    [$from, $to]
);

$partner_dep = fetch_kv(
    $conn,
    "SELECT DATE(deposit_time) AS d, SUM(deposit_amount) AS v
     FROM partner_deposits
     WHERE DATE(deposit_time) BETWEEN ? AND ?
     GROUP BY DATE(deposit_time)",
    'ss',
    [$from, $to]
);

$admin_sales = fetch_kv(
    $conn,
    "SELECT sales_date AS d, SUM(sales_amount) AS v
     FROM admin_sales_daily
     WHERE sales_date BETWEEN ? AND ?
     GROUP BY sales_date",
    'ss',
    [$from, $to]
);

$gm_sales = fetch_kv(
    $conn,
    "SELECT sales_date AS d, SUM(sales_amount) AS v
     FROM gm_sales_daily
     WHERE sales_date BETWEEN ? AND ?
     GROUP BY sales_date",
    'ss',
    [$from, $to]
);

// ---- totals ----
$totals = [
    'auto_dep' => 0, 'auto_div' => 0, 'tx_cnt' => 0, 'settled_cnt' => 0,
    'admin_dep' => 0, 'gm_dep' => 0, 'partner_dep' => 0,
    'admin_sales' => 0, 'gm_sales' => 0,
];

foreach ($dates as $d) {
    $totals['auto_dep'] += $auto_deposit[$d] ?? 0;
    $totals['auto_div'] += $auto_dividend[$d] ?? 0;
    $totals['tx_cnt'] += $auto_tx_cnt[$d] ?? 0;
    $totals['settled_cnt'] += $auto_settled_cnt[$d] ?? 0;

    $totals['admin_dep'] += $admin_dep[$d] ?? 0;
    $totals['gm_dep'] += $gm_dep[$d] ?? 0;
    $totals['partner_dep'] += $partner_dep[$d] ?? 0;

    $totals['admin_sales'] += $admin_sales[$d] ?? 0;
    $totals['gm_sales'] += $gm_sales[$d] ?? 0;
}

admin_render_header('집계/정산 · 대조(자동 vs 기록)');

$hint = "※ ‘자동’은 user_transactions 기준(입금=XM+ULTIMA, 배당=dividend_amount) / ‘기록’은 관리자·GM·파트너 테이블 입력값입니다. (날짜/수치 클릭 시 해당 거래목록으로 이동)";
?>

<div class="grid" style="grid-template-columns: repeat(12, 1fr); gap:14px; margin-bottom:14px;">
    <div class="card" style="grid-column: span 12;">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; gap:12px; flex-wrap:wrap">
            <div>
                <div style="font-weight:800; font-size:16px;">자동 집계 ↔ 수기 기록 대조</div>
                <div style="color:var(--muted); font-size:12px; margin-top:4px;"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
                <a class="btnlink" href="settlement_dashboard.php">집계/정산 대시보드</a>
                <a class="btnlink" href="settlement_transactions.php">거래 목록</a>
            </div>
        </div>

        <form method="get" style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end">
            <div>
                <label class="label">프리셋</label>
                <select name="preset" class="input">
                    <option value="today" <?= $preset==='today'?'selected':'' ?>>오늘</option>
                    <option value="7d" <?= $preset==='7d'?'selected':'' ?>>최근 7일</option>
                    <option value="month" <?= $preset==='month'?'selected':'' ?>>이번달</option>
                    <option value="custom" <?= $preset==='custom'?'selected':'' ?>>직접선택</option>
                </select>
            </div>
            <div>
                <label class="label">From</label>
                <input type="date" name="from" class="input" value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <label class="label">To</label>
                <input type="date" name="to" class="input" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div>
                <button class="btn" type="submit">대조하기</button>
            </div>
        </form>
    </div>

    <div class="card" style="grid-column: span 4;">
        <div style="color:var(--muted); font-size:12px;">거래 건수(자동)</div>
        <div style="font-size:22px; font-weight:900; margin-top:6px;"><?= number_format($totals['tx_cnt']) ?></div>
        <div style="margin-top:8px; color:var(--muted); font-size:12px;">정산완료(자동): <?= number_format($totals['settled_cnt']) ?></div>
    </div>

    <div class="card" style="grid-column: span 4;">
        <div style="color:var(--muted); font-size:12px;">입금(자동: XM+ULTIMA)</div>
        <div style="font-size:22px; font-weight:900; margin-top:6px;"><?= nf($totals['auto_dep']) ?></div>
        <div style="margin-top:8px; color:var(--muted); font-size:12px;">기록 합계(관리자+GM+파트너): <?= nf($totals['admin_dep']+$totals['gm_dep']+$totals['partner_dep']) ?></div>
    </div>

    <div class="card" style="grid-column: span 4;">
        <div style="color:var(--muted); font-size:12px;">배당(자동)</div>
        <div style="font-size:22px; font-weight:900; margin-top:6px;"><?= nf($totals['auto_div']) ?></div>
        <div style="margin-top:8px; color:var(--muted); font-size:12px;">매출 기록 합계(관리자+GM): <?= nf($totals['admin_sales']+$totals['gm_sales']) ?></div>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap">
        <div style="font-weight:800;">일자별 대조표</div>
        <div style="color:var(--muted); font-size:12px;">
            기간: <b><?= htmlspecialchars($from) ?></b> ~ <b><?= htmlspecialchars($to) ?></b>
        </div>
    </div>

    <div style="overflow:auto; margin-top:12px;">
        <table class="table" style="min-width:1100px">
            <thead>
                <tr>
                    <th style="min-width:110px">Date</th>
                    <th>TX</th>
                    <th>정산완료</th>
                    <th style="text-align:right">자동 입금</th>
                    <th style="text-align:right">관리자 입금</th>
                    <th style="text-align:right">GM 입금</th>
                    <th style="text-align:right">파트너 입금</th>
                    <th style="text-align:right">기록 입금합</th>
                    <th style="text-align:right">차이(자동-기록)</th>
                    <th style="text-align:right">자동 배당</th>
                    <th style="text-align:right">관리자 매출</th>
                    <th style="text-align:right">GM 매출</th>
                    <th style="text-align:right">차이(배당-매출)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dates as $d):
                $tx = $auto_tx_cnt[$d] ?? 0;
                $settled = $auto_settled_cnt[$d] ?? 0;

                $ad = $auto_deposit[$d] ?? 0;
                $rd_admin = $admin_dep[$d] ?? 0;
                $rd_gm = $gm_dep[$d] ?? 0;
                $rd_partner = $partner_dep[$d] ?? 0;
                $rd_total = $rd_admin + $rd_gm + $rd_partner;
                $diff_dep = $ad - $rd_total;

                $av = $auto_dividend[$d] ?? 0;
                $rs_admin = $admin_sales[$d] ?? 0;
                $rs_gm = $gm_sales[$d] ?? 0;
                $rs_total = $rs_admin + $rs_gm;
                $diff_sales = $av - $rs_total;

                $warn_dep = abs($diff_dep) > 0.009;
                $warn_sales = abs($diff_sales) > 0.009;

                // drilldown links (keep filters lightweight)
                $qd = urlencode($d);
                $base = "settlement_transactions.php?from={$qd}&to={$qd}";
                $lnk_all = $base;
                $lnk_settled = $base . "&status=settled";
                $lnk_unsettled = $base . "&status=unsettled";
                $lnk_dep = $base . "&status=deposit";
                $lnk_div = $base . "&status=dividend";
                ?>
                <tr>
                    <td><a href="<?= $lnk_all ?>" style="font-weight:800; text-decoration:none"><?= htmlspecialchars($d) ?></a></td>
                    <td><a href="<?= $lnk_all ?>" style="text-decoration:none"><?= number_format($tx) ?></a></td>
                    <td><a href="<?= $lnk_settled ?>" title="정산완료 목록" style="text-decoration:none"><?= number_format($settled) ?></a></td>
                    <td style="text-align:right"><a href="<?= $lnk_dep ?>" title="입금완료 거래" style="text-decoration:none"><?= nf($ad) ?></a></td>
                    <td style="text-align:right"><?= nf($rd_admin) ?></td>
                    <td style="text-align:right"><?= nf($rd_gm) ?></td>
                    <td style="text-align:right"><?= nf($rd_partner) ?></td>
                    <td style="text-align:right"><?= nf($rd_total) ?></td>
                    <td style="text-align:right; font-weight:800; <?= $warn_dep?'color:#ef4444;':'' ?>"><a href="<?= $lnk_dep ?>" title="입금완료 거래(자동 기준)" style="text-decoration:none; color:inherit"><?= nf($diff_dep) ?></a></td>
                    <td style="text-align:right"><a href="<?= $lnk_div ?>" title="배당완료 거래(자동 지표)" style="text-decoration:none"><?= nf($av) ?></a></td>
                    <td style="text-align:right"><?= nf($rs_admin) ?></td>
                    <td style="text-align:right"><?= nf($rs_gm) ?></td>
                    <td style="text-align:right; font-weight:800; <?= $warn_sales?'color:#ef4444;':'' ?>"><a href="<?= $lnk_div ?>" title="배당완료 거래(자동 기준)" style="text-decoration:none; color:inherit"><?= nf($diff_sales) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>합계</th>
                    <th><?= number_format($totals['tx_cnt']) ?></th>
                    <th><?= number_format($totals['settled_cnt']) ?></th>
                    <th style="text-align:right"><?= nf($totals['auto_dep']) ?></th>
                    <th style="text-align:right"><?= nf($totals['admin_dep']) ?></th>
                    <th style="text-align:right"><?= nf($totals['gm_dep']) ?></th>
                    <th style="text-align:right"><?= nf($totals['partner_dep']) ?></th>
                    <th style="text-align:right"><?= nf($totals['admin_dep']+$totals['gm_dep']+$totals['partner_dep']) ?></th>
                    <th style="text-align:right; font-weight:900;">
                        <?= nf($totals['auto_dep']-($totals['admin_dep']+$totals['gm_dep']+$totals['partner_dep'])) ?>
                    </th>
                    <th style="text-align:right"><?= nf($totals['auto_div']) ?></th>
                    <th style="text-align:right"><?= nf($totals['admin_sales']) ?></th>
                    <th style="text-align:right"><?= nf($totals['gm_sales']) ?></th>
                    <th style="text-align:right; font-weight:900;">
                        <?= nf($totals['auto_div']-($totals['admin_sales']+$totals['gm_sales'])) ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="margin-top:10px; color:var(--muted); font-size:12px; line-height:1.5">
        <div>• 입금 차이가 지속적으로 크면: <b>deposit_chk 기준</b> 또는 <b>수기 기록 입력일(날짜)</b>이 다를 가능성이 큽니다.</div>
        <div>• 배당/매출 차이는: 매출 테이블이 배당과 동일 의미가 아닌 경우가 있을 수 있습니다. 필요하면 ‘자동 배당’ 대신 다른 자동 지표(예: profit_loss 합계)로 대체 가능합니다.</div>
    </div>
</div>

<?php admin_render_footer(); ?>
