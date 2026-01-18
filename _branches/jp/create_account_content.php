<?php
// Unified create/edit content for create_account.php
// Expected vars: $conn, $current_role, $mode, $step, $prefill, $pool, $allowed_roles, $allowed_roles_labels, $redirect

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$is_edit = ($mode === 'edit');
$is_gm   = (strtolower((string)$current_role) === 'gm');
$target_role = strtolower((string)($prefill['role'] ?? ''));
$is_investor_edit = ($is_edit && $target_role === 'investor');

$current_user_id = isset($current_user_id) ? (int)$current_user_id : (int)($_SESSION['user_id'] ?? 0);
$target_id = isset($prefill['id']) ? (int)$prefill['id'] : 0;
$is_self_edit = ($is_edit && $target_id > 0 && $current_user_id > 0 && $target_id === $current_user_id);

$step = isset($_GET['step']) ? (int)$_GET['step'] : (int)$step;
if ($step !== 1 && $step !== 2) $step = 1;

function build_qs(array $params): string {
    $clean = [];
    foreach ($params as $k=>$v) {
        if ($v === null || $v === '') continue;
        $clean[] = urlencode((string)$k) . '=' . urlencode((string)$v);
    }
    return implode('&', $clean);
}

$redirect = isset($redirect) ? (string)$redirect : (string)($_GET['redirect'] ?? '');
$redirect = $redirect !== '' ? basename($redirect) : '';

$base_qs = build_qs([
  'mode' => $is_edit ? 'edit' : null,
  'id' => $is_edit ? ($prefill['id'] ?? '') : null,
  'redirect' => $redirect ?: null,
]);

$title = $is_edit ? t('account.step2.edit_title','Edit Member (Step 2)') : t('account.step2.create_title','Create Member (Step 2)');
?>

<h2 class="section-title" style="text-align:center; margin-top:20px;"><?= h($title) ?></h2>

