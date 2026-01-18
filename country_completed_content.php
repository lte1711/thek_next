<!-- ✅ Completed Transactions (C / L) -->
<h2><?= ucfirst($region) ?> - Completed Transactions (C / L)</h2>

<?php if (!isset($result_completed) || !$result_completed): ?>
  <div style="padding:10px 0; color:#666;">No completed transactions.</div>
<?php elseif (mysqli_num_rows($result_completed) === 0): ?>
  <div style="padding:10px 0; color:#666;">No completed transactions.</div>
<?php else: ?>
  <table>
    <tr>
      <th>Date</th>
      <th>Username</th>
      <th>XM Account</th>
      <th>Ultima Account</th>
      <th>Deposit</th>
      <th>Status</th>
    </tr>

    <?php while ($c = mysqli_fetch_assoc($result_completed)): ?>
      <?php
        // status priority:
        // 1) ready_trading.status (approved/rejected)
        // 2) user_transactions.settle_chk=2 => Rejecting (in progress)
        // 3) fallback => approved
        $status = trim((string)($c['status'] ?? ''));

        if ($status === '' && (int)($c['settle_chk'] ?? 0) === 2) {
          $status = 'Rejecting';
        }

        if ($status === '') {
          $status = 'approved';
        }
      ?>
      <tr>
        <td><?= htmlspecialchars($c['tx_date'] ?? ($c['settled_date'] ?? '-')) ?></td>
        <td><?= htmlspecialchars($c['username'] ?? '-') ?></td>

        <td style="text-align:center; white-space:pre-line;">
          id: <?= htmlspecialchars($c['xm_id'] ?? '-') ?>

          pw: <?= htmlspecialchars($c['xm_pw'] ?? '-') ?>

          server: <?= htmlspecialchars($c['xm_server'] ?? '-') ?>
        </td>

        <td style="text-align:center; white-space:pre-line;">
          id: <?= htmlspecialchars($c['ultima_id'] ?? '-') ?>

          pw: <?= htmlspecialchars($c['ultima_pw'] ?? '-') ?>

          server: <?= htmlspecialchars($c['ultima_server'] ?? '-') ?>
        </td>

        <td style="text-align:center; white-space:pre-line;">
          xm: ₩<?= number_format((float)($c['xm_value'] ?? 0), 2) ?>

          ultima: ₩<?= number_format((float)($c['ultima_value'] ?? 0), 2) ?>
        </td>

        <td style="text-align:center;">
          <?php
            $is_rejected = (strtolower($status) === 'rejected' || strtolower($status) === 'rejecting');
            $style = $is_rejected
              ? 'border:1px solid #ff2d6f; background:#ffe6ef; color:#b0003a;'
              : 'border:1px solid #1b5e20; background:#e8f5e9; color:#1b5e20;';
          ?>
          <span style="display:inline-block; padding:4px 8px; border-radius:6px; <?= $style ?> font-weight:600;">
            <?= htmlspecialchars($status) ?>
          </span>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
<?php endif; ?>
