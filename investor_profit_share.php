<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$session_user_id = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';

// 기본은 내 것
$user_id = $session_user_id;

// investor가 아닌(role이 admin/master/agent/gm/superadmin 등) 경우에만 GET user_id 허용
$req_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($req_user_id > 0 && $role !== 'investor') {
    $user_id = $req_user_id;
}

// content에서 링크 유지용
$view_user_id = $user_id;

// DB 연결
require_once "db_connect.php";
if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

/**
 * ✅ 출금이 없는 경우 정산 페이지 접근 차단
 * 정책: 출금(Withdrawal)이 '완료'된 거래가 1건이라도 있어야 정산(Profit Share) 화면 접근 가능
 * 기준:
 *  - withdrawal_chk = 1
 *  - (xm_total + ultima_total) > 0
 */
$has_withdrawal = false;
$chk_sql = "SELECT 1 FROM user_transactions
            WHERE user_id=?
              AND COALESCE(withdrawal_chk,0) = 1
            LIMIT 1";
$chk_stmt = $conn->prepare($chk_sql);
if ($chk_stmt) {
    $chk_stmt->bind_param("i", $user_id);
    $chk_stmt->execute();
    $chk_res = $chk_stmt->get_result();
    $has_withdrawal = ($chk_res && $chk_res->num_rows > 0);
    $chk_stmt->close();
}

if (!$has_withdrawal) {
    // ✅ 메시지 출력 후 대시보드로 이동
    echo "<script>alert('출금이 완료되지 않아 정산을 진행할 수 없습니다.'); location.href='investor_dashboard.php';</script>";
    exit;
}

// === 헬퍼 함수들 ===
// ✅ CodePay 관련 테이블 존재 여부 확인 (MySQL에서 SHOW TABLES LIKE ? 준비문 이슈 회피)
function tableExists($conn, $table) {
    $sql = "SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $ok = ($res && $res->num_rows > 0);
    $stmt->close();
    return $ok;
}


function getUsernameById($uid, $conn) {
    $sql = "SELECT username FROM users WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['username'] ?? null;
}
function getSponsorUsername($uid, $conn) {
    $sql = "SELECT sponsor_id FROM users WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && !empty($row['sponsor_id'])) {
        return getUsernameById((int)$row['sponsor_id'], $conn);
    }
    return null;
}
function getReferrerUsername($uid, $conn) {
    $sql = "SELECT referrer_id FROM users WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row && !empty($row['referrer_id'])) {
        return getUsernameById((int)$row['referrer_id'], $conn);
    }
    return null;
}
// ✅ sponsor_id를 ID 기준으로 가져오기
function getSponsorId($uid, $conn) {
    $sql = "SELECT sponsor_id FROM users WHERE id=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['sponsor_id']) ? (int)$row['sponsor_id'] : null;
}


/**
 * ✅ 1) 전체 거래를 "id 오름차순"으로 모두 읽어온다.
 * - last = 마지막(최신) 레코드
 * - prev = 마지막 바로 이전 레코드 (Summary 기준)
 */
$tx_sql = "
SELECT id, tx_date, code_value,
       COALESCE(xm_value,0) AS xm_value,
       COALESCE(ultima_value,0) AS ultima_value,
       COALESCE(xm_total,0) AS xm_total,
       COALESCE(ultima_total,0) AS ultima_total,
       COALESCE(dividend_amount,0) AS dividend_amount,
       COALESCE(dividend_chk,0) AS dividend_chk,
       COALESCE(withdrawal_chk,0) AS withdrawal_chk
FROM user_transactions
WHERE user_id=?
ORDER BY id ASC
";
$tx_stmt = $conn->prepare($tx_sql);
$tx_stmt->bind_param("i", $user_id);
$tx_stmt->execute();
$tx_res = $tx_stmt->get_result();
$tx_stmt->close();

$tx_rows = [];
while ($r = $tx_res->fetch_assoc()) {
    $tx_rows[] = $r;
}

$last_row = null;
$prev_row = null;

if (count($tx_rows) >= 1) {
    $last_row = $tx_rows[count($tx_rows) - 1];
}
if (count($tx_rows) >= 2) {
    $prev_row = $tx_rows[count($tx_rows) - 2]; // ✅ Summary 기준(최신-1)
}

/**
 * ✅ 최신 레코드는 "거래내역 리스트"에서 무조건 제외
 */
$exclude_latest_id = $last_row ? (int)$last_row['id'] : 0;

/**
 * ✅ Summary는 “prev_row(최신-1)” 기준
 */
