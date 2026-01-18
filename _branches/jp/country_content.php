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
    <th><?= t('table.date','Date') ?></th><th><?= t('table.username','Username') ?></th><th><?= t('table.xm_account','XM Account') ?></th><th><?= t('table.ultima_account','Ultima Account') ?></th><th><?= t('table.deposit','Deposit') ?></th><th><?= t('table.action','Action') ?></th>
  </tr>
  <?php if (mysqli_num_rows($result_ready) === 0): ?>
    <tr><td colspan="6"><?= t('msg.no_data') ?></td></tr>
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
        <th><?= t('table.date','Date') ?></th>
        <th><?= t('table.username','Username') ?></th>
        <th><?= t('table.xm_account','XM Account') ?></th>
        <th><?= t('table.ultima_account','Ultima Account') ?></th>
        <th><?= t('table.deposit','Deposit') ?></th>
        <th><?= t('table.status','Status') ?></th>
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
  <h2 style="margin-bottom:10px;"><?= t('title.settlement.partner_daily','Partner Daily Settlement') ?></h2>

  <form method="GET" class="form-inline" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <label style="font-weight:600;"><?= t('label.select_date','Select Date:') ?></label>
    <input type="text" class="js-date" name="partner_date" value="<?= htmlspecialchars($partner_date ?? '') ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD" style="width:165px;">

    <button type="submit"><?= t('btn.search','Search') ?></button>

    <?php if (!empty($partner_is_settled)): ?>
      <button type="button" disabled style="opacity:0.6; cursor:not-allowed;">
        <?= t('partner.settlement.settled','Settled') ?>
      </button>
      <span style="font-size:12px; opacity:0.8;">
        <?= !empty($partner_settled_at) ? "(" . t('partner.settlement.settled_at', 'Settled at') . ": " . htmlspecialchars($partner_settled_at) . ")" : "" ?>
      </span>
    <?php else: ?>
      <button type="button" onclick="confirmPartnerSettlement(event)"><?= t('btn.settle','Settle') ?></button>
    <?php endif; ?>
  </form>

  <table style="margin-top:12px;">
    <tr>
      <th><?= t('table.name','Name') ?></th>
      <th><?= t('table.usdt','USDT') ?></th>
      <th><?= t('table.ratio_pct','Ratio (%)') ?></th>
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
      <td><strong><?= t('common.total','Total') ?></strong></td>
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
  if (isSettled) { alert(<?= json_encode(t('settlement.already_completed')) ?>); return; }
  if (!confirm(<?= json_encode(t('settlement.confirm_proceed')) ?>)) return;
  const settleDate = "<?= htmlspecialchars($partner_date ?? '') ?>";

  // ✅ GM 정산 저장 호출 (기존 로직 재사용)
  fetch('gm_settle_confirm.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'settle_date=' + encodeURIComponent(settleDate)
  })
  .then(async (res) => {
    const text = await res.text();
    if (!res.ok) throw new Error(text || 'Settlement failed');
    alert(text);
    location.reload();
  })
  .catch((err) => alert(err.message));
}
</script>

<h2><?= ucfirst($region) ?> - Progressing</h2>
<table>
  <tr>
    <th><?= t('table.date','Date') ?></th>
    <th><?= t('table.pair','Pair') ?></th>
    <th><?= t('table.deposit','Deposit') ?></th>
    <th><?= t('table.withdrawal','Withdrawal') ?></th>
    <th><?= t('table.profit_share','P/S(Profit Share)') ?></th>
    <th><?= t('table.note','Note') ?></th>
    <th><?= t('table.settled','Settled') ?></th>
  </tr>

      <?php if (!$result_progress || mysqli_num_rows($result_progress) === 0): ?>
        <tr><td colspan="7"><?= t('msg.no_data') ?></td></tr>
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
  if (!reason) { alert(<?= json_encode(t('settlement.enter_reason')) ?>); return; }

  fetch('reject_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `user_id=${encodeURIComponent(userId)}&ready_id=${encodeURIComponent(readyId)}&tx_id=${encodeURIComponent(txId)}&reason=${encodeURIComponent(reason)}&region=<?= $region ?>`
  })
  .then(res => res.text())
  .then(msg => { alert(msg); closeRejectModal(); location.reload(); })
  .catch(err => alert(<?= json_encode(t('error.occurred_prefix','Error occurred: ')) ?> + err));
}



