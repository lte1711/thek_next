<?php
// 이 파일은 create_master.php에서 include되며, $conn, $current_role, $step, $auto_country, $auto_referral_code,
// $current_user 사용 가능

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 1 && $step !== 2) $step = 1;

$s1 = $_SESSION['create_master_step1'] ?? null;
?>
<div class="page-wrap">
  <div class="page-title">
    <h2><?= t('create_master.title') ?></h2>
  </div>

  <?php if ($step === 1): ?>
    <form method="post" action="create_master.php?step=1">
      <input type="hidden" name="_action" value="save_step1">

      <div class="card" style="padding:16px; margin-bottom:14px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div>
            <label><?= t('create_master.role_label') ?></label>
            <input type="text" value="master" readonly style="width:100%; padding:8px; background:#f3f3f3;">
            <div style="font-size:12px; color:#666; margin-top:6px;"><?= t('create_master.role_help') ?></div>
          </div>

          <div>
            <label><?= t('create_master.parent_label') ?></label>
            <input type="text"
                   value="<?= h(($current_user['name'] ?? '') . ' (' . ($current_user['username'] ?? '') . ') / ID:' . ($current_user['id'] ?? '')) ?>"
                   readonly
                   style="width:100%; padding:8px; background:#f3f3f3;">
            <input type="hidden" name="sponsor_id" value="<?= h($current_user['id'] ?? '') ?>">
            <div style="font-size:12px; color:#666; margin-top:6px;"><?= t('create_master.parent_help') ?></div>
          </div>
        </div>
      </div>

      <div class="card" style="padding:16px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div>
            <label><?= t('create_master.username_label') ?></label>
            <input type="text" name="username" required style="width:100%; padding:8px;">
          </div>
          <div>
            <label><?= t('create_master.password_label') ?></label>
            <input type="password" name="password" required style="width:100%; padding:8px;">
          </div>

          <div>
            <label><?= t('create_master.name_label') ?></label>
            <input type="text" name="name" required style="width:100%; padding:8px;">
          </div>
          <div>
            <label><?= t('create_master.email_label') ?></label>
            <input type="email" name="email" required style="width:100%; padding:8px;">
          </div>

          <div>
            <label><?= t('create_master.phone_label') ?></label>
            <input type="text" name="phone" style="width:100%; padding:8px;">
          </div>
          <div>
            <label><?= t('create_master.country_label') ?></label>
            <input type="text" value="<?= h($auto_country ?? 'KR') ?>" readonly style="width:100%; padding:8px; background:#f3f3f3;">
          </div>

          <div>
            <label><?= t('create_master.wallet_label') ?></label>
            <input type="text" name="wallet_address" required style="width:100%; padding:8px;">
            <?php
              $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
              if (is_file($__wallet_warning)) include $__wallet_warning;
            ?>
          </div>
          <div>
            <label><?= t('create_master.codepay_label') ?></label>
            <input type="text" name="codepay_address" style="width:100%; padding:8px;"
                   oninput="this.value=this.value.toUpperCase();">
            <div style="font-size:12px; color:#666; margin-top:6px;"><?= t('create_master.codepay_help') ?></div>
          </div>

          <div>
            <label>Referral Code (자동)</label>
            <input type="text" name="referral_code" value="<?= h($auto_referral_code ?? '') ?>" readonly
                   style="width:100%; padding:8px; background:#f3f3f3;">
          </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px;">
          <button type="submit" class="btn btn-primary"><?= t('create_master.next_step') ?></button>
          <a href="a_master_list.php" class="btn"><?= t('common.cancel') ?></a>
        </div>
      </div>
    </form>

  <?php else: ?>
    <?php if (!$s1): ?>
      <div class="card" style="padding:16px;">
        <div><?= t('create_master.no_step1') ?></div>
        <div style="margin-top:12px;"><a class="btn btn-primary" href="create_master.php"><?= t('create_master.go_step1') ?></a></div>
      </div>
    <?php else: ?>
      <div class="card" style="padding:16px; margin-bottom:14px;">
        <h3 style="margin:0 0 10px 0;"><?= t('create_master.step2_title') ?></h3>
        <div style="font-size:14px; color:#444; line-height:1.7;">
          <div><strong><?= t('create_master.role_display') ?></strong> master (<?= t('common.fixed') ?>)</div>
          <div><strong><?= t('create_master.parent_display') ?></strong> <?= h(($current_user['name'] ?? '') . ' (' . ($current_user['username'] ?? '') . ') / ID:' . ($current_user['id'] ?? '')) ?></div>
          <div><strong><?= t('create_master.username_display') ?></strong> <?= h($s1['username']) ?></div>
          <div><strong><?= t('create_master.name_display') ?></strong> <?= h($s1['name']) ?></div>
          <div><strong><?= t('create_master.email_display') ?></strong> <?= h($s1['email']) ?></div>
          <div><strong><?= t('create_master.phone_display') ?></strong> <?= h($s1['phone']) ?></div>
          <div><strong><?= t('create_master.wallet_display') ?></strong> <?= h($s1['wallet_address']) ?></div>
          <div><strong><?= t('create_master.codepay_display') ?></strong> <?= h($s1['codepay_address']) ?></div>
          <div><strong>Referral Code:</strong> <?= h($s1['referral_code']) ?></div>
        </div>
        <div style="font-size:12px; color:#666; margin-top:10px;"><?= t('create_master.platform_notice') ?></div>
      </div>

      <form method="post" action="create_master.php?step=2">
        <input type="hidden" name="_action" value="create_user">
        <button type="submit" class="btn btn-primary"><?= t('create_master.confirm_create') ?></button>
        <a href="create_master.php" class="btn"><?= t('common.prev') ?></a>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
