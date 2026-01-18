<h2 style="text-align:center;">라운드별 수익 (최근 7일)</h2>
<div class="chart-box">
    <canvas id="profitChart"></canvas>
</div>

<h2 style="text-align:center;">월별 매출 내역 (최근 6개월)</h2>
<div class="chart-box">
    <canvas id="salesChart"></canvas>
</div>

<script>
const roundLabels = <?= json_encode($round_labels, JSON_UNESCAPED_UNICODE) ?>;
const roundValues = <?= json_encode($round_values) ?>;

const monthLabels = <?= json_encode($month_labels, JSON_UNESCAPED_UNICODE) ?>;
const monthValues = <?= json_encode($month_values) ?>;

new Chart(document.getElementById('profitChart'), {
  type: 'line',
  data: {
    labels: roundLabels,
    datasets: [{
      label: '라운드별 수익',
      data: roundValues,
      borderColor: '#007bff',
      backgroundColor: 'rgba(0,123,255,0.1)',
      fill: true,
      tension: 0.3
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('salesChart'), {
  type: 'bar',
  data: {
    labels: monthLabels,
    datasets: [{
      label: '월별 매출',
      data: monthValues,
      backgroundColor: ['#28a745','#ffc107','#17a2b8','#dc3545','#6f42c1','#20c997']
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});
</script>