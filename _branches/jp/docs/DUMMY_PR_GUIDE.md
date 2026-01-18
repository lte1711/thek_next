# 더미 PR 생성 가이드 (Issue #1 검증용)

**이 가이드를 따라 PR 템플릿 + 체크리스트가 정상 작동하는지 검증합니다.**

---

## 📋 목표

Issue #1 (Git 브랜치 전략 및 릴리즈 규칙 확정)의 **Acceptance Criteria** 검증:

- ✅ PR 생성 시 템플릿 자동 로드
- ✅ 체크리스트 항목 표시
- ✅ 협력자의 PR 검토/승인 흐름 기록
- ✅ 머지 후 main 브랜치에서 직접 push 불가능 (브랜치 보호)

---

## 🔧 Step 1: 브랜치 생성

```bash
# dev 브랜치에서 시작
git checkout dev
git pull origin dev

# 더미 PR 브랜치 생성
git checkout -b docs/stabilization-pr-template-demo

# 또는 feature 네이밍으로
git checkout -b feature/test-pr-template
```

---

## ✏️ Step 2: 더미 커밋 생성

### 방법 A: 기존 파일에 작은 주석 추가 (권장)

```bash
# README 또는 새 파일에 코멘트 추가
echo "# v2026-01 Stabilization - PR Template Demo

이 PR은 PR 템플릿과 체크리스트가 정상 작동하는지 검증합니다.

## 검증 항목
- [ ] PR 템플릿 자동 로드
- [ ] 체크리스트 표시
- [ ] 협력자 리뷰 가능
- [ ] 머지 후 기록 남음
" > docs/PR_TEMPLATE_DEMO.md

git add docs/PR_TEMPLATE_DEMO.md
```

### 방법 B: 기존 문서에 작은 수정 추가

```bash
# 기존 WORKFLOW.md 끝에 한 줄 추가
echo "
---

**Demo Note**: PR template validation completed on 2026-01-18" >> docs/WORKFLOW.md

git add docs/WORKFLOW.md
```

---

## 💾 Step 3: Conventional Commits 형식으로 커밋

```bash
git commit -m "docs: add PR template validation test

- Create dummy PR to verify template auto-loading
- Verify checklist items display correctly
- Record team collaboration flow

This is a test commit for Issue #1 acceptance criteria.

Related Issue: #1"
```

**commit 메시지 형식**:
- **Type**: `docs:` (문서 수정)
- **Subject**: PR 템플릿 검증 관련
- **Body**: 테스트 목표
- **Footer**: `Related Issue: #1`

---

## 🚀 Step 4: Push & PR 생성

### 4-1: 브랜치를 원격에 Push

```bash
git push origin docs/stabilization-pr-template-demo

# 또는 feature 브랜치면
git push origin feature/test-pr-template
```

### 4-2: GitHub에서 PR 생성

1. **GitHub 저장소** → **Pull requests** 탭
2. **"New pull request"** 버튼 클릭
3. **Base**: `main` ← **Compare**: `docs/stabilization-pr-template-demo`
4. **"Create pull request"** 클릭

> 이 순간 `.github/PULL_REQUEST_TEMPLATE.md`가 자동 로드됩니다! ✨

---

## ✅ Step 5: PR 템플릿 검증

PR 생성 후 아래를 확인하세요:

### 템플릿 자동 로드 확인

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

...
```

**✅ 확인 항목**:
- [ ] 템플릿이 자동으로 로드되었나?
- [ ] 체크리스트 항목들이 보이나?
- [ ] 전체 구조가 정상인가?

---

## 📝 Step 6: PR 내용 작성

### 6-1: 변경 내용 섹션 채우기

```markdown
## 📝 변경 내용

- Add PR template demo document
- Verify template auto-loading
- Test checklist functionality
- Document team review workflow for Issue #1
```

### 6-2: 관련 이슈 연결

```markdown
## 🔗 관련 이슈

- Closes #1
```

이렇게 쓰면 **PR 머지 시 이슈 #1이 자동으로 닫힙니다!**

### 6-3: 체크리스트 확인

현재 PR이 "문서 추가"이므로, 이 항목들만 체크:

```markdown
### 파일 & 경로

- [x] 신규 파일 추가 시: 경로/네이밍 규칙 준수
  → ✅ docs/PR_TEMPLATE_DEMO.md 추가
- [x] Include 파일: `/includes/` 경로 확인
  → 해당 없음 (문서 추가만)
- [x] 배포 대상 파일 누락 없음 (Manifest 기준)
  → 문서는 Manifest 불필요

### 회귀 테스트 (필요 시)

- [x] 공통 영역 수정: 없음 (문서만 추가)
- [x] 다국어 확인: 해당 없음
- [x] 주요 페이지: 200 OK, 에러 없음
```

---

## 👥 Step 7: Code Review 단계

### 7-1: 누군가에게 리뷰 요청

PR 페이지 우측 **"Reviewers"** 섹션에서:
- 팀 멤버 선택
- 또는 CODEOWNERS 자동 할당

### 7-2: 리뷰어 확인 작업

리뷰어가 해야 할 일:

```
1. "Files changed" 탭 확인
   → 추가된 파일/수정사항 검토
   
2. 변경 내용 검토
   → "Looks good to me"인지 확인
   
3. 코멘트 남기기 (옵션)
   → "Approve" 또는 "Request changes"
   
