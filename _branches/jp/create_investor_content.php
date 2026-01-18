<?php
// 이 파일은 create_investor.php에서 include되며, $conn, $current_role, $step, $auto_country, $auto_referral_code 사용 가능

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$allowed_roles = allowed_child_roles($current_role);
$allowed_roles_labels = [
  'gm' => t('role.gm', '글로벌 마스터') . '(GM)',
  'admin' => t('role.admin', '관리자') . '(Admin)',
  'master' => t('role.master', '마스터') . '(Master)',
  'agent' => t('role.agent', '에이전트') . '(Agent)',
  'investor' => t('role.investor', '투자자') . '(Investor)',
];

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step !== 1 && $step !== 2) $step = 1;

$s1 = $_SESSION['create_investor_step1'] ?? null;

$country_val  = $s1['country'] ?? ($auto_country ?? 'KR');
$referral_val = $s1['referral_code'] ?? ($auto_referral_code ?? '');

function fetch_users_by_role(mysqli $conn, string $role): array {
    $sql = "SELECT id, name, username, phone FROM users WHERE role = ? ORDER BY id DESC";
    $st = $conn->prepare($sql);
    $st->bind_param("s", $role);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}

$pool = [];
foreach (['gm','admin','master','agent'] as $r) {
    $pool[$r] = fetch_users_by_role($conn, $r);
}
?>

<h2 class="section-title" style="text-align:center; margin-top:20px;"><?= t('title.create_account_2step','계정 생성 (2단계)') ?></h2>

