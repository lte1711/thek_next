<h2 style="text-align:center;">회원 정보 수정</h2>
<form method="POST" style="max-width:600px; margin:auto;">
    <!-- users 테이블 필드 -->
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="name" style="display:block; font-weight:bold;">이름</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($member['name'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="username" style="display:block; font-weight:bold;">아이디</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($member['username'] ?? '') ?>" required>
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="email" style="display:block; font-weight:bold;">이메일</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($member['email'] ?? '') ?>" required>
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="country" style="display:block; font-weight:bold;">국가</label>
        <input type="text" id="country" name="country" value="<?= htmlspecialchars($member['country'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="role" style="display:block; font-weight:bold;">역할</label>
        <select id="role" name="role">
            <option value="gm"       <?= $member['role']=='gm'?'selected':'' ?>>Global Master</option>
            <option value="admin"    <?= $member['role']=='admin'?'selected':'' ?>>Admin</option>
            <option value="master"   <?= $member['role']=='master'?'selected':'' ?>>Master</option>
            <option value="agent"    <?= $member['role']=='agent'?'selected':'' ?>>Agent</option>
            <option value="investor" <?= $member['role']=='investor'?'selected':'' ?>>Investor</option>
        </select>
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="phone" style="display:block; font-weight:bold;">전화번호</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="referral_code" style="display:block; font-weight:bold;">추천 코드</label>
        <input type="text" id="referral_code" value="<?= htmlspecialchars($member['referral_code'] ?? '') ?>" readonly>
    </div>

    <!-- user_details 테이블 필드 -->
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="wallet_address" style="display:block; font-weight:bold;">지갑 주소</label>
        <input type="text" id="wallet_address" name="wallet_address" value="<?= htmlspecialchars($member['wallet_address'] ?? '') ?>">
        <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
        ?>
    </div>

    <h3 style="text-align:center;">XM 계정 정보</h3>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="xm_id" style="display:block; font-weight:bold;">XM 아이디</label>
        <input type="text" id="xm_id" name="xm_id" value="<?= htmlspecialchars($member['xm_id'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="xm_pw" style="display:block; font-weight:bold;">XM 비밀번호</label>
        <input type="text" id="xm_pw" name="xm_pw" value="<?= htmlspecialchars($member['xm_pw'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="xm_server" style="display:block; font-weight:bold;">XM 서버</label>
        <input type="text" id="xm_server" name="xm_server" value="<?= htmlspecialchars($member['xm_server'] ?? '') ?>">
    </div>

    <h3 style="text-align:center;">Ultima 계정 정보</h3>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="ultima_id" style="display:block; font-weight:bold;">Ultima 아이디</label>
        <input type="text" id="ultima_id" name="ultima_id" value="<?= htmlspecialchars($member['ultima_id'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="ultima_pw" style="display:block; font-weight:bold;">Ultima 비밀번호</label>
        <input type="text" id="ultima_pw" name="ultima_pw" value="<?= htmlspecialchars($member['ultima_pw'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="ultima_server" style="display:block; font-weight:bold;">Ultima 서버</label>
        <input type="text" id="ultima_server" name="ultima_server" value="<?= htmlspecialchars($member['ultima_server'] ?? '') ?>">
    </div>

    <h3 style="text-align:center;">브로커 계정 정보</h3>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker_id" style="display:block; font-weight:bold;">브로커 아이디</label>
        <input type="text" id="broker_id" name="broker_id" value="<?= htmlspecialchars($member['broker_id'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker_pw" style="display:block; font-weight:bold;">브로커 비밀번호</label>
        <input type="text" id="broker_pw" name="broker_pw" value="<?= htmlspecialchars($member['broker_pw'] ?? '') ?>">
    </div>

    <h3 style="text-align:center;">추가 브로커 정보</h3>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker1_id" style="display:block; font-weight:bold;">브로커1 아이디</label>
        <input type="text" id="broker1_id" name="broker1_id" value="<?= htmlspecialchars($member['broker1_id'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker1_pw" style="display:block; font-weight:bold;">브로커1 비밀번호</label>
        <input type="text" id="broker1_pw" name="broker1_pw" value="<?= htmlspecialchars($member['broker1_pw'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker1_server" style="display:block; font-weight:bold;">브로커1 서버</label>
        <input type="text" id="broker1_server" name="broker1_server" value="<?= htmlspecialchars($member['broker1_server'] ?? '') ?>">
    </div>

    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker2_id" style="display:block; font-weight:bold;">브로커2 아이디</label>
        <input type="text" id="broker2_id" name="broker2_id" value="<?= htmlspecialchars($member['broker2_id'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker2_pw" style="display:block; font-weight:bold;">브로커2 비밀번호</label>
        <input type="text" id="broker2_pw" name="broker2_pw" value="<?= htmlspecialchars($member['broker2_pw'] ?? '') ?>">
    </div>
    <div class="form-group" style="text-align:center; margin-bottom:15px;">
        <label for="broker2_server" style="display:block; font-weight:bold;">브로커2 서버</label>
        <input type="text" id="broker2_server" name="broker2_server" value="<?= htmlspecialchars($member['broker2_server'] ?? '') ?>">
    </div>

<div class="form-group" style="text-align:center; margin-bottom:15px;">
    <label for="selected_broker" style="display:block; font-weight:bold;">선택된 브로커</label>
    <input type="text" id="selected_broker" name="selected_broker" 
           value="<?= htmlspecialchars($member['selected_broker'] ?? '') ?>">
</div>

<div style="text-align:center; margin-top:20px;">
    <button type="submit" class="btn edit">
        수정하기
    </button>
</div>
</form>
