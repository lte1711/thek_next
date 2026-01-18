<?php
// referral_settlement_content.php
// 요구사항:
//  - 기존 레이아웃(layout.php) 유지
//  - 로그인한 회원의 추천인(하위) 투자자들의 배당금 기준으로 정산(5%) 표시
//  - 기본 조회 날짜: 어제
//  - 날짜 선택 시 해당 날짜 기준 추천인 리스트 표시

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$login_id = (int)($_SESSION['user_id'] ?? 0);
if ($login_id <= 0) {
    echo "<p>로그인이 필요합니다.</p>";
    return;
}

// 기본 날짜: 어제
$default_date = date('Y-m-d', strtotime('-1 day'));
$selected_date = $_GET['date'] ?? $default_date;

// 날짜 검증(YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = $default_date;
}

// 추천정산: 하위(추천인 트리) 투자자들의 배당금 합계
// users.referrer_id 를 기준으로 하위 트리 검색 (MySQL 8+)

$sql = "WITH RECURSIVE downline AS (
            SELECT id
            FROM users
            WHERE id = ?
            UNION ALL
            SELECT u.id
            FROM users u
            INNER JOIN downline d ON u.referrer_id = d.id
        )
        SELECT
            u.id AS referral_id,
            u.username AS referral_username,
            COALESCE(SUM(dv.investor_amount), 0) / 0.25 AS total_profit,
            COALESCE(SUM(dv.investor_amount), 0) * 0.2 AS settle_amount
        FROM downline dl
        INNER JOIN users u ON u.id = dl.id
        LEFT JOIN dividend dv
            ON dv.user_id = u.id
           AND DATE(dv.tx_date) = ?
        WHERE u.role = 'investor'
          AND u.id <> ?
        GROUP BY u.id, u.username
        HAVING total_profit > 0
        ORDER BY total_profit DESC, u.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'isi', $login_id, $selected_date, $login_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($r = mysqli_fetch_assoc($result)) {
    $rows[] = $r;
}
mysqli_stmt_close($stmt);

// 합계
$sum_profit = 0.0;
$sum_settle = 0.0;
foreach ($rows as $r) {
    $sum_profit += (float)$r['total_profit'];
    $sum_settle += (float)$r['settle_amount'];
}
?>

<div class="card" style="padding:16px;">
    <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div>
            <div style="font-weight:700; font-size:18px;">추천정산</div>
            <div style="color:#666; font-size:12px; margin-top:4px;">정산 기준일: <strong><?= htmlspecialchars($selected_date) ?></strong> (기본: 어제)</div>
        </div>

        <form method="get" style="margin-left:auto; display:flex; gap:8px; align-items:flex-end;">
            <div>
                <label style="display:block; font-size:12px; color:#666; margin-bottom:4px;">날짜 선택</label>
                <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" style="padding:8px;">
            </div>
            <button type="submit" class="btn" style="padding:9px 12px;">조회</button>
        </form>
    </div>

    <div style="margin-top:14px; overflow:auto;">
        <table class="table" style="width:100%; min-width:680px;">
            <thead>
                <tr>
                    <th style="white-space:nowrap;">정산날짜</th>
                    <th style="white-space:nowrap;">추천아이디</th>
                    <th style="white-space:nowrap;">총수익(배당금)</th>
                    <th style="white-space:nowrap;">정산금액(5%)</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:18px; color:#777;">
                        해당 날짜에 정산할 추천인 수익 데이터가 없습니다.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($selected_date) ?></td>
                        <td>#<?= (int)$r['referral_id'] ?> (<?= htmlspecialchars($r['referral_username']) ?>)</td>
                        <td><?= number_format((float)$r['total_profit'], 2) ?></td>
                        <td><strong><?= number_format((float)$r['settle_amount'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2"><strong>합계</strong></td>
                    <td><strong><?= number_format($sum_profit, 2) ?></strong></td>
                    <td><strong><?= number_format($sum_settle, 2) ?></strong></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<!--     <div style="margin-top:10px; font-size:12px; color:#777; line-height:1.5;">
        • 총수익(배당금)은 <code>dividend</code> 테이블의 <code>investor_amount</code> 합계 기준입니다.<br>
        • 정산금액은 총수익의 <strong>5%</strong>로 계산됩니다.<br>
        • 추천인 범위는 <code>users.referrer_id</code> 기준으로 로그인 회원 하위(직/간접) 투자자 전체입니다.
    </div>
 --></div>
