# PHASE-1-C 증거 패키지

**수집 일시**: 2026-01-20  
**수집자**: 허니 (Honey)  
**범위**: approved 사용처 + settle_chk 위험 조건 패턴  
**서버**: 15.164.165.240:/var/www/html/_branches/jp

---

## 1. 서버 동기화 확인

### 동기화 로그
```
From https://github.com/lte1711/thek_next
 * branch            main       -> FETCH_HEAD
   b5f709c..2d94a16  main       -> origin/main
Updating b5f709c..2d94a16
Fast-forward
 docs/analysis/DB_FINANCIAL_RISK_SCAN_V3.md        |  39 +++
 docs/analysis/SETTLE_REJECT_FLOW_CONFIRMED.md     | 178 ++++++++++
 docs/evidence/PHASE-1-A_EVIDENCE_PACK.md          | 396 ++++++++++++++++++++++
 docs/guides/jp_i18n_application_checklist.md      | 198 +++++++++++
 docs/policies/TEAM_WORKFLOW_RULES.md              | 262 ++++++++++++++
 docs/tickets/TICKET-001_settle_chk2_definition.md | 178 ++++++++++
 6 files changed, 1251 insertions(+)
```

**서버 최신 커밋**: `2d94a16` (docs: track docs and add workflow rules + evidence pack)  
**동기화 상태**: ✅ 완료

---

## 2. approved 사용처 (8건)

### 2.1 ok_save.php:137 ⭐ (OK 처리 시 status='approved' 설정)

**파일**: `ok_save.php`  
**라인**: 137  
**용도**: OK 버튼 클릭 시 ready_trading.status에 'approved' 설정

```php
// ok_save.php:127-147
  $conn->begin_transaction();

  // ✅ tx_id 기준 UPSERT (UNIQUE(tx_id) 전제)
  // INSERT 컬럼 구성 (존재하는 컬럼만)
  $cols = ["user_id", "tx_id"];
  $vals = ["?", "?"];
  $it   = "ii";
  $ip   = [$user_id, $tx_id];

  if ($ready_has_tx_date) { $cols[]="tx_date"; $vals[]="?"; $it.="s"; $ip[]=$tx_date; }
  if ($ready_has_status) { $cols[]="status"; $vals[]="?"; $it.="s"; $ip[]='approved'; }  // ⭐
  if ($ready_has_settled_by) { $cols[]="settled_by"; $vals[]="?"; $it.="i"; $ip[]=$admin_id; }
  if ($ready_has_settled_dt) { $cols[]="settled_date"; $vals[]="NOW()"; }

  // UPDATE 절: status/settled_by/settled_date 를 동일하게 갱신
  $upd = [];
  if ($ready_has_status) $upd[] = "status=VALUES(status)";
  if ($ready_has_settled_by) $upd[] = "settled_by=VALUES(settled_by)";
  if ($ready_has_settled_dt) $upd[] = "settled_date=NOW()";
  if ($ready_has_tx_date) $upd[] = "tx_date=VALUES(tx_date)";
  // 최소 1개는 있어야 함
```

---

### 2.2 investor_withdrawal.php:89 (출금 조건)

**파일**: `investor_withdrawal.php`  
**라인**: 89  
**용도**: 출금 가능 거래 조회 시 status='approved' 조건

```php
// investor_withdrawal.php:79-99
       COALESCE(ultima_value,0) AS ultima_value
FROM user_transactions
WHERE user_id=?
  AND COALESCE(deposit_chk,0)=1
  -- ✅ GM OK 승인(approved) 된 거래만 출금 가능
  AND EXISTS (
      SELECT 1
      FROM korea_ready_trading r
      WHERE r.user_id = user_transactions.user_id
        AND r.tx_id   = user_transactions.id
        AND r.status  = 'approved'  // ⭐
  )
  AND COALESCE(external_done_chk,0)=1
  AND COALESCE(withdrawal_chk,0)=0
      AND (withdrawal_status IS NULL OR withdrawal_status='' OR withdrawal_status='0')
  AND (withdrawal_status IS NULL OR withdrawal_status='' OR withdrawal_status='0')
ORDER BY id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
```

---

### 2.3 investor_withdrawal.php:187 (출금 검증)

**파일**: `investor_withdrawal.php`  
**라인**: 187  
**용도**: 출금 처리 전 재검증 시 status='approved' 조건

