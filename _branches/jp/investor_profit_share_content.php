<div class="form-container">

  <!-- ✅ 1) Profit Share Summary를 위로 올림 -->
  <h2>📊 <?= t('profit_share.summary_title') ?> (<span id="summaryDate"><?= htmlspecialchars($summary_label ?? ($latest_date ?? '')) ?></span>)</h2>

  <style>
    /* ✅ 복사 버튼이 화면 밖으로 안 나가게 */
    .wallet-wrap{
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
    }
    .wallet-wrap input{
      flex:1;
      min-width:220px;
      max-width:100%;
      box-sizing:border-box;
    }
    .wallet-wrap .copy-btn{
      white-space:nowrap;
    }
  </style>

  <table class="form-table" id="summaryTable">
    <tbody>
      <!-- ✅ Summary 금액: 000,000 형식(소수점 없이 콤마) -->
      <tr><th><?= t('profit_share.total_deposit') ?></th><td id="sumDeposit"><?= number_format((float)$deposit, 2) ?> USDT</td></tr>
      <tr><th><?= t('profit_share.total_withdrawal') ?></th><td id="sumWithdrawal"><?= number_format((float)$withdrawal, 2) ?> USDT</td></tr>
      <tr><th><?= t('profit_share.total_profit') ?></th><td id="sumProfit"><?= number_format((float)$profit, 2) ?> USDT</td></tr>
      <tr><th><?= t('profit_share.col.share75') ?></th><td id="sumShare75"><?= number_format((float)$share80, 2) ?> USDT</td></tr>
      <tr><th><?= t('profit_share.col.share25') ?></th><td><strong class="highlight" id="sumShare25"><?= number_format((float)$share20, 2) ?> USDT</strong></td></tr>

      <tr>
        <th><?= t('profit_share.wallet_address') ?></th>
        <td>
          <div class="wallet-wrap">
            <input type="text" id="wallet_address" value="<?= htmlspecialchars($wallet ?? '') ?>" readonly>
            <button type="button" onclick="copyWallet()" class="copy-btn"><?= t('profit_share.btn.copy') ?></button>
          </div>
        </td>
      </tr>
      <tr><th><?= t('profit_share.today_date') ?></th><td><?= date("Y-m-d") ?></td></tr>
    </tbody>
  </table>

  <!-- ✅ 정산 버튼 조건부 표시(그대로 유지) -->
  <div id="dividendBtnArea">
    <?php if (!empty($finance['dividend_chk'])): ?>
      <p><strong><?= t('profit_share.msg.already_settled') ?></strong></p>
    <?php else: ?>
