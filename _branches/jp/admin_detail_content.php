<section class="content-area">
    <h2><?= t('org_detail.title', 'Organization Settlement Detail (Excl. GM Total)') ?></h2>

    <div class="form-inline" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div>
            Admin: <strong><?= htmlspecialchars($admin_name) ?></strong>
            (ID: <?= (int)$admin_id ?>) /
            <?= t('org_detail.period', 'Period') ?>: <strong><?= htmlspecialchars($title_period) ?></strong>
        </div>
        <div>
            <a href="javascript:history.back()">← <?= t('common.prev', 'Back') ?></a>
        </div>
    </div>

    <!-- ✅ 요약 -->
    <table style="margin-top:12px;">
        <tr>
            <th><?= t('org_detail.summary.admin_total','Admin Total') ?></th>
            <th><?= t('org_detail.summary.master_total','Master Total') ?></th>
            <th><?= t('org_detail.summary.agent_total','Agent Total') ?></th>
            <th><?= t('org_detail.summary.investor_total','Investor Total') ?></th>
            <th><?= t('org_detail.summary.referral_total','Referral Total') ?></th>
            <th><strong><?= t('org_detail.summary.ex_gm_total','Total (Excl. GM)') ?></strong></th>
        </tr>
        <tr>
            <td><?= number_format((float)$totals['admin'], 2) ?></td>
            <td><?= number_format((float)$totals['master'], 2) ?></td>
            <td><?= number_format((float)$totals['agent'], 2) ?></td>
            <td><?= number_format((float)$totals['investor'], 2) ?></td>
            <td><?= number_format((float)$totals['referral'], 2) ?></td>
            <td><strong><?= number_format((float)$totals['ex_gm'], 2) ?></strong></td>
        </tr>
    </table>

    <!-- ✅ 역할별 수혜자 합계 -->
    <h3 style="margin-top:22px;"><?= t('org_detail.by_role_title','Settlement Details by Role') ?></h3>

    <?php
    $role_titles = [
        'admin'    => t('org_detail.recipient.admin', 'Admin Recipients'),
'master'   => t('org_detail.recipient.master', 'Master Recipients'),
'agent'    => t('org_detail.recipient.agent', 'Agent Recipients'),
'investor' => t('org_detail.recipient.investor', 'Investor Recipients'),
'referral' => t('org_detail.recipient.referral', 'Referral Recipients'),
];
    ?>

    
<?php
// user_details 컬럼 체크 (지갑주소/코드페이주소)
$ud_cols = [];
$udRes = $conn->query("SHOW COLUMNS FROM user_details");
while ($c = $udRes->fetch_assoc()) { $ud_cols[$c['Field']] = true; }
$wallet_col = null;
foreach (['wallet_address','usdt_wallet_address','usdt_address','wallet'] as $cand) {
    if (isset($ud_cols[$cand])) { $wallet_col = $cand; break; }
}
$codepay_col = isset($ud_cols['codepay_address']) ? 'codepay_address' : (isset($ud_cols['codepay']) ? 'codepay' : null);

function fetch_user_detail_map($conn, $usernames, $wallet_col, $codepay_col) {
    $out = [];
    if (count($usernames) === 0) return $out;
    $placeholders = implode(',', array_fill(0, count($usernames), '?'));

    $sql = "SELECT u.id, u.username";
    if ($wallet_col) $sql .= ", ud.`$wallet_col` AS wallet_address"; else $sql .= ", NULL AS wallet_address";
    if ($codepay_col) $sql .= ", ud.`$codepay_col` AS codepay_address"; else $sql .= ", NULL AS codepay_address";
    $sql .= " FROM users u LEFT JOIN user_details ud ON ud.user_id = u.id WHERE u.username IN ($placeholders)";

    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($usernames));
    $stmt->bind_param($types, ...$usernames);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $out[$r['username']] = [
            'id' => (int)$r['id'],
            'name' => $r['username'], // 이름 컬럼이 없는 환경 대비(필요 시 DB 컬럼 맞춰 변경)
            'wallet' => $r['wallet_address'] ?? '',
            'codepay' => $r['codepay_address'] ?? ''
        ];
    }
    $stmt->close();
    return $out;
}
?>

