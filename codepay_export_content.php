<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * 테이블 존재 여부 확인
 */
function table_exists(mysqli $conn, string $table): bool {
    $sql = "
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = $res && $res->num_rows > 0;
    $stmt->close();
    return $ok;
}

if (!table_exists($conn, 'codepay_payout_batches') || !table_exists($conn, 'codepay_payout_items')) {
    echo "<div class='report-content'><p style='color:red'>CodePay 테이블이 존재하지 않습니다.</p></div>";
    return;
}

/**
 * batch 기준 목록 조회
 */
$sql = "
    SELECT
        b.id AS batch_id,
        b.dividend_id,
        d.tx_date,
        COUNT(i.id) AS item_count,
        COALESCE(SUM(i.amount), 0) AS total_amount,
        SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) AS pending_count
    FROM codepay_payout_batches b
    JOIN dividend d ON d.id = b.dividend_id
    LEFT JOIN codepay_payout_items i ON i.batch_id = b.id
    WHERE d.tx_date BETWEEN ? AND ?
    GROUP BY b.id, b.dividend_id, d.tx_date
    ORDER BY d.tx_date DESC, b.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();
$batches = [];
while ($row = $res->fetch_assoc()) {
    $batches[] = $row;
}
$stmt->close();
?>

<style>
/* ===== 체크박스 크기 미세 조정 ===== */
.codepay-checkbox {
    width: 16px;
    height: 16px;
    transform: scale(0.9);
    cursor: pointer;
}

/* 버튼 기본 상태 */
#bulkBtn {
    display: none;
}
</style>

<div class="report-content">

    <p style="margin-bottom:10px;">
        기간: <?= htmlspecialchars($start_date) ?> ~ <?= htmlspecialchars($end_date) ?>
    </p>

    <form method="POST" action="codepay_export.php" id="multiDownloadForm">
        <input type="hidden" name="action" value="bulk_download">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th style="width:50px; text-align:center;">
                        <input type="checkbox" id="checkAll" class="codepay-checkbox">
                    </th>
                    <th>Dividend ID</th>
                    <th>정산일</th>
                    <th class="text-end">지급건수</th>
                    <th class="text-end">총 지급액</th>
                    <th style="width:100px;">상태</th>
                    <th style="width:140px;">개별 다운로드</th>
                </tr>
            </thead>
            <tbody>

            <?php if (count($batches) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">데이터 없음</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($batches as $b): ?>
                <?php
                    $done = ((int)$b['pending_count'] === 0);
                ?>
                <tr>
                    <td style="text-align:center;">
                        <?php if (!$done): ?>
                            <input type="checkbox"
                                   name="batch_ids[]"
                                   value="<?= $b['batch_id'] ?>"
                                   class="codepay-checkbox">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>

                    <td><?= $b['dividend_id'] ?></td>
                    <td><?= htmlspecialchars($b['tx_date']) ?></td>
                    <td class="text-end"><?= number_format($b['item_count']) ?></td>
                    <td class="text-end"><?= number_format($b['total_amount'], 2) ?></td>
                    <td>
                        <?= $done ? '<span class="badge bg-success">완료</span>' : '<span class="badge bg-warning">대기중</span>' ?>
                    </td>
                    <td>
                        <?php
                            $bid = (int)($b['batch_id'] ?? 0);
                        ?>
                        <?php if (!$done && $bid > 0): ?>
                            <a href="codepay_export.php?download=1&batch_id=<?= $bid ?>"
                            class="btn btn-sm btn-success">
                                다운로드
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>                
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

        <div style="margin-top:15px; text-align:right;">
            <button type="submit"
                    id="bulkBtn"
                    class="btn btn-success">
                선택 다운로드
            </button>
        </div>

    </form>
</div>

<script>
(function(){
    const bulkBtn = document.getElementById('bulkBtn');
    const checkAll = document.getElementById('checkAll');
    const boxes = document.querySelectorAll('input[name="batch_ids[]"]');

    function toggleBulkButton() {
        const anyChecked = Array.from(boxes).some(cb => cb.checked);
        bulkBtn.style.display = anyChecked ? 'inline-block' : 'none';
    }

    boxes.forEach(cb => cb.addEventListener('change', toggleBulkButton));

    if (checkAll) {
        checkAll.addEventListener('change', function(){
            boxes.forEach(cb => cb.checked = this.checked);
            toggleBulkButton();
        });
    }
})();
</script>
