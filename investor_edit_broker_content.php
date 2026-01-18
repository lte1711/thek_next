<?php
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$is_self = isset($is_self) ? (bool)$is_self : false;
$step = isset($step) ? (int)$step : 1;
$redirect = isset($_GET['redirect']) ? basename((string)$_GET['redirect']) : '';
$base_qs = "id=" . urlencode((string)($prefill['id'] ?? ''));
if ($redirect !== '') $base_qs .= "&redirect=" . urlencode($redirect);
?>
<h2 class="section-title" style="text-align:center; margin-top:20px;">투자자 정보 수정 (브로커 포함)</h2>

<div class="chart-box" style="max-width:900px; margin:20px auto; padding:20px; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); border-radius:8px;">
  <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
    <div style="padding:8px 14px; border-radius:999px; <?= $step===1 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>">1단계: 기본정보</div>
    <div style="padding:8px 14px; border-radius:999px; <?= $step===2 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>">2단계: 플랫폼/브로커</div>
  </div>

  <?php if ($step === 1): ?>
    <form method="POST" action="investor_edit_broker.php?step=1&<?= h($base_qs) ?>">
      <input type="hidden" name="_action" value="save_step1">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label>아이디(로그인)</label>
          <input type="text" value="<?= h($prefill['username']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div>
          <label>비밀번호 (본인만 변경 가능 / 변경 시에만 입력)</label>
          <?php if (!$is_self): ?>
            <input type="password" value="" disabled style="width:100%; padding:8px; background:#f6f6f6;">
            <div style="font-size:12px;color:#888;margin-top:4px;">※ 비밀번호 변경은 회원 본인이 본인 수정할 때만 가능합니다.</div>
          <?php else: ?>
            <input type="password" name="password" value="" style="width:100%; padding:8px;" autocomplete="new-password">
            <div style="font-size:12px;color:#888;margin-top:4px;">※ 입력하면 변경되고, 비워두면 유지됩니다.</div>
          <?php endif; ?>
        </div>

        <div>
          <label>이름</label>
          <input type="text" value="<?= h($prefill['name']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div>
          <label>이메일</label>
          <input type="email" name="email" value="<?= h($prefill['email']) ?>" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>전화번호</label>
          <input type="text" name="phone" value="<?= h($prefill['phone']) ?>" style="width:100%; padding:8px;">
        </div>

        <div>
          <label>국적</label>
          <input type="text" value="<?= h($prefill['country']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div style="grid-column:1 / -1;">
          <label>USDT지갑주소</label>
          <input type="text" name="wallet_address" value="<?= h($prefill['wallet_address']) ?>" required style="width:100%; padding:8px;">
          <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
          ?>
        </div>

        <div style="grid-column:1 / -1;">
          <label>코드페이 간편주소</label>
          <input type="text" name="codepay_address" value="<?= h($prefill['codepay_address']) ?>" style="width:100%; padding:8px;" oninput="this.value=this.value.toUpperCase();">
        </div>
      </div>

      <div style="text-align:center; margin-top:22px;">
        <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
          2단계로 이동
        </button>
      </div>
    </form>

  <?php else: ?>
    <?php
      $selected = array_filter(array_map('trim', explode(',', (string)($prefill['selected_broker'] ?? ''))));
      $selected = array_values(array_unique($selected));
      $is_checked = function($v) use ($selected){ return in_array($v, $selected, true); };
    ?>

    <form method="POST" action="investor_edit_broker.php?step=2&<?= h($base_qs) ?>">
      <input type="hidden" name="_action" value="update_user">

      <div style="padding:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; margin-bottom:18px;">
        <div style="font-weight:600; margin-bottom:8px;">기본정보(요약)</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:14px;">
          <div><b>아이디:</b> <?= h($prefill['username']) ?></div>
          <div><b>이름:</b> <?= h($prefill['name']) ?></div>
          <div><b>이메일:</b> <?= h($prefill['email']) ?></div>
          <div><b>전화:</b> <?= h($prefill['phone']) ?></div>
          <div style="grid-column:1/-1;"><b>Wallet:</b> <?= h($prefill['wallet_address']) ?></div>
          <div style="grid-column:1/-1;"><b>CodePay:</b> <?= h($prefill['codepay_address']) ?></div>
        </div>
      </div>

      <div style="margin-top:6px; padding-top:10px; border-top:1px dashed #ddd;">
        <div style="font-weight:600; margin-bottom:8px;">선택 브로커</div>
        <div style="display:flex; gap:14px; flex-wrap:wrap;">
          <label><input type="checkbox" name="selected_broker[]" value="xm" <?= $is_checked('xm')?'checked':'' ?>> XM</label>
          <label><input type="checkbox" name="selected_broker[]" value="ultima" <?= $is_checked('ultima')?'checked':'' ?>> ULTIMA</label>
          <label><input type="checkbox" name="selected_broker[]" value="broker1" <?= $is_checked('broker1')?'checked':'' ?>> BROKER1</label>
          <label><input type="checkbox" name="selected_broker[]" value="broker2" <?= $is_checked('broker2')?'checked':'' ?>> BROKER2</label>
        </div>
      </div>

      <div style="margin-top:16px; padding:12px; border:1px solid #e9ecef; border-radius:8px; background:#fbfbfb;">
        <div style="font-weight:600; margin-bottom:10px;">XM 계정</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div><label>XM ID</label><input type="text" name="xm_id" value="<?= h($prefill['xm_id']) ?>" style="width:100%; padding:8px;"></div>
          <div><label>XM PW</label><input type="text" name="xm_pw" value="<?= h($prefill['xm_pw']) ?>" style="width:100%; padding:8px;"></div>
          <div style="grid-column:1/-1;"><label>XM SERVER</label><input type="text" name="xm_server" value="<?= h($prefill['xm_server']) ?>" style="width:100%; padding:8px;"></div>
        </div>
      </div>

      <div style="margin-top:16px; padding:12px; border:1px solid #e9ecef; border-radius:8px; background:#fbfbfb;">
        <div style="font-weight:600; margin-bottom:10px;">ULTIMA 계정</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div><label>ULTIMA ID</label><input type="text" name="ultima_id" value="<?= h($prefill['ultima_id']) ?>" style="width:100%; padding:8px;"></div>
          <div><label>ULTIMA PW</label><input type="text" name="ultima_pw" value="<?= h($prefill['ultima_pw']) ?>" style="width:100%; padding:8px;"></div>
          <div style="grid-column:1/-1;"><label>ULTIMA SERVER</label><input type="text" name="ultima_server" value="<?= h($prefill['ultima_server']) ?>" style="width:100%; padding:8px;"></div>
        </div>
      </div>

      <div style="margin-top:16px; padding:12px; border:1px solid #e9ecef; border-radius:8px; background:#fbfbfb;">
        <div style="font-weight:600; margin-bottom:10px;">BROKER 1</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div><label>BROKER1 ID</label><input type="text" name="broker1_id" value="<?= h($prefill['broker1_id']) ?>" style="width:100%; padding:8px;"></div>
          <div><label>BROKER1 PW</label><input type="text" name="broker1_pw" value="<?= h($prefill['broker1_pw']) ?>" style="width:100%; padding:8px;"></div>
          <div style="grid-column:1/-1;"><label>BROKER1 SERVER</label><input type="text" name="broker1_server" value="<?= h($prefill['broker1_server']) ?>" style="width:100%; padding:8px;"></div>
        </div>
      </div>

      <div style="margin-top:16px; padding:12px; border:1px solid #e9ecef; border-radius:8px; background:#fbfbfb;">
        <div style="font-weight:600; margin-bottom:10px;">BROKER 2</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div><label>BROKER2 ID</label><input type="text" name="broker2_id" value="<?= h($prefill['broker2_id']) ?>" style="width:100%; padding:8px;"></div>
          <div><label>BROKER2 PW</label><input type="text" name="broker2_pw" value="<?= h($prefill['broker2_pw']) ?>" style="width:100%; padding:8px;"></div>
          <div style="grid-column:1/-1;"><label>BROKER2 SERVER</label><input type="text" name="broker2_server" value="<?= h($prefill['broker2_server']) ?>" style="width:100%; padding:8px;"></div>
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:center; margin-top:22px;">
        <a href="investor_edit_broker.php?step=1&<?= h($base_qs) ?>" class="btn confirm" style="padding:10px 18px; background:#6c757d; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none;">1단계로</a>
        <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
          수정 완료
        </button>
      </div>
    </form>
  <?php endif; ?>
</div>
