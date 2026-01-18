<h2><?= ucfirst($region) ?> - Progressing</h2>

<style>
  .status-ok { color:#22c55e; font-weight:700; }
  .status-no { color:#ff2d6f; font-weight:700; }
</style>

<table>
  <tr>
    <th>Date</th>
    <th>Username</th>
    <th>Pair</th>
    <th>Deposit</th>
    <th>Withdrawal</th>
    <th>P/S(Profit Share)</th>
    <th>Note</th>
    <th>Settled</th>
  </tr>

  <?php if (!$result_progress || mysqli_num_rows($result_progress) === 0): ?>
    <tr><td colspan="8">No data available.</td></tr>
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