function submitOk(userId, txId){
  if (!userId || !txId) { alert(<?= json_encode(t('error.invalid_data')) ?>); return; }
  if (!confirm(<?= json_encode(t('settlement.confirm_ok_processing')) ?>)) return;

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
    if (!data) { alert(<?= json_encode(t('error.server_response_prefix','Server response: ')) ?> + t); return; }
    alert(data.message || (data.success ? <?= json_encode(t('msg.ok_processed','OK processed successfully')) ?> : <?= json_encode(t('error.process_failed','Process failed')) ?>));
    if (data.success) location.reload();
  })
  .catch(err => alert(<?= json_encode(t('error.occurred_prefix','Error occurred: ')) ?> + err));
}

</script>

<script>
(function(){
  // language + labels from server (t())
  const LANG = <?= json_encode($_GET['lang'] ?? ($_SESSION['lang'] ?? 'ko')) ?>;
  const T = {
    today: <?= json_encode(t('common.today', 'Today')) ?>,
    clear: <?= json_encode(t('common.clear', 'Clear')) ?>,
    months: <?= json_encode([
      t('month.1','1'), t('month.2','2'), t('month.3','3'), t('month.4','4'),
      t('month.5','5'), t('month.6','6'), t('month.7','7'), t('month.8','8'),
      t('month.9','9'), t('month.10','10'), t('month.11','11'), t('month.12','12')
    ]) ?>,
    weekdays: <?= json_encode([
      t('weekday.sun','Sun'), t('weekday.mon','Mon'), t('weekday.tue','Tue'),
      t('weekday.wed','Wed'), t('weekday.thu','Thu'), t('weekday.fri','Fri'), t('weekday.sat','Sat')
    ]) ?>,
    yearSuffix: <?= json_encode(t('unit.year_suffix','')) ?>
  };

  function pad2(n){ return (n<10?'0':'')+n; }
  function fmtDate(d){ return d.getFullYear()+'-'+pad2(d.getMonth()+1)+'-'+pad2(d.getDate()); }
  function fmtMonth(d){ return d.getFullYear()+'-'+pad2(d.getMonth()+1); }

  // single shared popup
  let popup = null;
  let activeInput = null;
  let mode = 'date'; // 'date' | 'month'
  let viewYear = null;
  let viewMonth = null;

  function ensurePopup(){
    if (popup) return popup;

    popup = document.createElement('div');
    popup.className = 'simple-picker';
    popup.style.cssText = [
      'position:absolute',
      'z-index:99999',
      'background:#fff',
      'border:1px solid rgba(0,0,0,0.12)',
      'border-radius:10px',
      'box-shadow:0 10px 30px rgba(0,0,0,0.15)',
      'padding:10px',
      'width:260px',
      'font-family:system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif',
      'user-select:none'
    ].join(';');

    popup.innerHTML = `
      <div class="sp-head" style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
        <button type="button" class="sp-prev" style="border:none;background:#f2f4f7;border-radius:8px;padding:6px 10px;cursor:pointer;">‹</button>
        <div class="sp-title" style="font-weight:700; text-align:center; flex:1;"></div>
        <button type="button" class="sp-next" style="border:none;background:#f2f4f7;border-radius:8px;padding:6px 10px;cursor:pointer;">›</button>
      </div>
      <div class="sp-body"></div>
      <div class="sp-foot" style="display:flex; justify-content:space-between; gap:8px; margin-top:10px;">
        <button type="button" class="sp-clear" style="border:none;background:#fff;color:#555;cursor:pointer;">${T.clear}</button>
        <button type="button" class="sp-today" style="border:none;background:#2563eb;color:#fff;border-radius:8px;padding:6px 10px;cursor:pointer;">${T.today}</button>
      </div>
    `;

    document.body.appendChild(popup);

    // close on outside click / esc
    document.addEventListener('mousedown', (e) => {
      if (!popup || !activeInput) return;
      if (popup.contains(e.target) || e.target === activeInput) return;
      hide();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') hide();
    });

    popup.querySelector('.sp-prev').addEventListener('click', () => {
      if (mode === 'month') { viewYear -= 1; }
      else {
        viewMonth -= 1;
        if (viewMonth < 0) { viewMonth = 11; viewYear -= 1; }
      }
      render();
    });
    popup.querySelector('.sp-next').addEventListener('click', () => {
      if (mode === 'month') { viewYear += 1; }
      else {
        viewMonth += 1;
        if (viewMonth > 11) { viewMonth = 0; viewYear += 1; }
      }
      render();
    });

    popup.querySelector('.sp-clear').addEventListener('click', () => {
      if (activeInput) activeInput.value = '';
      hide();
    });

    popup.querySelector('.sp-today').addEventListener('click', () => {
      const now = new Date();
      if (activeInput) activeInput.value = (mode === 'month') ? fmtMonth(now) : fmtDate(now);
      hide();
    });

    return popup;
  }

  function parseValue(val){
    if (!val) return null;
    const parts = val.split('-').map(x => parseInt(x,10));
    if (parts.length === 2 && !Number.isNaN(parts[0]) && !Number.isNaN(parts[1])) {
      return new Date(parts[0], parts[1]-1, 1);
    }
    if (parts.length === 3 && !parts.some(Number.isNaN)) {
      return new Date(parts[0], parts[1]-1, parts[2]);
    }
    return null;
  }

  function positionToInput(input){
    const r = input.getBoundingClientRect();
    const top = window.scrollY + r.bottom + 6;
    const left = window.scrollX + Math.min(r.left, window.innerWidth - 280);
    popup.style.top = top + 'px';
    popup.style.left = left + 'px';
  }

  function render(){
    const title = popup.querySelector('.sp-title');
    const body = popup.querySelector('.sp-body');

    if (mode === 'month') {
      title.textContent = viewYear + T.yearSuffix;
      body.innerHTML = '';
      const grid = document.createElement('div');
      grid.style.cssText = 'display:grid; grid-template-columns:repeat(3,1fr); gap:6px;';
      for (let m=0;m<12;m++){
        const btn = document.createElement('button');
        btn.type='button';
        btn.textContent = T.months[m];
        btn.style.cssText = 'border:1px solid rgba(0,0,0,0.1); background:#fff; border-radius:8px; padding:8px 6px; cursor:pointer;';
        btn.addEventListener('click', ()=>{
          const d = new Date(viewYear, m, 1);
          activeInput.value = fmtMonth(d);
          hide();
        });
        grid.appendChild(btn);
      }
      body.appendChild(grid);
      return;
    }

    // date mode title
    if (LANG === 'en') title.textContent = `${T.months[viewMonth]} ${viewYear}`;
    else {
      const monthSuffix = (LANG === 'ja') ? 'Month' : 'Month';
      title.textContent = `${viewYear}${T.yearSuffix} ${viewMonth+1}${monthSuffix}`;
    }

    const first = new Date(viewYear, viewMonth, 1);
    const startDow = first.getDay();
    const daysInMonth = new Date(viewYear, viewMonth+1, 0).getDate();

    body.innerHTML = '';
    const dow = document.createElement('div');
    dow.style.cssText = 'display:grid; grid-template-columns:repeat(7,1fr); gap:2px; margin-bottom:6px; font-size:12px; color:#667085;';
    T.weekdays.forEach(w=>{
      const d = document.createElement('div');
      d.textContent = w;
      d.style.cssText = 'text-align:center; padding:4px 0;';
      dow.appendChild(d);
    });
    body.appendChild(dow);

    const grid = document.createElement('div');
    grid.style.cssText = 'display:grid; grid-template-columns:repeat(7,1fr); gap:2px;';
    const totalCells = Math.ceil((startDow + daysInMonth)/7)*7;

    for (let i=0;i<totalCells;i++){
      const cell = document.createElement('button');
      cell.type='button';
      cell.style.cssText = 'height:32px; border:none; background:#fff; border-radius:8px; cursor:pointer; font-size:13px;';
      const dayNum = i - startDow + 1;
      if (dayNum < 1 || dayNum > daysInMonth){
        cell.disabled = true;
        cell.textContent = '';
        cell.style.cursor='default';
      } else {
        cell.textContent = String(dayNum);
        cell.addEventListener('click', ()=>{
          const d = new Date(viewYear, viewMonth, dayNum);
          activeInput.value = fmtDate(d);
          hide();
        });
        cell.addEventListener('mouseenter', ()=>{ cell.style.background='#f2f4f7'; });
        cell.addEventListener('mouseleave', ()=>{ cell.style.background='#fff'; });
      }
      grid.appendChild(cell);
    }
    body.appendChild(grid);
  }

  function show(input, newMode){
    mode = newMode;
    activeInput = input;
    ensurePopup();

    const parsed = parseValue(input.value);
    const base = parsed || new Date();

    viewYear = base.getFullYear();
    viewMonth = base.getMonth();

    positionToInput(input);
    popup.style.display = 'block';
    render();
  }

  function hide(){
    if (!popup) return;
    popup.style.display = 'none';
    activeInput = null;
  }

  function attach(selector, m){
    document.querySelectorAll(selector).forEach((input)=>{
      input.addEventListener('focus', ()=> show(input, m));
      input.addEventListener('click', ()=> show(input, m));
      input.addEventListener('keydown', (e)=>{
        if (e.key.length === 1) e.preventDefault();
      });
    });
  }

  attach('input.js-date', 'date');
  attach('input.js-month', 'month');
})();
</script>

