<?php
require_once 'admin_bootstrap.php';
require_once 'admin_layout.php';

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// detail 페이지에서 "목록으로" 되돌아가기 위한 현재 쿼리스트링
$back_qs = $_SERVER['QUERY_STRING'] ?? '';

// ---------------- Filters
$preset = isset($_GET['preset']) ? (string)$_GET['preset'] : 'month';
$from   = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
$to     = isset($_GET['to']) ? trim((string)$_GET['to']) : '';
$filter = isset($_GET['filter']) ? (string)$_GET['filter'] : 'all';
$q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$subtree = (isset($_GET['subtree']) && (string)$_GET['subtree'] === '1');
$uid     = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$per    = isset($_GET['per']) ? (int)$_GET['per'] : 50;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($per < 10) $per = 10;
if ($per > 200) $per = 200;
if ($page < 1) $page = 1;

// default dates: this month
$today = new DateTime('now');
if ($from === '' || $to === '') {
    if ($preset === 'today') {
        $from = $today->format('Y-m-d');
        $to   = $today->format('Y-m-d');
    } elseif ($preset === '7days') {
        $d = (clone $today)->modify('-6 day');
        $from = $d->format('Y-m-d');
        $to   = $today->format('Y-m-d');
    } else {
        $from = $today->format('Y-m-01');
        $to   = $today->format('Y-m-d');
        $preset = 'month';
    }
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today->format('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today->format('Y-m-d');

$from_dt = $from . ' 00:00:00';
$to_dt   = $to   . ' 23:59:59';

$filters = [
    'all' => '전체',
    'deposit' => '입금 완료',
    'withdrawal' => '출금 완료',
    'dividend' => '배당 완료',
    'settled' => '정산 완료',
    'unsettled' => '미정산',
];

if (!isset($filters[$filter])) $filter = 'all';

// ---------------- Build WHERE
$where = [];
$params = [];
$types = '';

$where[] = "t.tx_date BETWEEN ? AND ?";
$params[] = $from_dt; $params[] = $to_dt;
$types .= 'ss';

if ($filter === 'deposit') {
    $where[] = "t.deposit_chk = 1";
} elseif ($filter === 'withdrawal') {
    $where[] = "t.withdrawal_chk = 1";
} elseif ($filter === 'dividend') {
    $where[] = "t.dividend_chk = 1";
} elseif ($filter === 'settled') {
    $where[] = "t.settle_chk = 1";
} elseif ($filter === 'unsettled') {
    $where[] = "t.settle_chk = 0";
}

// user search (+ 하위 포함)
$sql_prefix = '';
$match_users = [];
$subtree_root = 0;

if ($subtree) {
    // 하위 포함 모드: 특정 회원 1명을 루트로 잡고 sponsor_id 트리 전체를 포함
    if ($uid > 0) {
        $subtree_root = $uid;
    } elseif ($q !== '') {
        if (ctype_digit($q)) {
            $subtree_root = (int)$q;
        } else {
            $like = '%' . $q . '%';
            $stmt = $conn->prepare("SELECT id, name, username, email FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY id ASC LIMIT 25");
            if ($stmt) {
                $stmt->bind_param('sss', $like, $like, $like);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($res && ($row = $res->fetch_assoc())) {
                    $label = trim((string)($row['name'] ?? ''));
                    if ($label === '') $label = trim((string)($row['username'] ?? ''));
                    if ($label === '') $label = trim((string)($row['email'] ?? ''));
                    if ($label === '') $label = 'ID ' . (int)$row['id'];
                    $match_users[] = ['id' => (int)$row['id'], 'label' => $label];
                }
                $stmt->close();
            }
            if (count($match_users) === 1) {
                $subtree_root = (int)$match_users[0]['id'];
            }
        }
    }

    if ($subtree_root > 0) {
        // CTE 파라미터는 SQL 가장 앞에 등장하므로 params/types 앞에 prepend
        $sql_prefix = "WITH RECURSIVE subtree AS (
"
                    . "  SELECT id FROM users WHERE id = ?
"
                    . "  UNION ALL
"
                    . "  SELECT u.id FROM users u INNER JOIN subtree s ON u.sponsor_id = s.id
"
                    . ") ";
        array_unshift($params, $subtree_root);
        $types = 'i' . $types;
        $where[] = "t.user_id IN (SELECT id FROM subtree)";
    } elseif ($q !== '') {
        // 하위 포함인데 루트 선택이 안 된 경우(동명이인 등): 선택 전까지 결과 없음
        $where[] = "1=0";
    }
} else {
    // 일반 검색: 매칭된 user_id들만 표시
    $user_ids = null;
    if ($q !== '') {
        if (ctype_digit($q)) {
            $user_ids = [(int)$q];
        } else {
            $like = '%' . $q . '%';
            $stmt = $conn->prepare("SELECT id FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY id ASC LIMIT 200");
            if ($stmt) {
                $stmt->bind_param('sss', $like, $like, $like);
                $stmt->execute();
                $res = $stmt->get_result();
                $user_ids = [];
                while ($res && ($row = $res->fetch_assoc())) $user_ids[] = (int)$row['id'];
                $stmt->close();
            } else {
                $user_ids = [];
            }
        }
        if (is_array($user_ids)) {
            if (count($user_ids) === 0) {
                $where[] = "1=0";
            } else {
                $in = implode(',', array_map('intval', $user_ids));
                $where[] = "t.user_id IN ($in)";
            }
        }
    }
}

$where_sql = empty($where) ? '1=1' : implode(' AND ', $where);

// ---------------- Export CSV
$export = isset($_GET['export']) ? (string)$_GET['export'] : '';
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="settlement_transactions_' . $from . '_' . $to . '.csv"');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, ['TX ID','User ID','User','TX Date','XM','ULTIMA','Dividend','Deposit','Withdrawal','DividendChk','SettleChk']);

    $sql = $sql_prefix . "SELECT
                t.id, t.user_id,
                COALESCE(NULLIF(TRIM(u.name),''), NULLIF(TRIM(u.username),''), u.email, CONCAT('ID ',u.id)) AS user_label,
                t.tx_date, t.xm_value, t.ultima_value, t.dividend_amount,
                t.deposit_chk, t.withdrawal_chk, t.dividend_chk, t.settle_chk
            FROM user_transactions t
            LEFT JOIN users u ON u.id = t.user_id
            WHERE $where_sql
            ORDER BY t.tx_date DESC, t.id DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            fputcsv($out, [
                $row['id'], $row['user_id'], $row['user_label'], $row['tx_date'],
                $row['xm_value'], $row['ultima_value'], $row['dividend_amount'],
                $row['deposit_chk'], $row['withdrawal_chk'], $row['dividend_chk'], $row['settle_chk']
            ]);
        }
        $stmt->close();
    }
    fclose($out);
    exit;
}

