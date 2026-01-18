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

// i18n
if (!function_exists('t')) {
    $i18n = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n)) require_once $i18n;
}


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

/**
 * ✅ 권한: gm/admin만
 */
$user_role = $_SESSION['role'] ?? '';
if ($user_role === '') {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $user_role = $r['role'] ?? '';
    $_SESSION['role'] = $user_role;
}
if ($user_role !== 'gm' && $user_role !== 'admin') {
    http_response_code(403);
    exit(t('err.no_permission', 'You do not have permission.'));
}

/**
 * ✅ 상태 파라미터
 */
$date   = $_GET['date'] ?? date('Y-m-d');
$level  = $_GET['level'] ?? 'admin';
$target = $_GET['target'] ?? '';
$path   = $_GET['path'] ?? '';
// ✅ 기본은 '전체' 화면으로 시작
$view   = $_GET['view'] ?? 'all';   // all | list

// date validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

// ✅ 미니달력 기준 월
$year_month = $_GET['year_month'] ?? substr($date, 0, 7);
if (!preg_match('/^\d{4}-\d{2}$/', $year_month)) {
    $year_month = substr(date('Y-m-d'), 0, 7);
}

// month 변경 시 date 보정
if (substr($date, 0, 7) !== $year_month) {
    $date = $year_month . '-01';
}

$date_param = $date;

/**
 * ✅ CodePay 진행상태/완료기준: "현재 레벨/타겟" 기준으로 계산
 * - 화면이 보여주는 테이블 단위(레벨/타겟)로 pending/sent를 집계
 * - 미니달력의 회색(완료)도 동일 기준으로 계산
 */

// level → payout role 매핑 (DB: codepay_payout_items.role)
$level_to_role = [
    'admin'    => 'admin',
    'master'   => 'master',
    'agent'    => 'agent',
    'investor' => 'investor',
    'referrer' => 'referral',
    'referral' => 'referral',
];

$wanted_role = $level_to_role[$level] ?? 'admin';

// 상위 필터 컬럼 (dividend 테이블 기준) - 화면 롤다운과 동일
$parent_filter_col = null;
if ($level === 'master')   $parent_filter_col = 'admin_username';
if ($level === 'agent')    $parent_filter_col = 'mastr_username';
if ($level === 'investor') $parent_filter_col = 'agent_username';
if ($level === 'referrer' || $level === 'referral') $parent_filter_col = 'investor_username';

/**
 * ✅ 정산 완료일(회색 처리용) - "현재 레벨/타겟" 기준
 * - 해당 월의 날짜별로, (현재 role) pending이 0이면 해당 날짜를 완료로 표시
 */
$settled_dates = [];

// 상위 필터가 필요한 레벨인데 target이 비어있으면(=상위 선택 안됨) 달력 완료표시는 비움
$where_parent_month = '';
$month_types = 's';
$month_params = [$year_month];
$can_mark_settled = true;
if ($parent_filter_col !== null) {
    if ($target === '') {
        $can_mark_settled = false;
    } else {
        $where_parent_month = " AND d.`{$parent_filter_col}` = ? ";
        $month_types .= 's';
        $month_params[] = $target;
    }
}

$settled_sql = "
    SELECT DATE(d.tx_date) AS sales_date
    FROM dividend d
    JOIN codepay_payout_items i
      ON i.dividend_id = d.id
    WHERE DATE_FORMAT(d.tx_date, '%Y-%m') = ?
      AND i.role = ?
      $where_parent_month
    GROUP BY DATE(d.tx_date)
    HAVING SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) = 0
       AND COUNT(*) > 0
";

// month bind 파라미터에 role 삽입
// (year_month, role, [target])
$month_types_with_role = $month_types;
$month_params_with_role = $month_params;
array_splice($month_params_with_role, 1, 0, [$wanted_role]);
$month_types_with_role = substr($month_types_with_role, 0, 1) . 's' . substr($month_types_with_role, 1);

if ($can_mark_settled) {
    $stmt_settled = $conn->prepare($settled_sql);
    $stmt_settled->bind_param($month_types_with_role, ...$month_params_with_role);
    $stmt_settled->execute();
    $res_settled = $stmt_settled->get_result();
    while ($row = $res_settled->fetch_assoc()) {
        $settled_dates[$row['sales_date']] = true;
    }
    $stmt_settled->close();
}

$is_settled = isset($settled_dates[$date]);

/**
 * ✅ 레벨 정의
 */
