<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

/*
  gm_revenue_report.php 에서 전달됨:
  $report_date, $report_type, $selected_role
*/

// 날짜 범위 계산
$start_date = $report_date;
$end_date   = $report_date;

switch ($report_type) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week', strtotime($report_date)));
        $end_date   = date('Y-m-d', strtotime('sunday this week', strtotime($report_date)));
        break;
    case 'month':
        $start_date = date('Y-m-01', strtotime($report_date));
        $end_date   = date('Y-m-t', strtotime($report_date));
        break;
    case 'year':
        $start_date = date('Y-01-01', strtotime($report_date));
        $end_date   = date('Y-12-31', strtotime($report_date));
        break;
    case 'day':
    default:
        break;
}

// dividend 테이블에서 합계 조회
$sql = "SELECT 
            SUM(gm1_amount) AS gm1_total,
            SUM(gm2_amount) AS gm2_total,
            SUM(gm3_amount) AS gm3_total,
            SUM(admin_amount) AS admin_total,
            SUM(mastr_amount) AS master_total,
            SUM(agent_amount) AS agent_total,
            SUM(investor_amount) AS investor_total,
            SUM(referral_amount) AS referral_total
        FROM dividend
        WHERE tx_date BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc() ?: [];
$stmt->close();

// 합계 배열
$report_data = [
    'GM1 (TheK_KO)'   => (float)($data['gm1_total'] ?? 0),
    'GM2 (Zayne)'     => (float)($data['gm2_total'] ?? 0),
    'GM3 (ezman)'     => (float)($data['gm3_total'] ?? 0),
    'Administrator'   => (float)($data['admin_total'] ?? 0),
    'Master'          => (float)($data['master_total'] ?? 0),
    'Agent'           => (float)($data['agent_total'] ?? 0),
    'Investor'        => (float)($data['investor_total'] ?? 0),
    'Referral'        => (float)($data['referral_total'] ?? 0),
];

// 역할 필터링
$keys_order = array_keys($report_data);
switch ($selected_role) {
    case 'admin':
        $start_index = array_search('Administrator', $keys_order);
        break;
    case 'master':
        $start_index = array_search('Master', $keys_order);
        break;
    case 'agent':
        $start_index = array_search('Agent', $keys_order);
        break;
    case 'investor':
        $start_index = array_search('Investor', $keys_order);
        break;
    case 'gm':
    default:
        $start_index = 0;
        break;
}
$filtered_data  = array_slice($report_data, (int)$start_index, null, true);
$filtered_total = array_sum($filtered_data);
?>

<style>
  .report-filter{
    display:flex;
    justify-content:center;
    align-items:flex-end;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:20px;
  }
  .report-filter .field{
    display:flex;
    flex-direction:column;
    gap:6px;
  }
  .report-filter .field label{
    margin:0;
    font-weight:700;
  }
  /* 공통 CSS의 width:100% override */
  .report-filter input[type="date"],
  .report-filter select{
    width:220px !important;
    max-width:220px !important;
    display:inline-block !important;
  }
  .report-filter .actions{
    display:flex;
    gap:8px;
    align-items:flex-end;
    padding-bottom:2px;
  }
</style>

<form method="GET" action="gm_revenue_report.php" class="report-filter">
    <div class="field">
        <label for="date">날짜</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($report_date) ?>">
    </div>

    <div class="field">
        <label for="type">유형</label>
        <select name="type" id="type">
            <option value="day"   <?= $report_type === 'day'   ? 'selected' : '' ?>>일별</option>
            <option value="week"  <?= $report_type === 'week'  ? 'selected' : '' ?>>주별</option>
            <option value="month" <?= $report_type === 'month' ? 'selected' : '' ?>>월별</option>
            <option value="year"  <?= $report_type === 'year'  ? 'selected' : '' ?>>연도별</option>
        </select>
    </div>

    <div class="field">
        <label for="role">역할</label>
        <select name="role" id="role">
            <option value="gm"       <?= $selected_role === 'gm'       ? 'selected' : '' ?>>그랜드마스터</option>
            <option value="admin"    <?= $selected_role === 'admin'    ? 'selected' : '' ?>>어드민</option>
            <option value="master"   <?= $selected_role === 'master'   ? 'selected' : '' ?>>마스터</option>
            <option value="agent"    <?= $selected_role === 'agent'    ? 'selected' : '' ?>>에이전트</option>
            <option value="investor" <?= $selected_role === 'investor' ? 'selected' : '' ?>>투자자</option>
        </select>
    </div>

    <div class="actions">
        <button type="submit">조회</button>

        <a id="codepayLink"
           href="codepay_export.php?date=<?= urlencode($report_date) ?>&type=<?= urlencode($report_type) ?>&role=<?= urlencode($selected_role) ?>"
           style="padding:6px 12px; background:#198754; color:#fff; border-radius:4px; text-decoration:none;">
          코드페이 엑셀
        </a>
    </div>
</form>

<div id="report-content" style="padding:20px; font-family:Arial, sans-serif;">
    <h2 style="text-align:center;">Dividend Revenue Report</h2>
    <p style="text-align:center;">기간: <?= htmlspecialchars($start_date) ?> ~ <?= htmlspecialchars($end_date) ?></p>

    <table style="margin:auto; width:70%; border-collapse:collapse; border:1px solid #000;">
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #000; padding:8px;">역할</th>
            <th style="border:1px solid #000; padding:8px;">USDT 합계</th>
        </tr>
        <?php foreach ($filtered_data as $label => $value): ?>
        <tr>
            <td style="border:1px solid #000; padding:8px;"><?= htmlspecialchars($label) ?></td>
            <td style="border:1px solid #000; padding:8px; text-align:right;"><?= number_format((float)$value, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold; background:#e0e0e0;">
            <td style="border:1px solid #000; padding:8px;">Total</td>
            <td style="border:1px solid #000; padding:8px; text-align:right;"><?= number_format((float)$filtered_total, 2) ?></td>
        </tr>
    </table>
</div>

<script>
(function(){
  const dateEl = document.getElementById('date');
  const typeEl = document.getElementById('type');
  const roleEl = document.getElementById('role');
  const linkEl = document.getElementById('codepayLink');
  if(!dateEl || !typeEl || !roleEl || !linkEl) return;

  function syncLink(){
    const date = encodeURIComponent(dateEl.value || '');
    const type = encodeURIComponent(typeEl.value || 'day');
    const role = encodeURIComponent(roleEl.value || 'gm');
    linkEl.href = `codepay_export.php?date=${date}&type=${type}&role=${role}`;
  }

  dateEl.addEventListener('change', syncLink);
  typeEl.addEventListener('change', syncLink);
  roleEl.addEventListener('change', syncLink);
  syncLink();
})();
</script>
