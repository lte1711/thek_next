# v2026-01 Stabilization - 안정화 패키지

**THEK-NEXT MLM Platform의 운영 안정성을 위한 4단계 안정화 프로젝트**

---

## 📦 이 패키지에 포함된 것

### 1️⃣ 정책 문서

| 문서 | 용도 | 주요 내용 |
|------|------|---------|
| `BRANCH_POLICY.md` | 팀 기준점 | 브랜치 전략, KO/JP 동기화 규칙 |
| `WORKFLOW.md` | 시각적 가이드 | Mermaid 플로우도 + 커밋 규칙 |
| `DEPLOY.md` | 배포 매뉴얼 | 5단계 배포 절차, Manifest 검증 |
| `REGRESSION_CHECKLIST.md` | 배포 게이트 | 7가지 필수 확인 항목 |

### 2️⃣ 코드 템플릿

| 파일 | 위치 | 용도 |
|------|------|------|
| `PULL_REQUEST_TEMPLATE.md` | `.github/` | PR 작성 시 자동 로드 (체크리스트 포함) |

### 3️⃣ GitHub 이슈 세트

| 이슈 | 우선순위 | 라벨 |
|------|---------|------|
| 1. 브랜치 전략 확정 | 🔴 High | `core`, `process` |
| 2. 배포 Manifest 검증 | 🔴 High | `deploy`, `priority:high` |
| 3. KO/JP 동기화 정책 | 🟡 Medium | `docs`, `process` |
| 4. 회귀 테스트 구축 | 🟡 Medium | `qa`, `process` |

---

## 🚀 빠른 시작 (3단계)

### Step 1: 문서 검토 (10분)

아래 순서대로 읽으세요:

1. **`BRANCH_POLICY.md`** ← 팀의 기준점
2. **`WORKFLOW.md`** ← 시각적으로 이해
3. **`DEPLOY.md`** ← 배포 절차 확인
4. **`REGRESSION_CHECKLIST.md`** ← 배포 전 체크

### Step 2: GitHub 이슈 등록 (15분)

1. `docs/GITHUB_ISSUES_TEMPLATE.md` 파일 오픈
2. 4개 이슈 내용을 GitHub Issues에 복붙
3. 라벨/마일스톤 추가
4. main 브랜치 보호 설정

### Step 3: 팀 공유 (5분)

1. PR 템플릿 소개 (`.github/PULL_REQUEST_TEMPLATE.md`)
2. 회귀 테스트 체크리스트 공유
3. 배포 Manifest 검증 프로세스 안내

---

## 📋 파일 구조

```
docs/
├── BRANCH_POLICY.md              ← 브랜치 & 동기화 규칙 (팀 기준)
├── WORKFLOW.md                   ← 플로우 다이어그램 & Commits 규칙
├── DEPLOY.md                     ← 배포 절차 & Manifest 검증
├── REGRESSION_CHECKLIST.md       ← 배포 전 필수 체크 7가지
├── GITHUB_ISSUES_TEMPLATE.md     ← GitHub 이슈 복붙용
└── v2026-01-STABILIZATION.md     ← 이 파일 (인덱스)

.github/
└── PULL_REQUEST_TEMPLATE.md      ← PR 작성 시 자동 로드
```

---

## 🎯 각 문서의 역할

### `BRANCH_POLICY.md` - "우리는 이렇게 일해요"
- **대상**: 모든 팀원
- **읽을 때**: 신규 팀원 온보딩, PR 작성 전
- **핵심**: 
  - main/dev/feature/hotfix 역할 구분
  - KO/JP 동기화 규칙 (공통 ↔ 전용)
  - 언어 파일 관리 규칙

### `WORKFLOW.md` - "진행 흐름이 어떻게 되나요?"
- **대상**: 개발팀, QA팀, 배포 담당자
- **읽을 때**: 처음 PR 작성할 때, 배포 전
- **핵심**:
  - Issue → PR → Release 플로우 (Mermaid 포함)
  - Conventional Commits 규칙
  - 다양한 상황별 대응 (버그 발견, 파일 누락 등)

### `DEPLOY.md` - "배포는 어떻게 하는 건가요?"
- **대상**: 배포 담당자, DevOps
- **읽을 때**: 배포 준비할 때, Manifest 검증 방법 필요할 때
- **핵심**:
  - 5단계 배포 절차
  - Manifest 생성 & 검증
  - 누락 파일 발견 시 롤백 절차

### `REGRESSION_CHECKLIST.md` - "배포 전에 뭘 확인해야 하나요?"
- **대상**: QA팀, 배포 담당자
- **읽을 때**: 배포 승인 전 (필수)
- **핵심**:
  - 7가지 필수 확인 항목
  - 언어(ko/ja/en) 전환 테스트
  - GM 레이아웃 유지 확인
  - 파일 누락/404 검증

