-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- 생성 시간: 26-01-19 00:55
-- 서버 버전: 8.0.44-0ubuntu0.22.04.2
-- PHP 버전: 8.1.2-1ubuntu2.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 데이터베이스: `thek_next_db_branch_jp`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `admin_audit_log`
--

CREATE TABLE `admin_audit_log` (
  `id` bigint NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event_code` varchar(40) NOT NULL,
  `settle_date` date DEFAULT NULL,
  `tx_id` bigint DEFAULT NULL,
  `profit_total` bigint DEFAULT NULL,
  `partner_sum` bigint DEFAULT NULL,
  `company_residual` bigint DEFAULT NULL,
  `company_user_id` int DEFAULT NULL,
  `status_before` varchar(24) DEFAULT NULL,
  `status_after` varchar(24) DEFAULT NULL,
  `reason_code` varchar(40) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `actor_user_id` int DEFAULT NULL,
  `actor_role` varchar(24) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `request_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `admin_audit_log`
--

INSERT INTO `admin_audit_log` (`id`, `created_at`, `event_code`, `settle_date`, `tx_id`, `profit_total`, `partner_sum`, `company_residual`, `company_user_id`, `status_before`, `status_after`, `reason_code`, `memo`, `actor_user_id`, `actor_role`, `ip`, `user_agent`, `request_id`) VALUES
(1, '2026-01-18 12:59:47', 'SETTLE_CONFIRM', '2026-01-16', NULL, 750, 300, 0, 5, 'not_found', '0', NULL, NULL, 5, 'gm', '112.187.121.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'REQ_696c5ab3efaa29.96374554'),
(2, '2026-01-18 12:59:56', 'SETTLE_CONFIRM', '2026-01-15', NULL, 70, 28, 0, 5, 'not_found', '0', NULL, NULL, 5, 'gm', '112.187.121.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'REQ_696c5abc7ff4e9.39545662');

-- --------------------------------------------------------

--
-- 테이블 구조 `admin_deposits_daily`
--

CREATE TABLE `admin_deposits_daily` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `deposit_amount` decimal(15,2) NOT NULL,
  `deposit_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `admin_sales_daily`
--

CREATE TABLE `admin_sales_daily` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `sales_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `sales_percentage` decimal(10,2) NOT NULL,
  `sales_date` date NOT NULL,
  `settled` tinyint(1) NOT NULL DEFAULT '0',
  `settled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `admin_sales_daily`
--

INSERT INTO `admin_sales_daily` (`id`, `user_id`, `sales_amount`, `sales_percentage`, `sales_date`, `settled`, `settled_at`, `created_at`, `updated_at`) VALUES
(39, 5, '0.00', '0.00', '2026-01-16', 1, '2026-01-18 12:59:47', '2026-01-18 03:59:47', '2026-01-18 03:59:47'),
(40, 5, '0.00', '0.00', '2026-01-15', 1, '2026-01-18 12:59:56', '2026-01-18 03:59:56', '2026-01-18 03:59:56');

-- --------------------------------------------------------

--
-- 테이블 구조 `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `admin_email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `admin_role` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_table` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `after_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `extra_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 테이블의 덤프 데이터 `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `admin_email`, `admin_role`, `action`, `target_table`, `target_id`, `before_json`, `after_json`, `extra_json`, `ip`, `user_agent`, `created_at`) VALUES
(45, 'lte1711@gmail.com', 'superadmin', 'login', NULL, NULL, NULL, NULL, NULL, '112.187.121.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-12 06:01:07'),
(46, 'lte1711@gmail.com', 'superadmin', 'login', NULL, NULL, NULL, NULL, NULL, '112.187.121.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-12 06:35:50');

-- --------------------------------------------------------

--
-- 테이블 구조 `cambodia_progressing`
--

CREATE TABLE `cambodia_progressing` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `pair` varchar(20) DEFAULT NULL,
  `deposit_status` tinyint(1) DEFAULT '0',
  `withdrawal_status` tinyint(1) DEFAULT '0',
  `profit_loss` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `cambodia_ready_trading`
--

CREATE TABLE `cambodia_ready_trading` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `account_id` varchar(50) DEFAULT NULL,
  `account_pw` varchar(50) DEFAULT NULL,
  `platform_1` varchar(50) DEFAULT NULL,
  `platform_2` varchar(50) DEFAULT NULL,
  `server_1` varchar(50) DEFAULT NULL,
  `server_2` varchar(50) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `reject_reason` text,
  `reject_by` varchar(50) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `status` enum('ready','rejected','approved') DEFAULT 'ready',
  `created_by` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `codepay_payout_batches`
--

CREATE TABLE `codepay_payout_batches` (
  `id` int NOT NULL,
  `batch_key` varchar(100) NOT NULL,
  `dividend_id` int DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `codepay_payout_batches`
--

INSERT INTO `codepay_payout_batches` (`id`, `batch_key`, `dividend_id`, `period_start`, `period_end`, `created_by`, `created_at`) VALUES
(27, 'dividend_43', 43, NULL, NULL, '87', '2026-01-15 04:45:34'),
(28, 'profitshare_korea_2026-01-15', NULL, '2026-01-15', '2026-01-15', 'test_in', '2026-01-15 04:45:48'),
(29, 'dividend_44', 44, NULL, NULL, '91', '2026-01-16 05:19:32'),
(30, 'profitshare_korea_2026-01-16', NULL, '2026-01-16', '2026-01-16', 'test_in2', '2026-01-16 05:19:43');

-- --------------------------------------------------------

--
-- 테이블 구조 `codepay_payout_items`
--

CREATE TABLE `codepay_payout_items` (
  `id` int NOT NULL,
  `batch_id` int NOT NULL,
  `dividend_id` int DEFAULT NULL,
  `user_id` int NOT NULL,
  `role` varchar(20) DEFAULT NULL,
  `codepay_address_snapshot` varchar(255) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `fail_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `codepay_payout_items`
--

INSERT INTO `codepay_payout_items` (`id`, `batch_id`, `dividend_id`, `user_id`, `role`, `codepay_address_snapshot`, `amount`, `status`, `fail_reason`, `created_at`) VALUES
(91, 28, NULL, 87, 'investor', 'SDRG', '70.00', 'pending', NULL, '2026-01-15 04:45:48'),
(92, 30, NULL, 91, 'investor', 'SDFGSDFG', '750.00', 'pending', NULL, '2026-01-16 05:19:43');

-- --------------------------------------------------------

--
-- 테이블 구조 `dividend`
--

CREATE TABLE `dividend` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` datetime NOT NULL,
  `gm1_username` varchar(50) DEFAULT 'TheK_KO',
  `gm1_amount` decimal(18,2) DEFAULT '0.00',
  `gm2_username` varchar(50) DEFAULT 'Zeyne',
  `gm2_amount` decimal(18,2) DEFAULT '0.00',
  `gm3_username` varchar(50) DEFAULT 'ezman',
  `gm3_amount` decimal(18,2) DEFAULT '0.00',
  `admin_username` varchar(50) DEFAULT NULL,
  `admin_amount` decimal(18,2) DEFAULT '0.00',
  `mastr_username` varchar(50) DEFAULT NULL,
  `mastr_amount` decimal(18,2) DEFAULT '0.00',
  `agent_username` varchar(50) DEFAULT NULL,
  `agent_amount` decimal(18,2) DEFAULT '0.00',
  `investor_username` varchar(50) DEFAULT NULL,
  `investor_amount` decimal(18,2) DEFAULT '0.00',
  `referral_username` varchar(50) DEFAULT NULL,
  `referral_amount` decimal(18,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `dividend`
--

INSERT INTO `dividend` (`id`, `user_id`, `tx_date`, `gm1_username`, `gm1_amount`, `gm2_username`, `gm2_amount`, `gm3_username`, `gm3_amount`, `admin_username`, `admin_amount`, `mastr_username`, `mastr_amount`, `agent_username`, `agent_amount`, `investor_username`, `investor_amount`, `referral_username`, `referral_amount`, `created_at`) VALUES
(43, 87, '2026-01-15 00:00:00', 'TheK_KO', '21.00', 'Zayne', '14.00', 'ezman', '7.00', 'kd369', '2.10', 'sm999', '2.10', 'py369', '2.80', 'test_in', '17.50', 'k1234', '3.50', '2026-01-15 04:45:34'),
(44, 91, '2026-01-16 00:00:00', 'TheK_KO', '225.00', 'Zayne', '150.00', 'ezman', '75.00', 'test_admin', '22.50', 'test_master', '22.50', 'test_agent', '30.00', 'test_in2', '187.50', 'test_in', '37.50', '2026-01-16 05:19:32');

-- --------------------------------------------------------

--
-- 테이블 구조 `gm_deposits`
--

CREATE TABLE `gm_deposits` (
  `id` int NOT NULL,
  `gm_id` int NOT NULL,
  `region` varchar(50) NOT NULL,
  `deposit_amount` decimal(18,2) NOT NULL,
  `settle_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `gm_sales_daily`
--

CREATE TABLE `gm_sales_daily` (
  `id` int NOT NULL,
  `region` varchar(100) NOT NULL DEFAULT 'default',
  `sales_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sales_date` date NOT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sales_percentage` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '매출 비율 (%)',
  `settled` tinyint(1) DEFAULT '0',
  `settled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `gm_share`
--

CREATE TABLE `gm_share` (
  `gm_id` int NOT NULL,
  `gm_percent` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `investor_agent_map`
--

CREATE TABLE `investor_agent_map` (
  `id` int NOT NULL,
  `investor_id` int NOT NULL,
  `agent_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `japan_progressing`
--

CREATE TABLE `japan_progressing` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `pair` varchar(20) DEFAULT NULL,
  `deposit_status` decimal(12,2) DEFAULT '0.00',
  `withdrawal_status` decimal(12,2) DEFAULT '0.00',
  `profit_loss` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `japan_ready_trading`
--

CREATE TABLE `japan_ready_trading` (
  `id` int NOT NULL,
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
  `day_seq` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '일자별 순번 (01,02...)',
  `created_date` date NOT NULL DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `korea_progressing`
--

CREATE TABLE `korea_progressing` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_id` int DEFAULT NULL,
  `tx_date` date NOT NULL,
  `pair` varchar(20) DEFAULT NULL,
  `deposit_status` decimal(12,2) DEFAULT '0.00',
  `withdrawal_status` decimal(12,2) DEFAULT '0.00',
  `profit_loss` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `korea_progressing`
--

INSERT INTO `korea_progressing` (`id`, `user_id`, `tx_id`, `tx_date`, `pair`, `deposit_status`, `withdrawal_status`, `profit_loss`, `notes`, `created_at`, `settled_by`, `settled_date`, `reject_reason`, `updated_at`) VALUES
(54, 49, 105, '2026-01-01', 'xm,ultima', '2000.00', '0.00', '-2000.00', NULL, '2025-12-30 01:25:49', NULL, NULL, NULL, NULL),
(55, 54, 106, '2026-01-01', 'xm,ultima', '2000.00', '0.00', '-2000.00', NULL, '2025-12-30 03:19:09', NULL, NULL, NULL, NULL),
(56, 50, 107, '2026-01-01', 'xm,ultima', '200.00', '0.00', '-200.00', NULL, '2025-12-30 03:52:56', NULL, NULL, NULL, NULL),
(57, 53, 108, '2026-01-01', 'xm,ultima', '4000.00', '0.00', '-4000.00', NULL, '2025-12-30 06:31:03', NULL, NULL, NULL, NULL),
(104, 87, 136, '2026-01-15', 'xm,ultima', '600.00', '0.00', '-600.00', 'Bot processing started', '2026-01-15 04:02:04', NULL, NULL, NULL, '2026-01-15 04:44:32'),
(105, 91, 137, '2026-01-16', 'xm,ultima', '1000.00', '0.00', '-1000.00', 'Bot processing started', '2026-01-16 05:01:06', NULL, NULL, NULL, '2026-01-16 05:02:12');

-- --------------------------------------------------------

--
-- 테이블 구조 `korea_ready_trading`
--

CREATE TABLE `korea_ready_trading` (
  `id` int NOT NULL,
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
  `day_seq` tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT '일자별 순번 (01,02...)',
  `created_date` date NOT NULL DEFAULT (curdate())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `korea_ready_trading`
--

INSERT INTO `korea_ready_trading` (`id`, `user_id`, `tx_id`, `tx_date`, `account_id`, `account_pw`, `platform_1`, `platform_2`, `server_1`, `server_2`, `deposit_amount`, `reject_reason`, `status`, `created_at`, `reject_by`, `reject_date`, `created_by`, `updated_at`, `day_seq`, `created_date`) VALUES
(79, 87, 136, '2026-01-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-01-15 04:44:32', NULL, NULL, NULL, '2026-01-15 04:44:32', 1, '2026-01-15'),
(80, 91, 137, '2026-01-16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-01-16 05:02:03', NULL, NULL, NULL, '2026-01-16 05:02:03', 1, '2026-01-16');

-- --------------------------------------------------------

--
-- 테이블 구조 `partner_deposits`
--

CREATE TABLE `partner_deposits` (
  `id` int NOT NULL,
  `region` varchar(50) NOT NULL,
  `deposit_amount` decimal(18,2) NOT NULL,
  `deposit_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `transaction_distribution`
--

CREATE TABLE `transaction_distribution` (
  `id` int NOT NULL,
  `tx_id` int NOT NULL,
  `user_id` int NOT NULL,
  `role` varchar(50) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `referral_code` varchar(50) NOT NULL,
  `referrer_id` int DEFAULT NULL COMMENT '추천인 사용자 ID',
  `sponsor_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `country`, `role`, `password_hash`, `created_at`, `last_login`, `phone`, `referral_code`, `referrer_id`, `sponsor_id`) VALUES
(5, 'TheK', 'TheK_KO', 'thekglobals@gmail.com', 'KR', 'gm', '$2y$10$q50Y8tz1rZGLgusAxRkoDuKG/G3I7kzHpO99rAAi0zh53YXKm/03G', '2025-12-12 17:26:38', NULL, '01095529981', 'REF_20251212_719996', NULL, NULL),
(6, 'Kim YongHee', 'ezman', 'ezman55@gmail.com', 'KR', 'gm', '$2y$10$P60.wCAAP8mO9Yd.dmVMfu4Z68I5/MH3Se8wqvl/mTBBwQ7Zu3dH.', '2025-12-12 17:34:03', NULL, '01057994523', 'REF_20251212_793923', NULL, NULL),
(7, 'Malaysia', 'Zayne', 'qweasd2@qasw.com', 'KR', 'gm', '$2y$10$RK7ceTiHLjAOWP8fjguo2uXVBj/tGeTQYiY3icIBkDFRAAHrUu9Ky', '2025-12-12 17:35:24', NULL, '010235469874', 'REF_20251212_500291', NULL, NULL),
(23, '특별 관리자', 'lte1711@gmail.com', 'lte1711@gmail.com', 'KR', 'superadmin', '$2y$10$Apfm6a6pEHg/rPJPQ8LA3ewEAX1w6vQbp003sDvwz2Bw1v12nxeIi', '2025-12-16 04:01:56', NULL, '01067863113', 'REF_20251216_170782', NULL, NULL),
(45, '정도건', 'kd369', 'changzocw@naver.com', 'KR', 'admin', '$2y$10$Yz2YMfFmAGFEyORFOrrzQuC9rrTWffs/l3E4jM3vOvZIQstYNskry', '2025-12-29 05:53:41', NULL, '01040589879', 'REF_20251229_390903', NULL, NULL),
(46, '안평근', 'as119', 'gng20004@gmail.com', 'KR', 'admin', '$2y$10$5fR8P06MqtKs/vCx9liMWu3negzAvUPGpZ0XROXTW91FfU0bkoXCm', '2025-12-29 07:22:26', NULL, '01074207754', 'REF_20251229_874253', NULL, NULL),
(47, '김명순', 'sm999', 'mskim1052@gmail.com', 'KR', 'master', '$2y$10$SrFOZ3JIJ.OAi7SLtTiOyOaRQP/3kbHYFn2DvfaFGPqlv495rw/AG', '2025-12-29 07:22:40', NULL, '01039799999', 'REF_20251229_285164', NULL, 45),
(48, '박연정', 'py369', 'gogos0179@naver.com', 'KR', 'agent', '$2y$10$4VvDk1knFRw85p1ccmWwGOi4PENrSOo3TGKQ58n/04agmtyLYe3xO', '2025-12-29 07:24:42', NULL, '01084270179', 'REF_20251229_663954', 0, 47),
(49, '김명순', 'k1234', 'kms1615@naver.com', 'KR', 'investor', '$2y$10$ybEyVl0H0ghN9vkIFNozvuvHwXYOb/31dtWJoFTp4i.TcilShMT02', '2025-12-29 08:29:01', NULL, '01039799999', 'REF_20251229_459488', NULL, 48),
(50, '이정열', 'l001', 'flowfish@daum.net', 'KR', 'investor', '$2y$10$7EO3fbAcdJwmL.0uVpwmPuIDkV8bYeNZetWbrCR1VpZhjJ99pEeau', '2025-12-29 08:31:47', NULL, '01076160303', 'REF_20251229_288094', 49, 48),
(51, '이미선', 'pp01', '12345@123.com', 'KR', 'investor', '$2y$10$oGJBZLNpIvphurEtu1xA1.XHJkBGhURQc0Y5CPe81G3fvKzFWzzoy', '2025-12-29 08:35:29', NULL, '01039660489', 'REF_20251229_702482', 50, 48),
(52, '정진숙', 'ppoo', '1234@12.com', 'KR', 'investor', '$2y$10$.1FysEkMO39GOcRJzACDL.RpIQEIRPur6/TTgDAICzMwmDFJsNEr2', '2025-12-29 08:36:49', NULL, '01041096622', 'REF_20251229_273780', 51, 48),
(53, '윤동훈', 'ppp02', 'fishdh4390@naver.com', 'KR', 'investor', '$2y$10$r.2ybn2JJoH63F.VEAWdbu1E3g4VhDeF092t0Xcf2xH6nZuBaJQgG', '2025-12-29 08:38:57', NULL, '01041192446', 'REF_20251229_712107', 52, 48),
(54, '강남석', 'k333', '111@11.com', 'KR', 'investor', '$2y$10$gijw.YQjLt0k4SjWNP0euu./TllDWe/CzK5mrWpHLzsWm3LWqiRlS', '2025-12-29 08:41:05', NULL, '01066684972', 'REF_20251229_104782', 50, 48),
(55, '정석규', 'dm4579', 'jsg3567@naver.com', 'KR', 'admin', '$2y$10$ErUS4VyqxJu/N/PY3Mk41uo4EKZobJ/FqlGOMsZ2PBZYAxaBbxyUy', '2025-12-29 12:34:10', NULL, '01051014579', 'REF_20251229_414773', NULL, NULL),
(56, '김대호', 'kdh369', 'daeho1442@gmail.com', 'KR', 'master', '$2y$10$gFDC1/Y74qOoFTZUn8.VHurlpEEJLB//YZSxlEe/Q.zo6HPreuB9O', '2025-12-30 01:12:35', NULL, '01085237779', 'REF_20251230_144939', NULL, 45),
(57, '이성숙', 'ss369', 'asdof@naver.com', 'KR', 'master', '$2y$10$UqcWbAO52JZycWaT9sg8N.mSavYZc8aqHQ643.BSxUGdwG1XXxKX6', '2025-12-30 01:15:36', NULL, '01062392698', 'REF_20251230_157531', NULL, 45),
(58, '조청래', 'sbs1234', 'ccl2555@naver.com', 'KR', 'admin', '$2y$10$lK1LXjBKhaEItyJ.tDtL7.vwQjm8WVeqjrpjGbVOLEc7uhpUtBmxm', '2025-12-30 01:49:21', NULL, '01082367789', 'REF_20251230_957537', NULL, NULL),
(63, '안나현', 'anh1', 'ainibukeqi@gmail.com', 'KR', 'master', '$2y$10$IPyuJ3.3LShWPHwYQ9eeCuh0d8CDeAyU.CbHJpvOPBQH1otmNFroK', '2025-12-30 04:33:05', NULL, '01080083093', 'REF_20251230_043105', NULL, 46),
(64, '이나연', 'sbs2085', 'oi83522065@gmail.com', 'KR', 'master', '$2y$10$YuS6zbZo2AEfSXuQIM.BkOShFK.hP/yLde5tx/IeeWK1kutdmiEmS', '2025-12-30 05:10:04', NULL, '01083522085', 'REF_20251230_050122', NULL, 58),
(65, '류동열', 'ryu3690', '8282ryu@naver.com', 'KR', 'master', '$2y$10$4AdkI8YaSGI.P0n2it8jmO9c9WDxb14AolDyWlrw1/V5CWOWLHxpe', '2025-12-30 05:17:02', NULL, '01026623690', 'REF_20251230_051145', NULL, 58),
(66, '김유남', '1dm5331', 'tomy72a@gmail.com', 'KR', 'master', '$2y$10$IrceFnLh0dMV0uxrDEWYe.s/hES0yj.GgBG6mh0G5L4ph8C8wQxR2', '2025-12-30 08:20:24', NULL, '01027105331', 'REF_20251230_081911', NULL, 55),
(68, '황세정', '예솔', 'sejeong9258@gmail.com', 'KR', 'agent', '$2y$10$Uyoe/RvWjAka4CRKCfZ.XuH/p7EN1i1nKE0lmnGUML7s5K.OA8g0y', '2025-12-31 06:21:35', NULL, '01024749258', 'REF_20251231_062007', NULL, 64),
(69, '김동식', 'dm9902', 'dongsik9902@gmail.com', 'KR', 'agent', '$2y$10$pVnygWZxOkE0is.b5z..AOmHmGUY1JzdRCDVl5fhPFxkobA4Wy4Vu', '2025-12-31 07:14:43', NULL, '01096668569', 'REF_20251231_070921', NULL, 66),
(70, '장승열', 'jsy7883', 'jsyy7883@gmail.com', 'KR', 'agent', '$2y$10$JQQFgeZF2nyY42/uLfVmkugQrTmvlPDdjs9oZ5CsROdbIZtY6dZu2', '2026-01-02 04:53:37', NULL, '01085817883', 'REF_20260102_045214', NULL, 64),
(71, '권태이', 'kte5698', 'a01076795698@gmail.com', 'KR', 'agent', '$2y$10$Qu0hVCCrmZtR0zlcolF53OR54/V5M9eRjIHa7NOHXvkK.MqVolf9a', '2026-01-02 06:54:19', NULL, '01076796698', 'REF_20260102_065233', NULL, 64),
(72, '윤윤선', 'yys1210', 'yyun3849@gmail.com', 'KR', 'investor', '$2y$10$eh0KJ1sPGJrWiHRAVJNCOO6Q8Uq/kNmOBrbsk3p59JhYihvXJvObK', '2026-01-03 04:38:26', NULL, '01054963849', 'REF_20260103_623012', NULL, 68),
(73, '박순임', '7WINWIN', 'winein2465@gmail.com', 'KR', 'master', '$2y$10$pPmaUruIUYPHIJ8Z8oyKaOddS8HdZVjGsyNDxLDjdZ3UOcbgkpxR6', '2026-01-03 04:50:19', NULL, '01030080090', 'REF_20260103_044657', NULL, 45),
(77, 'kim', 'Aezman', 'andyk9220@gmail.com', 'KR', 'admin', '$2y$10$qRQfutvl.DQvEALX8qEEFOC79RjpoTZAEVeNnWGAKFxX66DdQ0v8S', '2026-01-04 09:33:11', NULL, '01057994523', 'REF_20260104_550539', NULL, NULL),
(78, 'andy', 'Mezman', 'kima83101@gmail.com', 'KR', 'master', '$2y$10$HD.tGAOrudgobIryWdMw0ey1UnBc6yNfjjJSfW9XhSYK4zEjDImdC', '2026-01-04 09:35:05', NULL, '01057994523', 'REF_20260104_546255', NULL, 77),
(79, 'andy', 'agezman', 'akim98750@gmail.com', 'KR', 'agent', '$2y$10$EZSYe5ExAvqVUTVTxyNrKujp2Ubm0NeYA3BsfaVwpBkYlxOcEEsyO', '2026-01-04 09:37:28', NULL, '01057994523', 'REF_20260104_895776', NULL, 78),
(80, 'kimyh', 'ivezman', 'akimandy3030@gmail.com', 'KR', 'investor', '$2y$10$U35ixxAVyaOAxaPwLx/HK.eXTAhxjsg573VhQ8Lq6UQxN6ClRmamC', '2026-01-04 09:45:34', NULL, '01057994523', 'REF_20260104_894243', NULL, 79),
(87, 'test_in', 'test_in', 'test_in@next.com', 'KR', 'investor', '$2y$10$.CyZj6io8VSGykDKHneIhe4wqWO6GtsWf3wa1DUp3dN0WADtNDYQy', '2026-01-14 01:13:26', NULL, '01012341254', 'REF_20260114_101861', 49, 48),
(88, 'test_admin', 'test_admin', 'test_admin@next.com', 'KR', 'admin', '$2y$10$tJVZ1qEO2iEVn8RSLmfxJefO2h/kalk5UnE4OWQk0MN3RtdeKzOou', '2026-01-15 04:18:04', NULL, '01012341254', 'REF_20260115_549288', NULL, NULL),
(89, 'test_master', 'test_master', 'test_master@next.com', 'KR', 'master', '$2y$10$U4UKagd9glp2dBAiiFclnOVVrCyWFrb7CYLd9asPOi52IsbU.ScJG', '2026-01-15 04:18:39', NULL, '01012341254', 'REF_20260115_952694', NULL, 88),
(90, 'test_agent', 'test_agent', 'test_agent@next.com', 'KR', 'agent', '$2y$10$huLb8vP93eqA/PV6cYnf7O2Zflfq323vRLYqsz2mk3P1a7zoNO3Wq', '2026-01-15 04:19:32', NULL, '01012341254', 'REF_20260115_674751', NULL, 89),
(91, 'test_in2', 'test_in2', 'test_in2@next.com', 'KR', 'investor', '$2y$10$7AvzsLxNA7aAdmIQ6uL9zudoJMnayziv/lJ.Q0CXBRKaQzNG/neKe', '2026-01-15 04:20:43', NULL, '01012341254', 'REF_20260115_606976', 87, 90),
(92, 'test3_in', 'test3_in', 'test3_in@next.com', 'KR', 'investor', '$2y$10$jrQ/bi/Yk1KGgu3HwnTFvOd0CyJLQaZyfe3xC3D88owuPMirH.7LW', '2026-01-16 09:36:22', NULL, '01032145432', 'REF_20260116_496140', 87, 90);

-- --------------------------------------------------------

--
-- 테이블 구조 `user_details`
--

CREATE TABLE `user_details` (
  `user_id` int NOT NULL,
  `sponsor_user_id` int DEFAULT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `codepay_address` varchar(255) DEFAULT NULL,
  `broker_id` varchar(100) DEFAULT NULL,
  `broker_pw` varchar(100) DEFAULT NULL,
  `xm_id` varchar(100) DEFAULT NULL,
  `xm_pw` varchar(100) DEFAULT NULL,
  `xm_server` varchar(100) DEFAULT NULL,
  `ultima_id` varchar(100) DEFAULT NULL,
  `ultima_pw` varchar(100) DEFAULT NULL,
  `ultima_server` varchar(100) DEFAULT NULL,
  `referral_code` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `broker1_id` varchar(100) DEFAULT NULL,
  `broker1_pw` varchar(100) DEFAULT NULL,
  `broker1_server` varchar(100) DEFAULT NULL,
  `broker2_id` varchar(100) DEFAULT NULL,
  `broker2_pw` varchar(100) DEFAULT NULL,
  `broker2_server` varchar(100) DEFAULT NULL,
  `selected_broker` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `user_details`
--

INSERT INTO `user_details` (`user_id`, `sponsor_user_id`, `wallet_address`, `codepay_address`, `broker_id`, `broker_pw`, `xm_id`, `xm_pw`, `xm_server`, `ultima_id`, `ultima_pw`, `ultima_server`, `referral_code`, `created_at`, `broker1_id`, `broker1_pw`, `broker1_server`, `broker2_id`, `broker2_pw`, `broker2_server`, `selected_broker`) VALUES
(5, NULL, '0x50736a535B1114223D6487bF69431647A06d4F45', 'BAMTOLY', NULL, NULL, '', '', '', '', '', '', 'REF_20251212_719996', '2025-12-12 17:26:38', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, NULL, '0x81f318dc1427d5903dc90c9c518ea0581ac697d4', '', NULL, NULL, '', '', '', '', '', '', 'REF_20251212_793923', '2025-12-12 17:34:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, NULL, '0x741b996a3d9cf1c64dadf3c130d846423817c039', '', NULL, NULL, '', '', '', '', '', '', 'REF_20251212_500291', '2025-12-12 17:35:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, NULL, '없음12345', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20251216_170782', '2025-12-16 04:01:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, NULL, '0x50736a535B1114223D6487bF69431647A06d4F45', 'JJ8545419', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_390903', '2025-12-29 05:53:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, NULL, '0x23301E9D207E9d337847b481222e102f58166083', 'SCG7988', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_874253', '2025-12-29 07:22:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, NULL, '0123', 'KMS1615', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_285164', '2025-12-29 07:22:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, NULL, '0123', 'PYJ0179', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_663954', '2025-12-29 07:24:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, NULL, '1234', 'KMS1615', NULL, NULL, '391021139', 'Qwer123456!', 'XMGlobal-MT5 14', '21227200', 'jHwW9c*p', 'UltimaMarkets-Live 1', 'REF_20251229_459488', '2025-12-29 08:29:01', '', '', '', '', '', '', 'xm,ultima'),
(50, NULL, '1111', 'FLOW3584', NULL, NULL, '110137138', 'Flowfish@@', 'XMGlobal-MT5 2', '21711648', 'Flowfish@@', 'UltimaMarkets-Live 1', 'REF_20251229_288094', '2025-12-29 08:31:47', '', '', '', '', '', '', 'xm,ultima'),
(51, NULL, '111', 'leeme79', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_702482', '2025-12-29 08:35:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, NULL, '111', '6622', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_273780', '2025-12-29 08:36:49', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, NULL, '11', '4390', NULL, NULL, '351805117', 'Fish!43900', 'XMGlobal-MT5 11', '21228165', '06!3sCmi', 'UltimaMarkets-Live 1', 'REF_20251229_712107', '2025-12-29 08:38:57', '', '', '', '', '', '', 'xm,ultima'),
(54, NULL, '11', 'KANG4972', NULL, NULL, '381318568', '@Kang011800', 'XMGlobal-MT5 13', '21228136', '#UMLNd5U', 'UltimaMarkets-Live 1', 'REF_20251229_104782', '2025-12-29 08:41:05', '', '', '', '', '', '', 'xm,ultima'),
(55, NULL, '0xfBD1492FC984E866d24d4F895ae08500800340E9', 'DM4579', NULL, NULL, '', '', '', '', '', '', 'REF_20251229_414773', '2025-12-29 12:34:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(56, NULL, '7A06d4F45', '', NULL, NULL, '', '', '', '', '', '', 'REF_20251230_144939', '2025-12-30 01:12:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(57, NULL, '1111', '', NULL, NULL, '', '', '', '', '', '', 'REF_20251230_157531', '2025-12-30 01:15:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(58, NULL, '0xbcdD9603A08Cd4bA3EB94c555F77CCCf47249EF6', 'CCL25778', NULL, NULL, '', '', '', '', '', '', 'REF_20251230_957537', '2025-12-30 01:49:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, NULL, '0xCe2A0810BceC79dAe39b25c171C7f463F9a1ae22', 'ANNA8008', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20251230_043105', '2025-12-30 04:33:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, NULL, '0xbcdD9603A08Cd4bA3EB94c555F77CCCf47249EF6', 'SKY2085', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20251230_050122', '2025-12-30 05:10:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(65, NULL, 'kkkkkkkk', '8282RYU', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20251230_051145', '2025-12-30 05:17:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, NULL, '0xfBD1492FC984E866d24d4F895ae08500800340E9', 'DM5331', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20251230_081911', '2025-12-30 08:20:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, NULL, 'kkkkkkkk', 'HSJ9258', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20251231_062007', '2025-12-31 06:21:35', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, NULL, '1234', 'KL8569', NULL, NULL, '', '', '', '', '', '', 'REF_20251231_070921', '2025-12-31 07:14:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, NULL, 'kkkk', 'JSY7883', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20260102_045214', '2026-01-02 04:53:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, NULL, 'kkkkkkkk', 'KTE5698', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20260102_065233', '2026-01-02 06:54:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(72, NULL, 'kkkkkkkk', 'YYS3849', NULL, NULL, '', '', '', '', '', '', 'REF_20260103_623012', '2026-01-03 04:38:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, NULL, '0x84b823a239e7bbb03b7fe15b7df6478c56dac1c8', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'REF_20260103_044657', '2026-01-03 04:50:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(77, NULL, '0x81f318dc1427d5903dc90c9c518ea0581ac697d4', '', NULL, NULL, '', '', '', '', '', '', 'REF_20260104_550539', '2026-01-04 09:33:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(78, NULL, '0x81f318dc1427d5903dc90c9c518ea0581ac697d4', '', NULL, NULL, '', '', '', '', '', '', 'REF_20260104_546255', '2026-01-04 09:35:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(79, NULL, '0x81f318dc1427d5903dc90c9c518ea0581ac697d4', '', NULL, NULL, '', '', '', '', '', '', 'REF_20260104_895776', '2026-01-04 09:37:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(80, NULL, '0x81f318dc1427d5903dc90c9c518ea0581ac697d4', '', NULL, NULL, '301863176', 'Haha@123789', 'XMGlobal-MT5 6', '19618064', 'SDx#@z8', 'UltimaMarket Live-1', 'REF_20260104_894243', '2026-01-04 09:45:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, NULL, 'sdfg', 'SDRG', NULL, NULL, '2456234532', '23452345234', '23452345', '324523452345', '5234523452345', '23452345234', 'REF_20260114_101861', '2026-01-14 01:13:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(88, NULL, 'afdg', 'ADRG', NULL, NULL, '', '', '', '', '', '', 'REF_20260115_549288', '2026-01-15 04:18:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(89, NULL, 'qwer', 'QWER', NULL, NULL, '', '', '', '', '', '', 'REF_20260115_952694', '2026-01-15 04:18:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(90, NULL, 'sdfgsdfg', 'SDFG', NULL, NULL, '', '', '', '', '', '', 'REF_20260115_674751', '2026-01-15 04:19:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(91, NULL, 'adftrhsfdg', 'SDFGSDFG', NULL, NULL, '543764567', '675643567', '34252345', '2352345', '6789679', '578907890', 'REF_20260115_606976', '2026-01-15 04:20:43', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(92, NULL, 'Dffcdrf', 'FCDDGGV', NULL, NULL, '', '', '', '', '', '', 'REF_20260116_496140', '2026-01-16 09:36:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 테이블 구조 `user_rejects`
--

CREATE TABLE `user_rejects` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `user_summary`
--

CREATE TABLE `user_summary` (
  `user_id` int NOT NULL,
  `deposit` decimal(18,2) DEFAULT '0.00',
  `withdrawal` decimal(18,2) DEFAULT '0.00',
  `profit` decimal(18,2) DEFAULT '0.00',
  `share_80` decimal(18,2) DEFAULT '0.00',
  `share_20` decimal(18,2) DEFAULT '0.00',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `user_transactions`
--

CREATE TABLE `user_transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `xm_value` decimal(15,2) DEFAULT NULL,
  `ultima_value` decimal(15,2) DEFAULT NULL,
  `pair` varchar(50) DEFAULT NULL,
  `deposit_status` varchar(20) DEFAULT NULL,
  `withdrawal_status` varchar(20) DEFAULT NULL,
  `profit_loss` varchar(20) DEFAULT NULL,
  `etc_note` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `code_value` varchar(50) NOT NULL,
  `xm_total` decimal(18,2) DEFAULT '0.00',
  `ultima_total` decimal(18,2) DEFAULT '0.00',
  `xm_dividend` decimal(18,2) DEFAULT '0.00' COMMENT 'XM 배당금',
  `ultima_dividend` decimal(18,2) DEFAULT '0.00' COMMENT 'Ultima 배당금',
  `deposit_chk` tinyint(1) DEFAULT '0' COMMENT '입금 체크',
  `dividend_chk` tinyint(1) DEFAULT '0' COMMENT '배당 체크',
  `principal_xm` decimal(18,2) DEFAULT '0.00' COMMENT 'XM 원금',
  `principal_ultima` decimal(18,2) DEFAULT '0.00' COMMENT 'Ultima 원금',
  `settle_chk` tinyint(1) DEFAULT '0' COMMENT '정산 체크',
  `reject_reason` text,
  `reject_by` varchar(50) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `withdrawal_chk` tinyint(1) NOT NULL DEFAULT '0' COMMENT '출금 여부 체크 (0=미완료, 1=완료)',
  `dividend_amount` decimal(18,2) DEFAULT '0.00',
  `external_done_chk` tinyint(1) NOT NULL DEFAULT '0',
  `external_done_date` date DEFAULT NULL COMMENT '외부 거래 완료 확인 일자'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 테이블의 덤프 데이터 `user_transactions`
--

INSERT INTO `user_transactions` (`id`, `user_id`, `tx_date`, `xm_value`, `ultima_value`, `pair`, `deposit_status`, `withdrawal_status`, `profit_loss`, `etc_note`, `created_at`, `code_value`, `xm_total`, `ultima_total`, `xm_dividend`, `ultima_dividend`, `deposit_chk`, `dividend_chk`, `principal_xm`, `principal_ultima`, `settle_chk`, `reject_reason`, `reject_by`, `reject_date`, `settled_by`, `settled_date`, `withdrawal_chk`, `dividend_amount`, `external_done_chk`, `external_done_date`) VALUES
(105, 49, '2026-01-01', '1000.00', '1000.00', 'XM/Ultima', '1', NULL, NULL, NULL, '2025-12-30 01:25:49', 'CODE_69532a1d93cef', '0.00', '0.00', '0.00', '0.00', 1, 0, '0.00', '0.00', 0, NULL, NULL, NULL, NULL, NULL, 0, '0.00', 0, NULL),
(106, 54, '2026-01-01', '1000.00', '1000.00', 'XM/Ultima', '1', NULL, NULL, NULL, '2025-12-30 03:19:09', 'CODE_695344add1c3f', '0.00', '0.00', '0.00', '0.00', 1, 0, '0.00', '0.00', 0, NULL, NULL, NULL, NULL, NULL, 0, '0.00', 0, NULL),
(107, 50, '2026-01-01', '100.00', '100.00', 'XM/Ultima', '1', NULL, NULL, NULL, '2025-12-30 03:52:56', 'CODE_69534c98c6d09', '0.00', '0.00', '0.00', '0.00', 1, 0, '0.00', '0.00', 0, NULL, NULL, NULL, NULL, NULL, 0, '0.00', 0, NULL),
(108, 53, '2026-01-01', '2000.00', '2000.00', 'XM/Ultima', '1', NULL, NULL, NULL, '2025-12-30 06:31:03', 'CODE_695371a79f8cb', '0.00', '0.00', '0.00', '0.00', 1, 0, '0.00', '0.00', 0, NULL, NULL, NULL, NULL, NULL, 0, '0.00', 0, NULL),
(136, 87, '2026-01-15', '300.00', '300.00', 'XM/Ultima', NULL, NULL, NULL, NULL, '2026-01-15 04:02:04', 'CODE_696866bca25e9', '670.00', '0.00', '70.00', '0.00', 1, 1, '0.00', '0.00', 1, NULL, NULL, NULL, 'test_in', '2026-01-15 04:45:48', 1, '70.00', 1, '2026-01-15'),
(137, 91, '2026-01-16', '500.00', '500.00', 'XM/Ultima', NULL, NULL, NULL, NULL, '2026-01-16 05:01:06', 'CODE_6969c6122612a', '1750.00', '0.00', '750.00', '0.00', 1, 1, '0.00', '0.00', 1, NULL, NULL, NULL, 'test_in2', '2026-01-16 05:19:43', 1, '750.00', 1, '2026-01-16');

-- --------------------------------------------------------

--
-- 테이블 구조 `vietnam_progressing`
--

CREATE TABLE `vietnam_progressing` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `pair` varchar(20) DEFAULT NULL,
  `deposit_status` tinyint(1) DEFAULT '0',
  `withdrawal_status` tinyint(1) DEFAULT '0',
  `profit_loss` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `settled_by` varchar(50) DEFAULT NULL,
  `settled_date` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 테이블 구조 `vietnam_ready_trading`
--

CREATE TABLE `vietnam_ready_trading` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `tx_date` date NOT NULL,
  `account_id` varchar(50) DEFAULT NULL,
  `account_pw` varchar(50) DEFAULT NULL,
  `platform_1` varchar(50) DEFAULT NULL,
  `platform_2` varchar(50) DEFAULT NULL,
  `server_1` varchar(50) DEFAULT NULL,
  `server_2` varchar(50) DEFAULT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `reject_reason` text,
  `reject_by` varchar(50) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `status` enum('ready','rejected','approved') DEFAULT 'ready',
  `created_by` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_settle_date` (`settle_date`,`created_at`),
  ADD KEY `idx_audit_event` (`event_code`,`created_at`),
  ADD KEY `idx_audit_tx_id` (`tx_id`);

--
-- 테이블의 인덱스 `admin_deposits_daily`
--
ALTER TABLE `admin_deposits_daily`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 테이블의 인덱스 `admin_sales_daily`
--
ALTER TABLE `admin_sales_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_admin_date` (`user_id`,`sales_date`),
  ADD KEY `user_id` (`user_id`);

--
-- 테이블의 인덱스 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_admin_email` (`admin_email`),
  ADD KEY `idx_target` (`target_table`,`target_id`);

--
-- 테이블의 인덱스 `cambodia_progressing`
--
ALTER TABLE `cambodia_progressing`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `cambodia_ready_trading`
--
ALTER TABLE `cambodia_ready_trading`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `codepay_payout_batches`
--
ALTER TABLE `codepay_payout_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_codepay_batch_key` (`batch_key`),
  ADD KEY `idx_codepay_dividend_id` (`dividend_id`),
  ADD KEY `idx_codepay_created_at` (`created_at`);

--
-- 테이블의 인덱스 `codepay_payout_items`
--
ALTER TABLE `codepay_payout_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_codepay_batch_id` (`batch_id`),
  ADD KEY `idx_codepay_user_id` (`user_id`),
  ADD KEY `idx_codepay_dividend_id` (`dividend_id`),
  ADD KEY `idx_codepay_status` (`status`),
  ADD KEY `idx_codepay_created_at` (`created_at`);

--
-- 테이블의 인덱스 `dividend`
--
ALTER TABLE `dividend`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `gm_deposits`
--
ALTER TABLE `gm_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gm_region_date` (`gm_id`,`region`,`settle_date`);

--
-- 테이블의 인덱스 `gm_sales_daily`
--
ALTER TABLE `gm_sales_daily`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 테이블의 인덱스 `gm_share`
--
ALTER TABLE `gm_share`
  ADD PRIMARY KEY (`gm_id`);

--
-- 테이블의 인덱스 `investor_agent_map`
--
ALTER TABLE `investor_agent_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `investor_id` (`investor_id`),
  ADD KEY `agent_id` (`agent_id`);

--
-- 테이블의 인덱스 `japan_progressing`
--
ALTER TABLE `japan_progressing`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `japan_ready_trading`
--
ALTER TABLE `japan_ready_trading`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date_seq` (`user_id`,`created_date`,`day_seq`);

--
-- 테이블의 인덱스 `korea_progressing`
--
ALTER TABLE `korea_progressing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_date_pair` (`user_id`,`tx_date`,`pair`),
  ADD UNIQUE KEY `uniq_kp_tx_id` (`tx_id`),
  ADD KEY `idx_kp_tx_id` (`tx_id`);

--
-- 테이블의 인덱스 `korea_ready_trading`
--
ALTER TABLE `korea_ready_trading`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user_tx` (`user_id`,`tx_id`),
  ADD UNIQUE KEY `uniq_tx_id` (`tx_id`),
  ADD KEY `idx_user_date` (`user_id`,`tx_date`);

--
-- 테이블의 인덱스 `partner_deposits`
--
ALTER TABLE `partner_deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_region_date` (`region`,`deposit_time`);

--
-- 테이블의 인덱스 `transaction_distribution`
--
ALTER TABLE `transaction_distribution`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tx_id` (`tx_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 테이블의 인덱스 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_sponsor` (`sponsor_id`);

--
-- 테이블의 인덱스 `user_details`
--
ALTER TABLE `user_details`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `idx_user_details_sponsor_user_id` (`sponsor_user_id`);

--
-- 테이블의 인덱스 `user_rejects`
--
ALTER TABLE `user_rejects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 테이블의 인덱스 `user_summary`
--
ALTER TABLE `user_summary`
  ADD PRIMARY KEY (`user_id`);

--
-- 테이블의 인덱스 `user_transactions`
--
ALTER TABLE `user_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 테이블의 인덱스 `vietnam_progressing`
--
ALTER TABLE `vietnam_progressing`
  ADD PRIMARY KEY (`id`);

--
-- 테이블의 인덱스 `vietnam_ready_trading`
--
ALTER TABLE `vietnam_ready_trading`
  ADD PRIMARY KEY (`id`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `admin_audit_log`
--
ALTER TABLE `admin_audit_log`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 테이블의 AUTO_INCREMENT `admin_deposits_daily`
--
ALTER TABLE `admin_deposits_daily`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `admin_sales_daily`
--
ALTER TABLE `admin_sales_daily`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- 테이블의 AUTO_INCREMENT `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- 테이블의 AUTO_INCREMENT `cambodia_progressing`
--
ALTER TABLE `cambodia_progressing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `cambodia_ready_trading`
--
ALTER TABLE `cambodia_ready_trading`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `codepay_payout_batches`
--
ALTER TABLE `codepay_payout_batches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- 테이블의 AUTO_INCREMENT `codepay_payout_items`
--
ALTER TABLE `codepay_payout_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- 테이블의 AUTO_INCREMENT `dividend`
--
ALTER TABLE `dividend`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- 테이블의 AUTO_INCREMENT `gm_deposits`
--
ALTER TABLE `gm_deposits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `gm_sales_daily`
--
ALTER TABLE `gm_sales_daily`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- 테이블의 AUTO_INCREMENT `investor_agent_map`
--
ALTER TABLE `investor_agent_map`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 테이블의 AUTO_INCREMENT `japan_progressing`
--
ALTER TABLE `japan_progressing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 테이블의 AUTO_INCREMENT `japan_ready_trading`
--
ALTER TABLE `japan_ready_trading`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `korea_progressing`
--
ALTER TABLE `korea_progressing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- 테이블의 AUTO_INCREMENT `korea_ready_trading`
--
ALTER TABLE `korea_ready_trading`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- 테이블의 AUTO_INCREMENT `partner_deposits`
--
ALTER TABLE `partner_deposits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `transaction_distribution`
--
ALTER TABLE `transaction_distribution`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- 테이블의 AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- 테이블의 AUTO_INCREMENT `user_rejects`
--
ALTER TABLE `user_rejects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- 테이블의 AUTO_INCREMENT `user_transactions`
--
ALTER TABLE `user_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- 테이블의 AUTO_INCREMENT `vietnam_progressing`
--
ALTER TABLE `vietnam_progressing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 테이블의 AUTO_INCREMENT `vietnam_ready_trading`
--
ALTER TABLE `vietnam_ready_trading`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 덤프된 테이블의 제약사항
--

--
-- 테이블의 제약사항 `admin_deposits_daily`
--
ALTER TABLE `admin_deposits_daily`
  ADD CONSTRAINT `admin_deposits_daily_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `admin_sales_daily`
--
ALTER TABLE `admin_sales_daily`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `codepay_payout_items`
--
ALTER TABLE `codepay_payout_items`
  ADD CONSTRAINT `fk_codepay_items_batch` FOREIGN KEY (`batch_id`) REFERENCES `codepay_payout_batches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 테이블의 제약사항 `gm_sales_daily`
--
ALTER TABLE `gm_sales_daily`
  ADD CONSTRAINT `fk_gm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `investor_agent_map`
--
ALTER TABLE `investor_agent_map`
  ADD CONSTRAINT `investor_agent_map_ibfk_1` FOREIGN KEY (`investor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `investor_agent_map_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- 테이블의 제약사항 `transaction_distribution`
--
ALTER TABLE `transaction_distribution`
  ADD CONSTRAINT `fk_tx` FOREIGN KEY (`tx_id`) REFERENCES `user_transactions` (`id`),
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_sponsor` FOREIGN KEY (`sponsor_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `user_details`
--
ALTER TABLE `user_details`
  ADD CONSTRAINT `fk_details_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `user_rejects`
--
ALTER TABLE `user_rejects`
  ADD CONSTRAINT `fk_rejects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 테이블의 제약사항 `user_transactions`
--
ALTER TABLE `user_transactions`
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
