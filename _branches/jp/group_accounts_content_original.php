    <!-- 어드민 데일리 정산 -->
    <h2><?= t('title.settlement.org_daily', "Organization Daily Settlement") ?></h2>

<style>
/* JP standard: compact custom date input (page-only) */
.form-inline{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  flex-wrap:wrap;
}
.form-inline label{ white-space:nowrap; font-weight:600; font-size:13px; margin:0; }
.form-inline input.js-date{
  width:170px;
  max-width:170px;
  height:34px;
  padding:6px 10px;
  border-radius:10px;
  border:1px solid #dfe6ef;
  background:#fff;
  font-size:13px;
  line-height:1;
}
.form-inline button{
  height:34px;
  padding:0 14px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}
</style>


    <form method="GET" class="form-inline">
        <label><?= t('label.select_date', "Select Date:") ?></label>
        <input type="text" class="js-date" name="daily_date" value="<?= $settle_date ?>" autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD">
        <input type="hidden" name="list_period" value="<?= $period ?>">
        <button type="submit"><?= t('btn.search', "Search") ?></button>
            <?php if ($is_settled): ?>
                <button type="button" disabled style="opacity:0.6; cursor:not-allowed;"><?= t('btn.settled', "Settled") ?></button>
                <span style="font-size:12px; opacity:0.8;">
                    <?= $settled_at ? "(" . t('label.settled_at', "Settled at:") . " " . htmlspecialchars($settled_at) . ")" : "" ?>
                </span>
            <?php else: ?>
                <button type="button" onclick="confirmSettlement(event)"><?= t('btn.settle', "Settle") ?></button>
            <?php endif; ?>    
        </form>

    <table>
        <tr>
            <th><?= t('th.admin', "Admin") ?></th>
            <th><?= t('th.amount', "Amount") ?></th>
            <th><?= t('th.ratio', "Ratio") ?></th>
            <th><?= t('th.wallet', "Wallet Address") ?></th>
            <th><?= t('th.codepay', "Codepay Address") ?></th>
            <th><?= t('th.details', "Details") ?></th>
        </tr>

        <?php foreach ($admin_data as $row): ?>
        <?php
            $percent = ($admin_total > 0) ? (($row['total_sales'] / $admin_total) * 100) : 0;
            $percent = number_format($percent, 2);
        ?>
        <tr>
            <td data-label="<?= t('th.admin', "Admin") ?>"><?= htmlspecialchars($row['username']) . " (ID: " . $row['id'] . ")" ?></td>
            <td data-label="<?= t('th.amount', "Amount") ?>"><?= number_format($row['total_sales'], 2) ?></td>
            <td data-label="<?= t('th.ratio', "Ratio") ?>"><?= $percent ?>%</td>
            <td data-label="<?= t('th.wallet', "Wallet Address") ?>"><?= htmlspecialchars($row['wallet_address'] ?? '') ?></td>
            <td data-label="<?= t('th.codepay', "Codepay Address") ?>"><?= htmlspecialchars($row['codepay_address'] ?? '') ?></td>
            <td data-label="<?= t('th.details', "Details") ?>">
                <a href="admin_detail.php?admin_id=<?= $row['id'] ?>&date=<?= $settle_date ?>"><?= t('btn.view', "View") ?></a>
            </td>
        </tr>
        <?php endforeach; ?>

        <tr>
            <td><strong>Total</strong></td>
            <td><strong><?= number_format($admin_total, 2) ?></strong></td>
            <td>100%</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <hr>
<!-- 조직 정산 리스트 -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-top:30px; gap:10px;">
    <h2 style="margin:0;">
        <?php
            $__lang2 = isset($lang) ? $lang : (isset($_GET['lang']) ? $_GET['lang'] : 'en');
            $__lang2 = in_array($__lang2, ['ko','ja','en'], true) ? $__lang2 : 'en';
            $__list_suffix = ['en' => ' List', 'ko' => ' 목록', 'ja' => '一覧'];
            echo t('title.settlement.group', 'Organization Settlement') . ($__list_suffix[$__lang2] ?? ' List');
        ?>
    </h2>
    <a
        href="group_accounts_download.php?date=<?= urlencode($settle_date) ?>"
        class="btn btn-outline"
        style="padding:10px 16px; border-radius:10px; border:2px solid #c0392b; color:#c0392b; font-weight:700; text-decoration:none; white-space:nowrap;"
    ><?= t('btn.download_list', "Download List") ?></a>
</div>

