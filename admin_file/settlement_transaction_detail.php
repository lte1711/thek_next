<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$back = isset($_GET['back']) ? (string)$_GET['back'] : '';

$back_url = 'settlement_transactions.php';
if ($back !== '') {
    $decoded = ltrim(rawurldecode($back), '?');
    if ($decoded !== '') {
        $back_url .= '?' . $decoded;
    }
}

admin_render_header('거래 상세');

if ($id <= 0) {
    echo '<div class="card"><div class="notice" style="border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.06)">'
        . '잘못된 접근입니다. (id가 없습니다)'
        . '</div>'
        . '<div style="margin-top:12px"><a class="btn" href="' . h($back_url) . '">← 목록으로</a></div>'
        . '</div>';
    admin_render_footer();
    exit;
}

$sql = "SELECT
            t.*, 
            u.name, u.username, u.email, u.role, u.sponsor_id,
            d.wallet_address, d.codepay_address,
            d.xm_id, d.xm_server, d.ultima_id, d.ultima_server,
            d.referral_code
        FROM user_transactions t
        JOIN users u ON u.id = t.user_id
        LEFT JOIN user_details d ON d.user_id = u.id
        WHERE t.id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
$row = null;
if ($stmt) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$row) {
    echo '<div class="card"><div class="notice" style="border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.06)">'
        . '해당 거래를 찾을 수 없습니다. (TX ID: ' . h($id) . ')'
        . '</div>'
        . '<div style="margin-top:12px"><a class="btn" href="' . h($back_url) . '">← 목록으로</a></div>'
        . '</div>';
    admin_render_footer();
    exit;
}

$user_label = trim((string)($row['name'] ?? ''));
if ($user_label === '') $user_label = trim((string)($row['username'] ?? ''));
if ($user_label === '') $user_label = trim((string)($row['email'] ?? ''));
if ($user_label === '') $user_label = 'ID ' . (int)$row['user_id'];

$tx_date = (string)($row['tx_date'] ?? '');

?>

<div class="card" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
  <div>
    <div style="font-size:12px; color:var(--muted);">TX ID</div>
    <div style="font-size:22px; font-weight:900; letter-spacing:-.02em;">#<?= h($row['id']) ?></div>
    <div style="margin-top:6px; color:var(--muted); font-size:12px;">거래일: <b><?= h($tx_date) ?></b> · Pair: <b><?= h($row['pair'] ?? '-') ?></b></div>
  </div>
  <div style="display:flex; gap:8px; flex-wrap:wrap;">
    <a class="btn" href="<?= h($back_url) ?>">← 목록으로</a>
    <a class="btn" href="users.php?q=<?= h($row['user_id']) ?>">회원 보기</a>
    <a class="btn" href="user_transactions.php?q=<?= h($row['user_id']) ?>">회원 거래(원본)</a>
  </div>
</div>

