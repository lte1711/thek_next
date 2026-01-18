<h2 style="text-align:center;"><?= t('title.create_investor_account', 'Create Investor Account') ?></h2>
<form method="POST" style="max-width:600px; margin:auto;">
    <div class="form-group">
        <label><?= t('field.id', 'ID') ?></label>
        <input type="text" name="user_id" required>
        <span class="duplicate-check" onclick="alert(<?= json_encode(t('msg.duplicate_check_auto')) ?>)"><?= t('common.duplicate_check','Duplicate Check') ?></span>
    </div>
    <div class="form-group">
        <label><?= t('field.password','Password') ?></label>
        <input type="password" name="user_pw" required>
    </div>
    <div class="form-group">
        <label><?= t('field.name','Name') ?></label>
        <input type="text" name="user_name" required>
    </div>
    <div class="form-group">
        <label><?= t('field.country','Country') ?></label>
        <input type="text" name="country" value="<?= htmlspecialchars($auto_country ?? '') ?>" readonly>
    </div>
    <div class="form-group">
        <label><?= t('field.email','Email') ?></label>
        <input type="email" name="user_email" required>
    </div>
    <div class="form-group">
        <label><?= t('field.phone','Phone Number') ?></label>
        <input type="text" name="phone">
    </div>
    <div class="form-group">
        <label><?= t('field.usdt_wallet','USDT Wallet Address') ?></label>
        <input type="text" name="wallet_address" required>
        <?php
require_once __DIR__ . '/includes/i18n.php';

            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
        ?>
    </div>
    <div class="form-group">
        <label><?= t('table.role','Role') ?></label>
        <input type="text" name="user_role" value="investor" readonly>
    </div>
    <div class="form-group">
        <label><?= t('field.profit_share_rate','Profit Share Rate') ?></label>
        <input type="text" name="profit_share" value="5" readonly>
    </div>
    <!-- ✅ 레퍼럴 코드 입력란 추가 -->
    <div class="form-group">
        <label><?= t('field.referral','Referral Code') ?></label>
        <input type="text" name="referral_code" placeholder="<?= htmlspecialchars(t('placeholder.referral_code','Enter referral code')) ?>">
    </div>
    <div style="text-align:center; margin-top:20px;">
        <button type="submit" class="btn edit">
            <?= t('common.create','Create') ?>
        </button>
    </div>
</form>