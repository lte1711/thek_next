<?php
/**
 * Country 페이지 공통 페이지네이션 UI
 * 
 * 필요 변수:
 * - $page: 현재 페이지 번호
 * - $total_pages: 전체 페이지 수
 * - $total_count: 전체 데이터 개수
 * - $base_query: 필터 파라미터 querystring
 */
?>

<?php if ($total_pages > 1): ?>
<div class="country-pagination">
  <div class="country-pagination__info">
    Total: <?= number_format($total_count) ?> / Page <?= $page ?> of <?= $total_pages ?>
  </div>

  <div class="country-pagination__nav">
    <?php if ($page > 1): ?>
      <a class="btn btn-sm btn-outline-secondary" 
         href="?<?= $base_query ?>&page=<?= $page-1 ?>">Prev</a>
    <?php endif; ?>

    <?php
      // 페이지 번호 윈도우 (현재 페이지 ±3)
      $start = max(1, $page - 3);
      $end = min($total_pages, $page + 3);
      
      // 첫 페이지 표시 (윈도우와 떨어져있으면)
      if ($start > 1):
    ?>
      <a class="btn btn-sm btn-outline-primary" 
         href="?<?= $base_query ?>&page=1">1</a>
      <?php if ($start > 2): ?>
        <span class="btn btn-sm disabled">...</span>
      <?php endif; ?>
    <?php endif; ?>

    <?php
      // 윈도우 범위 페이지들
      for ($p = $start; $p <= $end; $p++):
    ?>
      <?php if ($p === $page): ?>
        <span class="btn btn-sm btn-primary disabled"><?= $p ?></span>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-primary" 
           href="?<?= $base_query ?>&page=<?= $p ?>"><?= $p ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php
      // 마지막 페이지 표시 (윈도우와 떨어져있으면)
      if ($end < $total_pages):
    ?>
      <?php if ($end < $total_pages - 1): ?>
        <span class="btn btn-sm disabled">...</span>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-primary" 
         href="?<?= $base_query ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
    <?php endif; ?>

    <?php if ($page < $total_pages): ?>
      <a class="btn btn-sm btn-outline-secondary" 
         href="?<?= $base_query ?>&page=<?= $page+1 ?>">Next</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
