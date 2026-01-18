# Deploy Guide

## 목표

배포 누락, 파일 미반영, 언어 불일치 등의 이슈를 **자동 검증**으로 사전 차단한다.

---

## 배포 절차 (5단계)

### Step 1: 개발 완료 & PR (dev 분기)

```bash
# feature 브랜치에서 작업 완료
git checkout dev
git pull origin dev
git merge feature/기능명

# 또는 GitHub PR 통해 dev에 머지
```

**체크**:
- [ ] 코드 리뷰 승인
- [ ] CI/Lint 통과
- [ ] 공통 파일(lang/, includes/) 수정 시 KO/JP 동시 반영 확인

---

### Step 2: main 병합 & 회귀 테스트

```bash
# main에 PR 생성
git checkout main
git pull origin main
git merge dev  # (또는 GitHub PR)
```

**회귀 테스트 수행** (`docs/REGRESSION_CHECKLIST.md` 참고):
- [ ] ko/ja/en 언어 전환 정상
- [ ] 대시보드 레이아웃(GM 형식) 유지
- [ ] 주요 페이지 200 OK
- [ ] 파일 누락/404 없음

**테스트 불통과**: 즉시 dev로 돌아가서 수정, 다시 PR

---

### Step 3: Manifest 생성 & 검증

배포 대상 파일 목록 생성:

```bash
# 예시 (실제 스크립트는 프로젝트별)
php scripts/generate_manifest.php > deploy_manifest.json

# 생성된 manifest 확인
cat deploy_manifest.json
```

**Manifest 내용**:
```json
{
  "files": [
    "includes/gm_dashboard_ui.php",
    "lang/ko.php",
    "lang/ja.php",
    "lang/en.php",
    "gm_dashboard_content.php",
    ...
  ],
  "timestamp": "2026-01-18T10:30:00Z",
  "version": "v2026.01.18"
}
```

---

### Step 4: 서버 배포 & 파일 검증

```bash
# 1. 파일 업로드 (rsync/FTP/자동 배포 도구)
rsync -avz ./ user@server:/var/www/prod/

# 2. 서버에서 파일 존재 검증
php scripts/verify_deploy.php deploy_manifest.json

# 검증 결과 예시
# ✓ includes/gm_dashboard_ui.php exists
# ✓ lang/ko.php exists
# ✗ lang/ja.php NOT FOUND (배포 실패)
```

**누락 파일 발견 시**:
- 배포 실패 처리 (non-zero exit)
- 누락 파일 목록 로그에 저장
- 수정 후 재배포

**배포 로그 저장**:
```
/logs/deploy/
├─ v2026.01.18_deploy.log (배포 스크립트 실행 로그)
├─ v2026.01.18_verify.log (검증 결과)
└─ v2026.01.18_errors.log (오류 목록)
```

---

### Step 5: Release Tag & 공지

```bash
# Release tag 생성 (main 브랜치에서)
git tag -a v2026.01.18 -m "Release v2026.01.18 - Dashboard GM layout fix"
git push origin v2026.01.18

# GitHub Release 생성 (자동 또는 수동)
# - docs/RELEASE_NOTES_TEMPLATE.md 참고
# - Release notes 작성 및 publish
```

**배포 완료 사항**:
- [ ] 모든 파일 업로드 확인
- [ ] 검증 스크립트 통과
- [ ] Release tag 생성 및 push
- [ ] 배포 로그 아카이브
- [ ] GitHub Release Notes 작성 완료

**Release Tag 규칙** (상세):
- **형식**: `vYYYY.MM.DD` (예: `v2026.01.18`)
- **Hotfix**: `vYYYY.MM.DD-hotfix.N` (예: `v2026.01.18-hotfix.1`)
- **참고**: `docs/BRANCH_POLICY.md` → 🏷️ 릴리즈 태그 규칙 섹션

---

## Manifest 생성 예시 (참고용)

### 공통 배포 대상 파일

```
/includes/
  - i18n.php
  - gm_dashboard_ui.php
  - db_connect.php
  - (기타 공통 파일)

/lang/
  - ko.php
  - ja.php
  - en.php

/docs/
  - BRANCH_POLICY.md
  - DEPLOY.md
  - REGRESSION_CHECKLIST.md

/.github/
  - PULL_REQUEST_TEMPLATE.md

(대시보드 관련 PHP 파일들)
  - gm_dashboard.php
  - gm_dashboard_content.php
  - admin_dashboard.php
  - admin_dashboard_content.php
  - master_dashboard.php
  - master_dashboard_content.php
  - agent_dividend_chart.php
  - investor_dashboard.php
  - investor_dashboard_content.php
```

---

## 배포 체크리스트 (최종)

배포 전 **반드시** 확인:

- [ ] main 브랜치가 최신 상태
- [ ] 회귀 테스트 (ko/ja/en) 모두 통과
- [ ] manifest 생성 및 검증 성공
- [ ] 배포 로그 저장 준비
- [ ] Release tag 메시지 준비

---

## 문제 발생 시 롤백

배포 후 문제 발견:

```bash
# 1. 이전 버전으로 즉시 복구
# (서버 백업에서 복구 또는 이전 커밋으로 재배포)

# 2. hotfix 브랜치 생성
git checkout main
git checkout -b hotfix/문제명

# 3. 문제 수정 후 PR → main → deploy
```

---

## 배포 자동화 (향후 확장)

향후 CI/CD 파이프라인 추가 시:

```yaml
# GitHub Actions 예시
on:
  push:
    tags:
      - 'v*'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v2
      
      - name: Generate Manifest
        run: php scripts/generate_manifest.php
      
      - name: Deploy
        run: |
          rsync -avz ./ ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:/var/www/prod/
      
      - name: Verify Deploy
        run: |
          ssh ${{ secrets.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} \
            "php /var/www/prod/scripts/verify_deploy.php"
```

---

**Version**: `0118_v4` (2026-01-18)
**Last Updated**: 2026-01-18
