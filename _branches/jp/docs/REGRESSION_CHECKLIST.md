# Regression Checklist (배포 전 필수)

**배포 전에 반드시 이 체크리스트를 모두 수행**한다.

---

## 1️⃣ 공통 사항

### 1.1 언어 전환 (ko/ja/en)

**테스트 방법**: 각 페이지에서 `?lang=ko`, `?lang=ja`, `?lang=en` 파라미터로 테스트

- [ ] 타이틀/메뉴가 각 언어로 정상 표시
- [ ] i18n 키 누락으로 인한 `key.name` 표시 없음
- [ ] 세션 `lang` 유지로 인한 언어 혼선 없음
- [ ] 특수문자(日本語/한글/中文) 깨짐 없음

**체크 대상 페이지**:
- `gm_dashboard.php`
- `admin_dashboard.php`
- `master_dashboard.php`
- `agent_dashboard.php`
- `investor_dashboard.php`
- `a_master_list.php`

---

### 1.2 HTTP 상태 코드

**테스트 방법**: 브라우저 개발자 도구(F12) → Network 탭

- [ ] 주요 페이지: 200 OK
- [ ] 로그인 페이지 리다이렉트: 301/302 정상
- [ ] 404/500 에러 없음

**에러 로그 확인**:
```bash
# 서버 로그 확인
tail -f /var/log/php-errors.log
tail -f /var/log/apache2/error.log  # 또는 nginx 경로
```

---

## 2️⃣ 대시보드 레이아웃 (GM 형식 유지)

**GM 형식 기준**:
- 최대 너비: 1200px (`.gm-wrap`)
- 그리드: 2열 (`.gm-grid` - 모바일 1열)
- 카드 높이: 차트 260px (`.gm-chart-box`)
- 반응형: 980px 이하에서 1열 자동 전환

### 2.1 GM Dashboard (`gm_dashboard_content.php`)

- [ ] 3개 카드가 **2열 정렬** (1행: 2개, 2행: 1개)
- [ ] 각 차트 높이: **260px 일정**
- [ ] 카드 그림자/라운드: 통일
- [ ] 모바일(380px): 1열 정렬 확인

**시각적 확인**:
```
PC (1200px+):          Mobile (< 980px):
┌─────────┬─────────┐  ┌──────────┐
│ 차트1   │ 차트2   │  │ 차트1    │
├─────────┼─────────┤  ├──────────┤
│         │         │  │ 차트2    │
├─────────┴─────────┤  ├──────────┤
│ 차트3   (1열)      │  │ 차트3    │
└─────────────────────┘  └──────────┘
```

### 2.2 Admin Dashboard (`admin_dashboard_content.php`)

- [ ] 2개 차트가 **2열 정렬**
- [ ] 도넛 차트 높이: 260px
- [ ] 막대 차트 높이: 260px
- [ ] 범례 위치: 하단 정렬

### 2.3 Master Dashboard (`master_dashboard_content.php`)

- [ ] 2개 차트 + 기준일 레이블이 2열 정렬
- [ ] 에이전트 배당(도넛): 260px
- [ ] 에이전트 상세(막대): 260px
- [ ] 디버그 메시지: 카드 하단에 표시

### 2.4 Investor Dashboard (`investor_dashboard_content.php`)

- [ ] Rejecting 경고: 카드 위에 표시 (레이아웃 깨짐 없음)
- [ ] 2개 차트 (라운드 차트 + 막대 차트) **2열 정렬**
- [ ] 각 차트 높이: 260px
- [ ] 라인 차트: 모든 라운드 포인트 표시, 범례 하단

### 2.5 Agent Dashboard (`agent_dividend_chart.php`)

- [ ] 도넛 차트: 카드 내 260px
- [ ] 범례: 하단 정렬
- [ ] 데이터 없을 시: "No dividend data" 메시지 표시

---

## 3️⃣ 핵심 페이지 기능 확인

### 3.1 Master List (`a_master_list.php`)

- [ ] 테이블 헤더: 한글/일본어/영어 정상
- [ ] 편집/삭제 버튼: 정상 동작
- [ ] 데이터 없을 시: "No MASTER members" 표시
- [ ] 페이지 네이션(있다면): 정상

### 3.2 파트너 계산 (`partner_accounts_v2_content.php`)