$latest_date = $prev_row['tx_date'] ?? '';
$latest_code = $prev_row['code_value'] ?? '';

$finance = [
    'dividend_chk' => 0
];

$xm_in = 0; $ultima_in = 0; $xm_out = 0; $ultima_out = 0;
$deposit = 0; $withdrawal = 0; $profit = 0; $share80 = 0; $share20 = 0;

if ($prev_row) {
    $xm_in      = (float)$prev_row['xm_value'];
    $ultima_in  = (float)$prev_row['ultima_value'];
    $xm_out     = (float)$prev_row['xm_total'];
    $ultima_out = (float)$prev_row['ultima_total'];

    $deposit    = $xm_in + $ultima_in;
    $withdrawal = $xm_out + $ultima_out;
    $profit     = $withdrawal - $deposit;

    $share80 = $profit > 0 ? $profit * 0.75 : 0;
    $share20 = $profit > 0 ? $profit * 0.25 : 0;

    $finance['dividend_chk'] = (int)($prev_row['dividend_chk'] ?? 0);
}

/**
 * ✅ 정산 버튼 처리 (기존 로직 유지)
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['dividend'])) {
    $tx_date    = $_POST['tx_date'] ?? null;
    $code_value = $_POST['code_value'] ?? null;

    if ($tx_date && $code_value) {
        $div_sql = "SELECT COALESCE(dividend_amount,0) AS dividend_amount
                    FROM user_transactions
                    WHERE user_id=? AND tx_date=? AND code_value=?
                    LIMIT 1";
        $div_stmt = $conn->prepare($div_sql);
        $div_stmt->bind_param("iss", $user_id, $tx_date, $code_value);
        $div_stmt->execute();
        $div_row = $div_stmt->get_result()->fetch_assoc();
        $div_stmt->close();

        $dividend_amount = (float)($div_row['dividend_amount'] ?? 0);

        $gm1         = $dividend_amount * 0.30;
        $gm2         = $dividend_amount * 0.20;
        $gm3         = $dividend_amount * 0.10;
        $adminAmt    = $dividend_amount * 0.03;
        $mastrAmt    = $dividend_amount * 0.03;
        $agentAmt    = $dividend_amount * 0.04;
        $investorAmt = $dividend_amount * 0.25;
        $referralAmt = $dividend_amount * 0.05;

$investor_username = getUsernameById($user_id, $conn);

// ✅ ID 기반 sponsor 체인 계산 (정상)
$agent_id  = getSponsorId($user_id, $conn);
$master_id = $agent_id  ? getSponsorId($agent_id,  $conn) : null;
$admin_id  = $master_id ? getSponsorId($master_id, $conn) : null;

$agent_username = $agent_id
    ? getUsernameById($agent_id, $conn)
    : 'TheK_KO';

$mastr_username = $master_id
    ? getUsernameById($master_id, $conn)
    : 'TheK_KO';

$admin_username = $admin_id
    ? getUsernameById($admin_id, $conn)
    : 'TheK_KO';

$referral_username = getReferrerUsername($user_id, $conn) ?? 'TheK_KO';

        $insert_sql = "INSERT INTO dividend 
            (user_id, tx_date,
             gm1_username, gm1_amount,
             gm2_username, gm2_amount,
             gm3_username, gm3_amount,
             admin_username, admin_amount,
             mastr_username, mastr_amount,
             agent_username, agent_amount,
             investor_username, investor_amount,
             referral_username, referral_amount)
            VALUES (?, ?, 
                    'TheK_KO', ?, 
                    'Zayne', ?, 
                    'ezman', ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?, 
                    ?, ?)";

        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "isdddsdsdsdsdsd",
            $user_id, $tx_date,
            $gm1, $gm2, $gm3,
            $admin_username, $adminAmt,
            $mastr_username, $mastrAmt,
            $agent_username, $agentAmt,
            $investor_username, $investorAmt,
            $referral_username, $referralAmt
        );
        if (!$insert_stmt->execute()) {
            echo "<script>alert('❌ dividend 저장 오류: ".$insert_stmt->error."');</script>";
        }
        $insert_stmt->close();

        // ==============================
        // CodePay Export Snapshot (Batch + Items)
        // 기준: dividend 레코드가 생성되는 시점에 1회 스냅샷 생성
        // - user_details.codepay_address 값을 codepay_address_snapshot으로 복사 저장
        // - 엑셀 1행 = codepay_payout_items 1레코드
        // 주의: 테이블이 없으면(아직 미생성) 스킵하고 경고만 남김
        // ==============================
        $dividend_id = $conn->insert_id;

        // (선택) CodePay 스냅샷 생성 여부 토글: 필요 시 false로 바꾸면 생성 안함
        $enable_codepay_snapshot = true;

        if ($enable_codepay_snapshot && $dividend_id > 0) {
            try {
                // 테이블 존재 여부 간단 체크
                $has_batches = tableExists($conn, 'codepay_payout_batches');
                $has_items   = tableExists($conn, 'codepay_payout_items');
                if ($has_batches && $has_items) {

                    // 1) 배치 생성(중복 방지): batch_key를 dividend_id 기준으로 고정
                    $batch_key = "dividend_" . $dividend_id;

                    // INSERT ... ON DUPLICATE KEY UPDATE 로 batch_id 확보
                    $batch_sql = "INSERT INTO codepay_payout_batches (batch_key, dividend_id, created_by, created_at)
                                  VALUES (?, ?, ?, NOW())
                                  ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
                    $batch_stmt = $conn->prepare($batch_sql);
                    $created_by = $_SESSION['user_id'] ?? 'system';
                    $batch_stmt->bind_param("sis", $batch_key, $dividend_id, $created_by);
                    if (!$batch_stmt->execute()) {
                        throw new Exception("codepay_payout_batches 저장 오류: " . $batch_stmt->error);
                    }
                    $batch_stmt->close();
                    $batch_id = $conn->insert_id;

                    // 2) 아이템 생성(중복 방지): 같은 batch_id에 이미 있으면 생성 스킵
                    $exists_stmt = $conn->prepare("SELECT 1 FROM codepay_payout_items WHERE batch_id=? LIMIT 1");
                    $exists_stmt->bind_param("i", $batch_id);
                    $exists_stmt->execute();
                    $exists_res = $exists_stmt->get_result();
                    $already_exists = ($exists_res && $exists_res->num_rows > 0);
                    $exists_stmt->close();

                    if (!$already_exists) {
                        // dividend의 username/amount 컬럼을 세로로 펼쳐 users/user_details와 조인해 items 생성
                        $items_sql = "
                            INSERT INTO codepay_payout_items
                              (batch_id, dividend_id, user_id, role, codepay_address_snapshot, amount, status, created_at)
                            SELECT
                              ? AS batch_id,
                              x.dividend_id,
                              u.id AS user_id,
                              x.role,
                              ud.codepay_address AS codepay_address_snapshot,
                              x.amount,
                              'pending' AS status,
                              NOW() AS created_at
                            FROM (
                              SELECT d.id AS dividend_id, 'gm' AS role, d.gm1_username AS username, d.gm1_amount AS amount
                              FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'gm', d.gm2_username, d.gm2_amount FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'gm', d.gm3_username, d.gm3_amount FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'admin', d.admin_username, d.admin_amount FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'master', d.mastr_username, d.mastr_amount FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'agent', d.agent_username, d.agent_amount FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'investor', d.investor_username, d.investor_amount FROM dividend d WHERE d.id = ?
                              UNION ALL
                              SELECT d.id, 'referral', d.referral_username, d.referral_amount FROM dividend d WHERE d.id = ?
                            ) x
                            JOIN users u
                              ON u.username = x.username
                            LEFT JOIN user_details ud
                              ON ud.user_id = u.id
                            WHERE
                              x.username IS NOT NULL
                              AND x.username <> ''
                              AND x.amount IS NOT NULL
                              AND x.amount > 0
                        ";
                        $items_stmt = $conn->prepare($items_sql);
                        // batch_id + dividend_id 9회
                        $items_stmt->bind_param(
                            "iiiiiiiiii",
                            $batch_id,
                            $dividend_id, $dividend_id, $dividend_id, $dividend_id,
                            $dividend_id, $dividend_id, $dividend_id, $dividend_id, $dividend_id
                        );
                        if (!$items_stmt->execute()) {
                            throw new Exception("codepay_payout_items 저장 오류: " . $items_stmt->error);
                        }
                        $items_stmt->close();
                    }
                }
            } catch (Throwable $e) {
                // 스냅샷 생성 실패가 정산 자체를 막지 않도록 경고 로그만 남김
                error_log("CodePay snapshot error (dividend_id={$dividend_id}): " . $e->getMessage());
            }
        }

        $update_sql = "UPDATE user_transactions SET dividend_chk=1 WHERE user_id=? AND tx_date=? AND code_value=? LIMIT 1";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iss", $user_id, $tx_date, $code_value);

        if ($update_stmt->execute()) {
            echo "<script>
                    alert('✅ 정산되었습니다.');
                    window.location.href = 'investor_profit_share.php?user_id=<?= (int)$user_id ?>';
                  </script>";
            exit;
        } else {
            echo "<script>
                    alert('❌ 정산 오류: ".$update_stmt->error."');
                    window.location.href = 'investor_profit_share.php?user_id=<?= (int)$user_id ?>';
                  </script>";
            exit;
        }
        $update_stmt->close();
    }
}

/**
 * ✅ 지갑 주소 조회
 */
