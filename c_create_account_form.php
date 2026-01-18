<h2 style="text-align:center;">INVESTOR 계정 생성</h2>
<form method="POST" style="max-width:600px; margin:auto;">
    <div class="form-group">
        <label>아이디</label>
        <input type="text" name="user_id" required>
        <span class="duplicate-check" onclick="alert('중복 체크는 서버에서 자동으로 수행됩니다.')">중복 체크</span>
    </div>
    <div class="form-group">
        <label>비밀번호</label>
        <input type="password" name="user_pw" required>
    </div>
    <div class="form-group">
        <label>이름</label>
        <input type="text" name="user_name" required>
    </div>
    <div class="form-group">
        <label>국가</label>
        <input type="text" name="country" value="<?= htmlspecialchars($auto_country ?? '') ?>" readonly>
    </div>
    <div class="form-group">
        <label>이메일</label>
        <input type="email" name="user_email" required>
    </div>
    <div class="form-group">
        <label>전화번호</label>
        <input type="text" name="phone">
    </div>
    <div class="form-group">
        <label>지갑 주소</label>
        <input type="text" name="wallet_address" required>
        <?php
            $__wallet_warning = __DIR__ . '/includes/wallet_warning.php';
            if (is_file($__wallet_warning)) include $__wallet_warning;
        ?>
    </div>
    <div class="form-group">
        <label>역할</label>
        <input type="text" name="user_role" value="investor" readonly>
    </div>
    <div class="form-group">
        <label>수익 배분율</label>
        <input type="text" name="profit_share" value="5" readonly>
    </div>
    <!-- ✅ 레퍼럴 코드 입력란 추가 -->
    <div class="form-group">
        <label>추천 코드</label>
        <input type="text" name="referral_code" placeholder="추천인 코드 입력">
    </div>
    <div style="text-align:center; margin-top:20px;">
        <button type="submit" class="btn edit">
            수정하기
        </button>
    </div>
</form>