<div class="chart-box" style="max-width:900px; margin:20px auto; padding:20px; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); border-radius:8px;">

  <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
    <div style="padding:8px 14px; border-radius:999px; <?= $step===1 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>"><?= h(t('account.step1.badge','Step 1: Basic Info')) ?></div>
    <div style="padding:8px 14px; border-radius:999px; <?= $step===2 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>"><?= h(t('account.step2.badge','Step 2: Role/Team/Referrer + XM/ULTIMA')) ?></div>
  </div>

  <?php if ($step === 1): ?>
    <form method="POST" action="create_account.php?<?= $base_qs ? h($base_qs.'&') : '' ?>step=1">
      <input type="hidden" name="_action" value="save_step1">
      <input type="hidden" name="_mode" value="<?= $is_edit ? 'edit' : 'create' ?>">
      <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?= h($prefill['id']) ?>">
      <?php endif; ?>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label><?= h(t('field.username_login','Username (Login)')) ?></label>
          <input type="text" name="username" value="<?= h($prefill['username']) ?>" <?= $is_edit && !$is_gm ? 'readonly style="width:100%; padding:8px; background:#f6f6f6;"' : 'required style="width:100%; padding:8px;"' ?>>
          <?php if ($is_edit && ($is_self_edit || strtolower((string)($prefill['role'] ?? '')) === 'gm')): ?>
            <div style="font-size:12px;color:#888;margin-top:4px;"><?= h(t('note.gm_only_change_id_name','* Only GM can change username/name.')) ?></div>
          <?php endif; ?>
        </div>

        <div>
          <label><?= h($is_edit ? t('field.password_edit_only','Password (change only when editing self)') : t('field.password','Password')) ?></label>
          <?php if ($is_edit && !$is_self_edit): ?>
            <input type="password" name="password" value="" disabled style="width:100%; padding:8px; background:#f6f6f6;">
            <div style="font-size:12px;color:#888;margin-top:4px;"><?= h(t('note.password_self_only','* Password can be changed only by the account owner (self-edit).')) ?></div>
          <?php else: ?>
            <input type="password" name="password" <?= $is_edit ? '' : 'required' ?> style="width:100%; padding:8px;" autocomplete="new-password">
            <?php if ($is_edit): ?>
              <div style="font-size:12px;color:#888;margin-top:4px;"><?= h(t('note.password_edit_hint','* If entered, password will be updated. Leave blank to keep unchanged.')) ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <div>
          <label><?= h(t('field.name','Name')) ?></label>
          <input type="text" name="name" value="<?= h($prefill['name']) ?>" <?= $is_edit && !$is_gm ? 'readonly style="width:100%; padding:8px; background:#f6f6f6;"' : 'required style="width:100%; padding:8px;"' ?>>
        </div>

        <div>
          <label><?= h(t('field.email','Email')) ?></label>
          <input type="email" name="email" value="<?= h($prefill['email']) ?>" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= h(t('field.phone','Phone Number')) ?></label>
          <input type="text" name="phone" value="<?= h($prefill['phone']) ?>" style="width:100%; padding:8px;">
        </div>

        <div>
          <label><?= h(t('field.country_locked','Country (auto-filled, read-only)')) ?></label>
          <input type="text" name="country" value="<?= h($prefill['country']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div style="grid-column:1 / -1;">
          <label><?= h(t('field.usdt_wallet','USDT Wallet Address')) ?></label>
          <input type="text" name="wallet_address" value="<?= h($prefill['wallet_address']) ?>" required style="width:100%; padding:8px;">
          <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
          ?>
        </div>

        <div style="grid-column:1 / -1;">
          <label><?= h(t('field.codepay_easy','CodePay Easy Address')) ?></label>
          <input type="text" name="codepay_address" value="<?= h($prefill['codepay_address']) ?>" style="width:100%; padding:8px;" oninput="this.value=this.value.toUpperCase();">
        </div>

        <div style="grid-column:1 / -1;">
          <label><?= h(t('field.referral_code','Referral Code (auto-generated, read-only)')) ?></label>
          <input type="text" name="referral_code" value="<?= h($prefill['referral_code']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>
      </div>

      <div style="text-align:center; margin-top:22px;">
        <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
          <?= h(t('common.next_step','Go to Step 2')) ?>
        </button>
      </div>
    </form>

  <?php else: ?>

      <div style="padding:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; margin-bottom:18px;">
        <div style="font-weight:600; margin-bottom:8px;"><?= t('account.step1.summary') ?></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:14px;">
          <div><b><?= t('field.username_login') ?>:</b> <?= h($prefill['username']) ?></div>
          <div><b><?= t('field.name') ?>:</b> <?= h($prefill['name']) ?></div>
          <div><b><?= t('field.email') ?>:</b> <?= h($prefill['email']) ?></div>
          <div><b><?= t('field.phone') ?>:</b> <?= h($prefill['phone']) ?></div>
          <div><b><?= t('field.country') ?>:</b> <?= h($prefill['country']) ?></div>
          <div><b><?= t('field.referral') ?>:</b> <?= h($prefill['referral_code']) ?></div>
        </div>
      </div>

      <form method="POST" action="create_account.php?<?= $base_qs ? h($base_qs.'&') : '' ?>step=2">
        <input type="hidden" name="_action" value="<?= $is_edit ? 'update_user' : 'create_user' ?>">
        <?php if ($is_edit): ?>
          <input type="hidden" name="id" value="<?= h($prefill['id']) ?>">
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">

          <div>
            <label><?= h(t('field.role','Role')) ?></label>
            <?php if ($is_edit): ?>
              <!-- ✅ 수정 화면에서는 누구도 역할을 변경할 수 없음(표시만) -->
              <input type="text" value="<?= h($allowed_roles_labels[strtolower($prefill['role'])] ?? $prefill['role']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
              <input type="hidden" name="role" value="<?= h($prefill['role']) ?>">
            <?php else: ?>
              <select name="role" required style="width:100%; padding:8px;">
                <option value=""><?= t('option.select') ?></option>
                <?php
                  $roles = $allowed_roles;
                  $roles = array_values(array_filter($roles, fn($x) => strtolower((string)$x) !== 'gm'));
                  foreach ($roles as $r):
                    $sel = (strtolower($prefill['role']) === $r) ? 'selected' : '';
                ?>
                  <option value="<?= h($r) ?>" <?= $sel ?>><?= h($allowed_roles_labels[$r] ?? $r) ?></option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>

