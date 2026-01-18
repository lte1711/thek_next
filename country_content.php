<?php if (!isset($result_completed)) { $result_completed = false; } ?>
<h2><?= ucfirst($region) ?> - Ready for Trading</h2>
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

  /* ✅ Progressing status icons */
  .status-ok { color:#22c55e; font-weight:700; }
  .status-no { color:#ff2d6f; font-weight:700; }
  .status-progress { color:#0ea5e9; font-weight:700; }

</style>

<table>
  <tr>
    <th>Date</th><th>Username</th><th>XM Account</th><th>Ultima Account</th><th>Deposit</th><th>Action</th>
  </tr>
  <?php if (mysqli_num_rows($result_ready) === 0): ?>
    <tr><td colspan="6">No data available.</td></tr>
  <?php endif; ?>
  <?php while($row = mysqli_fetch_assoc($result_ready)): ?>
  <tr>
    <!-- Date -->
    <td><?= htmlspecialchars($row['tx_date'] ?? ($row['settled_date'] ?? '-')) ?></td>

    <!-- Username -->
    <td><?= htmlspecialchars($row['username'] ?? '-') ?></td>

    <!-- XM Account -->
    <td class="platform-box">
      id: <?= htmlspecialchars($row['xm_id'] ?? '-') ?><br>
      pw: <?= htmlspecialchars($row['xm_pw'] ?? '-') ?><br>
      server: <?= htmlspecialchars($row['xm_server'] ?? '-') ?>
    </td>

    <!-- Ultima Account -->
    <td class="platform-box">
      id: <?= htmlspecialchars($row['ultima_id'] ?? '-') ?><br>
      pw: <?= htmlspecialchars($row['ultima_pw'] ?? '-') ?><br>
      server: <?= htmlspecialchars($row['ultima_server'] ?? '-') ?>
    </td>

    <!-- Deposit -->
    <td class="platform-box">
      xm: ₩<?= number_format((float)($row['xm_value'] ?? 0), 2) ?><br>
      ultima: ₩<?= number_format((float)($row['ultima_value'] ?? 0), 2) ?>
    </td>

    <!-- Action (OK / Reject) -->
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
        <button class="ok-btn"
          onclick="submitOk(<?= (int)($row['user_id'] ?? 0) ?>, <?= (int)($row['tx_id'] ?? 0) ?>)">
          OK
        </button>
        <button class="reject-btn"
          onclick="openRejectModal(<?= (int)($row['user_id'] ?? 0) ?>, <?= (int)($row['ready_id'] ?? 0) ?>, <?= (int)($row['tx_id'] ?? 0) ?>)">
          Reject
        </button>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
</table>


<!-- ✅ Completed Transactions -->
<div style="margin-top:22px;">
  <h2><?= ucfirst($region) ?> - Completed Transactions</h2>

  <?php if (!isset($result_completed) || !$result_completed): ?>
    <div style="padding:10px 0; color:#666;">No completed transactions.</div>
  <?php elseif (mysqli_num_rows($result_completed) === 0): ?>
    <div style="padding:10px 0; color:#666;">No completed transactions.</div>
  <?php else: ?>
    <table>
      <tr>
        <th>Date</th>
        <th>Username</th>
        <th>XM Account</th>
        <th>Ultima Account</th>
        <th>Deposit</th>
        <th>Status</th>
      </tr>

      <?php while ($c = mysqli_fetch_assoc($result_completed)): ?>
      <tr>
        <td><?= htmlspecialchars($c['tx_date'] ?? ($c['settled_date'] ?? '-')) ?></td>
        <td><?= htmlspecialchars($c['username'] ?? '-') ?></td>

        <td style="text-align:center; white-space:pre-line;">
          id: <?= htmlspecialchars($c['xm_id'] ?? '-') ?>

          pw: <?= htmlspecialchars($c['xm_pw'] ?? '-') ?>

          server: <?= htmlspecialchars($c['xm_server'] ?? '-') ?>
        </td>

        <td style="text-align:center; white-space:pre-line;">
          id: <?= htmlspecialchars($c['ultima_id'] ?? '-') ?>

          pw: <?= htmlspecialchars($c['ultima_pw'] ?? '-') ?>

          server: <?= htmlspecialchars($c['ultima_server'] ?? '-') ?>
        </td>

        <td style="text-align:center; white-space:pre-line;">
          xm: ₩<?= number_format((float)($c['xm_value'] ?? 0), 2) ?>

          ultima: ₩<?= number_format((float)($c['ultima_value'] ?? 0), 2) ?>
        </td>

        <td style="text-align:center;">
          <span style="display:inline-block; padding:4px 8px; border-radius:6px; border:1px solid #1b5e20; background:#e8f5e9; color:#1b5e20; font-weight:600;">
            <?= htmlspecialchars($c['status'] ?? 'approved') ?>
          </span>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  <?php endif; ?>
</div>

<!-- ✅ (3번) Partner Daily Settlement (Malaysia only) - by date (USDT) -->
<div style="margin:28px 0 18px;">
  <h2 style="margin-bottom:10px;">Partner Daily Settlement</h2>

  <form method="GET" class="form-inline" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
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
      <th>USDT</th>
      <th>Ratio (%)</th>
    </tr>
    <?php
      $amt = (float)($malaysia_amount ?? 0);
      $pct = ($amt > 0) ? 100 : 0;
      // ✅ Display label override (requested): show "Zayne" in the Name column
      // This keeps all settlement/data logic unchanged; it only affects the UI label.
      $malaysia_display_name = 'Zayne';
    ?>
    <tr>
      <td><?= htmlspecialchars($malaysia_display_name ?? ($malaysia_name ?? '-')) ?></td>
      <td><?= number_format($amt, 2) ?></td>
      <td><?= number_format($pct, 2) ?>%</td>
    </tr>
    <tr>
      <td><strong>Total</strong></td>
      <td><strong><?= number_format($amt, 2) ?></strong></td>
      <td><strong><?= number_format($pct, 2) ?>%</strong></td>
    </tr>
  </table>
</div>
</div>

<script>
function confirmPartnerSettlement(e) {
  if (e) e.preventDefault();
  const isSettled = <?= !empty($partner_is_settled) ? 'true' : 'false' ?>;
  if (isSettled) { alert('이미 정산 완료된 날짜입니다.'); return; }
  if (!confirm('정산을 진행하시겠습니까?')) return;
  const settleDate = "<?= htmlspecialchars($partner_date ?? '') ?>";

  // ✅ GM 정산 저장 호출 (기존 로직 재사용)
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

<h2><?= ucfirst($region) ?> - Progressing</h2>
<table>
  <tr>
    <th>Date</th>
    <th>Pair</th>
    <th>Deposit</th>
    <th>Withdrawal</th>
    <th>P/S(Profit Share)</th>
    <th>Note</th>
    <th>Settled</th>
  </tr>

      <?php if (!$result_progress || mysqli_num_rows($result_progress) === 0): ?>
        <tr><td colspan="7">No data available.</td></tr>
      <?php else: ?>
        <?php while($row = mysqli_fetch_assoc($result_progress)): ?>
    <?php
    $is_first_trade = false;
    $deposit    = (float)($row['deposit_status'] ?? 0);
    $withdrawal = (float)($row['withdrawal_status'] ?? 0);
    $pl         = (float)($row['profit_loss'] ?? 0);

    // Deposit 체크
    $deposit_chk = ($deposit > 0) ? 'V' : 'X';

    // Withdrawal 체크
    $withdrawal_chk = ($withdrawal > 0) ? 'V' : 'X';

    // P/L 체크
    if ($withdrawal <= 0) {
        // Withdrawal이 0이면 P/L은 무조건 X
        $pl_chk = 'X';
    } else {
        $pl_chk = ($pl != 0) ? 'V' : 'X';
    }
    ?>
    <tr>
        <td><?= htmlspecialchars($row['tx_date'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['pair'] ?? '-') ?></td>

        <!-- Deposit -->
        <td style="text-align:center; font-weight:bold;">
            <?= $deposit_chk ?>
        </td>

        <!-- Withdrawal -->
        <td style="text-align:center; font-weight:bold;">
            <?= $withdrawal_chk ?>
        </td>

        <!-- P/L -->
        <td style="text-align:center; font-weight:bold;">
            <?= $pl_chk ?>
        </td>

        <!-- ✅ notes가 있으면 notes, 없으면 first trade/ - -->
        <td>
          <?= htmlspecialchars(
                trim((string)($row['notes'] ?? '')) !== ''
                  ? $row['notes']
                  : ($is_first_trade ? 'First trade' : '-')
              ) ?>
        </td>

        <td><?= htmlspecialchars($row['settled_by'] ?? '-') ?></td>
    </tr>
    <?php endwhile; ?>
  <?php endif; ?>
</table>




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
      <textarea id="rejectReason" name="reason" placeholder="Enter reason" style="width:100%;height:80px;"></textarea>
      <br>
      <button type="button" onclick="submitReject()">OK</button>
    </form>
  </div>
</div>

<script>
function openReasonModal(reason, by, date) {
  document.getElementById('reasonText').innerText = reason;
  document.getElementById('reasonBy').innerText   = by;
  document.getElementById('reasonDate').innerText = date;
  document.getElementById('reasonModal').style.display = 'flex';
}
function closeReasonModal() {
  document.getElementById('reasonModal').style.display = 'none';
}

function openRejectModal(userId, readyId, txId) {
  document.getElementById('rejectUserId').value = userId;
  document.getElementById('rejectReadyId').value = readyId || 0;
  document.getElementById('rejectTxId').value = txId || 0;
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
  const reason = document.getElementById('rejectReason').value.trim();
  if (!reason) { alert("Please enter a reason."); return; }

  fetch('reject_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `user_id=${encodeURIComponent(userId)}&ready_id=${encodeURIComponent(readyId)}&tx_id=${encodeURIComponent(txId)}&reason=${encodeURIComponent(reason)}&region=<?= $region ?>`
  })
  .then(res => res.text())
  .then(msg => { alert(msg); closeRejectModal(); location.reload(); })
  .catch(err => alert("Error occurred: " + err));
}



function submitOk(userId, txId){
  if (!userId || !txId) { alert('Invalid data.'); return; }
  if (!confirm('OK 처리하시겠습니까? (봇 진행중으로 전환)')) return;

  fetch('ok_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      user_id: userId,
      tx_id: txId,
      region: 'korea'
    })
  })
  .then(res => res.text())
  .then(t => {
    // JSON이 아닐 수도 있으니 안전 처리
    let data = null;
    try { data = JSON.parse(t); } catch(e) {}
    if (!data) { alert('Server response: ' + t); return; }
    alert(data.message || (data.success ? 'OK 처리 완료' : '처리 실패'));
    if (data.success) location.reload();
  })
  .catch(err => alert('Error occurred: ' + err));
}

</script>