$level_names = [
    'admin' => t('role.admin','Admin'),
    'master' => t('role.master','Master'),
    'agent' => t('role.agent','Agent'),
    'investor' => t('role.investor','Investor'),
    'referrer' => '추천자',
];

$next_level = [
    'admin'    => 'master',
    'master'   => 'agent',
    'agent'    => 'investor',
    'investor' => 'referrer',
    'referrer' => null,
];

$col_by_level = [
    'admin'    => 'admin_username',
    'master'   => 'mastr_username',
    'agent'    => 'agent_username',
    'investor' => 'investor_username',
    'referrer' => 'referral_username',
];

$amt_by_level = [
    'admin'    => 'admin_amount',
    'master'   => 'mastr_amount',
    'agent'    => 'agent_amount',
    'investor' => 'investor_amount',
    'referrer' => 'referral_amount',
];

// level 정합성 보정
if (!isset($level_names[$level])) $level = 'admin';

/**
 * ✅ admin 유저면 자기 조직부터 보게 할지 (현재는 ON)
 * 원치 않으면 이 블록을 주석 처리하면 됨.
 */
$current_username = '';
$stmt_me = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmt_me->bind_param("i", $_SESSION['user_id']);
$stmt_me->execute();
$me = $stmt_me->get_result()->fetch_assoc();
$stmt_me->close();
$current_username = $me['username'] ?? '';

if ($user_role === 'admin' && $level === 'admin' && $target === '') {
    $level  = 'master';
    $target = $current_username;
    $path   = t('role.admin','Admin') . " > " . $current_username;
}

/**
 * ✅ 드롭다운 옵션
 * - level=admin: v1 방식대로 users에서 admin 목록(날짜 무관)
 * - 그 외: dividend에서 해당 날짜 + 상위(target) 기준 distinct
 */
$dropdown_options = [];

if ($level === 'admin') {
    $sql = "SELECT username FROM users WHERE role='admin' ORDER BY username ASC";
    $st = $conn->prepare($sql);
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $dropdown_options[] = $r['username'];
    }
    $st->close();
} else {
    $parent_level = array_search($level, $next_level, true);
    if ($parent_level === false) $parent_level = 'admin';

    $parent_col = $col_by_level[$parent_level];
    $child_col  = $col_by_level[$level];

    $sql = "SELECT DISTINCT $child_col AS username
            FROM dividend
            WHERE DATE(tx_date)=?
              AND $parent_col = ?
              AND $child_col IS NOT NULL AND $child_col <> ''
            ORDER BY $child_col ASC";
    $st = $conn->prepare($sql);
    $st->bind_param("ss", $date_param, $target);
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $dropdown_options[] = $r['username'];
    }
    $st->close();
}

/**
 * ✅ 하위 리스트(view=list)
 */
$child_rows = [];
$child_total = 0.0;
$need_select_parent = false;

$nl = $next_level[$level];

if ($view === 'list' && $nl !== null) {

    if ($level === 'admin' && $target === '') {
        $need_select_parent = true;
    } else {
        $parent_col = $col_by_level[$level];
        $child_col  = $col_by_level[$nl];
        $amt_col    = $amt_by_level[$nl];

        $sql = "SELECT
                    $child_col AS username,
                    COALESCE(SUM($amt_col),0) AS amount
                FROM dividend
                WHERE DATE(tx_date)=?
                  AND $parent_col = ?
                  AND $child_col IS NOT NULL AND $child_col <> ''
                GROUP BY $child_col
                ORDER BY amount DESC";
        $st = $conn->prepare($sql);
        $st->bind_param("ss", $date_param, $target);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            $child_rows[] = [
                'username' => $r['username'],
                'amount'   => (float)$r['amount'],
            ];
            $child_total += (float)$r['amount'];
        }
        $st->close();
    }
}

/**
 * ✅ 3단계: 전체(view=all) 정산 테이블
 */
$table_rows = [];
$table_total = 0.0;

// ✅ CodePay 진행상태 집계(현재 레벨/타겟/날짜 기준)
// - DB 변경 없이 codepay_payout_items.status 로만 계산
$progress = [
    'pending_cnt' => 0,
    'sent_cnt'    => 0,
    'pending_amt' => 0.0,
    'sent_amt'    => 0.0,
    'total_cnt'   => 0,
];

// username별 pending/sent (정산 테이블 행에 상태 표시용)
$row_progress = []; // [username => ['pending_cnt'=>..,'sent_cnt'=>..,'pending_amt'=>..,'sent_amt'=>..]]

