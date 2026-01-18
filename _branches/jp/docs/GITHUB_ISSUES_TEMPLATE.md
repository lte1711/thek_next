# GitHub Issue Set for v2026-01 Stabilization

**이 파일의 내용을 GitHub Issues에 그대로 복붙하면 됩니다.**

---

## 📋 Step 1: Labels 사전 생성 (필수)

GitHub Repository → Settings → Labels에서 아래 라벨 생성:

| Label | Color | Description |
|-------|-------|-------------|
| `core` | #990000 | 핵심 기능/아키텍처 |
| `process` | #0366d6 | 프로세스/워크플로우 개선 |
| `deploy` | #ffc040 | 배포 관련 |
| `docs` | #5319e7 | 문서 작성 |
| `qa` | #c2e0c6 | QA/테스트 관련 |
| `priority:high` | #ff0000 | 높은 우선순위 |

---

## 📋 Step 2: Milestone 사전 생성 (필수)

GitHub Repository → Projects → Milestones에서:

**Milestone**: `v2026-01 Stabilization`
- **Description**: THEK-NEXT 안정화 (브랜치/배포/회귀테스트)
- **Due Date**: 2026-01-31 (참고용)

---

## 🎯 GitHub Issue 4개 (복붙용)

### Issue 1: 브랜치 전략

**Title**: `[Core] Git 브랜치 전략 및 릴리즈 규칙 확정`

**Labels**: `core`, `process`  
**Milestone**: `v2026-01 Stabilization`

**Description**:

