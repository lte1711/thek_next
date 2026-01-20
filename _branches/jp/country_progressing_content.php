<?php
$region = $_GET['region'] ?? 'korea';
$countryLabel = ($region === 'japan') ? 'Japan' : 'Korea';

// Filter parameters
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';
$search_query = $_GET['q'] ?? '';
$is_export_enabled = false; // Progressing page does not have export yet
$search_placeholder = 'Username / Pair';
$current_page = 'country_progressing.php';
?>
<div class="country-page-header">
  <h1 class="country-page-title"><?= $countryLabel ?> - Progressing</h1>
</div>

<?php include __DIR__ . '/includes/country_filterbar.php'; ?>

<style>
  .status-ok { color:#22c55e; font-weight:700; }
  .status-no { color:#ff2d6f; font-weight:700; }
</style>

<table>
  <tr>
    <th><?= t('table.date','Date') ?></th>
    <th><?= t('table.username','Username') ?></th>
    <th><?= t('table.pair','Pair') ?></th>
    <th><?= t('table.deposit','Deposit') ?></th>
    <th><?= t('table.withdrawal','Withdrawal') ?></th>
    <th><?= t('table.profit_share','P/S(Profit Share)') ?></th>
    <th><?= t('table.note','Note') ?></th>
    <th><?= t('table.settled','Settled') ?></th>
  </tr>

  <?php if (!$result_progress || mysqli_num_rows($result_progress) === 0): ?>
    <tr><td colspan="8"><?= t('msg.no_data') ?></td></tr>
  <?php else: ?>
    <?php while($row = mysqli_fetch_assoc($result_progress)): ?>
      <?php
        $deposit = (float)($row['deposit_status'] ?? 0);
        $deposit_chk = ($deposit > 0) ? 'V' : 'X';

        // 상태 표시는 korea_progressing의 금액 컬럼이 아니라,
        // user_transactions의 chk 컬럼(승인 플래그)을 기준으로 표시한다.
        $w_chk = (int)($row['withdrawal_chk'] ?? 0);
        $d_chk = (int)($row['dividend_chk'] ?? 0);

        $withdrawal_chk = ($w_chk === 1) ? 'V' : 'X';

        // Withdrawal 미완료면 Profit Share는 무조건 X
        $pl_chk = ($w_chk === 1 && $d_chk === 1) ? 'V' : 'X';

        // settle_chk은 더 이상 필수 체크로 사용하지 않음.
        // 최종 완료로는 dividend_chk=1(Profit Share 완료)을 기준으로 표시.
        if ($d_chk === 1) {
          $settled_by = trim((string)($row['settled_by'] ?? ''));
          if ($settled_by === '') {
            $settled_by = (string)($row['username'] ?? '');
          }
        } else {
          $settled_by = '-';
        }
      ?>
      <tr>
        <td><?= htmlspecialchars($row['tx_date'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['username'] ?? '-') ?></td>
        <td><?= htmlspecialchars($row['pair'] ?? '-') ?></td>

        <td style="text-align:center; font-weight:bold;">
          <?= $deposit_chk ?>
        </td>

        <td style="text-align:center; font-weight:bold;">
          <?= $withdrawal_chk ?>
        </td>

        <td style="text-align:center; font-weight:bold;">
          <?= $pl_chk ?>
        </td>

        <td>
          <?= htmlspecialchars(trim((string)($row['notes'] ?? '')) !== '' ? $row['notes'] : '-') ?>
        </td>

        <td><?= htmlspecialchars($settled_by) ?></td>
      </tr>
    <?php endwhile; ?>
  <?php endif; ?>
</table>
