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

/* Alert styling */
.alert {
  padding: 12px 14px;
  border-radius: 6px;
  border: 1px solid;
}
.alert-warning {
  background-color: #fef3c7;
  border-color: #fbbf24;
  color: #92400e;
}
.alert hr {
  border: none;
  border-top: 1px solid rgba(0,0,0,.1);
  margin: 8px 0;
}
.mt-2 { margin-top: 8px; }
.mb-2 { margin-bottom: 8px; }
.my-1 { margin-top: 4px; margin-bottom: 4px; }

</style>

<div class="ver2-wrap">
  <div class="ver2-title"><?= t('partner.title','Partner Settlement') ?></div>
  <div class="ver2-sub">- <?= t('partner.subtitle','Global Master Report (ver2)') ?></div>

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
          <th><?= t('calendar.weekday_sun_short','S') ?></th><th><?= t('calendar.weekday_mon_short','M') ?></th><th><?= t('calendar.weekday_tue_short','T') ?></th><th><?= t('calendar.weekday_wed_short','W') ?></th><th><?= t('calendar.weekday_thu_short','T') ?></th><th><?= t('calendar.weekday_fri_short','F') ?></th><th><?= t('calendar.weekday_sat_short','S') ?></th>
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
                    echo "<div class='badge'>" . t('partner.badge_settled','Settled') . "</div>";
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
    <h3><?= htmlspecialchars($settle_date) ?> <?= t('partner.gm_settlement','Global Master Settlement') ?></h3>

    <?php if (empty($gm_rows)): ?>
      <p style="margin:0; color:#666;"><?= t('msg.no_data','No data available.') ?></p>
    <?php else: ?>
      <table class="tbl">
        <thead>
          <tr>
            <th style="width:180px;"><?= t('common.id','ID') ?></th>
            <th><?= t('common.wallet_address','Wallet Address') ?></th>
            <th style="width:160px; text-align:right;"><?= t('partner.settle_amount','Settlement Amount') ?></th>
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
            <td colspan="2" style="text-align:right;"><?= t('common.total','Total') ?></td>
            <td class="amount"><?= number_format((float)$gm_total, 2) ?></td>
          </tr>
        </tfoot>
      </table>

      <!-- Residual policy notice -->
      <div class="alert alert-warning mt-2 mb-2" style="font-size:13px;">
        <div><b><?= t('policy.notice','정책 안내') ?></b> : <?= t('policy.residual_desc','잔액(Residual)은 운영사 계정 (user_id=5) 귀속으로 처리됩니다.') ?></div>

        <?php if (isset($profit) || isset($partner_sum) || isset($company_residual)) : ?>
          <hr class="my-1">
          <div><?= t('report.total_profit','총 이익금') ?>: <b><?= isset($profit) ? number_format((float)$profit, 2) : '-' ?></b> USDT</div>
          <div><?= t('report.partner_sum','파트너 합계') ?>: <b><?= isset($partner_sum) ? number_format((float)$partner_sum, 2) : '-' ?></b> USDT</div>
          <div><?= t('report.company_residual_desc','회사 귀속분') ?>: <b><?= isset($company_residual) ? number_format((float)$company_residual, 2) : '-' ?></b> USDT</div>

          <?php if (isset($company_residual) && (float)$company_residual > 0) : ?>
            <div class="mt-1">※ <?= t('policy.decimal_handling','소수점 처리 정책에 따라 발생한 잔액 USDT는 운영사 계정(user_id=5) 귀속분으로 산입되었습니다.') ?></div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <?php if ($is_settled): ?>
        <div style="margin-top:14px; text-align:right;">
          <button type="button" class="btn-disabled" disabled><?= t('partner.settle_done','Settlement Completed') ?></button>
        </div>
      <?php else: ?>
        <form method="post" action="settle_confirm.php"
              onsubmit="return confirm(<?= json_encode(sprintf(t('partner.confirm_settle_date','Confirm settlement for %s?'), $settle_date)) ?>);">
          <input type="hidden" name="action" value="confirm_sent">
          <input type="hidden" name="settle_date" value="<?= htmlspecialchars($settle_date) ?>">
          <input type="hidden" name="level" value="admin">
          <input type="hidden" name="redirect" value="Partner_accounts_v2.php?year_month=<?= htmlspecialchars($year_month) ?>&settle_date=<?= htmlspecialchars($settle_date) ?>">

          <div style="margin-top:14px; text-align:right;">
            <button type="submit"
                    style="padding:10px 18px;
                           background:#2b6cb0;
                           color:#fff;
                           border:none;
                           border-radius:6px;
                           font-size:14px;
                           cursor:pointer;">
              <?= t('partner.settle_confirm_btn','Confirm Settlement') ?>
            </button>
          </div>
        </form>
      <?php endif; ?>

      <!-- ✅ 감사로그 (Audit Logs) -->
      <?php if (!empty($audit_logs)): ?>
        <div class="card" style="margin-top:12px; padding:12px;">
          <div style="font-weight:800; margin-bottom:8px;">
            <?= t('audit.recent_logs','Recent Audit Logs (Last 10)') ?>
          </div>
          <table class="tbl" style="font-size:12px;">
            <thead>
              <tr>
                <th style="width:150px;"><?= t('common.time','Time') ?></th>
                <th style="width:120px;"><?= t('audit.event','Event') ?></th>
                <th style="width:100px;"><?= t('audit.actor','Actor') ?></th>
                <th style="width:110px;"><?= t('audit.ip','IP') ?></th>
                <th style="width:130px; text-align:right;"><?= t('audit.gm_payout_total','GM Payout') ?></th>
                <th style="width:130px; text-align:right;"><?= t('audit.company_residual','Company Residual') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($audit_logs as $lg): ?>
                <tr>
                  <td><?= htmlspecialchars($lg['created_at']) ?></td>
                  <td><?= htmlspecialchars($lg['event_code']) ?></td>
                  <td><?= htmlspecialchars(($lg['actor_role'] ?? '-') . ' #' . ($lg['actor_user_id'] ?? '-')) ?></td>
                  <td><?= htmlspecialchars($lg['ip'] ?? '-') ?></td>
                  <td class="amount"><?= number_format((float)(($lg['profit_total'] - $lg['partner_sum']) ?? 0), 2) ?> USDT</td>
                  <td class="amount"><?= number_format((float)($lg['company_residual'] ?? 0), 2) ?> USDT</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div style="margin-top:6px; color:#666; font-size:11px;">
            <?= t('audit.note','* This log is stored in admin_audit_log for dispute-proof audit.') ?>
          </div>
        </div>
      <?php else: ?>
        <div style="margin-top:10px; color:#888; font-size:12px;">
          <?= t('audit.no_logs','No audit logs for this date.') ?>
        </div>
      <?php endif; ?>


    <?php endif; ?>
  </div>

</div>


</div>