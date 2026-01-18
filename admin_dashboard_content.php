<section class="content-area">
    <h2 style="text-align:center;">지역별 마스터 전일 매출</h2>
    <div class="chart-box" style="margin:30px auto; max-width:800px;">
        <canvas id="donutChart"></canvas>
    </div>

    <h2 style="text-align:center;">지역별 마스터 전일 매출 상세 내역</h2>
    <div class="chart-box" style="margin:30px auto; max-width:800px;">
        <canvas id="barChart"></canvas>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // PHP 데이터를 JS로 전달
  const countryLabels = <?= json_encode(array_keys($country_sales)) ?>;
  const countrySales  = <?= json_encode(array_values($country_sales)) ?>;
  const detailData    = <?= json_encode($detail_data) ?>;

  // 도넛 차트: 지역별 마스터 전일 매출 합계
  new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
      labels: countryLabels,
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

  // 막대 차트: 지역별 마스터 전일 매출 상세 내역
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
      barLabels.push(m.username + " ("+country+")");
      barData.push(m.sales);
      barColors.push(colorMap[country] || '#999');
    });
  }

  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: barLabels,
      datasets: [{
        label: '전일 매출',
        data: barData,
        backgroundColor: barColors
      }]
    },
    options: {
      plugins: { legend: { display:false } },
      scales: { 
        y: { beginAtZero:true, title: { display:true, text:'매출 금액 (원)' } },
        x: { title: { display:true, text:'마스터 (지역)' } }
      }
    }
  });
</script>