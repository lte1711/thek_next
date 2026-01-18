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
    <h2>마스터 생성</h2>
  </div>

  <?php if ($step === 1): ?>
    <form method="post" action="create_master.php?step=1">
      <input type="hidden" name="_action" value="save_step1">

      <div class="card" style="padding:16px; margin-bottom:14px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div>
            <label>생성할 역할(Role)</label>
            <input type="text" value="master" readonly style="width:100%; padding:8px; background:#f3f3f3;">
            <div style="font-size:12px; color:#666; margin-top:6px;">role은 고정입니다.</div>
          </div>

          <div>
            <label>상위(소속) (고정)</label>
            <input type="text"
                   value="<?= h(($current_user['name'] ?? '') . ' (' . ($current_user['username'] ?? '') . ') / ID:' . ($current_user['id'] ?? '')) ?>"
                   readonly
                   style="width:100%; padding:8px; background:#f3f3f3;">
            <input type="hidden" name="sponsor_id" value="<?= h($current_user['id'] ?? '') ?>">
            <div style="font-size:12px; color:#666; margin-top:6px;">현재 로그인 사용자로 자동 지정됩니다.</div>
          </div>
        </div>
      </div>

      <div class="card" style="padding:16px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
          <div>
            <label>아이디(username)</label>
            <input type="text" name="username" required style="width:100%; padding:8px;">
          </div>
          <div>
            <label>비밀번호(password)</label>
            <input type="password" name="password" required style="width:100%; padding:8px;">
          </div>

          <div>
            <label>이름(name)</label>
            <input type="text" name="name" required style="width:100%; padding:8px;">
          </div>
          <div>
            <label>이메일(email)</label>
            <input type="email" name="email" required style="width:100%; padding:8px;">
          </div>

          <div>
            <label>전화(phone)</label>
            <input type="text" name="phone" style="width:100%; padding:8px;">
          </div>
          <div>
            <label>국가(country)</label>
            <input type="text" value="<?= h($auto_country ?? 'KR') ?>" readonly style="width:100%; padding:8px; background:#f3f3f3;">
          </div>

          <div>
            <label>USDT지갑주소(wallet_address)</label>
            <input type="text" name="wallet_address" required style="width:100%; padding:8px;">
            <?php
              $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
              if (is_file($__wallet_warning)) include $__wallet_warning;
            ?>
          </div>
          <div>
            <label>코트페이(codepay_address)</label>
            <input type="text" name="codepay_address" style="width:100%; padding:8px;"
                   oninput="this.value=this.value.toUpperCase();">
            <div style="font-size:12px; color:#666; margin-top:6px;">대문자로 자동 변환됩니다.</div>
          </div>

          <div>
            <label>Referral Code (자동)</label>
            <input type="text" name="referral_code" value="<?= h($auto_referral_code ?? '') ?>" readonly
                   style="width:100%; padding:8px; background:#f3f3f3;">
          </div>
        </div>

        <div style="margin-top:14px; display:flex; gap:10px;">
          <button type="submit" class="btn btn-primary">2단계로 이동</button>
          <a href="a_master_list.php" class="btn">취소</a>
        </div>
      </div>
    </form>

  <?php else: ?>
    <?php if (!$s1): ?>
      <div class="card" style="padding:16px;">
        <div>❌ 1단계 정보가 없습니다. 다시 진행해주세요.</div>
        <div style="margin-top:12px;"><a class="btn btn-primary" href="create_master.php">1단계로 이동</a></div>
      </div>
    <?php else: ?>
      <div class="card" style="padding:16px; margin-bottom:14px;">
        <h3 style="margin:0 0 10px 0;">2단계: 생성 정보 확인</h3>
        <div style="font-size:14px; color:#444; line-height:1.7;">
          <div><strong>Role:</strong> master (고정)</div>
          <div><strong>상위(소속):</strong> <?= h(($current_user['name'] ?? '') . ' (' . ($current_user['username'] ?? '') . ') / ID:' . ($current_user['id'] ?? '')) ?></div>
          <div><strong>아이디:</strong> <?= h($s1['username']) ?></div>
          <div><strong>이름:</strong> <?= h($s1['name']) ?></div>
          <div><strong>이메일:</strong> <?= h($s1['email']) ?></div>
          <div><strong>전화:</strong> <?= h($s1['phone']) ?></div>
          <div><strong>USDT지갑주소:</strong> <?= h($s1['wallet_address']) ?></div>
          <div><strong>코트페이:</strong> <?= h($s1['codepay_address']) ?></div>
          <div><strong>Referral Code:</strong> <?= h($s1['referral_code']) ?></div>
        </div>
        <div style="font-size:12px; color:#666; margin-top:10px;">※ 플랫폼 계정 정보(XM/ULTIMA)는 입력/저장하지 않습니다.</div>
      </div>

      <form method="post" action="create_master.php?step=2">
        <input type="hidden" name="_action" value="create_user">
        <button type="submit" class="btn btn-primary">생성 확정</button>
        <a href="create_master.php" class="btn">이전</a>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
