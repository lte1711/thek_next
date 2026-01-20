-- =============================================
-- 새 국가/브랜치 추가용 SQL 템플릿
-- =============================================
-- 사용법: {country}를 실제 국가명(소문자)으로 치환
-- 예: vietnam, thailand, philippines 등
-- =============================================

-- Step 1: ready_trading 테이블 생성
CREATE TABLE `{country}_ready_trading` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tx_id` int DEFAULT NULL COMMENT '거래 ID, NULL 가능',
  `tx_date` date NOT NULL,
  `account_id` varchar(50) DEFAULT NULL,
  `account_pw` varchar(50) DEFAULT NULL,
  `platform_1` varchar(50) DEFAULT NULL,
  `platform_2` varchar(50) DEFAULT NULL,
  `server_1` varchar(50) DEFAULT NULL,
  `server_2` varchar(50) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `reject_reason` text,
  `status` enum('ready','rejected','approved') DEFAULT 'ready' COMMENT '승인 상태',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `reject_by` varchar(50) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '⚠️ 필수 컬럼',
  `day_seq` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '일자별 순번 (01,02...)',
  `created_date` date NOT NULL DEFAULT (curdate()),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_tx` (`user_id`,`tx_id`),
  UNIQUE KEY `uniq_tx_id` (`tx_id`),
  KEY `idx_user_date` (`user_id`,`tx_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='[{country}] 승인 대기 거래 테이블';

-- Step 2: progressing 테이블 생성
CREATE TABLE `{country}_progressing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tx_id` int DEFAULT NULL COMMENT '⚠️ NULL 초기값, OK 승인 시 UPDATE',
  `tx_date` date NOT NULL,
  `pair` varchar(20) DEFAULT NULL COMMENT '거래 페어 (xm,ultima 등)',
  `deposit_status` decimal(12,2) DEFAULT '0.00',
  `withdrawal_status` decimal(12,2) DEFAULT '0.00',
  `profit_loss` decimal(10,2) DEFAULT NULL,
  `notes` text COMMENT '⚠️ 필수 컬럼 - 메모',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '⚠️ 필수 - 자동 업데이트',
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_date_pair` (`user_id`,`tx_date`,`pair`) COMMENT '⚠️ 중복 INSERT 방지',
  UNIQUE KEY `uniq_{country}_tx_id` (`tx_id`),
  KEY `idx_{country}_tx_id` (`tx_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='[{country}] 진행 중 거래 테이블';

-- =============================================
-- 검증 쿼리
-- =============================================

-- 컬럼 확인
SHOW COLUMNS FROM {country}_ready_trading;
SHOW COLUMNS FROM {country}_progressing;

-- 필수 컬럼 존재 확인
SELECT 
  TABLE_NAME,
  COLUMN_NAME,
  DATA_TYPE,
  IS_NULLABLE,
  COLUMN_DEFAULT,
  EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'thek_next_db_branch_jp'
  AND TABLE_NAME IN ('{country}_ready_trading', '{country}_progressing')
  AND COLUMN_NAME IN ('tx_id', 'status', 'notes', 'created_at', 'updated_at')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- UNIQUE KEY 확인
SHOW INDEX FROM {country}_ready_trading WHERE Non_unique = 0;
SHOW INDEX FROM {country}_progressing WHERE Non_unique = 0;

-- =============================================
-- 기존 테이블 수정 (컬럼 누락 시)
-- =============================================

-- updated_at 컬럼 추가 (누락된 경우)
-- ALTER TABLE {country}_progressing 
-- ADD COLUMN updated_at DATETIME 
-- DEFAULT CURRENT_TIMESTAMP 
-- ON UPDATE CURRENT_TIMESTAMP 
-- AFTER created_at;

-- tx_id 컬럼 추가 (누락된 경우)
-- ALTER TABLE {country}_progressing 
-- ADD COLUMN tx_id INT DEFAULT NULL 
-- AFTER user_id,
-- ADD UNIQUE KEY uniq_{country}_tx_id (tx_id);

-- notes 컬럼 추가 (누락된 경우)
-- ALTER TABLE {country}_progressing 
-- ADD COLUMN notes TEXT 
-- AFTER profit_loss;

-- status 컬럼 추가 (ready_trading에 누락된 경우)
-- ALTER TABLE {country}_ready_trading 
-- ADD COLUMN status ENUM('ready','rejected','approved') 
-- DEFAULT 'ready' 
-- AFTER reject_reason;