$wallet_sql = "SELECT wallet_address FROM user_details WHERE user_id=? LIMIT 1";
$wallet_stmt = $conn->prepare($wallet_sql);
$wallet_stmt->bind_param("i", $user_id);
$wallet_stmt->execute();
$wallet = $wallet_stmt->get_result()->fetch_assoc()['wallet_address'] ?? '지갑 없음';
$wallet_stmt->close();

/**
 * ✅ content에서 사용할 거래내역 리스트(= 최신 레코드 제외)
 */
$transactions_for_list = [];
foreach ($tx_rows as $row) {
    // ✅ 출금 입력된 거래만 Profit Share 리스트에 노출
    // - withdrawal_chk = 1
    // - (xm_total + ultima_total) > 0
    $withdrawal_chk = (int)($row['withdrawal_chk'] ?? 0);
    $withdrawal_sum = (float)($row['xm_total'] ?? 0) + (float)($row['ultima_total'] ?? 0);
    if ($withdrawal_chk !== 1 || $withdrawal_sum <= 0) {
        continue;
    }

    $transactions_for_list[] = $row;
}


// ============================
// ✅ 날짜/코드 필터 준비
// ============================

// 필터 목록(거래내역 리스트 기준)
$filter_dates = [];
$filter_codes = [];
foreach ($transactions_for_list as $r) {
    if (!empty($r['tx_date']))   { $filter_dates[$r['tx_date']] = true; }
    if (!empty($r['code_value'])){ $filter_codes[$r['code_value']] = true; }
}
// 날짜는 최신이 위로 보이게 내림차순 정렬(문자열 YYYY-MM-DD)
$filter_dates = array_keys($filter_dates);
rsort($filter_dates);

