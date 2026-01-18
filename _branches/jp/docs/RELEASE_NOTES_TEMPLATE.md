## 🏷️ Release Notes Template

**이 템플릿을 사용하여 GitHub Release를 작성하세요.**

---

### Release Title

```
v2026.01.18 - Dashboard Unification & Stabilization
```

### Release Description

```markdown
## 📋 Overview

**Version**: v2026.01.18  
**Release Date**: 2026-01-18  
**Status**: Stable  
**Base**: 0118_v4

---

## ✨ What's New

### Features
- GM dashboard layout unification (2-column grid, 260px chart height)
- Multi-language support (ko/ja/en) for Master list
- Deployment manifest validation framework
- Stabilization policy documentation

### Improvements
- Extract common CSS to `includes/gm_dashboard_ui.php`
- Unified chart sizing across all dashboards
- Responsive design (mobile: 1 column < 980px)
- PR template with comprehensive checklist

### Bug Fixes
- Fix missing PHP tag in admin_dashboard_content.php
- Fix hardcoded Japanese text in i18n files
- Ensure agent_dividend_chart.php session safety

---

## 📦 Changed Files

### Core Files
- `includes/gm_dashboard_ui.php` (new)
- `gm_dashboard_content.php`
- `admin_dashboard_content.php`
- `master_dashboard_content.php`
- `investor_dashboard_content.php`
- `agent_dividend_chart.php`

### Language Files
- `lang/ko.php` (+16 keys)
- `lang/ja.php` (+16 keys)
- `lang/en.php` (+16 keys)

### Documentation
- `docs/BRANCH_POLICY.md`
- `docs/DEPLOY.md`
- `docs/REGRESSION_CHECKLIST.md`
- `docs/WORKFLOW.md`
- `docs/CONVENTIONAL_COMMITS.md`
- `.github/PULL_REQUEST_TEMPLATE.md`

### Configuration
- `.github/PULL_REQUEST_TEMPLATE.md` (new)

---

## 🧪 Testing

### Regression Checklist Completed
- ✅ Language switching (ko/ja/en) verified
- ✅ GM dashboard layout confirmed
- ✅ Chart sizing (260px) validated
- ✅ File paths verified (no 404s)
- ✅ Database queries unchanged
- ✅ Manifest validation framework ready

### Deployment Validation
- ✅ All core files present
- ✅ All language keys synchronized
- ✅ No syntax errors
- ✅ PR template loads correctly

---

## 📋 Breaking Changes

**None** - All changes are backward compatible.

---

## 🚀 Deployment

### Before Deploying
1. Run `docs/REGRESSION_CHECKLIST.md`
2. Verify file manifest
3. Confirm ko/ja/en language display
4. Check GM dashboard layout on desktop & mobile

### Deployment Steps
```bash
# 1. Pull latest main
git pull origin main

# 2. Verify manifest
php scripts/generate_manifest.php

# 3. Deploy files
# (your deployment command)

# 4. Verify on server
php scripts/verify_deploy.php deploy_manifest.json

# 5. Confirm in browser
# Visit each dashboard: ko/ja/en
```

### After Deployment
- ✅ All files uploaded
- ✅ Manifest validation passed
- ✅ Dashboard layout visible
- ✅ Languages switching correctly

---

## 📝 Notes for Team

### For Developers
- Start all feature branches from `dev`
- Use Conventional Commits format
- PR template includes checklist - use it!

### For QA
- Use `REGRESSION_CHECKLIST.md` before deployment
- Test ko/ja/en for each dashboard
- Verify GM layout: 2 columns on desktop, 1 on mobile

### For DevOps
- Deploy using manifest validation
- Monitor `logs/deploy/` directory
- Verify file count matches manifest

### For Product
- Dashboard now has unified layout (professional appearance)
- Multi-language support for all major pages
- Stabilization policies in place for future releases

---

## 🔗 Related Documentation

- **Branch Strategy**: `docs/BRANCH_POLICY.md`
- **Workflow**: `docs/WORKFLOW.md`
- **Deployment Guide**: `docs/DEPLOY.md`
- **QA Checklist**: `docs/REGRESSION_CHECKLIST.md`
- **Commits Format**: `docs/CONVENTIONAL_COMMITS.md`

---

## 📊 Metrics

| Metric | Value |
|--------|-------|
| Files Changed | 15+ |
| Language Keys Added | 48 (ko/ja/en) |
| Documentation Pages | 6 |
| Test Checklist Items | 7 |
| Dashboards Unified | 5 |

---

## ✅ Sign-off

- **Code Review**: ✅ Approved
- **QA Testing**: ✅ Passed
- **Regression Checklist**: ✅ All items verified
- **Deployment Manifest**: ✅ Verified
- **Documentation**: ✅ Complete

---

## 🎯 Next Steps (v2026-02)

- [ ] Implement automated manifest generation in CI/CD
- [ ] Add deployment webhook verification
- [ ] Extend regression checklist with automated tests
- [ ] KO branch synchronization workflow

---

**Release prepared by**: GitHub Actions / Manual  
**Release date**: 2026-01-18  
**Version**: v2026.01.18  
```

---

## 📌 사용 방법

### GitHub Release 생성 시

1. **Releases 탭** → **Draft a new release**
2. **Tag version**: `v2026.01.18`
3. **Release title**: 위 템플릿의 "Release Title" 섹션 복붙
4. **Description**: 위 템플릿의 "Release Description" 섹션 복붙
5. **Publish release**

### Git Command로 생성 시

```bash
# Annotated tag 생성 (권장)
git tag -a v2026.01.18 -m "Release v2026.01.18

- Dashboard layout unification
- Multi-language support
- Stabilization policies"

# Push to GitHub
git push origin v2026.01.18
```

### Commit 메시지 참고

```
feat: v2026.01.18 release

- GM dashboard layout unification across 5 dashboards
- Multi-language support (ko/ja/en) for Master list
- Common CSS extracted to includes/gm_dashboard_ui.php
- Documentation and policies for stabilization
- PR template with comprehensive checklist

See: docs/v2026-01-STABILIZATION.md for full details

Closes #1, #3, #4
```

---

**Version**: `0118_v4` (2026-01-18)  
**Last Updated**: 2026-01-18
