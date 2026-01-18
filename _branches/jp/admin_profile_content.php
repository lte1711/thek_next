<section class="content-area">
  <h2><?= t('title.admin_profile', 'Edit My Profile') ?></h2>

  <form method="post" action="admin_profile.php" class="form-box" style="max-width:720px; margin:0 auto;">
    <input type="hidden" name="_action" value="update_self">

    <div class="form-group">
      <label><?= t('label.user_id_readonly', 'User ID (read-only)') ?></label>
      <input type="text" value="<?= htmlspecialchars($prefill['username'] ?? '') ?>" readonly>
    </div>

    <div class="form-group">
      <label><?= t('label.role_readonly', 'Role (read-only)') ?></label>
      <input type="text" value="<?= htmlspecialchars($prefill['role'] ?? '') ?>" readonly>
    </div>

    <div class="form-group">
      <label><?= t('field.name', 'Name') ?></label>
      <input type="text" name="name" value="<?= htmlspecialchars($prefill['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label><?= t('field.email', 'Email') ?></label>
      <input type="email" name="email" value="<?= htmlspecialchars($prefill['email'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label><?= t('field.phone', 'Phone Number') ?></label>
      <input type="text" name="phone" value="<?= htmlspecialchars($prefill['phone'] ?? '') ?>">
    </div>

    <hr style="margin:20px 0;">

    <div class="form-group">
      <label><?= t('label.password_change_optional', 'Change Password (optional)') ?></label>
      <input type="password" name="password" placeholder="<?= htmlspecialchars(t('placeholder.new_password','Enter new password (min 6 chars)')) ?>">
    </div>

    <div class="form-group">
      <label><?= t('label.password_confirm_optional', 'Confirm Password (optional)') ?></label>
      <input type="password" name="password_confirm" placeholder="<?= htmlspecialchars(t('placeholder.confirm_password','Re-enter password')) ?>">
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
      <a class="btn" href="admin_dashboard.php"><?= t('common.cancel','Cancel') ?></a>
      <button class="btn" type="submit"><?= t('common.save','Save') ?></button>
    </div>
  </form>
</section>
