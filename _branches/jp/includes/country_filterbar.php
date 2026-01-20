<?php
/**
 * Country Pages Common Filter Bar
 * 
 * Required variables:
 * - $region: current region (korea|japan)
 * - $current_page: current PHP file name (e.g., 'country_ready.php')
 * 
 * Optional variables:
 * - $from_date: default from date (YYYY-MM-DD)
 * - $to_date: default to date (YYYY-MM-DD)
 * - $search_query: default search query
 * - $is_export_enabled: whether export button is enabled (default: false)
 * - $search_placeholder: placeholder text for search input (default: 'Username / Pair / Note')
 */

// Set defaults
$from_date = $from_date ?? '';
$to_date = $to_date ?? '';
$search_query = $search_query ?? '';
$is_export_enabled = $is_export_enabled ?? false;
$search_placeholder = $search_placeholder ?? 'Username / Pair / Note';
$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
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
    <a href="<?= htmlspecialchars($current_page) ?>?region=<?= htmlspecialchars($region) ?>" 
       class="btn-filter btn-reset">Reset</a>
    <?php if ($is_export_enabled): ?>
      <button type="button" class="btn-filter btn-export" onclick="exportData()">Export</button>
    <?php else: ?>
      <button type="button" class="btn-filter btn-export" disabled>Export</button>
    <?php endif; ?>
  </div>
</form>
