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
        function current_lang() {
            return 'ko';
        }
    }
}

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}




// GM only access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gm') {
    http_response_code(403);
    exit(t('common.gm_only', 'GM access only.'));
}

include 'db_connect.php';

$admin_id = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
$date     = $_GET['date'] ?? null;    // YYYY-MM-DD
$period   = $_GET['period'] ?? null;  // YYYY-MM or YEARWEEK(6) or (may come as YYYY-MM-DD)

// admin_id validation
if ($admin_id <= 0) {
    http_response_code(400);
    exit(t('common.bad_request_admin_missing', 'Invalid request. (admin_id missing)'));
}

// ✅ If period=YYYY-MM-DD, treat it as date
if (!$date && $period && preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
    $date = $period;
    $period = null;
}

// Fetch admin username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND role='admin' LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    http_response_code(404);
    exit(t('common.admin_not_found', 'Admin not found.'));
}

$admin_name = $res['username'];

// Build period condition
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
    // ✅ ISO week basis (same as group_accounts.php)
    $whereSql = " AND YEARWEEK(d.tx_date, 3) = ? ";
    $bindTypes .= "i";
    $bindVals[] = (int)$period;
    $title_period = $period;

} else {
    http_response_code(400);
    exit(t('common.invalid_period_format', 'Invalid period format. Use date=YYYY-MM-DD or period=YYYY-MM or YEARWEEK (6 digits).'));
}

/**
 * ✅ Key: all distribution rows (excluding GM) under the selected admin organization
 * - filter key: d.admin_username = (selected admin)
 * - each row contains admin/master/agent/investor/referral amounts
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

// Dynamic binding
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

// Aggregate by beneficiary per role (who received how much)
$by_role = [
    'admin'    => [], // username => sum
    'master'   => [],
    'agent'    => [],
    'investor' => [],
    'referral' => [],
];

if ($detail_res) {
    while ($r = $detail_res->fetch_assoc()) {
        // Convert amounts to float
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

        // Totals
        $totals['admin']    += $a;
        $totals['master']   += $m;
        $totals['agent']    += $ag;
        $totals['investor'] += $iv;
        $totals['referral'] += $rf;
        $totals['ex_gm']     += ($a + $m + $ag + $iv + $rf);

        // Sum per beneficiary by role
        if (!empty($r['admin_username']))    $by_role['admin'][$r['admin_username']]       = ($by_role['admin'][$r['admin_username']] ?? 0) + $a;
        if (!empty($r['mastr_username']))    $by_role['master'][$r['mastr_username']]      = ($by_role['master'][$r['mastr_username']] ?? 0) + $m;
        if (!empty($r['agent_username']))    $by_role['agent'][$r['agent_username']]       = ($by_role['agent'][$r['agent_username']] ?? 0) + $ag;
        if (!empty($r['investor_username'])) $by_role['investor'][$r['investor_username']] = ($by_role['investor'][$r['investor_username']] ?? 0) + $iv;
        if (!empty($r['referral_username'])) $by_role['referral'][$r['referral_username']] = ($by_role['referral'][$r['referral_username']] ?? 0) + $rf;
    }
}

// Sort descending (highest first)
foreach ($by_role as $k => $arr) {
    arsort($arr);
    $by_role[$k] = $arr;
}


// =====================
// CSV Download (settlement details by role)
// =====================
if (isset($_GET['download']) && $_GET['download'] == '1') {

    
    // ✅ Exclude investor lines in download (same as UI)
    if (isset($by_role['investor'])) { unset($by_role['investor']); }
// Check user_details columns (column names may differ by environment)
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

    // Collect usernames
    $all_usernames = [];
    foreach ($by_role as $role => $arr) {
        
        if ($role === 'investor') { continue; }
foreach (array_keys($arr) as $un) {
            if (!empty($un)) $all_usernames[$un] = true;
        }
    }
    $all_usernames = array_keys($all_usernames);

    // Fetch details
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

    // Output CSV
    header('Content-Type: text/csv; charset=UTF-8');
    $fname = "admin_detail_{$admin_name}_" . ($title_period ?: date('Y-m-d')) . ".csv";
    header('Content-Disposition: attachment; filename="'.$fname.'"');

    // UTF-8 BOM (Excel compatible)
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


$page_title = t('org_detail.title', 'Organization Settlement Detail (Excl. GM Total)');
$content_file = __DIR__ . "/admin_detail_content.php";
include "layout.php";