- [ ] 금액 표시: USDT (숫자 2자리)
- [ ] 잔액 정책 안내: 표시
- [ ] 감사 로그 테이블: 2개 칼럼(GM Payout Total / Company Residual) 정상

---

## 4️⃣ 파일 누락 & 경로 오류 (과거 이슈 재현 검증)

**과거 이슈**: 배포 후 특정 파일 없음 → 404 에러

테스트 방법: 브라우저 개발자 도구 → Network → 모든 요청 상태 확인

### 4.1 Include 파일 체크

- [ ] `/includes/gm_dashboard_ui.php` 존재 (모든 대시보드에서 include)
- [ ] `/includes/i18n.php` 존재
- [ ] `/includes/db_connect.php` 존재

**테스트**: 개발자 도구에서 파일 로드 확인 (또는 파일 시스템 직접 확인)

### 4.2 js/css 리소스 체크

- [ ] Chart.js CDN 로드 정상 (또는 로컬 경로 확인)
- [ ] 스타일시트: 로드 완료 (깨진 링크 없음)
- [ ] 이미지: 404 없음

---

## 5️⃣ 데이터 표시 정확성

### 5.1 다국어 키 일치

**메서드**: `lang/ko.php`, `lang/ja.php`, `lang/en.php` 파일 비교

- [ ] 모든 `t()` 호출 키가 3개 언어 파일에 존재
- [ ] 빠진 키 없음 (diff 도구로 확인 가능)

```bash
# 빠진 키 확인 (간단한 예시)
diff <(grep -o "'[^']*'" lang/ko.php | sort) \
     <(grep -o "'[^']*'" lang/ja.php | sort)
```

### 5.2 차트 데이터 표시

- [ ] 차트 X축/Y축: 레이블 표시
- [ ] 데이터: 데이터베이스에서 정상 로드
- [ ] 범례: 정상 표시, 클릭 시 라인/색상 토글 정상

---

## 6️⃣ 배포 Manifest 검증

**배포 후 반드시 수행**:

```bash
# 서버에서 manifest 검증 실행
php /var/www/prod/scripts/verify_deploy.php deploy_manifest.json

# 예상 출력:
# ✓ includes/gm_dashboard_ui.php exists
# ✓ lang/ko.php exists
# ✓ lang/ja.php exists
# ✓ lang/en.php exists
# ... (모두 ✓)
```

- [ ] 검증 스크립트 통과 (모두 ✓)
- [ ] 누락 파일 0개

**누락 파일 발견 시**: 배포 롤백 → 수정 → 재배포

---

## 7️⃣ 성능 & 보안 (선택)

성능/보안 관련 선택 체크:

- [ ] 페이지 로드 시간: 3초 이내 (느리다면 DB 쿼리 확인)
- [ ] 에러 로그: 경고/공지 없음
- [ ] XSS/SQL injection: htmlspecialchars, prepared statement 사용 확인

---

## ✅ 최종 체크리스트

배포 전 **모두 통과**했는지 확인:

| 항목 | 결과 |
|------|------|
| 1. 언어(ko/ja/en) 정상 | ☐ |
| 2. HTTP 상태(200/301) 정상 | ☐ |
| 3. 대시보드 레이아웃(GM 형식) 유지 | ☐ |
| 4. 핵심 페이지 기능 정상 | ☐ |
| 5. 파일 누락/404 없음 | ☐ |
| 6. 다국어 키 일치 | ☐ |
| 7. Manifest 검증 통과 | ☐ |

**모두 체크 완료**:
- ✅ **배포 승인**
- Release tag 생성 & Push

---

## 알려진 이슈 & 대응

### Issue: "lang 키 없음" 표시

**원인**: `lang/{ko,ja,en}.php`에 키가 없음  
**대응**: 누락 키를 모든 3개 언어 파일에 추가

### Issue: 차트 깨짐

**원인**: `maintainAspectRatio:false` 미적용 또는 `.gm-chart-box` 높이 누락  
**대응**: `gm_dashboard_ui.php` 확인, 차트 초기화 코드 재점검

### Issue: 파일 없음 404

**원인**: `includes/` 또는 `/lang/` 파일 배포 누락  
**대응**: Manifest 검증 실행, 누락 파일 재배포

---

**Version**: `0118_v4` (2026-01-18)
**Last Updated**: 2026-01-18
