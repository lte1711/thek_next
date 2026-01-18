<?php
$pending_codes = $pending_codes ?? [];
?>

<?php if (empty($pending_codes)): ?>
<script>
  alert(<?= json_encode(t('withdrawal.alert.not_ready')) ?>);
  location.href = "investor_profit_share.php";
</script>
<?php exit; ?>
<?php endif; ?>

<form method="POST" onsubmit="beforeSubmit()">
  <input type="hidden" name="mode" value="withdraw">

  <!-- 거래 선택 -->
  <table class="data-table">
    <tr>
      <th><?= t('withdrawal.select.deposit_history') ?></th>
      <td>
        <select name="id" required>
          <?php foreach ($pending_codes as $pc): ?>
            <option value="<?= (int)$pc['id'] ?>">
              <?= t('common.id') ?> <?= $pc['id'] ?>
              (XM <?= number_format($pc['xm_value'],0) ?> /
               Ultima <?= number_format($pc['ultima_value'],0) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </td>
    </tr>
  </table>

  <!-- 출금 입력 -->
  <table class="data-table">
    <tr>
      <th><?= t('withdrawal.field.xm') ?></th>
      <td>
        <input type="text" name="xm_total"
               oninput="formatInput(this)"
               placeholder="0">
      </td>
    </tr>
    <tr>
      <th><?= t('withdrawal.field.ultima') ?></th>
      <td>
        <input type="text" name="ultima_total"
               oninput="formatInput(this)"
               placeholder="0">
      </td>
    </tr>
  </table>

  <button type="submit" class="btn"><?= t('withdrawal.btn.save') ?></button>
</form>

<script>
/* ===========================
   숫자 표시 공통 유틸
=========================== */
function onlyNumber(v) {
  return v.replace(/[^\d]/g, '');
}
function formatComma(v) {
  if (!v) return '';
  return Number(v).toLocaleString('en-US');
}

/* 입력 중에도 콤마 유지 */
function formatInput(el) {
  let raw = onlyNumber(el.value);
  el.value = formatComma(raw);
}

/* 서버 전송 전 콤마 제거 */
function beforeSubmit() {
  document.querySelectorAll("input[name='xm_total'], input[name='ultima_total']")
    .forEach(el => {
      el.value = onlyNumber(el.value);
    });
}
</script>
