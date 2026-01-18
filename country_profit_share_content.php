<h2>P / S - Partner Daily Settlement</h2>

<div style="margin:12px 0 18px;">
  <form method="GET" class="form-inline" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <input type="hidden" name="region" value="<?= htmlspecialchars($region) ?>">

    <label style="font-weight:600;">Select Date:</label>
    <input type="date" name="partner_date" value="<?= htmlspecialchars($partner_date ?? '') ?>">

    <button type="submit">Search</button>

    <?php if (!empty($partner_is_settled)): ?>
      <button type="button" disabled style="opacity:0.6; cursor:not-allowed;">Settled</button>
      <span style="font-size:12px; opacity:0.8;">
        <?= !empty($partner_settled_at) ? "(Settled at: " . htmlspecialchars($partner_settled_at) . ")" : "" ?>
      </span>
    <?php else: ?>
      <button type="button" onclick="confirmPartnerSettlement(event)">Settle</button>
    <?php endif; ?>
  </form>

  <table style="margin-top:12px;">
    <tr>
      <th>Name</th>
      <th>Total Revenue</th>
      <th style="color:#d33;">profit(20%)</th>
    </tr>

    <?php if (!empty($dividend_rows) && is_array($dividend_rows)): ?>
      <?php foreach ($dividend_rows as $row): ?>
        <?php
          // Name은 gm2_username을 기본으로 사용 (기존 화면: Zayne)
          $name = $row['gm2_username'] ?? 'Zayne';
          // 같은 날짜에 여러 건일 때 구분을 위해 시간 표기
          $time_suffix = '';
          if (!empty($row['tx_date'])) {
            $time_suffix = ' (' . date('H:i:s', strtotime($row['tx_date'])) . ')';
          }
          $row_total = (float)($row['row_total_revenue'] ?? 0);
          $row_profit20 = (float)($row['row_profit20'] ?? 0);
        ?>
        <tr>
          <td><?= htmlspecialchars($name . $time_suffix) ?></td>
          <td><?= number_format($row_total, 2) ?></td>
          <td><?= number_format($row_profit20, 2) ?></td>
        </tr>
      <?php endforeach; ?>

      <!-- ✅ Total 행: 각 열 합계 -->
      <tr>
        <td><strong>Total</strong></td>
        <td><strong><?= number_format((float)($total_revenue_sum ?? 0), 2) ?></strong></td>
        <td><strong><?= number_format((float)($profit20_sum ?? 0), 2) ?></strong></td>
      </tr>
    <?php else: ?>
      <tr>
        <td colspan="3" style="text-align:center; padding:14px; opacity:0.7;">
          선택한 날짜에 데이터가 없습니다.
        </td>
      </tr>
    <?php endif; ?>

  </table>
</div>

<script>
function confirmPartnerSettlement(e) {
  if (e) e.preventDefault();
  const isSettled = <?= !empty($partner_is_settled) ? 'true' : 'false' ?>;
  if (isSettled) { alert('이미 정산 완료된 날짜입니다.'); return; }
  if (!confirm('정산을 진행하시겠습니까?')) return;

  const settleDate = "<?= htmlspecialchars($partner_date ?? '') ?>";

  fetch('gm_settle_confirm.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'settle_date=' + encodeURIComponent(settleDate)
  })
  .then(async (res) => {
    const text = await res.text();
    if (!res.ok) throw new Error(text || '정산 실패');
    alert(text);
    location.reload();
  })
  .catch((err) => alert(err.message));
}
</script>
