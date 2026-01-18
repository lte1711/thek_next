# Recommended Commits for v2026-01 Stabilization

**아래 커밋 메시지를 순서대로 사용하면 깔끔한 git history가 만들어집니다.**

---

## 커밋 1️⃣: 정책 문서 추가

```bash
git add docs/BRANCH_POLICY.md docs/WORKFLOW.md docs/DEPLOY.md docs/REGRESSION_CHECKLIST.md docs/v2026-01-STABILIZATION.md

git commit -m "docs: add v2026-01 stabilization policy documents

- Add BRANCH_POLICY.md (KO/JP synchronization rules)
- Add WORKFLOW.md (Issue → PR → Release flow with Mermaid)
- Add DEPLOY.md (deployment procedure with manifest validation)
- Add REGRESSION_CHECKLIST.md (pre-deploy QA checklist)
- Add v2026-01-STABILIZATION.md (index and overview)

These documents establish:
* Clear branch naming conventions (main/dev/feature/hotfix)
* KO/JP file synchronization rules
* Deployment validation through manifest verification
* Regression testing checklist (7 essential checks)

Related Issue: #1, #3, #4"
```

---

## 커밋 2️⃣: PR 템플릿 추가

```bash
git add .github/PULL_REQUEST_TEMPLATE.md

git commit -m "chore: add PR template with stability checks

- Add .github/PULL_REQUEST_TEMPLATE.md
- Include language (ko/ja/en) verification checklist
- Include GM dashboard layout confirmation
- Include file deployment validation checks
- Include Conventional Commits format guide

This template helps developers remember critical checks
before submitting PRs and ensures consistency.

Related Issue: #1"
```

---

## 커밋 3️⃣: GitHub 이슈 템플릿

```bash
git add docs/GITHUB_ISSUES_TEMPLATE.md

git commit -m "docs: add GitHub issues template for v2026-01

- Add GITHUB_ISSUES_TEMPLATE.md (copy-paste ready for GitHub)
- Include 4 issue templates with labels and milestones
- Include label definitions and milestone setup instructions
- Include step-by-step deployment after issue registration

This enables non-technical team members to post issues
directly to GitHub with proper format.

Related Issue: #1"
```

---

## 커밋 메시지 형식 설명

### Conventional Commits 구조

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Type**:
- `feat`: 신규 기능
- `fix`: 버그 수정
- `docs`: 문서 수정
- `style`: 코드 스타일 (기능 무관)
- `refactor`: 리팩토링 (기능 무관)
- `chore`: 빌드/설정 (기능 무관)
- `test`: 테스트 추가

**Scope** (선택):
- 영향받는 영역 (예: `dashboard`, `i18n`, `deploy`)

**Subject**:
- 명령형 현재형 (Imperative mood)
- 첫 글자 소문자
- 마침표 없음
- 50자 이하

**Body** (선택):
- 무엇을 왜 했는지 설명
- 72자 이하로 줄 바꿈
- 여러 줄 가능

**Footer** (선택):
- `Related Issue: #123, #456`
- `Closes #123` (자동 close)
- `Breaking Change: ...` (호환성 깨짐)

---

## 예시: 다양한 커밋 메시지

### 예시 1: 대시보드 레이아웃 통일

```
feat(dashboard): unify GM layout across all dashboards

- Apply 2-column grid to GM/Admin/Master/Agent/Investor dashboards
- Set chart height to 260px for consistency
- Extract common CSS to includes/gm_dashboard_ui.php
- Add responsive design (1 column on mobile < 980px)

All dashboards now follow GM format for size/position.
Data and display logic remain unchanged (maintenance principle).

Closes #5"
```

### 예시 2: i18n 키 추가

```
feat(i18n): add master list translations

- Add 16 new i18n keys to lang/ko.php
- Add 16 new i18n keys to lang/ja.php
- Add 16 new i18n keys to lang/en.php

Master list now supports ko/ja/en language switching.

Keys added:
* master.list.title
* master.list.empty
* common.name, common.email, common.phone, common.country, ...

Related Issue: #6"
```

### 예시 3: 버그 수정

```
fix(admin-dashboard): resolve missing PHP tag issue

- Move country_name_map inside PHP tags
- Prevent PHP code from appearing as HTML output
- Restore proper JSON encoding in JavaScript

Admin dashboard now displays correct language labels
without PHP syntax appearing in rendered HTML.

Closes #8"
```

### 예시 4: 안전장치 추가

