<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// GM만 접근
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gm') {
    http_response_code(403);
    exit('GM만 접근 가능합니다.');
}

include 'db_connect.php';

$admin_id = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
$date     = $_GET['date'] ?? null;    // YYYY-MM-DD
$period   = $_GET['period'] ?? null;  // YYYY-MM or YEARWEEK(6) or (YYYY-MM-DD가 들어올 수도 있음)

// admin_id 체크
if ($admin_id <= 0) {
    http_response_code(400);
    exit("잘못된 요청입니다. (admin_id 누락)");
}

// ✅ period=YYYY-MM-DD → date로 흡수
if (!$date && $period && preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
    $date = $period;
    $period = null;
}

// admin username 조회
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND role='admin' LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    http_response_code(404);
    exit("관리자 정보를 찾을 수 없습니다.");
}

$admin_name = $res['username'];

// 기간 조건 구성
$whereSql = "";
$bindTypes = "s";
$bindVals = [$admin_name];
$title_period = "";

if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $whereSql = " AND DATE(d.tx_date) = ? ";
    $bindTypes .= "s";
    $bindVals[] = $date;
    $title_period = $date;

} elseif ($period && preg_match('/^\d{4}-\d{2}$/', $period)) {
    $whereSql = " AND DATE_FORMAT(d.tx_date, '%Y-%m') = ? ";
    $bindTypes .= "s";
    $bindVals[] = $period;
    $title_period = $period;

} elseif ($period && preg_match('/^\d{6}$/', $period)) {
    // ✅ group_accounts.php와 동일 ISO 주차
    $whereSql = " AND YEARWEEK(d.tx_date, 3) = ? ";
    $bindTypes .= "i";
    $bindVals[] = (int)$period;
    $title_period = $period;

} else {
    http_response_code(400);
    exit("잘못된 period 형식입니다. (date=YYYY-MM-DD 또는 period=YYYY-MM 또는 YEARWEEK 6자리)");
}

/**
 * ✅ 핵심: 해당 admin 조직에서 발생한 GM 제외 분배 내역 전체
 * - 조건 키는 d.admin_username = (선택 admin)
 * - 각 행에 admin/master/agent/investor/referral 금액이 함께 있음
 */
$sql = "
    SELECT
        u.id AS depositor_id,
        u.username AS depositor,
        d.tx_date,

        d.admin_username,
        d.admin_amount,

        d.mastr_username,
        d.mastr_amount,

        d.agent_username,
        d.agent_amount,

        d.investor_username,
        d.investor_amount,

        d.referral_username,
        d.referral_amount
    FROM dividend d
    JOIN users u ON d.user_id = u.id
    WHERE d.admin_username = ?
    $whereSql
    ORDER BY d.tx_date ASC, d.id ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("DB Prepare Error: " . htmlspecialchars($conn->error));
}

// 가변 바인딩
$refs = [];
$refs[] = $bindTypes;
for ($i = 0; $i < count($bindVals); $i++) $refs[] = &$bindVals[$i];
call_user_func_array([$stmt, 'bind_param'], $refs);

$stmt->execute();
$detail_res = $stmt->get_result();
$stmt->close();

$rows = [];
$totals = [
    'admin'    => 0.0,
    'master'   => 0.0,
    'agent'    => 0.0,
    'investor' => 0.0,
    'referral' => 0.0,
    'ex_gm'    => 0.0,
];

// 역할별 수혜자 집계(누가 얼마 받았는지)
$by_role = [
    'admin'    => [], // username => sum
    'master'   => [],
    'agent'    => [],
    'investor' => [],
    'referral' => [],
];