### `.github/PULL_REQUEST_TEMPLATE.md` - "PR을 어떻게 작성해요?"
- **대상**: 모든 개발자
- **읽을 때**: GitHub PR 생성 시 (자동 로드)
- **핵심**:
  - 변경 내용 설명
  - 체크리스트 (언어/레이아웃/배포)
  - Conventional Commits 형식 안내

---

## ✅ 안정화의 4가지 축

### 🏗️ 축 1: 명확한 브랜치 정책 (Issue 1)
```
문제: "왜 main에 직접 push했어?"
해결: 브랜치 보호 규칙 + PR 필수
효과: 의도하지 않은 배포 차단
```

### 📦 축 2: 배포 파일 검증 (Issue 2)
```
문제: "배포 후 파일이 없네?"
해결: Manifest 생성 → 검증 → 누락 시 배포 중단
효과: 파일 누락 100% 사전 차단
```

### 🔀 축 3: KO/JP 동기화 규칙 (Issue 3)
```
문제: "JP만 깨졌어" / "KO와 다르네"
해결: 공통/전용 범위 명확화 + 동시 테스트
효과: 브랜치 간 불일치 방지
```

### 🧪 축 4: 회귀 테스트 체크리스트 (Issue 4)
```
문제: "배포 후 언어/레이아웃 깨짐"
해결: 배포 전 필수 체크 (7개 항목)
효과: 배포 후 품질 보장
```

---

## 🔄 사용 순서

### 신규 기능 개발할 때

```
1. BRANCH_POLICY.md 읽기
   ↓ "feature/* 브랜치 생성"
2. 코드 개발 + Conventional Commits
   ↓ "feat: 기능명" 형식
3. PR 작성 (템플릿 자동 로드)
   ↓ 체크리스트 확인
4. Code Review + CI 통과
   ↓ dev 병합
```

### 배포할 때

```
1. dev → main PR 생성
   ↓
2. REGRESSION_CHECKLIST.md 수행
   ↓ ko/ja/en 확인, 레이아웃 확인
3. DEPLOY.md 따라 배포 실행
   ↓ Manifest 생성 & 검증
4. Release tag 생성 + 공지
   ↓ 배포 완료
```

### 배포 후 버그 발견할 때

```
1. WORKFLOW.md에서 "hotfix" 섹션 읽기
   ↓
2. hotfix/* 브랜치 생성
   ↓
3. main/dev 동시 머지
   ↓
4. v2026.01.18-hotfix.1 태그 생성
```

---

## 📊 지표 & 모니터링

이 정책 도입 후 추적할 지표:

| 지표 | 목표 | 측정 방법 |
|------|------|---------|
| 파일 누락 이슈 | 0건 | 배포 로그 분석 |
| KO/JP 불일치 | 0건 | 배포 후 회귀 테스트 |
| 배포 시간 | 감소 | 자동화 정책 도입으로 |
| PR 리뷰 시간 | 개선 | 체크리스트 자동화 |

---

## 💡 FAQ

**Q: 이 모든 정책을 바로 시작해야 하나요?**  
A: 아니오. Issue 1 (브랜치 정책)부터 시작하고, 점진적으로 확대하세요.

**Q: 기존 코드도 이 정책을 따라야 하나요?**  
A: 신규 코드부터 적용하세요. 기존 코드는 필요에 따라 리팩토링합니다.

**Q: Manifest 검증 스크립트는 누가 만드나요?**  
A: Issue 2에서 DevOps/리드 개발자가 담당합니다.

**Q: 과거 이슈들도 이 체크리스트로 예방되나요?**  
A: 대부분 예방됩니다. `REGRESSION_CHECKLIST.md`에 과거 이슈 URL 포함합니다.

---

## 🎯 최종 목표

```
v2026-01 이전:
- "왜 또 깨져있어?"
- "누가 이걸 배포했어?"
- "KO/JP 중 뭐가 기준인데?"
→ 비상 대응 상태

v2026-01 이후:
- ✅ 명확한 정책
- ✅ 자동화된 검증
- ✅ 배포 전 필수 체크
→ 예방 체계 완성
```

---

## 📞 문의 & 피드백

각 문서에 대한 질문:
- **BRANCH_POLICY.md**: 형우(기술 리드) / 백설이
- **DEPLOY.md**: DevOps 담당자
- **REGRESSION_CHECKLIST.md**: QA 리드
- **WORKFLOW.md**: 전체 팀

---

**Version**: `0118_v4` (2026-01-18)  
**Status**: ✅ Ready to Deploy to GitHub  
**Last Updated**: 2026-01-18

---

## 🎉 다음 단계

1. ✅ 문서 검토 (이 파일 포함 모든 docs/)
2. ✅ GitHub Issues 등록 (복붙용 파일 참고)
3. ✅ main 브랜치 보호 설정
4. ✅ 팀 교육 & 공유
5. ✅ 첫 번째 PR 시범 운영

**준비 완료!** 🚀
