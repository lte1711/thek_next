<?php
$deposit_blocked = $deposit_blocked ?? false;
$blocked_tx = $blocked_tx ?? null;
$blocked_reasons = $blocked_reasons ?? [];
$blocked_action_url = $blocked_action_url ?? "investor_withdrawal.php";
$reject_mode = $reject_mode ?? false;
$reject_tx = $reject_tx ?? null;

$display_date = $selected_date ?? date("Y-m-d");
$display_xm = "";
$display_ultima = "";

if ($deposit_blocked && $blocked_tx) {
    $display_date = $blocked_tx['tx_date'] ?? $display_date;
    $display_xm = number_format((float)($blocked_tx['xm_value'] ?? 0), 2);
    $display_ultima = number_format((float)($blocked_tx['ultima_value'] ?? 0), 2);
}
?>
<div class="form-container">
  <h2>거래 입력</h2>

  <?php if ($reject_mode && $reject_tx): ?>
    <div class="alert-box" style="background:#fff3cd; border:1px solid #ffc107; color:#856404; margin-bottom:16px; padding:12px;">
      <div style="font-weight:700; margin-bottom:6px;">⚠️ Rejected Transaction - Amount Edit Only</div>
      <div style="margin-bottom:10px;">
        Transaction ID: <strong><?= htmlspecialchars($reject_tx['id']) ?></strong><br>
        Rejected By: <strong><?= htmlspecialchars($reject_tx['reject_by'] ?? 'N/A') ?></strong><br>
        <?php if (!empty($reject_tx['reject_reason'])): ?>
          Reject Reason: <?= htmlspecialchars($reject_tx['reject_reason']) ?><br>
        <?php else: ?>
          Reject Reason: <em>(사유 없음)</em><br>
        <?php endif; ?>
        <em>입금액만 수정할 수 있습니다. 다른 정보는 변경할 수 없습니다.</em>
      </div>
    </div>

    <form method="POST">
      <input type="hidden" name="reject_update_mode" value="1">
      <input type="hidden" name="tx_id" value="<?= htmlspecialchars($reject_tx['id']) ?>">
      <input type="hidden" name="tx_date" value="<?= htmlspecialchars($reject_tx['tx_date']) ?>">

      <div class="form-group">
        <label>거래소에 입금한 날짜</label>
        <input type="text" value="<?= htmlspecialchars($reject_tx['tx_date']) ?>" disabled style="background:#f5f5f5;">
      </div>

      <table class="data-table">
        <thead>
          <tr><th>XM (원)</th><th>Ultima (원)</th></tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <input type="text" name="xm_value" id="xm_value_reject"
                     value="<?= htmlspecialchars(number_format((float)$reject_tx['xm_value'], 2)) ?>"
                     placeholder="XM 금액 입력">
            </td>
            <td>
              <input type="text" name="ultima_value" id="ultima_value_reject"
                     value="<?= htmlspecialchars(number_format((float)$reject_tx['ultima_value'], 2)) ?>"
                     placeholder="Ultima 금액 입력">
            </td>
          </tr>
        </tbody>
      </table>

      <button type="submit" class="btn" style="background:#ffc107; color:#000;">
        Update Amount
      </button>
    </form>

    <script>
    function formatNumberWithComma(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value === '') { input.value = ''; return; }
        input.value = Number(value).toLocaleString();
    }
    document.getElementById('xm_value_reject').addEventListener('input', function () { formatNumberWithComma(this); });
    document.getElementById('ultima_value_reject').addEventListener('input', function () { formatNumberWithComma(this); });
    document.querySelector('form').addEventListener('submit', function () {
        const xmInput = document.getElementById('xm_value_reject');
        const ultimaInput = document.getElementById('ultima_value_reject');
        xmInput.value = xmInput.value.replace(/,/g, '');
        ultimaInput.value = ultimaInput.value.replace(/,/g, '');
    });
    </script>

  <?php elseif ($deposit_blocked): ?>
    <div class="alert-box alert-danger" style="margin-bottom:16px;">
      <div style="font-weight:700; margin-bottom:6px;">입금 불가</div>
      <div style="margin-bottom:10px;">
        <?= htmlspecialchars(implode(", ", $blocked_reasons)) ?> 상태입니다.<br>
        AI 진행(승인/처리) 완료 전에는 새 입금을 진행할 수 없습니다.
      </div>

      <a class="btn btn-dark"
         href="<?= htmlspecialchars($blocked_action_url) ?>"
         title="미완료(출금/정산/배당) 처리 화면으로 이동">
        미완료 처리하러가기
      </a>
    </div>
  <?php endif; ?>

  <?php if (!$reject_mode): ?>
  <form method="POST">
    <div class="form-group">
      <label>거래소에 입금한 날짜</label>
      <input type="date" name="tx_date" value="<?= htmlspecialchars($display_date) ?>" <?= $deposit_blocked ? 'disabled' : '' ?>>
      <?php if ($deposit_blocked): ?>
        <input type="hidden" name="tx_date" value="<?= htmlspecialchars($display_date) ?>">
      <?php endif; ?>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>XM (원)</th><th>Ultima (원)</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <input type="text" name="xm_value" id="xm_value"
                   placeholder="XM 금액 입력"
                   value="<?= $deposit_blocked ? htmlspecialchars($display_xm) : '' ?>"
                   <?= $deposit_blocked ? 'readonly disabled' : '' ?>>
          </td>
          <td>
            <input type="text" name="ultima_value" id="ultima_value"
                   placeholder="Ultima 금액 입력"
                   value="<?= $deposit_blocked ? htmlspecialchars($display_ultima) : '' ?>"
                   <?= $deposit_blocked ? 'readonly disabled' : '' ?>>
          </td>
        </tr>
      </tbody>
    </table>

    <button type="submit" class="btn" <?= $deposit_blocked ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
      저장하기
    </button>
  </form>
  <?php endif; ?>
</div>

<?php if (!$deposit_blocked): ?>
<script>
function formatNumberWithComma(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value === '') { input.value = ''; return; }
    input.value = Number(value).toLocaleString();
}
document.getElementById('xm_value').addEventListener('input', function () { formatNumberWithComma(this); });
document.getElementById('ultima_value').addEventListener('input', function () { formatNumberWithComma(this); });
document.querySelector('form').addEventListener('submit', function () {
    const xmInput = document.getElementById('xm_value');
    const ultimaInput = document.getElementById('ultima_value');
    xmInput.value = xmInput.value.replace(/,/g, '');
    ultimaInput.value = ultimaInput.value.replace(/,/g, '');
});
</script>
<?php endif; ?>
