<?php
// ✅ 정산 ON 후 alert 표시 (settle_toggle.php가 settle_seq를 넘겨줌)
if (isset($_GET['settle_seq']) && $_GET['settle_seq'] !== ''):
  $seq_for_alert = preg_replace('/[^0-9]/', '', (string)$_GET['settle_seq']);
  if ($seq_for_alert !== ''):
?>
<script>
  alert('정산 완료: 오늘 순번 <?= htmlspecialchars($seq_for_alert) ?>');
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
  <h2><a href="profit_share.php" class="title-link">PROFIT SHARE LIST</a></h2>
  <form method="GET" class="month-form">
    <input type="hidden" name="user_id" value="<?= $user_id ?>">
    <input type="hidden" name="region" value="<?= htmlspecialchars($region ?? 'korea') ?>">
    <select name="month" onchange="this.form.submit()">
      <?php for ($m = 1; $m <= 12; $m++): ?>
        <option value="<?= str_pad($m,2,'0',STR_PAD_LEFT) ?>" <?= ($current_month == $m ? 'selected' : '') ?>>
          <?= date('F', mktime(0,0,0,$m,1)) ?>
        </option>
      <?php endfor; ?>
    </select>
  </form>
</div>

<table class="form-table">
  <thead>
    <tr>
      <th>Date</th>
      <th>Deposit</th>
      <th>C / L</th>
      <th>Withdrawal</th>
      <th>Profit Share</th>
      <th>State</th>
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
      $reject     = $row['reject_reason'];

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
      <button type="submit" class="toggle on" style="padding:6px 10px;">확인</button>
    </form>
  <?php endif; ?>
</td>

      <td><?= ($withdrawal == 1) ? '<span class="check">✅</span>' : ((int)$external===1 ? '<a href="investor_withdrawal.php?user_id='.$user_id.'&id='.$row['id'].'" class="fail">❌</a>' : '<span class="muted">⏳</span>') ?></td>
      <td><?= ($dividend == 1) ? '<span class="check">✅</span>' : ((int)$external===1 ? '<a href="investor_profit_share.php?user_id='.$user_id.'&id='.$row['id'].'" class="fail">❌</a>' : '<span class="muted">⏳</span>') ?></td>
      <td>
        <?php if ($reject): ?>
          <button class="confirm-btn" onclick="alert('Reject 사유: <?= htmlspecialchars($reject) ?>')">거절사유보기</button>

          <!-- ✅ Rejecting 해제: 최소 초기화만 수행 (settle_chk=0, reject_* NULL) → 이후 사용자가 다시 ON 진행 -->
          <form method="POST" action="reject_reset.php" style="display:inline; margin-left:6px;" onsubmit="return confirm('거절 상태를 해제하시겠습니까?\n해제 후 다시 ON 버튼으로 진행완료를 전송할 수 있습니다.');">
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
            <input type="hidden" name="region" value="<?= htmlspecialchars($region ?? 'korea') ?>">
            <input type="hidden" name="month" value="<?= htmlspecialchars($current_month) ?>">
            <button type="submit" class="toggle on" style="padding:6px 10px;">재진행</button>
          </form>

        <?php elseif ($all_four): ?>
          <button class="toggle off">OFF</button>
          <small>
            처리자: <?= htmlspecialchars($row['settled_by'] ?? '-') ?> /
            <?= htmlspecialchars($row['settled_date'] ?? '-') ?>
            <?php if ($seq_label !== '-'): ?>
              <br>오늘 순번: <b><?= htmlspecialchars($seq_label) ?></b>
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
