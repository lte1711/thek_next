<?php
// ✅ 안전장치: 세션/i18n 함수 보장
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!function_exists('t')) {
    $i18n_path = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n_path)) {
        require_once $i18n_path;
    } else {
        function t($key, $fallback = null) {
            return $fallback ?? $key;
        }
    }
}

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

<?php include 'includes/gm_dashboard_ui.php'; ?>

<div class="gm-wrap">
    <div class="gm-grid">
        <div class="gm-card">
            <div class="gm-card-title"><?= htmlspecialchars(t('agent.my_investor_dividend_total')) ?></div>
            <?php if (empty($labels)): ?>
                <p style="text-align:center; margin-top:20px; color:#666;">
                    <?= htmlspecialchars(t('agent.no_dividend_data')) ?>
                </p>
            <?php else: ?>
                <div class="gm-chart-box">
                  <canvas id="dividendChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } }
    }
  });
</script>