<div class="form-container">
  <h2>EDIT ACCOUNT</h2>
  <?php
require_once __DIR__ . '/includes/i18n.php';
 if (!empty($message)): ?>
    <p style="color:green;"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <form method="POST" id="accountForm">
    <div class="form-group"><label>ID</label><input type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required></div>
    <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
    <div class="form-group"><label>Country</label><input type="text" value="<?= htmlspecialchars($user['country'] ?? '') ?>" readonly></div>
    <div class="form-group"><label>Wallet Address</label><input type="text" name="wallet_address" value="<?= htmlspecialchars($user['wallet_address'] ?? '') ?>">
        <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
        ?>
    </div>
    <div class="form-group"><label>Referral Code</label><input type="text" value="<?= htmlspecialchars($user['referral_code'] ?? '') ?>" readonly></div>

    <h3>📦 <?= t('title.broker_info', 'Broker Info') ?></h3>
    <table class="form-table">
      <thead>
        <tr>
          <th><?= t('table.item','항목') ?></th>
          <th><?= t('table.xm','XM') ?></th>
          <th><?= t('table.ultima','Ultima') ?></th>
          <th><?= t('table.planned1','추가예정1') ?></th>
          <th><?= t('table.planned2','추가예정2') ?></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th><?= t('table.id','ID') ?></th>
          <td><input type="text" id="xm_id" name="xm_id" value="<?= htmlspecialchars($user['xm_id'] ?? '') ?>"></td>
          <td><input type="text" id="ultima_id" name="ultima_id" value="<?= htmlspecialchars($user['ultima_id'] ?? '') ?>"></td>
          <td><input type="text" id="broker1_id" name="broker1_id" value="<?= htmlspecialchars($user['broker1_id'] ?? '') ?>"></td>
          <td><input type="text" id="broker2_id" name="broker2_id" value="<?= htmlspecialchars($user['broker2_id'] ?? '') ?>"></td>
        </tr>
        <tr>
          <th><?= t('table.pw','PW') ?></th>
          <td><input type="text" id="xm_pw" name="xm_pw" value="<?= htmlspecialchars($user['xm_pw'] ?? '') ?>"></td>
          <td><input type="text" id="ultima_pw" name="ultima_pw" value="<?= htmlspecialchars($user['ultima_pw'] ?? '') ?>"></td>
          <td><input type="text" id="broker1_pw" name="broker1_pw" value="<?= htmlspecialchars($user['broker1_pw'] ?? '') ?>"></td>
          <td><input type="text" id="broker2_pw" name="broker2_pw" value="<?= htmlspecialchars($user['broker2_pw'] ?? '') ?>"></td>
        </tr>
        <tr>
          <th><?= t('table.server','Server') ?></th>
          <td><input type="text" id="xm_server" name="xm_server" value="<?= htmlspecialchars($user['xm_server'] ?? '') ?>"></td>
          <td><input type="text" id="ultima_server" name="ultima_server" value="<?= htmlspecialchars($user['ultima_server'] ?? '') ?>"></td>
          <td><input type="text" id="broker1_server" name="broker1_server" value="<?= htmlspecialchars($user['broker1_server'] ?? '') ?>"></td>
          <td><input type="text" id="broker2_server" name="broker2_server" value="<?= htmlspecialchars($user['broker2_server'] ?? '') ?>"></td>
        </tr>
        <tr>
          <th><?= t('table.use_select','사용 선택') ?></th>
          <td><input type="checkbox" id="chk_xm" name="selected_broker[]" value="xm" <?= in_array('xm', explode(',', $user['selected_broker'] ?? '')) ? 'checked' : '' ?>></td>
          <td><input type="checkbox" id="chk_ultima" name="selected_broker[]" value="ultima" <?= in_array('ultima', explode(',', $user['selected_broker'] ?? '')) ? 'checked' : '' ?>></td>
          <td><input type="checkbox" id="chk_broker1" name="selected_broker[]" value="broker1" <?= in_array('broker1', explode(',', $user['selected_broker'] ?? '')) ? 'checked' : '' ?>></td>
          <td><input type="checkbox" id="chk_broker2" name="selected_broker[]" value="broker2" <?= in_array('broker2', explode(',', $user['selected_broker'] ?? '')) ? 'checked' : '' ?>></td>
        </tr>
      </tbody>
    </table>

    <button type="submit"><?= t('btn.update', 'Update') ?></button>
  </form>
</div>

<script>
// 체크박스 제어 함수
function validateCheckbox(idPrefix, checkboxId) {
  const idField = document.getElementById(idPrefix + "_id");
  const pwField = document.getElementById(idPrefix + "_pw");
  const serverField = document.getElementById(idPrefix + "_server");
  const checkbox = document.getElementById(checkboxId);

  checkbox.addEventListener("change", function() {
    if (checkbox.checked) {
      if (!idField.value.trim() || !pwField.value.trim() || !serverField.value.trim()) {
        alert(idPrefix.toUpperCase() + ' ' + <?= json_encode(t('err.platform_fields_required','All fields (ID/PW/Server) are required.')) ?>);
        checkbox.checked = false;
      }
    }
  });
}

// 각 브로커별 체크박스 검증 적용
validateCheckbox("xm", "chk_xm");
validateCheckbox("ultima", "chk_ultima");
validateCheckbox("broker1", "chk_broker1");
validateCheckbox("broker2", "chk_broker2");
</script>