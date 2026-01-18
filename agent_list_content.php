<section class="content-area">
    <h2 style="text-align:center;">AGENT 회원 리스트</h2>

    <!-- 검색창 -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="회원 검색 (이름/이메일)">
    </div>

    <table id="memberTable">
        <tr>
            <th>ID</th>
            <th>이름</th>
            <th>이메일</th>
            <th>전화번호</th>
            <th>등급</th>
            <th>관리</th>
        </tr>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td data-label="ID"><?= $row['id'] ?></td>
                    <td data-label="이름"><?= $row['username'] ?></td>
                    <td data-label="이메일"><?= $row['email'] ?></td>
                    <td data-label="전화번호"><?= $row['phone'] ?></td>
                    <td data-label="등급"><?= $row['role'] ?></td>

                    <td data-label="관리">
                        <div class="button-group">

                            <a class="button edit"
                               href="edit_member.php?id=<?= $row['id'] ?>&redirect=agent_list.php">
                               ✏ 수정
                            </a>

                            <a class="btn delete"
                               href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                               onclick="return confirm('정말 삭제하시겠습니까?');">
                               🗑 삭제
                            </a>

                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">AGENT 회원이 없습니다.</td></tr>
        <?php endif; ?>
    </table>
</section>

<script>
    // 검색 기능
    document.getElementById("searchInput").addEventListener("keyup", function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll("#memberTable tr:not(:first-child)");
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    });
</script>