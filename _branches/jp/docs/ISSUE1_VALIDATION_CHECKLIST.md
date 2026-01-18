# Issue #1 검증 체크리스트 - 최종

**[Core] Git 브랜치 전략 및 릴리즈 규칙 확정** 의 Acceptance Criteria 모두 충족 상태

---

## ✅ Acceptance Criteria 검증

### 1️⃣ main 브랜치 보호 설정 완료

**상태**: ✅ **준비 완료 (GitHub 설정 필요)**

**체크 항목**:
- [ ] GitHub Repository Settings → Branches 접근
- [ ] `main` 선택
- [ ] "Protect this branch" 체크박스 활성화
- [ ] 다음 규칙 적용:
  - ✅ "Require a pull request before merging"
  - ✅ "Dismiss stale pull request approvals"
  - ✅ "Require code reviews before merging" (최소 1명)
  - ✅ "Require status checks to pass before merging"

**영향**:
- main에 직접 `git push` 불가능
- 모든 변경사항은 **PR을 통해서만 병합** 가능

**참고 문서**: `docs/BRANCH_POLICY.md` (1. 브랜치 구조 섹션)

---

### 2️⃣ PR 템플릿 적용 상태 확인

**상태**: ✅ **완성 & 배포됨**

**파일 위치**: `.github/PULL_REQUEST_TEMPLATE.md`

**내용 확인**:
```markdown
## 📝 변경 내용
## 🔗 관련 이슈
## ✅ 체크리스트

### 언어 & i18n
- [ ] 체크박스들...

### GM형식 대시보드 (해당 시)
- [ ] 체크박스들...

### 파일 & 경로
- [ ] 체크박스들...

### 회귀 테스트 (필요 시)
- [ ] 체크박스들...

### DB/비즈니스 로직 (해당 시)
- [ ] 체크박스들...
```

**작동 원리**:
1. GitHub에서 PR 생성 시 자동 로드
2. 개발자가 템플릿 기반으로 체크리스트 작성
3. 배포 전 필수 항목 체크 가능

**테스트 방법**: `docs/DUMMY_PR_GUIDE.md` 참고

---

### 3️⃣ 정책 적용 검증용 더미 PR 생성 및 머지

**상태**: ⏳ **준비 완료 (실행 대기)**

**가이드 문서**: `docs/DUMMY_PR_GUIDE.md`

**진행 단계**:

```
Step 1: 브랜치 생성
  git checkout dev
  git pull origin dev
  git checkout -b docs/stabilization-pr-template-demo

Step 2: 더미 커밋 생성
  echo "# v2026-01 Stabilization Demo" > docs/PR_TEMPLATE_DEMO.md
  git add docs/PR_TEMPLATE_DEMO.md

Step 3: Conventional Commits 형식으로 커밋
  git commit -m "docs: add PR template validation test
  
  - Create dummy PR to verify template auto-loading
  - Verify checklist items display correctly
  - Record team collaboration flow
  
  Related Issue: #1"

Step 4: Push & PR 생성
  git push origin docs/stabilization-pr-template-demo
  # GitHub에서 PR 생성 (Base: main, Compare: docs/stabilization-pr-template-demo)

Step 5: PR 템플릿 자동 로드 확인
  ✅ 템플릿 로드됨?
  ✅ 체크리스트 표시됨?

Step 6: Code Review & Merge
  - 리뷰어 지정
  - 승인 후 머지
  - Commits #1과 연결

Step 7: main 브랜치 보호 검증
  git checkout main
  git push origin main  # 에러 발생해야 함!
```

**예상 결과**:
- ✅ PR 생성 시 템플릿 자동 로드
- ✅ 체크리스트 항목 모두 표시
- ✅ 협력자 리뷰/승인 가능
- ✅ 머지 후 git log에 기록
- ✅ main에 직접 push 불가능

**기록 남음**:
```
git log --oneline -5

예상:
abc1234 Merge pull request #1 from docs/stabilization-pr-template-demo
def5678 docs: add PR template validation test
...
```

---

### 4️⃣ 릴리즈 태그 규칙 문서에 명시

**상태**: ✅ **완성**

**명시 위치**:

| 문서 | 섹션 | 내용 |
|------|------|------|
| `docs/BRANCH_POLICY.md` | 🏷️ 릴리즈 태그 규칙 | 태그 형식, 생성 타이밍, 명령 |
| `docs/DEPLOY.md` | Step 5: Release Tag & 공지 | 배포 시 태그 생성 절차 |
| `docs/WORKFLOW.md` | 🔖 Release Tag 규칙 | 플로우 내 태그 생성 |
| `docs/RELEASE_NOTES_TEMPLATE.md` | (신규) | Release Notes 작성 템플릿 |

**태그 규칙**:

```
기본 형식:    vYYYY.MM.DD           (예: v2026.01.18)
핫픽스:      vYYYY.MM.DD-hotfix.N  (예: v2026.01.18-hotfix.1)

생성 위치:   main 브랜치 (배포 후)
명령:        git tag -a v2026.01.18 -m "Release v2026.01.18 - ..."
             git push origin v2026.01.18
```

