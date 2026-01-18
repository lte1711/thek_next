<?php
// 강력한 에러 확인
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // DB 연결 (실제 비밀번호로 교체하십시오)
    $mysqli = new mysqli("localhost", "thek_db_admin", "thek_pw_admin!", "thek_next_db");
    $mysqli->set_charset("utf8mb4");

    // 월별 보너스 집계 쿼리 수정 (이미 수정 완료됨)
    $bonus_sql = "
        SELECT 
            YEAR(created_at) AS year,
            MONTH(created_at) AS month,
            SUM(investor_amount) AS investor_total,
            SUM(company_malaysia_amount) AS malaysia_total,
            SUM(company_thek_amount) AS thek_total,
            SUM(company_vietnam_amount) AS vietnam_total,
            SUM(admin_amount) AS admin_total,
            SUM(master_amount) AS master_total,
            SUM(agent_amount) AS agent_total,
            SUM(referrer_amount) AS referrer_total
        FROM bonus_distribution_log
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY YEAR(created_at), MONTH(created_at)
    ";
    $bonus_result = $mysqli->query($bonus_sql);

    // 공통: 월별 sales 집계 함수 (수정된 부분)
    function getMonthlySales(mysqli $mysqli, string $table): mysqli_result {
// 수정된 코드의 핵심 부분
$sql = "
    SELECT 
        YEAR(sales_date) AS year,
        MONTH(sales_date) AS month,
        AVG(sales_percentage) AS avg_sales
    FROM {$table}
    GROUP BY YEAR(sales_date), MONTH(sales_date)
    -- 이 부분이 수정되었습니다:
    ORDER BY YEAR(sales_date), MONTH(sales_date) 
";
        return $mysqli->query($sql);
    }
    // 이 함수가 호출되는 라인 근처(원래 코드의 62번째 줄)에서 오류가 발생했습니다.

    // 각 Sales 테이블 조회
    $investor_sales = getMonthlySales($mysqli, "investor_sales_daily");
    $gm_sales       = getMonthlySales($mysqli, "gm_sales_daily");
    $admin_sales    = getMonthlySales($mysqli, "admin_sales_daily");
    $master_sales   = getMonthlySales($mysqli, "master_sales_daily");
    $agent_sales    = getMonthlySales($mysqli, "agent_sales_daily");

} catch (mysqli_sql_exception $e) {
    // DB 연결 오류 또는 쿼리 실행 오류 발생 시 처리
    http_response_code(500);
    echo "DB 오류: " . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>월별 보너스 및 Sales 리포트</title>
    <style>

h2 { margin-top: 36px; }
    
</style>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <h1>월별 보너스 및 Sales 리포트</h1>

    <h2>보너스 분배 로그</h2>
    <table>
        <tr>
            <th>년도</th><th>월</th>
            <th>Investor</th><th>Malaysia</th><th>Thek</th><th>Vietnam</th>
            <th>Admin</th><th>Master</th><th>Agent</th><th>Referrer</th>
        </tr>
        <?php while ($row = $bonus_result->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['year'] ?></td>
            <td><?= (int)$row['month'] ?></td>
            <td><?= number_format((float)$row['investor_total'], 2) ?></td>
            <td><?= number_format((float)$row['malaysia_total'], 2) ?></td>
            <td><?= number_format((float)$row['thek_total'], 2) ?></td>
            <td><?= number_format((float)$row['vietnam_total'], 2) ?></td>
            <td><?= number_format((float)$row['admin_total'], 2) ?></td>
            <td><?= number_format((float)$row['master_total'], 2) ?></td>
            <td><?= number_format((float)$row['agent_total'], 2) ?></td>
            <td><?= number_format((float)$row['referrer_total'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $bonus_result->free(); ?>

    <h2>Investor Sales</h2>
    <table>
        <tr><th>년도</th><th>월</th><th>평균 Sales %</th></tr>
        <?php while ($row = $investor_sales->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['year'] ?></td>
            <td><?= (int)$row['month'] ?></td>
            <td><?= number_format((float)$row['avg_sales'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $investor_sales->free(); ?>

    <h2>global_masterSales</h2>
    <table>
        <tr><th>년도</th><th>월</th><th>평균 Sales %</th></tr>
        <?php while ($row = $gm_sales->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['year'] ?></td>
            <td><?= (int)$row['month'] ?></td>
            <td><?= number_format((float)$row['avg_sales'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $gm_sales->free(); ?>

    <h2>Admin Sales</h2>
    <table>
        <tr><th>년도</th><th>월</th><th>평균 Sales %</th></tr>
        <?php while ($row = $admin_sales->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['year'] ?></td>
            <td><?= (int)$row['month'] ?></td>
            <td><?= number_format((float)$row['avg_sales'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $admin_sales->free(); ?>

    <h2>Master Sales</h2>
    <table>
        <tr><th>년도</th><th>월</th><th>평균 Sales %</th></tr>
        <?php while ($row = $master_sales->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['year'] ?></td>
            <td><?= (int)$row['month'] ?></td>
            <td><?= number_format((float)$row['avg_sales'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $master_sales->free(); ?>

    <h2>Agent Sales</h2>
    <table>
        <tr><th>년도</th><th>월</th><th>평균 Sales %</th></tr>
        <?php while ($row = $agent_sales->fetch_assoc()): ?>
        <tr>
            <td><?= (int)$row['year'] ?></td>
            <td><?= (int)$row['month'] ?></td>
            <td><?= number_format((float)$row['avg_sales'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php $agent_sales->free(); ?>

</body>
</html>
<?php
// DB 연결 종료
$mysqli->close();
?>
