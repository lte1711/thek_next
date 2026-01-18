<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db_connect.php';

/**
 * ✅ GM별 고정 수익배율(전체 100분율 기준 표시용)
 * - 화면 우측(빨간 박스)처럼 "100% 기준 수익배율"을 함께 표시
 * - 일별/월별 모두 동일(정책값)
 *
 * ⚠️ 운영 환경에 맞게 숫자만 수정하면 됩니다.
 */
$gm_profit_ratio_map = [
    'GM1' => 30,
    'GM2' => 20,
    'GM3' => 10,
];
$total_profit_ratio = array_sum($gm_profit_ratio_map);

/**
 * ✅ (선택) GM 표시 이름을 DB와 무관하게 강제 고정하고 싶을 때 사용
 * - 아래 값을 채우면 users 테이블 매칭 결과와 상관없이 화면에 이 이름이 그대로 표시됩니다.
 * - 비워두면(빈 문자열) 기존처럼 users(role='gm')에서 매칭한 이름을 사용합니다.
 */
$gm_display_name_override = [
    'GM1' => '',
    'GM2' => '',
    'GM3' => '',
];

/**
 * ✅ GM 슬롯(1/2/3) ↔ users 매핑(이름 뒤바뀜 방지)
 *
 * 기존 구현은 users(role='gm')를 id ASC로 3명 가져와서 GM1~GM3에 순서대로 붙였습니다.
 * 그런데 운영DB에서 GM 계정 생성 순서(id)가 정책 슬롯(GM1/GM2/GM3)과 다를 수 있어
 * 화면에서 GM2/GM3 이름이 뒤바뀌어 보이는 문제가 발생합니다.
 *
 * 해결: "슬롯 → 특정 GM 계정"을 username(또는 id)로 고정 매핑해서 사용합니다.
 * - 아래 값만 운영 환경에 맞게 수정하면 됩니다.
 */
// ✅ 슬롯 매핑은 "username만"으로 고정하면 운영DB에서 변경될 때 다시 뒤바뀔 수 있어
//    name/username/email 중 하나라도 맞으면 슬롯에 고정되도록 "식별자 후보"를 여러 개 둡니다.
//    (필요시 여기 값만 추가/수정)
$gm_slot_identifiers = [
    'GM1' => ['TheK_KO', 'TheK', 'thekglobals@gmail.com'],
    'GM2' => ['Zayne', 'Malaysia', 'qweasd2@qasw.com'],
    'GM3' => ['ezman', 'Kim YongHee', 'ezman55@gmail.com'],

];

/**
 * GM 접근 제한
 * - 세션에 role이 있으면 그걸 사용
 * - 없으면 users 테이블에서 user_id로 role 조회
 */
$is_gm = false;

if (isset($_SESSION['role'])) {
    $is_gm = ($_SESSION['role'] === 'gm');
} else {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $is_gm = (isset($res['role']) && $res['role'] === 'gm');
}

if (!$is_gm) {
    http_response_code(403);
    exit('GM만 접근 가능합니다.');
}

// 날짜 선택 (기본: 전날)
$settle_date = $_GET['settle_date'] ?? date('Y-m-d', strtotime('-1 day'));
$year_month  = $_GET['year_month'] ?? date('Y-m');

/**
 * ✅ 정산 완료 여부(gm_sales_daily settled=1) 조회
 * - region 값이 달라도(또는 region 컬럼이 혼재돼도) "날짜 기준"으로 완료 처리되게 함
 */
$is_settled = false;
$settled_at = null;

