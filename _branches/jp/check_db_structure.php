<?php
session_start();

// Safe initialization
if (!function_exists('t')) {
    $i18n_path = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n_path)) {
        require_once $i18n_path;
    } else {
        function t($key, $fallback = null) {
            return $fallback ?? $key;
        }
    }
}

if (!isset($_SESSION['user_id'])) {
    die("로그인이 필요합니다.");
}

include 'db_connect.php';

// GM 권한 확인
$is_gm = false;
if (isset($_SESSION['role'])) {
    $is_gm = ($_SESSION['role'] === 'gm');
}

if (!$is_gm) {
    die("GM 권한이 필요합니다.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>테이블 구조 확인</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        h2 { color: #333; margin-top: 30px; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>

<h1>📊 데이터베이스 테이블 구조 확인</h1>

<div class="info">
    <strong>현재 사용자:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'N/A') ?><br>
    <strong>권한:</strong> <?= htmlspecialchars($_SESSION['role'] ?? 'N/A') ?>
</div>

<h2>1️⃣ admin_sales_daily 테이블 구조</h2>

<?php
$result = $conn->query("DESCRIBE admin_sales_daily");
if ($result) {
    echo "<table>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>테이블을 찾을 수 없습니다.</p>";
}
?>

<h2>2️⃣ admin_sales_daily 샘플 데이터 (최근 5개)</h2>

<?php
$result = $conn->query("SELECT * FROM admin_sales_daily ORDER BY sales_date DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    
    // 헤더
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>데이터가 없습니다.</p>";
}
?>

<h2>3️⃣ dividend 테이블 구조</h2>

<?php
$result = $conn->query("DESCRIBE dividend");
if ($result) {
    echo "<table>";
    echo "<tr><th>컬럼명</th><th>타입</th><th>NULL</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>테이블을 찾을 수 없습니다.</p>";
}
?>

<h2>4️⃣ dividend 샘플 데이터 (최근 5개)</h2>

<?php
$result = $conn->query("SELECT * FROM dividend ORDER BY tx_date DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table style='font-size: 11px;'>";
    
    // 헤더
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>데이터가 없습니다.</p>";
}
?>

<h2>5️⃣ 모든 테이블 목록</h2>

<?php
$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "<table>";
    echo "<tr><th>테이블명</th></tr>";
    while ($row = $result->fetch_array()) {
        echo "<tr><td>" . htmlspecialchars($row[0]) . "</td></tr>";
    }
    echo "</table>";
}
?>

<div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 5px;">
    <h3>💡 다음 단계</h3>
    <ol>
        <li>위의 <strong>admin_sales_daily 테이블 구조</strong>를 확인하세요.</li>
        <li>어떤 컬럼들이 실제로 있는지 확인하세요.</li>
        <li>이 정보를 바탕으로 settle_confirm.php의 INSERT 문을 수정해야 합니다.</li>
    </ol>
</div>

<div style="margin-top: 20px;">
    <a href="Partner_accounts_v2.php" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
        ← Partner Accounts로 돌아가기
    </a>
</div>

</body>
</html>