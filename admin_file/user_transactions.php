<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';
$flash_messages = [];

function fetch_tx_row(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM user_transactions WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// 세션 플래시 메시지(대시보드 퀵처리 등)
if (!empty($_SESSION['flash_success'])) { $flash_messages[] = $_SESSION['flash_success']; unset($_SESSION['flash_success']); }
if (!empty($_SESSION['flash_error']))   { $flash_messages[] = $_SESSION['flash_error'];   unset($_SESSION['flash_error']); }

/**
 * 안전모드 퀵처리: user_transactions만 업데이트
 * mark: deposit_done | withdrawal_done
 */
function quick_mark_tx(mysqli $conn, int $tx_id, string $mark): array {
    $allowed = ['deposit_done', 'withdrawal_done'];
    if (!in_array($mark, $allowed, true)) return [false, "잘못된 요청입니다. (mark)"];

    try {
        $conn->begin_transaction();

        $before = fetch_tx_row($conn, $tx_id);
        if ($mark === 'deposit_done') {
            $stmt = $conn->prepare("UPDATE user_transactions SET deposit_status = '1' WHERE id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE user_transactions SET withdrawal_status = '1' WHERE id = ?");
        }
        $stmt->bind_param("i", $tx_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected < 1) {
            $conn->rollback();
            return [false, "처리 실패: 대상 거래를 찾지 못했습니다. (id={$tx_id})"];
        }

        $after = fetch_tx_row($conn, $tx_id);
        audit_log($conn, 'quick_mark', 'user_transactions', $tx_id, $before, $after, ['mark' => $mark, 'safe_mode' => true]);
        $conn->commit();
        return [true, ($mark==='deposit_done' ? '입금완료' : '출금완료') . " 처리 완료 (tx_id={$tx_id})"];
    } catch (mysqli_sql_exception $e) {
        try { $conn->rollback(); } catch (Throwable $t) {}
        return [false, "처리 실패 (DB 오류): " . $e->getMessage()];
    }
}


/**
 * 거래 1건 삭제 (FK: transaction_distribution.tx_id)
 */
function delete_tx_safely(mysqli $conn, int $tx_id): array {
    try {
        $conn->begin_transaction();

        $before = fetch_tx_row($conn, $tx_id);

        // transaction_distribution 먼저 제거
        $stmt = $conn->prepare("DELETE FROM transaction_distribution WHERE tx_id = ?");
        $stmt->bind_param("i", $tx_id);
        $stmt->execute();
        $stmt->close();

        // 본 거래 삭제
        $stmt = $conn->prepare("DELETE FROM user_transactions WHERE id = ?");
        $stmt->bind_param("i", $tx_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected < 1) {
            $conn->rollback();
            return [false, "삭제 실패: 대상 거래를 찾지 못했습니다. (id={$tx_id})"];
        }

        audit_log($conn, 'delete', 'user_transactions', $tx_id, $before, null);
        $conn->commit();
        return [true, "삭제 완료: 거래(id={$tx_id}) 및 관련 분배 데이터가 삭제되었습니다."];

    } catch (mysqli_sql_exception $e) {
        try { $conn->rollback(); } catch (Throwable $t) {}
        return [false, "삭제 실패 (DB 오류): " . $e->getMessage()];
    }
}

// ✅ 등록/수정/삭제 처리
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    try {
        // ✅ 대시보드 '퀵처리' (안전모드: user_transactions만 업데이트)
        if ($_POST['action'] === 'quick_mark' && isset($_POST['id'], $_POST['mark'])) {
            $id = intval($_POST['id']);
            $mark = (string)$_POST['mark'];
            [$ok, $msg] = quick_mark_tx($conn, $id, $mark);

            if ($ok) $_SESSION['flash_success'] = $msg;
            else     $_SESSION['flash_error']   = $msg;

            $return = $_POST['return'] ?? '';
            if (is_string($return) && $return !== '' && preg_match('/^[A-Za-z0-9_\-\.\?\=&%\/]+$/', $return)) {
                header("Location: " . $return);
                exit;
            }
            header("Location: user_transactions.php?focus_id=" . urlencode((string)$id));
            exit;
        }


        if ($_POST['action'] === "create") {
            $user_id     = intval($_POST['user_id']);
            $tx_date     = $_POST['tx_date'];
            $xm_value    = floatval($_POST['xm_value']);
            $ultima_value= floatval($_POST['ultima_value']);
            $pair        = $_POST['pair'];
            $deposit_status    = intval($_POST['deposit_status']);
            $withdrawal_status = intval($_POST['withdrawal_status']);
            $profit_loss       = floatval($_POST['profit_loss']);
            $etc_note    = $_POST['etc_note'];
            $code_value  = $_POST['code_value'];

            $stmt = $conn->prepare("INSERT INTO user_transactions (user_id, tx_date, xm_value, ultima_value, pair, deposit_status, withdrawal_status, profit_loss, etc_note, code_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddsiidss", $user_id, $tx_date, $xm_value, $ultima_value, $pair, $deposit_status, $withdrawal_status, $profit_loss, $etc_note, $code_value);
            $stmt->execute();
            $new_id = (int)$conn->insert_id;
            $stmt->close();
            $flash_messages[] = "등록 완료: 거래내역이 생성되었습니다.";

            $after = fetch_tx_row($conn, $new_id);
            audit_log($conn, 'create', 'user_transactions', $new_id, null, $after);

        } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
            $id          = intval($_POST['id']);
            $before = fetch_tx_row($conn, $id);
            $user_id     = intval($_POST['user_id']);
            $tx_date     = $_POST['tx_date'];
            $xm_value    = floatval($_POST['xm_value']);
            $ultima_value= floatval($_POST['ultima_value']);
            $pair        = $_POST['pair'];
            $deposit_status    = intval($_POST['deposit_status']);
            $withdrawal_status = intval($_POST['withdrawal_status']);
            $profit_loss       = floatval($_POST['profit_loss']);
            $etc_note    = $_POST['etc_note'];
            $code_value  = $_POST['code_value'];

            $stmt = $conn->prepare("UPDATE user_transactions SET user_id=?, tx_date=?, xm_value=?, ultima_value=?, pair=?, deposit_status=?, withdrawal_status=?, profit_loss=?, etc_note=?, code_value=? WHERE id=?");
            $stmt->bind_param("isddsiidssi", $user_id, $tx_date, $xm_value, $ultima_value, $pair, $deposit_status, $withdrawal_status, $profit_loss, $etc_note, $code_value, $id);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "수정 완료: 거래내역이 저장되었습니다. (id={$id})";

            $after = fetch_tx_row($conn, $id);
            audit_log($conn, 'update', 'user_transactions', $id, $before, $after);

        } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            [$ok, $msg] = delete_tx_safely($conn, $id);
            $flash_messages[] = $msg;

        } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids'])) {
            $ok_cnt = 0;
            $fail_cnt = 0;
            foreach ($_POST['ids'] as $id) {
                $id = intval($id);
                [$ok, $msg] = delete_tx_safely($conn, $id);
                if ($ok) $ok_cnt++; else $fail_cnt++;
                $flash_messages[] = $msg;
            }
            $flash_messages[] = "일괄삭제 결과: 성공 {$ok_cnt}건 / 실패 {$fail_cnt}건";
            audit_log($conn, 'bulk_delete', 'user_transactions', null, null, null, ['ok' => $ok_cnt, 'fail' => $fail_cnt, 'ids' => array_map('intval', (array)$_POST['ids'])]);
        }

    } catch (mysqli_sql_exception $e) {
        $flash_messages[] = "처리 실패 (DB 오류): " . $e->getMessage();
    }
}

