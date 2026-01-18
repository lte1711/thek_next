    <!-- 데일리 정산 -->
    <h2><?= t('title.settlement.partner_daily', 'Partner Daily Settlement') ?></h2>

    <form method="GET" class="form-inline">
        <label><?= t('common.select_date', 'Select Date') ?>:</label>
        <input type="text" class="js-date" name="settle_date" value="<?= htmlspecialchars($settle_date) ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD">
        <button type="submit"><?= t('common.search', 'Search') ?></button>

        <?php if (!empty($is_settled)): ?>
            <button type="button" disabled style="opacity:0.6; cursor:not-allowed;"><?= t('partner.settlement.settled', 'Settled') ?></button>
            <span style="font-size:12px; opacity:0.8;">
                <?= !empty($settled_at) ? "(" . t('partner.settlement.settled_at', 'Settled at') . ": " . htmlspecialchars($settled_at) . ")" : "" ?>
            </span>
        <?php else: ?>
            <button type="button" onclick="confirmSettlement(event)"><?= t('partner.settlement.settle', 'Settle') ?></button>
        <?php endif; ?>
    </form>

    <table id="dailyTable">
        <tr><th><?= t('common.role', 'Role') ?></th><th><?= t('common.name', 'Name') ?></th><th><?= t('common.usdt', 'USDT') ?></th><th><?= t('common.ratio', 'Ratio') ?></th></tr>

        <?php
        // ✅ 요청사항
        // - 실비율(회색) 제거
        // - GM 고정 수익배율만 표시(회색)
        foreach ($role_data as $label => $amount):
            $gm_name = $gm_name_map[$label] ?? '';
            $fixed_ratio = $gm_profit_ratio_map[$label] ?? 0;
        ?>
            <tr>
                <td data-label="<?= t('common.role', 'Role') ?>"><?= htmlspecialchars($label) ?></td>
                <td data-label="<?= t('common.name', 'Name') ?>"><?= htmlspecialchars($gm_name) ?></td>
                <td data-label="<?= t('common.usdt', 'USDT') ?>">₩<?= number_format((float)$amount, 2) ?></td>
                <td data-label="<?= t('common.ratio', 'Ratio') ?>">
                    <span style="color:#555; font-weight:700;"><?= (int)$fixed_ratio ?>%</span>
                </td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td><strong><?= t('common.total', 'Total') ?></strong></td>
            <td></td>
            <td><strong>₩<?= number_format((float)$total_usdt, 2) ?></strong></td>
            <td>
                <strong style="color:#555;"><?= (int)($total_profit_ratio ?? 0) ?>%</strong>
            </td>
        </tr>
    </table>

    <!-- 월별 정산 -->
    <h2><?= t('title.settlement.partner_monthly_list', 'Partner Monthly Settlement List') ?></h2>
    <form method="GET" class="form-inline">
        <label><?= t('common.select_month', 'Select Month') ?>:</label>
        <input type="text" class="js-month" name="year_month" value="<?= htmlspecialchars($year_month) ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM">
        <button type="submit"><?= t('common.search', 'Search') ?></button>

        <!-- 데일리 날짜 유지 -->
        <input type="hidden" name="settle_date" value="<?= htmlspecialchars($settle_date) ?>">
    </form>

    <table id="monthlyTable">
        <tr>
            <th><?= t('common.date', 'Date') ?></th>
            <?php foreach(array_keys($role_data) as $role): ?>
                <?php $hname = $gm_name_map[$role] ?? ''; ?>
                <th><?= htmlspecialchars($role . ($hname ? " ($hname)" : "")) ?></th>
            <?php endforeach; ?>
            <th><?= t('common.total', 'Total') ?></th>
        </tr>

        <?php foreach($data as $date => $row): ?>
            <tr>
                <td data-label="<?= t('common.date', 'Date') ?>"><?= htmlspecialchars($date) ?></td>
                <?php foreach(array_keys($role_data) as $role): ?>
                    <td data-label="<?= htmlspecialchars($role) ?>">₩<?= number_format((float)($row[$role] ?? 0), 2) ?></td>
                <?php endforeach; ?>
                <td data-label="<?= t('common.total', 'Total') ?>"><strong>₩<?= number_format((float)$row['Total'], 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<script>
function confirmSettlement(e) {
    if (e) e.preventDefault();

    const isSettled = <?= !empty($is_settled) ? 'true' : 'false' ?>;
    if (isSettled) {
        alert('<?= t('alert.already_settled_date', 'This date is already settled.') ?>');
        return;
    }

    if (!confirm("<?= t('confirm.do_settlement', 'Proceed with settlement?') ?>")) return;

    const settleDate = "<?= htmlspecialchars($settle_date) ?>";

    // ✅ GM 정산 저장 호출
    fetch('gm_settle_confirm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'settle_date=' + encodeURIComponent(settleDate)
    })
    .then(async (res) => {
        const text = await res.text();
        if (!res.ok) throw new Error(text || '<?= t('error.settlement_failed', 'Settlement failed') ?>');
        alert(text);
        location.reload(); // 저장 후 화면 갱신 → 버튼 비활성화 반영
    })
    .catch((err) => {
        alert(err.message);
    });
}
</script>



