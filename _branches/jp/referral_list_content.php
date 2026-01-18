<h2 style="text-align:center;"><?= function_exists('t') ? t('referral.title', 'Referral List') : 'Referral List' ?></h2>

<!-- 검색창 -->
<div class="search-box">
  <input type="text" id="searchInput" placeholder="<?= function_exists('t') ? t('referral.search.placeholder', 'Search members (name/email)') : 'Search' ?>">
</div>

<table id="referralTable" class="form-table">
  <thead>
    <tr>
      <th><?= function_exists('t') ? t('referral.table.id', 'ID') : 'ID' ?></th>
      <th><?= function_exists('t') ? t('referral.table.name', '이름') : '이름' ?></th>
      <th><?= function_exists('t') ? t('referral.table.email', '이메일') : '이메일' ?></th>
      <th><?= function_exists('t') ? t('referral.table.phone', '전화번호') : '전화번호' ?></th>
      <th><?= function_exists('t') ? t('referral.table.role', '등급') : '등급' ?></th>
      <th><?= function_exists('t') ? t('referral.table.joined', '가입일') : '가입일' ?></th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td data-label="<?= function_exists('t') ? t('referral.table.id', 'ID') : 'ID' ?>"><?= $row['id'] ?></td>
          <td data-label="<?= function_exists('t') ? t('referral.table.name', '이름') : '이름' ?>"><?= htmlspecialchars($row['username']) ?></td>
          <td data-label="<?= function_exists('t') ? t('referral.table.email', '이메일') : '이메일' ?>"><?= htmlspecialchars($row['email']) ?></td>
          <td data-label="<?= function_exists('t') ? t('referral.table.phone', '전화번호') : '전화번호' ?>"><?= htmlspecialchars($row['phone']) ?></td>
          <td data-label="<?= function_exists('t') ? t('referral.table.role', '등급') : '등급' ?>"><?= htmlspecialchars($row['role']) ?></td>
          <td data-label="<?= function_exists('t') ? t('referral.table.joined', '가입일') : '가입일' ?>"><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6"><?= function_exists('t') ? t('referral.empty', 'No referred members.') : 'No referred members.' ?></td></tr>
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