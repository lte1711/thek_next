# Branch & Synchronization Policy (KO/JP)

## 기준
- **기준 소스**: `0118_v4` (2026-01-18 확정)
- **소스 수정/버전 관리**: GitHub (main/dev 기반)
- **기획/정리 문서**: Claude (토론/분석)
- **오케스트레이션**: 백설이 (기준 제시/최종 확정)

---

## 브랜치 구조 & 역할

### `main` (운영 배포 브랜치)
- **역할**: 프로덕션 배포 기준점
- **정책**: 직접 push 금지, PR 필수 (브랜치 보호)
- **머지**: `dev` → `main` (회귀 체크리스트 후)
- **태그**: `vYYYY.MM.DD` (예: `v2026.01.18`)

### `dev` (통합 개발 브랜치)
- **역할**: 테스트/QA 기준점
- **정책**: feature/hotfix에서 PR 받음
- **머지**: 코드 리뷰 + CI 통과 후

### `feature/*` (기능 단위 개발)
- **네이밍**: `feature/기능명` (예: `feature/gm-dashboard-layout`)
- **베이스**: `dev`에서 생성
- **PR**: `dev`로 제출

### `hotfix/*` (운영 긴급 수정)
- **네이밍**: `hotfix/버그명` (예: `hotfix/missing-file-index`)
- **베이스**: `main`에서 생성
- **PR**: `main` → `dev` 동시 머지

---

## KO/JP 동기화 규칙

### 공통 영역 (동시 반영 필수)

아래 디렉토리/파일 수정 시 **KO/JP 양쪽에 동일 반영**:

```
/includes/         ← i18n.php, gm_dashboard_ui.php 등
/lang/             ← ko.php, ja.php, en.php (언어 키)
/.github/          ← 레포 정책(PR 템플릿 등)
/docs/             ← 공통 문서
```

**반영 절차**:
1. KO 기준으로 수정 (현재: `_branches/jp`)
2. JP 브랜치에도 동일 반영 확인
3. 회귀 체크리스트 수행 (ko/ja/en 모두)
4. PR 제출 시 양쪽 분기 명시

### JP 전용 영역 (JP만 수정)

```
/_branches/jp/     ← JP 전용 UI/로직
```

JP 전용 기능/문구는 JP 영역에서만 수정한다.
**단, 공통 함수나 include를 수정했다면 JP와 KO의 호출점 모두 점검**한다.

---

## 언어 파일 관리 규칙

### 최신 기준
- `lang/ko.php` (한국어 - 주요 버전)
- `lang/ja.php` (일본어)
- `lang/en.php` (영어)

### 키 정책
- 페이지별/기능별 **네임스페이스** 권장
  - 예: `dashboard.pnl_by_country`, `master.list.title`
- **하드코딩 금지**: 사용자 노출 텍스트는 `t()` 함수 사용
- 신규 키 추가 시 3개 언어 모두 동시 추가

### 검증
- PR 머지 전: `lang/` 디렉토리의 모든 파일이 **동일한 키 구조**를 갖는지 확인

---

## 배포 체크리스트

공통 영역 수정 후 배포 전:

- [ ] KO/JP 양쪽에서 문법 에러(`get_errors`) 없음
- [ ] lang 파일 키 일치성 확인
- [ ] 회귀 테스트 (ko/ja/en) 통과
- [ ] manifest 검증 통과
- [ ] Release tag 생성 준비

---

## 예시: 신규 기능 추가 흐름

```
1. 기능 개발 (feature 브랜치)
   ├─ KO 로직 구현 (/includes/, /lang/ko.php 등)
   └─ JP도 동일하게 (/lang/ja.php, /lang/en.php 등)

2. PR → dev
   ├─ 코드 리뷰
   └─ CI 통과

3. dev → main (회귀 체크)
   ├─ 언어 확인 (ko/ja/en)
   ├─ 레이아웃 확인 (GM 형식)
   └─ 파일 누락 검증

4. Release tag + Deploy
   ├─ vYYYY.MM.DD 태그 생성
   └─ 배포 후 manifest 검증
```

---

## 🏷️ 릴리즈 태그 규칙

### 태그 형식

```
vYYYY.MM.DD          기본 형식 (예: v2026.01.18)
v2026.01.18-hotfix.1 핫픽스 (예: v2026.01.18-hotfix.1)
```

### 태그 생성 타이밍

- **Regular Release**: `main` 브랜치에 병합 후
- **Hotfix Release**: 긴급 수정 완료 후 `main`/`dev` 동시 머지 후

### 태그 생성 명령

```bash
# Regular Release (main 브랜치에서)
git tag -a v2026.01.18 -m "Release v2026.01.18 - Dashboard Unification"
git push origin v2026.01.18

# Hotfix Release
git tag -a v2026.01.18-hotfix.1 -m "Hotfix v2026.01.18-hotfix.1 - Missing File Fix"
git push origin v2026.01.18-hotfix.1
```

### 태그 명시 위치

- **`docs/BRANCH_POLICY.md`**: 이 섹션 (정책)
- **`docs/DEPLOY.md`**: Step 5 (배포 절차)
- **`docs/WORKFLOW.md`**: Release Tag 규칙 섹션
- **`docs/RELEASE_NOTES_TEMPLATE.md`**: 태그별 릴리즈 노트 템플릿 (신규)
- **GitHub Release**: 각 tag 별로 Release Notes 작성

### 릴리즈 노트 작성

- `docs/RELEASE_NOTES_TEMPLATE.md` 참고
- GitHub Releases에 자동 생성 또는 수동 작성
- 배포 항목, 변경사항, 테스트 결과 기재

---

## FAQ

**Q: JP 전용 수정인데 lang/ko.php도 건드렸어?**
A: 공통 include를 수정했다면 KO의 호출점도 테스트해야 합니다. 양쪽 분기에서 회귀 테스트를 수행하세요.

**Q: main에 직접 push했는데?**
A: 불가능합니다. 브랜치 보호 규칙이 적용되어 있으니 PR을 통해 제출하세요.

---

**Version**: `0118_v4` (2026-01-18)
**Last Updated**: 2026-01-18
