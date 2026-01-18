<?php
include 'db_connect.php';

/* 1. 나라별 회원 거래 손익 합계 (전일 기준) */
$country_profit = [];
$sql = "SELECT u.country, SUM(t.profit_loss) AS total_profit
        FROM user_transactions t
        JOIN users u ON t.user_id = u.id
        WHERE DATE(t.tx_date) = CURDATE() - INTERVAL 1 DAY
        GROUP BY u.country";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $country_profit[$row['country']] = (float)$row['total_profit'];
    }
    $result->free();
}

/* 2. 추천인별 회원 수 (user_details 활용) */
$referrer_data = [];
$sql = "SELECT u.referrer_id, COUNT(d.user_id) AS total_users
        FROM user_details d
        JOIN users u ON d.user_id = u.id
        GROUP BY u.referrer_id";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $referrer_data[$row['referrer_id']] = (int)$row['total_users'];
    }
    $result->free();
}

/* 3. 배당 데이터 (전일 기준, dividend 테이블 역할별 합계) */
$dividend_data = [];
$sql = "SELECT 
            SUM(gm1_amount) AS gm1,
            SUM(gm2_amount) AS gm2,
            SUM(gm3_amount) AS gm3,
            SUM(admin_amount) AS admin,
            SUM(mastr_amount) AS mastr,
            SUM(agent_amount) AS agent,
            SUM(investor_amount) AS investor,
            SUM(referral_amount) AS referral
        FROM dividend
        WHERE DATE(tx_date) = CURDATE() - INTERVAL 1 DAY";
if ($result = $conn->query($sql)) {
    if ($row = $result->fetch_assoc()) {
        $dividend_data = [
            "GM1"      => (float)$row['gm1'],
            "GM2"      => (float)$row['gm2'],
            "GM3"      => (float)$row['gm3'],
            "Admin"    => (float)$row['admin'],
            "Master"   => (float)$row['mastr'],
            "Agent"    => (float)$row['agent'],
            "Investor" => (float)$row['investor'],
            "Referral" => (float)$row['referral']
        ];
    }
    $result->free();
}

$conn->close();
?>
<section class="content-area">
  <h2 class="section-title">📊 전체 데이터 대시보드</h2>
  <div class="dashboard" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:20px; max-width:1200px; margin:0 auto;">
    <div class="card" style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
      <h3 style="margin-bottom:15px; font-size:18px; color:#333; text-align:center;">나라별 전일 거래 손익 합계</h3>
      <div class="chart-box"><canvas id="countryProfitChart"></canvas></div>
    </div>
    <div class="card" style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
      <h3 style="margin-bottom:15px; font-size:18px; color:#333; text-align:center;">추천인별 회원 수</h3>
      <div class="chart-box"><canvas id="referrerChart"></canvas></div>
    </div>
    <div class="card" style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.05);">
      <h3 style="margin-bottom:15px; font-size:18px; color:#333; text-align:center;">역할별 전일 배당 합계</h3>
      <div class="chart-box"><canvas id="dividendChart"></canvas></div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const countryLabels = <?= json_encode(array_keys($country_profit), JSON_UNESCAPED_UNICODE) ?>;
  const countryValues = <?= json_encode(array_values($country_profit)) ?>;

  const referrerLabels = <?= json_encode(array_keys($referrer_data), JSON_UNESCAPED_UNICODE) ?>;
  const referrerValues = <?= json_encode(array_values($referrer_data)) ?>;

  const dividendLabels = <?= json_encode(array_keys($dividend_data), JSON_UNESCAPED_UNICODE) ?>;
  const dividendValues = <?= json_encode(array_values($dividend_data)) ?>;

  // 나라별 거래 손익 바 차트
  new Chart(document.getElementById('countryProfitChart'), {
    type: 'bar',
    data: {
      labels: countryLabels,
      datasets: [{
        label: '전일 손익 합계',
        data: countryValues,
        backgroundColor:'rgba(54,162,235,0.6)',
        borderColor:'rgba(54,162,235,1)',
        borderWidth:1
      }]
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: { legend: { position: 'bottom' } },
      scales: { y: { beginAtZero: true } }
    }
  });

  // 추천인별 회원 수 도넛 차트
  new Chart(document.getElementById('referrerChart'), {
    type: 'doughnut',
    data: {
      labels: referrerLabels,
      datasets: [{
        data: referrerValues,
        backgroundColor:['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF'],
        hoverOffset:4
      }]
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: { legend: { position: 'bottom' } }
    }
  });

  // 역할별 배당 파이 차트
  new Chart(document.getElementById('dividendChart'), {
    type: 'pie',
    data: {
      labels: dividendLabels,
      datasets: [{
        data: dividendValues,
        backgroundColor:['#FF9F40','#FF6384','#36A2EB','#4BC0C0','#9966FF','#8BC34A','#C9CBCF','#E91E63','#795548'],
        hoverOffset:4
      }]
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: { legend: { position: 'bottom' } }
    }
  });
</script>
