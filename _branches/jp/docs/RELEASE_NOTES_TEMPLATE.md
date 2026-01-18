# Release Notes Template (JP)

**GitHub Release 작성용 템플릿**

---

## 📋 Release Title 형식

```
v2026.MM.DD - [간단한 설명]
```

**예시**:
```
v2026.01.18 - Dashboard Unification & Stabilization
v2026.02.15 - Hotfix: Login Session Fix
```

---

## 📝 Release Description 템플릿

아래 마크다운을 복사하여 GitHub Release 본문에 붙여넣으세요.

```markdown
# Release v2026.MM.DD

**배포일**: YYYY-MM-DD  
**기준 브랜치**: main  
**이전 버전**: vYYYY.MM.DD

---

## 🎯 주요 변경 사항

### ✨ 신규 기능
- (예) GM 대시보드 레이아웃 통합
- (예) 다국어 지원 (ko/ja/en)

### 🐛 버그 수정
- (예) 로그인 세션 만료 문제 해결
- (예) 일본어 텍스트 깨짐 수정

### 📚 문서 개선
- (예) 배포 매니페스트 검증 프로세스 문서화
- (예) PR 템플릿 추가

### 🔧 기술 개선
- (예) 공통 CSS 모듈화
- (예) 차트 크기 통일 (260px)

---

## 📦 변경된 파일

### 핵심 파일
- `파일명.php` (신규/수정/삭제)
- `includes/파일명.php`

### 언어 파일
- `lang/ko.php` (+N keys)
- `lang/ja.php` (+N keys)
- `lang/en.php` (+N keys)

### 문서
- `docs/파일명.md`

---

## 📋 배포 체크리스트

### 배포 전 확인 완료
- [ ] 회귀 테스트 (ko/ja/en) 통과
- [ ] KO/JP 양쪽 환경 검증
- [ ] DB 스키마 변경 여부 확인
- [ ] 파일 누락 없음 (manifest 검증)
- [ ] 언어 파일 키 일치 확인

### 배포 후 확인 필요
- [ ] 운영 서버 에러 로그 확인 (1시간)
- [ ] 주요 페이지 접속 확인
- [ ] 언어 전환 (ko/ja/en) 정상 작동
- [ ] 사용자 피드백 모니터링

---

## ⚠️ Breaking Changes

**없음** (또는 상세 기재)

---

## 🔗 관련 이슈 & PR

- Closes #번호
- Related: #번호

---

## 🚀 배포 방법

### 1. 배포 전
```bash
# dev 브랜치 테스트 완료 확인
git checkout dev
php scripts/regression_test.php
```

### 2. Release PR 생성
```bash
git checkout main
git pull origin main
git checkout -b release/v2026.MM.DD dev
git push -u origin release/v2026.MM.DD
```

### 3. GitHub에서 PR 생성 & Merge
- Base: main
- Compare: release/v2026.MM.DD
- Squash merge 후 태그 생성

### 4. 태그 생성 & 푸시
```bash
git checkout main
git pull origin main
git tag -a v2026.MM.DD -m "Release v2026.MM.DD"
git push origin main v2026.MM.DD
```

### 5. 배포 실행
```bash
# Manifest 검증
php scripts/verify_manifest.php

# 배포 (rsync/FTP 등)
rsync -av --files-from=docs/DEPLOY.md ./ user@server:/path/
```

### 6. 배포 후 검증
```bash
# 서버 파일 확인
ssh user@server "ls -la /path/ | grep '파일명'"

# 로그 확인
ssh user@server "tail -f /path/logs/error.log"
```

---

## 📞 문의

**Release by**: @배포담당자  
**Reviewed by**: @리뷰어1, @리뷰어2  
**Deploy Target**: Production (15.164.165.240)

---

## 📊 배포 지표

| 항목 | 값 |
|------|-----|
| 변경 파일 수 | N개 |
| 추가 언어 키 | N개 (ko/ja/en) |
| 관련 이슈 | #N, #N |
| 테스트 항목 | N개 통과 |

---

**Version**: v2026.MM.DD  
**Last Updated**: YYYY-MM-DD
```

---

## 🎯 GitHub Release 생성 단계

### 웹 UI에서

1. 레포 → **Releases** 탭 클릭
2. **Draft a new release** 클릭
3. **Choose a tag** → `v2026.MM.DD` 입력 (새 태그 생성)
4. **Target**: `main` 선택
5. **Release title**: `v2026.MM.DD - [설명]` 입력
6. **Description**: 위 템플릿 복붙
7. **Publish release** 클릭

### CLI에서

```bash
# 1. 태그 생성
git tag -a v2026.MM.DD -m "Release v2026.MM.DD

- 주요 변경사항 1
- 주요 변경사항 2
- 주요 변경사항 3

Closes #N"

# 2. 푸시
git push origin v2026.MM.DD

# 3. GitHub에서 Release Notes 추가
# (웹 UI에서 Releases → 해당 태그 → Edit → Description 추가)
```

---

## 💡 작성 팁

### 제목 작성
- ✅ `v2026.01.18 - Dashboard Unification`
- ❌ `Release 2026.01.18` (v 접두사 없음)
- ❌ `v1.2.3` (날짜 형식 사용)

### 본문 작성
- **신규 기능**: 사용자에게 보이는 변화 중심
- **버그 수정**: 문제/해결 명확히
- **기술 개선**: 개발자/운영자 관점 설명
- **Breaking Changes**: 없으면 "없음", 있으면 마이그레이션 가이드 필수

### 파일 목록
- 핵심 파일만 나열 (10개 이하 권장)
- 경로 포함하여 명확히
- (신규/수정/삭제) 표시

### 체크리스트
- 배포 전: 반드시 완료해야 하는 항목
- 배포 후: 모니터링 필요 항목

---

## 📚 참고 문서

- **브랜치 전략**: `docs/BRANCH_POLICY.md`
- **워크플로우**: `docs/WORKFLOW.md`
- **배포 가이드**: `docs/DEPLOY.md`
- **회귀 테스트**: `docs/REGRESSION_CHECKLIST.md`
- **커밋 규칙**: `docs/CONVENTIONAL_COMMITS.md`

---

**Template Version**: 0118_v2 (2026-01-18)  
**Last Updated**: 2026-01-18