// ---------------- Pagination + totals
$offset = ($page - 1) * $per;

$total_rows = 0;
$sum = ['xm'=>0.0,'ultima'=>0.0,'dividend'=>0.0,'count'=>0,'settled'=>0,'unsettled'=>0];

try {
    $sqlCount = $sql_prefix . "SELECT COUNT(*) AS c,
                        COALESCE(SUM(COALESCE(t.xm_value,0)),0) AS xm_sum,
                        COALESCE(SUM(COALESCE(t.ultima_value,0)),0) AS ultima_sum,
                        COALESCE(SUM(COALESCE(t.dividend_amount,0)),0) AS div_sum,
                        SUM(CASE WHEN t.settle_chk=1 THEN 1 ELSE 0 END) AS settled,
                        SUM(CASE WHEN t.settle_chk=0 THEN 1 ELSE 0 END) AS unsettled
                 FROM user_transactions t
                 WHERE $where_sql";
    $stmt = $conn->prepare($sqlCount);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        if ($r) {
            $total_rows = (int)($r['c'] ?? 0);
            $sum['count'] = $total_rows;
            $sum['xm'] = (float)($r['xm_sum'] ?? 0);
            $sum['ultima'] = (float)($r['ultima_sum'] ?? 0);
            $sum['dividend'] = (float)($r['div_sum'] ?? 0);
            $sum['settled'] = (int)($r['settled'] ?? 0);
            $sum['unsettled'] = (int)($r['unsettled'] ?? 0);
        }
        $stmt->close();
    }
} catch (Throwable $e) {}

