<section class="content-area">
    <!-- 데일리 정산 -->
    <h2>파트너 데일리 정산</h2>

    <form method="GET" class="form-inline">
        <label>날짜 선택:</label>
        <input type="date" name="settle_date" value="<?= htmlspecialchars($settle_date) ?>">
        <button type="submit">조회</button>

        <?php if (!empty($is_settled)): ?>
            <button type="button" disabled style="opacity:0.6; cursor:not-allowed;">정산완료</button>
            <span style="font-size:12px; opacity:0.8;">
                <?= !empty($settled_at) ? "(정산시각: " . htmlspecialchars($settled_at) . ")" : "" ?>
            </span>
        <?php else: ?>
            <button type="button" onclick="confirmSettlement(event)">정산</button>
        <?php endif; ?>
    </form>

    <table id="dailyTable">
        <tr><th>역할</th><th>이름</th><th>USDT</th><th>비율</th></tr>

        <?php
        // ✅ 요청사항
        // - 실비율(회색) 제거
        // - GM 고정 수익배율만 표시(회색)
        foreach ($role_data as $label => $amount):
            $gm_name = $gm_name_map[$label] ?? '';
            $fixed_ratio = $gm_profit_ratio_map[$label] ?? 0;
        ?>
            <tr>
                <td data-label="역할"><?= htmlspecialchars($label) ?></td>
                <td data-label="이름"><?= htmlspecialchars($gm_name) ?></td>
                <td data-label="USDT">₩<?= number_format((float)$amount, 2) ?></td>
                <td data-label="비율">
                    <span style="color:#555; font-weight:700;"><?= (int)$fixed_ratio ?>%</span>
                </td>
            </tr>
        <?php endforeach; ?>

        <tr>
            <td><strong>Total</strong></td>
            <td></td>
            <td><strong>₩<?= number_format((float)$total_usdt, 2) ?></strong></td>
            <td>
                <strong style="color:#555;"><?= (int)($total_profit_ratio ?? 0) ?>%</strong>
            </td>
        </tr>
    </table>

    <!-- 월별 정산 -->
    <h2>파트너 월별 정산 리스트</h2>
    <form method="GET" class="form-inline">
        <label>월 선택:</label>
        <input type="month" name="year_month" value="<?= htmlspecialchars($year_month) ?>">
        <button type="submit">조회</button>

        <!-- 데일리 날짜 유지 -->
        <input type="hidden" name="settle_date" value="<?= htmlspecialchars($settle_date) ?>">
    </form>

    <table id="monthlyTable">
        <tr>
            <th>Date</th>
            <?php foreach(array_keys($role_data) as $role): ?>
                <?php $hname = $gm_name_map[$role] ?? ''; ?>
                <th><?= htmlspecialchars($role . ($hname ? " ($hname)" : "")) ?></th>
            <?php endforeach; ?>
            <th>Total</th>
        </tr>

        <?php foreach($data as $date => $row): ?>
            <tr>
                <td data-label="Date"><?= htmlspecialchars($date) ?></td>
                <?php foreach(array_keys($role_data) as $role): ?>
                    <td data-label="<?= htmlspecialchars($role) ?>">₩<?= number_format((float)($row[$role] ?? 0), 2) ?></td>
                <?php endforeach; ?>
                <td data-label="Total"><strong>₩<?= number_format((float)$row['Total'], 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>

<script>
function confirmSettlement(e) {
    if (e) e.preventDefault();

    const isSettled = <?= !empty($is_settled) ? 'true' : 'false' ?>;
    if (isSettled) {
        alert('이미 정산 완료된 날짜입니다.');
        return;
    }

    if (!confirm("정산을 진행하시겠습니까?")) return;

    const settleDate = "<?= htmlspecialchars($settle_date) ?>";

    // ✅ GM 정산 저장 호출
    fetch('gm_settle_confirm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'settle_date=' + encodeURIComponent(settleDate)
    })
    .then(async (res) => {
        const text = await res.text();
        if (!res.ok) throw new Error(text || '정산 실패');
        alert(text);
        location.reload(); // 저장 후 화면 갱신 → 버튼 비활성화 반영
    })
    .catch((err) => {
        alert(err.message);
    });
}
</script>