<?php $role = isset($role) ? $role : strtolower((string)($prefill['role'] ?? ($_POST['role'] ?? ($_GET['role'] ?? '')))); ?>
           <div id="sponsorBox" style="<?= ($role === 'gm') ? 'display:none;' : '' ?>">
            <?php if ($role !== 'admin' && $role !== 'gm'): ?>
<label><?= h(t('field.parent','Parent (Team)')) ?></label>
            <?php if ($is_edit): ?>
              <!-- ✅ 수정 화면: 상위(소속)는 표시만 하고 수정 불가(모든 역할/권한 공통) -->
              <input type="text" value="<?= h($prefill['sponsor_label'] ?? $prefill['sponsor_id']) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
              <input type="hidden" name="sponsor_id" value="<?= h($prefill['sponsor_id']) ?>">
            <?php else: ?>
              <select name="sponsor_id" id="sponsorSelect" style="width:100%; padding:8px;">
                <option value="">-- <?= htmlspecialchars($lang['account.parent.auto_filtered'] ?? 'Filtered after role selection') ?> --</option>
              </select>
              <input type="hidden" id="currentSponsorId" value="<?= h($prefill['sponsor_id']) ?>">
            <?php endif; ?>
          <?php endif; ?>
          </div>

<?php
  $show_referrer = ($is_edit && strtolower((string)($prefill['role'] ?? '')) === 'investor'); // 최초 로드(편집 대상이 investor면 표시)
  $ref_ro = ($is_edit && !$is_gm) ? 'readonly' : '';
?>
<div id="referrerBox" style="<?= $show_referrer ? '' : 'display:none;' ?> grid-column:1 / -1; position:relative;">
  <label><?= h(t('field.referrer_search','Referrer Search (search by referral code and select)')) ?></label>

  <!-- 추천인 선택값(필수 아님) -->
  <input type="hidden" name="referrer_id" id="referrer_id" value="<?= h($prefill['referrer_id'] ?? '') ?>">

  <!-- 사용자가 검색하는 입력창 -->
  <input type="text"
         id="referrer_search"
         name="referrer_search"
         value="<?= h($prefill['referrer_name'] ?? '') ?>"
         placeholder="<?= h(t('ph.referrer_code_example','e.g., REF_20260103_123456')) ?>"
         style="width:100%; padding:8px;"
         autocomplete="off"
         <?= $ref_ro ?>>

  <!-- 자동완성 리스트 -->
  <div id="referrer_suggest"
       style="display:none; position:absolute; left:0; right:0; top:72px; background:#fff; border:1px solid #ddd; border-radius:6px; max-height:220px; overflow:auto; z-index:50;"></div>

  <div style="font-size:12px;color:#888;margin-top:6px;">
    <?= h(t('hint.referrer_search','* Type at least 1 character to search by referral code. Select from the list.')) ?>
  </div>
</div>
<?php
$target_role = strtolower((string)($prefill['role'] ?? ''));
$show_xm = ($is_edit && $target_role === 'investor'); // 최초 로드시만(편집 대상이 investor면 표시)
?>

