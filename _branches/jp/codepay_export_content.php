<?php
require_once __DIR__ . '/includes/i18n.php';

error_reporting(E_ALL);
// 운영 환경에서는 에러 화면 노출을 방지
ini_set('display_errors', (defined('DEBUG_MODE') && DEBUG_MODE) ? '1' : '0');

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
    echo "<div class='report-content'><p style='color:red'><?= htmlspecialchars(t('err.codepay_table_missing','CodePay table does not exist.')) ?></p></div>";
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
        <?= t('label.period', 'Period') ?>: <?= htmlspecialchars($start_date) ?> ~ <?= htmlspecialchars($end_date) ?>
    </p>

    <form method="POST" action="codepay_export.php" id="multiDownloadForm">
        <input type="hidden" name="action" value="bulk_download">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th style="width:50px; text-align:center;">
                        <input type="checkbox" id="checkAll" class="codepay-checkbox">
                    </th>
                    <th><?= t('table.dividend_id','Dividend ID') ?></th>
                    <th><?= t('table.settlement_date','정산일') ?></th>
                    <th class="text-end"><?= t('table.payout_count','Payout Count') ?></th>
                    <th class="text-end"><?= t('table.total_payout','Total Payout') ?></th>
                    <th style="width:100px;"><?= t('table.status','Status') ?></th>
                    <th style="width:140px;"><?= t('table.individual_download','Individual Download') ?></th>
                </tr>
            </thead>
            <tbody>

            <?php if (count($batches) === 0): ?>
                <tr>
                    <td colspan="7" style="text-align:center;"><?= t('msg.no_data','No data available.') ?></td>
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
                        <?php if ($done): ?>
                            <span class="badge bg-success"><?= htmlspecialchars(t('status.done','Done')) ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning"><?= htmlspecialchars(t('status.pending','Pending')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                            $bid = (int)($b['batch_id'] ?? 0);
                        ?>
                        <?php if (!$done && $bid > 0): ?>
                            <a href="codepay_export.php?download=1&batch_id=<?= $bid ?>"
                            class="btn btn-sm btn-success">
                                <?= t('common.download','Download') ?>
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
                <?= t('btn.download_selected','Download Selected') ?>
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
