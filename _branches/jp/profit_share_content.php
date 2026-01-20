<?php
// ✅ 정산 ON 후 alert 표시 (settle_toggle.php가 settle_seq를 넘겨줌)
if (isset($_GET['settle_seq']) && $_GET['settle_seq'] !== ''):
  $seq_for_alert = preg_replace('/[^0-9]/', '', (string)$_GET['settle_seq']);
  if ($seq_for_alert !== ''):
?>
<script>
    alert(<?= json_encode(t('profit_share.alert.settle_done_prefix') . $seq_for_alert) ?>);
  // URL에서 settle_seq 파라미터 제거 (새로고침 시 alert 반복 방지)
  (function () {
    const url = new URL(window.location.href);
    url.searchParams.delete('settle_seq');
    url.searchParams.delete('settle_tx');
    history.replaceState({}, '', url.toString());
  })();
</script>
<?php
  endif;
endif;
?>

<div class="dashboard-header">
  <h2><a href="profit_share.php" class="title-link"><?= t('profit_share.list.heading') ?></a></h2>
  <form method="GET" class="month-form" style="display:flex; align-items:center; gap:10px;">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)$user_id) ?>">
    <input type="hidden" name="region" value="<?= htmlspecialchars($region ?? 'korea') ?>">

    <!-- Year-Month picker (JP standard: 160~170px inline) -->
    <input
        type="text"
        id="year_month_picker"
        name="year_month"
        class="date-picker js-month"
        value="<?= htmlspecialchars($year_month ?? ($current_year . '-' . $current_month)) ?>"
        style="width:170px;"
        autocomplete="off"
        readonly
    >

    <!-- keep compatibility for server-side filters -->
    <input type="hidden" id="year_hidden" name="year" value="<?= htmlspecialchars($current_year) ?>">
    <input type="hidden" id="month_hidden" name="month" value="<?= htmlspecialchars($current_month) ?>">
</form>
</div>

<table class="form-table">
  <thead>
    <tr>
      <th><?= t('common.date') ?></th>
      <th><?= t('common.deposit') ?></th>
      <th><?= t('profit_share.col.cl') ?></th>
      <th><?= t('common.withdrawal') ?></th>
      <th><?= t('profit_share.col.profit_share') ?></th>
      <th><?= t('profit_share.col.state') ?></th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $month_result->fetch_assoc()):
      $date       = date('Y-m-d', strtotime($row['tx_date']));
      $deposit    = $row['deposit_chk'];
      $external   = $row['external_done_chk'] ?? 0;
      $withdrawal = $row['withdrawal_chk'] ?? 0;
      $dividend   = $row['dividend_chk'];
      $settled    = $row['settle_chk'];
      $reject     = $row['reject_reason'] ?? null;
      $reject_by  = $row['reject_by'] ?? null;

      $all_three = ($deposit == 1 && $external == 1 && $withdrawal == 1 && $dividend == 1);
      $all_four  = ($all_three && $settled == 1);

      // ✅ 오늘 몇 번째(01/02...) 표시용
      $today = date('Y-m-d');
      $seq_label = '-';
      if (!empty($row['day_seq']) && !empty($row['created_date']) && $row['created_date'] === $today) {
        $seq_label = str_pad((int)$row['day_seq'], 2, '0', STR_PAD_LEFT);
      }
    ?>
    <tr>
      <td><?= $date ?></td>
      <td><?= ($deposit == 1) ? '<span class="check">✅</span>' : '<a href="investor_deposit.php?user_id='.$user_id.'&id='.$row['id'].'" class="fail">❌</a>' ?></td>
<td>
  <?php if ($deposit != 1): ?>
    <span class="muted">-</span>
  <?php elseif ((int)$external === 1): ?>
    <span class="check">✅</span>
  <?php else: ?>
    <form method="POST" action="external_done_toggle.php" style="display:inline;">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <input type="hidden" name="month" value="<?= htmlspecialchars($current_month) ?>">
      <input type="hidden" name="region" value="<?= htmlspecialchars($region ?? 'korea') ?>">
      <button type="submit" class="toggle on" style="padding:6px 10px;"><?= t('profit_share.btn.confirm') ?></button>
    </form>
  <?php endif; ?>
