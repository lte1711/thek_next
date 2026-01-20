# 새 국가/브랜치 추가 시 필수 DB 스키마 가이드

## 발생 이슈 (2026-01-20)
- **문제**: Japan OK/Reject 시 `Unknown column 'updated_at'` 에러
- **원인**: `japan_progressing` 테이블에 `updated_at` 컬럼 누락
- **교훈**: 새 국가 추가 시 반드시 동일 스키마 유지 필요

---

## 필수 테이블 2개

### 1. `{country}_ready_trading`
- 승인 대기 중인 거래 데이터 저장

### 2. `{country}_progressing`
- 진행 중인 거래 데이터 저장

---

## 필수 컬럼 체크리스트

### ✅ {country}_ready_trading 필수 컬럼
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- user_id (INT, NOT NULL)
- tx_id (INT, DEFAULT NULL)  -- 거래 ID, NULL 가능
- tx_date (DATE, NOT NULL)
- status (ENUM('ready','rejected','approved'), DEFAULT 'ready')
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- updated_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- reject_reason (TEXT)
- reject_by (VARCHAR(50))
- reject_date (DATETIME)
- created_by (VARCHAR(50))
- day_seq (TINYINT UNSIGNED, DEFAULT 1)
- created_date (DATE, DEFAULT CURRENT_DATE)
```

### ✅ {country}_progressing 필수 컬럼
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- user_id (INT, NOT NULL)
- tx_id (INT, DEFAULT NULL)  -- ⚠️ NULL 초기값, 이후 UPDATE
- tx_date (DATE, NOT NULL)
- pair (VARCHAR(20))
- notes (TEXT)  -- 메모 필드
- created_at (DATETIME, DEFAULT CURRENT_TIMESTAMP)
- updated_at (DATETIME, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)  -- ⚠️ 필수!
- deposit_status (DECIMAL(12,2), DEFAULT 0.00)
- withdrawal_status (DECIMAL(12,2), DEFAULT 0.00)
- profit_loss (DECIMAL(10,2))
- settled_by (VARCHAR(50))
- settled_date (DATETIME)
- reject_reason (VARCHAR(255))
```

### ⚠️ 필수 UNIQUE KEY 제약조건
```sql
-- {country}_ready_trading
UNIQUE KEY uniq_user_tx (user_id, tx_id)
UNIQUE KEY uniq_tx_id (tx_id)
KEY idx_user_date (user_id, tx_date)

-- {country}_progressing
UNIQUE KEY uniq_user_date_pair (user_id, tx_date, pair)  -- ⚠️ 중복 INSERT 방지
UNIQUE KEY uniq_{country_code}_tx_id (tx_id)
KEY idx_{country_code}_tx_id (tx_id)
```

---

## 새 국가 추가 SQL 템플릿

### Step 1: {country}_ready_trading 생성
```sql
CREATE TABLE `{country}_ready_trading` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tx_id` int DEFAULT NULL,
  `tx_date` date NOT NULL,
  `account_id` varchar(50) DEFAULT NULL,
  `account_pw` varchar(50) DEFAULT NULL,
  `platform_1` varchar(50) DEFAULT NULL,
  `platform_2` varchar(50) DEFAULT NULL,
  `server_1` varchar(50) DEFAULT NULL,
  `server_2` varchar(50) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `reject_reason` text,
  `status` enum('ready','rejected','approved') DEFAULT 'ready',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `reject_by` varchar(50) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `day_seq` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '일자별 순번',
  `created_date` date NOT NULL DEFAULT (curdate()),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_tx` (`user_id`,`tx_id`),
  UNIQUE KEY `uniq_tx_id` (`tx_id`),
  KEY `idx_user_date` (`user_id`,`tx_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

### Step 2: {country}_progressing 생성
```sql
CREATE TABLE `{country}_progressing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tx_id` int DEFAULT NULL,
  `tx_date` date NOT NULL,
  `pair` varchar(20) DEFAULT NULL,
  `deposit_status` decimal(12,2) DEFAULT '0.00',
  `withdrawal_status` decimal(12,2) DEFAULT '0.00',
  `profit_loss` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_date_pair` (`user_id`,`tx_date`,`pair`),
  UNIQUE KEY `uniq_{country_code}_tx_id` (`tx_id`),
  KEY `idx_{country_code}_tx_id` (`tx_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

---

## 기존 테이블 컬럼 추가 (누락 시)

### updated_at 컬럼 추가
```sql
ALTER TABLE {country}_progressing 
ADD COLUMN updated_at DATETIME 
DEFAULT CURRENT_TIMESTAMP 
ON UPDATE CURRENT_TIMESTAMP 
AFTER created_at;
```

### tx_id 컬럼 추가 (없을 경우)
```sql
ALTER TABLE {country}_progressing 
ADD COLUMN tx_id INT DEFAULT NULL 
AFTER user_id;

ALTER TABLE {country}_progressing
ADD UNIQUE KEY uniq_{country_code}_tx_id (tx_id);
```

### notes 컬럼 추가
```sql
ALTER TABLE {country}_progressing 
ADD COLUMN notes TEXT 
AFTER profit_loss;
```

---

## 검증 체크리스트

### ✅ 컬럼 존재 확인
```sql
-- ready_trading 확인
SHOW COLUMNS FROM {country}_ready_trading;

-- progressing 확인
SHOW COLUMNS FROM {country}_progressing;
```

### ✅ 필수 컬럼 grep 확인
```bash
mysql -u thek_db_admin -p thek_next_db_branch_jp \
  -e "SHOW COLUMNS FROM {country}_ready_trading;" | grep -E "tx_id|status|created_at|updated_at"

mysql -u thek_db_admin -p thek_next_db_branch_jp \
  -e "SHOW COLUMNS FROM {country}_progressing;" | grep -E "tx_id|notes|created_at|updated_at"
```

### ✅ UNIQUE KEY 확인
```sql
SHOW INDEX FROM {country}_ready_trading;
SHOW INDEX FROM {country}_progressing;
```

---

## 코드에서 사용하는 컬럼 (참고)

### ok_save.php에서 사용
- `{country}_ready_trading`: status, tx_id, updated_at
- `{country}_progressing`: tx_id, updated_at, deposit_status, withdrawal_status, profit_loss

### reject_save.php에서 사용
- `{country}_ready_trading`: status, reject_reason, reject_by, reject_date, updated_at
- `{country}_progressing`: updated_at, reject_reason

### country_ready.php에서 사용
- `{country}_ready_trading`: status (WHERE status='ready')

---

## 주의사항

1. **updated_at은 반드시 `ON UPDATE CURRENT_TIMESTAMP` 포함**
   - 자동 업데이트 시간 추적을 위해 필수

2. **tx_id는 NULL 허용**
   - 초기 INSERT 시 NULL
   - OK 승인 시 UPDATE로 값 할당

3. **UNIQUE KEY는 반드시 설정**
   - 중복 데이터 INSERT 방지
   - `progressing`: (user_id, tx_date, pair) 조합 UNIQUE

4. **charset은 utf8mb4 사용**
   - 한글 및 특수문자 지원

---

## 관련 커밋 이력
- `31c119b` - HIT 로깅 추가
- `b885cad` - affected_rows=0 체크 추가
- `4c6bcd5` - tx_id=NULL fallback 로직 추가
- `61564e4` - Ready/Completed 상태 필터링 수정
- **2026-01-20** - japan_progressing에 updated_at 추가 (ALTER TABLE)
