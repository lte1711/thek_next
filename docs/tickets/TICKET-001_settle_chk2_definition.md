# TICKET-001: settle_chk=2 공식 정의 확정

**티켓 번호**: TICKET-001  
**생성일**: 2026-01-20  
**생성자**: 백설이 (총괄)  
**우선순위**: P1 (높음)  
**상태**: 대기 중 (Pending)

---

## 목적

settle_chk=2의 의미/사용처/조회조건을 **공식 정의**로 확정하고,  
status(ready_trading) vs settle_chk(user_transactions)의 역할 분리 기준을 명확히 한다.

**중요**: 이 티켓은 **코드 수정이 아닌 정의/문서화**만 수행한다.

---

## 배경

### 발견된 문제
1. `settle_chk`는 tinyint(1)이지만 실제로 0/1/2 세 가지 값 사용
2. settle_chk=2는 "Rejecting" 상태를 의미하지만 **공식 문서화되지 않음**
3. ready_trading.status와 user_transactions.settle_chk가 독립적으로 관리되어 불일치 가능

### 증거
- **PHASE-1-A_EVIDENCE_PACK.md** (docs/evidence/)
- reject_save.php:155 - settle_chk=2 설정
- country_completed_content.php:43 - settle_chk=2 → 'Rejecting' 표시
- user_transactions 스키마 - tinyint(1) DEFAULT 0

---

## 범위

### 영향 테이블
1. **user_transactions**
   - settle_chk 필드 (tinyint)
   - 관련: reject_reason, reject_by, reject_date

2. **{country}_ready_trading**
   - status 필드 (ENUM: 'ready'/'rejected'/'approved')

### 영향 파일 (읽기만, 수정 없음)
- reject_save.php (settle_chk=2 설정)
- reject_reset.php (settle_chk=0 초기화)
- country_completed_content.php (settle_chk=2 조회)
- country_progressing.php (settle_chk≠2 필터)
- investor_dashboard_content.php (settle_chk=2 알림)
- settle_toggle.php (settle_chk=1 설정)
- settle_profit.php (settle_chk=1 설정)

---

## 작업 내용

### 1. settle_chk 값 체계 공식 정의

**문서**: docs/definitions/SETTLE_CHK_VALUES.md

| 값 | 공식 명칭 | 의미 | 설정 조건 | 해제 조건 |
|---|---|---|---|---|
| 0 | NOT_SETTLED | 정산 미완료 (초기값) | 생성 시 기본값 | - |
| 1 | SETTLED | 정산 완료 | settle_toggle.php / settle_profit.php | reject_reset.php |
| 2 | REJECTING | 거부 진행 중 | reject_save.php (Reject 버튼) | reject_reset.php |

**상태 전이**:
```
0 (NOT_SETTLED) → 1 (SETTLED)     [정산 완료 시]
0 (NOT_SETTLED) → 2 (REJECTING)   [Reject 시]
2 (REJECTING)   → 0 (NOT_SETTLED) [Reject Reset 시]
1 (SETTLED)     → 0 (NOT_SETTLED) [Reject Reset 시]
```

### 2. status vs settle_chk 역할 분리

**문서**: docs/definitions/STATUS_VS_SETTLE_CHK.md

| 필드 | 테이블 | 책임 범위 | 사용 시점 |
|---|---|---|---|
| **status** | {country}_ready_trading | 승인 단계 관리 | Ready → Progressing 전환 |
| **settle_chk** | user_transactions | 정산/거부 상태 | Progressing → Completed 전환 |

**원칙**:
- status는 **승인 워크플로우** 관리
- settle_chk는 **정산 및 거부** 관리
- 두 필드는 **독립적이지만 연계**되어야 함

### 3. 불일치 케이스 정의

**허용되는 조합**:
```
status='ready'    + settle_chk=0  (정상: 승인 대기)
status='rejected' + settle_chk=2  (정상: 거부 진행 중)
status='approved' + settle_chk=0  (정상: 승인됨, 정산 미완료)
status='approved' + settle_chk=1  (정상: 승인됨, 정산 완료)
```

**비정상 조합** (데이터 무결성 위반):
```
status='ready'    + settle_chk=2  (비정상: Ready인데 Rejecting?)
status='ready'    + settle_chk=1  (비정상: Ready인데 정산 완료?)
status='rejected' + settle_chk=1  (비정상: Rejected인데 정산 완료?)
```

---

## 완료 기준

### 문서 산출물
- [ ] docs/definitions/SETTLE_CHK_VALUES.md 작성
- [ ] docs/definitions/STATUS_VS_SETTLE_CHK.md 작성
- [ ] docs/analysis/STATE_FLAG_SYSTEM_DEFINITION.md 업데이트

### 검증 쿼리 실행
- [ ] settle_chk=2 실제 데이터 존재 여부 확인
- [ ] status + settle_chk 불일치 케이스 조회
- [ ] 결과를 docs/evidence/에 저장

### 승인
- [ ] 재미니: DB 관점 검토 완료
- [ ] 설탕이: 문서 품질 검토 완료
- [ ] 백설이: 최종 승인

### 다음 단계 준비
- [ ] TICKET-002 발행 가능 상태 (코드 수정 티켓)
- [ ] 정의 문서를 바탕으로 리팩토링 옵션 제시

---

## 제약 사항

### 금지 사항
- ❌ **코드 수정 절대 금지** (정의만)
- ❌ Country 관련 파일 접근 금지
- ❌ DB 테이블 구조 변경 금지
- ❌ 기존 로직 변경 금지

### 허용 사항
- ✅ 파일 읽기 (grep, cat)
- ✅ DB SELECT 쿼리 (READ ONLY)
- ✅ 문서 작성/커밋
- ✅ 검증 쿼리 실행

---

## 예상 소요 시간

- 문서 작성: 2시간
- 검증 쿼리: 30분
- 검토/승인: 1시간
- **총 예상**: 3.5시간

---

## 다음 티켓 (예정)

### TICKET-002: settle_chk=2 로직 정규화
- 목적: settle_chk=2 사용처 코드 개선
- 방법: 상수화 + 주석 추가
- 범위: 5개 파일 수정 (Country 제외)

### TICKET-003: status + settle_chk 무결성 체크
- 목적: 비정상 조합 탐지/알림
- 방법: DB 트리거 또는 정기 체크 스크립트

---

## 참고 문서

- docs/evidence/PHASE-1-A_EVIDENCE_PACK.md
- docs/policies/TEAM_WORKFLOW_RULES.md
- STATE_FLAG_SYSTEM_DEFINITION.md (루트)

---

**승인 대기 중**: 백설이 → 사용자
