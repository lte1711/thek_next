<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

// 1) 기준일: 전일
$target_date = date('Y-m-d', strtotime('-1 day'));
$start_dt = $target_date . ' 00:00:00';
$end_dt   = $target_date . ' 23:59:59';

// 2) 전일 데이터 존재 여부 확인
$sql_check = "SELECT COUNT(*) AS cnt FROM dividend WHERE tx_date BETWEEN ? AND ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ss", $start_dt, $end_dt);
$stmt_check->execute();
$res_check = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

// 3) 전일 데이터가 없으면 최신 날짜로 폴백
if (empty($res_check) || (int)$res_check['cnt'] === 0) {
    $sql_latest = "SELECT DATE(MAX(tx_date)) AS latest_date FROM dividend";
    $res_latest = $conn->query($sql_latest)->fetch_assoc();
    if (!empty($res_latest['latest_date'])) {
        $target_date = $res_latest['latest_date'];
        $start_dt = $target_date . ' 00:00:00';
        $end_dt   = $target_date . ' 23:59:59';
    }
}

// 4) 에이전트별 배당 합계 (agent_username 기준)
$sql_agent_sales = "
    SELECT COALESCE(d.agent_username, '기타') AS agent,
           COALESCE(SUM(d.agent_amount), 0) AS total_dividend
    FROM dividend d
    WHERE d.tx_date BETWEEN ? AND ?
    GROUP BY COALESCE(d.agent_username, '기타')
    ORDER BY agent
";
$stmt_sales = $conn->prepare($sql_agent_sales);
$stmt_sales->bind_param("ss", $start_dt, $end_dt);
$stmt_sales->execute();
$result_agent_sales = $stmt_sales->get_result();

$agent_sales = [];
while ($row = $result_agent_sales->fetch_assoc()) {
    $agent_sales[$row['agent']] = (float)$row['total_dividend'];
}
$stmt_sales->close();

// 5) 에이전트별 배당 상세 내역
$sql_agent_detail = "
    SELECT COALESCE(d.agent_username, '기타') AS agent,
           COALESCE(d.agent_amount, 0) AS agent_amount,
           d.tx_date
    FROM dividend d
    WHERE d.tx_date BETWEEN ? AND ?
    ORDER BY agent, d.tx_date
";
$stmt_detail = $conn->prepare($sql_agent_detail);
$stmt_detail->bind_param("ss", $start_dt, $end_dt);
$stmt_detail->execute();
$result_agent_detail = $stmt_detail->get_result();

$detail_data = [];
while ($row = $result_agent_detail->fetch_assoc()) {
    $detail_data[$row['agent']][] = [
        'amount' => (float)$row['agent_amount'],
        'date'   => $row['tx_date']
    ];
}
$stmt_detail->close();
$conn->close();

// 디버그 메시지
$debug_msg = '기준일: ' . $target_date
           . ' / 합계 에이전트 수: ' . count($agent_sales)
           . ' / 상세 에이전트 수: ' . count($detail_data);

// 안전 기본값 처리
if (empty($agent_sales)) {
    $agent_sales = ['데이터 없음' => 0];
}
if (empty($detail_data)) {
    $detail_data = ['데이터 없음' => [['amount' => 0, 'date' => $target_date]]];
}
?>

<h2 style="text-align:center;">에이전트별 배당 합계 (기준일: <?= htmlspecialchars($target_date) ?>)</h2>
<div style="text-align:center; color:#999; font-size:12px; margin-bottom:10px;">
  <?= htmlspecialchars($debug_msg) ?>
</div>
<div class="chart-box" style="margin:30px auto; max-width:800px;">
  <canvas id="agentDividendChart"></canvas>
</div>

<h2 style="text-align:center;">에이전트별 배당 상세 내역 (기준일: <?= htmlspecialchars($target_date) ?>)</h2>
<div class="chart-box" style="margin:30px auto; max-width:800px;">
  <canvas id="agentDetailChart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const agentLabels  = <?= json_encode(array_keys($agent_sales), JSON_UNESCAPED_UNICODE) ?>;
  const agentAmounts = <?= json_encode(array_values($agent_sales), JSON_NUMERIC_CHECK) ?>;
  const detailData   = <?= json_encode($detail_data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) ?>;

  new Chart(document.getElementById('agentDividendChart'), {
    type: 'doughnut',
    data: {
      labels: agentLabels,
      datasets: [{
        data: agentAmounts,
        backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1','#fd7e14','#20c997','#6c757d']
      }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
  });

  const barLabels = [];
  const barData   = [];
  const barColors = [];
  const colorMap = {
    '에이전트A':'#007bff',
    '에이전트B':'#28a745',
    '에이전트C':'#ffc107',
    '기타':'#17a2b8',
    '데이터 없음':'#6c757d'
  };

  for (const [agent, records] of Object.entries(detailData)) {
    records.forEach(r => {
      const d = ('' + r.date).substring(0, 10);
      barLabels.push(agent + " (" + d + ")");
      barData.push(r.amount);
      barColors.push(colorMap[agent] || '#999');
    });
  }

  new Chart(document.getElementById('agentDetailChart'), {
    type: 'bar',
    data: {
      labels: barLabels,
      datasets: [{ label: '배당 금액', data: barData, backgroundColor: barColors }]
    },
    options: {
      plugins: { legend: { display:false } },
      scales: {
        y: { beginAtZero:true, title: { display:true, text:'배당 금액 (원)' } },
        x: { title: { display:true, text:'에이전트 (날짜)' } }
      }
    }
  });
</script>