```
fix(agent-dividend): add session and i18n safety checks

- Add session_start() guard in agent_dividend_chart.php
- Add i18n function fallback if not loaded
- Ensure script works in various include scenarios

Prevents undefined variable/function errors when
agent_dividend_chart.php is included from different contexts.

Related Issue: #2"
```

---

## 좋은 커밋 메시지의 요소

### ✅ 좋은 예시

```
feat(deploy): add manifest validation for deployment

* Generate deployment target file list
* Verify server files after upload
* Fail deployment if files are missing
* Log deployment results with timestamp
* Document deployment procedure

This prevents recurring issues of missing files
after deployment (past issue: missing lang/ja.php).

Closes #10
```

**이유**:
- Type + Scope 명확함
- Subject: 명령형, 간결
- Body: 무엇을 했는지 설명
- Footer: 이슈 연결

### ❌ 나쁜 예시

```
fixed stuff
```

**문제**:
- Type/Scope 없음
- 무엇을 했는지 불명확
- 이슈 연결 없음
- git log에서 추적 불가

---

## Rebase 및 Squash 활용

### 여러 커밋을 하나로 정리 (기능 완성 시)

```bash
# 마지막 3개 커밋을 하나로 합치기
git rebase -i HEAD~3

# 대화형 모드:
# pick aaa1111 ...
# squash bbb2222 ...  (s로 축약 가능)
# squash ccc3333 ...

# 재작성된 커밋 메시지로 최종 확정
```

### 커밋 메시지 수정 (이미 커밋한 후)

```bash
# 마지막 커밋 메시지만 수정
git commit --amend

# 특정 과거 커밋 수정 (조심!)
git rebase -i HEAD~5
# 수정할 커밋을 'reword'로 변경
```

---

## 일일 개발 패턴

### Day 1: 신규 기능 개발

```bash
# feature 브랜치 생성
git checkout -b feature/gm-dashboard-unification

# 개발 진행 (여러 커밋)
git commit -m "feat(dashboard): add gm-wrap container"
git commit -m "feat(dashboard): add gm-grid 2-column layout"
git commit -m "feat(dashboard): set chart height to 260px"
git commit -m "feat(dashboard): extract common CSS"

# 정리: 4개 커밋을 1-2개로 squash (optional)
git rebase -i origin/dev
```

### Day 2: PR 생성 & 리뷰

```bash
# PR 생성
git push origin feature/gm-dashboard-unification

# GitHub: PR 생성 → 템플릿 자동 로드 → 체크리스트 확인

# 리뷰 피드백 적용
git commit -m "fix: address review comments"

# 추가 커밋은 자동으로 PR에 포함됨
git push origin feature/gm-dashboard-unification
```

### Day 3: 병합 & 배포

```bash
# dev에 병합 (GitHub UI 또는 CLI)
git merge feature/gm-dashboard-unification

# 회귀 테스트 (REGRESSION_CHECKLIST.md)
# ... (ko/ja/en 확인, 레이아웃 확인, 파일 검증)

# main에 병합 준비
git checkout main
git pull origin main
git merge dev

# Release tag 생성
git tag -a v2026.01.18 -m "Release v2026.01.18

- Add GM dashboard layout unification
- Add i18n support for master list
- Add deployment manifest validation
- Fix missing file issues"

git push origin v2026.01.18
```

---

## 참고: git log 보기

### 예쁜 포맷으로 보기

```bash
# 한 줄씩
git log --oneline -10

# 그래프로 (브랜치 시각화)
git log --graph --oneline --all -20

# 상세 정보
git log --format="%H %s %an %ai" -10
```

### 커밋 타입별 필터링

```bash
# feat 타입만 보기
git log --oneline --grep="^feat" -20

# 특정 파일의 커밋만
git log --oneline -- docs/BRANCH_POLICY.md
```

---

## 베스트 프랙티스

### ✅ 해야 할 것

- [ ] 기능별로 커밋 분리 (너무 크지 않게)
- [ ] 명확한 메시지 작성
- [ ] 관련 이슈 링크 포함
- [ ] 배포 전 squash로 정리
- [ ] main 브랜치 매우 깔끔 유지

### ❌ 하지 말 것

- [ ] main에 직접 push (브랜치 보호)
- [ ] 메시지 없이 커밋 (`git commit -m ""`)
- [ ] 무의미한 "update", "fix typo" 반복
- [ ] 여러 기능을 하나의 커밋에 혼합
- [ ] 과거 커밋 강제 수정 (push -f) - 공유 브랜치에서

---

**Version**: `0118_v4` (2026-01-18)
**Reference**: `docs/BRANCH_POLICY.md`, `docs/WORKFLOW.md`