```
## 목적
멀티 브랜치(KO/JP) + 다국어 구조에서 운영 안정성을 위해 
브랜치/릴리즈 규칙을 레포 기준으로 고정한다.

## Scope
* `main / dev / feature/* / hotfix/*` 브랜치 역할 정의
* `main` 브랜치 보호 규칙 적용 (직접 push 금지)
* 릴리즈 태그 규칙: `vYYYY.MM.DD` (예: `v2026.01.18`)
* PR 기본 템플릿 추가

## Acceptance Criteria
* [ ] `main` 브랜치 보호 설정 완료 (필수 리뷰, 직접 push 금지)
* [ ] 브랜치 네이밍 규칙 문서화 (`feature/`, `hotfix/`)
* [ ] `.github/PULL_REQUEST_TEMPLATE.md` 추가 완료
* [ ] 릴리즈/태그 규칙 `docs/BRANCH_POLICY.md`에 명시

## Notes
- 참고: `docs/BRANCH_POLICY.md` (이미 초안 작성됨)
- 기준 버전: `0118_v4` (2026-01-18)
```

---

### Issue 2: 배포 파일 검증

**Title**: `[Deploy] 배포 파일 누락 방지(manifest 검증) 추가`

**Labels**: `deploy`, `priority:high`  
**Milestone**: `v2026-01 Stabilization`

**Description**:

```
## 목적
"서버에 파일 없음 / 패치 미반영" 재발 방지를 위해 
배포 시 파일 누락을 자동 검증한다.

## Scope
* 배포 대상 파일 manifest 생성
* 서버 업로드 후 존재 여부 검증 (누락 시 실패)
* 배포 로그 저장
* 배포 절차 문서화

## Acceptance Criteria
* [ ] Manifest 생성 로직 정의 (스크립트 또는 CI)
* [ ] 배포 후 파일 존재 검증 프로세스 추가
* [ ] 누락 파일 발견 시 배포 중단 (non-zero exit)
* [ ] 배포 로그 저장 경로/규칙 확정 (`/logs/deploy/`)
* [ ] `docs/DEPLOY.md`에 절차 문서화

## Notes
- 참고: `docs/DEPLOY.md` (초안 작성됨)
- 과거 이슈: 배포 후 특정 PHP/lang 파일 누락으로 500 에러
- 우선순위: 높음 (배포 안정성 직결)
```

---

### Issue 3: KO/JP 동기화 정책

**Title**: `[Docs] KO/JP 브랜치 동기화 및 공통 소스 관리 정책 문서화`

**Labels**: `docs`, `process`  
**Milestone**: `v2026-01 Stabilization`

**Description**:

```
## 목적
KO/JP 구조에서 공통 수정/브랜치 전용 수정 규칙을 
문서로 고정하여 혼선을 방지한다.

## Scope
* 기준 소스: `0118_v4` 확정 내용 명시
* 공통 디렉토리 (예: `includes/`, `lang/`) 변경 시 KO/JP 동시 반영 규칙
* JP 전용 영역: `/_branches/jp/` 범위 명확화
* 언어 파일 관리: `lang/{ko,ja,en}.php` 최신 기준

## Acceptance Criteria
* [ ] `docs/BRANCH_POLICY.md` 작성 완료
* [ ] 공통/전용 범위 명확히 정의
* [ ] KO/JP 동시 반영 체크리스트 포함
* [ ] 신규 기능 반영 흐름(ko → jp) 명시

## Definition of Done
* 문서 작성/검토 완료
* 팀이 사용 가능한 수준의 명료성

## Notes
- 참고: `docs/BRANCH_POLICY.md` (이미 초안 작성됨)
- 기준 버전: 0118_v4
```

---

### Issue 4: 회귀 테스트 체크리스트

**Title**: `[QA] 배포 전 회귀 테스트 체크리스트 구축(다국어/대시보드/레이아웃)`

**Labels**: `qa`, `process`  
**Milestone**: `v2026-01 Stabilization`

**Description**:

```
## 목적
언어/차트/레이아웃 깨짐을 배포 전에 잡기 위한 
최소 회귀 테스트 체크리스트를 정의한다.

## Scope
* 핵심 페이지 선정: GM/Admin/Master/Agent/Investor 대시보드
* 언어별 (ko/ja/en) 확인 항목
* GM형식 레이아웃 (차트 크기/위치) 유지 여부
* "파일 없음/경로 오류" 재현 URL 점검

## Acceptance Criteria
* [ ] `docs/REGRESSION_CHECKLIST.md` 작성 완료
* [ ] 페이지별 체크 항목 7가지 이상 정의
* [ ] 배포 게이트("체크 후 배포") 문구 명시
* [ ] 과거 이슈 URL 포함 (재발 방지)

## Definition of Done
* 배포 전 필수 체크리스트로 팀 운영

## Notes
- 참고: `docs/REGRESSION_CHECKLIST.md` (이미 초안 작성됨)
- GM 형식: 차트 높이 260px, 2열 그리드, 반응형 980px
- 다국어: ko/ja/en 전환 정상 표시 확인
```

---

## ✅ 이슈 등록 후 할 일

1. **각 이슈에 라벨/마일스톤 추가**
   - 위 4개 이슈 설명 참고

2. **main 브랜치 보호 설정** (Issue 1)
   - Settings → Branches → main 선택
   - "Require a pull request before merging" ✅
   - "Require status checks to pass before merging" ✅ (CI/Lint)
   - "Require code reviews before merging" ✅ (최소 1명)
   - "Dismiss stale pull request approvals" ✅

3. **PR 템플릿 확인**
   - `.github/PULL_REQUEST_TEMPLATE.md` 이미 생성됨
   - 향후 PR 생성 시 자동 로드

4. **문서 확인 및 팀 공유**
   - `docs/BRANCH_POLICY.md` (KO/JP 정책)
   - `docs/DEPLOY.md` (배포 절차)
   - `docs/REGRESSION_CHECKLIST.md` (회귀 테스트)
   - `docs/WORKFLOW.md` (플로우 다이어그램)

---

## 📊 이슈별 작업 시간 추정 (참고)

| Issue | 작업 | 예상 시간 |
|-------|------|---------|
| 1 | 브랜치 보호 설정 | 30분 |
| 2 | Manifest 검증 스크립트 | 2-3시간 |
| 3 | (문서만 → 이미 작성됨) | 검토만 10분 |
| 4 | (체크리스트 → 이미 작성됨) | 검토만 10분 |

**총 소요 시간**: ~3시간

---

## 🎯 완료 후 효과

```
이전:
❌ "왜 서버랑 다르지?"
❌ "JP만 깨졌네?"
❌ "언어 또 이상해"

이후:
✅ 명확한 브랜치 정책 → 파일 누락 차단
✅ Manifest 검증 → 배포 안정성 보장
✅ KO/JP 동기화 규칙 → 혼선 방지
✅ 회귀 체크리스트 → 배포 전 자동 검증
```

---

**Version**: `0118_v4` (2026-01-18)
**Ready to Post**: ✅
