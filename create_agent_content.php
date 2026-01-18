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

  <h2 style="text-align:center; margin:0 0 18px 0;">에이전트 생성</h2>

  <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
    <div style="padding:8px 14px; border-radius:999px; <?= $step===1 ? 'background:#0b5ed7;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>">1단계: 기본정보</div>
    <div style="padding:8px 14px; border-radius:999px; <?= $step===2 ? 'background:#0b5ed7;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>">2단계: 확인</div>
  </div>

  <?php if ($step === 1): ?>
    <form method="post" action="create_agent.php?step=1">
      <input type="hidden" name="_action" value="save_step1">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label>아이디(username)</label>
          <input type="text" name="username" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>비밀번호(password)</label>
          <input type="password" name="password" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>이름(name)</label>
          <input type="text" name="name" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>이메일(email)</label>
          <input type="email" name="email" value="" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>전화(phone)</label>
          <input type="text" name="phone" value="" style="width:100%; padding:8px;">
        </div>

        <div>
          <label>국가(country) (자동 입력, 수정 불가)</label>
          <input type="text" name="country" value="<?= h($auto_country ?? 'KR') ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div style="grid-column:1 / span 2;">
          <label>USDT지갑주소(wallet_address)</label>
          <input type="text" name="wallet_address" value="" style="width:100%; padding:8px;">
          <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
          ?>
        </div>

        <div style="grid-column:1 / span 2;">
          <label>코드페이(codepay_address)</label>
          <input type="text" name="codepay_address" value="" style="width:100%; padding:8px; text-transform:uppercase;">
          <div style="font-size:12px;color:#888;margin-top:4px;">대문자로 자동 변환됩니다.</div>
        </div>

        <div style="grid-column:1 / span 2;">
          <label>Referral Code (자동)</label>
          <input type="text" name="referral_code" value="<?= h($auto_referral_code ?? '') ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>
      </div>

      <div style="margin-top:18px; display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary">2단계로 이동</button>
        <a href="a_agent_list.php" class="btn">취소</a>
      </div>
    </form>

  <?php else: ?>
    <?php if (!$s1): ?>
      <div style="padding:14px; background:#fff3cd; border:1px solid #ffeeba; border-radius:6px; color:#856404;">
        ❌ 1단계 정보가 없습니다. 다시 진행해주세요.
      </div>
      <div style="margin-top:14px;">
        <a href="create_agent.php" class="btn btn-primary">1단계로 돌아가기</a>
      </div>
    <?php else: ?>
      <form method="post" action="create_agent.php?step=2">
        <input type="hidden" name="_action" value="create_user">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
          <div>
            <label>생성할 역할(Role)</label>
            <input type="text" value="agent" readonly style="width:100%; padding:8px; background:#f6f6f6;">
            <div style="font-size:12px;color:#888;margin-top:4px;">role은 고정입니다.</div>
          </div>

          <div>
            <label>상위(소속) (고정)</label>
            <input type="text" value="<?= h(($current_user['name'] ?? '') . ' (' . ($current_user['username'] ?? '') . ') / ID:' . ($current_user['id'] ?? '')) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
            <div style="font-size:12px;color:#888;margin-top:4px;">현재 로그인 사용자로 자동 지정됩니다.</div>
          </div>
        </div>

        <div style="padding:14px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:6px;">
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div><strong>아이디</strong><br><?= h($s1['username'] ?? '') ?></div>
            <div><strong>이름</strong><br><?= h($s1['name'] ?? '') ?></div>
            <div><strong>이메일</strong><br><?= h($s1['email'] ?? '') ?></div>
            <div><strong>전화</strong><br><?= h($s1['phone'] ?? '') ?></div>
            <div><strong>국가</strong><br><?= h($s1['country'] ?? '') ?></div>
            <div><strong>Referral Code</strong><br><?= h($s1['referral_code'] ?? '') ?></div>
            <div style="grid-column:1 / span 2;"><strong>USDT지갑주소</strong><br><?= h($s1['wallet_address'] ?? '') ?></div>
            <div style="grid-column:1 / span 2;"><strong>코드페이</strong><br><?= h($s1['codepay_address'] ?? '') ?></div>
          </div>
          <div style="font-size:12px;color:#888;margin-top:10px;">
            ※ 비밀번호는 보안상 표시되지 않습니다.
          </div>
        </div>

        <div style="margin-top:18px; display:flex; gap:10px;">
          <button type="submit" class="btn btn-primary">생성</button>
          <a href="create_agent.php" class="btn">1단계로 돌아가기</a>
        </div>
      </form>
    <?php endif; ?>
  <?php endif; ?>

</div>
