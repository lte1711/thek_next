<!-- ✅ Completed Transactions (C / L) -->
<?php
$region = $_GET['region'] ?? 'korea';
$countryLabel = ($region === 'japan') ? 'Japan' : 'Korea';

// Filter parameters
$from_date = $_GET['from'] ?? '';
$to_date = $_GET['to'] ?? '';
$search_query = $_GET['q'] ?? '';
$is_export_enabled = false; // Completed page does not have export yet
$search_placeholder = 'Username / Pair';
?>
<div class="country-page-header">
  <h1 class="country-page-title"><?= $countryLabel ?> - C / L</h1>
</div>

<?php include __DIR__ . '/includes/country_filterbar.php'; ?>

<?php if (!isset($result_completed) || !$result_completed): ?>
  <div style="padding:10px 0; color:#666;">No completed transactions.</div>
<?php elseif (mysqli_num_rows($result_completed) === 0): ?>
  <div style="padding:10px 0; color:#666;">No completed transactions.</div>
<?php else: ?>
  <div class="country-table-wrap">
  <table class="country-table country-table--completed">
    <tr>
      <th><?= t('table.date','Date') ?></th>
      <th><?= t('table.username','Username') ?></th>
      <th><?= t('table.xm_account','XM Account') ?></th>
      <th><?= t('table.ultima_account','Ultima Account') ?></th>
      <th><?= t('table.deposit','Deposit') ?></th>
      <th><?= t('table.status','Status') ?></th>
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
  </div>
<?php endif; ?>