4. 승인 클릭
   → "Approve" 버튼
```

**리뷰 코멘트 예시**:
```
✅ PR template loads correctly
✅ Checklist items are visible
✅ Template format matches docs/BRANCH_POLICY.md

Approved! Ready to merge.
```

---

## 🔀 Step 8: dev 브랜치에 병합

### 8-1: PR 머지

PR 페이지에서:
1. **"Merge pull request"** 버튼 클릭
2. **"Confirm merge"** 클릭

> GitHub UI에서 자동으로 병합됩니다.

### 8-2: 커밋 메시지 확인

병합 후 커밋이 생성됩니다:
```
Merge pull request #123 from docs/stabilization-pr-template-demo

docs: add PR template validation test
```

---

## 🔒 Step 9: main 브랜치 보호 규칙 검증

### 9-1: main에 직접 push 시도 (금지되어야 함)

```bash
git checkout main
git pull origin main

# 브랜치 보호로 인해 push 실패해야 함
git push origin main
```

**예상 결과**:
```
remote: error: protected branch hook declined
remote: [pre-receive hook declined]
fatal: the remote end hung up unexpectedly
```

### 9-2: main으로의 PR 생성만 가능

```bash
# feature → main도 PR을 통해서만 가능
git checkout -b feature/test-main-pr
git add some_file.txt
git commit -m "test: try pushing to main via PR"
git push origin feature/test-main-pr

# GitHub에서 PR 생성 → main 선택
# → PR로만 머지 가능 (직접 push 불가)
```

---

## 📊 Step 10: 검증 결과 기록

### 체크리스트 (모두 확인 필수)

- [ ] **PR 템플릿 자동 로드**: ✅ 완료
  - PR 생성 시 `.github/PULL_REQUEST_TEMPLATE.md` 자동 로드됨
  
- [ ] **체크리스트 표시**: ✅ 완료
  - 모든 체크박스 항목이 PR 본문에 표시됨
  
- [ ] **협력자 리뷰**: ✅ 완료
  - 리뷰어 지정 가능, 코멘트/승인 가능
  
- [ ] **기록 남음**: ✅ 완료
  - PR 머지 후 커밋 히스토리에 기록됨
  - `git log --oneline`에서 확인 가능
  
- [ ] **main 브랜치 보호**: ✅ 완료
  - main에 직접 push 불가능
  - PR을 통해서만 병합 가능

---

## 📝 최종 기록

### git log에서 확인

```bash
git log --oneline -5

# 예상 결과:
# abc1234 Merge pull request #123 from docs/stabilization-pr-template-demo
# def5678 docs: add PR template validation test
# ... (기존 커밋들)
```

### GitHub Timeline

PR 페이지에서:
- 커밋 1건 생성됨
- 리뷰 1건 (Approve)
- Merge 1건
- Related issue #1과 연결됨

---

## 🎯 Issue #1 Acceptance Criteria 검증 완료

✅ **조건 1: main 브랜치에 직접 push 불가**
```
→ git push origin main 시 에러 발생 확인
```

✅ **조건 2: dev → main은 PR로만 가능**
```
→ PR 생성/머지 흐름 정상 작동 확인
```

✅ **조건 3: PR 생성 시 템플릿 자동 로드**
```
→ .github/PULL_REQUEST_TEMPLATE.md 자동 로드 확인
```

✅ **조건 4: 더미 PR 기반으로 기록 남음**
```
→ git log / GitHub PR 페이지에서 완전한 기록 확인
```

---

## 💡 Troubleshooting

### PR 생성 시 템플릿이 안 나와요

```bash
# 1. 파일 경로 확인
ls -la .github/PULL_REQUEST_TEMPLATE.md

# 2. 파일명이 정확한지 확인
# 정확한 경로: .github/PULL_REQUEST_TEMPLATE.md (md 확장자)

# 3. 파일 내용 확인
cat .github/PULL_REQUEST_TEMPLATE.md | head -20

# 4. GitHub에 push 후 새로고침
git push origin main
# (GitHub 웹브라우저에서 새로고침)
```

### main 브랜치에 push가 성공해버렸어요

```bash
# 브랜치 보호가 설정되지 않은 것 같습니다.
# GitHub Repository Settings 확인 필요:

# Settings → Branches → main 선택
# "Protect this branch" 체크박스 활성화 확인
# - "Require a pull request before merging" ✅
# - "Require status checks to pass" ✅
# - "Require code reviews" ✅ (최소 1명)
```

### 이미 dev에 병합했는데 다시 PR을 만들고 싶어요

```bash
# 새로운 브랜치 생성
git checkout dev
git pull origin dev

# 원래 브랜치 삭제 (선택)
git branch -D docs/stabilization-pr-template-demo
git push origin --delete docs/stabilization-pr-template-demo

# 새 브랜치로 다시
git checkout -b docs/stabilization-pr-template-demo-v2
echo "Updated content" >> docs/PR_TEMPLATE_DEMO.md
git add docs/PR_TEMPLATE_DEMO.md
git commit -m "docs: update PR template demo v2"
git push origin docs/stabilization-pr-template-demo-v2
```

---

**Version**: `0118_v4` (2026-01-18)  
**Purpose**: Issue #1 Acceptance Criteria Validation  
**Next**: 실제 PR 생성 후 이 가이드 기반으로 검증
