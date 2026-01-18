<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';
$flash_messages = [];
function flash_add(array &$flash_messages, string $msg): void { $flash_messages[] = $msg; }

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === "create") {
            $gm_id          = intval($_POST['gm_id']);
            $region         = $_POST['region'];
            $deposit_amount = floatval($_POST['deposit_amount']);
            $settle_date    = $_POST['settle_date'];

            $stmt = $conn->prepare("INSERT INTO gm_deposits (gm_id, region, deposit_amount, settle_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $gm_id, $region, $deposit_amount, $settle_date);
            $stmt->execute();
            $stmt->close();

            flash_add($flash_messages, "등록 완료");

        } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
            $id             = intval($_POST['id']);
            $gm_id          = intval($_POST['gm_id']);
            $region         = $_POST['region'];
            $deposit_amount = floatval($_POST['deposit_amount']);
            $settle_date    = $_POST['settle_date'];

            $stmt = $conn->prepare("UPDATE gm_deposits SET gm_id=?, region=?, deposit_amount=?, settle_date=? WHERE id=?");
            $stmt->bind_param("isdsi", $gm_id, $region, $deposit_amount, $settle_date, $id);
            $stmt->execute();
            $stmt->close();

            flash_add($flash_messages, "수정 완료 (id={$id})");

        } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
            $id = intval($_POST['id']);

            $conn->begin_transaction();
            $stmt = $conn->prepare("DELETE FROM gm_deposits WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            $conn->commit();

            flash_add($flash_messages, $affected > 0 ? "삭제 완료 (id={$id})" : "삭제 대상 없음 (id={$id})");

        } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $_POST['ids']), fn($v) => $v > 0));
            if (empty($ids)) {
                flash_add($flash_messages, "일괄삭제: 선택된 ID가 없습니다.");
            } else {
                $conn->begin_transaction();

                $stmt = $conn->prepare("DELETE FROM gm_deposits WHERE id=?");
                $deleted = 0;
                foreach ($ids as $id) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $deleted += $stmt->affected_rows > 0 ? 1 : 0;
                }
                $stmt->close();
                $conn->commit();

                $total = count($ids);
                flash_add($flash_messages, "일괄삭제 완료: 요청 {$total}건 / 실제 삭제 {$deleted}건");
            }
        }

    } catch (mysqli_sql_exception $e) {
        try { $conn->rollback(); } catch (Throwable $t) {}
        flash_add($flash_messages, "처리 실패 (DB 오류): " . $e->getMessage());
    }
}

$result = $conn->query("SELECT * FROM gm_deposits ORDER BY id ASC");
?>

<?php admin_render_header('GM 입금 관리 (gm_deposits)'); ?>

<?php if (!empty($flash_messages)): ?>
  <div class="notice" style="margin-bottom:14px;">
    <strong>처리 결과</strong>
    <ul style="margin:8px 0 0 18px;">
      <?php foreach ($flash_messages as $m): ?>
        <li><?= htmlspecialchars($m) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <h2 style="margin-top:0;">GM 입금 등록</h2>
  <form method="POST">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="create">

    <div class="form-row">
      <div>
        <label>GM ID</label>
        <input type="number" name="gm_id" required>
      </div>
      <div>
        <label>지역</label>
        <input type="text" name="region" required>
      </div>
      <div>
        <label>입금 금액</label>
        <input type="text" name="deposit_amount" required>
      </div>
      <div>
        <label>정산 날짜</label>
        <input type="date" name="settle_date" required>
      </div>
      <div style="min-width:auto;">
        <button type="submit" class="btn btn-primary">등록</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
    <h2 style="margin:0;">GM 입금 목록</h2>
    <div class="pill">총 <?= number_format($result->num_rows) ?>건</div>
  </div>
  <hr class="sep">

  <form method="POST" onsubmit="return confirm('선택한 입금 내역을 정말 삭제하시겠습니까?');">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="bulk_delete">

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:48px;">선택</th>
            <th style="width:70px;">ID</th>
            <th>GM ID</th>
            <th>지역</th>
            <th class="num">입금 금액</th>
            <th>정산 날짜</th>
            <th>생성일</th>
            <th style="width:90px;">수정</th>
            <th style="width:90px;">삭제</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $row_forms = [];
          while ($row = $result->fetch_assoc()):
            $rid = (int)$row['id'];
            $f_upd = 'f_upd_' . $rid;
            $f_del = 'f_del_' . $rid;

            $row_forms[] = '<form id="' . $f_upd . '" method="POST">'
              . csrf_input()
              . '<input type="hidden" name="action" value="update">'
              . '<input type="hidden" name="id" value="' . htmlspecialchars((string)$rid) . '">' 
              . '</form>';
            $row_forms[] = '<form id="' . $f_del . '" method="POST" onsubmit="return confirm(\'정말 삭제하시겠습니까?\');">'
              . csrf_input()
              . '<input type="hidden" name="action" value="delete">'
              . '<input type="hidden" name="id" value="' . htmlspecialchars((string)$rid) . '">' 
              . '</form>';
        ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= htmlspecialchars($row['id']) ?>"></td>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><input form="<?= $f_upd ?>" type="number" name="gm_id" value="<?= htmlspecialchars($row['gm_id']) ?>"></td>
            <td><input form="<?= $f_upd ?>" type="text" name="region" value="<?= htmlspecialchars($row['region']) ?>"></td>
            <td class="num"><input form="<?= $f_upd ?>" type="text" name="deposit_amount" value="<?= htmlspecialchars($row['deposit_amount']) ?>"></td>
            <td><input form="<?= $f_upd ?>" type="date" name="settle_date" value="<?= htmlspecialchars($row['settle_date']) ?>"></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td><button form="<?= $f_upd ?>" type="submit" class="btn btn-primary">수정</button></td>
            <td><button form="<?= $f_del ?>" type="submit" class="btn btn-danger">삭제</button></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div style="margin-top:12px; display:flex; gap:10px; justify-content:flex-end;">
      <button type="submit" class="btn btn-danger">선택 삭제</button>
    </div>
  </form>

  <?php if (!empty($row_forms)): ?>
    <div style="display:none;">
      <?= implode("\n", $row_forms) ?>
    </div>
  <?php endif; ?>
</div>

<?php admin_render_footer(); ?>