<?php
// Period labels for list dropdown (page-only, avoids missing lang keys)
$__lang = isset($lang) ? $lang : (isset($_GET['lang']) ? $_GET['lang'] : 'en');
$__lang = in_array($__lang, ['ko','ja','en'], true) ? $__lang : 'en';
$__period_labels = [
    'daily'   => ['en' => 'Daily',   'ko' => '일간', 'ja' => '日次'],
    'weekly'  => ['en' => 'Weekly',  'ko' => '주간', 'ja' => '週次'],
    'monthly' => ['en' => 'Monthly', 'ko' => '월간', 'ja' => '月次'],
];
$__period_label = function(string $key) use ($__period_labels, $__lang) : string {
    return $__period_labels[$key][$__lang] ?? ($__period_labels[$key]['en'] ?? $key);
};
?>

<form method="GET"
      class="form-inline"
      style="display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:20px;">

    <span><?= t('label.period', "Period:") ?></span>

    <select name="list_period"
            style="width:auto; min-width:120px;">
        <option value="daily" <?= $period=='daily'?'selected':'' ?>><?= htmlspecialchars($__period_label('daily')) ?></option>
        <option value="weekly" <?= $period=='weekly'?'selected':'' ?>><?= htmlspecialchars($__period_label('weekly')) ?></option>
        <option value="monthly" <?= $period=='monthly'?'selected':'' ?>><?= htmlspecialchars($__period_label('monthly')) ?></option>
    </select>

    <!-- 위쪽 데일리 날짜 유지 -->
    <input type="hidden" name="daily_date" value="<?= htmlspecialchars($settle_date) ?>">

    <button type="submit" class="btn btn-primary"><?= t('btn.search', "Search") ?></button>
</form>
    <table>
        <?php foreach ($group_data as $p => $rows): ?>
            <tr><th colspan="3"><?= htmlspecialchars($p) ?></th></tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="<?= t('th.admin', "Admin") ?>"><?= htmlspecialchars($r['username'])." (ID: ".$r['id'].")" ?></td>
                <td data-label="<?= t('th.amount', "Amount") ?>">
                    <a href="admin_detail.php?admin_id=<?= $r['id'] ?>&period=<?= $p ?>">
                        <?= number_format($r['total_sales'], 2) ?>
                    </a>
                </td>
                <td data-label="<?= t('th.details', "Details") ?>">
                    <a href="admin_detail.php?admin_id=<?= $r['id'] ?>&period=<?= $p ?>"><?= t('btn.view_details', "View Details") ?></a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
</section>

<script>
function confirmSettlement(e) {
    if (e) e.preventDefault();

    const isSettled = <?= $is_settled ? 'true' : 'false' ?>;
    if (isSettled) {
        alert(<?= json_encode(t('alert.already_settled', 'This date is already settled.')) ?>);
        return;
    }

    if (!confirm(<?= json_encode(t('confirm.settle', 'Proceed with settlement?')) ?>)) return;

    const settleDate = "<?= htmlspecialchars($settle_date) ?>";

    fetch('/settle_confirm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'settle_date=' + encodeURIComponent(settleDate)
    })
    .then(res => res.text().then(t => ({ok:res.ok, text:t})))
    .then(r => {
        if (!r.ok) throw new Error(r.text);
        alert(r.text);
        location.reload();
    })
    .catch(err => alert(err.message));
}
</script>


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

  // i18n calendar labels (current language)
  const CAL_MONTHS = <?= json_encode([t('calendar.month_1','1'),t('calendar.month_2','2'),t('calendar.month_3','3'),t('calendar.month_4','4'),t('calendar.month_5','5'),t('calendar.month_6','6'),t('calendar.month_7','7'),t('calendar.month_8','8'),t('calendar.month_9','9'),t('calendar.month_10','10'),t('calendar.month_11','11'),t('calendar.month_12','12')]) ?>;
  const CAL_WEEKDAYS = <?= json_encode([t('calendar.weekday_sun','Sun'),t('calendar.weekday_mon','Mon'),t('calendar.weekday_tue','Tue'),t('calendar.weekday_wed','Wed'),t('calendar.weekday_thu','Thu'),t('calendar.weekday_fri','Fri'),t('calendar.weekday_sat','Sat')]) ?>;
  const CAL_TODAY = <?= json_encode(t('calendar.today','Today')) ?>;
  const CAL_CLEAR = <?= json_encode(t('calendar.clear','Clear')) ?>;
  const YEAR_SUFFIX = <?= json_encode(t('calendar.year_suffix','')) ?>;
  const MONTH_SUFFIX = <?= json_encode(t('calendar.month_suffix','')) ?>;
  const T = { months: CAL_MONTHS, weekdays: CAL_WEEKDAYS, today: CAL_TODAY, clear: CAL_CLEAR };

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
      title.textContent = viewYear + YEAR_SUFFIX;
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
        title.textContent = T.months[viewMonth] + ' ' + viewYear + YEAR_SUFFIX;

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
