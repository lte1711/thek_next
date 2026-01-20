# PHASE-1-A 증거 패키지 (Evidence Pack)

**수집일**: 2026-01-20  
**수집자**: 허니 (Git Bash)  
**수집 범위**: 서버 15.164.165.240:/var/www/html/_branches/jp  
**목적**: settle_chk/status/external_done_chk 상태 플래그 체계 분석

---

## 1. user_transactions 테이블 스키마 (원본)

### 수집 명령어
```bash
mysql -u thek_db_admin -pthek_pw_admin! thek_next_db_branch_jp \
  -e 'SHOW CREATE TABLE user_transactions\G'
```

### 결과 (전문)
```sql
*************************** 1. row ***************************
       Table: user_transactions
Create Table: CREATE TABLE `user_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `xm_value` decimal(15,2) DEFAULT NULL,
  `ultima_value` decimal(15,2) DEFAULT NULL,
  `pair` varchar(50) DEFAULT NULL,
  `deposit_status` varchar(20) DEFAULT NULL,
  `withdrawal_status` varchar(20) DEFAULT NULL,
  `profit_loss` varchar(20) DEFAULT NULL,
  `etc_note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `code_value` varchar(50) NOT NULL,
  `xm_total` decimal(18,2) DEFAULT '0.00',
  `ultima_total` decimal(18,2) DEFAULT '0.00',
  `xm_dividend` decimal(18,2) DEFAULT '0.00' COMMENT 'XM 배당금',
  `ultima_dividend` decimal(18,2) DEFAULT '0.00' COMMENT 'Ultima 배당금',
  `deposit_chk` tinyint(1) DEFAULT '0' COMMENT '입금 체크',
  `dividend_chk` tinyint(1) DEFAULT '0' COMMENT '배당 체크',
  `principal_xm` decimal(18,2) DEFAULT '0.00' COMMENT 'XM 원금',
  `principal_ultima` decimal(18,2) DEFAULT '0.00' COMMENT 'Ultima 원금',
  `settle_chk` tinyint(1) DEFAULT '0' COMMENT '정산 체크',
  `reject_reason` text,
  `reject_by` varchar(50) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `withdrawal_chk` tinyint(1) NOT NULL DEFAULT '0' COMMENT '출금 여부 체크 (0=미완료, 1=완료)',
  `dividend_amount` decimal(18,2) DEFAULT '0.00',
  `external_done_chk` tinyint(1) NOT NULL DEFAULT '0',
  `external_done_date` date DEFAULT NULL COMMENT '외부 거래 완료 확인 일자',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1351 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
```

### 핵심 플래그 필드
```sql
`settle_chk` tinyint(1) DEFAULT '0' COMMENT '정산 체크'
`external_done_chk` tinyint(1) NOT NULL DEFAULT '0'
`withdrawal_chk` tinyint(1) NOT NULL DEFAULT '0' COMMENT '출금 여부 체크'
`dividend_chk` tinyint(1) DEFAULT '0' COMMENT '배당 체크'
```

**주요 발견**: settle_chk는 tinyint(1)이지만 실제 코드에서 0/1/2 세 가지 값 사용

---

## 2. settle_chk=2 설정 증거

### 파일: reject_save.php
**라인**: 155-156

```php
// reject_save.php:155-156
SET settle_chk=2,
    external_done_chk=0,
```

**수집 명령어**:
```bash
grep -Rin 'settle_chk\s*=\s*2' . --include='*.php'
```

**전체 결과**:
```
./reject_save.php:155:                SET settle_chk=2,
./country_completed_content.php:39:        // 2) user_transactions.settle_chk=2 => Rejecting (in progress)
./investor_dashboard_content.php:6:// ✅ 로그인한 회원 Rejecting 알림 (settle_chk=2 또는 reject_reason 존재)
./investor_dashboard_content.php:14:                        AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))
./investor_dashboard_content.php:29:                            AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))";
```

**결론**: settle_chk=2는 **오직 reject_save.php에서만** 설정됨

---

## 3. settle_chk=2 조회/표시 증거

