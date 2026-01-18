<section class="content-area">
    <h2 style="text-align:center;">INVESTOR 회원 리스트</h2>
    <table style="width:100%; border-collapse:collapse; text-align:center;">
        <tr>
            <th>ID</th>
            <th>이름</th>
            <th>이메일</th>
            <th>전화</th>
            <th>지역</th>
            <th>등급</th>
            <th>관리</th>
        </tr>
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>".htmlspecialchars($row['id'])."</td>";
                echo "<td>".htmlspecialchars($row['username'])."</td>";
                echo "<td>".htmlspecialchars($row['email'])."</td>";
                echo "<td>".htmlspecialchars($row['phone'])."</td>";
                echo "<td>".htmlspecialchars($row['country'])."</td>";
                echo "<td>".htmlspecialchars($row['role'])."</td>";
                echo "<td>
                        <a class='btn edit' href='edit_investor.php?id=".urlencode($row['id'])."&redirect=c_investor_list.php'>수정</a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>INVESTOR 회원이 없습니다.</td></tr>";
        }
        ?>
    </table>
</section>