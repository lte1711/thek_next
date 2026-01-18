# 🔄 운영 절차 (Operations Guide)

> **이 섹션은 일상 개발/배포의 실행 가이드입니다.**  
> **정책/배경은 [BRANCH_POLICY.md](./BRANCH_POLICY.md) 참고**

---

## 📋 일반 개발 흐름 (feature)

```bash
# 1. feature 브랜치 생성
git checkout dev
git pull origin dev
git checkout -b feature/기능명

# 2. 개발 & 커밋 (Conventional Commits)
git add .
git commit -m "feat: 기능 설명"

# 3. dev로 PR 제출
git push origin feature/기능명
# → GitHub에서 PR 생성 (dev ← feature/기능명)

# 4. 코드 리뷰 + CI 통과 후 머지
# → Squash and merge 권장

# 5. 로컬 정리
git checkout dev
git pull origin dev
git branch -d feature/기능명
```

**체크포인트**:
- [ ] PR 템플릿 체크리스트 완료
- [ ] 코드 리뷰 승인 (최소 1명)
- [ ] CI/테스트 통과

---

## 🚨 긴급 수정 흐름 (hotfix)

```bash
# 1. main에서 hotfix 생성
git checkout main
git pull origin main
git checkout -b hotfix/버그명

# 2. 수정 & 커밋
git add .
git commit -m "fix: 긴급 버그 설명"

# 3. main과 dev에 동시 PR
git push origin hotfix/버그명
# → GitHub: main ← hotfix/버그명
# → GitHub: dev ← hotfix/버그명 (별도 PR)

# 4. 머지 후 정리
git checkout main
git pull origin main
git branch -d hotfix/버그명
```

**주의**: hotfix는 main과 dev 양쪽에 반영 필수!

---

## 🚀 운영 배포 흐름 (dev → main)

```bash
# 1. dev 브랜치에서 회귀 테스트 수행
- 언어 전환 (ko/ja/en) 정상 작동
- 주요 페이지 200 OK
- GM 대시보드 레이아웃 정상

# 2. main으로 PR 제출
git checkout dev
git pull origin dev
# → GitHub: main ← dev PR 생성

# 3. 회귀 체크리스트 완료 확인
- [ ] 공통 영역 (includes/, lang/) 테스트
- [ ] KO/JP 양쪽 정상 작동
- [ ] 파일 누락 없음

# 4. PR 머지 (Squash and merge)
# → main 브랜치 업데이트

# 5. 릴리즈 태그 생성
git checkout main
git pull origin main
git tag -a vYYYY.MM.DD -m "Release vYYYY.MM.DD"
git push origin vYYYY.MM.DD

# 6. GitHub Release 생성
# → Releases 탭에서 태그 선택
# → 릴리즈 노트 작성 (아래 템플릿 사용)
# → Publish release
```

---

## ⚙️ GitHub 브랜치 보호 규칙 (main)

### Settings → Branches → main 브랜치 규칙

**필수 설정**:
```
✅ Require a pull request before merging
   ✅ Require approvals (1명 이상)
   ✅ Dismiss stale pull request approvals when new commits are pushed
   ⬜ Require review from Code Owners (선택)

✅ Require status checks to pass before merging
   ✅ Require branches to be up to date before merging
   (CI 있으면) ✅ 필수 체크: ci/tests

✅ Require conversation resolution before merging

✅ Require linear history
   → feature→dev: Squash and merge
   → dev→main: Squash and merge (권장)

✅ Do not allow bypassing the above settings
   (관리자 예외 필요시) ⬜ Allow specified actors to bypass
```

**dev 브랜치 규칙** (선택):
```
✅ Require a pull request before merging
   ✅ Require approvals (1명)
⬜ Require status checks (개발 단계에서는 선택)
```

---

## 📝 PR 체크리스트 개선안

### 현재 템플릿 유지 + 추가 제안

**추가 항목**:
```markdown
### 배포 영향도
- [ ] Breaking change 없음 (또는 마이그레이션 가이드 작성됨)
- [ ] DB 스키마 변경 없음 (또는 마이그레이션 스크립트 포함)
- [ ] 환경 변수 변경 없음 (또는 .env.example 업데이트)

### 보안 체크
- [ ] 사용자 입력 검증 완료 (SQL Injection, XSS 방지)
- [ ] 권한 체크 완료 (GM/Admin/User 역할 검증)
- [ ] 민감 정보 로깅 없음 (비밀번호, 토큰 등)
```

**자동화 제안**:
- GitHub Actions에서 PR 템플릿 체크리스트 자동 검증
- 미완료 시 "WIP" 라벨 자동 부여

---

## 🏷️ 릴리즈 태그 규칙

