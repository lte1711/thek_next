# 상태 플래그 체계 공식 정의 (증거 기반)

**작성일**: 2026-01-20  
**근거**: PHASE-1-A 증거 패키지  
**상태**: 정의 단계 (코드 수정 전)

---

## 1. settle_chk 값 체계 (user_transactions)

### DB 스키마
```sql
`settle_chk` tinyint(1) DEFAULT '0' COMMENT '정산 체크'
```

### 값 정의 (증거 기반)

| 값 | 의미 | 설정 위치 | 조회 조건 | 비고 |
|---|---|---|---|---|
| **0** | 정산 미완료 (초기값) | DEFAULT | `settle_chk=0` | reject_reset.php에서 초기화 시 사용 |
| **1** | 정산 완료 | settle_toggle.php:108<br>settle_profit.php:86 | `settle_chk=1` | country_progressing.php에서 조회 |
| **2** | **Rejecting (거부 진행 중)** | reject_save.php:155 | `settle_chk=2`<br>`settle_chk<>2` (제외) | **특수 상태: 문서화되지 않음** |

---

## 2. settle_chk=2 상세 분석

### 2.1 설정 위치
```php
// reject_save.php:155
SET settle_chk=2,
    external_done_chk=0,
    ...
```

**설정 시나리오**: Reject 버튼 클릭 시

### 2.2 조회/표시 위치

#### A. country_completed_content.php (Completed 페이지 표시)
```php
// Line 39-48
// 2) user_transactions.settle_chk=2 => Rejecting (in progress)

$status = trim((string)($c['status'] ?? ''));

if ($status === '' && (int)($c['settle_chk'] ?? 0) === 2) {
  $status = 'Rejecting';
}

if ($status === '') {
  $status = 'approved';
}
```

**로직**: ready_trading.status가 빈 값이고 settle_chk=2이면 'Rejecting' 표시

#### B. country_progressing.php (Progressing 페이지 필터링)
```php
// Line 116, 168
AND COALESCE(t.settle_chk,0) <> 2
```

**로직**: settle_chk=2인 항목은 Progressing 페이지에서 **제외**

#### C. investor_dashboard_content.php (투자자 대시보드 알림)
```php
// Line 14, 29
AND (settle_chk = 2 OR (reject_reason IS NOT NULL AND reject_reason <> ''))
```

**로직**: settle_chk=2이거나 reject_reason이 있으면 **Rejecting 알림** 표시

### 2.3 해제/초기화 위치
```php
// reject_reset.php:94
SET settle_chk = 0,
    reject_reason = NULL,
    reject_by = NULL,
    reject_date = NULL,
    settled_by = NULL,
    settled_date = NULL,
    ...
```

**시나리오**: Reject Reset (却下中 해제) 버튼 클릭 시 settle_chk=0으로 초기화

---

## 3. status vs settle_chk 혼용 분석

### 3.1 테이블별 상태 관리

| 테이블 | 상태 필드 | 타입 | 값 | 책임 범위 |
|---|---|---|---|---|
| **{country}_ready_trading** | `status` | ENUM | 'ready'<br>'rejected'<br>'approved' | **승인 단계** 상태 관리 |
| **user_transactions** | `settle_chk` | tinyint(1) | 0 (미정산)<br>1 (정산완료)<br>2 (Rejecting) | **정산/거부** 상태 관리 |

### 3.2 상태 전이 흐름

```
[Ready 단계]
ready_trading.status = 'ready'
user_transactions.settle_chk = 0
         ↓
    (OK 클릭)
         ↓
[Progressing 단계]
ready_trading.status = 'approved' (추정)
user_transactions.settle_chk = 0
         ↓
    (정산 완료)
         ↓
[Completed 단계]
user_transactions.settle_chk = 1


[Reject 경로]
         ↓
    (Reject 클릭)
         ↓
[Rejecting 상태]
ready_trading.status = 'rejected'
user_transactions.settle_chk = 2  ← 특수 상태
user_transactions.external_done_chk = 0 (리셋)
         ↓
    (Reject Reset)
         ↓
[Ready 복원]
ready_trading.status = 'ready'
user_transactions.settle_chk = 0
```

### 3.3 혼용 패턴 분석

#### 패턴 A: 페이지별 필터 기준 불일치

**country_ready.php**
```php
// Line 84, 141
WHERE (r.status IS NULL OR r.status = 'ready')
  AND (COALESCE(t.withdrawal_chk,0) = 0 AND COALESCE(t.settle_chk,0) <> 2)
```
- ready_trading.status 기준 필터
- **추가 조건**: settle_chk≠2 (Rejecting 제외)

**country_progressing.php**
```php
// Line 116, 168
AND COALESCE(t.settle_chk,0) <> 2
```
- settle_chk만 사용 (status 무시)

**country_completed.php**
```php
// Line 95-99
WHERE (
    AND COALESCE(t.settle_chk,0) = 1
  )
  OR COALESCE(t.settle_chk,0) = 2
```
- settle_chk=1 (정산 완료) **OR** settle_chk=2 (Rejecting)
- ready_trading.status는 표시용으로만 사용

