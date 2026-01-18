# Git Commit Package

## 📝 커밋 메시지 (Conventional Commits)

```
docs: add operations guide to WORKFLOW.md

- 1페이지 실행 절차 추가 (feature/hotfix/배포)
- GitHub 브랜치 보호 규칙 상세 명시 (Squash merge 통일)
- 릴리즈 태그 규칙 및 노트 템플릿 추가
- PR 체크리스트 개선안 (배포 영향도/보안)

Closes #1
```

---

## 📦 변경 파일 목록

### 수정
- `docs/WORKFLOW.md` - 운영 절차 섹션 추가
- `docs/v2026-01-STABILIZATION.md` - WORKFLOW.md 링크 업데이트

### 신규 추가
- 없음

### 삭제
- 없음

---

## 📄 PR 본문 텍스트

```markdown
## 📝 변경 내용

**docs/WORKFLOW.md에 운영 절차 섹션 추가** (Issue #1 완료)

### 추가된 내용
1. **1페이지 실행 절차**
   - 일반 개발 흐름 (feature)
   - 긴급 수정 흐름 (hotfix)
   - 운영 배포 흐름 (dev → main)

2. **GitHub 브랜치 보호 규칙**
   - main 브랜치 필수 설정 (체크박스 상세)
   - Squash merge 통일 (Linear history 적용)
   - dev 브랜치 권장 설정

3. **릴리즈 관리**
   - 태그 규칙: vYYYY.MM.DD (실제 배포일 기준 강조)
   - 릴리즈 노트 템플릿 (GitHub Releases용)
   - 변경 로그 자동 생성 명령

4. **PR 체크리스트 개선**
   - 배포 영향도 항목 추가
   - 보안 체크 항목 추가
   - 자동화 제안

### 정합성 수정
- ✅ 날짜 예시를 vYYYY.MM.DD로 일반화 (복붙 사고 방지)
- ✅ Linear history + Squash merge로 통일 (충돌 해결)
- ✅ Release by를 @실제배포담당자로 수정

---

## 🔗 관련 이슈

- Closes #1: GitHub 브랜치 전략 및 릴리즈 규칙

---

## ✅ 체크리스트

### 문서 & i18n
- [x] 문서 추가: WORKFLOW.md 운영 절차 섹션
- [x] 링크 업데이트: v2026-01-STABILIZATION.md
- [ ] 언어 파일 변경 없음

### 파일 & 경로
- [x] 신규 파일 없음
- [x] 경로/네이밍 규칙 준수

### 회귀 테스트
- [ ] 공통 영역 수정 없음 (docs/ 변경만)
- [x] 마크다운 형식 검증 완료

---

## 📋 추가 정보

### Release Notes용 3줄 요약

```
### 📚 문서 개선
- **WORKFLOW.md**: 1페이지 실행 절차 추가 (feature/hotfix/배포 흐름)
- **브랜치 보호**: GitHub 설정 상세 가이드 및 Squash merge 통일
- **릴리즈 관리**: 태그 규칙(vYYYY.MM.DD) 및 노트 템플릿 제공
```

### v2026-01-STABILIZATION.md 연동

**88번 라인 수정 필요**:
```markdown
### `WORKFLOW.md` - "진행 흐름이 어떻게 되나요?"
- **대상**: 개발팀, QA팀, 배포 담당자
- **읽을 때**: 처음 PR 작성할 때, 배포 전
- **핵심**:
  - Issue → PR → Release 플로우 (Mermaid 포함)
  - Conventional Commits 규칙
  - **운영 절차 실행 가이드** ← 추가
  - 다양한 상황별 대응 (버그 발견, 파일 누락 등)
```

---

## 🎯 Conventional Commits 형식

PR 제목: `docs: add operations guide to WORKFLOW.md`

**카테고리**: docs (문서 수정)  
**범위**: WORKFLOW.md 운영 절차 추가

---

**감사합니다! 리뷰를 대기 중입니다.** 🚀
```

---

## 🚀 적용 순서

### 1. docs/WORKFLOW.md 업데이트
```bash
cd thek_repo

# 기존 WORKFLOW.md 백업
cp docs/WORKFLOW.md docs/WORKFLOW.md.backup

# 새 운영 절차 섹션을 240번 라인 뒤에 추가
# (240번 라인: **Last Updated**: 2026-01-18)
```

**추가 위치**: 기존 WORKFLOW.md 끝에 추가

**섹션 제목**:
```markdown
---

# 🔄 운영 절차 (Operations Guide)

> **이 섹션은 일상 개발/배포의 실행 가이드입니다.**  
> **정책/배경은 [BRANCH_POLICY.md](./BRANCH_POLICY.md) 참고**

[나머지 내용...]
```

### 2. docs/v2026-01-STABILIZATION.md 업데이트

**88-94번 라인 수정**:
```markdown
### `WORKFLOW.md` - "진행 흐름이 어떻게 되나요?"
- **대상**: 개발팀, QA팀, 배포 담당자
- **읽을 때**: 처음 PR 작성할 때, 배포 전
- **핵심**:
  - Issue → PR → Release 플로우 (Mermaid 포함)
  - Conventional Commits 규칙
  - **운영 절차 실행 가이드 (1페이지)** ← 추가
  - 다양한 상황별 대응 (버그 발견, 파일 누락 등)
```

### 3. Git 작업

```bash
# 브랜치 생성
git checkout -b docs/workflow-operations-guide

# 파일 추가
git add docs/WORKFLOW.md docs/v2026-01-STABILIZATION.md

# 커밋
git commit -m "docs: add operations guide to WORKFLOW.md

- 1페이지 실행 절차 추가 (feature/hotfix/배포)
- GitHub 브랜치 보호 규칙 상세 명시 (Squash merge 통일)
- 릴리즈 태그 규칙 및 노트 템플릿 추가
- PR 체크리스트 개선안 (배포 영향도/보안)

Closes #1"

# 푸시
git push origin docs/workflow-operations-guide
```

### 4. GitHub PR 생성

- **Base**: `main` (또는 `dev`)
- **Compare**: `docs/workflow-operations-guide`
- **Title**: `docs: add operations guide to WORKFLOW.md`
- **Body**: 위의 PR 본문 텍스트 사용

---

## 📊 예상 결과

### PR 머지 후
- ✅ WORKFLOW.md에 실행 가이드 추가
- ✅ v2026-01-STABILIZATION.md 인덱스 업데이트
- ✅ Issue #1 자동 닫힘

### Release Notes에 포함될 내용
```markdown
### 📚 문서 개선
- **WORKFLOW.md**: 1페이지 실행 절차 추가 (feature/hotfix/배포 흐름)
- **브랜치 보호**: GitHub 설정 상세 가이드 및 Squash merge 통일
- **릴리즈 관리**: 태그 규칙(vYYYY.MM.DD) 및 노트 템플릿 제공
```

---

**준비 완료!** 🎉
