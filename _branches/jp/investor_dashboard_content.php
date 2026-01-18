<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
include 'db_connect.php';
include 'includes/gm_dashboard_ui.php';  // ✅ 공통 CSS include

// ✅ 로그인한 회원 Rejecting 알림 (settle_chk=2 또는 reject_reason 존재)
$current_user_id = (int)($_SESSION['user_id'] ?? 0);
$rejecting_count = 0;
$rejecting_rows = [];
if ($current_user_id > 0) {
    $sql_rejecting = "SELECT id, tx_date, reject_reason
                      FROM user_transactions
                      WHERE user_id = ?
                        AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))
                      ORDER BY tx_date DESC, id DESC
                      LIMIT 3";
    $stmt_r = $conn->prepare($sql_rejecting);
    $stmt_r->bind_param('i', $current_user_id);
    $stmt_r->execute();
    $res_r = $stmt_r->get_result();
    while ($r = $res_r->fetch_assoc()) {
        $rejecting_rows[] = $r;
    }
    $stmt_r->close();

    $sql_rejecting_cnt = "SELECT COUNT(*) AS cnt
                          FROM user_transactions
                          WHERE user_id = ?
                            AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))";
    $stmt_c = $conn->prepare($sql_rejecting_cnt);
    $stmt_c->bind_param('i', $current_user_id);
    $stmt_c->execute();
    $rejecting_count = (int)($stmt_c->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt_c->close();
}

# 기준: 오늘부터 뒤로 7일간
$start_date = date('Y-m-d', strtotime('-7 days'));
$end_date   = date('Y-m-d');

# 1. 라운드별 수익 (날짜별 dividend_amount 합계, tx_date 기준)
$sql_round = "SELECT tx_date, pair AS round, SUM(dividend_amount) AS total_dividend
              FROM user_transactions
              WHERE dividend_amount IS NOT NULL
                AND user_id = ?
                AND tx_date BETWEEN ? AND ?
              GROUP BY tx_date, pair
              ORDER BY tx_date ASC, pair ASC";
$stmt_round = $conn->prepare($sql_round);
$stmt_round->bind_param("iss", $current_user_id, $start_date, $end_date);
$stmt_round->execute();
$result_round = $stmt_round->get_result();

# 데이터 구조: 날짜별 라운드별 수익
$round_data = [];
while($row = $result_round->fetch_assoc()) {
    $date = $row['tx_date'];
    $round = $row['round'];
    $amount = round($row['total_dividend'], 2);

    $round_data[$round][$date] = $amount;
}

# 날짜 라벨 (최근 7일)
$date_labels = [];
$period = new DatePeriod(
    new DateTime($start_date),
    new DateInterval('P1D'),
    (new DateTime($end_date))->modify('+1 day')
);
foreach ($period as $dt) {
    $date_labels[] = $dt->format("Y-m-d");
}

# 라운드별 데이터셋 준비
$datasets = [];
$colors = ['#007bff','#28a745','#ffc107','#17a2b8','#dc3545','#6f42c1','#20c997'];
$colorIndex = 0;

foreach ($round_data as $round => $values) {
    $data_points = [];
    foreach ($date_labels as $d) {
        $data_points[] = isset($values[$d]) ? $values[$d] : 0;
    }
    $datasets[] = [
        'label' => $round,
        'data' => $data_points,
        'borderColor' => $colors[$colorIndex % count($colors)],
        'backgroundColor' => 'rgba(0,0,0,0)',
        'fill' => false,
        'tension' => 0.3
    ];
    $colorIndex++;
}

# 2. 월별 매출 (이번달 포함 최근 6개월 = 이전 5개월 + 이번달)
$month_labels = [];
for ($i = 5; $i >= 0; $i--) {
    $month_labels[] = date('Y-m', strtotime("-{$i} months"));
}

$start_month = date('Y-m-01', strtotime('-5 months')); // 5개월 전의 1일
$end_month   = date('Y-m-01', strtotime('+1 month'));  // 다음달 1일 (미만)

$sql_month = "SELECT DATE_FORMAT(tx_date, '%Y-%m') AS ym, SUM(dividend_amount) AS total_sales
              FROM user_transactions
              WHERE dividend_amount IS NOT NULL
                AND user_id = ?
                AND tx_date >= ?
                AND tx_date <  ?
              GROUP BY ym
              ORDER BY ym ASC";
$stmt_month = $conn->prepare($sql_month);
$stmt_month->bind_param("iss", $current_user_id, $start_month, $end_month);
$stmt_month->execute();
$result_month = $stmt_month->get_result();

$month_map = [];
while ($row = $result_month->fetch_assoc()) {
    $month_map[$row['ym']] = round((float)$row['total_sales'], 2);
}
$stmt_month->close();

$month_values = [];
foreach ($month_labels as $ym) {
    $month_values[] = $month_map[$ym] ?? 0;
}