#### 패턴 B: 상태 표시 우선순위

**country_completed_content.php 로직**
```php
1. ready_trading.status 값 확인
2. status가 비어있고 settle_chk=2이면 → 'Rejecting'
3. status가 비어있고 settle_chk≠2이면 → 'approved'
4. status 값이 있으면 → 그대로 사용
```

**문제점**: 
- status와 settle_chk가 불일치할 경우 표시 로직 복잡
- settle_chk=2가 "임시 상태"인지 "영구 상태"인지 불명확

---

## 4. external_done_chk 의존성

### 4.1 OK 버튼 동작 조건
```php
// ok_save.php:105
if ((int)($txrow['ext'] ?? 0) !== 1) 
  throw new Exception('External processing not confirmed yet. (external_done_chk=0)');
```

**필수 조건**: external_done_chk=1이어야 OK 버튼 실행 가능

### 4.2 Reject 시 동작
```php
// reject_save.php:156
external_done_chk=0
```

**동작**: Reject 시 external_done_chk를 0으로 리셋

### 4.3 설정 위치
```php
// external_done_toggle.php:52
SET external_done_chk=1, external_done_date=COALESCE(external_done_date, CURDATE())
```

**수동 토글**: 별도 버튼으로 관리자가 수동 설정

---

## 5. 발견된 문제점 (리스크)

### 🔴 P1: settle_chk=2의 불명확성
- **문제**: tinyint(1)인데 0/1/2 세 가지 값 사용
- **리스크**: DB 타입과 실제 사용 불일치 (boolean이 아님)
- **영향**: 다른 개발자가 "0=false, 1=true"로 오해 가능

### 🔴 P1: 상태 관리 책임 분산
- **문제**: status(ready_trading) + settle_chk(user_transactions) 이중 관리
- **리스크**: 
  - status='ready'인데 settle_chk=2인 경우?
  - status='rejected'인데 settle_chk=0인 경우?
- **영향**: 데이터 무결성 검증 불가능

### 🟡 P2: Rejecting 상태의 임시성 불명확
- **문제**: settle_chk=2가 "진행 중" 상태인지 "완료" 상태인지 모호
- **리스크**: Reject Reset 없이 다른 작업 진행 시 예외 처리 누락 가능

### 🟡 P2: external_done_chk 의존성
- **문제**: OK 버튼이 external_done_chk=1에 강하게 의존
- **리스크**: 토글 깜빡하면 OK 버튼 작동 불가
- **영향**: 사용자 경험 저하 (에러 메시지만 표시)

---

## 6. 권장 사항 (코드 수정 전 방향성)

### 옵션 A: settle_chk를 tinyint(2)로 확장 + 상수화
```php
// 상수 정의
const SETTLE_NOT_DONE = 0;
const SETTLE_COMPLETED = 1;
const SETTLE_REJECTING = 2;
```
- **장점**: 현재 로직 유지, 명확한 의미 부여
- **단점**: 여전히 status와 이중 관리

### 옵션 B: status를 단일 진실 공급원(Single Source of Truth)로 통합
```sql
ALTER TABLE {country}_ready_trading 
MODIFY status ENUM('ready','approved','rejected','rejecting','completed');
```
- **장점**: 상태 관리 일원화
- **단점**: 대규모 리팩토링 필요, Country 동결 위반

### 옵션 C: settle_chk=2를 별도 플래그로 분리
```sql
ALTER TABLE user_transactions 
ADD COLUMN is_rejecting tinyint(1) DEFAULT 0;
```
- **장점**: 의미 명확화
- **단점**: 컬럼 추가, 기존 로직 수정 필요

### 옵션 D: 현 상태 유지 + 문서화만 강화
- **장점**: 코드 수정 최소화
- **단점**: 근본적 해결 아님

---

## 7. 다음 단계 제안

### 백설이 (총괄)
- 옵션 A/B/C/D 중 방향성 결정
- 우선순위 분류 (P1 먼저 vs P2 먼저)

### 재미니 (DB 전문)
- settle_chk=2 상태의 데이터 실제 존재 여부 확인
- status + settle_chk 불일치 케이스 검색

### 설탕이 (문서화)
- 상태 전이 다이어그램 작성
- API 명세서 형태로 정리

### 허니 (실행자)
- 티켓 발행 대기
- 검증 쿼리 실행 준비

---

## 부록: 증거 파일 목록

### settle_chk=2 관련
- reject_save.php:155 (설정)
- country_completed_content.php:43 (조회/표시)
- country_progressing.php:116,168 (필터링)
- investor_dashboard_content.php:14,29 (알림)
- reject_reset.php:94 (초기화)

### status 관련
- reject_restore.php:66 (status='ready')
- reject_save.php:180 (status='rejected')
- reject_reset.php:108 (status='ready')
- country_ready.php:83,140 (WHERE status='ready')
- country_completed.php:98,161 (WHERE status IN ...)

### external_done_chk 관련
- ok_save.php:105 (조건 체크)
- external_done_toggle.php:52 (설정)
- reject_save.php:156 (리셋)