<style>
/* Partner_accounts page-only: keep custom picker but restore compact "native date" layout */
.form-inline{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  flex-wrap:wrap;
  margin: 8px 0 18px;
}
.form-inline label{
  margin:0;
  font-weight:600;
  font-size:13px;
  white-space:nowrap;
}

/* Restore original form-inline date/month input styling (was targeting input[type=date/month]) */
.form-inline input.js-date,
.form-inline input.js-month{
  width:170px;         /* compact width */
  max-width:170px;
  min-height:40px;
  height:40px;
  padding:6px 10px;
  border-radius:10px;
  border:1px solid #dfe6ef;
  background:#fff;
  font-size:13px;
  line-height:1;
  box-sizing:border-box;
}

/* Match buttons height/spacing */
.form-inline button{
  height:40px;
  padding:0 14px;
  border-radius:10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}
</style>

<script>
(function(){
  // --- Simple, dependency-free date/month picker (page-only) ---
  function getLang(){
    const u = new URL(window.location.href);
    const l = (u.searchParams.get('lang') || 'en').toLowerCase();
    if (l.startsWith('ja')) return 'ja';
    if (l.startsWith('ko')) return 'ko';
    return 'en';
  }

  const LANG = getLang();

  const T = {
    months: [
      "<?= t('month.1') ?>","<?= t('month.2') ?>","<?= t('month.3') ?>","<?= t('month.4') ?>","<?= t('month.5') ?>","<?= t('month.6') ?>",
      "<?= t('month.7') ?>","<?= t('month.8') ?>","<?= t('month.9') ?>","<?= t('month.10') ?>","<?= t('month.11') ?>","<?= t('month.12') ?>"
    ],
    weekdays: [
      "<?= t('weekday.sun') ?>","<?= t('weekday.mon') ?>","<?= t('weekday.tue') ?>","<?= t('weekday.wed') ?>","<?= t('weekday.thu') ?>","<?= t('weekday.fri') ?>","<?= t('weekday.sat') ?>"
    ],
    today: "<?= t('common.today') ?>",
    clear: "<?= t('common.clear') ?>"
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
    // YYYY-MM or YYYY-MM-DD
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
      title.textContent = viewYear + ' <?= t('unit.year_suffix') ?>';
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

    // date mode
    title.textContent = (LANG === 'ja')
      ? `${viewYear}年 ${viewMonth+1}月`
      : (LANG === 'ko')
        ? `${viewYear}년 ${viewMonth+1}월`
        : `${T.months[viewMonth]} ${viewYear}`;

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

  // Attach
  function attach(selector, m){
    document.querySelectorAll(selector).forEach((input)=>{
      input.addEventListener('focus', ()=> show(input, m));
      input.addEventListener('click', ()=> show(input, m));
      input.addEventListener('keydown', (e)=>{
        // prevent manual typing mistakes but allow navigation keys
        if (e.key.length === 1) e.preventDefault();
      });
    });
  }

  attach('input.js-date', 'date');
  attach('input.js-month', 'month');
})();
</script>
