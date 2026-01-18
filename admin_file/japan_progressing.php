<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';
// ✅ 등록/수정/삭제/연쇄 삭제 처리
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === "create") {
        $user_id          = intval($_POST['user_id']);
        $tx_date          = $_POST['tx_date'];
        $pair             = $_POST['pair'];
        $deposit_status   = intval($_POST['deposit_status']);
        $withdrawal_status= intval($_POST['withdrawal_status']);
        $profit_loss      = floatval($_POST['profit_loss']);
        $notes            = $_POST['notes'];
        $settled_by       = $_POST['settled_by'];
        $settled_date     = $_POST['settled_date'];

        $stmt = $conn->prepare("INSERT INTO japan_progressing (user_id, tx_date, pair, deposit_status, withdrawal_status, profit_loss, notes, settled_by, settled_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiidsss", $user_id, $tx_date, $pair, $deposit_status, $withdrawal_status, $profit_loss, $notes, $settled_by, $settled_date);
        $stmt->execute();
        $stmt->close();

    } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
        $id               = intval($_POST['id']);
        $user_id          = intval($_POST['user_id']);
        $tx_date          = $_POST['tx_date'];
        $pair             = $_POST['pair'];
        $deposit_status   = intval($_POST['deposit_status']);
        $withdrawal_status= intval($_POST['withdrawal_status']);
        $profit_loss      = floatval($_POST['profit_loss']);
        $notes            = $_POST['notes'];
        $settled_by       = $_POST['settled_by'];
        $settled_date     = $_POST['settled_date'];

        $stmt = $conn->prepare("UPDATE japan_progressing SET user_id=?, tx_date=?, pair=?, deposit_status=?, withdrawal_status=?, profit_loss=?, notes=?, settled_by=?, settled_date=? WHERE id=?");
        $stmt->bind_param("issiidsssi", $user_id, $tx_date, $pair, $deposit_status, $withdrawal_status, $profit_loss, $notes, $settled_by, $settled_date, $id);
        $stmt->execute();
        $stmt->close();

    } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM japan_progressing WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

    } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids'])) {
        foreach ($_POST['ids'] as $id) {
            $id = intval($id);
            $conn->query("DELETE FROM japan_progressing WHERE id=$id");
        }
    }
}

// ✅ 목록 불러오기
$result = $conn->query("SELECT * FROM japan_progressing ORDER BY id ASC");
?>

?>
<?php admin_render_header('일본 진행 상황 관리 (japan_progressing)'); ?>
<h2>일본 진행 상황 등록</h2>
    <form method="POST">
    <?= csrf_input() ?>

        <input type="hidden" name="action" value="create">
        회원 ID: <input type="number" name="user_id" required><br>
        거래 날짜: <input type="date" name="tx_date" required><br>
        거래 페어: <input type="text" name="pair"><br>
        입금 상태(0/1): <input type="number" name="deposit_status" value="0"><br>
        출금 상태(0/1): <input type="number" name="withdrawal_status" value="0"><br>
        손익: <input type="text" name="profit_loss"><br>
        메모: <input type="text" name="notes"><br>
        정산자: <input type="text" name="settled_by"><br>
        정산일: <input type="datetime-local" name="settled_date"><br>
        <button type="submit">등록</button>
    </form>

    <h2>일본 진행 상황 목록</h2>
    <form method="POST" onsubmit="return confirm('선택한 진행 상황을 정말 삭제하시겠습니까?');">
    <?= csrf_input() ?>

        <input type="hidden" name="action" value="bulk_delete">
        <table>
            <tr>
                <th>선택</th><th>ID</th><th>User ID</th><th>거래날짜</th><th>페어</th><th>입금상태</th><th>출금상태</th><th>손익</th><th>메모</th><th>정산자</th><th>정산일</th><th>생성일</th><th>수정</th><th>삭제</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="ids[]" value="<?= htmlspecialchars($row['id']) ?>"></td>
                <td><?= htmlspecialchars($row['id']) ?></td>
                <form method="POST">
    <?= csrf_input() ?>

                    <td><input type="number" name="user_id" value="<?= htmlspecialchars($row['user_id']) ?>"></td>
                    <td><input type="date" name="tx_date" value="<?= htmlspecialchars($row['tx_date']) ?>"></td>
                    <td><input type="text" name="pair" value="<?= htmlspecialchars($row['pair']) ?>"></td>
                    <td><input type="number" name="deposit_status" value="<?= htmlspecialchars($row['deposit_status']) ?>"></td>
                    <td><input type="number" name="withdrawal_status" value="<?= htmlspecialchars($row['withdrawal_status']) ?>"></td>
                    <td><input type="text" name="profit_loss" value="<?= htmlspecialchars($row['profit_loss']) ?>"></td>
                    <td><input type="text" name="notes" value="<?= htmlspecialchars($row['notes']) ?>"></td>
                    <td><input type="text" name="settled_by" value="<?= htmlspecialchars($row['settled_by']) ?>"></td>
                    <td><input type="datetime-local" name="settled_date" value="<?= htmlspecialchars($row['settled_date']) ?>"></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
                        <input type="hidden" name="action" value="update">
                        <button type="submit">수정</button>
                    </td>
                </form>
                <form method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
    <?= csrf_input() ?>

    <td>
        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
        <input type="hidden" name="action" value="delete">
        <button type="submit" style="color:red;">삭제</button>
    </td>
</form>
</tr>
<?php endwhile; ?>
</table>
<br>
<button type="submit" style="color:red;">선택 진행 상황 삭제</button>
</form>
<?php admin_render_footer(); ?>