<div class="chart-box" style="max-width:900px; margin:20px auto; padding:20px; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); border-radius:8px;">

  <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
    <div style="padding:8px 14px; border-radius:999px; <?= $step===1 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>"><?= t('create_investor.step1_basic','1단계: 기본정보') ?></div>
    <div style="padding:8px 14px; border-radius:999px; <?= $step===2 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>"><?= t('create_investor.step2_role','2단계: 역할/소속/추천') ?></div>
  </div>

  <?php if ($step === 1): ?>
    <form method="POST" action="create_investor.php?step=1">
      <input type="hidden" name="_action" value="save_step1">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label><?= t('common.username_login','아이디(로그인)') ?></label>
          <input type="text" name="username" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('common.password','비밀번호') ?></label>
          <input type="password" name="password" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('common.name','이름') ?></label>
          <input type="text" name="name" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('common.email','이메일') ?></label>
          <input type="email" name="email" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('common.phone','전화번호') ?></label>
          <input type="text" name="phone" style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= t('common.country_auto','국적 (자동 입력, 수정 불가)') ?></label>
          <input type="text" name="country" value="<?= h($country_val) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div style="grid-column:1 / -1;">
          <label><?= t('common.usdt_wallet','USDT지갑주소') ?></label>
          <input type="text" name="wallet_address" required style="width:100%; padding:8px;">
          <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
          ?>
        </div>

        <div style="grid-column:1 / -1;">
          <label><?= t('common.codepay_address','코드페이 간편주소') ?></label>
          <input type="text" name="codepay_address" style="width:100%; padding:8px;" oninput="this.value=this.value.toUpperCase();">
        </div>

        <div style="grid-column:1 / -1;">
          <label><?= t('field.referral_code', 'Referral Code') ?> (<?= t('common.auto_generated_readonly', 'auto generated, read-only') ?>)</label>
          <input type="text" name="referral_code" value="<?= h($referral_val) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>
      </div>

      <div style="text-align:center; margin-top:22px;">
        <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
          <?= t('common.next_step','2단계로 이동') ?>
        </button>
      </div>
    </form>

  <?php else: ?>
    <?php if (!$s1): ?>
      <p style="color:red; text-align:center;"><?= t('err.step1_missing','1단계 정보가 없습니다. 다시 진행해주세요.') ?></p>
      <div style="text-align:center; margin-top:15px;">
        <a href="create_investor.php?step=1" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none;">
          <?= t('common.back_to_step1','1단계로 돌아가기') ?>
        </a>
      </div>
    <?php else: ?>
      <div style="padding:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; margin-bottom:18px;">
        <div style="font-weight:600; margin-bottom:8px;"><?= t('create_investor.step1_summary','1단계 입력 요약') ?></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:14px;">
          <div><b><?= t('common.username_col','아이디:') ?></b> <?= h($s1['username']) ?></div>
          <div><b><?= t('common.name','이름') ?>:</b> <?= h($s1['name']) ?></div>
          <div><b><?= t('common.email','이메일') ?>:</b> <?= h($s1['email']) ?></div>
          <div><b><?= t('common.phone_col','전화:') ?></b> <?= h($s1['phone']) ?></div>
          <div><b><?= t('common.country_col','국적:') ?></b> <?= h($s1['country']) ?></div>
          <div><b>Referral:</b> <?= h($s1['referral_code']) ?></div>
          <div style="grid-column:1/-1;"><b>Wallet:</b> <?= h($s1['wallet_address']) ?></div>
          <div style="grid-column:1/-1;"><b>CodePay:</b> <?= h($s1['codepay_address']) ?></div>
        </div>
      </div>

      <form method="POST" action="create_investor.php?step=2">
        <input type="hidden" name="_action" value="create_user">


        <!-- STEP 2: XM / ULTIMA 계정 정보 -->
        <div style="margin-top:10px; padding:12px; border:1px solid #e9ecef; border-radius:8px; background:#fbfbfb;">
          <div style="font-weight:600; margin-bottom:10px;"><?= t('create_investor.platform_accounts','플랫폼 계정 정보 (XM / ULTIMA)') ?></div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
            <div>
              <label>XM ID</label>
              <input type="text" name="xm_id" required style="width:100%; padding:8px;">
            </div>
            <div>
              <label>XM PW</label>
              <input type="text" name="xm_pw" required style="width:100%; padding:8px;">
            </div>
            <div style="grid-column:1/-1;">
              <label>XM SERVER</label>
              <input type="text" name="xm_server" required style="width:100%; padding:8px;">
            </div>

            <div>
              <label>ULTIMA ID</label>
              <input type="text" name="ultima_id" required style="width:100%; padding:8px;">
            </div>
            <div>
              <label>ULTIMA PW</label>
              <input type="text" name="ultima_pw" required style="width:100%; padding:8px;">
            </div>
            <div style="grid-column:1/-1;">
              <label>ULTIMA SERVER</label>
              <input type="text" name="ultima_server" required style="width:100%; padding:8px;">
            </div>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:end;">
          <div>
            <label><?= t('create_investor.role_to_create','생성할 역할(Role)') ?></label>
            <select name="role" id="roleSelect" required style="width:100%; padding:8px;">
              <option value=""><?= t('common.select', '선택하세요') ?></option>
              <?php foreach ($allowed_roles as $r): ?>
                <option value="<?= h($r) ?>"><?= h($allowed_roles_labels[$r] ?? $r) ?></option>
              <?php endforeach; ?>
            </select>
            <div style="font-size:12px; color:#666; margin-top:6px;"><?= t('create_investor.allowed_roles_prefix','현재 권한(') ?><?= h($current_role) ?>) 기준으로 허용된 역할만 표시됩니다.</div>
          </div>

          <div>
            <label><?= t('create_investor.select_sponsor','상위(소속) 선택') ?></label>
            <select name="sponsor_id" id="sponsorSelect" style="width:100%; padding:8px;" disabled>
              <option value=""><?= t('create_investor.select_role_first','역할을 먼저 선택하세요') ?></option>
            </select>
            <div id="sponsorHint" style="font-size:12px; color:#666; margin-top:6px;"></div>
          </div>

          <div id="referrerBox" style="grid-column:1/-1; display:none;">
            <div style="padding:12px; border:1px dashed #ced4da; border-radius:8px;">
              <div style="font-weight:600; margin-bottom:10px;"><?= t('create_investor.referrer_section','추천인 선택 (투자자 전용)') ?></div>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                  <label><?= t('label.referrer_name_exact', 'Referrer Name (exact)') ?></label>
                  <input type="text" name="referrer_name" style="width:100%; padding:8px;">
                </div>
                <div>
                  <label><?= t('label.referrer_phone_when_duplicate', 'Referrer Phone (if same name)') ?></label>
                  <input type="text" name="referrer_phone" style="width:100%; padding:8px;">
                </div>
              </div>
              <div style="font-size:12px; color:#666; margin-top:8px;">
                * 동명이인이 있으면 <?= t('common.phone','전화번호') ?>로 2차 확인합니다.
              </div>
            </div>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; gap:10px; margin-top:22px;">
          <a href="create_investor.php?step=1" class="btn" style="padding:10px 22px; background:#6c757d; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none;">
            <?= t('common.prev_step','1단계로') ?>
          </a>

          <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
            <?= t('create_investor.confirm_create','계정 생성 확정') ?>
          </button>
        </div>
      </form>

      <script>
      
  const MSG_SELECT_ROLE_FIRST = <?= json_encode(t('create_investor.select_role_first','역할을 먼저 선택하세요')) ?>;