</td>

      <td><?= ($withdrawal == 1) ? '<span class="check">✅</span>' : ((int)$external===1 ? '<a href="investor_withdrawal.php?user_id='.$user_id.'&id='.$row['id'].'" class="fail">❌</a>' : '<span class="muted">⏳</span>') ?></td>
      <td><?= ($dividend == 1) ? '<span class="check">✅</span>' : ((int)$external===1 ? '<a href="investor_profit_share.php?user_id='.$user_id.'&id='.$row['id'].'" class="fail">❌</a>' : '<span class="muted">⏳</span>') ?></td>
      <td>
        <!-- DEBUG: reject=<?= var_export($reject, true) ?>, reject_by=<?= var_export($reject_by, true) ?> -->
        <?php if (!empty($reject) || !empty($reject_by)): ?>
          <button class="confirm-btn" onclick="alert(<?= json_encode(t('label.reject_reason','Reject reason: ') . (!empty($reject) ? $reject : '(사유 없음)')) ?>)"><?= t('btn.view_reject_reason','View reason') ?></button>

          <!-- ✅ 却下中 해제: 최소 초기화만 수행 (settle_chk=0, reject_* NULL) → 이후 사용자가 다시 ON 진행 -->
          <form method="POST" action="reject_reset.php" style="display:inline; margin-left:6px;" onsubmit="return confirm(<?= json_encode(t('profit_share.confirm.reject_reset')) ?>);">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
            <input type="hidden" name="region" value="<?= htmlspecialchars($region ?? 'korea') ?>">
            <input type="hidden" name="month" value="<?= htmlspecialchars($current_month) ?>">
            <button type="submit" class="toggle on" style="padding:6px 10px;"><?= t('profit_share.btn.retry') ?></button>
          </form>

        <?php elseif ($all_four): ?>
          <button class="toggle off">OFF</button>
          <small>
            <?= t('profit_share.label.settled_by') ?>: <?= htmlspecialchars($row['settled_by'] ?? '-') ?> /
            <?= htmlspecialchars($row['settled_date'] ?? '-') ?>
            <?php if ($seq_label !== '-'): ?>
              <br><?= t('profit_share.label.today_seq') ?>: <b><?= htmlspecialchars($seq_label) ?></b>
            <?php endif; ?>
          </small>

        <?php elseif ($all_three): ?>
          <form method="POST" action="settle_toggle.php" style="display:inline;">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <input type="hidden" name="region" value="<?= htmlspecialchars($region ?? 'korea') ?>">
            <input type="hidden" name="month" value="<?= htmlspecialchars($current_month) ?>">
            <button type="submit" class="toggle on">ON</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>


<!-- flatpickr + monthSelectPlugin (page-scoped) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ko.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
(function(){
  function initMonthPicker(){
    if (!window.flatpickr || !window.monthSelectPlugin) return false;
    var el = document.getElementById('year_month_picker');
    if (!el) return true;

    // locale based on current_lang()
    var lang = <?= json_encode(function_exists('current_lang') ? current_lang() : 'en') ?>;
    var locale = (lang === 'ja') ? flatpickr.l10ns.ja : (lang === 'ko' ? flatpickr.l10ns.ko : 'default');

    flatpickr(el, {
      locale: locale,
      dateFormat: "Y-m",
      plugins: [new monthSelectPlugin({
        shorthand: true,
        dateFormat: "Y-m",
        altFormat: "Y-m"
      })],
      onChange: function(selectedDates, dateStr){
        // sync hidden fields for backward compatibility
        if (dateStr && /^\\d{4}-\\d{2}$/.test(dateStr)){
          document.getElementById('year_hidden').value = dateStr.substring(0,4);
          document.getElementById('month_hidden').value = dateStr.substring(5,7);
        }
        // auto submit (keep existing behavior)
        if (el.form) el.form.submit();
      }
    });
    return true;
  }

  // Init after DOM ready, retry a few times (CDN timing)
  document.addEventListener('DOMContentLoaded', function(){
    var tries = 0;
    var timer = setInterval(function(){
      tries++;
      if (initMonthPicker() || tries > 20) clearInterval(timer);
    }, 150);
  });
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