### A. country_completed_content.php
**라인**: 39-48

```php
// country_completed_content.php:39-48
// 2) user_transactions.settle_chk=2 => Rejecting (in progress)

$status = trim((string)($c['status'] ?? ''));

if ($status === '' && (int)($c['settle_chk'] ?? 0) === 2) {
  $status = 'Rejecting';
}

if ($status === '') {
  $status = 'approved';
}
```

**의미**: ready_trading.status가 비어있고 settle_chk=2이면 "Rejecting" 표시

### B. country_progressing.php
**라인**: 116, 168

```php
// country_progressing.php:116
AND COALESCE(t.settle_chk,0) <> 2

// country_progressing.php:168
AND COALESCE(t.settle_chk,0) <> 2
```

**의미**: settle_chk=2인 항목은 Progressing 페이지에서 **제외**

### C. investor_dashboard_content.php
**라인**: 14, 29

```php
// investor_dashboard_content.php:14
AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))

// investor_dashboard_content.php:29
AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))
```

**의미**: settle_chk=2이거나 reject_reason이 있으면 Rejecting 알림 표시

---

## 4. settle_chk=2 초기화 증거

### 파일: reject_reset.php
**라인**: 91-94

```php
// reject_reset.php:91-94
// 1) user_transactions: Reject 관련 값 초기화 + 정산 재처리 가능하도록 settle_chk=0
$stmt1 = $conn->prepare("
    UPDATE user_transactions
       SET settle_chk = 0,
           reject_reason = NULL,
           reject_by = NULL,
           reject_date = NULL,
           settled_by = NULL,
           settled_date = NULL,
           updated_at = NOW()
     WHERE id = ?
");
```

**의미**: Reject Reset 버튼 클릭 시 settle_chk를 0으로 초기화

---

## 5. external_done_chk=1 의존성 증거

### A. OK 버튼 조건 체크
**파일**: ok_save.php  
**라인**: 88, 105

```php
// ok_save.php:88
$stmt = $conn->prepare("SELECT DATE(tx_date) AS d, COALESCE(external_done_chk,0) AS ext, COALESCE(pair, 'XM/Ultima') AS pair FROM user_transactions WHERE id=? AND user_id=?");

// ok_save.php:105
if ((int)($txrow['ext'] ?? 0) !== 1) throw new Exception(t('error.external_not_done','External processing not confirmed yet. (external_done_chk=0)'));
```

**의미**: external_done_chk=1이 아니면 OK 버튼 실행 불가

### B. external_done_chk 설정
**파일**: external_done_toggle.php  
**라인**: 52

```php
// external_done_toggle.php:52
SET external_done_chk=1, external_done_date=COALESCE(external_done_date, CURDATE())
```

**의미**: 별도 토글 버튼으로 수동 설정

### C. Reject 시 리셋
**파일**: reject_save.php  
**라인**: 156

```php
// reject_save.php:156
external_done_chk=0,
```

**의미**: Reject 시 external_done_chk를 0으로 리셋

---

## 6. status (ready_trading) 조작 증거

### A. status='ready' 설정
**파일**: reject_restore.php  
**라인**: 66

```php
// reject_restore.php:66
$stmt = $conn->prepare("UPDATE {$table_ready} SET status='ready', reject_reason=NULL, reject_by=NULL, reject_date=NULL WHERE id=?");
```

**파일**: reject_reset.php  
**라인**: 108-112

```php
// reject_reset.php:108-112
$stmt2 = $conn->prepare("
    UPDATE `$table_ready`
       SET status = 'ready',
           reject_reason = NULL,
           reject_by = NULL,
           reject_date = NULL,
           updated_at = NOW()
     WHERE tx_id = ?
");
```

### B. status='rejected' 설정
**파일**: reject_save.php  
**라인**: 175-184

```php
// reject_save.php:175-184
$sql3 = "INSERT INTO `$table_ready`
             (user_id, tx_id, tx_date, status, reject_reason, reject_by, reject_date, updated_at)
         VALUES
            (?, ?, ?, 'rejected', ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            user_id=VALUES(user_id),
            tx_date=VALUES(tx_date),
            status='rejected',
            reject_reason=VALUES(reject_reason),
            reject_by=VALUES(reject_by),
            reject_date=NOW(),
            updated_at=NOW()";
```

