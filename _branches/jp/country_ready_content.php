<?php
$region = $_GET['region'] ?? 'korea';
$countryLabel = ($region === 'japan') ? 'Japan' : 'Korea';

// Filter parameters
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';
$search_query = $_GET['q'] ?? '';
$is_export_enabled = false; // Ready page does not have export yet
$search_placeholder = 'Username / Pair / Broker';
?>
<div class="country-page-header">
  <h1 class="country-page-title"><?= $countryLabel ?> - Ready for Trading</h1>
</div>

<?php include __DIR__ . '/includes/country_filterbar.php'; ?>

<!-- ✅ 최소 CSS 추가 (모달이 제대로 보이도록) -->
<style>
  .ok-btn{
    padding: 6px 10px;
    border: 1px solid #2e7d32;
    background: #e8f5e9;
    color: #1b5e20;
    border-radius: 6px;
    cursor: pointer;
    margin-right: 6px;
    font-weight: 600;
  }
  .ok-btn:hover{ filter: brightness(0.97); }

  .modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.45);
  }
  .modal .modal-content {
    background: #fff;
    padding: 16px;
    border-radius: 10px;
    width: min(520px, 92vw);
  }
  .modal .close {
    cursor: pointer;
    float: right;
    font-size: 22px;
    line-height: 22px;
  }
</style>

<div class="country-table-wrap">
<table class="country-table country-table--ready">
  <tr>
    <th><?= t('table.date','Date') ?></th><th><?= t('table.username','Username') ?></th><th><?= t('table.xm_account','XM Account') ?></th><th><?= t('table.ultima_account','Ultima Account') ?></th><th><?= t('table.deposit','Deposit') ?></th><th><?= t('table.action','Action') ?></th>
  </tr>
  <?php if (!$result_ready || mysqli_num_rows($result_ready) === 0): ?>
    <tr><td colspan="6"><?= t('msg.no_data') ?></td></tr>
  <?php else: ?>
    <?php while($row = mysqli_fetch_assoc($result_ready)): ?>
    <tr>
      <td><?= htmlspecialchars($row['tx_date'] ?? ($row['settled_date'] ?? '-')) ?></td>
      <td><?= htmlspecialchars($row['username'] ?? '-') ?></td>

      <td>
        <div class="acct-lines">
          <div class="acct-line"><span class="k">id:</span><span class="v"><?= htmlspecialchars($row['xm_id'] ?? '-') ?></span></div>
          <div class="acct-line"><span class="k">pw:</span><span class="v"><?= htmlspecialchars($row['xm_pw'] ?? '-') ?></span></div>
          <div class="acct-line"><span class="k">server:</span><span class="v"><?= htmlspecialchars($row['xm_server'] ?? '-') ?></span></div>
        </div>
      </td>

      <td>
        <div class="acct-lines">
          <div class="acct-line"><span class="k">id:</span><span class="v"><?= htmlspecialchars($row['ultima_id'] ?? '-') ?></span></div>
          <div class="acct-line"><span class="k">pw:</span><span class="v"><?= htmlspecialchars($row['ultima_pw'] ?? '-') ?></span></div>
          <div class="acct-line"><span class="k">server:</span><span class="v"><?= htmlspecialchars($row['ultima_server'] ?? '-') ?></span></div>
        </div>
      </td>

      <td>
        <div>xm: ₩<?= number_format((float)($row['xm_value'] ?? 0), 2) ?></div>
        <div>ultima: ₩<?= number_format((float)($row['ultima_value'] ?? 0), 2) ?></div>
      </td>

      <td>
        <?php if (!empty($row['reject_by'])): ?>
          <span class="rejected-label">Rejecting</span>
        <?php elseif (($row['settle_chk'] ?? '') == 2): ?>
          <button class="confirm-btn"
            onclick="openReasonModal('<?= htmlspecialchars($row['reject_reason'] ?? '-') ?>',
                                     '<?= htmlspecialchars($row['reject_by'] ?? '-') ?>',
                                     '<?= htmlspecialchars($row['reject_date'] ?? '-') ?>')">
            Rejected
          </button>
        <?php else: ?>
          <?php $pid = (int)($row['progressing_id'] ?? 0); ?>
          <?php $is_external_ready = ((int)($row['external_done_chk'] ?? 0) === 1); ?>
          <?php if ($pid > 0 && $is_external_ready): ?>
            <button class="ok-btn" onclick="submitOk(<?= $pid ?>, <?= (int)($row['user_id'] ?? 0) ?>, <?= (int)($row['tx_id'] ?? 0) ?>)">OK</button>
          <?php elseif ($pid > 0 && !$is_external_ready): ?>
            <button class="ok-btn" disabled style="opacity:0.45; cursor:not-allowed;" title="<?= htmlspecialchars(t('hint.ok_only_when_external_done','OK is available only when external_done_chk=1.')) ?>">OK</button>
          <?php else: ?>
            <button class="ok-btn" disabled style="opacity:0.45; cursor:not-allowed;" title="<?= htmlspecialchars(t('hint.progressing_id_missing','Cannot find korea_progressing ID.')) ?>">OK</button>
          <?php endif; ?>
          <button class="reject-btn" onclick="openRejectModal(<?= (int)($row['user_id'] ?? 0) ?>, <?= (int)($row['ready_id'] ?? 0) ?>, <?= (int)($row['tx_id'] ?? 0) ?>, <?= $pid ?>)">Reject</button>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php endif; ?>