```php
// investor_withdrawal.php:177-197
    $check = "
    SELECT id
    FROM user_transactions
    WHERE user_id=?
      AND COALESCE(deposit_chk,0)=1
      AND EXISTS (
          SELECT 1
          FROM korea_ready_trading r
          WHERE r.user_id = user_transactions.user_id
            AND r.tx_id   = user_transactions.id
            AND r.status  = 'approved'  // ⭐
      )
      AND COALESCE(external_done_chk,0)=1
      AND COALESCE(withdrawal_chk,0)=0
      AND (withdrawal_status IS NULL OR withdrawal_status='' OR withdrawal_status='0')
    ORDER BY id DESC
    ";
    $cs = $conn->prepare($check);
    $cs->bind_param("i", $user_id);
    $cs->execute();
    $cr = $cs->get_result();
```

---

### 2.4 country_completed.php:98 (Completed 페이지 필터)

**파일**: `country_completed.php`  
**라인**: 98  
**용도**: Completed 페이지에 approved/rejected/rejecting 상태 포함

```php
// country_completed.php:88-108
    GROUP BY user_id, tx_date
  ) pm ON pm.user_id = t.user_id AND pm.tx_date = DATE(t.tx_date)
  LEFT JOIN {$table_progress} p ON p.id = pm.max_pid
  WHERE (
    (
      COALESCE(t.deposit_chk,0) = 1
      AND COALESCE(t.withdrawal_chk,0) = 1
      AND COALESCE(t.settle_chk,0) = 1
      AND COALESCE(t.dividend_chk,0) = 1
    )
    OR r.status IN ('approved','rejected','rejecting')  // ⭐
    OR COALESCE(t.settle_chk,0) = 2
  )
  AND r.id IS NOT NULL
";

$total_count = 0;
$total_pages = 1;
$res_cnt = mysqli_query($conn, $sql_count);
if ($res_cnt) {
    $row_cnt = mysqli_fetch_assoc($res_cnt);
```

**참고**: Line 161에도 동일한 패턴 사용

---

### 2.5 country_content.php:156 (UI 기본값)

**파일**: `country_content.php`  
**라인**: 156  
**용도**: status 값이 NULL일 경우 'approved'를 기본값으로 표시

```php
// country_content.php:146-166
        </td>

        <td style="text-align:center; white-space:pre-line;">
          xm: ₩<?= number_format((float)($c['xm_value'] ?? 0), 2) ?>

          ultima: ₩<?= number_format((float)($c['ultima_value'] ?? 0), 2) ?>
        </td>

        <td style="text-align:center;">
          <span style="display:inline-block; padding:4px 8px; border-radius:6px; border:1px solid #1b5e20; background:#e8f5e9; color:#1b5e20; font-weight:600;">
            <?= htmlspecialchars($c['status'] ?? 'approved') ?>  // ⭐
          </span>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  <?php endif; ?>
</div>

<!-- ✅ (3번) Partner Daily Settlement (Malaysia only) - by date (USDT) -->
<div style="margin:28px 0 18px;">
```

---

### 2.6 country_completed_content.php:48 (UI 표시 로직)

**파일**: `country_completed_content.php`  
**라인**: 48  
**용도**: status 값이 비어있고 settle_chk≠2일 때 'approved'로 표시

```php
// country_completed_content.php:38-58
        // 1) ready_trading.status (approved/rejected)
        // 2) user_transactions.settle_chk=2 => Rejecting (in progress)
        // 3) fallback => approved
        $status = trim((string)($c['status'] ?? ''));

        if ($status === '' && (int)($c['settle_chk'] ?? 0) === 2) {
          $status = 'Rejecting';
        }

        if ($status === '') {
          $status = 'approved';  // ⭐ fallback
        }
      ?>
      <tr>
        <td><?= htmlspecialchars($c['tx_date'] ?? ($c['settled_date'] ?? '-')) ?></td>
        <td><?= htmlspecialchars($c['username'] ?? '-') ?></td>

        <td style="text-align:center; white-space:pre-line;">
          id: <?= htmlspecialchars($c['xm_id'] ?? '-') ?>

          pw: <?= htmlspecialchars($c['xm_pw'] ?? '-') ?>
```

---

## 3. settle_chk 위험 조건 패턴

### 3.1 settle_chk != 1 사용처

#### investor_deposit.php:133 ⚠️ (Reject 재입금 검증)

**파일**: `investor_deposit.php`  
**라인**: 133  
**위험**: `settle_chk != 1`이 0(미정산)과 2(Rejecting)를 동시에 포함