<form method="POST" action="investor_profit_share.php?user_id=<?= (int)($view_user_id ?? $user_id ?? 0) ?>">
        <input type="hidden" name="dividend" value="1">
        <input type="hidden" id="sel_tx_date" name="tx_date" value="<?= htmlspecialchars($latest_date ?? '') ?>">
        <input type="hidden" id="sel_code_value" name="code_value" value="<?= htmlspecialchars($latest_code ?? '') ?>">
        <button type="submit" class="btn btn-primary"><?= t('profit_share.btn.settle') ?></button>
      </form>
    <?php endif; ?>
  </div>

  <!-- ✅ A안: 기본은 전체 코드 노출 + 날짜 범위(일/주/월) 선택 -->
  <div style="margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
    <form method="GET" action="investor_profit_share.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
      <input type="hidden" name="user_id" value="<?= (int)($view_user_id ?? $user_id ?? 0) ?>">

      <div>
        <label style="display:block; font-size:12px; margin-bottom:4px;"><?= t('profit_share.filter.period') ?></label>
        <select name="period" id="periodSel" onchange="togglePeriodInputs()">
          <option value="recent" <?= (($current_period ?? 'recent') === 'recent') ? 'selected' : '' ?>><?= t('profit_share.period.recent10') ?></option>
          <option value="day" <?= (($current_period ?? '') === 'day') ? 'selected' : '' ?>><?= t('profit_share.period.day') ?></option>
          <option value="week" <?= (($current_period ?? '') === 'week') ? 'selected' : '' ?>><?= t('profit_share.period.week') ?></option>
          <option value="month" <?= (($current_period ?? '') === 'month') ? 'selected' : '' ?>><?= t('profit_share.period.month') ?></option>
        </select>
      </div>

      <div id="inpDayWrap" style="display:none;">
        <label style="display:block; font-size:12px; margin-bottom:4px;"><?= t('profit_share.filter.date_day') ?></label>
        <input type="text" class="js-date" name="day" value="<?= htmlspecialchars($current_day ?? '') ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD" style="width:165px;">
      </div>

      <div id="inpWeekWrap" style="display:none;">
        <label style="display:block; font-size:12px; margin-bottom:4px;"><?= t('profit_share.filter.date_week') ?></label>
        <input type="text" class="js-date" name="week" value="<?= htmlspecialchars($current_week ?? '') ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD" style="width:165px;">
      </div>

      <div id="inpMonthWrap" style="display:none;">
        <label style="display:block; font-size:12px; margin-bottom:4px;"><?= t('profit_share.filter.month') ?></label>
        <input type="text" class="js-month" name="month" value="<?= htmlspecialchars($current_month ?? '') ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM" style="width:170px;">
      </div>

      <div>
        <label style="display:block; font-size:12px; margin-bottom:4px;"><?= t('profit_share.filter.code') ?></label>
        <select name="code_value">
          <option value=""><?= t('profit_share.option.all') ?></option>
          <?php foreach (($filter_codes ?? []) as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= (!empty($current_code) && $current_code===$c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary" style="height:36px;"><?= t('profit_share.btn.apply') ?></button>
    </form>
  </div>

  <!-- ✅ 2) 거래내역 테이블을 아래로 내림 -->
  <h2 style="margin-top:24px;">📦 <?= t('profit_share.tx_list_title') ?></h2>
  <table class="form-table">
    <thead>
      <tr>
        <th><?= t('profit_share.col.date') ?></th>
        <th><?= t('profit_share.filter.code') ?></th>
        <th><?= t('profit_share.col.xm_deposit') ?></th>
        <th><?= t('profit_share.col.ultima_deposit') ?></th>
        <th><?= t('profit_share.col.xm_withdrawal') ?></th>
        <th><?= t('profit_share.col.ultima_withdrawal') ?></th>
        <th><?= t('profit_share.col.profit') ?></th>
        <th><?= t('profit_share.col.share75') ?></th>
        <th><?= t('profit_share.col.share25') ?></th>
        <th><?= t('profit_share.col.status_select') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($transactions_for_list ?? []) as $row):
        $depositRow    = ($row['xm_value'] ?? 0) + ($row['ultima_value'] ?? 0);
        $withdrawalRow = ($row['xm_total'] ?? 0) + ($row['ultima_total'] ?? 0);
        $profitRow     = $withdrawalRow - $depositRow;
        $share75Row    = $profitRow * 0.75;
        $share25Row    = $profitRow * 0.25;
      ?>
        <tr>
          <td><?= htmlspecialchars($row['tx_date'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['code_value'] ?? '') ?></td>
          <td><?= number_format($row['xm_value'], 2) ?></td>
          <td><?= number_format($row['ultima_value'], 2) ?></td>
          <td><?= number_format($row['xm_total'], 2) ?></td>
          <td><?= number_format($row['ultima_total'], 2) ?></td>
          <td><strong><?= number_format($profitRow, 2) ?></strong></td>
          <td><?= number_format($share75Row, 2) ?></td>
          <td><strong class="highlight"><?= number_format($share25Row, 2) ?></strong></td>
          <td>
            <?php if (!empty($row['dividend_chk'])): ?>
              <span class="check"><?= t('profit_share.status.settled') ?></span>
            <?php endif; ?>
            <button type="button" onclick="loadSummary('<?= $row['tx_date'] ?>','<?= $row['code_value'] ?>')"><?= t('profit_share.btn.select') ?></button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</div>

<script>
function copyWallet() {
  const walletInput = document.getElementById("wallet_address");
  walletInput.select();
  walletInput.setSelectionRange(0, 99999);
  document.execCommand("copy");
  alert(MSG_WALLET_COPIED);
}

/* ✅ Summary 숫자: 000,000 형식 (소수점 없이) */
function fmt2(v){
  const n = Number(v);
  if (!isFinite(n)) return v;
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

// ✅ Summary + 버튼 + 숨은 입력 갱신
function loadSummary(tx_date, code_value) {
const uid = <?= (int)($view_user_id ?? $user_id ?? 0) ?>;
fetch("load_summary.php?user_id=" + uid + "&date=" + encodeURIComponent(tx_date) + "&code=" + encodeURIComponent(code_value))
    .then(response => response.json())
    .then(data => {
      if (data.error) { alert(MSG_ERR_PREFIX + " " + data.error); return; }

      // ✅ 콤마 형식으로 표시
      document.getElementById("sumDeposit").innerText    = fmt2(data.deposit) + " USDT";
      document.getElementById("sumWithdrawal").innerText = fmt2(data.withdrawal) + " USDT";
      document.getElementById("sumProfit").innerText     = fmt2(data.profit) + " USDT";
      document.getElementById("sumShare75").innerText    = fmt2(data.share75) + " USDT";
      document.getElementById("sumShare25").innerText    = fmt2(data.share25) + " USDT";

      // ✅ 선택된 tx_date를 Summary 제목에 반영
      document.getElementById("summaryDate").innerText = tx_date;

      // 버튼 표시 상태 갱신
      const btnArea = document.getElementById("dividendBtnArea");
      if (data.dividend_chk === 1) {
        btnArea.innerHTML = "<p><strong><?= t('profit_share.msg.already_settled') ?></strong></p>";
      } else {
        btnArea.innerHTML = '<form method="POST" action="investor_profit_share.php" style="margin-top:20px;">' +
                            '<input type="hidden" name="dividend" value="1">' +
                            '<input type="hidden" id="sel_tx_date" name="tx_date" value="' + tx_date + '">' +
                            '<input type="hidden" id="sel_code_value" name="code_value" value="' + code_value + '">' +
                            '<button type="submit" class="btn btn-primary"><?= t('profit_share.btn.settle') ?></button>' +
                            '</form>';
      }

      // 폼 hidden 값 동기화
      const hDate = document.getElementById('sel_tx_date');
      const hCode = document.getElementById('sel_code_value');
      if (hDate) hDate.value = tx_date;
      if (hCode) hCode.value = code_value;
    })
    .catch(err => alert(MSG_SUMMARY_LOAD_ERR + " " + err));
}

function togglePeriodInputs(){
  const v = document.getElementById('periodSel')?.value || 'recent';
  document.getElementById('inpDayWrap').style.display   = (v==='day') ? 'block' : 'none';
  document.getElementById('inpWeekWrap').style.display  = (v==='week') ? 'block' : 'none';
  document.getElementById('inpMonthWrap').style.display = (v==='month') ? 'block' : 'none';
}

// 초기 렌더링
togglePeriodInputs();
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
      const monthSuffix = (LANG === 'ja') ? '月' : '월';
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

