<?php
// This file expects $result (mysqli_result) to be available from c_investor_list.php
?>
<section class="content-area">
  <h2 style="text-align:center;"><?= t('investor_list.title') ?></h2>

  <table style="width:100%; border-collapse:collapse; text-align:center;">
    <thead>
      <tr>
        <th><?= t('common.id') ?></th>
        <th><?= t('common.name') ?></th>
        <th><?= t('common.email') ?></th>
        <th><?= t('common.phone') ?></th>
        <th><?= t('common.country') ?></th>
        <th><?= t('common.role') ?></th>
        <th><?= t('investor_list.actions') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($result) && @mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <?php
            $id = (int)($row['id'] ?? 0);
            $name = $row['username'] ?? $row['name'] ?? $row['user_name'] ?? '';
            $email = $row['email'] ?? '';
            $phone = $row['phone'] ?? '';
            $country = $row['country'] ?? $row['region'] ?? '';
            $role = $row['role'] ?? '';
            $edit_url = "create_account.php?mode=edit&id={$id}&redirect=c_investor_list.php";
          ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$email, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$phone, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$country, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)$role, ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <a href="<?= htmlspecialchars($edit_url, ENT_QUOTES, 'UTF-8') ?>"
                 class="btn btn-sm btn-primary"
                 style="display:inline-block; padding:6px 12px; border-radius:8px; color:#fff; background:#0d6efd; text-decoration:none; font-weight:600;">
                <?= t('btn.edit') ?>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="7"><?= t('investor_list.empty') ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