$filter_codes = array_keys($filter_codes);
sort($filter_codes);

// ============================
// ✅ A안 적용: 기본은 "전체 코드"(code 필터 강제 X)
// ✅ 날짜 선택: 일/주/월(캘린더 입력) + (선택) 최근 10개
// ============================

// GET 파라미터
$period = $_GET['period'] ?? 'recent'; // day|week|month|recent
$period = strtolower(trim($period));
if (!in_array($period, ['day','week','month','recent'], true)) $period = 'recent';

$selected_code = $_GET['code_value'] ?? ($_GET['code'] ?? '');
$selected_code = trim($selected_code);
if ($selected_code === 'all') $selected_code = '';
if ($selected_code && !in_array($selected_code, $filter_codes, true)) {
    // 목록에 없는 코드면 전체로 처리
    $selected_code = '';
}

// 날짜 범위 계산
$range_start = '';
$range_end   = '';
$input_day   = trim($_GET['day'] ?? ($_GET['tx_date'] ?? ($_GET['date'] ?? '')));
$input_week  = trim($_GET['week'] ?? '');
$input_month = trim($_GET['month'] ?? '');

// 기본값(가장 최신 날짜)
$default_latest_date = !empty($filter_dates) ? $filter_dates[0] : '';

if ($period === 'day') {
    $d = $input_day ?: $default_latest_date;
    if ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        $range_start = $d;
        $range_end = $d;
    }
} elseif ($period === 'week') {
    $d = $input_week ?: ($input_day ?: $default_latest_date);
    if ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        $dt = new DateTime($d);
        // ISO-8601 기준 월요일 시작
        $dt->modify('monday this week');
        $range_start = $dt->format('Y-m-d');
        $dt->modify('sunday this week');
        $range_end = $dt->format('Y-m-d');
    }
} elseif ($period === 'month') {
    $m = $input_month;
    if (!$m && $default_latest_date) $m = substr($default_latest_date, 0, 7);
    if ($m && preg_match('/^\d{4}-\d{2}$/', $m)) {
        $range_start = $m . '-01';
        $dt = new DateTime($range_start);
        $dt->modify('last day of this month');
        $range_end = $dt->format('Y-m-d');
    }
} else {
    // recent: 범위를 비워두고 최근 10개만 보여줌
}

