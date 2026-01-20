<?php
/**
 * Country Pages Common Filter Bar
 * 
 * Required variables:
 * - $region: current region (korea|japan)
 * 
 * Optional variables:
 * - $from_date: default from date (YYYY-MM-DD)
 * - $to_date: default to date (YYYY-MM-DD)
 * - $search_query: default search query
 * - $is_export_enabled: whether export button is enabled (default: false)
 * - $search_placeholder: placeholder text for search input (default: 'Username / Pair / Note')
 * - $export_onclick: JavaScript function to call for export (e.g., 'exportData()')
 */

// Set defaults
$from_date = $from_date ?? '';
$to_date = $to_date ?? '';
$search_query = $search_query ?? '';
$is_export_enabled = $is_export_enabled ?? false;
$search_placeholder = $search_placeholder ?? 'Username / Pair / Note';
$export_onclick = $export_onclick ?? 'exportData()';

// Get current page for reset
$current_page = basename($_SERVER['PHP_SELF']);
$reset_url = $current_page . '?region=' . urlencode($region);
?>

<form method="GET" class="country-filterbar">
  <input type="hidden" name="region" value="<?= htmlspecialchars($region) ?>">
  
  <div class="filter-left">
    <div class="filter-item filter-date">
      <label>From</label>
      <input type="text" class="js-date" name="from" value="<?= htmlspecialchars($from_date) ?>" 
             autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD">
    </div>
    
    <div class="filter-item filter-date">
      <label>To</label>
      <input type="text" class="js-date" name="to" value="<?= htmlspecialchars($to_date) ?>" 
             autocomplete="off" inputmode="none" placeholder="YYYY-MM-DD">
    </div>
    
    <div class="filter-item filter-search">
      <label>Search</label>
      <input type="text" name="q" class="filter-input" 
             value="<?= htmlspecialchars($search_query) ?>" 
             placeholder="<?= htmlspecialchars($search_placeholder) ?>">
    </div>
  </div>
  
  <div class="filter-right">
    <button type="submit" class="btn-filter btn-apply">Apply</button>
    <a href="<?= htmlspecialchars($reset_url) ?>" class="btn-filter btn-reset">Reset</a>
    <?php if ($is_export_enabled): ?>
      <button type="button" class="btn-filter btn-export" onclick="<?= htmlspecialchars($export_onclick) ?>">Export</button>
    <?php else: ?>
      <button type="button" class="btn-filter btn-export" disabled>Export</button>
    <?php endif; ?>
  </div>
</form>

<script>
// Initialize custom date picker for filter bar
(function(){
  function attach(selector, mode) {
    document.querySelectorAll(selector).forEach(input => {
      if (input.dataset.pickerAttached) return;
      input.dataset.pickerAttached = 'true';

      const m = mode || 'date';
      const fmt = m === 'month' ? 'yyyy-MM' : 'yyyy-MM-dd';

      function show(input, m){
        const val = input.value || new Date().toISOString().slice(0, m === 'month' ? 7 : 10);
        input.type = m;
        input.value = val;
        input.showPicker?.();
        setTimeout(() => input.focus(), 0);
      }

      input.type = 'text';
      input.addEventListener('focus', ()=> show(input, m));
      input.addEventListener('click', ()=> show(input, m));
      input.addEventListener('keydown', (e)=>{
        if (e.key.length === 1) e.preventDefault();
      });
    });
  }

  // Initialize date pickers on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ()=> attach('input.js-date', 'date'));
  } else {
    attach('input.js-date', 'date');
  }
})();
</script>
