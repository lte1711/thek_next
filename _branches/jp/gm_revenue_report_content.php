<?php
error_reporting(E_ALL);
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');

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
        <label for="date"><?= t('field.date', 'Date') ?></label>
        <input type="text" class="js-date" name="date" id="date" value="<?= htmlspecialchars($report_date ?? '') ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD" style="width:165px;">
    </div>

    <div class="field">
        <label for="type"><?= t('field.type', 'Type') ?></label>
        <select name="type" id="type">
            <option value="day"   <?= $report_type === 'day'   ? 'selected' : '' ?>><?= t('option.daily', 'Daily') ?></option>
            <option value="week"  <?= $report_type === 'week'  ? 'selected' : '' ?>><?= t('option.weekly','Weekly') ?></option>
            <option value="month" <?= $report_type === 'month' ? 'selected' : '' ?>><?= t('option.monthly','Monthly') ?></option>
            <option value="year"  <?= $report_type === 'year'  ? 'selected' : '' ?>><?= t('option.yearly','Yearly') ?></option>
        </select>
    </div>

    <div class="field">
        <label for="role"><?= t('field.role','Role') ?></label>
        <select name="role" id="role">
            <option value="gm"       <?= $selected_role === 'gm'       ? 'selected' : '' ?>><?= t('role.gm','GM') ?></option>
            <option value="admin"    <?= $selected_role === 'admin'    ? 'selected' : '' ?>><?= t('role.admin','Admin') ?></option>
            <option value="master"   <?= $selected_role === 'master'   ? 'selected' : '' ?>><?= t('role.master','Master') ?></option>
            <option value="agent"    <?= $selected_role === 'agent'    ? 'selected' : '' ?>><?= t('role.agent','Agent') ?></option>
            <option value="investor" <?= $selected_role === 'investor' ? 'selected' : '' ?>><?= t('role.investor','Investor') ?></option>
        </select>
    </div>

    <div class="actions">
        <button type="submit"><?= t('common.search','조회') ?></button>

        <a id="codepayLink"
           href="codepay_export.php?date=<?= urlencode($report_date) ?>&type=<?= urlencode($report_type) ?>&role=<?= urlencode($selected_role) ?>"
           style="padding:6px 12px; background:#198754; color:#fff; border-radius:4px; text-decoration:none;">
          <?= t('btn.codepay_excel','코드페이 엑셀') ?>
        </a>
    </div>
</form>

<div id="report-content" style="padding:20px; font-family:Arial, sans-serif;">
    <h2 style="text-align:center;"><?= t('title.dividend_revenue_report','Dividend Revenue Report') ?></h2>
    <p style="text-align:center;"><?= t('label.period','기간') ?>: <?= htmlspecialchars($start_date) ?> ~ <?= htmlspecialchars($end_date) ?></p>

    <table style="margin:auto; width:70%; border-collapse:collapse; border:1px solid #000;">
        <tr style="background:#f0f0f0;">
            <th style="border:1px solid #000; padding:8px;"><?= t('table.role','역할') ?></th>
            <th style="border:1px solid #000; padding:8px;"><?= t('table.usdt_total','USDT 합계') ?></th>
        </tr>
        <?php foreach ($filtered_data as $label => $value): ?>
        <tr>
            <td style="border:1px solid #000; padding:8px;"><?= htmlspecialchars($label) ?></td>
            <td style="border:1px solid #000; padding:8px; text-align:right;"><?= number_format((float)$value, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold; background:#e0e0e0;">
            <td style="border:1px solid #000; padding:8px;"><?= t('common.total','Total') ?></td>
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
      const monthSuffix = (LANG === 'en') ? '' : (LANG === 'ja' ? <?= json_encode(t('calendar.month_suffix','月')) ?> : <?= json_encode(t('calendar.month_suffix','월')) ?>);
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