$rows = [];
try {
    $sql = $sql_prefix . "SELECT
                t.id, t.user_id,
                COALESCE(NULLIF(TRIM(u.name),''), NULLIF(TRIM(u.username),''), u.email, CONCAT('ID ',u.id)) AS user_label,
                t.tx_date, t.xm_value, t.ultima_value, t.dividend_amount,
                t.deposit_chk, t.withdrawal_chk, t.dividend_chk, t.settle_chk
            FROM user_transactions t
            LEFT JOIN users u ON u.id = t.user_id
            WHERE $where_sql
            ORDER BY t.tx_date DESC, t.id DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $types2 = $types . 'ii';
        $params2 = array_merge($params, [$per, $offset]);
        $stmt->bind_param($types2, ...$params2);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) $rows[] = $row;
        $stmt->close();
    }
} catch (Throwable $e) {}

$total_pages = max(1, (int)ceil($total_rows / $per));

function build_query(array $overrides = []): string {
    $q = $_GET;
    foreach ($overrides as $k=>$v) {
        if ($v === null) unset($q[$k]);
        else $q[$k] = $v;
    }
    return http_build_query($q);
}

admin_render_header('집계/정산 · 거래 목록 (user_transactions)');
?>

<div class="card">
  <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div>
      <div style="font-weight:900; font-size:16px;">거래 목록</div>
      <div style="margin-top:10px;">
        <a class="btn secondary" href="settlement_dashboard.php?<?= h(http_build_query(['preset'=>$preset,'from'=>$from,'to'=>$to])) ?>">← 대시보드</a>
      </div>
      <div style="color:var(--muted); font-size:12px; margin-top:6px;">
        기간 <b><?=h($from)?></b> ~ <b><?=h($to)?></b> · 필터 <b><?=h($filters[$filter])?></b>
        · 총 <b><?= number_format($sum['count']) ?></b>건
      </div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <a class="btn btn-primary" href="?<?=h(build_query(['export'=>'csv','page'=>1]))?>">CSV 다운로드</a>
    </div>
  </div>

  <hr class="sep">

  <form method="GET">
    <?php if ($subtree && $uid > 0): ?>
      <input type="hidden" name="uid" value="<?=h()?>">
    <?php endif; ?>
    <div class="form-row">
      <div>
        <label>프리셋</label><br>
        <select name="preset" onchange="this.form.submit()">
          <option value="today" <?= $preset==='today'?'selected':'' ?>>오늘</option>
          <option value="7days" <?= $preset==='7days'?'selected':'' ?>>최근 7일</option>
          <option value="month" <?= $preset==='month'?'selected':'' ?>>이번달</option>
        </select>
      </div>

      <div>
        <label>From</label><br>
        <input type="date" name="from" value="<?=h($from)?>">
      </div>
      <div>
        <label>To</label><br>
        <input type="date" name="to" value="<?=h($to)?>">
      </div>

      <div>
        <label>상태</label><br>
        <select name="filter">
          <?php foreach ($filters as $k=>$lab): ?>
            <option value="<?=h($k)?>" <?= $filter===$k?'selected':'' ?>><?=h($lab)?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="min-width:220px;">
        <label>회원 검색(ID/이름/username/email)</label><br>
        <input type="text" name="q" value="<?=h($q)?>" placeholder="예) 98 또는 Zayne">
      </div>

      <div>
        <label>하위 포함</label><br>
        <label style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:var(--muted);">
          <input type="checkbox" name="subtree" value="1" <?= $subtree ? 'checked' : '' ?>>
          하위 포함
        </label>
      </div>

      <?php if ($subtree && $q !== '' && $uid <= 0 && count($match_users) > 1): ?>
      <div style="min-width:240px;">
        <label>동명이인 선택</label><br>
        <select name="uid">
          <option value="">선택하세요</option>
          <?php foreach ($match_users as $mu): ?>
            <option value="<?=h($mu['id'])?>"><?=h($mu['label'])?> (ID <?=h($mu['id'])?>)</option>
          <?php endforeach; ?>
        </select>
        <div style="font-size:12px; color:var(--muted); margin-top:6px;">하위 포함 모드에서는 회원 1명을 선택해야 합니다.</div>
      </div>
      <?php endif; ?>


      <div>
        <label>표시 개수</label><br>
        <select name="per">
          <?php foreach ([20,50,100,200] as $n): ?>
            <option value="<?= $n ?>" <?= $per===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <button class="btn btn-primary" type="submit">적용</button>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <div class="grid" style="grid-template-columns:repeat(6,1fr);">
    <div class="stat">
      <div class="k">XM 합계</div>
      <div class="v"><?= number_format($sum['xm'], 2) ?></div>
    </div>
    <div class="stat">
      <div class="k">ULTIMA 합계</div>
      <div class="v"><?= number_format($sum['ultima'], 2) ?></div>
    </div>
    <div class="stat">
      <div class="k">배당 합계</div>
      <div class="v"><?= number_format($sum['dividend'], 2) ?></div>
    </div>
    <div class="stat">
      <div class="k">정산 완료</div>
      <div class="v"><?= number_format($sum['settled']) ?></div>
    </div>
    <div class="stat">
      <div class="k">미정산</div>
      <div class="v"><?= number_format($sum['unsettled']) ?></div>
    </div>
    <div class="stat">
      <div class="k">총 건수</div>
      <div class="v"><?= number_format($sum['count']) ?></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="min-width:80px;">TX ID</th>
          <th style="min-width:90px;">User ID</th>
          <th style="min-width:200px;">회원</th>
          <th style="min-width:170px;">거래일시</th>
          <th style="min-width:110px;">XM</th>
          <th style="min-width:110px;">ULTIMA</th>
          <th style="min-width:110px;">Dividend</th>
          <th style="min-width:90px;">입금</th>
          <th style="min-width:90px;">출금</th>
          <th style="min-width:90px;">배당</th>
          <th style="min-width:90px;">정산</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="11" style="text-align:center; color:var(--muted); padding:18px;">데이터가 없습니다.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <a href="settlement_transaction_detail.php?id=<?=h($r['id'])?>&back=<?=h(rawurlencode($back_qs))?>"
                 style="font-weight:800; text-decoration:none; border-bottom:1px dashed rgba(37,99,235,.45);">
                <?=h($r['id'])?>
              </a>
            </td>
            <td><?=h($r['user_id'])?></td>
            <td><?=h($r['user_label'])?></td>
            <td><?=h($r['tx_date'])?></td>
            <td style="text-align:right; font-family:var(--mono)"><?= number_format((float)($r['xm_value'] ?? 0), 2) ?></td>
            <td style="text-align:right; font-family:var(--mono)"><?= number_format((float)($r['ultima_value'] ?? 0), 2) ?></td>
            <td style="text-align:right; font-family:var(--mono)"><?= number_format((float)($r['dividend_amount'] ?? 0), 2) ?></td>
            <td style="text-align:center;"><?= ((int)$r['deposit_chk']===1) ? '✅' : '—' ?></td>
            <td style="text-align:center;"><?= ((int)$r['withdrawal_chk']===1) ? '✅' : '—' ?></td>
            <td style="text-align:center;"><?= ((int)$r['dividend_chk']===1) ? '✅' : '—' ?></td>
            <td style="text-align:center;"><?= ((int)$r['settle_chk']===1) ? '✅' : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:12px; flex-wrap:wrap;">
    <div style="color:var(--muted); font-size:12px;">
      Page <b><?= $page ?></b> / <b><?= $total_pages ?></b>
    </div>
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
      <?php
        $prev = max(1, $page-1);
        $next = min($total_pages, $page+1);
      ?>
      <a class="btn" href="?<?=h(build_query(['page'=>1,'export'=>null]))?>">« 처음</a>
      <a class="btn" href="?<?=h(build_query(['page'=>$prev,'export'=>null]))?>">‹ 이전</a>
      <a class="btn" href="?<?=h(build_query(['page'=>$next,'export'=>null]))?>">다음 ›</a>
      <a class="btn" href="?<?=h(build_query(['page'=>$total_pages,'export'=>null]))?>">끝 »</a>
    </div>
  </div>
</div>

<?php admin_render_footer(); ?>
PHP