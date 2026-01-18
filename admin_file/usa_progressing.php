<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';
// ✅ 등록/수정/삭제/연쇄 삭제 처리
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === "create") {
        $stmt = $conn->prepare("INSERT INTO usa_progressing (title, description, progress_date, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $_POST['title'], $_POST['description'], $_POST['progress_date'], $_POST['status']);
        $stmt->execute();
        $stmt->close();

    } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
        $stmt = $conn->prepare("UPDATE usa_progressing SET title=?, description=?, progress_date=?, status=? WHERE id=?");
        $stmt->bind_param("ssssi", $_POST['title'], $_POST['description'], $_POST['progress_date'], $_POST['status'], $_POST['id']);
        $stmt->execute();
        $stmt->close();

    } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
        $stmt = $conn->prepare("DELETE FROM usa_progressing WHERE id=?");
        $stmt->bind_param("i", $_POST['id']);
        $stmt->execute();
        $stmt->close();

    } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids'])) {
        foreach ($_POST['ids'] as $id) {
            $id = intval($id);
            $conn->query("DELETE FROM usa_progressing WHERE id=$id");
        }
    }
}

// ✅ 목록 불러오기
$result = $conn->query("SELECT * FROM usa_progressing ORDER BY id ASC");
?>

?>
<?php admin_render_header('미국 진행 상황 관리 (usa_progressing)'); ?>
<h2>미국 진행 상황 등록</h2>
    <form method="POST">
    <?= csrf_input() ?>

        <input type="hidden" name="action" value="create">
        제목: <input type="text" name="title" required><br>
        설명: <input type="text" name="description"><br>
        진행 날짜: <input type="date" name="progress_date" required><br>
        상태: <input type="text" name="status" required><br>
        <button type="submit">등록</button>
    </form>

    <h2>미국 진행 상황 목록</h2>
    <form method="POST" onsubmit="return confirm('선택한 진행 상황을 정말 삭제하시겠습니까?');">
    <?= csrf_input() ?>

        <input type="hidden" name="action" value="bulk_delete">
        <table>
            <tr>
                <th>선택</th><th>ID</th><th>제목</th><th>설명</th><th>진행 날짜</th><th>상태</th><th>생성일</th><th>수정</th><th>삭제</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><input type="checkbox" name="ids[]" value="<?= $row['id'] ?>"></td>
                <form method="POST">
    <?= csrf_input() ?>

                    <td><?= $row['id'] ?></td>
                    <td><input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>"></td>
                    <td><input type="text" name="description" value="<?= htmlspecialchars($row['description']) ?>"></td>
                    <td><input type="date" name="progress_date" value="<?= $row['progress_date'] ?>"></td>
                    <td><input type="text" name="status" value="<?= htmlspecialchars($row['status']) ?>"></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="update">
                        <button type="submit">수정</button>
                    </td>
                </form>
                <form method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
    <?= csrf_input() ?>

                    <td>
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
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