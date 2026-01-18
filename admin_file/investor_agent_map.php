<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

$flash_messages = [];

// ✅ 등록/수정/삭제 처리
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === "create") {
            $investor_id = (int)($_POST['investor_id'] ?? 0);
            $agent_id    = (int)($_POST['agent_id'] ?? 0);

            $stmt = $conn->prepare("INSERT INTO investor_agent_map (investor_id, agent_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $investor_id, $agent_id);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "등록 완료";

        } elseif ($_POST['action'] === "update" && isset($_POST['id'])) {
            $id          = (int)($_POST['id'] ?? 0);
            $investor_id = (int)($_POST['investor_id'] ?? 0);
            $agent_id    = (int)($_POST['agent_id'] ?? 0);

            $stmt = $conn->prepare("UPDATE investor_agent_map SET investor_id=?, agent_id=? WHERE id=?");
            $stmt->bind_param("iii", $investor_id, $agent_id, $id);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "수정 완료: id={$id}";

        } elseif ($_POST['action'] === "delete" && isset($_POST['id'])) {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM investor_agent_map WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "삭제 완료: id={$id}";

        } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $_POST['ids']), fn($v) => $v > 0));
            $ok = 0;
            $stmt = $conn->prepare("DELETE FROM investor_agent_map WHERE id=?");
            foreach ($ids as $id) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) $ok++;
            }
            $stmt->close();
            $flash_messages[] = "선택 삭제 완료: {$ok}건";
        }
    } catch (mysqli_sql_exception $e) {
        $flash_messages[] = "처리 실패 (DB 오류): " . $e->getMessage();
    }
}

// ✅ 목록 불러오기
$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT * FROM investor_agent_map";
$types = '';
$params = [];
if ($q !== '') {
    if (ctype_digit($q)) {
        $sql .= " WHERE id = ? OR investor_id = ? OR agent_id = ?";
        $types = 'iii';
        $params = [(int)$q, (int)$q, (int)$q];
    }
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

admin_render_header('투자자-에이전트 매핑 관리 (investor_agent_map)');
?>

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
.u-editgrid{display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:8px;}
.u-editgrid input{width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
</style>

<div class="u-toolbar">
    <div class="left">
        <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="검색 (id/investor_id/agent_id)" />
            <button class="btnx" type="submit">조회</button>
            <a class="btnx" href="investor_agent_map.php">초기화</a>
        </form>
        <span class="u-mini">* 목록은 id DESC</span>
    </div>

    <details class="u-card" style="padding:10px 12px;">
        <summary style="cursor:pointer; font-weight:700;">+ 매핑 등록</summary>
        <div style="margin-top:10px;">
            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create">
                <div class="u-editgrid">
                    <input type="number" name="investor_id" placeholder="Investor ID" required>
                    <input type="number" name="agent_id" placeholder="Agent ID" required>
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
        <form method="POST" id="bulkForm" onsubmit="return confirm('선택한 매핑을 정말 삭제하시겠습니까?');" style="margin:0;">
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
                    <th style="width:110px;">Investor</th>
                    <th style="width:110px;">Agent</th>
                    <th style="width:170px;">Created</th>
                    <th style="width:220px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><input class="rowChk" type="checkbox" value="<?= (int)$row['id'] ?>"></td>
                        <td class="mono"><?= (int)$row['id'] ?></td>
                        <td class="mono"><?= (int)$row['investor_id'] ?></td>
                        <td class="mono"><?= (int)$row['agent_id'] ?></td>
                        <td class="mono"><?= htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
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
                                                <input type="number" name="investor_id" value="<?= (int)$row['investor_id'] ?>" required>
                                                <input type="number" name="agent_id" value="<?= (int)$row['agent_id'] ?>" required>
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
  const holder = document.getElementById('bulkHidden');
  holder.innerHTML = '';
  ids.forEach(id => {
    const i = document.createElement('input');
    i.type = 'hidden';
    i.name = 'ids[]';
    i.value = id;
    holder.appendChild(i);
  });
});
</script>

<?php admin_render_footer(); ?>