<div class="card">
  <div style="font-weight:900; margin-bottom:10px;">요약</div>
  <div style="display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:10px;">
    <div class="notice" style="background:rgba(37,99,235,.05)">
      <div style="font-size:12px; color:var(--muted)">회원</div>
      <div style="font-weight:900; margin-top:2px;"><?= h($user_label) ?></div>
      <div style="font-size:12px; color:var(--muted); margin-top:2px;">User ID: <b><?= h($row['user_id']) ?></b></div>
    </div>
    <div class="notice" style="background:rgba(17,24,39,.03)">
      <div style="font-size:12px; color:var(--muted)">XM</div>
      <div style="font-weight:900; margin-top:2px" class="mono"><?= number_format((float)($row['xm_value'] ?? 0), 2) ?></div>
      <div style="font-size:12px; color:var(--muted); margin-top:2px;">원금: <b class="mono"><?= number_format((float)($row['principal_xm'] ?? 0), 2) ?></b></div>
    </div>
    <div class="notice" style="background:rgba(17,24,39,.03)">
      <div style="font-size:12px; color:var(--muted)">ULTIMA</div>
      <div style="font-weight:900; margin-top:2px" class="mono"><?= number_format((float)($row['ultima_value'] ?? 0), 2) ?></div>
      <div style="font-size:12px; color:var(--muted); margin-top:2px;">원금: <b class="mono"><?= number_format((float)($row['principal_ultima'] ?? 0), 2) ?></b></div>
    </div>
    <div class="notice" style="background:rgba(34,197,94,.06); border-color:rgba(34,197,94,.22)">
      <div style="font-size:12px; color:var(--muted)">Dividend</div>
      <div style="font-weight:900; margin-top:2px" class="mono"><?= number_format((float)($row['dividend_amount'] ?? 0), 2) ?></div>
      <div style="font-size:12px; color:var(--muted); margin-top:2px;">XM/ULT: <b class="mono"><?= number_format((float)($row['xm_dividend'] ?? 0), 2) ?></b> / <b class="mono"><?= number_format((float)($row['ultima_dividend'] ?? 0), 2) ?></b></div>
    </div>
    <div class="notice" style="background:rgba(245,158,11,.06); border-color:rgba(245,158,11,.25)">
      <div style="font-size:12px; color:var(--muted)">정산 상태</div>
      <div style="font-weight:900; margin-top:2px;"><?= ((int)($row['settle_chk'] ?? 0) === 1) ? '✅ 정산 완료' : '⏳ 미정산' ?></div>
      <div style="font-size:12px; color:var(--muted); margin-top:2px;">정산일: <b><?= h($row['settled_date'] ?? '-') ?></b></div>
    </div>
    <div class="notice" style="background:rgba(239,68,68,.04); border-color:rgba(239,68,68,.18)">
      <div style="font-size:12px; color:var(--muted)">체크</div>
      <div style="margin-top:4px; display:flex; gap:6px; flex-wrap:wrap;">
        <span class="pill">입금 <?= ((int)($row['deposit_chk'] ?? 0)===1) ? '✅' : '—' ?></span>
        <span class="pill">출금 <?= ((int)($row['withdrawal_chk'] ?? 0)===1) ? '✅' : '—' ?></span>
        <span class="pill">배당 <?= ((int)($row['dividend_chk'] ?? 0)===1) ? '✅' : '—' ?></span>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div style="font-weight:900; margin-bottom:10px;">거래 상세</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>필드</th>
          <th>값</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>code_value</td><td class="mono"><?= h($row['code_value'] ?? '') ?></td></tr>
        <tr><td>deposit_status</td><td><?= h($row['deposit_status'] ?? '-') ?></td></tr>
        <tr><td>withdrawal_status</td><td><?= h($row['withdrawal_status'] ?? '-') ?></td></tr>
        <tr><td>profit_loss</td><td class="mono"><?= h($row['profit_loss'] ?? '-') ?></td></tr>
        <tr><td>etc_note</td><td><?= nl2br(h($row['etc_note'] ?? '')) ?></td></tr>
        <tr><td>created_at</td><td class="mono"><?= h($row['created_at'] ?? '-') ?></td></tr>
        <tr><td>reject_reason</td><td><?= nl2br(h($row['reject_reason'] ?? '')) ?></td></tr>
        <tr><td>reject_by</td><td><?= h($row['reject_by'] ?? '-') ?></td></tr>
        <tr><td>reject_date</td><td class="mono"><?= h($row['reject_date'] ?? '-') ?></td></tr>
        <tr><td>settled_by</td><td><?= h($row['settled_by'] ?? '-') ?></td></tr>
        <tr><td>settled_date</td><td class="mono"><?= h($row['settled_date'] ?? '-') ?></td></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div style="font-weight:900; margin-bottom:10px;">회원 정보(참고)</div>
  <div style="display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px;">
    <div class="notice" style="background:rgba(17,24,39,.03)">
      <div style="font-size:12px; color:var(--muted)">기본</div>
      <div style="margin-top:6px; font-size:13px;">
        <div>Role: <b><?= h($row['role'] ?? '-') ?></b></div>
        <div>Sponsor ID: <b><?= h($row['sponsor_id'] ?? '-') ?></b></div>
        <div>Email: <b><?= h($row['email'] ?? '-') ?></b></div>
        <div>Referral: <b class="mono"><?= h($row['referral_code'] ?? '-') ?></b></div>
      </div>
    </div>
    <div class="notice" style="background:rgba(17,24,39,.03)">
      <div style="font-size:12px; color:var(--muted)">지갑/코드페이</div>
      <div style="margin-top:6px; font-size:13px;">
        <div>Wallet: <span class="mono"><?= h($row['wallet_address'] ?? '-') ?></span></div>
        <div>Codepay: <span class="mono"><?= h($row['codepay_address'] ?? '-') ?></span></div>
      </div>
      <div style="margin-top:10px; font-size:12px; color:var(--muted)">플랫폼 계정(참고)</div>
      <div style="margin-top:6px; font-size:13px;">
        <div>XM: <span class="mono"><?= h($row['xm_id'] ?? '-') ?></span> / <span class="mono"><?= h($row['xm_server'] ?? '-') ?></span></div>
        <div>ULTIMA: <span class="mono"><?= h($row['ultima_id'] ?? '-') ?></span> / <span class="mono"><?= h($row['ultima_server'] ?? '-') ?></span></div>
      </div>
    </div>
  </div>
</div>

<?php admin_render_footer(); ?>
