<section class="content-area">
    <!-- 어드민 데일리 정산 -->
    <h2>조직 데일리 정산</h2>

    <form method="GET" class="form-inline">
        <label>날짜 선택:</label>
        <input type="date" name="daily_date" value="<?= $settle_date ?>">
        <input type="hidden" name="list_period" value="<?= $period ?>">
        <button type="submit">조회</button>
            <?php if ($is_settled): ?>
                <button type="button" disabled style="opacity:0.6; cursor:not-allowed;">정산완료</button>
                <span style="font-size:12px; opacity:0.8;">
                    <?= $settled_at ? "(정산시각: " . htmlspecialchars($settled_at) . ")" : "" ?>
                </span>
            <?php else: ?>
                <button type="button" onclick="confirmSettlement(event)">정산</button>
            <?php endif; ?>    
        </form>

    <table>
        <tr>
            <th>관리자</th>
            <th>금액</th>
            <th>비율</th>
            <th>지갑주소</th>
            <th>코드페이주소</th>
            <th>상세보기</th>
        </tr>

        <?php foreach ($admin_data as $row): ?>
        <?php
            $percent = ($admin_total > 0) ? (($row['total_sales'] / $admin_total) * 100) : 0;
            $percent = number_format($percent, 2);
        ?>
        <tr>
            <td data-label="관리자"><?= htmlspecialchars($row['username']) . " (ID: " . $row['id'] . ")" ?></td>
            <td data-label="금액"><?= number_format($row['total_sales'], 2) ?></td>
            <td data-label="비율"><?= $percent ?>%</td>
            <td data-label="지갑주소"><?= htmlspecialchars($row['wallet_address'] ?? '') ?></td>
            <td data-label="코드페이주소"><?= htmlspecialchars($row['codepay_address'] ?? '') ?></td>
            <td data-label="상세보기">
                <a href="admin_detail.php?admin_id=<?= $row['id'] ?>&date=<?= $settle_date ?>">보기</a>
            </td>
        </tr>
        <?php endforeach; ?>

        <tr>
            <td><strong>Total</strong></td>
            <td><strong><?= number_format($admin_total, 2) ?></strong></td>
            <td>100%</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <hr>
<!-- 조직 정산 리스트 -->
<div style="display:flex; align-items:center; justify-content:space-between; margin-top:30px; gap:10px;">
    <h2 style="margin:0;">조직 정산 리스트</h2>
    <a
        href="group_accounts_download.php?date=<?= urlencode($settle_date) ?>"
        class="btn btn-outline"
        style="padding:10px 16px; border-radius:10px; border:2px solid #c0392b; color:#c0392b; font-weight:700; text-decoration:none; white-space:nowrap;"
    >목록 다운로드</a>
</div>

<form method="GET"
      class="form-inline"
      style="display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:20px;">

    <span>구분:</span>

    <select name="list_period"
            style="width:auto; min-width:120px;">
        <option value="daily" <?= $period=='daily'?'selected':'' ?>>일별</option>
        <option value="weekly" <?= $period=='weekly'?'selected':'' ?>>주별</option>
        <option value="monthly" <?= $period=='monthly'?'selected':'' ?>>월별</option>
    </select>

    <!-- 위쪽 데일리 날짜 유지 -->
    <input type="hidden" name="daily_date" value="<?= htmlspecialchars($settle_date) ?>">

    <button type="submit" class="btn btn-primary">조회</button>
</form>
    <table>
        <?php foreach ($group_data as $p => $rows): ?>
            <tr><th colspan="3"><?= htmlspecialchars($p) ?></th></tr>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td data-label="관리자"><?= htmlspecialchars($r['username'])." (ID: ".$r['id'].")" ?></td>
                <td data-label="금액">
                    <a href="admin_detail.php?admin_id=<?= $r['id'] ?>&period=<?= $p ?>">
                        <?= number_format($r['total_sales'], 2) ?>
                    </a>
                </td>
                <td data-label="상세보기">
                    <a href="admin_detail.php?admin_id=<?= $r['id'] ?>&period=<?= $p ?>">내역보기</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>
</section>

<script>
function confirmSettlement(e) {
    if (e) e.preventDefault();

    const isSettled = <?= $is_settled ? 'true' : 'false' ?>;
    if (isSettled) {
        alert('이미 정산 완료된 날짜입니다.');
        return;
    }

    if (!confirm("정산을 진행하시겠습니까?")) return;

    const settleDate = "<?= htmlspecialchars($settle_date) ?>";

    fetch('/settle_confirm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'settle_date=' + encodeURIComponent(settleDate)
    })
    .then(res => res.text().then(t => ({ok:res.ok, text:t})))
    .then(r => {
        if (!r.ok) throw new Error(r.text);
        alert(r.text);
        location.reload();
    })
    .catch(err => alert(err.message));
}
</script>
