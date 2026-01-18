<?php
// $year_month, $settle_date, $calendar_data, $gm_rows, $gm_total 를 Partner_accounts_v2.php에서 받음

// 달력 계산
$dt = DateTime::createFromFormat('Y-m-d', $year_month . '-01');
$year  = (int)$dt->format('Y');
$month = (int)$dt->format('m');

$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$lastDay  = (clone $firstDay)->modify('last day of this month');
$startWeekday = (int)$firstDay->format('w'); // 0=Sun
$daysInMonth  = (int)$lastDay->format('j');

// 이전/다음 월
$prevMonth = (clone $firstDay)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $firstDay)->modify('+1 month')->format('Y-m');

// 선택일 표시용
$selected = $settle_date;
?>

<style>
/* ver2 달력 전용(필요 최소만) */
.ver2-wrap { max-width: 980px; margin: 0 auto; }
.ver2-title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
.ver2-sub { color:#666; margin-bottom: 18px; }

.cal-head {
  display:flex; align-items:center; justify-content:center;
  gap:18px; margin: 10px 0 14px;
}
.cal-head a {
  text-decoration:none; font-size:24px; padding: 4px 10px;
}
.cal-head .ym {
  font-size: 34px; font-weight: 800; letter-spacing: 1px;
}

.calendar {
  width:100%; border-collapse: collapse;
  background:#fff; border-radius: 10px; overflow:hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
}
.calendar th, .calendar td {
  border-bottom: 1px solid #eee;
  padding: 10px;
  vertical-align: top;
  height: 76px;
}
.calendar th { background:#fafafa; font-weight:700; text-align:center; }
.calendar td { position:relative; }
.calendar .daynum { font-size: 22px; font-weight: 800; }
.calendar .amt { margin-top: 6px; font-size: 12px; color:#444; }
.calendar a.daylink { display:block; text-decoration:none; color:inherit; }
.calendar td.selected {
  outline: 3px solid #2b6cb0;
  outline-offset: -3px;
  background: rgba(43,108,176,.06);
}
.card {
  margin-top: 18px;
  background:#fff;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  padding: 16px;
}
.card h3 { margin: 0 0 12px; font-size: 18px; }
.tbl { width:100%; border-collapse: collapse; }
.tbl th, .tbl td { border:1px solid #e6e6e6; padding: 10px; text-align:left; }
.tbl th { background:#f6f8fb; }
.tbl td.amount { text-align:right; font-weight:700; }
.tbl tfoot td { font-weight:800; }

.ver2-grid{
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 18px;
  align-items: start;
}
@media (max-width: 980px){
  .ver2-grid{ grid-template-columns: 1fr; }
}
.calendar td.today {
  background: rgba(0,0,0,.03);
}
.calendar td.today .daynum{
  text-decoration: underline;
}
.calendar td.settled {
  background: rgba(34,197,94,.10); /* 연한 초록 느낌 */
}
.calendar td.settled .badge {
  display:inline-block;
  margin-top:6px;
  font-size:11px;
  padding:2px 6px;
  border-radius:999px;
  background: rgba(34,197,94,.18);
}
.btn-disabled {
  padding:10px 18px;
  background:#aaa;
  color:#fff;
  border:none;
  border-radius:6px;
  font-size:14px;
  cursor:not-allowed;
}

</style>

<div class="ver2-wrap">
  <div class="ver2-title">1. 파트너 정산</div>
  <div class="ver2-sub">- 글로벌 마스터 리포트 (ver2)</div>

  <div class="cal-head">
    <a href="Partner_accounts_v2.php?year_month=<?= htmlspecialchars($prevMonth) ?>&settle_date=<?= htmlspecialchars($prevMonth) ?>-01">‹</a>
    <div class="ym"><?= htmlspecialchars(strtoupper(date('F', mktime(0,0,0,$month,1,$year)))) ?> <?= htmlspecialchars((string)$year) ?></div>
    <a href="Partner_accounts_v2.php?year_month=<?= htmlspecialchars($nextMonth) ?>&settle_date=<?= htmlspecialchars($nextMonth) ?>-01">›</a>
  </div>

  <div class="ver2-grid">

  <!-- 왼쪽: 달력 -->
  <div>
    <table class="calendar">
      <thead>
        <tr>
          <th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <?php
          $today = date('Y-m-d');

          for ($i=0; $i<$startWeekday; $i++) echo "<td></td>";

          $cell = $startWeekday;
          for ($d=1; $d<=$daysInMonth; $d++, $cell++) {
              $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
              $amt = $calendar_data[$dateStr] ?? null;

                $classes = [];
                if ($dateStr === $selected) $classes[] = 'selected';
                if ($dateStr === $today) $classes[] = 'today';
                if (isset($settled_dates[$dateStr])) $classes[] = 'settled';
                $tdClass = implode(' ', $classes);

              echo "<td class='{$tdClass}'>";
              echo "<a class='daylink' href='Partner_accounts_v2.php?year_month=" . htmlspecialchars($year_month) . "&settle_date=" . htmlspecialchars($dateStr) . "'>";
              echo "<div class='daynum'>{$d}</div>";
              if ($amt !== null && (float)$amt != 0.0) {
                  echo "<div class='amt'>" . number_format((float)$amt, 2) . "</div>";
              }

                if (isset($settled_dates[$dateStr])) {
                    echo "<div class='badge'>정산완료</div>";
                }

              echo "</a>";
              echo "</td>";

              if ($cell % 7 === 6 && $d !== $daysInMonth) echo "</tr><tr>";
          }

          $remain = (7 - (($cell) % 7)) % 7;
          for ($i=0; $i<$remain; $i++) echo "<td></td>";
          ?>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- 오른쪽: GM 상세표 -->
  <div class="card">
    <h3><?= htmlspecialchars($settle_date) ?> 글로벌 마스터 정산</h3>

    <?php if (empty($gm_rows)): ?>
      <p style="margin:0; color:#666;">해당 날짜 데이터가 없습니다.</p>
    <?php else: ?>
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:180px;">아이디</th>
            <th>wallet address</th>
            <th style="width:160px; text-align:right;">정산 금액</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($gm_rows as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td><?= htmlspecialchars($row['wallet']) ?></td>
              <td class="amount"><?= number_format((float)$row['amount'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" style="text-align:right;">합계</td>
            <td class="amount"><?= number_format((float)$gm_total, 2) ?></td>
          </tr>
        </tfoot>
      </table>

<?php if (!empty($gm_rows)): ?>

  <?php if ($is_settled): ?>
      <div style="margin-top:14px; text-align:right;">
          <button type="button" class="btn-disabled" disabled>정산 완료</button>
      </div>
  <?php else: ?>
      <form method="post" action="settle_confirm.php"
            onsubmit="return confirm('<?= $settle_date ?> 정산을 확정하시겠습니까?');">
          <input type="hidden" name="settle_date" value="<?= htmlspecialchars($settle_date) ?>">
          <input type="hidden" name="from_page" value="partner_accounts_v2">

          <div style="margin-top:14px; text-align:right;">
              <button type="submit"
                      style="padding:10px 18px;
                             background:#2b6cb0;
                             color:#fff;
                             border:none;
                             border-radius:6px;
                             font-size:14px;
                             cursor:pointer;">
                  정산 확정
              </button>
          </div>
      </form>
  <?php endif; ?>

<?php endif; ?>



    <?php endif; ?>
  </div>

</div>


</div>
