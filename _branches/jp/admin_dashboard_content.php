<?php
// === Chart data builder (language-only / UI-safe change; business logic unchanged) ===
// admin_sales_daily columns: sales_date, sales_amount, user_id
// country source: users.country
$country_sales = [];
$detail_data   = [];

if (isset($conn) && $conn) {
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $sql = "
        SELECT
            COALESCE(NULLIF(u.country, ''), '기타') AS country,
            u.username,
            SUM(d.sales_amount) AS total_sales
        FROM admin_sales_daily d
        JOIN users u ON u.id = d.user_id
        WHERE d.sales_date = ?
          AND u.role = 'master'
        GROUP BY country, u.id, u.username
        ORDER BY country ASC, total_sales DESC
    ";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $yesterday);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $country = $row['country'] ?? '기타';
                $sales   = (float)($row['total_sales'] ?? 0);

                if (!isset($country_sales[$country])) $country_sales[$country] = 0;
                $country_sales[$country] += $sales;

                if (!isset($detail_data[$country])) $detail_data[$country] = [];
                $detail_data[$country][] = [
                    'username' => $row['username'] ?? '',
                    'sales'    => $sales,
                ];
            }
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<?php include 'includes/gm_dashboard_ui.php'; ?>

<div class="gm-wrap">
    <div class="gm-grid">
        <div class="gm-card">
            <div class="gm-card-title"><?= t('chart.admin_sales.title') ?></div>
            <div class="gm-chart-box"><canvas id="donutChart"></canvas></div>
        </div>
        <div class="gm-card">
            <div class="gm-card-title"><?= t('chart.admin_sales.detail_title') ?></div>
            <div class="gm-chart-box"><canvas id="barChart"></canvas></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // PHP 데이터를 JS로 전달
  const countryLabels = <?= json_encode(array_keys($country_sales)) ?>;
  const countrySales  = <?= json_encode(array_values($country_sales)) ?>;
  const detailData    = <?= json_encode($detail_data) ?>;

  // country name translation map (db labels -> ui labels)
  const countryNameMap = <?= json_encode([
    '말레이시아' => t('country.malaysia','Malaysia'),
    '한국' => t('country.korea','Korea'),
    '베트남' => t('country.vietnam','Vietnam'),
    '기타' => t('country.other','Other'),
  ]) ?>;


  // 도넛 차트: <?= t('chart.admin_sales.title') ?> 합계
  new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
      labels: countryLabels.map(c=>countryNameMap[c]||c),
      datasets: [{
        data: countrySales,
        backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8']
      }]
    },
    options: {
      plugins: {
        legend: { position: 'bottom', labels: { color: '#333' } }
      }
    }
  });

  // 막대 차트: <?= t('chart.admin_sales.detail_title') ?>
  const barLabels = [];
  const barData   = [];
  const barColors = [];

  let colorMap = {
    '말레이시아':'#007bff',
    '한국':'#28a745',
    '베트남':'#ffc107',
    '기타':'#17a2b8'
  };

  for (const [country, masters] of Object.entries(detailData)) {
    masters.forEach(m => {
      barLabels.push(m.username + " ("+(countryNameMap[country]||country)+")");
      barData.push(m.sales);
      barColors.push(colorMap[country] || '#999');
    });
  }

  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: barLabels,
      datasets: [{
        label: <?= json_encode(t('chart.yesterday_sales','Yesterday Sales')) ?>,
        data: barData,
        backgroundColor: barColors
      }]
    },
    options: {
      plugins: { legend: { display:false } },
      scales: { 
        y: { beginAtZero:true, title: { display:true, text:<?= json_encode(t('chart.sales_amount_krw','Sales Amount (KRW)')) ?> } },
        x: { title: { display:true, text:<?= json_encode(t('chart.master_country','Master (Country)')) ?> } }
      }
    }
  });
</script>