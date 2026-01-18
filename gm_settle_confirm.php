<?php
session_start();
header('Content-Type: text/plain; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "로그인이 필요합니다.";
    exit;
}

include 'db_connect.php';

/* GM만 접근 (세션 role 없으면 DB에서 확인) */
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

/* 입력 */
$settle_date = $_POST['settle_date'] ?? '';
$dt = DateTime::createFromFormat('Y-m-d', $settle_date);
if (!$dt || $dt->format('Y-m-d') !== $settle_date) {
    http_response_code(400);
    echo "잘못된 날짜 형식입니다. (YYYY-MM-DD)";
    exit;
}

$region = 'default';
$user_id = (int)$_SESSION['user_id'];

/**
 * ✅ 파트너 정산의 gm_sales_daily 저장 기준
 * - sales_amount: 해당 날짜 dividend의 전체 합계(= 파트너 데일리 Total)
 * - sales_percentage: 100.00 (GM 테이블에 “그 날 총 매출” 기록 개념)
 *
 * 원하면 여기서 GM1+GM2+GM3만 저장하도록 바꿀 수도 있어.
 */
$sum_sql = "
    SELECT
        COALESCE(SUM(gm1_amount),0) +
        COALESCE(SUM(gm2_amount),0) +
        COALESCE(SUM(gm3_amount),0) +
        COALESCE(SUM(admin_amount),0) +
        COALESCE(SUM(mastr_amount),0) +
        COALESCE(SUM(agent_amount),0) +
        COALESCE(SUM(investor_amount),0) +
        COALESCE(SUM(referral_amount),0) AS total_amount
    FROM dividend
    WHERE DATE(tx_date) = ?
";

try {
    $conn->begin_transaction();

    /* ✅ 이미 정산 완료된 날짜면 차단 (서버 이중 방어) */
    $chk = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM gm_sales_daily
        WHERE sales_date = ?
          AND region = ?
          AND settled = 1
    ");
    $chk->bind_param("ss", $settle_date, $region);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!empty($row) && (int)$row['cnt'] > 0) {
        $conn->rollback();
        http_response_code(409);
        echo "이미 정산 완료된 날짜입니다: {$settle_date}";
        exit;
    }

    /* 1) 금액 계산 */
    $stmt = $conn->prepare($sum_sql);
    $stmt->bind_param("s", $settle_date);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $sales_amount = (float)($r['total_amount'] ?? 0);
    $sales_percentage = 100.00;

    /* 2) 같은 날짜/region 행이 있으면 UPDATE, 없으면 INSERT */
    $find = $conn->prepare("SELECT id FROM gm_sales_daily WHERE sales_date = ? AND region = ? LIMIT 1");
    $find->bind_param("ss", $settle_date, $region);
    $find->execute();
    $exist = $find->get_result()->fetch_assoc();
    $find->close();

    if ($exist && isset($exist['id'])) {
        $id = (int)$exist['id'];
        $up = $conn->prepare("
            UPDATE gm_sales_daily
            SET sales_amount = ?,
                user_id = ?,
                sales_percentage = ?,
                settled = 1,
                settled_at = NOW()
            WHERE id = ?
        ");
        $up->bind_param("didi", $sales_amount, $user_id, $sales_percentage, $id);
        $up->execute();
        $up->close();
    } else {
        $ins = $conn->prepare("
            INSERT INTO gm_sales_daily
                (region, sales_amount, sales_date, user_id, sales_percentage, settled, settled_at)
            VALUES
                (?, ?, ?, ?, ?, 1, NOW())
        ");
        $ins->bind_param("sdsid", $region, $sales_amount, $settle_date, $user_id, $sales_percentage);
        $ins->execute();
        $ins->close();
    }

    $conn->commit();

    echo "GM 정산 저장 완료\n- 날짜: {$settle_date}\n- 금액(Total): " . number_format($sales_amount, 2);

} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo "GM 정산 저장 실패: " . $e->getMessage();
}
