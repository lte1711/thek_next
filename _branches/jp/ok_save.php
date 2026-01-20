<?php
// ok_save.php (tx_id 기반, 중복 완전 차단)
// 핵심:
//   - korea_ready_trading 은 tx_id(=user_transactions.id) 1:1 매핑
//   - UNIQUE(tx_id) 로 신규/중복을 DB가 보장
//   - OK 클릭은 UPSERT(INSERT ... ON DUPLICATE KEY UPDATE)
//   - 더 이상 (user_id, tx_date, day_seq) 같은 조합키에 의존하지 않습니다.

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

header('Content-Type: application/json; charset=utf-8');

// i18n (t()) 로드 - ajax 엔드포인트는 layout.php를 안 타므로 직접 포함 필요
if (!function_exists('t')) {
  
}

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success'=>false,'message'=>'Not logged in']);
  exit;
}

// ✅ 권한: Zayne + superadmin 고정
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
if (!($username === 'Zayne' || $role === 'superadmin')) {
  echo json_encode(['success'=>false,'message'=>'Forbidden']);
  exit;
}

include 'db_connect.php';

function has_column(mysqli $conn, string $table, string $column): bool {
  $sql = "SELECT 1
            FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND COLUMN_NAME = ?
           LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $table, $column);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_row();
  $stmt->close();
  return !empty($res);
}

