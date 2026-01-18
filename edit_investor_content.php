    <h2 style="text-align:center;">회원 정보 수정</h2>
<form method="POST" style="max-width:600px; margin:auto;">
        <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

        <div class="form-group" style="text-align:center; margin-bottom:15px;">
            <label for="username" style="display:block; font-weight:bold;">이름</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($form_username) ?>" required>
        </div>

        <div class="form-group" style="text-align:center; margin-bottom:15px;">
            <label for="email" style="display:block; font-weight:bold;">이메일</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_email) ?>" required>
        </div>

        <div class="form-group" style="text-align:center; margin-bottom:15px;">
            <label for="phone" style="display:block; font-weight:bold;">전화번호</label>
            <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($form_phone) ?>">
        </div>

        <div class="form-group" style="text-align:center; margin-bottom:15px;">
            <label for="country" style="display:block; font-weight:bold;">국가</label>
            <input type="text" id="country" name="country" value="<?= htmlspecialchars($form_country) ?>" readonly>
        </div>

        <div class="form-group" style="text-align:center; margin-bottom:15px;">
            <label for="referral_code" style="display:block; font-weight:bold;">레퍼럴 코드</label>
            <input type="text" id="referral_code" name="referral_code" value="<?= htmlspecialchars($form_referral_code) ?>" readonly>
        </div>

        <div class="form-group" style="text-align:center; margin-bottom:15px;">
            <label for="role" style="display:block; font-weight:bold;">역할</label>
            <input type="text" id="role" name="role" value="<?= htmlspecialchars($form_role) ?>" readonly>
        </div>

        <div style="text-align:center; margin-top:20px;">
            <button type="submit" class="btn edit">
                수정하기
            </button>
        </div>
    </form>