<div id="xmUltimaBox" style="<?= $show_xm ? '' : 'display:none;' ?>; grid-column:1 / -1; position:relative; margin-top:10px; padding-top:10px; border-top:1px dashed #ddd;">
  <div style="font-weight:600; margin-bottom:8px;"><?= h(t('section.platform_accounts','XM / ULTIMA Account Info')) ?></div>

  <style>
    #xmUltimaBox .xm-ultima-wrap{display:flex; gap:16px; align-items:stretch;}
    #xmUltimaBox .xm-ultima-card{flex:1; border:1px solid #ddd; border-radius:10px; padding:14px; background:#fff;}
    #xmUltimaBox .xm-ultima-card h4{margin:0 0 10px; font-size:14px;}
    #xmUltimaBox .xm-ultima-field{margin-bottom:10px;}
    #xmUltimaBox .xm-ultima-field label{display:block; font-size:12px; font-weight:600; margin-bottom:6px;}
    #xmUltimaBox .xm-ultima-field input{width:100%; padding:8px; border:1px solid #ddd; border-radius:8px;}
    #xmUltimaBox .xm-ultima-field:last-child{margin-bottom:0;}
    @media (max-width: 900px){
      #xmUltimaBox .xm-ultima-wrap{flex-direction:column;}
    }
  </style>

  <div class="xm-ultima-wrap">
    <div class="xm-ultima-card">
      <h4><?= h(t('section.xm_account','XM Account Info')) ?></h4>
      <div class="xm-ultima-field">
        <label><?= h(t('platform.xm_id','XM ID')) ?></label>
        <input type="text" name="xm_id" value="<?= h($prefill['xm_id']) ?>">
      </div>
      <div class="xm-ultima-field">
        <label><?= h(t('platform.xm_pw','XM Password')) ?></label>
        <input type="text" name="xm_pw" value="<?= h($prefill['xm_pw']) ?>">
      </div>
      <div class="xm-ultima-field">
        <label><?= h(t('platform.xm_server','XM Server')) ?></label>
        <input type="text" name="xm_server" value="<?= h($prefill['xm_server']) ?>">
      </div>
    </div>

    <div class="xm-ultima-card">
      <h4><?= h(t('section.ultima_account','ULTIMA Account Info')) ?></h4>
      <div class="xm-ultima-field">
        <label><?= h(t('platform.ultima_id','ULTIMA ID')) ?></label>
        <input type="text" name="ultima_id" value="<?= h($prefill['ultima_id']) ?>">
      </div>
      <div class="xm-ultima-field">
        <label><?= h(t('platform.ultima_pw','ULTIMA Password')) ?></label>
        <input type="text" name="ultima_pw" value="<?= h($prefill['ultima_pw']) ?>">
      </div>
      <div class="xm-ultima-field">
        <label><?= h(t('platform.ultima_server','ULTIMA Server')) ?></label>
        <input type="text" name="ultima_server" value="<?= h($prefill['ultima_server']) ?>">
      </div>
    </div>
  </div>
</div>
        </div>

        <div style="display:flex; gap:10px; justify-content:center; margin-top:22px;">
          <a href="create_account.php?<?= $base_qs ? h($base_qs.'&') : '' ?>step=1" class="btn confirm" style="padding:10px 18px; background:#6c757d; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none;"><?= h(t('btn.to_step1','Back to Step 1')) ?></a>
          <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
            <?= $is_edit ? t('btn.edit_complete') : t('btn.create_account') ?>
          </button>
        </div>
      </form>

      <?php if (!$is_edit): ?>

      <script>
        const pool = <?= json_encode($pool, JSON_UNESCAPED_UNICODE) ?>;
        const sponsorSelect = document.getElementById('sponsorSelect');
        
    // i18n labels
    const TXT_SELECT = <?= json_encode($lang['option.select'] ?? 'Select') ?>;
    const TXT_NONE = <?= json_encode($lang['option.none'] ?? 'None') ?>;
const roleSelect = document.querySelector('select[name="role"]');
        const currentSponsorId = document.getElementById('currentSponsorId') ? document.getElementById('currentSponsorId').value : '';
        const xmBox = document.getElementById('xmUltimaBox');
const refBox = document.getElementById('referrerBox');
const refInput = document.getElementById('referrer_search');
const refSuggest = document.getElementById('referrer_suggest');
const refIdInput = document.getElementById('referrer_id');

function toggleReferrerBox(){
  if(!roleSelect || !refBox) return;
  const isInvestor = (roleSelect.value || '').toLowerCase() === 'investor';
  refBox.style.display = isInvestor ? '' : 'none';

  // 투자자 아니면 추천인 값 초기화(오입력 방지)
  if(!isInvestor){
    if(refInput) refInput.value = '';
    if(refIdInput) refIdInput.value = '';
    if(refSuggest) { refSuggest.style.display = 'none'; refSuggest.innerHTML = ''; }
  }
}

async function fetchReferrer(q){
  const url = `create_account.php?action=search_referrer&q=${encodeURIComponent(q)}`;
  const res = await fetch(url, { credentials:'same-origin' });
  if(!res.ok) return [];
  return await res.json();
}

