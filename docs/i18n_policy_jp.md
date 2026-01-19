# JP Branch i18n Policy

## 1. 목적 (Purpose)
본 문서는 JP 브랜치에서 발생했던 다국어 혼용(한국어/일본어/영어) 문제를 방지하고,  
모든 페이지에서 **일관된 언어 로딩 규칙과 기준 파일**을 유지하기 위한 정책을 정의한다.

본 정책은 **정합성·일관성·재발 방지**를 최우선 목표로 한다.

---

## 2. 기본값 (Default Language)

- 기본 언어는 **일본어(`ja`)** 로 한다.
- `$lang` 값이 다음 조건 중 하나라도 만족하지 못하면 기본값 `ja`를 사용한다.

### 기본값 적용 조건
- `$lang` 파라미터가 존재하지 않을 경우
- `$lang` 값이 `ko`, `ja`, `en` 중 하나가 아닐 경우
- 세션/쿠키 값이 비정상적인 경우

```php
// pseudo rule
if (!in_array($lang, ['ko', 'ja', 'en'])) {
    $lang = 'ja';
}
```

---

## 3. Fallback 규칙 (Fallback Rules)

### 3.1 언어 파일 레벨

- 특정 언어 파일에 키가 존재하지 않을 경우:
  1️⃣ 동일 키의 **기본 언어(`ja`)** 값으로 fallback
  2️⃣ 그래도 없을 경우, 키를 그대로 노출 (`[[KEY_NAME]]`)

### 3.2 화면 출력 규칙

- 빈 문자열 출력 ❌
- 다른 언어 자동 섞기 ❌
- **의도적으로 누락이 보이도록** 키 형태 유지 ⭕

```text
예:
[[MENU_DASHBOARD]]
```

→ QA 단계에서 즉시 인지 가능하도록 설계

---

## 4. 키 규칙 (Naming Convention)

### 4.1 기본 원칙

- 키는 **영문 대문자 + 스네이크 케이스**만 허용
- UI 의미 단위 기준으로 작성
- 언어별로 동일한 키 구조를 반드시 유지

```text
MENU_DASHBOARD
BTN_SAVE
BTN_CANCEL
TITLE_GM_DASHBOARD
LABEL_TOTAL_AMOUNT
```

### 4.2 금지 사항

- 언어별로 키 이름 변경 ❌
- 페이지명/파일명 직접 포함 ❌
- 한글/일본어 키 ❌

---

## 5. 로더 규칙 (Language Loader Rule)

### 5.1 단일 진입점 원칙

- JP 브랜치에서는 **언어 파일을 직접 include 하지 않는다.**
- 반드시 **공통 로더 파일(i18n.php 또는 이에 준하는 파일)** 을 통해 로딩한다.

```php
// allowed
require_once $_SERVER['DOCUMENT_ROOT'] . '/_branches/jp/includes/i18n.php';

// forbidden
include 'lang/ja.php';
include 'lang/ko.php';
```

### 5.2 로딩 순서 (권장)

1. `$lang` 결정 (GET → SESSION → default)
2. 기본 언어 파일 로드 (`ja.php`)
3. 선택 언어 파일 로드 (`ko.php` / `en.php`)
4. 키 병합 (선택 언어가 기본 언어를 override)

---

## 6. 테스트 케이스 (Test Cases)

### 6.1 필수 테스트 URL

- `gm_dashboard.php?lang=ja`
- `gm_dashboard.php?lang=ko`
- `gm_dashboard.php?lang=en`

### 6.2 필수 확인 항목

- 메뉴 / 헤더 / 페이지 타이틀이 **단일 언어로만 출력**
- 한 페이지 내 언어 혼용 0건
- 누락 키 발생 시 `[[KEY]]` 형태로 노출

### 6.3 최소 테스트 페이지

- 대시보드 (Dashboard)
- 리스트 페이지 (List)
- 상세 페이지 (Detail)

---

## 7. 적용 범위 (Scope)

- `_branches/jp/` 전체
- 신규 JP 페이지는 본 정책을 **의무적으로 준수**

---

## 8. 변경 관리 (Change Management)

- 본 정책 변경 시:
  - GitHub Issue 생성 필수
  - `/docs/i18n_policy_jp.md` 업데이트 필수
- 코드 변경보다 **문서가 우선**

---

## 9. 원칙 요약

- 기준은 하나
- 로더는 하나
- 언어는 섞이지 않는다
