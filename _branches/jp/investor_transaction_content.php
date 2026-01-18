<?php
$deposit_blocked = $deposit_blocked ?? false;
$blocked_tx = $blocked_tx ?? null;
$blocked_reasons = $blocked_reasons ?? [];
$blocked_action_url = $blocked_action_url ?? "investor_withdrawal.php";

// ✅ blocked_reasons 번역 처리 (로직 영향 없음)
$translated_reasons = [];
if (is_array($blocked_reasons)) {
    foreach ($blocked_reasons as $r) {
        // 이전 버전(한글 문자열) 호환 + 신규(키) 방식 지원
        if ($r === '출금 미완료') $r = 'deposit.blocked.reason.withdrawal';
        if ($r === '정산 미완료') $r = 'deposit.blocked.reason.settle';
        if ($r === '배당 미완료') $r = 'deposit.blocked.reason.dividend';

        if (is_string($r) && strpos($r, 'deposit.blocked.reason.') === 0) {
            $translated_reasons[] = t($r, $r);
        } else {
            $translated_reasons[] = $r;
        }
    }
}

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
  <h2><?= t('page.investor_deposit', '거래 입력') ?></h2>

  <?php if ($deposit_blocked): ?>
    <div class="alert-box alert-danger" style="margin-bottom:16px;">
      <div style="font-weight:700; margin-bottom:6px;"><?= t('deposit.blocked.title', '입금 불가') ?></div>
      <div style="margin-bottom:10px;">
        <?= htmlspecialchars(implode(", ", $translated_reasons)) ?> <?= t('deposit.blocked.status_suffix', '상태입니다.') ?><br>
        <?= t('deposit.blocked.ai_notice', 'AI 진행(승인/처리) 완료 전에는 새 입금을 진행할 수 없습니다.') ?>
      </div>

      <a class="btn btn-dark"
         href="<?= htmlspecialchars($blocked_action_url) ?>"
         title="<?= htmlspecialchars(t('deposit.blocked.action_title', '미완료(출금/정산/배당) 처리 화면으로 이동')) ?>">
        <?= t('deposit.blocked.action_btn', '미완료 처리하러가기') ?>
      </a>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label><?= t('deposit.form.tx_date', '거래소에 입금한 날짜') ?></label>
      <input type="text" class="js-date" name="tx_date" value="<?= htmlspecialchars($display_date) ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD" style="width:165px;" <?= $deposit_blocked ? 'disabled' : '' ?>>
      <?php if ($deposit_blocked): ?>
        <input type="hidden" name="tx_date" value="<?= htmlspecialchars($display_date) ?>">
      <?php endif; ?>
    </div>

    <table class="data-table">
      <thead>
        <tr><th><?= t('deposit.form.xm_th', 'XM (원)') ?></th><th><?= t('deposit.form.ultima_th', 'Ultima (원)') ?></th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <input type="text" name="xm_value" id="xm_value"
                   placeholder="<?= htmlspecialchars(t('deposit.form.xm_placeholder', 'XM 금액 입력')) ?>"
                   value="<?= $deposit_blocked ? htmlspecialchars($display_xm) : '' ?>"
                   <?= $deposit_blocked ? 'readonly disabled' : '' ?>>
          </td>
          <td>
            <input type="text" name="ultima_value" id="ultima_value"
                   placeholder="<?= htmlspecialchars(t('deposit.form.ultima_placeholder', 'Ultima 금액 입력')) ?>"
                   value="<?= $deposit_blocked ? htmlspecialchars($display_ultima) : '' ?>"
                   <?= $deposit_blocked ? 'readonly disabled' : '' ?>>
          </td>
        </tr>
      </tbody>
    </table>

    <button type="submit" class="btn" <?= $deposit_blocked ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
      <?= t('common.save', '저장하기') ?>
    </button>
  </form>
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

