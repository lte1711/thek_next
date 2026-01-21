# PHASE-1-A 금융 파일 증거 수집 결과

## 수집 일시
2026-01-20

## 수집 범위
서버: 15.164.165.240:/var/www/html/_branches/jp

---

## 1. 금융 키워드 포함 파일 (deposit/withdraw/settle)

### 입금 관련 (investor_deposit)
- investor_deposit.php
- investor_deposit1.php
- investor_dashboard_content.php

### 출금 관련 (investor_withdrawal)
- investor_withdrawal.php
- investor_withdrawal_content.php

### 정산 관련 (settle)
- gm_settle_confirm.php
- settle_confirm.php
- settle_confirm_v2.php
- settle_profit.php
- settle_toggle.php
- referral_settlement.php
- referral_settlement_content.php

### Country 관련 (🔒 수정 금지)
- country_ready.php / country_ready_content.php
- country_progressing.php / country_progressing_content.php
- country_completed.php / country_completed_content.php
- country_profit_share.php / country_profit_share_content.php
- country_content.php
- ok_save.php
- reject_save.php
- reject_reset.php

### 계정/그룹 관련
- group_accounts.php / group_accounts_content.php
- group_accounts_v2.php / group_accounts_v2_content.php
- partner_accounts.php / partner_accounts_content.php
- Partner_accounts.php / Partner_accounts_v2.php

### 기타
- admin_detail.php / admin_detail_content.php
- codepay_export_content.php
- investor_profit_share.php / investor_profit_share_content.php
- investor_dividend_content.php
- investor_transaction_content.php
- profit_share.php / profit_share_content.php
- load_summary.php
- layout.php
- check_db_structure.php

### 언어 파일
- lang/ko.php
- lang/ja.php
- lang/en.php

---

## 2. 상태 변경 키워드 발견 (status=)

### ready_trading 상태 변경
```php
// reject_restore.php:66
SET status='ready', reject_reason=NULL, reject_by=NULL, reject_date=NULL

// reject_save.php:180
status='rejected'

// ok_save.php:143
status=VALUES(status)
```

### progressing 관련 상태
```php
// ok_save.php:314-315
deposit_status=?
withdrawal_status=?

// investor_deposit.php:169
SET deposit_status = ?

// investor_deposit.php:263-264
deposit_status = VALUES(deposit_status)
withdrawal_status = VALUES(withdrawal_status)
```

### codepay 상태
```php
// codepay_export_download.php:149
SET status='sent'

// settle_confirm_v2.php:240
SET status='sent'
```

---

## 3. _chk 플래그 발견

### external_done_chk (외부 처리 확인)
```php
// ok_save.php:105
if ((int)($txrow['ext'] ?? 0) !== 1) throw new Exception('external_done_chk=0')

// external_done_toggle.php:52
SET external_done_chk=1, external_done_date=COALESCE(external_done_date, CURDATE())

// reject_save.php:156
external_done_chk=0
```

### settle_chk (정산 상태)
```php
// reject_save.php:155
SET settle_chk=2

// country_completed_content.php:43-44
if ($status === '' && (int)($c['settle_chk'] ?? 0) === 2) {
  $status = 'Rejecting';
}
```

### withdrawal_chk / dividend_chk (출금/배당 확인)
```php
// investor_withdrawal.php:156
withdrawal_chk=1

// investor_profit_share.php:423
SET dividend_chk=1

// country_progressing_content.php:46-47
$w_chk = (int)($row['withdrawal_chk'] ?? 0);
$d_chk = (int)($row['dividend_chk'] ?? 0);
```

---

## 4. user_transactions 테이블 사용 파일 (21개)

### Country 관련 (🔒 수정 금지)
- country_ready.php
- country_completed.php / country_completed_content.php
- country_progressing.php / country_progressing_content.php
- ok_save.php
- reject_save.php
- reject_reset.php

### 입출금 관련
- investor_deposit.php
- investor_withdrawal.php
- investor_profit_share.php

### 정산 관련
- profit_share.php
- settle_profit.php
- settle_toggle.php

### 기타
- external_done_toggle.php
- gm_dashboard_content.php
- investor_dashboard_content.php
- load_summary.php
- quick_check.php

### 언어 파일
- lang/ko.php
- lang/ja.php
- lang/en.php

---

## 5. 발견된 상태 플래그 체계

### A. ready_trading 테이블
- **status**: 'ready' / 'rejected' / 'approved'
- **external_done_chk**: 0 / 1

### B. progressing 테이블
- **deposit_status**: decimal (금액)
- **withdrawal_status**: decimal (금액)

### C. user_transactions 테이블
- **withdrawal_chk**: 0 / 1
- **dividend_chk**: 0 / 1
- **settle_chk**: 0 / 1 / 2

### D. codepay_payout_items 테이블
- **status**: 'pending' / 'sent'

---

## 주의사항

### 🔒 수정 금지 파일 (Country 동결)
- country_*.php
- ok_save.php
- reject_save.php
- reject_reset.php

### ⚠️ 위험 키워드 발견
- `external_done_chk=0` 체크 로직 (ok_save.php)
- `settle_chk=2` (Rejecting 상태)
- `status` + `_chk` 플래그 혼용

---

## 다음 단계 제안
1. 재미니: user_transactions 스키마 분석
2. 설탕이: 상태 전이 흐름도 작성
3. 백설이: 리스크 우선순위 분류
