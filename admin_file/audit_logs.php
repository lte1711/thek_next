<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

admin_render_header('감사 로그 (Audit Logs)');

// ===== Filters =====
$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
$admin_email = isset($_GET['admin_email']) ? trim((string)$_GET['admin_email']) : '';
$start = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
$end = isset($_GET['end']) ? trim((string)$_GET['end']) : '';

$where = [];
$params = [];
$types = '';

if ($action !== '') {
    $where[] = "action = ?";
    $params[] = $action;
    $types .= 's';
}
if ($admin_email !== '') {
    $where[] = "admin_email = ?";
    $params[] = $admin_email;
    $types .= 's';
}
if ($start !== '') {
    $where[] = "created_at >= ?";
    $params[] = $start . ' 00:00:00';
    $types .= 's';
}
if ($end !== '') {
    $where[] = "created_at <= ?";
    $params[] = $end . ' 23:59:59';
    $types .= 's';
}

$sql = "SELECT id, created_at, admin_email, admin_role, action, target_table, target_id, ip, user_agent, before_json, after_json, extra_json
        FROM audit_logs";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY id DESC LIMIT 200";

$rows = [];
$table_missing = false;
try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    $stmt->close();
} catch (Throwable $e) {
    // audit_logs 테이블이 없으면 안내
    $table_missing = true;
}

// action 목록 (최근 로그 기반)
$action_opts = [];
if (!$table_missing) {
    try {
        $res = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC LIMIT 50");
        while ($r = $res->fetch_assoc()) $action_opts[] = (string)$r['action'];
    } catch (Throwable $e) {}
}

?>

<div class="card">
    <h2 style="margin-top:0">필터</h2>
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end">
        <div>
            <label class="muted">Action</label><br>
            <select name="action" class="input" style="min-width:180px">
                <option value="">(전체)</option>
                <?php foreach ($action_opts as $opt): ?>
                    <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>" <?= $action===$opt?'selected':'' ?>><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="muted">Admin Email</label><br>
            <input class="input" type="text" name="admin_email" value="<?= htmlspecialchars($admin_email, ENT_QUOTES, 'UTF-8') ?>" placeholder="lte1711@gmail.com">
        </div>
        <div>
            <label class="muted">Start</label><br>
            <input class="input" type="date" name="start" value="<?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label class="muted">End</label><br>
            <input class="input" type="date" name="end" value="<?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <button class="btn" type="submit">조회</button>
            <a class="btnlink" href="audit_logs.php" style="margin-left:6px">초기화</a>
        </div>
    </form>

    <?php if ($table_missing): ?>
        <div class="msg" style="margin-top:14px;border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.10)">
            <b>audit_logs 테이블이 아직 없습니다.</b><br>
            먼저 <code>audit_logs_table.sql</code>을 DB에 적용한 뒤 새로고침하세요.
        </div>
    <?php else: ?>
        <p class="muted" style="margin-top:12px">최근 200건 표시 (필터 적용)</p>
        <div style="overflow:auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>일시</th>
                        <th>Admin</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP</th>
                        <th>UA</th>
                        <th>Before/After</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['admin_email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['admin_role'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($r['action'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string)$r['target_table'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($r['target_id'])): ?>
                                    #<?= htmlspecialchars((string)$r['target_id'], ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)$r['ip'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars((string)$r['user_agent'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)$r['user_agent'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="max-width:420px">
                                <details>
                                    <summary class="muted" style="cursor:pointer">JSON 보기</summary>
                                    <pre style="white-space:pre-wrap;word-break:break-word;font-size:12px;line-height:1.35">
BEFORE: <?= htmlspecialchars((string)$r['before_json'], ENT_QUOTES, 'UTF-8') ?>
AFTER : <?= htmlspecialchars((string)$r['after_json'], ENT_QUOTES, 'UTF-8') ?>
EXTRA : <?= htmlspecialchars((string)$r['extra_json'], ENT_QUOTES, 'UTF-8') ?>
                                    </pre>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="9" class="muted">표시할 로그가 없습니다.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
admin_render_footer();
