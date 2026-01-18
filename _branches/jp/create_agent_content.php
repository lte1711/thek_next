<?php
// 이 파일은 create_agent.php에서 include되며, $conn, $current_role, $step, $auto_country, $auto_referral_code,
// $current_user 사용 가능

if (!function_exists('h')) {
    function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 1 && $step !== 2) $step = 1;

$s1 = $_SESSION['create_agent_step1'] ?? null;
?>
<div class="chart-box" style="max-width:900px; margin:20px auto; padding:24px; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); border-radius:8px;">

  <h2 style="text-align:center; margin:0 0 18px 0;"><?= t('create_agent.title') ?></h2>

  <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
    <div style="padding:8px 14px; border-radius:999px; <?= $step===1 ? 'background:#0b5ed7;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>"><?= t('create_agent.step1') ?></div>
    <div style="padding:8px 14px; border-radius:999px; <?= $step===2 ? 'background:#0b5ed7;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>"><?= t('create_agent.step2') ?></div>
  </div>

  <?php if ($step === 1): ?>
    <form method="post" action="create_agent.php?step=1">
      <input type="hidden" name="_action" value="save_step1">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label><?= t('create_agent.label.username') ?></label>
          <input type="text" name="username" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('create_agent.label.password') ?></label>
          <input type="password" name="password" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('create_agent.label.name') ?></label>
          <input type="text" name="name" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('create_agent.label.email') ?></label>
          <input type="email" name="email" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('create_agent.label.phone') ?></label>
          <input type="text" name="phone" value="" style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('create_agent.label.country_locked') ?></label>
          <input type="text" name="country" value="<?= h($auto_country ?? 'KR') ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div style="grid-column:1 / span 2;">
          <label><?= t('create_agent.label.wallet') ?></label>
          <input type="text" name="wallet_address" value="" style="width:100%; padding:8px;">
          <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
          ?>
        </div>

        <div style="grid-column:1 / span 2;">
          <label><?= t('create_agent.label.codepay') ?></label>
          <input type="text" name="codepay_address" value="" style="width:100%; padding:8px; text-transform:uppercase;">
          <div style="font-size:12px;color:#888;margin-top:4px;"><?= t('create_agent.note.uppercase') ?></div>
        </div>

        <div style="grid-column:1 / span 2;">
          <label><?= t('create_agent.label.referral_code_auto') ?></label>
          <input type="text" name="referral_code" value="<?= h($auto_referral_code ?? '') ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>
      </div>

      <div style="margin-top:18px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary"><?= t('create_agent.btn.next') ?></button>
        <a href="a_agent_list.php" class="btn"><?= t('common.cancel') ?></a>
      </div>
    </form>

  <?php else: ?>
    <?php if (!$s1): ?>
      <div style="padding:14px; background:#fff3cd; border:1px solid #ffeeba; border-radius:6px; color:#856404;">
        <?= t('create_agent.err.no_step1') ?>
      </div>
      <div style="margin-top:14px;">
        <a href="create_agent.php" class="btn btn-primary"><?= t('create_agent.btn.back_step1') ?></a>
      </div>
    <?php else: ?>
      <form method="post" action="create_agent.php?step=2">
        <input type="hidden" name="_action" value="create_user">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
          <div>
            <label><?= t('create_agent.label.role_fixed') ?></label>
            <input type="text" value="agent" readonly style="width:100%; padding:8px; background:#f6f6f6;">
            <div style="font-size:12px;color:#888;margin-top:4px;"><?= t('create_agent.note.role_fixed') ?></div>
          </div>

          <div>
            <label><?= t('create_agent.label.parent_fixed') ?></label>
            <input type="text" value="<?= h(($current_user['name'] ?? '') . ' (' . ($current_user['username'] ?? '') . ') / ID:' . ($current_user['id'] ?? '')) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
            <div style="font-size:12px;color:#888;margin-top:4px;"><?= t('create_agent.note.parent_auto') ?></div>
          </div>
        </div>

        <div style="padding:14px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px;">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div><strong><?= t('create_agent.summary.username') ?></strong><br><?= h($s1['username'] ?? '') ?></div>
            <div><strong><?= t('create_agent.summary.name') ?></strong><br><?= h($s1['name'] ?? '') ?></div>
            <div><strong><?= t('create_agent.summary.email') ?></strong><br><?= h($s1['email'] ?? '') ?></div>
            <div><strong><?= t('create_agent.summary.phone') ?></strong><br><?= h($s1['phone'] ?? '') ?></div>
            <div><strong><?= t('create_agent.summary.country') ?></strong><br><?= h($s1['country'] ?? '') ?></div>
            <div><strong><?= t('create_agent.summary.referral_code') ?></strong><br><?= h($s1['referral_code'] ?? '') ?></div>
            <div style="grid-column:1 / span 2;"><strong><?= t('create_agent.summary.wallet') ?></strong><br><?= h($s1['wallet_address'] ?? '') ?></div>
            <div style="grid-column:1 / span 2;"><strong><?= t('create_agent.summary.codepay') ?></strong><br><?= h($s1['codepay_address'] ?? '') ?></div>
          </div>
          <div style="font-size:12px;color:#888;margin-top:10px;">
            <?= t('create_agent.note.pw_hidden') ?>
          </div>
        </div>

        <div style="margin-top:18px; display:flex; gap:10px;">
          <button type="submit" class="btn btn-primary"><?= t('common.create') ?></button>
          <a href="create_agent.php" class="btn"><?= t('create_agent.btn.back_step1') ?></a>
        </div>
      </form>
    <?php endif; ?>
  <?php endif; ?>

</div>