// 거래내역(표시용) 필터 적용
$filtered_transactions = [];
foreach ($transactions_for_list as $r) {
    $txd = $r['tx_date'] ?? '';
    if ($txd === '') continue;

    // 날짜 범위 필터
    if ($range_start && $range_end) {
        if ($txd < $range_start || $txd > $range_end) continue;
    }

    // 코드 필터(기본: 전체)
    if ($selected_code !== '' && ($r['code_value'] ?? '') !== $selected_code) continue;

    $filtered_transactions[] = $r;
}

// 최신이 위로 오도록 정렬 (tx_date DESC, id DESC)
usort($filtered_transactions, function($a, $b) {
    $da = $a['tx_date'] ?? '';
    $db = $b['tx_date'] ?? '';
    if ($da === $db) {
        return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
    }
    return strcmp($db, $da);
});

if ($period === 'recent') {
    $filtered_transactions = array_slice($filtered_transactions, 0, 10);
}

// content에서 실제로 뿌릴 리스트를 교체
$transactions_for_list = $filtered_transactions;

// Summary 기본 표시는 "현재 리스트의 첫 행" 기준(없으면 0)
$summary_base_row = $transactions_for_list[0] ?? null;
$latest_date = $summary_base_row['tx_date'] ?? ($default_latest_date ?? '');
$latest_code = $summary_base_row['code_value'] ?? '';

// Summary 제목에 표시할 라벨(필터 범위)
$summary_label = '';
if ($period === 'day') {
    $summary_label = $range_start ?: $latest_date;
} elseif ($period === 'week') {
    $summary_label = ($range_start && $range_end) ? ($range_start . ' ~ ' . $range_end) : ($latest_date ?: '');
} elseif ($period === 'month') {
    $summary_label = ($range_start ? substr($range_start, 0, 7) : ($default_latest_date ? substr($default_latest_date, 0, 7) : ''));
} else {
    $summary_label = 'Recent 10';
}

// ✅ Summary: (period 범위 + (선택) 코드) 기준 합산
$sum_where = "WHERE user_id=?";
$sum_params = [$user_id];
$sum_types = "i";

if ($range_start && $range_end) {
    $sum_where .= " AND DATE(tx_date) BETWEEN ? AND ?";
    $sum_params[] = $range_start;
    $sum_params[] = $range_end;
    $sum_types .= "ss";
}
if (!empty($selected_code)) {
    $sum_where .= " AND code_value=?";
    $sum_params[] = $selected_code;
    $sum_types .= "s";
}

$sum_sql = "SELECT 
                COALESCE(SUM(xm_value),0) AS xm_in,
                COALESCE(SUM(ultima_value),0) AS ultima_in,
                COALESCE(SUM(xm_total),0) AS xm_out,
                COALESCE(SUM(ultima_total),0) AS ultima_out,
                MAX(dividend_chk) AS dividend_chk
            FROM user_transactions
            $sum_where";
$sum_stmt = $conn->prepare($sum_sql);
$sum_stmt->bind_param($sum_types, ...$sum_params);
$sum_stmt->execute();
$sum_row = $sum_stmt->get_result()->fetch_assoc();
$sum_stmt->close();

$xm_in      = (float)($sum_row['xm_in'] ?? 0);
$ultima_in  = (float)($sum_row['ultima_in'] ?? 0);
$xm_out     = (float)($sum_row['xm_out'] ?? 0);
$ultima_out = (float)($sum_row['ultima_out'] ?? 0);

$deposit    = $xm_in + $ultima_in;
$withdrawal = $xm_out + $ultima_out;
$profit     = $withdrawal - $deposit;

$share80 = $profit > 0 ? $profit * 0.75 : 0;
$share20 = $profit > 0 ? $profit * 0.25 : 0;

$finance['dividend_chk'] = (int)($sum_row['dividend_chk'] ?? 0);

// content에서 사용할 현재 필터 값
$current_period = $period;
$current_code   = $selected_code;
$current_day    = ($period === 'day') ? ($range_start ?: $input_day) : ($input_day ?: '');
$current_week   = ($period === 'week') ? ($input_week ?: $input_day ?: $default_latest_date) : ($input_week ?: '');
$current_month  = ($period === 'month') ? ($input_month ?: ($default_latest_date ? substr($default_latest_date,0,7) : '')) : ($input_month ?: '');

// === 레이아웃 적용 ===
$page_title   = "Investor Profit Share";
$content_file = __DIR__ . "/investor_profit_share_content.php";
$menu_type    = "investor";
include __DIR__ . "/layout.php";