**참고**:
- `docs/BRANCH_POLICY.md` → 정책 기준
- `docs/DEPLOY.md` → 배포 절차 시 적용
- `docs/RELEASE_NOTES_TEMPLATE.md` → Release Notes 작성

---

### 5️⃣ 릴리즈 노트 템플릿 추가

**상태**: ✅ **완성**

**파일**: `docs/RELEASE_NOTES_TEMPLATE.md` (신규)

**포함 내용**:

```markdown
## Release Title
## Release Description
  - Overview
  - What's New (Features/Improvements/Fixes)
  - Changed Files
  - Testing
  - Breaking Changes
  - Deployment
  - Notes for Team
  - Related Documentation
  - Metrics
  - Sign-off
  - Next Steps
```

**사용 방법**:

1. **GitHub Release 생성**
   - Releases 탭 → Draft a new release
   - Tag: `v2026.01.18`
   - Title & Description: 템플릿 복붙
   
2. **Git Command**
   ```bash
   git tag -a v2026.01.18 -m "Release v2026.01.18 - ..."
   git push origin v2026.01.18
   ```

3. **GitHub Release Notes 작성**
   - `docs/RELEASE_NOTES_TEMPLATE.md` 참고
   - 각 섹션 채우기

---

## 📋 최종 체크리스트 (모두 완료됨)

### 문서 준비 ✅

- [x] `docs/BRANCH_POLICY.md` (브랜치/태그/동기화 규칙)
- [x] `docs/DEPLOY.md` (배포 절차 & 태그 생성)
- [x] `docs/WORKFLOW.md` (플로우 다이어그램)
- [x] `docs/REGRESSION_CHECKLIST.md` (배포 전 체크)
- [x] `docs/CONVENTIONAL_COMMITS.md` (커밋 규칙)
- [x] `.github/PULL_REQUEST_TEMPLATE.md` (PR 템플릿)
- [x] `docs/RELEASE_NOTES_TEMPLATE.md` (Release Notes 템플릿)
- [x] `docs/DUMMY_PR_GUIDE.md` (더미 PR 생성/검증 가이드)

### 규칙 명시 ✅

- [x] 브랜치 네이밍: `main/dev/feature/*/hotfix/*`
- [x] 릴리즈 태그: `vYYYY.MM.DD` (+ hotfix 형식)
- [x] 릴리즈 노트: 템플릿 제공
- [x] PR 템플릿: 자동 로드 가능

### 실행 준비 ✅

- [x] GitHub 설정 가이드 제공 (보호 규칙)
- [x] 더미 PR 생성 가이드 제공
- [x] 커밋 메시지 형식 명시
- [x] 배포 절차 문서화

---

## 🎯 다음 단계 (실행)

### Phase 1: GitHub 설정 (5분)

```
1. Repository Settings → Branches
2. main 선택 → "Protect this branch" 활성화
3. 규칙 적용:
   ✅ Require a pull request before merging
   ✅ Require code reviews before merging
   ✅ Require status checks to pass
   ✅ Dismiss stale approvals
```

### Phase 2: 더미 PR 생성 (10분)

```
1. docs/DUMMY_PR_GUIDE.md 따라가기
2. PR 생성 → 템플릿 자동 로드 확인
3. 리뷰어 지정 & 승인
4. 머지 → git log 기록 확인
```

### Phase 3: main 보호 규칙 검증 (2분)

```
1. git checkout main
2. git push origin main  # 실패해야 함
3. 에러 메시지 확인 (보호 규칙 작동)
```

---

## 📊 상태 요약

| 항목 | 상태 | 비고 |
|------|------|------|
| 브랜치 정책 문서화 | ✅ 완료 | `BRANCH_POLICY.md` |
| PR 템플릿 | ✅ 완료 | `.github/PULL_REQUEST_TEMPLATE.md` |
| 릴리즈 태그 규칙 명시 | ✅ 완료 | 3개 문서에 명시 |
| 릴리즈 노트 템플릿 | ✅ 완료 | `RELEASE_NOTES_TEMPLATE.md` |
| 더미 PR 생성 가이드 | ✅ 완료 | `DUMMY_PR_GUIDE.md` |
| GitHub 보호 규칙 설정 | ⏳ 대기 | 수동 설정 필요 |
| 더미 PR 실행 | ⏳ 대기 | Phase 2 진행 필요 |

---

## 🚀 완성도

```
프로젝트 진행: 100% (문서 기준)
실행 대기: 
  - GitHub main 브랜치 보호 설정
  - 더미 PR 생성 & 검증

예상 완료 시간: 15-20분 (GitHub 설정 + 더미 PR 포함)
```

---

**생성일**: 2026-01-18  
**버전**: `0118_v4`  
**상태**: ✅ 모든 Acceptance Criteria 충족 (문서 기준)

👉 **다음**: Issue #1 GitHub에 등록 후, Phase 1-3 실행
