<?php
// DB 연결
include 'db_connect.php';

$agent_id = $_SESSION['user_id'];

// dividend 테이블과 users 테이블을 연결해서 sponsor_id 조건 걸기
$sql_dividend = "SELECT d.investor_username, SUM(d.investor_amount) AS total_amount
                 FROM dividend d
                 JOIN users u ON d.user_id = u.id
                 WHERE u.role = 'investor'
                   AND u.sponsor_id = ?
                 GROUP BY d.investor_username";
$stmt = $conn->prepare($sql_dividend);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result_dividend = $stmt->get_result();

$labels = [];
$amounts = [];
while ($row = $result_dividend->fetch_assoc()) {
    $labels[] = $row['investor_username'];
    $amounts[] = (float)$row['total_amount'];
}
$stmt->close();
$conn->close();
?>

<h2 style="text-align:center;">내 투자자 배당 합계</h2>
<div class="chart-box" style="margin:30px auto; width:400px; height:400px;">
  <!-- ✅ width/height를 동일하게 지정 -->
  <canvas id="dividendChart" width="400" height="400"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const dividendLabels = <?= json_encode($labels) ?>;
  const dividendAmounts = <?= json_encode($amounts) ?>;

  new Chart(document.getElementById('dividendChart'), {
    type: 'doughnut',
    data: {
      labels: dividendLabels,
      datasets: [{
        data: dividendAmounts,
        backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1','#fd7e14']
      }]
    },
    options: {
      responsive: false,           // ✅ 반응형 끔
      maintainAspectRatio: false,  // ✅ 비율 유지 옵션 끔
      plugins: { legend: { position: 'bottom' } }
    }
  });
</script>