try {
  $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
  $tx_id   = isset($_POST['tx_id']) ? (int)$_POST['tx_id'] : 0;
  $region  = isset($_POST['region']) ? trim($_POST['region']) : 'korea';

  if ($user_id<=0 || $tx_id<=0) throw new Exception('Invalid parameters');

  $allowed_regions = ['korea', 'japan'];
  if (!in_array($region, $allowed_regions, true)) throw new Exception('Invalid region');

  // ✅ Country mapping and validation
  $country_map = ['korea' => 'KR', 'japan' => 'JP'];
  $expected_country = $country_map[$region];
  
  // Verify user belongs to the requested region
  $stmt_country = $conn->prepare("SELECT country FROM users WHERE id=? LIMIT 1");
  $stmt_country->bind_param("i", $user_id);
  $stmt_country->execute();
  $user_row = $stmt_country->get_result()->fetch_assoc();
  $stmt_country->close();
  
  if (!$user_row || $user_row['country'] !== $expected_country) {
    throw new Exception("Invalid region for this user. Expected: {$expected_country}, Got: " . ($user_row['country'] ?? 'NULL'));
  }

  $table_ready = $region.'_ready_trading';
  $table_prog  = $region.'_progressing';
  $admin_id = (int)$_SESSION['user_id'];

  // 거래일 확보 (YYYY-MM-DD)
  $stmt = $conn->prepare("SELECT DATE(tx_date) AS d, COALESCE(external_done_chk,0) AS ext, COALESCE(pair, 'XM/Ultima') AS pair FROM user_transactions WHERE id=? AND user_id=?");
  $stmt->bind_param("ii",$tx_id,$user_id);
  $stmt->execute();
  $txrow = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if(!$txrow || empty($txrow['d'])) throw new Exception('Transaction not found');
  if ((int)($txrow['ext'] ?? 0) !== 1) throw new Exception(t('error.external_not_done','External processing not confirmed yet. (external_done_chk=0)'));
  $pair = trim((string)($txrow['pair'] ?? 'XM/Ultima'));
  $tx_date = $txrow['d'];

  // ✅ 이제 ready_trading은 tx_id 1:1 매핑이 전제입니다.
  //   (DB 마이그레이션: tx_id 컬럼 + UNIQUE(tx_id))
  $ready_has_tx_id      = has_column($conn, $table_ready, 'tx_id');
  if (!$ready_has_tx_id) {
    throw new Exception(t('error.tx_id_column_missing','Missing tx_id column in ready table. Migration required.'));
  }
  $ready_has_tx_date    = has_column($conn, $table_ready, 'tx_date');
  $ready_has_status     = has_column($conn, $table_ready, 'status');
  $ready_has_settled_by = has_column($conn, $table_ready, 'settled_by');
  $ready_has_settled_dt = has_column($conn, $table_ready, 'settled_date');

  // progressing note 컬럼명: notes 또는 note (둘 중 존재하는 것을 사용)
  $prog_note_col = null;
  if (has_column($conn, $table_prog, 'notes')) $prog_note_col = 'notes';
  else if (has_column($conn, $table_prog, 'note')) $prog_note_col = 'note';

  $note = "Bot processing started";

  $conn->begin_transaction();

  // ✅ tx_id 기준 UPSERT (UNIQUE(tx_id) 전제)
  // INSERT 컬럼 구성 (존재하는 컬럼만)
  $cols = ["user_id", "tx_id"];
  $vals = ["?", "?"];
  $it   = "ii";
  $ip   = [$user_id, $tx_id];

  if ($ready_has_tx_date) { $cols[]="tx_date"; $vals[]="?"; $it.="s"; $ip[]=$tx_date; }
  if ($ready_has_status) { $cols[]="status"; $vals[]="?"; $it.="s"; $ip[]='approved'; }
  if ($ready_has_settled_by) { $cols[]="settled_by"; $vals[]="?"; $it.="i"; $ip[]=$admin_id; }
  if ($ready_has_settled_dt) { $cols[]="settled_date"; $vals[]="NOW()"; }

  // UPDATE 절: status/settled_by/settled_date 를 동일하게 갱신
  $upd = [];
  if ($ready_has_status) $upd[] = "status=VALUES(status)";
  if ($ready_has_settled_by) $upd[] = "settled_by=VALUES(settled_by)";
  if ($ready_has_settled_dt) $upd[] = "settled_date=NOW()";
  if ($ready_has_tx_date) $upd[] = "tx_date=VALUES(tx_date)";
  // 최소 1개는 있어야 함
  if (empty($upd)) throw new Exception("No updatable columns found in {$table_ready}");

  $sql = "INSERT INTO {$table_ready} (".implode(",", $cols).") VALUES (".implode(",", $vals).")\n"
       . "ON DUPLICATE KEY UPDATE ".implode(", ", $upd);

  $stmt = $conn->prepare($sql);
  $stmt->bind_param($it, ...$ip);
  $stmt->execute();
  $stmt->close();

  // ready_id 확보
  $stmt = $conn->prepare("SELECT id FROM {$table_ready} WHERE tx_id=? LIMIT 1");
  $stmt->bind_param("i", $tx_id);
  $stmt->execute();
  $rid = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $ready_id = (int)($rid['id'] ?? 0);

  // progressing notes 업데이트 (가능한 경우만)
  if ($prog_note_col) {
    // ✅ tx_id 기반으로 korea_progressing 갱신 (tx_id 컬럼 존재 시)
    $prog_has_tx_id = has_column($conn, $table_prog, 'tx_id');

    if ($prog_has_tx_id) {
      $stmt = $conn->prepare("UPDATE {$table_prog} SET {$prog_note_col}=? , updated_at=NOW() WHERE tx_id=?");
      $stmt->bind_param("si", $note, $tx_id);
      $stmt->execute();
      $stmt->close();
    } else {
      // (레거시 폴백) tx_id 컬럼이 없으면 기존 키로 갱신
      $has_prog_pair = has_column($conn, $table_prog, 'pair');
      if ($has_prog_pair) {
        $stmt = $conn->prepare("UPDATE {$table_prog} SET {$prog_note_col}=? , updated_at=NOW() WHERE user_id=? AND tx_date=? AND pair=?");
        $stmt->bind_param("siss", $note, $user_id, $tx_date, $pair);
      } else {
        $stmt = $conn->prepare("UPDATE {$table_prog} SET {$prog_note_col}=? , updated_at=NOW() WHERE user_id=? AND tx_date=?");
        $stmt->bind_param("sis", $note, $user_id, $tx_date);
      }
      $stmt->execute();
      $stmt->close();
    }
  }

$conn->commit();

  echo json_encode(['success'=>true,'message'=>t('msg.ok_processed_bot_running','OK processed (bot running)'), 'ready_id'=>$ready_id]);

} catch (Throwable $e){
  if(isset($conn)) { try{$conn->rollback();}catch(Throwable $ignore){} }
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>