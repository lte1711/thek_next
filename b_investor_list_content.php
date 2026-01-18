<section class="content-area">
    <h2 style="text-align:center;">INVESTOR 회원 리스트</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>이름</th>
            <th>이메일</th>
            <th>전화</th>
            <th>지역</th>
            <th>등급</th>
            <th>관리</th>
        </tr>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['country']) ?></td>
                    <td><?= htmlspecialchars($row['role']) ?></td>

                    <td>
                        <a class="btn edit"
                           href="create_investor.php?id=<?= urlencode($row['id']) ?>&redirect=b_investor_list.php">
                           수정
                        </a>

                        <a class="btn delete"
                           href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                           onclick="return confirm('정말 삭제하시겠습니까?');">
                           🗑 삭제
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">INVESTOR 회원이 없습니다.</td></tr>
        <?php endif; ?>
    </table>
</section>