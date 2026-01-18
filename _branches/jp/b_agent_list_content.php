<section class="content-area">
    <h2 style="text-align:center;"><?= t('agent_list.title') ?></h2>
    <table>
        <tr>
            <th><?= t('field.id','ID') ?></th>
            <th><?= t('agent_list.th_name') ?></th>
            <th><?= t('agent_list.th_email') ?></th>
            <th><?= t('agent_list.th_phone') ?></th>
            <th><?= t('agent_list.th_country') ?></th>
            <th><?= t('agent_list.th_role') ?></th>
            <th><?= t('agent_list.th_actions') ?></th>
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
                           href="create_agent.php?id=<?= urlencode($row['id']) ?>&redirect=b_agent_list.php">
                           <?= t('common.edit') ?>
                        </a>

                        <a class="btn delete"
                           href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                           onclick="return confirm('<?= t('agent_list.confirm_delete') ?>');">
                           🗑 <?= t('common.delete') ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7"><?= t('agent_list.empty') ?></td></tr>
        <?php endif; ?>
    </table>
</section>