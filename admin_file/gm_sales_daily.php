<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';
$flash_messages = [];
function flash_add(array &$flash_messages, string $msg): void { $flash_messages[] = $msg; }

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === "create") {
            $region           = $_POST['region'];
            $sales_amount     = floatval($_POST['sales_amount']);
            $sales_date       = $_POST['sales_date'];
            $user_id          = intval($_POST['user_id']);
            $sales_percentage = floatval($_POST['sales_percentage']);
            $settled          = intval($_POST['settled']);
            $settled_at       = $_POST['settled_at'];

            $stmt = $conn->prepare("INSERT INTO gm_sales_daily (region, sales_amount, sales_date, user_id, sales_percentage, settled, settled_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsidis", $region, $sales_amount, $sales_date, $user_id, $sales_percentage, $settled, $settled_at);
            $stmt->execute();
            $stmt->close();

            flash_add($flash_messages, "등록 완료");

        } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
            $id               = intval($_POST['id']);
            $region           = $_POST['region'];
            $sales_amount     = floatval($_POST['sales_amount']);
            $sales_date       = $_POST['sales_date'];
            $user_id          = intval($_POST['user_id']);
            $sales_percentage = floatval($_POST['sales_percentage']);
            $settled          = intval($_POST['settled']);
            $settled_at       = $_POST['settled_at'];

            $stmt = $conn->prepare("UPDATE gm_sales_daily SET region=?, sales_amount=?, sales_date=?, user_id=?, sales_percentage=?, settled=?, settled_at=? WHERE id=?");
            $stmt->bind_param("sdsidisi", $region, $sales_amount, $sales_date, $user_id, $sales_percentage, $settled, $settled_at, $id);
            $stmt->execute();
            $stmt->close();

            flash_add($flash_messages, "수정 완료 (id={$id})");

        } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
            $id = intval($_POST['id']);

            $conn->begin_transaction();
            $stmt = $conn->prepare("DELETE FROM gm_sales_daily WHERE id=?");
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

                $stmt = $conn->prepare("DELETE FROM gm_sales_daily WHERE id=?");
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

$result = $conn->query("SELECT * FROM gm_sales_daily ORDER BY id ASC");
?>

<?php admin_render_header('GM 매출 관리 (gm_sales_daily)'); ?>

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
  <h2 style="margin-top:0;">GM 매출 등록</h2>
  <form method="POST">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="create">

    <div class="form-row">
      <div>
        <label>지역</label>
        <input type="text" name="region" required>
      </div>
      <div>
        <label>매출 금액</label>
        <input type="text" name="sales_amount" required>
      </div>
      <div>
        <label>매출 날짜</label>
        <input type="date" name="sales_date" required>
      </div>
      <div>
        <label>User ID</label>
        <input type="number" name="user_id" required>
      </div>
      <div>
        <label>매출 퍼센트(%)</label>
        <input type="text" name="sales_percentage" required>
      </div>
      <div>
        <label>정산 여부</label>
        <select name="settled" required>
          <option value="0">미정산</option>
          <option value="1">정산</option>
        </select>
      </div>
      <div>
        <label>정산일시</label>
        <input type="datetime-local" name="settled_at">
      </div>
      <div style="min-width:auto;">
        <button type="submit" class="btn btn-primary">등록</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
    <h2 style="margin:0;">GM 매출 목록</h2>
    <div class="pill">총 <?= number_format($result->num_rows) ?>건</div>
  </div>
  <hr class="sep">

  <form method="POST" onsubmit="return confirm('선택한 매출 내역을 정말 삭제하시겠습니까?');">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="bulk_delete">

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:48px;">선택</th>
            <th style="width:70px;">ID</th>
            <th>지역</th>
            <th class="num">매출 금액</th>
            <th>매출 날짜</th>
            <th>User ID</th>
            <th class="num">매출 퍼센트</th>
            <th>정산</th>
            <th>정산일시</th>
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
            <td><input form="<?= $f_upd ?>" type="text" name="region" value="<?= htmlspecialchars($row['region']) ?>"></td>
            <td class="num"><input form="<?= $f_upd ?>" type="text" name="sales_amount" value="<?= htmlspecialchars($row['sales_amount']) ?>"></td>
            <td><input form="<?= $f_upd ?>" type="date" name="sales_date" value="<?= htmlspecialchars($row['sales_date']) ?>"></td>
            <td><input form="<?= $f_upd ?>" type="number" name="user_id" value="<?= htmlspecialchars($row['user_id']) ?>"></td>
            <td class="num"><input form="<?= $f_upd ?>" type="text" name="sales_percentage" value="<?= htmlspecialchars($row['sales_percentage']) ?>"></td>
            <td>
              <select form="<?= $f_upd ?>" name="settled">
                <option value="0" <?= ((int)$row['settled']===0)?'selected':'' ?>>미정산</option>
                <option value="1" <?= ((int)$row['settled']===1)?'selected':'' ?>>정산</option>
              </select>
            </td>
            <td><input form="<?= $f_upd ?>" type="datetime-local" name="settled_at" value="<?= htmlspecialchars(str_replace(' ', 'T', (string)$row['settled_at'])) ?>"></td>
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
