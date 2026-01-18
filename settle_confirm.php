<?php
session_start();
$from_page = $_POST['from_page'] ?? '';
if ($from_page === 'partner_accounts_v2') {
    header('Content-Type: text/html; charset=UTF-8');
} else {
    header('Content-Type: text/plain; charset=UTF-8');
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "로그인이 필요합니다.";
    exit;
}

include 'db_connect.php';

// GM만 허용 (세션 role 없으면 DB에서 확인)
$is_gm = false;
if (isset($_SESSION['role'])) {
    $is_gm = ($_SESSION['role'] === 'gm');
} else {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $is_gm = (isset($r['role']) && $r['role'] === 'gm');
}
if (!$is_gm) {
    http_response_code(403);
    echo "GM만 정산 가능합니다.";
    exit;
}

// 입력
$settle_date = $_POST['settle_date'] ?? '';
$dt = DateTime::createFromFormat('Y-m-d', $settle_date);
if (!$dt || $dt->format('Y-m-d') !== $settle_date) {
    http_response_code(400);
    echo "잘못된 날짜 형식입니다. (YYYY-MM-DD)";
    exit;
}
/* ======================================================
   ✅ [여기에 추가] 이미 정산 완료된 날짜인지 서버에서 차단
   ====================================================== */
$chk = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM admin_sales_daily
    WHERE sales_date = ?
      AND settled = 1
");
$chk->bind_param("s", $settle_date);
$chk->execute();
$r = $chk->get_result()->fetch_assoc();
$chk->close();

if (!empty($r) && (int)$r['cnt'] > 0) {
    http_response_code(409); // Conflict
    echo "이미 정산 완료된 날짜입니다: {$settle_date}";
    exit;
}

/**
 * ✅ GM 제외 합계(조직 정산 합계)
 * admin_amount + mastr_amount + agent_amount + investor_amount + referral_amount
 */
$sum_ex_gm_expr = "
    (COALESCE(d.admin_amount,0)
   + COALESCE(d.mastr_amount,0)
   + COALESCE(d.agent_amount,0)
   + COALESCE(d.investor_amount,0)
   + COALESCE(d.referral_amount,0))
";

try {
    $conn->begin_transaction();

    // 1) 해당 날짜의 admin별 GM제외 합계 계산
    $sql = "
        SELECT
            u.id AS admin_id,
            u.username AS admin_username,
            COALESCE(SUM($sum_ex_gm_expr), 0) AS amount
        FROM users u
        LEFT JOIN dividend d
            ON d.admin_username = u.username
           AND DATE(d.tx_date) = ?
        WHERE u.role = 'admin'
        GROUP BY u.id, u.username
        ORDER BY u.id ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $settle_date);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $total_amount = 0.0;
    while ($row = $res->fetch_assoc()) {
        $amt = (float)$row['amount'];
        $rows[] = [
            'admin_id' => (int)$row['admin_id'],
            'amount'   => $amt,
        ];
        $total_amount += $amt;
    }
    $stmt->close();

    // 2) 저장 (admin_sales_daily)
    //    - 테이블에 아래 컬럼이 있어야 함:
    //      user_id, sales_amount, sales_percentage, sales_date, settled, settled_at
    //    - 그리고 (user_id, sales_date) UNIQUE 또는 PK가 있으면 upsert 가능
    $upsert = "
        INSERT INTO admin_sales_daily
            (user_id, sales_amount, sales_percentage, sales_date, settled, settled_at)
        VALUES
            (?, ?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            sales_amount = VALUES(sales_amount),
            sales_percentage = VALUES(sales_percentage),
            settled = 1,
            settled_at = NOW()
    ";
    $stmt2 = $conn->prepare($upsert);
    if (!$stmt2) {
        throw new Exception("admin_sales_daily upsert 준비 실패: " . $conn->error);
    }

    $saved_cnt = 0;
    foreach ($rows as $r) {
        $admin_id = $r['admin_id'];
        $amount   = $r['amount'];
        $percent  = ($total_amount > 0) ? round(($amount / $total_amount) * 100, 2) : 0.00;

        // i d d s  (int, double, double, string)
        $stmt2->bind_param("idds", $admin_id, $amount, $percent, $settle_date);
        $stmt2->execute();
        $saved_cnt++;
    }
    $stmt2->close();

    $conn->commit();

// ✅ day_seq: 현재 로직은 '이미 정산된 날짜 차단(409)'이라, 한 날짜당 1회만 정산됩니다.
// 따라서 ver2에서 요구한 "오늘 몇 번째(01/02)"는 항상 01이 됩니다.
// (여러 회차를 지원하려면 별도 로그 테이블이 필요 → DB 변경 승인 필요)
$day_seq = '01';

if (($from_page ?? '') === 'partner_accounts_v2') {
    $ym = substr($settle_date, 0, 7);
    echo "<script>
        alert('정산 완료되었습니다. (오늘 {$day_seq}번째)');
        window.location.href = 'Partner_accounts_v2.php?year_month={$ym}&settle_date={$settle_date}';
    </script>";
    exit;
}

echo "정산 저장 완료\n- 날짜: {$settle_date}\n- 저장건수(admin): {$saved_cnt}\n- 총액(GM 제외): " . number_format($total_amount, 2);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo "정산 저장 실패: " . $e->getMessage();
}