### 태그 형식
```
vYYYY.MM.DD
```

**⚠️ 중요**: 태그는 **실제 배포일 기준**으로 생성합니다!

**예시** (참고용):
- `vYYYY.MM.DD` - 정규 릴리즈
- `vYYYY.MM.DD.1` - 동일 날짜 핫픽스 (선택)

### 태그 생성 명령
```bash
# 실제 배포일로 교체하여 사용
# Annotated tag (권장)
git tag -a vYYYY.MM.DD -m "Release vYYYY.MM.DD: 릴리즈 설명"

# 푸시
git push origin vYYYY.MM.DD
```

---

## 📄 릴리즈 노트 템플릿

### GitHub Releases에서 사용할 템플릿

```markdown
# Release vYYYY.MM.DD

**배포일**: YYYY-MM-DD (실제 배포일로 교체)  
**기준 브랜치**: main  
**베이스 버전**: vYYYY.MM.DD (이전 릴리즈)

---

## 🎯 주요 변경 사항

### ✨ 신규 기능
- **기능명**: 기능 설명

### 🐛 버그 수정
- **파일명**: 버그 설명 및 수정 내용

### 📚 문서 개선
- **문서명**: 개선 내용

### 🔧 기술 개선
- **영역**: 개선 내용

---

## 📋 배포 체크리스트

### 배포 전 확인 완료
- [x] 회귀 테스트 (ko/ja/en) 통과
- [x] KO/JP 양쪽 환경 검증
- [x] DB 스키마 변경 없음
- [x] 파일 누락 없음 (manifest 검증)

### 배포 후 확인 필요
- [ ] 운영 서버 에러 로그 확인 (1시간)
- [ ] 주요 페이지 접속 확인
- [ ] 사용자 피드백 모니터링

---

## 🔗 관련 이슈

- Closes #번호: 이슈 제목

---

## 📦 변경 파일 목록

<details>
<summary>총 N개 파일 수정 (클릭하여 펼치기)</summary>

### 신규 추가
- `파일경로`

### 수정
- `파일경로`

### 삭제
- (없음)

</details>

---

## ⚠️ Breaking Changes

**없음** - 하위 호환성 유지됨

---

## 🚀 다음 릴리즈 예정

### vYYYY.MM.DD 목표 (예시)
- [ ] 기능1
- [ ] 기능2

---

## 📞 문의

문제 발생 시:
1. GitHub Issues 등록
2. 긴급: hotfix 브랜치로 수정 후 PR

**감사합니다!** 🎉

---

**Release by**: @실제배포담당자  
**Reviewed by**: @리뷰어1, @리뷰어2  
**Deploy Target**: Production (운영서버)
```

---

## 💡 릴리즈 노트 작성 팁

### 필수 포함 사항
1. **버전 번호**: vYYYY.MM.DD
2. **배포일**: 실제 배포한 날짜
3. **주요 변경 사항**: 카테고리별 분류
4. **관련 이슈**: Closes #번호
5. **배포 체크리스트**: 완료 여부

### 선택 포함 사항
- 스크린샷 (UI 변경 시)
- 성능 벤치마크
- 마이그레이션 가이드
- Known Issues

### 자동화 제안
```bash
# GitHub CLI 사용
gh release create vYYYY.MM.DD \
  --title "Release vYYYY.MM.DD" \
  --notes-file RELEASE_NOTES.md \
  --target main
```

---

## 🔄 버전 관리 전략

### Semantic Versioning 대안 (날짜 기반)

**날짜 기반 장점**:
- 직관적 (언제 배포됐는지 명확)
- 순서 보장 (날짜순 자동 정렬)
- 월별 정리 용이

**핫픽스 표기** (예시):
```
vYYYY.MM.DD     - 정규 릴리즈
vYYYY.MM.DD.1   - 핫픽스 1
vYYYY.MM.DD.2   - 핫픽스 2
```

### 변경 로그 관리

**CHANGELOG.md 자동 생성**:
```bash
# Conventional Commits 기반 (실제 태그로 교체)
git log --oneline --no-merges vYYYY.MM.DD..HEAD \
  | grep -E '^[0-9a-f]+ (feat|fix|docs):' \
  > changes.txt
```

---

**관련 문서**:
- [BRANCH_POLICY.md](./BRANCH_POLICY.md) - 브랜치 전략 상세
- [DEPLOY.md](./DEPLOY.md) - 배포 절차
- [REGRESSION_CHECKLIST.md](./REGRESSION_CHECKLIST.md) - 회귀 테스트

---

**Version**: vYYYY.MM.DD  
**Last Updated**: YYYY-MM-DD  
**Maintained by**: TheK Project Team