$st = $conn->prepare("
    SELECT COUNT(*) AS cnt, MAX(settled_at) AS settled_at
    FROM gm_sales_daily
    WHERE (sales_date = ? OR DATE(sales_date) = ?)
      AND settled = 1
");
$st->bind_param("ss", $settle_date, $settle_date);
$st->execute();
$sr = $st->get_result()->fetch_assoc();
$st->close();

if (!empty($sr) && (int)$sr['cnt'] > 0) {
    $is_settled = true;
    $settled_at = $sr['settled_at'];
}

/* ✅ 디버그는 반드시 '조회 이후'에 만든다 */
$__settle_debug = [
  'settle_date' => $settle_date,
  'cnt' => $sr['cnt'] ?? null,
  'is_settled' => $is_settled,
  'settled_at' => $settled_at,
];
/**
 * ✅ 데일리 정산 (8쿼리 → 1쿼리)
 */
$daily_sql = "
    SELECT
        COALESCE(SUM(gm1_amount), 0) AS gm1,
        COALESCE(SUM(gm2_amount), 0) AS gm2,
        COALESCE(SUM(gm3_amount), 0) AS gm3,
        COALESCE(SUM(admin_amount), 0) AS admin,
        COALESCE(SUM(mastr_amount), 0) AS master,
        COALESCE(SUM(agent_amount), 0) AS agent,
        COALESCE(SUM(investor_amount), 0) AS investor,
        COALESCE(SUM(referral_amount), 0) AS referral
    FROM dividend
    WHERE DATE(tx_date) = ?
";
$stmt = $conn->prepare($daily_sql);
$stmt->bind_param("s", $settle_date);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$stmt->close();

/**
 * ✅ GM 이름 매핑(뒤바뀜 방지)
 * - dividend의 gm1_amount/gm2_amount/gm3_amount는 "슬롯" 의미로 고정이므로
 *   users 테이블의 id 순서로 이름을 붙이면 운영 DB에서 GM2/GM3가 뒤바뀔 수 있습니다.
 * - 해결: 상단의 $gm_slot_username_map(또는 필요시 id/username)을 기준으로 슬롯에 이름을 매핑.
 */
$gm_name_map = ['GM1' => '', 'GM2' => '', 'GM3' => ''];

// 1) role='gm' 전체를 가져와 lookup(여러 키) 생성
$gm_stmt = $conn->prepare("SELECT id, name, username, email, country FROM users WHERE role='gm'");
$gm_stmt->execute();
$gm_res = $gm_stmt->get_result();
$gm_lookup = []; // key(lower) => row
while ($u = $gm_res->fetch_assoc()) {
    foreach (['username','name','email'] as $k) {
        if (!empty($u[$k])) {
            $gm_lookup[strtolower(trim($u[$k]))] = $u;
        }
    }
}
$gm_stmt->close();

// 2) 슬롯 식별자 후보(username/name/email 중 하나)로 고정 매핑
foreach (['GM1','GM2','GM3'] as $slot) {
    $candidates = $gm_slot_identifiers[$slot] ?? [];
    foreach ($candidates as $key) {
        $lk = strtolower(trim((string)$key));
        if ($lk !== '' && isset($gm_lookup[$lk])) {
            $row = $gm_lookup[$lk];
            $gm_name_map[$slot] = $row['name'] ?: ($row['username'] ?? '');
            break;
        }
    }
}

	// 3) (선택) 화면 표시 이름 강제 오버라이드
	foreach (['GM1','GM2','GM3'] as $slot) {
	    if (!empty($gm_display_name_override[$slot])) {
	        $gm_name_map[$slot] = $gm_display_name_override[$slot];
	    }
	}

// ⚠️ 중요: 더 이상 id ASC로 "자동 채움"하지 않습니다.
// 운영에서 GM2/GM3 이름 뒤바뀜 문제의 원인이 바로 "순서 가정"이었기 때문입니다.
// 매핑이 비면(=식별자 불일치) 이름은 빈 칸으로 남겨두고,
// 위 $gm_slot_identifiers에 후보값을 추가해서 확정 매핑하세요.

// ✅ 데일리: GM만 표시 (하단 역할별 테이블/행 제거 목적)
$role_data = [
    'GM1' => (float)($r['gm1'] ?? 0),
    'GM2' => (float)($r['gm2'] ?? 0),
    'GM3' => (float)($r['gm3'] ?? 0),
];

$total_usdt = array_sum($role_data);

/**
 * ✅ 월별 정산
 */
$monthly_sql = "
    SELECT
        DATE(tx_date) AS sales_date,
        SUM(gm1_amount) AS gm1,
        SUM(gm2_amount) AS gm2,
        SUM(gm3_amount) AS gm3,
        SUM(admin_amount) AS admin,
        SUM(mastr_amount) AS master,
        SUM(agent_amount) AS agent,
        SUM(investor_amount) AS investor,
        SUM(referral_amount) AS referral
    FROM dividend
    WHERE DATE_FORMAT(tx_date, '%Y-%m') = ?
    GROUP BY sales_date
    ORDER BY sales_date DESC
";
$stmt = $conn->prepare($monthly_sql);
$stmt->bind_param("s", $year_month);
$stmt->execute();
$monthly_result = $stmt->get_result();

$data = [];
while ($row = $monthly_result->fetch_assoc()) {
    $date = $row['sales_date'];
    $data[$date] = [
        // ✅ 월별도 GM만 표시
        'GM1' => (float)($row['gm1'] ?? 0),
        'GM2' => (float)($row['gm2'] ?? 0),
        'GM3' => (float)($row['gm3'] ?? 0),
    ];
    $data[$date]['Total'] = array_sum($data[$date]);
}
$stmt->close();

// 레이아웃 출력
$page_title   = "파트너 정산 (GM)";
$content_file = __DIR__ . "/partner_accounts_content.php";
include "layout.php";