// ✅ 목록 불러오기 (필터/기간)
$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all','withdrawal_pending','deposit_pending','withdrawn'];
if (!in_array($filter, $allowed_filters, true)) $filter = 'all';

$today = date('Y-m-d');
$start = $_GET['start'] ?? $today;
$end   = $_GET['end'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = $today;
if ($start > $end) { $tmp=$start; $start=$end; $end=$tmp; }

$q = trim($_GET['q'] ?? '');
$focus_id = isset($_GET['focus_id']) ? intval($_GET['focus_id']) : 0;

$where = [];
$types = '';
$params = [];

if ($start && $end) {
    $where[] = "tx_date BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $start;
    $params[] = $end;
}

if ($filter === 'withdrawal_pending') {
    $where[] = "deposit_status > 0 AND withdrawal_status = 0";
} elseif ($filter === 'deposit_pending') {
    $where[] = "(deposit_status IS NULL OR deposit_status = 0)";
} elseif ($filter === 'withdrawn') {
    $where[] = "withdrawal_status > 0";
}

if ($q !== '') {
    if (ctype_digit($q)) {
        $where[] = "user_id = ?";
        $types .= "i";
        $params[] = intval($q);
    } else {
        $where[] = "code_value LIKE ?";
        $types .= "s";
        $params[] = '%' . $q . '%';
    }
}

$sql = "SELECT * FROM user_transactions";
if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY tx_date DESC, id DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<?php admin_render_header('회원 거래내역 관리 (user_transactions)'); ?>

<?php if (!empty($flash_messages)): ?>
    <div class="flash-box">
        <strong>처리 결과</strong>
        <ul>
            <?php foreach ($flash_messages as $m): ?>
                <li><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<style>
/* users.php와 동일한 톤(간단 카드+테이블) */
.u-toolbar{display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; margin:10px 0 12px;}
.u-toolbar .left{display:flex; flex-wrap:wrap; gap:8px; align-items:center;}
.u-toolbar input[type="text"], .u-toolbar select{padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
.u-card{background:#fff; border:1px solid #eee; border-radius:14px; padding:12px;}
.u-table{width:100%; border-collapse:collapse;}
.u-table th{position:sticky; top:0; background:#fafafa; z-index:1; text-align:left; font-size:12px; color:#555; border-bottom:1px solid #eee; padding:10px 8px; white-space:nowrap;}
.u-table td{border-bottom:1px solid #f0f0f0; padding:10px 8px; vertical-align:top;}
.u-table tr:hover td{background:#fcfcff;}
.u-mini{font-size:12px; color:#666;}
.u-actions{display:flex; gap:6px; align-items:center; justify-content:flex-end; flex-wrap:wrap;}
.btnx{border:1px solid #000000ff; background:#fff; padding:7px 10px; border-radius:10px; cursor:pointer;}
.btnx-danger{border-color:#ffd0d0; background:#fff5f5; color:#b00020;}
.u-editgrid{display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:8px;}
.u-editgrid input,.u-editgrid select{width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
@media (max-width:1100px){ .u-editgrid{grid-template-columns:repeat(3, minmax(0,1fr));} }
@media (max-width:640px){ .u-editgrid{grid-template-columns:repeat(2, minmax(0,1fr));} }
</style>

<div class="u-toolbar">
    <div class="left">
        <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <select name="filter">
                <option value="all" <?= $filter==='all'?'selected':'' ?>>전체</option>
                <option value="withdrawal_pending" <?= $filter==='withdrawal_pending'?'selected':'' ?>>미출금</option>
                <option value="deposit_pending" <?= $filter==='deposit_pending'?'selected':'' ?>>미입금</option>
                <option value="withdrawn" <?= $filter==='withdrawn'?'selected':'' ?>>출금완료</option>
            </select>
            <input type="date" name="start" value="<?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="date" name="end" value="<?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="user_id 또는 code_value 검색" />
            <button class="btnx" type="submit">조회</button>
            <a class="btnx" href="user_transactions.php">초기화</a>
        </form>
        <span class="u-mini">* focus_id 지정 시 해당 행 강조</span>
    </div>

    <details class="u-card" style="padding:10px 12px;">
        <summary style="cursor:pointer; font-weight:700;">+ 거래내역 등록</summary>
        <div style="margin-top:10px;">
            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create">
                <div class="u-editgrid">
                    <input type="number" name="user_id" placeholder="User ID" required>
                    <input type="date" name="tx_date" required>
                    <input type="text" name="xm_value" placeholder="XM Value">
                    <input type="text" name="ultima_value" placeholder="Ultima Value">
                    <input type="text" name="pair" placeholder="Pair">
                    <input type="number" name="deposit_status" placeholder="deposit_status(0/1)">
                    <input type="number" name="withdrawal_status" placeholder="withdrawal_status(0/1)">
                    <input type="text" name="profit_loss" placeholder="profit_loss">
                    <input type="text" name="etc_note" placeholder="etc_note">
                    <input type="text" name="code_value" placeholder="code_value" required>
                </div>
                <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                    <button class="btnx" type="submit">등록</button>
                </div>
            </form>
        </div>
    </details>
</div>

<div class="u-card">
    <div style="display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:10px;">
        <div class="u-mini">선택 삭제는 체크박스 선택 후 버튼을 누르세요.</div>
        <form method="POST" id="bulkForm" onsubmit="return confirm('선택한 거래내역을 정말 삭제하시겠습니까?');" style="margin:0;">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="bulk_delete">
            <span id="bulkHidden"></span>
            <button class="btnx btnx-danger" type="submit">선택 삭제</button>
        </form>
    </div>

    <div style="overflow-x:auto;">
        <table class="u-table">
            <thead>
                <tr>
                    <th style="width:44px;"><input type="checkbox" id="chkAll"></th>
                    <th style="width:70px;">ID</th>
                    <th style="width:90px;">User ID</th>
                    <th style="width:130px;">Date</th>
                    <th>XM</th>
                    <th>Ultima</th>
                    <th>Pair</th>
                    <th style="width:90px;">Deposit</th>
                    <th style="width:90px;">Withdrawal</th>
                    <th>PNL</th>
                    <th>Note</th>
                    <th>Code</th>
                    <th style="width:240px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php $is_focus = ($focus_id && (int)$row['id'] === (int)$focus_id); ?>
                    <tr <?= $is_focus ? 'style="outline:2px solid #2563eb;background:#f5f9ff"' : '' ?>>
                        <td><input class="rowChk" type="checkbox" value="<?= (int)$row['id'] ?>"></td>
                        <td class="mono"><?= (int)$row['id'] ?></td>
                        <td class="mono"><?= (int)$row['user_id'] ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['tx_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['xm_value'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['ultima_value'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['pair'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['deposit_status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['withdrawal_status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['profit_loss'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['etc_note'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['code_value'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align:right;">
                            <div class="u-actions">
                                <details>
                                    <summary class="btnx" style="list-style:none;">수정</summary>
                                    <div class="u-card" style="margin-top:8px;">
                                        <form method="POST">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                            <div class="u-editgrid">
                                                <input type="number" name="user_id" value="<?= (int)$row['user_id'] ?>" required>
                                                <input type="date" name="tx_date" value="<?= htmlspecialchars((string)$row['tx_date'], ENT_QUOTES, 'UTF-8') ?>" required>
                                                <input type="text" name="xm_value" value="<?= htmlspecialchars((string)$row['xm_value'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="text" name="ultima_value" value="<?= htmlspecialchars((string)$row['ultima_value'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="text" name="pair" value="<?= htmlspecialchars((string)$row['pair'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="number" name="deposit_status" value="<?= htmlspecialchars((string)$row['deposit_status'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="number" name="withdrawal_status" value="<?= htmlspecialchars((string)$row['withdrawal_status'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="text" name="profit_loss" value="<?= htmlspecialchars((string)$row['profit_loss'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="text" name="etc_note" value="<?= htmlspecialchars((string)$row['etc_note'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="text" name="code_value" value="<?= htmlspecialchars((string)$row['code_value'], ENT_QUOTES, 'UTF-8') ?>" required>
                                            </div>
                                            <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                                                <button class="btnx" type="submit">저장</button>
                                            </div>
                                        </form>
                                    </div>
                                </details>

                                <form method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');" style="margin:0;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button class="btnx btnx-danger" type="submit">삭제</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const chkAll = document.getElementById('chkAll');
const rowChks = () => Array.from(document.querySelectorAll('.rowChk'));
chkAll?.addEventListener('change', () => rowChks().forEach(chk => chk.checked = chkAll.checked));

document.getElementById('bulkForm')?.addEventListener('submit', (e) => {
  const ids = rowChks().filter(c => c.checked).map(c => c.value);
  if (ids.length < 1) { alert('선택된 항목이 없습니다.'); e.preventDefault(); return; }
  const box = document.getElementById('bulkHidden');
  box.innerHTML = '';
  ids.forEach(v => {
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'ids[]';
    inp.value = v;
    box.appendChild(inp);
  });
});
</script>

<?php admin_render_footer(); ?>