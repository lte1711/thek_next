<?php
// 회원 조회 쿼리 (등급이 gm인 회원만)
$sql = "SELECT id, username, email, role, phone, country FROM users WHERE role='gm'";
$result = mysqli_query($conn, $sql);
?>

<h2 style="text-align:center;"><?= t('title.list.gm', 'Global Master List') ?></h2>

<!-- 검색창 -->
<div class="search-box">
    <input type="text" id="searchInput" placeholder="<?= htmlspecialchars(t('search.member_name_email', 'Search members (name/email)')) ?>">
</div>

<table id="memberTable">
    <tr>
        <th><?= t('field.id','ID') ?></th>
        <th><?= t('field.name','Name') ?></th>
        <th><?= t('field.email','Email') ?></th>
        <th><?= t('field.phone','Phone Number') ?></th>
        <th><?= t('field.country','Country') ?></th>
        <th><?= t('field.role','Role') ?></th>
        <th><?= t('field.actions','Actions') ?></th>
    </tr>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['username'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['phone'] ?></td>
                <td><?= $row['country'] ?></td>
                <td><?= $row['role'] ?></td>

                <td>
                    <a class="button edit"
                       href="edit_member.php?id=<?= $row['id'] ?>&redirect=gm_list.php">
                       ✏ <?= t('btn.edit','Edit') ?>
                    </a>

                    <a class="btn delete"
                       href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                       onclick="return confirm(<?= json_encode(t('confirm.delete_member', 'Are you sure you want to delete this member?')) ?>);">
                       🗑 <?= t('btn.delete','Delete') ?>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7"><?= t('gm_list.empty','No Global Master members found.') ?></td></tr>
    <?php endif; ?>
</table>

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