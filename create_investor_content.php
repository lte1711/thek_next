<?php
// 이 파일은 create_investor.php에서 include되며, $conn, $current_role, $step, $auto_country, $auto_referral_code 사용 가능

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$allowed_roles = allowed_child_roles($current_role);
$allowed_roles_labels = [
  'gm' => '글로벌 마스터(GM)',
  'admin' => '관리자(Admin)',
  'master' => '마스터(Master)',
  'agent' => '에이전트(Agent)',
  'investor' => '투자자(Investor)',
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

<h2 class="section-title" style="text-align:center; margin-top:20px;">계정 생성 (2단계)</h2>

<div class="chart-box" style="max-width:900px; margin:20px auto; padding:20px; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.08); border-radius:8px;">

  <div style="display:flex; gap:10px; justify-content:center; margin-bottom:20px;">
    <div style="padding:8px 14px; border-radius:999px; <?= $step===1 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>">1단계: 기본정보</div>
    <div style="padding:8px 14px; border-radius:999px; <?= $step===2 ? 'background:#0d6efd;color:#fff;' : 'background:#f1f1f1;color:#333;' ?>">2단계: 역할/소속/추천</div>
  </div>

  <?php if ($step === 1): ?>
    <form method="POST" action="create_investor.php?step=1">
      <input type="hidden" name="_action" value="save_step1">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
        <div>
          <label>아이디(로그인)</label>
          <input type="text" name="username" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>비밀번호</label>
          <input type="password" name="password" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>이름</label>
          <input type="text" name="name" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>이메일</label>
          <input type="email" name="email" required style="width:100%; padding:8px;">
        </div>

        <div>
          <label>전화번호</label>
          <input type="text" name="phone" style="width:100%; padding:8px;">
        </div>

        <div>
          <label>국적 (자동 입력, 수정 불가)</label>
          <input type="text" name="country" value="<?= h($country_val) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>

        <div style="grid-column:1 / -1;">
          <label>USDT지갑주소</label>
          <input type="text" name="wallet_address" required style="width:100%; padding:8px;">
          <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
          ?>
        </div>

        <div style="grid-column:1 / -1;">
          <label>코드페이 간편주소</label>
          <input type="text" name="codepay_address" style="width:100%; padding:8px;" oninput="this.value=this.value.toUpperCase();">
        </div>

        <div style="grid-column:1 / -1;">
          <label>Referral Code (자동 생성, 수정 불가)</label>
          <input type="text" name="referral_code" value="<?= h($referral_val) ?>" readonly style="width:100%; padding:8px; background:#f6f6f6;">
        </div>
      </div>

      <div style="text-align:center; margin-top:22px;">
        <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
          2단계로 이동
        </button>
      </div>
    </form>

  <?php else: ?>
    <?php if (!$s1): ?>
      <p style="color:red; text-align:center;">1단계 정보가 없습니다. 다시 진행해주세요.</p>
      <div style="text-align:center; margin-top:15px;">
        <a href="create_investor.php?step=1" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none;">
          1단계로 돌아가기
        </a>
      </div>
    <?php else: ?>
      <div style="padding:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; margin-bottom:18px;">
        <div style="font-weight:600; margin-bottom:8px;">1단계 입력 요약</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:14px;">
          <div><b>아이디:</b> <?= h($s1['username']) ?></div>
          <div><b>이름:</b> <?= h($s1['name']) ?></div>
          <div><b>이메일:</b> <?= h($s1['email']) ?></div>
          <div><b>전화:</b> <?= h($s1['phone']) ?></div>
          <div><b>국적:</b> <?= h($s1['country']) ?></div>
          <div><b>Referral:</b> <?= h($s1['referral_code']) ?></div>
          <div style="grid-column:1/-1;"><b>Wallet:</b> <?= h($s1['wallet_address']) ?></div>
          <div style="grid-column:1/-1;"><b>CodePay:</b> <?= h($s1['codepay_address']) ?></div>
        </div>
      </div>

      <form method="POST" action="create_investor.php?step=2">
        <input type="hidden" name="_action" value="create_user">


        <!-- STEP 2: XM / ULTIMA 계정 정보 -->
        <div style="margin-top:10px; padding:12px; border:1px solid #e9ecef; border-radius:8px; background:#fbfbfb;">
          <div style="font-weight:600; margin-bottom:10px;">플랫폼 계정 정보 (XM / ULTIMA)</div>

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
            <label>생성할 역할(Role)</label>
            <select name="role" id="roleSelect" required style="width:100%; padding:8px;">
              <option value="">-- 선택 --</option>
              <?php foreach ($allowed_roles as $r): ?>
                <option value="<?= h($r) ?>"><?= h($allowed_roles_labels[$r] ?? $r) ?></option>
              <?php endforeach; ?>
            </select>
            <div style="font-size:12px; color:#666; margin-top:6px;">현재 권한(<?= h($current_role) ?>) 기준으로 허용된 역할만 표시됩니다.</div>
          </div>

          <div>
            <label>상위(소속) 선택</label>
            <select name="sponsor_id" id="sponsorSelect" style="width:100%; padding:8px;" disabled>
              <option value="">역할을 먼저 선택하세요</option>
            </select>
            <div id="sponsorHint" style="font-size:12px; color:#666; margin-top:6px;"></div>
          </div>

          <div id="referrerBox" style="grid-column:1/-1; display:none;">
            <div style="padding:12px; border:1px dashed #ced4da; border-radius:8px;">
              <div style="font-weight:600; margin-bottom:10px;">추천인 선택 (투자자 전용)</div>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                  <label>추천인 이름(정확히)</label>
                  <input type="text" name="referrer_name" style="width:100%; padding:8px;">
                </div>
                <div>
                  <label>추천인 전화번호(동명이인 있을 때)</label>
                  <input type="text" name="referrer_phone" style="width:100%; padding:8px;">
                </div>
              </div>
              <div style="font-size:12px; color:#666; margin-top:8px;">
                * 동명이인이 있으면 전화번호로 2차 확인합니다.
              </div>
            </div>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; gap:10px; margin-top:22px;">
          <a href="create_investor.php?step=1" class="btn" style="padding:10px 22px; background:#6c757d; color:#fff; border:none; border-radius:6px; cursor:pointer; text-decoration:none;">
            1단계로
          </a>

          <button type="submit" class="btn confirm" style="padding:10px 22px; background:#0d6efd; color:#fff; border:none; border-radius:6px; cursor:pointer;">
            계정 생성 확정
          </button>
        </div>
      </form>

      <script>
      (function(){
                const sponsorSelect = document.getElementById('sponsorSelect');
        const sponsorHint = document.getElementById('sponsorHint');
        const referrerBox = document.getElementById('referrerBox');

        const pools = <?= json_encode($pool, JSON_UNESCAPED_UNICODE) ?>;
        const labels = <?= json_encode($allowed_roles_labels, JSON_UNESCAPED_UNICODE) ?>;

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
            sponsorSelect.innerHTML = '<option value="">역할을 먼저 선택하세요</option>';
            sponsorHint.textContent = '';
            referrerBox.style.display = 'none';
            return;
          }

          referrerBox.style.display = (role === 'investor') ? 'block' : 'none';

          if(req === null){
            sponsorSelect.disabled = true;
            sponsorSelect.innerHTML = '<option value="">상위 선택 없음</option>';
            sponsorHint.textContent = '이 역할은 상위(소속) 선택이 필요 없습니다.';
            return;
          }

          sponsorSelect.disabled = false;
          const list = pools[req] || [];
          sponsorSelect.appendChild(new Option('-- 선택 --', ''));

          list.forEach(u => {
            const txt = `${u.id} | ${u.name||''} (${u.username}) ${u.phone?'- '+u.phone:''}`;
            sponsorSelect.appendChild(new Option(txt, u.id));
          });

          sponsorHint.textContent = `선택한 역할(${labelRole(role)})의 상위 역할은 ${labelRole(req)} 입니다.`;
        }

        setSponsorOptions('investor');
      })();
      </script>
    <?php endif; ?>
  <?php endif; ?>

</div>
