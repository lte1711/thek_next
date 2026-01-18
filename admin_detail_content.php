<section class="content-area">
    <h2>조직 정산 상세 (GM 제외 전체)</h2>

    <div class="form-inline" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <div>
            Admin: <strong><?= htmlspecialchars($admin_name) ?></strong>
            (ID: <?= (int)$admin_id ?>) /
            기간: <strong><?= htmlspecialchars($title_period) ?></strong>
        </div>
        <div>
            <a href="javascript:history.back()">← 뒤로</a>
        </div>
    </div>

    <!-- ✅ 요약 -->
    <table style="margin-top:12px;">
        <tr>
            <th>Admin 합계</th>
            <th>Master 합계</th>
            <th>Agent 합계</th>
            <th>Investor 합계</th>
            <th>Referral 합계</th>
            <th><strong>GM 제외 총합</strong></th>
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
    <h3 style="margin-top:22px;">권한별 정산내역</h3>

    <?php
    $role_titles = [
        'admin'    => 'Admin 수혜자',
        'master'   => 'Master 수혜자',
        'agent'    => 'Agent 수혜자',
        'investor' => 'Investor 수혜자',
        'referral' => 'Referral 수혜자',
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
                <th>수혜자</th>
                <th>합계 (USDT)</th>
            </tr>
            <?php if (count($map) === 0): ?>
                <tr><td colspan="2" style="text-align:center; padding:12px;">표시할 내역이 없습니다.</td></tr>
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
                <th>아이디</th>
                <th>이름</th>
                <th>지갑주소</th>
                <th>코드페이주소</th>
                <th>정산금액</th>
            </tr>

            <?php if (count($map) === 0): ?>
                <tr><td colspan="5" style="text-align:center; padding:12px;">표시할 내역이 없습니다.</td></tr>
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
        다운로드
    </a>
</div>


    <!-- ✅ 거래별 분배 내역 -->
    <h3 style="margin-top:22px;">거래별 분배 내역 (입금자 기준)</h3>

    <table>
        <tr>
            <th>입금자 (ID/이름)</th>
            <th>Admin</th>
            <th>Master</th>
            <th>Agent</th>
            <th>Investor</th>
            <th>Referral</th>
            <th>합계(GM 제외)</th>
            <th>날짜</th>
        </tr>

        <?php if (count($rows) === 0): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:20px;">해당 기간에 표시할 내역이 없습니다.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $r): 
                $ex = (float)$r['admin_amount'] + (float)$r['mastr_amount'] + (float)$r['agent_amount'] + (float)$r['investor_amount'] + (float)$r['referral_amount'];
            ?>
                <tr>
                    <td data-label="입금자">
                        <?= htmlspecialchars($r['depositor']) ?> (ID: <?= (int)$r['depositor_id'] ?>)
                    </td>
                    <td data-label="Admin"><?= number_format((float)$r['admin_amount'], 2) ?></td>
                    <td data-label="Master"><?= number_format((float)$r['mastr_amount'], 2) ?></td>
                    <td data-label="Agent"><?= number_format((float)$r['agent_amount'], 2) ?></td>
                    <td data-label="Investor"><?= number_format((float)$r['investor_amount'], 2) ?></td>
                    <td data-label="Referral"><?= number_format((float)$r['referral_amount'], 2) ?></td>
                    <td data-label="합계(GM 제외)"><strong><?= number_format($ex, 2) ?></strong></td>
                    <td data-label="날짜"><?= htmlspecialchars($r['tx_date']) ?></td>
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
