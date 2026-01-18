<h2 style="text-align:center;">Referral List (내가 등록한 회원)</h2>

<!-- 검색창 -->
<div class="search-box">
  <input type="text" id="searchInput" placeholder="회원 검색 (이름/이메일)">
</div>

<table id="referralTable" class="form-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>이름</th>
      <th>이메일</th>
      <th>전화번호</th>
      <th>등급</th>
      <th>가입일</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td data-label="ID"><?= $row['id'] ?></td>
          <td data-label="이름"><?= htmlspecialchars($row['username']) ?></td>
          <td data-label="이메일"><?= htmlspecialchars($row['email']) ?></td>
          <td data-label="전화번호"><?= htmlspecialchars($row['phone']) ?></td>
          <td data-label="등급"><?= htmlspecialchars($row['role']) ?></td>
          <td data-label="가입일"><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6">추천인 회원이 없습니다.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<script>
  // 검색 기능
  document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#referralTable tbody tr");
    rows.forEach(row => {
      let text = row.innerText.toLowerCase();
      row.style.display = text.includes(filter) ? "" : "none";
    });
  });
</script>