```php
// investor_deposit.php:118-148
        $xm_value     = (float)($_POST['xm_value'] ?? 0);
        $ultima_value = (float)($_POST['ultima_value'] ?? 0);

        if ($tx_id_to_update <= 0 || ($xm_value <= 0 && $ultima_value <= 0)) {
            die(t('err.invalid_reject_update', 'Invalid reject update parameters'));
        }

        // 보안: 해당 거래가 현재 사용자 소유이며 Reject 상태인지 재확인
        $sql_verify = "
            SELECT id FROM user_transactions
            WHERE id = ?
              AND user_id = ?
              AND deposit_chk = 1
              AND reject_by IS NOT NULL
              AND COALESCE(external_done_chk, 0) = 0
              AND settle_chk != 1  // ⚠️ 0과 2를 모두 포함
              AND dividend_chk != 1
            LIMIT 1
        ";
        $stmtV = $conn->prepare($sql_verify);
        if (!$stmtV) die(t('msg.db_error', 'Database error'));
        $stmtV->bind_param("ii", $tx_id_to_update, $user_id);
        $stmtV->execute();
        $resV = $stmtV->get_result();
        if (!$resV || $resV->num_rows === 0) {
            $stmtV->close();
            die(t('err.invalid_reject_transaction', 'Invalid reject transaction'));
        }
        $stmtV->close();

        // UPDATE: 입금액만 수정
```

**분석**:
- `reject_by IS NOT NULL` 조건이 함께 사용되므로 Reject 상태 거래만 대상
- `settle_chk != 1`은 "정산 완료가 아닌" 모든 상태 허용
- settle_chk=0(미정산)과 settle_chk=2(Rejecting) 모두 포함
- 의도: Reject 상태에서 입금액 수정 가능한 거래 찾기

---

#### investor_deposit.php:319 ⚠️ (Reject 거래 조회)

**파일**: `investor_deposit.php`  
**라인**: 319  
**위험**: `settle_chk != 1`이 0(미정산)과 2(Rejecting)를 동시에 포함

```php
// investor_deposit.php:304-334
// 여기까지 오면 GET 화면 렌더

// ==============================
// ✅ Reject 거래 확인 (입금액만 수정 모드)
// ==============================
$reject_mode = false;
$reject_tx = null;

$sql_reject = "
    SELECT id, tx_date, xm_value, ultima_value, reject_reason, reject_by, settle_chk, dividend_chk
    FROM user_transactions
    WHERE user_id = ?
      AND deposit_chk = 1
      AND reject_by IS NOT NULL
      AND COALESCE(external_done_chk, 0) = 0
      AND settle_chk != 1  // ⚠️ 0과 2를 모두 포함
      AND dividend_chk != 1
    ORDER BY id DESC
    LIMIT 1
";
$stmtR = $conn->prepare($sql_reject);
if ($stmtR) {
    $stmtR->bind_param("i", $user_id);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    $rowR = $resR ? $resR->fetch_assoc() : null;
    $stmtR->close();

    if ($rowR) {
        $reject_mode = true;
        $reject_tx = $rowR;
```

**분석**:
- Line 133과 동일한 패턴
- Reject 거래 중 입금액 수정 가능한 최신 거래 1건 조회
- settle_chk=0과 settle_chk=2를 구분하지 않음
- 현재 로직상 `reject_by IS NOT NULL`이면 대부분 settle_chk=2일 것으로 추정

---

### 3.2 settle_chk != 0 검색 결과

**명령어**:
```bash
grep -Rin "settle_chk.*!=.*0" . --include="*.php"
```

**결과**: 0건 (검색 범위: /var/www/html/_branches/jp)

---

### 3.3 settle_chk > 0 검색 결과

**명령어**:
```bash
grep -Rin "settle_chk.*>.*0" . --include="*.php"
```

**결과**: 0건 (검색 범위: /var/www/html/_branches/jp)

---

## 4. 요약

### ✅ 확정된 증거

| # | 항목 | 파일:라인 | 상태 |
|---|------|-----------|------|
| 1 | OK 처리 시 status='approved' 설정 | ok_save.php:137 | ✅ 확정 |
| 2 | 출금 조건: status='approved' | investor_withdrawal.php:89,187 | ✅ 확정 |
| 3 | Completed 페이지: status IN ('approved',...) | country_completed.php:98,161 | ✅ 확정 |
| 4 | UI 기본값: 'approved' | country_content.php:156, country_completed_content.php:48 | ✅ 확정 |
| 5 | settle_chk != 1 패턴 존재 | investor_deposit.php:133,319 | ✅ 확정 |
| 6 | settle_chk != 0 패턴 | (검색 결과) | ✅ 0건 확정 |
| 7 | settle_chk > 0 패턴 | (검색 결과) | ✅ 0건 확정 |

### ⚠️ 확인된 위험

**settle_chk != 1 조건의 의미 모호성**:
- `investor_deposit.php` 2개 지점에서 사용
- settle_chk=0(미정산)과 settle_chk=2(Rejecting)를 구분하지 않음
- `reject_by IS NOT NULL` 조건과 함께 사용되므로 Reject 거래에 국한
- 현재는 문제없으나, 향후 로직 변경 시 혼동 가능성

---

**[허니 증거 수집 완료]**