if ($view === 'all') {

    $parent_map = [
        'admin'    => null,
        'master'   => 'admin',
        'agent'    => 'master',
        'investor' => 'agent',
        'referrer' => 'investor',
    ];

    $cur_col = $col_by_level[$level];
    $cur_amt = $amt_by_level[$level];
    $parent_level = $parent_map[$level];

    $where  = "WHERE DATE(d.tx_date)=?";
    $types  = "s";
    $params = [$date_param];

    if ($parent_level !== null) {
        if ($target === '') {
            // 상위 미선택이면 비움
            $table_rows = [];
            $table_total = 0.0;
        } else {
            $parent_col = $col_by_level[$parent_level];
            $where .= " AND d.$parent_col = ?";
            $types .= "s";
            $params[] = $target;
        }
    }

    if (!($parent_level !== null && $target === '')) {
$sql = "
    SELECT
        d.$cur_col AS username,
        COALESCE(ud.codepay_address,'') AS code,
        COALESCE(SUM(d.$cur_amt),0) AS amount
    FROM dividend d
    LEFT JOIN users u ON u.username = d.$cur_col
    LEFT JOIN user_details ud ON ud.user_id = u.id
    $where
      AND d.$cur_col IS NOT NULL AND d.$cur_col <> ''
    GROUP BY d.$cur_col, ud.codepay_address
    ORDER BY amount DESC
";
        $st = $conn->prepare($sql);
        $st->bind_param($types, ...$params);
        $st->execute();
        $rs = $st->get_result();

        while ($r = $rs->fetch_assoc()) {
            $table_rows[] = [
                'username' => $r['username'],
                'code'     => $r['code'],
                'amount'   => (float)$r['amount'],
            ];
            $table_total += (float)$r['amount'];
        }
        $st->close();
    }

    // ✅ 현재 화면(레벨/타겟)의 CodePay 상태 집계
    // - settle_confirm_v2.php 와 동일 필터
    $wanted_role = $wanted_role ?? 'admin';
    $where_parent_day = '';
    if ($parent_filter_col !== null && $target !== '') {
        $where_parent_day = " AND d.`{$parent_filter_col}` = ? ";
    }

    $sqlProg = "
        SELECT
            d.$cur_col AS username,
            SUM(CASE WHEN i.status='pending' THEN 1 ELSE 0 END) AS pending_cnt,
            SUM(CASE WHEN i.status='sent' THEN 1 ELSE 0 END) AS sent_cnt,
            SUM(CASE WHEN i.status='pending' THEN i.amount ELSE 0 END) AS pending_amt,
            SUM(CASE WHEN i.status='sent' THEN i.amount ELSE 0 END) AS sent_amt
        FROM codepay_payout_items i
        JOIN dividend d ON d.id = i.dividend_id
        WHERE DATE(d.tx_date)=?
          AND i.role = ?
          $where_parent_day
          AND d.$cur_col IS NOT NULL AND d.$cur_col <> ''
        GROUP BY d.$cur_col
    ";

    $stp = $conn->prepare($sqlProg);
    if ($where_parent_day !== '') {
        $stp->bind_param('sss', $date_param, $wanted_role, $target);
    } else {
        $stp->bind_param('ss', $date_param, $wanted_role);
    }
    $stp->execute();
    $rsp = $stp->get_result();
    while ($r = $rsp->fetch_assoc()) {
        $u = (string)$r['username'];
        $pc = (int)$r['pending_cnt'];
        $sc = (int)$r['sent_cnt'];
        $pa = (float)$r['pending_amt'];
        $sa = (float)$r['sent_amt'];

        $row_progress[$u] = [
            'pending_cnt' => $pc,
            'sent_cnt'    => $sc,
            'pending_amt' => $pa,
            'sent_amt'    => $sa,
        ];

        $progress['pending_cnt'] += $pc;
        $progress['sent_cnt']    += $sc;
        $progress['pending_amt'] += $pa;
        $progress['sent_amt']    += $sa;
    }
    $stp->close();

    $progress['total_cnt'] = $progress['pending_cnt'] + $progress['sent_cnt'];

    // ✅ "정산 완료" 표시는 (현재 레벨/타겟) pending=0 일 때만
    if ($progress['total_cnt'] > 0) {
        $is_settled = ($progress['pending_cnt'] === 0);
    } else {
        $is_settled = false;
    }
}

$page_title   = "조직 정산 ver2";
$page_css     = "group_accounts.css";
$content_file = __DIR__ . "/group_accounts_v2_content.php";

include "layout.php";