<?php foreach ($by_role as $role => $map): ?>
    <?php if ($role === 'investor') continue; ?>
    <h4 style="margin-top:16px;"><?= $role_titles[$role] ?></h4>

    <?php
    $usernames = array_keys($map);
    $detail_map = fetch_user_detail_map($conn, $usernames, $wallet_col, $codepay_col);
    ?>

    <?php if ($role === 'investor'): ?>
        <!-- Investor는 이미지처럼 간단 표기 -->
        <table>
            <tr>
                <th><?= t('org_detail.recipient','Recipient') ?></th>
                <th><?= t('org_detail.total_usdt','Total (USDT)') ?></th>
            </tr>
            <?php if (count($map) === 0): ?>
                <tr><td colspan="2" style="text-align:center; padding:12px;"><?= t('common.no_data','No records to display.') ?></td></tr>
            <?php else: ?>
                <?php foreach ($map as $username => $amt): ?>
                    <tr>
                        <td><?= htmlspecialchars($username) ?></td>
                        <td><?= number_format((float)$amt, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    <?php else: ?>
        <!-- Admin/Master/Agent/Referral: 상세 컬럼 표기 -->
        <table>
            <tr>
                <th><?= t('common.id','ID') ?></th>
                <th><?= t('common.name','Name') ?></th>
                <th><?= t('field.usdt_wallet','USDT Wallet') ?></th>
                <th><?= t('th.codepay','CodePay Address') ?></th>
                <th><?= t('org_detail.settle_amount','Settlement Amount') ?></th>
            </tr>

            <?php if (count($map) === 0): ?>
                <tr><td colspan="5" style="text-align:center; padding:12px;"><?= t('common.no_data','No records to display.') ?></td></tr>
            <?php else: ?>
                <?php foreach ($map as $username => $amt): 
                    $d = $detail_map[$username] ?? ['id'=>'','name'=>$username,'wallet'=>'','codepay'=>''];
                ?>
                    <tr>
                        <td><?= (int)($d['id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($d['name'] ?? $username) ?></td>
                        <td style="font-size:12px; word-break:break-all;"><?= htmlspecialchars($d['wallet'] ?? '') ?></td>
                        <td style="font-size:12px; word-break:break-all;"><?= htmlspecialchars($d['codepay'] ?? '') ?></td>
                        <td><?= number_format((float)$amt, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    <?php endif; ?>

<?php endforeach; ?>

<!-- 권한별 정산내역 다운로드 -->
<div style="display:flex; justify-content:flex-end; margin-top:10px;">
    <a class="btn" href="admin_detail.php?admin_id=<?= (int)$admin_id ?>&period=<?= urlencode($period ?: '') ?>&date=<?= urlencode($date ?: '') ?>&download=1"
    style="padding:10px 16px; border:1px solid #222; border-radius:8px; background:#222; color:#fff; text-decoration:none;">
        <?= t('common.download','Download') ?>
    </a>
</div>


    <!-- ✅ 거래별 분배 내역 -->
    <h3 style="margin-top:22px;"><?= t('org_detail.tx_dist_title','Distribution by Transaction (Based on Depositor)') ?></h3>

    <table>
        <tr>
            <th><?= t('org_detail.depositor','Depositor (ID/Name)') ?></th>
            <th><?= t('org_detail.role.admin','Admin') ?></th>
            <th><?= t('org_detail.role.master','Master') ?></th>
            <th><?= t('org_detail.role.agent','Agent') ?></th>
            <th><?= t('org_detail.role.investor','Investor') ?></th>
            <th><?= t('org_detail.role.referral','Referral') ?></th>
            <th><?= t('org_detail.total_ex_gm','Total (Excl. GM)') ?></th>
            <th><?= t('common.date','Date') ?></th>
        </tr>

        <?php if (count($rows) === 0): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px;"><?= t('common.no_data','No records for the selected period.') ?></td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $r): 
                $ex = (float)$r['admin_amount'] + (float)$r['mastr_amount'] + (float)$r['agent_amount'] + (float)$r['investor_amount'] + (float)$r['referral_amount'];
            ?>
                <tr>
                    <td data-label="<?= t('org_detail.depositor_short', 'Depositor') ?>">
                        <?= htmlspecialchars($r['depositor']) ?> (ID: <?= (int)$r['depositor_id'] ?>)
                    </td>
                    <td data-label="Admin"><?= number_format((float)$r['admin_amount'], 2) ?></td>
                    <td data-label="Master"><?= number_format((float)$r['mastr_amount'], 2) ?></td>
                    <td data-label="Agent"><?= number_format((float)$r['agent_amount'], 2) ?></td>
                    <td data-label="Investor"><?= number_format((float)$r['investor_amount'], 2) ?></td>
                    <td data-label="Referral"><?= number_format((float)$r['referral_amount'], 2) ?></td>
                    <td data-label="<?= t('org_detail.total_ex_gm', 'Total (Excl. GM)') ?>"><strong><?= number_format($ex, 2) ?></strong></td>
                    <td data-label="<?= t('common.date', 'Date') ?>"><?= htmlspecialchars($r['tx_date']) ?></td>
                </tr>
            <?php endforeach; ?>

            <tr>
                <td><strong>Total</strong></td>
                <td><strong><?= number_format((float)$totals['admin'], 2) ?></strong></td>
                <td><strong><?= number_format((float)$totals['master'], 2) ?></strong></td>
                <td><strong><?= number_format((float)$totals['agent'], 2) ?></strong></td>
                <td><strong><?= number_format((float)$totals['investor'], 2) ?></strong></td>
                <td><strong><?= number_format((float)$totals['referral'], 2) ?></strong></td>
                <td><strong><?= number_format((float)$totals['ex_gm'], 2) ?></strong></td>
                <td></td>
            </tr>
        <?php endif; ?>
    </table>
</section>
