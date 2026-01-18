<h2 style="text-align:center;"><?= t('master.list.title','MASTER Member List') ?></h2>

<div class="chart-box" style="max-width:1000px; margin:auto;">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th><?= t('common.name','Name') ?></th>
            <th><?= t('common.email','Email') ?></th>
            <th><?= t('common.phone','Phone') ?></th>
            <th><?= t('common.country','Country') ?></th>
            <th><?= t('common.role','Role') ?></th>
            <th><?= t('common.manage','Manage') ?></th>
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
                       <?= t('common.edit','Edit') ?>
                    </a>

                    <a class="btn delete"
                       href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                       onclick="return confirm(<?= json_encode(t('common.confirm_delete','Are you sure you want to delete?')) ?>);">
                       🗑 <?= t('common.delete','Delete') ?>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="7"><?= t('master.list.empty','No MASTER members.') ?></td></tr>
    <?php endif; ?>
</tbody>
</table>
</div>