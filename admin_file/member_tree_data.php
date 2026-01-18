<?php
require_once 'admin_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// subtree endpoint
// GET member_tree_data.php?action=subtree&depth=5&q=keyword  OR  &user_id=123
if (isset($_GET['action']) && $_GET['action'] === 'subtree') {
    $max_depth = 5;
    if (isset($_GET['depth']) && is_numeric($_GET['depth'])) {
        $d = (int)$_GET['depth'];
        if ($d >= 1 && $d <= 8) $max_depth = $d;
    }

    $target_id = 0;
    $q = '';
    if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $target_id = (int)$_GET['user_id'];
    } else {
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($q !== '' && ctype_digit($q)) {
            $target_id = (int)$q;
        }
    }

    // 이름/아이디 검색 시: 같은 회원이 여러 명이면 선택하도록 후보 목록 반환
    if ($target_id <= 0) {
        $q = trim((string)$q);
        if ($q === '') {
            echo json_encode(['found' => false, 'message' => 'notfound'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $like = '%' . $q . '%';
        $sqlFind = "
            SELECT id, name, username, email, role
            FROM users
            WHERE (name LIKE ? OR username LIKE ? OR email LIKE ?)
              AND LOWER(role) NOT IN ('gm','superadmin','specialadmin')
            ORDER BY id ASC
            LIMIT 30
        ";
        $stmtFind = $conn->prepare($sqlFind);
        if (!$stmtFind) {
            http_response_code(500);
            echo json_encode(['error' => 'failed', 'message' => 'prepare failed'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmtFind->bind_param('sss', $like, $like, $like);
        $stmtFind->execute();
        $rs = $stmtFind->get_result();

        $matches = [];
        while ($row = $rs->fetch_assoc()) {
            $matches[] = [
                'id' => (int)$row['id'],
                'name' => (string)($row['name'] ?? ''),
                'username' => (string)($row['username'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'role' => (string)($row['role'] ?? ''),
            ];
        }
        $stmtFind->close();

        if (count($matches) === 0) {
            echo json_encode(['found' => false, 'message' => 'notfound'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (count($matches) > 1) {
            echo json_encode([
                'found' => false,
                'status' => 'multiple',
                'q' => $q,
                'matches' => $matches,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $target_id = (int)$matches[0]['id'];
    }

    // Build full map once, then return subtree only.
    try {
        $sql = "SELECT id, name, username, email, phone, role, sponsor_id, referrer_id FROM users ORDER BY id ASC";
        $res = $conn->query($sql);
        $users = [];
        $children = [];
        $excluded_ids = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $id = (int)$row['id'];
                $role_lc = strtolower((string)($row['role'] ?? ''));
                if ($role_lc === 'gm' || $role_lc === 'superadmin' || $role_lc === 'specialadmin') {
                    $excluded_ids[$id] = true;
                    continue;
                }
                $sponsor_id = $row['sponsor_id'] !== null ? (int)$row['sponsor_id'] : null;
                $users[$id] = [
                    'id' => $id,
                    'name' => $row['name'] ?? '',
                    'username' => $row['username'] ?? '',
                    'email' => $row['email'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'role' => $row['role'] ?? '',
                    'sponsor_id' => $sponsor_id,
                    'referrer_id' => $row['referrer_id'] !== null ? (int)$row['referrer_id'] : null,
                ];
                if ($sponsor_id !== null && !isset($excluded_ids[$sponsor_id])) {
                    if (!isset($children[$sponsor_id])) $children[$sponsor_id] = [];
                    $children[$sponsor_id][] = $id;
                }
            }
        }
        $labels = [];
        foreach ($users as $uid => $u) {
            $labels[$uid] = trim(($u['name'] ?: $u['username'] ?: $u['email']) ?: '');
        }

        foreach ($children as $pid => $arr) {
            sort($arr, SORT_NUMERIC);
            $children[$pid] = $arr;
        }

        if (!isset($users[$target_id])) {
            echo json_encode(['found' => false, 'message' => 'notfound'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $make_node = function($id, $depth) use (&$make_node, $users, $children, $labels) {
            $u = $users[$id] ?? null;
            $label = $u ? (trim(($u['name'] ?: $u['username'] ?: $u['email'])) ?: ('ID '.$id)) : ('ID '.$id);
            $node = [
                'id' => $id,
                'name' => $label,
                'role' => $u ? ($u['role'] ?? '') : '',
                'meta' => $u ? [
                    'username' => $u['username'],
                    'email' => $u['email'],
                    'phone' => $u['phone'],
                    'sponsor_id' => $u['sponsor_id'],
                    'referrer_id' => $u['referrer_id'],
                    'sponsor_name' => ($u['sponsor_id'] !== null && isset($labels[$u['sponsor_id']])) ? $labels[$u['sponsor_id']] : '',
                    'referrer_name' => ($u['referrer_id'] !== null && isset($labels[$u['referrer_id']])) ? $labels[$u['referrer_id']] : '',
                    'link' => "users.php?id={$id}",
                ] : null,
                'children' => [],
            ];
            if ($depth <= 0) return $node;
            $kids = $children[$id] ?? [];
            $cnt = count($kids);
            $show = array_slice($kids, 0, 2);
            foreach ($show as $cid) {
                $node['children'][] = $make_node((int)$cid, $depth - 1);
            }
            if ($cnt > 2) {
                $more_n = $cnt - 2;
                $node['children'][] = [
                    'id' => "more_{$id}",
                    'name' => "+{$more_n}명 더보기",
                    'role' => 'more',
                    'meta' => [
                        'link' => "users.php?sponsor_id={$id}",
                        'parent_id' => $id,
                        'offset' => 2,
                        'remaining' => $more_n,
                    ],
                    'children' => [],
                ];
            }
            return $node;
        };

        $root = $make_node((int)$target_id, $max_depth - 1);
        $root['meta']['subtree'] = true;
        echo json_encode(['found' => true, 'root' => $root], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'failed', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// dynamic loading: children chunk endpoint
// GET member_tree_data.php?action=children&parent_id=123&offset=2&limit=10
if (isset($_GET['action']) && $_GET['action'] === 'children') {
    $parent_id = isset($_GET['parent_id']) && is_numeric($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
    $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;

    try {
        // exclude gm + special admins
        $sql = "SELECT u.id, u.name, u.username, u.email, u.phone, u.role, u.sponsor_id, u.referrer_id,
                s.name AS sponsor_name, s.username AS sponsor_username, s.email AS sponsor_email,
                r.name AS referrer_name, r.username AS referrer_username, r.email AS referrer_email
                FROM users u
                LEFT JOIN users s ON u.sponsor_id = s.id
                LEFT JOIN users r ON u.referrer_id = r.id
                WHERE u.sponsor_id = ?
                  AND LOWER(u.role) NOT IN ('gm','superadmin','specialadmin')
                ORDER BY u.id ASC
                LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception('prepare failed');
        $stmt->bind_param('iii', $parent_id, $offset, $limit);
        $stmt->execute();
        $res = $stmt->get_result();

        $items = [];
        while ($row = $res->fetch_assoc()) {
            $id = (int)$row['id'];
            $label = trim(($row['name'] ?? '') ?: ($row['username'] ?? '') ?: ($row['email'] ?? ''));
            if ($label === '') $label = 'ID '.$id;
            $items[] = [
                'id' => $id,
                'name' => $label,
                'role' => $row['role'] ?? '',
                'meta' => [
                    'username' => $row['username'] ?? '',
                    'email' => $row['email'] ?? '',
                    'phone' => $row['phone'] ?? '',
                    'sponsor_id' => $row['sponsor_id'] !== null ? (int)$row['sponsor_id'] : null,
                    'referrer_id' => $row['referrer_id'] !== null ? (int)$row['referrer_id'] : null,
                    'sponsor_name' => trim(($row['sponsor_name'] ?? '') ?: ($row['sponsor_username'] ?? '') ?: ($row['sponsor_email'] ?? '')),
                    'referrer_name' => trim(($row['referrer_name'] ?? '') ?: ($row['referrer_username'] ?? '') ?: ($row['referrer_email'] ?? '')),
                    'link' => "users.php?id={$id}",
                ],
                'children' => [],
            ];
        }
        $stmt->close();

        // total count (for remaining)
        $sql2 = "SELECT COUNT(*) AS c
                 FROM users
                 WHERE sponsor_id = ?
                   AND LOWER(role) NOT IN ('gm','superadmin','specialadmin')";
        $stmt2 = $conn->prepare($sql2);
        if (!$stmt2) throw new Exception('prepare failed');
        $stmt2->bind_param('i', $parent_id);
        $stmt2->execute();
        $row2 = $stmt2->get_result()->fetch_assoc();
        $total = (int)($row2['c'] ?? 0);
        $stmt2->close();

        echo json_encode([
            'parent_id' => $parent_id,
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'failed', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$max_depth = 5;
if (isset($_GET['depth']) && is_numeric($_GET['depth'])) {
    $d = (int)$_GET['depth'];
    if ($d >= 1 && $d <= 8) $max_depth = $d;
}

// 0 = 회사(가상 루트)
$ROOT_ID = 0;

try {
    // GM은 '회사'로만 표시(개별 GM 유저 노드로 보여주지 않음)
    // 특별관리자(예: superadmin/specialadmin)는 트리에서 숨김
    $sql = "SELECT id, name, username, email, phone, role, sponsor_id, referrer_id FROM users ORDER BY id ASC";
    $res = $conn->query($sql);
    $users = [];
    $children = []; // sponsor_id => [child ids]
    $top = [];
    $excluded_ids = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $id = (int)$row['id'];
            $sponsor_id = $row['sponsor_id'] !== null ? (int)$row['sponsor_id'] : null;

            $role_lc = strtolower((string)($row['role'] ?? ''));
            // hide gm + special admins
            if ($role_lc === 'gm' || $role_lc === 'superadmin' || $role_lc === 'specialadmin') {
                $excluded_ids[$id] = true;
                continue;
            }

            $users[$id] = [
                'id' => $id,
                'name' => $row['name'] ?? '',
                'username' => $row['username'] ?? '',
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
                'role' => $row['role'] ?? '',
                'sponsor_id' => $sponsor_id,
                'referrer_id' => $row['referrer_id'] !== null ? (int)$row['referrer_id'] : null,
            ];

            // parent attachment: if sponsor is null/unknown/excluded, attach under company root
            if ($sponsor_id === null || isset($excluded_ids[$sponsor_id])) {
                $top[] = $id;
            } else {
                if (!isset($children[$sponsor_id])) $children[$sponsor_id] = [];
                $children[$sponsor_id][] = $id;
            }
        }
    }

    // second pass: some users may reference a sponsor_id that doesn't exist in $users (because excluded or missing)
    // Re-home those as top-level under company.
    foreach ($users as $uid => $u) {
        $pid = $u['sponsor_id'];
        if ($pid !== null && !isset($users[$pid])) {
            $top[] = $uid;
        }
    }
    // de-dup top
    $top = array_values(array_unique($top));

    // sort children for stable left/right assignment
    foreach ($children as $pid => $arr) {
        sort($arr, SORT_NUMERIC);
        $children[$pid] = $arr;
    }
    sort($top, SORT_NUMERIC);

    $labels = [];
    foreach ($users as $uid => $u) {
        $labels[$uid] = trim(($u['name'] ?: $u['username'] ?: $u['email']) ?: '');
    }

    $make_node = function($id, $depth) use (&$make_node, $users, $children, $max_depth, $labels) {
        // virtual "more" nodes are strings
        if (!is_int($id)) {
            return [
                'id' => (string)$id,
                'name' => '+ 더보기',
                'role' => 'more',
                'children' => [],
            ];
        }

        $u = $users[$id] ?? null;
        $label = $u ? (trim(($u['name'] ?: $u['username'] ?: $u['email'])) ?: ('ID '.$id)) : ('ID '.$id);
        $role = $u ? ($u['role'] ?? '') : '';
        $node = [
            'id' => $id,
            'name' => $label,
            'role' => $role,
            'meta' => $u ? [
                'username' => $u['username'],
                'email' => $u['email'],
                'phone' => $u['phone'],
                'sponsor_id' => $u['sponsor_id'],
                'referrer_id' => $u['referrer_id'],
                'sponsor_name' => ($u['sponsor_id'] !== null && isset($labels[$u['sponsor_id']])) ? $labels[$u['sponsor_id']] : '',
                'referrer_name' => ($u['referrer_id'] !== null && isset($labels[$u['referrer_id']])) ? $labels[$u['referrer_id']] : '',
                'link' => "users.php?id={$id}",
            ] : null,
            'children' => [],
        ];

        if ($depth <= 0) return $node;

        $kids = $children[$id] ?? [];
        $cnt = count($kids);

        // enforce binary view: show only first 2 children, pack the rest into a "more" virtual node
        $show = array_slice($kids, 0, 2);
        foreach ($show as $cid) {
            $node['children'][] = $make_node((int)$cid, $depth - 1);
        }

        if ($cnt > 2) {
            $more_n = $cnt - 2;
            $node['children'][] = [
                'id' => "more_$id",
                'name' => "+{$more_n}명 더보기",
                'role' => 'more',
                'meta' => [
                    'link' => "users.php?sponsor_id={$id}",
                    'parent_id' => $id,
                    'offset' => 2,
                    'remaining' => $more_n,
                ],
                'children' => [],
            ];
        }

        return $node;
    };

    // 회사 루트 노드
    $root = [
        'id' => 0,
        'name' => '회사',
        'role' => 'company',
        'children' => [],
    ];

    // GM은 '회사'로 묶는 요청이므로, sponsor_id가 NULL인 최상단 유저들을 회사 아래로 붙임
    foreach ($top as $tid) {
        $root['children'][] = $make_node((int)$tid, $max_depth - 1);
    }

    echo json_encode($root, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'failed', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
