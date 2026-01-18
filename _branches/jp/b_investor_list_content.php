<section class="content-area">
    <h2 style="text-align:center;"><?= t('investor_list.title') ?></h2>
    <table>
        <tr>
            <th><?= t('field.id','ID') ?></th>
            <th><?= t('investor_list.th_name') ?></th>
            <th><?= t('investor_list.th_email') ?></th>
            <th><?= t('investor_list.th_phone') ?></th>
            <th><?= t('investor_list.th_country') ?></th>
            <th><?= t('investor_list.th_role') ?></th>
            <th><?= t('investor_list.th_actions') ?></th>
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
                           <?= t('common.edit') ?>                        </a>

                        <a class="btn delete"
                           href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                           onclick="return confirm(<?= json_encode(t('common.confirm_delete','Are you sure you want to delete?')) ?>);">
                           🗑 <?= t('common.delete') ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7"><?= t('investor_list.empty') ?></td></tr>
        <?php endif; ?>
    </table>
</section>