</table>

<?php include __DIR__ . '/includes/country_pagination.php'; ?>

</div>

<!-- Reject Reason Modal -->
<div id="reasonModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeReasonModal()">&times;</span>
    <h3>Reject Details</h3>
    <p><strong>Reason:</strong> <span id="reasonText"></span></p>
    <p><strong>By:</strong> <span id="reasonBy"></span></p>
    <p><strong>Date:</strong> <span id="reasonDate"></span></p>
  </div>
</div>

<!-- Reject Input Modal -->
<div id="rejectModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeRejectModal()">&times;</span>
    <h3>Enter Reject Reason</h3>
    <form id="rejectForm">
      <input type="hidden" id="rejectUserId" name="user_id">
      <input type="hidden" id="rejectReadyId" name="ready_id">
      <input type="hidden" id="rejectTxId" name="tx_id">
      <input type="hidden" id="rejectProgId" name="progressing_id">
      <textarea id="rejectReason" name="reason" placeholder="Enter reason" style="width:100%;height:80px;"></textarea>
      <br>
      <button type="button" onclick="submitReject()">OK</button>
    </form>
  </div>
</div>

<script>
const REGION = <?= json_encode($region) ?>;

function openReasonModal(reason, by, date) {
  document.getElementById('reasonText').innerText = reason;
  document.getElementById('reasonBy').innerText   = by;
  document.getElementById('reasonDate').innerText = date;
  document.getElementById('reasonModal').style.display = 'flex';
}
function closeReasonModal() {
  document.getElementById('reasonModal').style.display = 'none';
}

function openRejectModal(userId, readyId, txId, progId) {
  document.getElementById('rejectUserId').value = userId;
  document.getElementById('rejectReadyId').value = readyId || 0;
  document.getElementById('rejectTxId').value = txId || 0;
  document.getElementById('rejectProgId').value = progId || 0;
  document.getElementById('rejectReason').value = '';
  document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
  document.getElementById('rejectModal').style.display = 'none';
}

function submitReject() {
  const userId = document.getElementById('rejectUserId').value;
  const readyId = document.getElementById('rejectReadyId').value;
  const txId   = document.getElementById('rejectTxId').value;
  const progId = document.getElementById('rejectProgId').value;
  const reason = document.getElementById('rejectReason').value.trim();
  if (!reason) { alert("<?= t('js.msg.27062d8758') ?>"); return; }

  fetch('reject_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `user_id=${encodeURIComponent(userId)}&ready_id=${encodeURIComponent(readyId)}&tx_id=${encodeURIComponent(txId)}&progressing_id=${encodeURIComponent(progId)}&reason=${encodeURIComponent(reason)}&region=${encodeURIComponent(REGION)}`
  })
  .then(res => res.text())
  .then(msg => {
    alert(msg);
    closeRejectModal();
    window.location.reload();
  })
  .catch(err => alert(<?= json_encode(t('error.occurred_prefix','Error occurred: ')) ?> + err));
}

function submitOk(progressingId, userId, txId){
  if (!progressingId || !userId || !txId) { alert("<?= t('js.msg.8f77326616') ?>"); return; }
  if (!confirm(<?= json_encode(t('settlement.confirm_ok_processing','Proceed with OK processing?')) ?>)) return;

  fetch('ok_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ progressing_id: progressingId, user_id: userId, tx_id: txId, region: REGION })
  })
  .then(res => res.text())
  .then(t => {
    let data = null;
    try { data = JSON.parse(t); } catch(e) {}
    if (!data) { alert('Server response: ' + t); return; }
    alert(data.message || (data.success ? <?= json_encode(t('msg.ok_processed','OK processed successfully')) ?> : <?= json_encode(t('error.process_failed','Process failed')) ?>));
    if (data.success) {
      window.location.reload();
    }
  })
  .catch(err => alert(<?= json_encode(t('error.occurred_prefix','Error occurred: ')) ?> + err));
}
</script>