if ($detail_res) {
    while ($r = $detail_res->fetch_assoc()) {
        // 금액 float 변환
        $a  = (float)$r['admin_amount'];
        $m  = (float)$r['mastr_amount'];
        $ag = (float)$r['agent_amount'];
        $iv = (float)$r['investor_amount'];
        $rf = (float)$r['referral_amount'];

        $r['admin_amount']    = $a;
        $r['mastr_amount']    = $m;
        $r['agent_amount']    = $ag;
        $r['investor_amount'] = $iv;
        $r['referral_amount'] = $rf;

        $rows[] = $r;

        // 총합
        $totals['admin']    += $a;
        $totals['master']   += $m;
        $totals['agent']    += $ag;
        $totals['investor'] += $iv;
        $totals['referral'] += $rf;
        $totals['ex_gm']     += ($a + $m + $ag + $iv + $rf);

        // 역할별 수혜자별 합산
        if (!empty($r['admin_username']))    $by_role['admin'][$r['admin_username']]       = ($by_role['admin'][$r['admin_username']] ?? 0) + $a;
        if (!empty($r['mastr_username']))    $by_role['master'][$r['mastr_username']]      = ($by_role['master'][$r['mastr_username']] ?? 0) + $m;
        if (!empty($r['agent_username']))    $by_role['agent'][$r['agent_username']]       = ($by_role['agent'][$r['agent_username']] ?? 0) + $ag;
        if (!empty($r['investor_username'])) $by_role['investor'][$r['investor_username']] = ($by_role['investor'][$r['investor_username']] ?? 0) + $iv;
        if (!empty($r['referral_username'])) $by_role['referral'][$r['referral_username']] = ($by_role['referral'][$r['referral_username']] ?? 0) + $rf;
    }
}

// 내림차순 정렬(많이 받은 순)
foreach ($by_role as $k => $arr) {
    arsort($arr);
    $by_role[$k] = $arr;
}


// =====================
// CSV Download (권한별 정산내역)
// =====================
if (isset($_GET['download']) && $_GET['download'] == '1') {

    
    // ✅ 다운로드에서 investor(투자자) 라인 제외 (화면과 동일)
    if (isset($by_role['investor'])) { unset($by_role['investor']); }
// user_details 컬럼 존재 여부 체크 (환경마다 컬럼명이 달라질 수 있음)
    $cols = [];
    $colRes = $conn->query("SHOW COLUMNS FROM user_details");
    while ($c = $colRes->fetch_assoc()) {
        $cols[$c['Field']] = true;
    }
    $wallet_col = null;
    foreach (['wallet_address','usdt_wallet_address','usdt_address','wallet'] as $cand) {
        if (isset($cols[$cand])) { $wallet_col = $cand; break; }
    }
    $codepay_col = isset($cols['codepay_address']) ? 'codepay_address' : (isset($cols['codepay']) ? 'codepay' : null);

    // username 목록 수집
    $all_usernames = [];
    foreach ($by_role as $role => $arr) {
        
        if ($role === 'investor') { continue; }
foreach (array_keys($arr) as $un) {
            if (!empty($un)) $all_usernames[$un] = true;
        }
    }
    $all_usernames = array_keys($all_usernames);

    // 상세정보 조회
    $details = []; // username => [id, wallet, codepay]
    if (count($all_usernames) > 0) {
        $placeholders = implode(',', array_fill(0, count($all_usernames), '?'));
        $sql = "SELECT u.id, u.username";
        if ($wallet_col) $sql .= ", ud.`$wallet_col` AS wallet_address";
        else $sql .= ", NULL AS wallet_address";
        if ($codepay_col) $sql .= ", ud.`$codepay_col` AS codepay_address";
        else $sql .= ", NULL AS codepay_address";
        $sql .= " FROM users u LEFT JOIN user_details ud ON ud.user_id = u.id WHERE u.username IN ($placeholders)";

        $stmt = $conn->prepare($sql);
        $types = str_repeat('s', count($all_usernames));
        $stmt->bind_param($types, ...$all_usernames);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $details[$row['username']] = [
                'id' => (int)$row['id'],
                'wallet' => $row['wallet_address'] ?? '',
                'codepay' => $row['codepay_address'] ?? ''
            ];
        }
        $stmt->close();
    }

    // CSV 출력
    header('Content-Type: text/csv; charset=UTF-8');
    $fname = "admin_detail_{$admin_name}_" . ($title_period ?: date('Y-m-d')) . ".csv";
    header('Content-Disposition: attachment; filename="'.$fname.'"');

    // UTF-8 BOM (엑셀 호환)
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Role','ID','Username','Wallet Address','Codepay Address','Amount(USDT)']);

    foreach ($by_role as $role => $arr) {
        foreach ($arr as $username => $amt) {
            $d = $details[$username] ?? ['id'=>'','wallet'=>'','codepay'=>''];
            fputcsv($out, [
                $role,
                $d['id'],
                $username,
                $d['wallet'],
                $d['codepay'],
                number_format((float)$amt, 2, '.', '')
            ]);
        }
    }
    fclose($out);
    exit;
}


$page_title   = "조직 정산 상세 (GM 제외 전체)";
$content_file = __DIR__ . "/admin_detail_content.php";
include "layout.php";