$conn->close();
?>

<section class="content-area">
    <?php if ($rejecting_count > 0): ?>
      <div style="max-width:980px; margin:12px auto 18px; padding:12px 14px; border:1px solid #f1c40f; background:#fff9e6; border-radius:10px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
          <div>
            <div style="font-weight:800; margin-bottom:6px;">⚠️ <?= htmlspecialchars(t('investor.dashboard.rejecting_needed', 'Action required: Rejecting')) ?>: <b><?= (int)$rejecting_count ?></b><?= htmlspecialchars(t('common.count_unit', '')) ?></div>
            <?php if (!empty($rejecting_rows)): ?>
              <ul style="margin:0; padding-left:18px;">
                <?php foreach ($rejecting_rows as $rr): ?>
                  <li style="margin:2px 0;">
                    <b><?= htmlspecialchars($rr['tx_date']) ?></b>
                    <?php if (!empty($rr['reject_reason'])): ?>
                      — <?= htmlspecialchars(mb_strimwidth((string)$rr['reject_reason'], 0, 60, '…', 'UTF-8')) ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
          <div style="white-space:nowrap;">
            <a href="profit_share.php?user_id=<?= (int)$current_user_id ?>&region=korea" class="toggle on" style="display:inline-block; padding:8px 12px; text-decoration:none;"><?= htmlspecialchars(t('investor.dashboard.handle_in_history', 'Handle in History')) ?></a>
          </div>
        </div>
        <div style="margin-top:8px; color:#6b6b6b; font-size:12px;">
          <?= htmlspecialchars(t('investor.dashboard.rejecting_help', 'In History, set to Re-process to clear Rejecting, then press ON again to submit completion.')) ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="gm-wrap">
        <div class="gm-grid">
            <div class="gm-card">
                <div class="gm-card-title"><?= htmlspecialchars(t('investor.dashboard.round_profit_7days', 'Profit by Round (last 7 days)')) ?></div>
                <div class="gm-chart-box"><canvas id="profitChart"></canvas></div>
            </div>
            <div class="gm-card">
                <div class="gm-card-title"><?= htmlspecialchars(t('investor.dashboard.monthly_sales', 'Monthly Sales')) ?></div>
                <div class="gm-chart-box"><canvas id="salesChart"></canvas></div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const dateLabelsRaw = <?= json_encode($date_labels) ?>;
  const dateLabels = (dateLabelsRaw || []).map(d => {
    const s = String(d);
    const parts = s.split('-');
    return parts.length === 3 ? parts[2] : s; // YYYY-MM-DD -> DD
  });
  const roundDatasets = <?= json_encode($datasets) ?>;

  const monthLabels = <?= json_encode($month_labels) ?>;
  const monthValues = <?= json_encode($month_values) ?>;
  // ===== y축 자동 스케일/스텝 계산 =====
  function getMaxFromDatasets(datasets){
    let max = 0;
    (datasets || []).forEach(ds => {
      (ds.data || []).forEach(v => {
        const n = Number(v) || 0;
        if (n > max) max = n;
      });
    });
    return max;
  }

  function niceStep(max){
    // 대략 5칸 정도로 보기 좋은 step 만들기
    if (max <= 0) return 1;
    const raw = max / 5;
    const pow = Math.pow(10, Math.floor(Math.log10(raw)));
    const base = raw / pow;

    let nice;
    if (base <= 1) nice = 1;
    else if (base <= 2) nice = 2;
    else if (base <= 5) nice = 5;
    else nice = 10;

    return nice * pow;
  }

  const profitMax = getMaxFromDatasets(roundDatasets);
  const profitStep = niceStep(profitMax);
  const profitSuggestedMax = profitMax > 0 ? Math.ceil((profitMax * 1.1) / profitStep) * profitStep : 1;

  const salesMax = Math.max(...(monthValues || [0]).map(v => Number(v) || 0));
  const salesStep = niceStep(salesMax);
  const salesSuggestedMax = salesMax > 0 ? Math.ceil((salesMax * 1.1) / salesStep) * salesStep : 1;

  new Chart(document.getElementById('profitChart'), {
    type: 'line',
    data: {
      labels: dateLabels,
      datasets: roundDatasets
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: profitSuggestedMax,
          ticks: {
            stepSize: profitStep,
            callback: (v) => Number(v).toLocaleString()
          }
        }
      }
    }
  });

  new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
      labels: monthLabels,
      datasets: [{
        label: <?= json_encode(t('investor.dashboard.monthly_dividend_sales', 'Monthly dividend sales')) ?>,
        data: monthValues,
        backgroundColor: ['#28a745','#ffc107','#17a2b8','#dc3545','#6f42c1','#20c997']
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: salesSuggestedMax,
          ticks: {
            stepSize: salesStep,
            callback: (v) => Number(v).toLocaleString()
          }
        }
      }
    }
  });
</script>