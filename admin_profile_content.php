<section class="content-area">
  <h2>내 정보 수정</h2>

  <form method="post" action="admin_profile.php" class="form-box" style="max-width:720px; margin:0 auto;">
    <input type="hidden" name="_action" value="update_self">

    <div class="form-group">
      <label>아이디(수정 불가)</label>
      <input type="text" value="<?= htmlspecialchars($prefill['username'] ?? '') ?>" readonly>
    </div>

    <div class="form-group">
      <label>역할(수정 불가)</label>
      <input type="text" value="<?= htmlspecialchars($prefill['role'] ?? '') ?>" readonly>
    </div>

    <div class="form-group">
      <label>이름</label>
      <input type="text" name="name" value="<?= htmlspecialchars($prefill['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label>이메일</label>
      <input type="email" name="email" value="<?= htmlspecialchars($prefill['email'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label>전화번호</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($prefill['phone'] ?? '') ?>">
    </div>

    <hr style="margin:20px 0;">

    <div class="form-group">
      <label>비밀번호 변경 (선택)</label>
      <input type="password" name="password" placeholder="비밀번호를 바꾸려면 입력하세요 (6자 이상)">
    </div>

    <div class="form-group">
      <label>비밀번호 확인 (선택)</label>
      <input type="password" name="password_confirm" placeholder="비밀번호를 다시 입력하세요">
    </div>

    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
      <a class="btn" href="admin_dashboard.php">취소</a>
      <button class="btn" type="submit">저장</button>
    </div>
  </form>
</section>