(function(){
                const sponsorSelect = document.getElementById('sponsorSelect');
        const sponsorHint = document.getElementById('sponsorHint');
        const referrerBox = document.getElementById('referrerBox');

        const pools = <?= json_encode($pool, JSON_UNESCAPED_UNICODE) ?>;
        const labels = <?= json_encode($allowed_roles_labels, JSON_UNESCAPED_UNICODE) ?>;

        const TR = {
          select_role: <?= json_encode(t('hint.selected_role','Selected role')) ?>,
          parent_role_is: <?= json_encode(t('hint.parent_role_is','parent role is')) ?>,
          is_suffix: <?= json_encode(t('hint.parent_role_suffix','')) ?>
        };

        function requiredSponsorRole(role){
          role = (role||'').toLowerCase();
          if(role==='admin') return 'gm';
          if(role==='master') return 'admin';
          if(role==='agent') return 'master';
          if(role==='investor') return 'agent';
          return null;
        }

        function labelRole(r){ return labels[r] || r; }

        function setSponsorOptions(role){
          const req = requiredSponsorRole(role);
          sponsorSelect.innerHTML = '';

          if(!role){
            sponsorSelect.disabled = true;
            sponsorSelect.innerHTML = '<option value="">'+<?= json_encode(t('msg.select_role_first','Please select a role first.')) ?>+'</option>';
            sponsorHint.textContent = '';
            referrerBox.style.display = 'none';
            return;
          }

          referrerBox.style.display = (role === 'investor') ? 'block' : 'none';

          if(req === null){
            sponsorSelect.disabled = true;
            sponsorSelect.innerHTML = '<option value=""><?= t('common.no_parent', '상위 선택 없음') ?></option>';
            sponsorHint.textContent = <?= json_encode(t('hint.no_parent_needed', '이 역할은 상위(소속) 선택이 필요 없습니다.')) ?>;
            return;
          }

          sponsorSelect.disabled = false;
          const list = pools[req] || [];
          sponsorSelect.appendChild(new Option('<?= t('common.select', '선택하세요') ?>', ''));

          list.forEach(u => {
            const txt = `${u.id} | ${u.name||''} (${u.username}) ${u.phone?'- '+u.phone:''}`;
            sponsorSelect.appendChild(new Option(txt, u.id));
          });

          sponsorHint.textContent = `${TR.select_role}(${labelRole(role)}) ${TR.parent_role_is} ${labelRole(req)} ${TR.is_suffix}`;
        }

        setSponsorOptions('investor');
      })();
      </script>
    <?php endif; ?>
  <?php endif; ?>

</div>