function renderSuggest(list){
  if(!refSuggest) return;
  if(!Array.isArray(list) || list.length === 0){
    refSuggest.style.display = 'none';
    refSuggest.innerHTML = '';
    return;
  }
  refSuggest.innerHTML = list.map(u => {
    const label = `${u.referral_code || ''}`;
    const display = `${u.referral_code || ''} — ${u.name} (#${u.id} ${u.username})`;
    return `<div class="ref-item" data-id="${u.id}" data-label="${encodeURIComponent(label)}"
                style="padding:8px 10px; cursor:pointer; border-bottom:1px solid #f1f1f1;">
              ${display}
            </div>`;
  }).join('');
  refSuggest.style.display = '';
}

let refTimer = null;
if(refInput && !refInput.hasAttribute('readonly')){
  refInput.addEventListener('input', () => {
    const q = (refInput.value || '').trim();
    if(refTimer) clearTimeout(refTimer);

    // ✅ 1글자 이상부터
    if(q.length < 1){
      renderSuggest([]);
      if(refIdInput) refIdInput.value = '';
      return;
    }

    // 약간의 디바운스(최소 부하)
    refTimer = setTimeout(async () => {
      const list = await fetchReferrer(q);
      renderSuggest(list);
    }, 120);
  });

  document.addEventListener('click', (e) => {
    if(!refSuggest) return;
    const item = e.target.closest('.ref-item');
    if(item){
      const id = item.getAttribute('data-id') || '';
      const label = decodeURIComponent(item.getAttribute('data-label') || '');
      if(refIdInput) refIdInput.value = id;
      if(refInput) refInput.value = label;
      refSuggest.style.display = 'none';
      return;
    }
    // 박스 밖 클릭 시 닫기
    if(!refBox.contains(e.target)){
      refSuggest.style.display = 'none';
    }
  });
}

// role 변경 시 추천인 박스 토글 연결
if(roleSelect){
  roleSelect.addEventListener('change', toggleReferrerBox);
  toggleReferrerBox();
}

        function requiredSponsorRole(role){
          role = (role||'').toLowerCase();
          if (role === 'admin') return 'gm';
          if (role === 'master') return 'admin';
          if (role === 'agent') return 'master';
          if (role === 'investor') return 'agent';
          return '';
        }

        function refreshSponsorOptions(){
          if(!sponsorSelect || !roleSelect) return;
          const role = roleSelect.value;
           const roleLower = (role || '').toLowerCase();
          const sponsorBox = document.getElementById('sponsorBox') || sponsorSelect.parentElement;
           // ✅ GM/ADMIN은 상위(소속) 선택 없음

if (role === 'admin') {
    sponsorBox.style.display = 'none';
    sponsorSelect.value = '';
} else {
    sponsorBox.style.display = '';
}

           if (roleLower === 'gm' || roleLower === 'admin') {
             if (sponsorBox) sponsorBox.style.display = 'none';
             sponsorSelect.innerHTML = '';
             const opt = document.createElement('option');
             opt.value = '';
             opt.textContent = <?= json_encode(t('option.none')) ?>;
             sponsorSelect.appendChild(opt);
             return;
           }
           if (sponsorBox) sponsorBox.style.display = '';
          const need = requiredSponsorRole(role);
          sponsorSelect.innerHTML = '';
          const opt0 = document.createElement('option');
          opt0.value = '';
          opt0.textContent = need ? ('-- '+need.toUpperCase()+' ' + TXT_SELECT + ' --') : <?= json_encode(t('option.none')) ?>;
          sponsorSelect.appendChild(opt0);

          if(!need) return;

          const list = pool[need] || [];
          list.forEach(u => {
            const op = document.createElement('option');
            op.value = u.id;
            op.textContent = `#${u.id} ${u.referral_code} — ${u.name} (${u.username}) ${u.phone||''}`;
            if (String(u.id) === String(currentSponsorId)) op.selected = true;
            sponsorSelect.appendChild(op);
          });
        }

        if (roleSelect) {
          roleSelect.addEventListener('change', refreshSponsorOptions);
          refreshSponsorOptions();
        }

        function toggleXmUltima(){
          if(!roleSelect || !xmBox) return;
          const isInvestor = roleSelect.value.toLowerCase() === 'investor';
          xmBox.style.display = isInvestor ? '' : 'none';

        }

        if(roleSelect){
          roleSelect.addEventListener('change', toggleXmUltima);
          toggleXmUltima();
        }
      </script>
      <?php endif; ?>

  <?php endif; ?>

</div>
