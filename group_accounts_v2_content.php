<?php
$dt = DateTime::createFromFormat('Y-m-d', $year_month . '-01');
$year  = (int)$dt->format('Y');
$month = (int)$dt->format('m');

$firstDay = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$lastDay  = (clone $firstDay)->modify('last day of this month');
$startWeekday = (int)$firstDay->format('w'); // 0=Sun
$daysInMonth  = (int)$lastDay->format('j');

$prevMonth = (clone $firstDay)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $firstDay)->modify('+1 month')->format('Y-m');

$today = date('Y-m-d');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function q($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$level_kor = $level_names[$level] ?? $level;
$nl = $next_level[$level] ?? null;
$nl_kor = $nl ? ($level_names[$nl] ?? $nl) : '';
?>

<!-- ✅ 새로고침/날짜 변경 시 스크롤 위치가 유지되는 현상 방지 -->
<script>
  try {
    if ('scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }
  } catch (e) {}

  window.addEventListener('load', function () {
    window.scrollTo(0, 0);
  });
</script>

<style>
.v2-wrap{ max-width: 1040px; margin: 0 auto; }
.v2-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.v2-title{ font-size:22px; font-weight:800; margin:0; }
.v2-sub{ margin-top:6px; color:#666; }

.card{
  background:#fff; border-radius:10px;
  box-shadow: 0 2px 10px rgba(0,0,0,.06);
  padding:14px;
}

.mini-cal{ width: 340px; }
.mini-cal .top{
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:10px;
}
.mini-cal .top a{
  text-decoration:none; font-size:22px; padding:2px 10px;
}
.mini-cal .ym{ font-size:18px; font-weight:800; }

.cal{ width:100%; border-collapse: collapse; table-layout: fixed; }
.cal th, .cal td{ text-align:center; padding: 8px 0; border-bottom:1px solid #eee; }
.cal th{ font-size:12px; color:#666; background:#fafafa; }
.cal td{ font-size:13px; }
.cal a{
  display:inline-block; width:34px; height:30px; line-height:30px;
  border-radius:8px; text-decoration:none; color:inherit;
}
.cal td.selected a{ outline:2px solid #2b6cb0; outline-offset: -2px; background: rgba(43,108,176,.08); font-weight:800; }
.cal td.today a{ text-decoration: underline; }
.cal td.settled a{ background:#f1f1f1; color:#999; }
.cal td.settled .dot{ display:block; width:4px; height:4px; border-radius:999px; margin:2px auto 0; background:#aaa; }

.breadcrumb{
  margin-top:12px; padding:10px 12px; border:1px solid #eee;
  border-radius:10px; background:#fafafa; font-size:13px; color:#444;
}

.main-area{ flex:1; min-width: 320px; }
.state-row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
.pill{ padding:6px 10px; border-radius:999px; background:#f6f8fb; border:1px solid #e8eef7; font-size:12px; }
.pill.settled{ background:#f1f1f1; border-color:#e2e2e2; color:#666; }

.placeholder{
  margin-top:14px; padding:14px; border:1px dashed #ddd;
  border-radius:10px; color:#666; background:#fff;
}
</style>

<div class="v2-wrap">
  <div class="v2-head">

    <!-- ✅ 왼쪽: 미니 달력 (현재 레벨/타겟 기준 완료 표시) -->
    <div class="card mini-cal">
      <div class="top">
        <?php
          $cal_keep = "";
        $cal_note = "";
        // ✅ 달력 이동(날짜/월 변경)은 최상위로 리셋: date/year_month만 전달
        ?>
        <a href="group_accounts_v2.php?year_month=<?=h($prevMonth)?>&date=<?=h($prevMonth)?>-01">‹</a>
        <div class="ym"><?=h($year)?>-<?=h(sprintf('%02d',$month))?></div>
        <a href="group_accounts_v2.php?year_month=<?=h($nextMonth)?>&date=<?=h($nextMonth)?>-01">›</a>
      </div>

      <table class="cal">
        <thead>
          <tr><th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th></tr>
        </thead>
        <tbody>
          <tr>
          <?php
            for ($i=0; $i<$startWeekday; $i++) echo "<td></td>";

            $cell = $startWeekday;
            for ($d=1; $d<=$daysInMonth; $d++, $cell++){
              $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);

              $classes = [];
              if ($dateStr === $date) $classes[] = 'selected';
              if ($dateStr === $today) $classes[] = 'today';
              if (isset($settled_dates[$dateStr])) $classes[] = 'settled';
              $tdClass = implode(' ', $classes);

              echo "<td class='{$tdClass}'>";
              echo "<a href='group_accounts_v2.php?year_month=" . h($year_month) . "&date=" . h($dateStr) . "'>";
              echo $d;
              echo "</a>";
              if (isset($settled_dates[$dateStr])) echo "<span class='dot'></span>";
              echo "</td>";

              if ($cell % 7 === 6 && $d !== $daysInMonth) echo "</tr><tr>";
            }

            $remain = (7 - (($cell) % 7)) % 7;
            for ($i=0; $i<$remain; $i++) echo "<td></td>";
          ?>
          </tr>
        </tbody>
      </table>

      <div style="margin-top:10px; font-size:12px; color:#666;">
        선택: <strong><?=h($date)?></strong>
        <?php if (!empty($is_settled)): ?>
          <span style="margin-left:6px; color:#666;">(정산 완료)</span>
        <?php else: ?>
          <span style="margin-left:6px; color:#2b6cb0;">(미정산)</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- ✅ 오른쪽: 롤다운/리스트/테이블 -->
    <div class="card main-area">
      <p class="v2-title" style="margin:0;">조직 정산 ver2</p>
      <div class="v2-sub">- 롤다운(전체/선택) 구조 + 미니 달력 + 완료일 회색처리</div>

      <div class="breadcrumb">
        현재 위치: <strong><?= h($path !== '' ? $path : 'admin 전체') ?></strong>
      </div>

      <div class="state-row">
        <div class="pill">level: <strong><?=h($level)?></strong></div>
        <div class="pill">target: <strong><?=h($target !== '' ? $target : '-')?></strong></div>
        <div class="pill <?= !empty($is_settled) ? 'settled' : '' ?>">
          상태: <strong><?= !empty($is_settled) ? '정산 완료' : '미정산' ?></strong>
        </div>

        <?php if (isset($progress) && ($view === 'all')): ?>
          <div class="pill" style="background:#fff; border-color:#eee;">
            pending: <strong><?= number_format((int)($progress['pending_cnt'] ?? 0)) ?></strong>
            (<?= number_format((float)($progress['pending_amt'] ?? 0), 2) ?>)
          </div>
          <div class="pill" style="background:#fff; border-color:#eee;">
            sent: <strong><?= number_format((int)($progress['sent_cnt'] ?? 0)) ?></strong>
            (<?= number_format((float)($progress['sent_amt'] ?? 0), 2) ?>)
          </div>
        <?php endif; ?>
      </div>

      <!-- ✅ 드롭다운 -->
      <div style="margin-top:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
        <div style="flex:1; min-width:240px;">
          <label style="display:block; font-size:12px; color:#666; margin-bottom:6px;">
            <?= q($level_kor) ?> 선택
          </label>

          <?php
            $all_url = "group_accounts_v2.php?year_month=" . q($year_month)
                     . "&date=" . q($date)
                     . "&level=" . q($level)
                     . "&target=" . q($target)
                     . "&path=" . q($path)
                     . "&view=all";
          ?>

          <select onchange="if(this.value) location.href=this.value"
                  style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">

            <!-- ✅ 기본은 항상 '전체'가 선택되도록 ("선택하세요" 제거) -->
            <option value="<?= $all_url ?>" <?= ($view !== 'list' ? 'selected' : '') ?>>전체</option>

            <?php foreach ($dropdown_options as $opt): ?>
              <?php
                $nl2 = $next_level[$level] ?? null;
                if ($nl2 === null) continue;

                $new_path = ($path ? ($path . " > " . $opt) : (($level === 'admin' ? "어드민 > " : "") . $opt));
                // ✅ 하위로 들어가도 기본은 항상 '전체' 화면(view=all)
                $url = "group_accounts_v2.php?year_month=" . q($year_month)
                     . "&date=" . q($date)
                     . "&level=" . q($nl2)
                     . "&target=" . q($opt)
                     . "&path=" . q($new_path)
                     . "&view=all";
              ?>
              <option value="<?= $url ?>"><?= q($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($view === 'all'): ?>
        <div style="display:flex; gap:8px; align-items:center;">

          <?php
            $pending_cnt = (int)($progress['pending_cnt'] ?? 0);
            $can_action = ($pending_cnt > 0);
            $back = "group_accounts_v2.php?year_month=" . q($year_month)
                  . "&date=" . q($date)
                  . "&level=" . q($level)
                  . "&target=" . q($target)
                  . "&path=" . q($path)
                  . "&view=all";
          ?>

          <!-- ✅ 1) 다운로드(안전): CSV만 내려받기, status 변경 없음 -->
          <form method="post" action="settle_confirm_v2.php" style="margin:0;">
            <input type="hidden" name="action" value="download_only">
            <input type="hidden" name="settle_date" value="<?= h($date) ?>">
            <input type="hidden" name="level" value="<?= h($level) ?>">
            <input type="hidden" name="target" value="<?= h($target) ?>">
            <input type="hidden" name="redirect" value="<?= h($back) ?>">

            <button type="submit"
                    style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:#fff; cursor:pointer;"
                    <?= !$can_action ? 'disabled style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:#f5f5f5; color:#999; cursor:not-allowed;"' : '' ?>>
              DOWNLOAD
            </button>
          </form>

          <!-- ✅ 2) SENT 확정(안전장치): pending → sent 변경 (confirm) -->
          <form method="post" action="settle_confirm_v2.php" style="margin:0;" onsubmit="return confirm('정말로 SENT 확정하시겠습니까?\n(현재 화면 범위의 pending 항목이 sent로 변경됩니다)');">
            <input type="hidden" name="action" value="confirm_sent">
            <input type="hidden" name="settle_date" value="<?= h($date) ?>">
            <input type="hidden" name="level" value="<?= h($level) ?>">
            <input type="hidden" name="target" value="<?= h($target) ?>">
            <input type="hidden" name="redirect" value="<?= h($back) ?>">

            <button type="submit"
                    style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:#fff; cursor:pointer;"
                    <?= !$can_action ? 'disabled style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; background:#f5f5f5; color:#999; cursor:not-allowed;"' : '' ?>>
              SENT 확정
            </button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <!-- ✅ 결과 영역 -->
      <?php if ($view === 'all'): ?>

        <div style="margin-top:14px;">
          <div style="font-weight:800; margin-bottom:10px;">
            <?= q($date) ?> <?= q($level_kor) ?> 정산 테이블
            <?php if ($target !== '' && $level !== 'admin'): ?>
              <span style="color:#666; font-weight:600;">(상위: <?= q($target) ?>)</span>
            <?php endif; ?>
          </div>

          <?php if (empty($table_rows)): ?>
            <div class="placeholder">
              정산 테이블 데이터가 없습니다.
              <?php if ($level !== 'admin' && $target === ''): ?>
                <br>(상위 선택이 필요합니다)
              <?php endif; ?>
            </div>
          <?php else: ?>
            <table class="tbl">
              <thead>
                <tr>
                  <th style="width:220px;">아이디</th>
                  <th>코드페이</th>
                  <th style="width:190px; text-align:center;">진행상태</th>
                  <th style="width:180px; text-align:right;">정산 금액</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($table_rows as $r): ?>
                  <?php
                    $u = (string)$r['username'];
                    $rp = $row_progress[$u] ?? ['pending_cnt'=>0,'sent_cnt'=>0,'pending_amt'=>0,'sent_amt'=>0];
                    $rp_pending = (int)$rp['pending_cnt'];
                    $rp_sent    = (int)$rp['sent_cnt'];
                    $rp_pa      = (float)$rp['pending_amt'];
                    $rp_sa      = (float)$rp['sent_amt'];
                    $done = ($rp_pending === 0 && ($rp_sent > 0 || ($rp_sa > 0)));
                  ?>
                  <tr>
                    <td><?= q($r['username']) ?></td>
                    <td><?= q($r['code']) ?></td>
                    <td style="text-align:center; font-size:12px; color:#555;">
                      <?php if ($done): ?>
                        <span style="color:#666;">완료</span>
                      <?php else: ?>
                        <span style="color:#2b6cb0;">미완료</span><br>
                        <span style="color:#666;">pending <?= number_format($rp_pending) ?> (<?= number_format($rp_pa, 2) ?>)</span>
                      <?php endif; ?>
                    </td>
                    <td class="amount"><?= number_format((float)$r['amount'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="3" style="text-align:right; font-weight:800;">합계</td>
                  <td class="amount"><?= number_format((float)$table_total, 2) ?></td>
                </tr>
              </tfoot>
            </table>
          <?php endif; ?>
        </div>

      <?php else: ?>

        <?php if ($nl === null): ?>
          <div class="placeholder">
            ✅ 최하위 단계입니다. (더 이상 하위 리스트가 없습니다)<br>
            “전체”를 누르면 정산 테이블이 표시됩니다.
          </div>
        <?php else: ?>

          <div style="margin-top:14px;">
            <div style="font-weight:800; margin-bottom:10px;">
              <?= q($date) ?> <?= q($nl_kor) ?> 하위 리스트
            </div>

            <?php if (!empty($need_select_parent)): ?>
              <div class="placeholder">어드민을 먼저 선택해주세요.</div>

            <?php elseif (empty($child_rows)): ?>
              <div class="placeholder">해당 날짜에 하위 데이터가 없습니다.</div>

            <?php else: ?>
              <table class="tbl">
                <thead>
                  <tr>
                    <th style="width:220px;">아이디</th>
                    <th style="width:160px; text-align:right;">정산 금액</th>
                    <th style="width:140px; text-align:center;">이동</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($child_rows as $r): ?>
                    <?php
                      $child = $r['username'];
                      $new_path = ($path ? ($path . " > " . $child) : ($level_kor . " > " . $child));
                      $url = "group_accounts_v2.php?year_month=" . q($year_month)
                           . "&date=" . q($date)
                           . "&level=" . q($nl)
                           . "&target=" . q($child)
                           . "&path=" . q($new_path)
                           . "&view=list";
                    ?>
                    <tr>
                      <td><?= q($child) ?></td>
                      <td class="amount"><?= number_format((float)$r['amount'], 2) ?></td>
                      <td style="text-align:center;">
                        <a href="<?= $url ?>" style="text-decoration:none; font-weight:700;">하위 보기</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <td style="text-align:right; font-weight:800;">합계</td>
                    <td class="amount"><?= number_format((float)$child_total, 2) ?></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            <?php endif; ?>
          </div>

        <?php endif; ?>

      <?php endif; ?>

    </div><!-- /.card main-area -->
  </div><!-- /.v2-head -->
</div><!-- /.v2-wrap -->
