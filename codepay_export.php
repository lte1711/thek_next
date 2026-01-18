<?php
session_start();

// 로그인 여부 확인 (gm_revenue_report.php와 동일한 레벨로 맞춤)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// GM 권한만 접근 가능 (필요 시 admin 허용 가능)
if ($_SESSION['role'] !== 'gm') {
    echo "<script>alert('접근 권한이 없습니다.'); window.location.href='gm_dashboard.php';</script>";
    exit;
}

include 'db_connect.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 날짜 및 조회 유형 파라미터 처리 (gm_revenue_report.php와 동일)
$report_type   = $_GET['type'] ?? ($_POST['type'] ?? 'day');   // day, week, month, year
$report_date   = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));
$selected_role = $_GET['role'] ?? ($_POST['role'] ?? 'gm');    // 표시용

// 날짜 범위 계산
$start_date = $report_date;
$end_date   = $report_date;

switch ($report_type) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week', strtotime($report_date)));
        $end_date   = date('Y-m-d', strtotime('sunday this week', strtotime($report_date)));
        break;
    case 'month':
        $start_date = date('Y-m-01', strtotime($report_date));
        $end_date   = date('Y-m-t', strtotime($report_date));
        break;
    case 'year':
        $start_date = date('Y-01-01', strtotime($report_date));
        $end_date   = date('Y-12-31', strtotime($report_date));
        break;
    case 'day':
    default:
        break;
}

/**
 * CSV Export handler (단일/선택 다운로드)
 * - 다른 파일(codepay_export_download.php) 수정 없이 여기서 처리
 * - pending 항목만 내보내고, 내보낸 항목은 sent로 업데이트하여 버튼이 사라지도록 함
 */
function export_codepay_csv(mysqli $conn, array $batch_ids): void {
    $batch_ids = array_values(array_filter(array_map('intval', $batch_ids)));
    if (count($batch_ids) === 0) {
        http_response_code(400);
        echo "Invalid batch_id";
        exit;
    }

    // pending 데이터 조회
    $in = implode(',', array_fill(0, count($batch_ids), '?'));
    $types = str_repeat('i', count($batch_ids));

    $sqlSelect = "
        SELECT
            i.id,
            i.batch_id,
            i.codepay_address_snapshot AS codepay_address,
            i.amount,
            u.username,
            u.phone,
            i.role,
            i.user_id,
            i.dividend_id
        FROM codepay_payout_items i
        JOIN users u ON u.id = i.user_id
        WHERE i.batch_id IN ($in)
          AND i.status = 'pending'
        ORDER BY i.batch_id ASC, i.amount DESC, i.id ASC
    ";

    $stmt = $conn->prepare($sqlSelect);
    $stmt->bind_param($types, ...$batch_ids);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $item_ids = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
        $item_ids[] = (int)$row['id'];
    }
    $stmt->close();

    if (count($rows) === 0) {
        // 내보낼 pending이 없으면 안내
        header('Content-Type: text/plain; charset=UTF-8');
        echo "No pending items to export.";
        exit;
    }

    // 트랜잭션으로 sent 업데이트
    try {
        $conn->begin_transaction();

        $in2 = implode(',', array_fill(0, count($item_ids), '?'));
        $types2 = str_repeat('i', count($item_ids));
        $sqlUpdate = "UPDATE codepay_payout_items SET status='sent' WHERE id IN ($in2)";
        $stmtU = $conn->prepare($sqlUpdate);
        $stmtU->bind_param($types2, ...$item_ids);
        $stmtU->execute();
        $stmtU->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Export failed: " . $e->getMessage();
        exit;
    }

    // CSV 출력
    $filename = "codepay_export_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo "\xEF\xBB\xBF"; // BOM

    $out = fopen('php://output', 'w');
    fputcsv($out, ['batch_id','codepay_address','amount','username','phone','role','user_id','dividend_id','status']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['batch_id'],
            $r['codepay_address'],
            $r['amount'],
            $r['username'],
            $r['phone'],
            $r['role'],
            $r['user_id'],
            $r['dividend_id'],
            'sent',
        ]);
    }

    fclose($out);
    exit;
}

// ✅ 개별 다운로드 (GET)
if (isset($_GET['download']) && $_GET['download'] === '1') {
    $batch_id = (int)($_GET['batch_id'] ?? 0);
    export_codepay_csv($conn, [$batch_id]);
}

// ✅ 선택 다운로드 (POST)
if (isset($_POST['action']) && $_POST['action'] === 'bulk_download') {
    $batch_ids = $_POST['batch_ids'] ?? [];
    if (!is_array($batch_ids)) $batch_ids = [];
    export_codepay_csv($conn, $batch_ids);
}

// 페이지 타이틀과 본문 파일 지정 (gm_revenue_report.php 방식)
$page_title   = "CodePay 엑셀 내보내기";
$content_file = __DIR__ . "/codepay_export_content.php";

// 레이아웃 적용 (gm_revenue_report.php와 동일)
include "layout.php";
