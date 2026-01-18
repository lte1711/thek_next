    <h2 style="text-align:center;"><?= t('title.list.investor', 'Investor Member List') ?></h2>

    <div class="search-box">
        <input type="text" id="searchInput" placeholder="<?= t('placeholder.member_search', 'Search members (name/email)') ?>">
    </div>

    <table id="memberTable">
        <tr>
            <th><?= t('table.id', 'ID') ?></th>
            <th><?= t('table.name', 'Name') ?></th>
            <th><?= t('table.email', 'Email') ?></th>
            <th><?= t('table.phone', 'Phone') ?></th>
            <th><?= t('table.role', 'Role') ?></th>
            <th><?= t('table.manage', 'Manage') ?></th>
        </tr>

        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td data-label="<?= t('table.id', 'ID') ?>"><?= $row['id'] ?></td>
                    <td data-label="<?= t('table.name', 'Name') ?>"><?= htmlspecialchars($row['username']) ?></td>
                    <td data-label="<?= t('table.email', 'Email') ?>"><?= htmlspecialchars($row['email']) ?></td>
                    <td data-label="<?= t('table.phone', 'Phone') ?>"><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                    <td data-label="<?= t('table.role', 'Role') ?>"><?= htmlspecialchars($row['role']) ?></td>

                    <td data-label="<?= t('table.manage', 'Manage') ?>">
                        <div class="button-group">
                            <a class="button edit"
                               href="edit_member.php?id=<?= $row['id'] ?>&redirect=investor_list.php">
                               ✏ <?= t('btn.edit', 'Edit') ?>
                            </a>

                            <a class="btn delete"
                               href="delete_member.php?id=<?= $row['id'] ?>&return=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                               onclick="return confirm('<?= t('confirm.delete', 'Are you sure you want to delete?') ?>');">
                               🗑 <?= t('btn.delete', 'Delete') ?>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6"><?= t('msg.no_investor_members', 'No investor members found.') ?></td></tr>
        <?php endif; ?>
    </table>
</section>

<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#memberTable tr:not(:first-child)");
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
});
</script>