---

## 7. 파일 목록 (금융 관련)

### 수집 명령어
```bash
grep -Ril 'deposit\|withdraw\|settle' . | sort
```

### 결과 (45개 파일)
```
./Partner_accounts.php
./Partner_accounts_v2.php
./admin_detail.php
./admin_detail_content.php
./check_db_structure.php
./codepay_export_content.php
./country_completed.php
./country_completed_content.php
./country_content.php
./country_profit_share.php
./country_profit_share_content.php
./country_progressing.php
./country_progressing_content.php
./country_ready.php
./country_ready_content.php
./gm_settle_confirm.php
./group_accounts.php
./group_accounts_content.php
./group_accounts_v2.php
./group_accounts_v2_content.php
./investor_dashboard_content.php
./investor_deposit.php
./investor_deposit1.php
./investor_dividend_content.php
./investor_profit_share.php
./investor_profit_share_content.php
./investor_transaction_content.php
./investor_withdrawal.php
./investor_withdrawal_content.php
./lang/en.php
./lang/ja.php
./lang/ko.php
./layout.php
./load_summary.php
./ok_save.php
./partner_accounts.php
./partner_accounts_content.php
./partner_accounts_v2_content.php
./profit_share.php
./profit_share_content.php
./referral_settlement.php
./referral_settlement_content.php
./reject_reset.php
./reject_save.php
./settle_confirm.php
./settle_confirm_v2.php
./settle_profit.php
./settle_toggle.php
```

### user_transactions 테이블 사용 파일 (21개)
```
./country_completed.php
./country_completed_content.php
./country_progressing.php
./country_progressing_content.php
./country_ready.php
./external_done_toggle.php
./gm_dashboard_content.php
./investor_dashboard_content.php
./investor_deposit.php
./investor_profit_share.php
./investor_withdrawal.php
./lang/en.php
./lang/ja.php
./lang/ko.php
./load_summary.php
./ok_save.php
./profit_share.php
./quick_check.php
./reject_reset.php
./reject_save.php
./settle_profit.php
./settle_toggle.php
```

---

## 8. 핵심 발견 요약

### settle_chk 값 체계
| 값 | 의미 | 설정 위치 | 증거 라인 |
|---|---|---|---|
| 0 | 미정산 | DEFAULT | user_transactions 스키마 |
| 1 | 정산 완료 | settle_toggle.php:108, settle_profit.php:86 | grep 결과 |
| 2 | **Rejecting** | reject_save.php:155 | **오직 여기서만 설정** |

### 이중 상태 관리 확인
- **ready_trading.status**: 'ready' / 'rejected' / ('approved' 추정)
- **user_transactions.settle_chk**: 0 / 1 / 2

**문제**: 두 상태가 독립적으로 관리됨, 불일치 가능성

### external_done_chk 의존성 확인
- OK 버튼은 external_done_chk=1 **필수** (ok_save.php:105)
- Reject 시 0으로 리셋 (reject_save.php:156)
- 수동 토글 필요 (external_done_toggle.php:52)

---

## 9. 다음 분석 필요 사항

1. **settle_chk=2 실제 데이터 존재 여부 확인**
   ```sql
   SELECT COUNT(*) FROM user_transactions WHERE settle_chk=2;
   ```

2. **status + settle_chk 불일치 케이스 조회**
   ```sql
   SELECT r.status, t.settle_chk, COUNT(*) 
   FROM korea_ready_trading r
   JOIN user_transactions t ON r.tx_id = t.id
   GROUP BY r.status, t.settle_chk;
   ```

3. **external_done_chk=0인데 OK 시도한 에러 로그 확인**
   ```bash
   grep "external_done_chk=0" error.log
   ```

---

**증거 수집 완료**: 2026-01-20  
**다음 단계**: TICKET-001 발행 (settle_chk=2 공식 정의 확정)
