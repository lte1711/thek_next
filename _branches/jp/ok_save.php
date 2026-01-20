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

  // ✅ 디버그 로그: POST 파라미터 확인
  error_log("[OK_SAVE] POST=" . json_encode($_POST));

  if ($user_id<=0 || $tx_id<=0) throw new Exception('Invalid parameters');

  $allowed_regions = ['korea', 'japan'];
  if (!in_array($region, $allowed_regions, true)) throw new Exception('Invalid region');

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
  $affected_ready = $stmt->affected_rows;
  $stmt->close();

  // ✅ 디버그 로그: ready_trading UPSERT 결과
  error_log("[OK_SAVE] ready_trading SQL_ERR=" . mysqli_error($conn));
  error_log("[OK_SAVE] ready_trading affected_rows={$affected_ready}");

  // ready_id 확보
  $stmt = $conn->prepare("SELECT id FROM {$table_ready} WHERE tx_id=? LIMIT 1");
  $stmt->bind_param("i", $tx_id);
  $stmt->execute();
  $rid = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $ready_id = (int)($rid['id'] ?? 0);

  // ✅ 핵심: progressing row 생성 (없으면 INSERT, 있으면 UPDATE)
  // user_transactions 정보 조회
  $stmt = $conn->prepare("
    SELECT
      t.user_id,
      DATE(t.tx_date) AS tx_date,
      COALESCE(t.xm_value,0) AS xm_value,
      COALESCE(t.ultima_value,0) AS ultima_value,
      COALESCE(t.xm_total,0) AS xm_total,
      COALESCE(t.ultima_total,0) AS ultima_total,
      COALESCE(t.settled_date, t.created_at) AS created_at,
      t.settled_by,
      t.settled_date,
      t.reject_reason
    FROM user_transactions t
    WHERE t.id=? AND t.user_id=?
    LIMIT 1
  ");
  $stmt->bind_param("ii", $tx_id, $user_id);
  $stmt->execute();
  $tx_data = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$tx_data) throw new Exception('Transaction data not found for progressing');

  $deposit_total = (float)$tx_data['xm_value'] + (float)$tx_data['ultima_value'];
  $withdrawal_total = (float)$tx_data['xm_total'] + (float)$tx_data['ultima_total'];
  $profit_loss = $withdrawal_total - $deposit_total;

  // tx_id 기반 progressing row 존재 확인
  $prog_has_tx_id = has_column($conn, $table_prog, 'tx_id');
  $existing_prog = null;

  if ($prog_has_tx_id) {
    $stmt = $conn->prepare("SELECT id FROM {$table_prog} WHERE tx_id=? LIMIT 1");
    $stmt->bind_param("i", $tx_id);
    $stmt->execute();
    $existing_prog = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  } else {
    // 레거시: user_id + tx_date + pair 기반 확인
    $stmt = $conn->prepare("SELECT id FROM {$table_prog} WHERE user_id=? AND tx_date=? AND pair='xm,ultima' LIMIT 1");
    $stmt->bind_param("is", $user_id, $tx_data['tx_date']);
    $stmt->execute();
    $existing_prog = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }

  if (!$existing_prog) {
    // INSERT: progressing row 생성
    $insert_cols = ["user_id", "tx_date", "pair", "deposit_status", "withdrawal_status", "profit_loss", "created_at"];
    $insert_vals = ["?", "?", "?", "?", "?", "?", "?"];
    $insert_types = "issddds";
    $insert_params = [
      $user_id,
      $tx_data['tx_date'],
      'xm,ultima',
      $deposit_total,
      $withdrawal_total,
      $profit_loss,
      $tx_data['created_at']
    ];

    if ($prog_has_tx_id) {
      $insert_cols[] = "tx_id";
      $insert_vals[] = "?";
      $insert_types .= "i";
      $insert_params[] = $tx_id;
    }

    // notes 컬럼 존재 확인
    $prog_note_col = null;
    if (has_column($conn, $table_prog, 'notes')) {
      $prog_note_col = 'notes';
    } else if (has_column($conn, $table_prog, 'note')) {
      $prog_note_col = 'note';
    }

    if ($prog_note_col) {
      $insert_cols[] = $prog_note_col;
      $insert_vals[] = "?";
      $insert_types .= "s";
      $insert_params[] = "Bot processing started";
    }

    $insert_sql = "INSERT INTO {$table_prog} (" . implode(",", $insert_cols) . ") VALUES (" . implode(",", $insert_vals) . ")";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param($insert_types, ...$insert_params);
    $stmt->execute();
    $affected_prog = $stmt->affected_rows;
    $stmt->close();

    error_log("[OK_SAVE] progressing INSERT affected_rows={$affected_prog}");
  } else {
    // UPDATE: 기존 progressing row 갱신
    $prog_note_col = null;
    if (has_column($conn, $table_prog, 'notes')) {
      $prog_note_col = 'notes';
    } else if (has_column($conn, $table_prog, 'note')) {
      $prog_note_col = 'note';
    }

    $update_parts = [
      "deposit_status=?",
      "withdrawal_status=?",
      "profit_loss=?",
      "updated_at=NOW()"
    ];
    $update_types = "ddd";
    $update_params = [$deposit_total, $withdrawal_total, $profit_loss];

    if ($prog_note_col) {
      $update_parts[] = "{$prog_note_col}=?";
      $update_types .= "s";
      $update_params[] = "Bot processing started";
    }

    if ($prog_has_tx_id) {
      $update_sql = "UPDATE {$table_prog} SET " . implode(", ", $update_parts) . " WHERE tx_id=?";
      $update_types .= "i";
      $update_params[] = $tx_id;
    } else {
      $update_sql = "UPDATE {$table_prog} SET " . implode(", ", $update_parts) . " WHERE user_id=? AND tx_date=? AND pair=?";
      $update_types .= "iss";
      $update_params[] = $user_id;
      $update_params[] = $tx_data['tx_date'];
      $update_params[] = 'xm,ultima';
    }

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param($update_types, ...$update_params);
    $stmt->execute();
    $affected_prog = $stmt->affected_rows;
    $stmt->close();

    error_log("[OK_SAVE] progressing UPDATE affected_rows={$affected_prog}");
  }

  error_log("[OK_SAVE] progressing SQL_ERR=" . mysqli_error($conn));

  $conn->commit();

  echo json_encode(['success'=>true,'message'=>t('msg.ok_processed_bot_running','OK processed (bot running)'), 'ready_id'=>$ready_id]);

} catch (Throwable $e){
  if(isset($conn)) { try{$conn->rollback();}catch(Throwable $ignore){} }
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>