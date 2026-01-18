<h2 style="text-align:center;">MASTER 회원 리스트</h2>

<div class="chart-box" style="max-width:1000px; margin:auto;">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>이름</th>
            <th>이메일</th>
            <th>전화</th>
            <th>지역</th>
            <th>등급</th>
            <th>관리</th>
        </tr>
    </thead>
<tbody>
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['username'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['phone'] ?></td>
                <td><?= $row['country'] ?></td>
                <td><?= $row['role'] ?></td>

                <td>
                    <a class="btn edit"
                       href="create_master.php?id=<?= $row['id'] ?>&redirect=a_master_list.php">
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
        <tr><td colspan="7">MASTER 회원이 없습니다.</td></tr>
    <?php endif; ?>
</tbody>
</table>
</div>