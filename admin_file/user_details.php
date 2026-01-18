<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

$flash_messages = [];

// ✅ 등록/수정/삭제 처리
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === "create") {
            $user_id       = (int)($_POST['user_id'] ?? 0);
            $wallet        = trim((string)($_POST['wallet_address'] ?? ''));
            $broker_id     = trim((string)($_POST['broker_id'] ?? ''));
            $broker_pw     = trim((string)($_POST['broker_pw'] ?? ''));
            $xm_id         = trim((string)($_POST['xm_id'] ?? ''));
            $xm_pw         = trim((string)($_POST['xm_pw'] ?? ''));
            $xm_server     = trim((string)($_POST['xm_server'] ?? ''));
            $ultima_id     = trim((string)($_POST['ultima_id'] ?? ''));
            $ultima_pw     = trim((string)($_POST['ultima_pw'] ?? ''));
            $ultima_server = trim((string)($_POST['ultima_server'] ?? ''));
            $referral      = trim((string)($_POST['referral_code'] ?? ''));

            $stmt = $conn->prepare(
                "INSERT INTO user_details (user_id, wallet_address, broker_id, broker_pw, xm_id, xm_pw, xm_server, ultima_id, ultima_pw, ultima_server, referral_code)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issssssssss", $user_id, $wallet, $broker_id, $broker_pw, $xm_id, $xm_pw, $xm_server, $ultima_id, $ultima_pw, $ultima_server, $referral);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "등록 완료: user_id={$user_id}";

        } elseif ($_POST['action'] === "update" && isset($_POST['user_id'])) {
            $user_id       = (int)($_POST['user_id'] ?? 0);
            $wallet        = trim((string)($_POST['wallet_address'] ?? ''));
            $broker_id     = trim((string)($_POST['broker_id'] ?? ''));
            $broker_pw     = trim((string)($_POST['broker_pw'] ?? ''));
            $xm_id         = trim((string)($_POST['xm_id'] ?? ''));
            $xm_pw         = trim((string)($_POST['xm_pw'] ?? ''));
            $xm_server     = trim((string)($_POST['xm_server'] ?? ''));
            $ultima_id     = trim((string)($_POST['ultima_id'] ?? ''));
            $ultima_pw     = trim((string)($_POST['ultima_pw'] ?? ''));
            $ultima_server = trim((string)($_POST['ultima_server'] ?? ''));
            $referral      = trim((string)($_POST['referral_code'] ?? ''));

            $stmt = $conn->prepare(
                "UPDATE user_details
                    SET wallet_address=?, broker_id=?, broker_pw=?, xm_id=?, xm_pw=?, xm_server=?, ultima_id=?, ultima_pw=?, ultima_server=?, referral_code=?
                  WHERE user_id=?"
            );
            $stmt->bind_param("ssssssssssi", $wallet, $broker_id, $broker_pw, $xm_id, $xm_pw, $xm_server, $ultima_id, $ultima_pw, $ultima_server, $referral, $user_id);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "수정 완료: user_id={$user_id}";

        } elseif ($_POST['action'] === "delete" && isset($_POST['user_id'])) {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM user_details WHERE user_id=?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $flash_messages[] = "삭제 완료: user_id={$user_id}";

        } elseif ($_POST['action'] === "bulk_delete" && !empty($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $_POST['ids']), fn($v) => $v > 0));
            $ok = 0;
            $stmt = $conn->prepare("DELETE FROM user_details WHERE user_id=?");
            foreach ($ids as $user_id) {
                $stmt->bind_param("i", $user_id);
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
$sql = "SELECT * FROM user_details";
$params = [];
$types = '';
if ($q !== '') {
    // user_id 검색 (숫자)
    if (ctype_digit($q)) {
        $sql .= " WHERE user_id = ?";
        $types .= 'i';
        $params[] = (int)$q;
    } else {
        $sql .= " WHERE wallet_address LIKE ? OR broker_id LIKE ? OR xm_id LIKE ? OR ultima_id LIKE ? OR referral_code LIKE ?";
        $like = "%{$q}%";
        $types .= 'sssss';
        $params = [$like,$like,$like,$like,$like];
    }
}
$sql .= " ORDER BY user_id ASC";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

admin_render_header('회원 상세 관리 (user_details)');
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
    /* users.php 스타일을 user_details에도 동일 적용 */
    .u-toolbar{display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between; margin:10px 0 12px;}
    .u-toolbar .left{display:flex; flex-wrap:wrap; gap:8px; align-items:center;}
    .u-toolbar input[type="text"], .u-toolbar input[type="number"], .u-toolbar select{padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
    .u-card{background:#fff; border:1px solid #eee; border-radius:14px; padding:12px;}
    .u-table{width:100%; border-collapse:collapse;}
    .u-table th{position:sticky; top:0; background:#fafafa; z-index:1; text-align:left; font-size:12px; color:#555; border-bottom:1px solid #eee; padding:10px 8px; white-space:nowrap;}
    .u-table td{border-bottom:1px solid #f0f0f0; padding:10px 8px; vertical-align:top;}
    .u-table tr:hover td{background:#fcfcff;}
    .u-mini{font-size:12px; color:#666;}
    .u-actions{display:flex; gap:6px; align-items:center; justify-content:flex-end; flex-wrap:wrap;}
    .btn{border:1px solid #000000ff; background:#fff; padding:7px 10px; border-radius:10px; cursor:pointer;}
    .btn-danger{border-color:#ffd0d0; background:#fff5f5; color:#b00020;}
    .u-editgrid{display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:8px;}
    .u-editgrid input{width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:10px; background:#fff;}
    @media (max-width:1100px){ .u-editgrid{grid-template-columns:repeat(3, minmax(0,1fr));} }
    @media (max-width:640px){ .u-editgrid{grid-template-columns:repeat(2, minmax(0,1fr));} }
    details.u-details summary{cursor:pointer; font-weight:700;}
</style>

<div class="u-toolbar">
    <div class="left">
        <form method="GET" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="검색 (user_id / 지갑 / 브로커 / XM / ULTIMA / 추천코드)" />
            <button class="btn" type="submit">조회</button>
            <a class="btn" href="user_details.php">초기화</a>
        </form>
        <span class="u-mini">* user_details 테이블 관리</span>
    </div>

    <details class="u-card u-details" style="padding:10px 12px;">
        <summary>+ 신규 등록</summary>
        <div style="margin-top:10px;">
            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create">
                <div class="u-editgrid">
                    <input type="number" name="user_id" placeholder="회원 ID" required>
                    <input type="text" name="wallet_address" placeholder="지갑주소">
                    <input type="text" name="broker_id" placeholder="Broker ID">
                    <input type="text" name="broker_pw" placeholder="Broker PW">
                    <input type="text" name="xm_id" placeholder="XM ID">
                    <input type="text" name="xm_pw" placeholder="XM PW">
                    <input type="text" name="xm_server" placeholder="XM Server">
                    <input type="text" name="ultima_id" placeholder="Ultima ID">
                    <input type="text" name="ultima_pw" placeholder="Ultima PW">
                    <input type="text" name="ultima_server" placeholder="Ultima Server">
                    <input type="text" name="referral_code" placeholder="추천코드">
                </div>
                <div style="margin-top:10px;">
                    <?php
                        $__wallet_warning = dirname(__DIR__) . '/includes/wallet_warning.php';
                        if (is_file($__wallet_warning)) include $__wallet_warning;
                    ?>
                </div>
                <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                    <button class="btn" type="submit">등록</button>
                </div>
            </form>
        </div>
    </details>
</div>

<!-- ✅ 일괄삭제 폼(체크박스는 테이블에 있고, 제출 시 JS로 ids[]를 주입) -->
<form method="POST" id="bulkForm" onsubmit="return submitBulkDelete();" style="margin-bottom:10px;">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="bulk_delete">
    <div class="u-card" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <span class="u-mini">체크된 항목을 선택 삭제합니다.</span>
        <button class="btn btn-danger" type="submit">선택 삭제</button>
    </div>
</form>

<div class="u-card">
    <div style="overflow-x:auto;">
        <table class="u-table">
            <thead>
                <tr>
                    <th style="width:44px;"><input type="checkbox" id="chkAll"></th>
                    <th style="width:80px;">User ID</th>
                    <th>지갑주소</th>
                    <th>Broker ID</th>
                    <th>Broker PW</th>
                    <th>XM ID</th>
                    <th>XM PW</th>
                    <th>XM Server</th>
                    <th>Ultima ID</th>
                    <th>Ultima PW</th>
                    <th>Ultima Server</th>
                    <th>추천코드</th>
                    <th style="width:220px; text-align:right;">작업</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" class="rowChk" value="<?= (int)$row['user_id'] ?>"></td>
                    <td><?= (int)$row['user_id'] ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['wallet_address']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['broker_id']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['broker_pw']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['xm_id']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['xm_pw']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['xm_server']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['ultima_id']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['ultima_pw']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['ultima_server']) ?></td>
                    <td class="mono"><?= htmlspecialchars((string)$row['referral_code']) ?></td>
                    <td style="text-align:right;">
                        <div class="u-actions">
                            <details class="u-details" style="display:inline-block;">
                                <summary class="btn">수정</summary>
                                <div class="u-card" style="margin-top:8px; padding:10px; max-width:920px;">
                                    <form method="POST">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                                        <div class="u-editgrid">
                                            <input type="text" name="wallet_address" value="<?= htmlspecialchars((string)$row['wallet_address']) ?>" placeholder="지갑주소">
                                            <input type="text" name="broker_id" value="<?= htmlspecialchars((string)$row['broker_id']) ?>" placeholder="Broker ID">
                                            <input type="text" name="broker_pw" value="<?= htmlspecialchars((string)$row['broker_pw']) ?>" placeholder="Broker PW">
                                            <input type="text" name="xm_id" value="<?= htmlspecialchars((string)$row['xm_id']) ?>" placeholder="XM ID">
                                            <input type="text" name="xm_pw" value="<?= htmlspecialchars((string)$row['xm_pw']) ?>" placeholder="XM PW">
                                            <input type="text" name="xm_server" value="<?= htmlspecialchars((string)$row['xm_server']) ?>" placeholder="XM Server">
                                            <input type="text" name="ultima_id" value="<?= htmlspecialchars((string)$row['ultima_id']) ?>" placeholder="Ultima ID">
                                            <input type="text" name="ultima_pw" value="<?= htmlspecialchars((string)$row['ultima_pw']) ?>" placeholder="Ultima PW">
                                            <input type="text" name="ultima_server" value="<?= htmlspecialchars((string)$row['ultima_server']) ?>" placeholder="Ultima Server">
                                            <input type="text" name="referral_code" value="<?= htmlspecialchars((string)$row['referral_code']) ?>" placeholder="추천코드">
                                        </div>
                                        <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                                            <button class="btn" type="submit">저장</button>
                                        </div>
                                    </form>
                                </div>
                            </details>

                            <form method="POST" onsubmit="return confirm('정말 삭제하시겠습니까? (user_id=<?= (int)$row['user_id'] ?>)');" style="display:inline;">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
                                <button class="btn btn-danger" type="submit">삭제</button>
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
    // 전체 선택
    const chkAll = document.getElementById('chkAll');
    chkAll?.addEventListener('change', () => {
        document.querySelectorAll('.rowChk').forEach(chk => { chk.checked = chkAll.checked; });
    });

    function submitBulkDelete(){
        const form = document.getElementById('bulkForm');
        // 기존 ids 제거
        form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        const checked = Array.from(document.querySelectorAll('.rowChk')).filter(c => c.checked).map(c => c.value);
        if (checked.length < 1) {
            alert('삭제할 항목을 선택하세요.');
            return false;
        }
        if (!confirm(`선택한 ${checked.length}건을 정말 삭제하시겠습니까?`)) return false;
        checked.forEach(v => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'ids[]';
            i.value = v;
            form.appendChild(i);
        });
        return true;
    }
</script>

<?php
$stmt->close();
admin_render_footer();
?>
