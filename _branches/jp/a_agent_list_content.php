<h2 style="text-align:center;"><?= t('agent_list.title') ?></h2>

<div class="chart-box" style="max-width:1000px; margin:auto;">
    <table>
        <thead>
            <tr>
                <th><?= t('field.id','ID') ?></th>
                <th><?= t('agent_list.th_name') ?></th>
                <th><?= t('agent_list.th_email') ?></th>
                <th><?= t('agent_list.th_phone') ?></th>
                <th><?= t('agent_list.th_country') ?></th>
                <th><?= t('agent_list.th_role') ?></th>
                <th><?= t('agent_list.th_actions') ?></th>
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
                               href="edit_member.php?id=<?= $row['id'] ?>&redirect=a_agent_list.php"><?= t('common.edit') ?></a>

                            <a class="btn delete"
                               href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                               onclick="return confirm(<?= json_encode(t('common.confirm_delete')) ?>);">🗑 <?= t('common.delete') ?></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7"><?= t('agent_list.empty') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>