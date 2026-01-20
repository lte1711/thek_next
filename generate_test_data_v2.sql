-- ===============================================
-- 테스트 데이터 생성 스크립트 (phpMyAdmin용)
-- 한국 100명 + 일본 100명 (총 200명)
-- 패스워드: 1234
-- ===============================================

-- 외래 키 체크 비활성화
SET FOREIGN_KEY_CHECKS = 0;

-- 기존 테스트 데이터 삭제
DELETE FROM korea_progressing WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'kr_%' OR username LIKE 'jp_%');
DELETE FROM japan_progressing WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'kr_%' OR username LIKE 'jp_%');
DELETE FROM user_transactions WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'kr_%' OR username LIKE 'jp_%');
DELETE FROM users WHERE username LIKE 'kr_%' OR username LIKE 'jp_%';

-- 외래 키 체크 재활성화
SET FOREIGN_KEY_CHECKS = 1;

-- 한국 사용자 100명 생성
-- gm: 1명, admin: 5명, master: 5명, agent: 20명, investor: 69명
INSERT INTO users (name, username, email, country, role, password_hash, referral_code) VALUES
-- GM (1�?
('KR_GM_01', 'kr_gm_01', 'kr_gm_01@test.com', 'KR', 'gm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRGM01'),
-- Admin (5�?
('KR_Admin_01', 'kr_adm_01', 'kr_adm_01@test.com', 'KR', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAD01'),
('KR_Admin_02', 'kr_adm_02', 'kr_adm_02@test.com', 'KR', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAD02'),
('KR_Admin_03', 'kr_adm_03', 'kr_adm_03@test.com', 'KR', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAD03'),
('KR_Admin_04', 'kr_adm_04', 'kr_adm_04@test.com', 'KR', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAD04'),
('KR_Admin_05', 'kr_adm_05', 'kr_adm_05@test.com', 'KR', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAD05'),
-- Master (5�?
('KR_Master_01', 'kr_mst_01', 'kr_mst_01@test.com', 'KR', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRMST01'),
('KR_Master_02', 'kr_mst_02', 'kr_mst_02@test.com', 'KR', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRMST02'),
('KR_Master_03', 'kr_mst_03', 'kr_mst_03@test.com', 'KR', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRMST03'),
('KR_Master_04', 'kr_mst_04', 'kr_mst_04@test.com', 'KR', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRMST04'),
('KR_Master_05', 'kr_mst_05', 'kr_mst_05@test.com', 'KR', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRMST05'),
-- Agent (20�?
('KR_Agent_01', 'kr_agt_01', 'kr_agt_01@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT01'),
('KR_Agent_02', 'kr_agt_02', 'kr_agt_02@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT02'),
('KR_Agent_03', 'kr_agt_03', 'kr_agt_03@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT03'),
('KR_Agent_04', 'kr_agt_04', 'kr_agt_04@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT04'),
('KR_Agent_05', 'kr_agt_05', 'kr_agt_05@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT05'),
('KR_Agent_06', 'kr_agt_06', 'kr_agt_06@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT06'),
('KR_Agent_07', 'kr_agt_07', 'kr_agt_07@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT07'),
('KR_Agent_08', 'kr_agt_08', 'kr_agt_08@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT08'),
('KR_Agent_09', 'kr_agt_09', 'kr_agt_09@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT09'),
('KR_Agent_10', 'kr_agt_10', 'kr_agt_10@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT10'),
('KR_Agent_11', 'kr_agt_11', 'kr_agt_11@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT11'),
('KR_Agent_12', 'kr_agt_12', 'kr_agt_12@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT12'),
('KR_Agent_13', 'kr_agt_13', 'kr_agt_13@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT13'),
('KR_Agent_14', 'kr_agt_14', 'kr_agt_14@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT14'),
('KR_Agent_15', 'kr_agt_15', 'kr_agt_15@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT15'),
('KR_Agent_16', 'kr_agt_16', 'kr_agt_16@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT16'),
('KR_Agent_17', 'kr_agt_17', 'kr_agt_17@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT17'),
('KR_Agent_18', 'kr_agt_18', 'kr_agt_18@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT18'),
('KR_Agent_19', 'kr_agt_19', 'kr_agt_19@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT19'),
('KR_Agent_20', 'kr_agt_20', 'kr_agt_20@test.com', 'KR', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRAGT20'),
-- Investor (69�?
('KR_Investor_01', 'kr_inv_01', 'kr_inv_01@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV01'),
('KR_Investor_02', 'kr_inv_02', 'kr_inv_02@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV02'),
('KR_Investor_03', 'kr_inv_03', 'kr_inv_03@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV03'),
('KR_Investor_04', 'kr_inv_04', 'kr_inv_04@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV04'),
('KR_Investor_05', 'kr_inv_05', 'kr_inv_05@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV05'),
('KR_Investor_06', 'kr_inv_06', 'kr_inv_06@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV06'),
('KR_Investor_07', 'kr_inv_07', 'kr_inv_07@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV07'),
('KR_Investor_08', 'kr_inv_08', 'kr_inv_08@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV08'),
('KR_Investor_09', 'kr_inv_09', 'kr_inv_09@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV09'),
('KR_Investor_10', 'kr_inv_10', 'kr_inv_10@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV10'),
('KR_Investor_11', 'kr_inv_11', 'kr_inv_11@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV11'),
('KR_Investor_12', 'kr_inv_12', 'kr_inv_12@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV12'),
('KR_Investor_13', 'kr_inv_13', 'kr_inv_13@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV13'),
('KR_Investor_14', 'kr_inv_14', 'kr_inv_14@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV14'),
('KR_Investor_15', 'kr_inv_15', 'kr_inv_15@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV15'),
('KR_Investor_16', 'kr_inv_16', 'kr_inv_16@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV16'),
('KR_Investor_17', 'kr_inv_17', 'kr_inv_17@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV17'),
('KR_Investor_18', 'kr_inv_18', 'kr_inv_18@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV18'),
('KR_Investor_19', 'kr_inv_19', 'kr_inv_19@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV19'),
('KR_Investor_20', 'kr_inv_20', 'kr_inv_20@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV20'),
('KR_Investor_21', 'kr_inv_21', 'kr_inv_21@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV21'),
('KR_Investor_22', 'kr_inv_22', 'kr_inv_22@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV22'),
('KR_Investor_23', 'kr_inv_23', 'kr_inv_23@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV23'),
('KR_Investor_24', 'kr_inv_24', 'kr_inv_24@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV24'),
('KR_Investor_25', 'kr_inv_25', 'kr_inv_25@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV25'),
('KR_Investor_26', 'kr_inv_26', 'kr_inv_26@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV26'),
('KR_Investor_27', 'kr_inv_27', 'kr_inv_27@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV27'),
('KR_Investor_28', 'kr_inv_28', 'kr_inv_28@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV28'),
('KR_Investor_29', 'kr_inv_29', 'kr_inv_29@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV29'),
('KR_Investor_30', 'kr_inv_30', 'kr_inv_30@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV30'),
('KR_Investor_31', 'kr_inv_31', 'kr_inv_31@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV31'),
('KR_Investor_32', 'kr_inv_32', 'kr_inv_32@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV32'),
('KR_Investor_33', 'kr_inv_33', 'kr_inv_33@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV33'),
('KR_Investor_34', 'kr_inv_34', 'kr_inv_34@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV34'),
('KR_Investor_35', 'kr_inv_35', 'kr_inv_35@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV35'),
('KR_Investor_36', 'kr_inv_36', 'kr_inv_36@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV36'),
('KR_Investor_37', 'kr_inv_37', 'kr_inv_37@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV37'),
('KR_Investor_38', 'kr_inv_38', 'kr_inv_38@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV38'),
('KR_Investor_39', 'kr_inv_39', 'kr_inv_39@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV39'),
('KR_Investor_40', 'kr_inv_40', 'kr_inv_40@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV40'),
('KR_Investor_41', 'kr_inv_41', 'kr_inv_41@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV41'),
('KR_Investor_42', 'kr_inv_42', 'kr_inv_42@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV42'),
('KR_Investor_43', 'kr_inv_43', 'kr_inv_43@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV43'),
('KR_Investor_44', 'kr_inv_44', 'kr_inv_44@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV44'),
('KR_Investor_45', 'kr_inv_45', 'kr_inv_45@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV45'),
('KR_Investor_46', 'kr_inv_46', 'kr_inv_46@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV46'),
('KR_Investor_47', 'kr_inv_47', 'kr_inv_47@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV47'),
('KR_Investor_48', 'kr_inv_48', 'kr_inv_48@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV48'),
('KR_Investor_49', 'kr_inv_49', 'kr_inv_49@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV49'),
('KR_Investor_50', 'kr_inv_50', 'kr_inv_50@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV50'),
('KR_Investor_51', 'kr_inv_51', 'kr_inv_51@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV51'),
('KR_Investor_52', 'kr_inv_52', 'kr_inv_52@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV52'),
('KR_Investor_53', 'kr_inv_53', 'kr_inv_53@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV53'),
('KR_Investor_54', 'kr_inv_54', 'kr_inv_54@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV54'),
('KR_Investor_55', 'kr_inv_55', 'kr_inv_55@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV55'),
('KR_Investor_56', 'kr_inv_56', 'kr_inv_56@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV56'),
('KR_Investor_57', 'kr_inv_57', 'kr_inv_57@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV57'),
('KR_Investor_58', 'kr_inv_58', 'kr_inv_58@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV58'),
('KR_Investor_59', 'kr_inv_59', 'kr_inv_59@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV59'),
('KR_Investor_60', 'kr_inv_60', 'kr_inv_60@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV60'),
('KR_Investor_61', 'kr_inv_61', 'kr_inv_61@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV61'),
('KR_Investor_62', 'kr_inv_62', 'kr_inv_62@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV62'),
('KR_Investor_63', 'kr_inv_63', 'kr_inv_63@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV63'),
('KR_Investor_64', 'kr_inv_64', 'kr_inv_64@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV64'),
('KR_Investor_65', 'kr_inv_65', 'kr_inv_65@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV65'),
('KR_Investor_66', 'kr_inv_66', 'kr_inv_66@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV66'),
('KR_Investor_67', 'kr_inv_67', 'kr_inv_67@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV67'),
('KR_Investor_68', 'kr_inv_68', 'kr_inv_68@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV68'),
('KR_Investor_69', 'kr_inv_69', 'kr_inv_69@test.com', 'KR', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'KRINV69'),

-- ?�본 ?�용??100�??�성
-- GM (1�?
('JP_GM_01', 'jp_gm_01', 'jp_gm_01@test.com', 'JP', 'gm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPGM01'),
-- Admin (5�?
('JP_Admin_01', 'jp_adm_01', 'jp_adm_01@test.com', 'JP', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAD01'),
('JP_Admin_02', 'jp_adm_02', 'jp_adm_02@test.com', 'JP', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAD02'),
('JP_Admin_03', 'jp_adm_03', 'jp_adm_03@test.com', 'JP', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAD03'),
('JP_Admin_04', 'jp_adm_04', 'jp_adm_04@test.com', 'JP', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAD04'),
('JP_Admin_05', 'jp_adm_05', 'jp_adm_05@test.com', 'JP', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAD05'),
-- Master (5�?
('JP_Master_01', 'jp_mst_01', 'jp_mst_01@test.com', 'JP', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPMST01'),
('JP_Master_02', 'jp_mst_02', 'jp_mst_02@test.com', 'JP', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPMST02'),
('JP_Master_03', 'jp_mst_03', 'jp_mst_03@test.com', 'JP', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPMST03'),
('JP_Master_04', 'jp_mst_04', 'jp_mst_04@test.com', 'JP', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPMST04'),
('JP_Master_05', 'jp_mst_05', 'jp_mst_05@test.com', 'JP', 'master', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPMST05'),
-- Agent (20�?
('JP_Agent_01', 'jp_agt_01', 'jp_agt_01@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT01'),
('JP_Agent_02', 'jp_agt_02', 'jp_agt_02@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT02'),
('JP_Agent_03', 'jp_agt_03', 'jp_agt_03@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT03'),
('JP_Agent_04', 'jp_agt_04', 'jp_agt_04@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT04'),
('JP_Agent_05', 'jp_agt_05', 'jp_agt_05@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT05'),
('JP_Agent_06', 'jp_agt_06', 'jp_agt_06@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT06'),
('JP_Agent_07', 'jp_agt_07', 'jp_agt_07@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT07'),
('JP_Agent_08', 'jp_agt_08', 'jp_agt_08@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT08'),
('JP_Agent_09', 'jp_agt_09', 'jp_agt_09@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT09'),
('JP_Agent_10', 'jp_agt_10', 'jp_agt_10@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT10'),
('JP_Agent_11', 'jp_agt_11', 'jp_agt_11@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT11'),
('JP_Agent_12', 'jp_agt_12', 'jp_agt_12@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT12'),
('JP_Agent_13', 'jp_agt_13', 'jp_agt_13@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT13'),
('JP_Agent_14', 'jp_agt_14', 'jp_agt_14@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT14'),
('JP_Agent_15', 'jp_agt_15', 'jp_agt_15@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT15'),
('JP_Agent_16', 'jp_agt_16', 'jp_agt_16@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT16'),
('JP_Agent_17', 'jp_agt_17', 'jp_agt_17@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT17'),
('JP_Agent_18', 'jp_agt_18', 'jp_agt_18@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT18'),
('JP_Agent_19', 'jp_agt_19', 'jp_agt_19@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT19'),
('JP_Agent_20', 'jp_agt_20', 'jp_agt_20@test.com', 'JP', 'agent', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPAGT20'),
-- Investor (69�?
('JP_Investor_01', 'jp_inv_01', 'jp_inv_01@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV01'),
('JP_Investor_02', 'jp_inv_02', 'jp_inv_02@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV02'),
('JP_Investor_03', 'jp_inv_03', 'jp_inv_03@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV03'),
('JP_Investor_04', 'jp_inv_04', 'jp_inv_04@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV04'),
('JP_Investor_05', 'jp_inv_05', 'jp_inv_05@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV05'),
('JP_Investor_06', 'jp_inv_06', 'jp_inv_06@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV06'),
('JP_Investor_07', 'jp_inv_07', 'jp_inv_07@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV07'),
('JP_Investor_08', 'jp_inv_08', 'jp_inv_08@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV08'),
('JP_Investor_09', 'jp_inv_09', 'jp_inv_09@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV09'),
('JP_Investor_10', 'jp_inv_10', 'jp_inv_10@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV10'),
('JP_Investor_11', 'jp_inv_11', 'jp_inv_11@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV11'),
('JP_Investor_12', 'jp_inv_12', 'jp_inv_12@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV12'),
('JP_Investor_13', 'jp_inv_13', 'jp_inv_13@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV13'),
('JP_Investor_14', 'jp_inv_14', 'jp_inv_14@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV14'),
('JP_Investor_15', 'jp_inv_15', 'jp_inv_15@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV15'),
('JP_Investor_16', 'jp_inv_16', 'jp_inv_16@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV16'),
('JP_Investor_17', 'jp_inv_17', 'jp_inv_17@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV17'),
('JP_Investor_18', 'jp_inv_18', 'jp_inv_18@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV18'),
('JP_Investor_19', 'jp_inv_19', 'jp_inv_19@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV19'),
('JP_Investor_20', 'jp_inv_20', 'jp_inv_20@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV20'),
('JP_Investor_21', 'jp_inv_21', 'jp_inv_21@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV21'),
('JP_Investor_22', 'jp_inv_22', 'jp_inv_22@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV22'),
('JP_Investor_23', 'jp_inv_23', 'jp_inv_23@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV23'),
('JP_Investor_24', 'jp_inv_24', 'jp_inv_24@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV24'),
('JP_Investor_25', 'jp_inv_25', 'jp_inv_25@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV25'),
('JP_Investor_26', 'jp_inv_26', 'jp_inv_26@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV26'),
('JP_Investor_27', 'jp_inv_27', 'jp_inv_27@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV27'),
('JP_Investor_28', 'jp_inv_28', 'jp_inv_28@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV28'),
('JP_Investor_29', 'jp_inv_29', 'jp_inv_29@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV29'),
('JP_Investor_30', 'jp_inv_30', 'jp_inv_30@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV30'),
('JP_Investor_31', 'jp_inv_31', 'jp_inv_31@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV31'),
('JP_Investor_32', 'jp_inv_32', 'jp_inv_32@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV32'),
('JP_Investor_33', 'jp_inv_33', 'jp_inv_33@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV33'),
('JP_Investor_34', 'jp_inv_34', 'jp_inv_34@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV34'),
('JP_Investor_35', 'jp_inv_35', 'jp_inv_35@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV35'),
('JP_Investor_36', 'jp_inv_36', 'jp_inv_36@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV36'),
('JP_Investor_37', 'jp_inv_37', 'jp_inv_37@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV37'),
('JP_Investor_38', 'jp_inv_38', 'jp_inv_38@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV38'),
('JP_Investor_39', 'jp_inv_39', 'jp_inv_39@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV39'),
('JP_Investor_40', 'jp_inv_40', 'jp_inv_40@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV40'),
('JP_Investor_41', 'jp_inv_41', 'jp_inv_41@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV41'),
('JP_Investor_42', 'jp_inv_42', 'jp_inv_42@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV42'),
('JP_Investor_43', 'jp_inv_43', 'jp_inv_43@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV43'),
('JP_Investor_44', 'jp_inv_44', 'jp_inv_44@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV44'),
('JP_Investor_45', 'jp_inv_45', 'jp_inv_45@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV45'),
('JP_Investor_46', 'jp_inv_46', 'jp_inv_46@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV46'),
('JP_Investor_47', 'jp_inv_47', 'jp_inv_47@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV47'),
('JP_Investor_48', 'jp_inv_48', 'jp_inv_48@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV48'),
('JP_Investor_49', 'jp_inv_49', 'jp_inv_49@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV49'),
('JP_Investor_50', 'jp_inv_50', 'jp_inv_50@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV50'),
('JP_Investor_51', 'jp_inv_51', 'jp_inv_51@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV51'),
('JP_Investor_52', 'jp_inv_52', 'jp_inv_52@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV52'),
('JP_Investor_53', 'jp_inv_53', 'jp_inv_53@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV53'),
('JP_Investor_54', 'jp_inv_54', 'jp_inv_54@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV54'),
('JP_Investor_55', 'jp_inv_55', 'jp_inv_55@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV55'),
('JP_Investor_56', 'jp_inv_56', 'jp_inv_56@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV56'),
('JP_Investor_57', 'jp_inv_57', 'jp_inv_57@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV57'),
('JP_Investor_58', 'jp_inv_58', 'jp_inv_58@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV58'),
('JP_Investor_59', 'jp_inv_59', 'jp_inv_59@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV59'),
('JP_Investor_60', 'jp_inv_60', 'jp_inv_60@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV60'),
('JP_Investor_61', 'jp_inv_61', 'jp_inv_61@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV61'),
('JP_Investor_62', 'jp_inv_62', 'jp_inv_62@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV62'),
('JP_Investor_63', 'jp_inv_63', 'jp_inv_63@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV63'),
('JP_Investor_64', 'jp_inv_64', 'jp_inv_64@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV64'),
('JP_Investor_65', 'jp_inv_65', 'jp_inv_65@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV65'),
('JP_Investor_66', 'jp_inv_66', 'jp_inv_66@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV66'),
('JP_Investor_67', 'jp_inv_67', 'jp_inv_67@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV67'),
('JP_Investor_68', 'jp_inv_68', 'jp_inv_68@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV68'),
('JP_Investor_69', 'jp_inv_69', 'jp_inv_69@test.com', 'JP', 'investor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'JPINV69');

-- ?�원 관�??�정 (UPDATE �??�정 - ?�브쿼리 문제 ?�결)
-- ?�국: Admin ??Master
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_adm_01' SET u1.sponsor_id = u2.id WHERE u1.username = 'kr_mst_01';
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_adm_02' SET u1.sponsor_id = u2.id WHERE u1.username = 'kr_mst_02';
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_adm_03' SET u1.sponsor_id = u2.id WHERE u1.username = 'kr_mst_03';
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_adm_04' SET u1.sponsor_id = u2.id WHERE u1.username = 'kr_mst_04';
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_adm_05' SET u1.sponsor_id = u2.id WHERE u1.username = 'kr_mst_05';

-- ?�국: Master ??Agent
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_mst_01' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_agt_01', 'kr_agt_02', 'kr_agt_03', 'kr_agt_04');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_mst_02' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_agt_05', 'kr_agt_06', 'kr_agt_07', 'kr_agt_08');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_mst_03' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_agt_09', 'kr_agt_10', 'kr_agt_11', 'kr_agt_12');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_mst_04' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_agt_13', 'kr_agt_14', 'kr_agt_15', 'kr_agt_16');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_mst_05' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_agt_17', 'kr_agt_18', 'kr_agt_19', 'kr_agt_20');

-- ?�국: Agent ??Investor
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_01' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_01', 'kr_inv_02', 'kr_inv_03', 'kr_inv_04');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_02' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_05', 'kr_inv_06', 'kr_inv_07');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_03' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_08', 'kr_inv_09', 'kr_inv_10', 'kr_inv_11');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_04' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_12', 'kr_inv_13', 'kr_inv_14');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_05' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_15', 'kr_inv_16', 'kr_inv_17', 'kr_inv_18');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_06' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_19', 'kr_inv_20', 'kr_inv_21');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_07' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_22', 'kr_inv_23', 'kr_inv_24', 'kr_inv_25');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_08' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_26', 'kr_inv_27', 'kr_inv_28');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_09' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_29', 'kr_inv_30', 'kr_inv_31', 'kr_inv_32');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_10' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_33', 'kr_inv_34', 'kr_inv_35');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_11' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_36', 'kr_inv_37', 'kr_inv_38', 'kr_inv_39');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_12' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_40', 'kr_inv_41', 'kr_inv_42');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_13' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_43', 'kr_inv_44', 'kr_inv_45', 'kr_inv_46');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_14' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_47', 'kr_inv_48', 'kr_inv_49');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_15' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_50', 'kr_inv_51', 'kr_inv_52', 'kr_inv_53');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_16' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_54', 'kr_inv_55', 'kr_inv_56');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_17' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_57', 'kr_inv_58', 'kr_inv_59', 'kr_inv_60');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_18' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_61', 'kr_inv_62', 'kr_inv_63');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_19' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_64', 'kr_inv_65', 'kr_inv_66');
UPDATE users u1 JOIN users u2 ON u2.username = 'kr_agt_20' SET u1.sponsor_id = u2.id WHERE u1.username IN ('kr_inv_67', 'kr_inv_68', 'kr_inv_69');

-- ?�본: Admin ??Master
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_adm_01' SET u1.sponsor_id = u2.id WHERE u1.username = 'jp_mst_01';
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_adm_02' SET u1.sponsor_id = u2.id WHERE u1.username = 'jp_mst_02';
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_adm_03' SET u1.sponsor_id = u2.id WHERE u1.username = 'jp_mst_03';
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_adm_04' SET u1.sponsor_id = u2.id WHERE u1.username = 'jp_mst_04';
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_adm_05' SET u1.sponsor_id = u2.id WHERE u1.username = 'jp_mst_05';

-- ?�본: Master ??Agent
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_mst_01' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_agt_01', 'jp_agt_02', 'jp_agt_03', 'jp_agt_04');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_mst_02' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_agt_05', 'jp_agt_06', 'jp_agt_07', 'jp_agt_08');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_mst_03' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_agt_09', 'jp_agt_10', 'jp_agt_11', 'jp_agt_12');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_mst_04' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_agt_13', 'jp_agt_14', 'jp_agt_15', 'jp_agt_16');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_mst_05' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_agt_17', 'jp_agt_18', 'jp_agt_19', 'jp_agt_20');

-- ?�본: Agent ??Investor
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_01' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_01', 'jp_inv_02', 'jp_inv_03', 'jp_inv_04');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_02' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_05', 'jp_inv_06', 'jp_inv_07');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_03' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_08', 'jp_inv_09', 'jp_inv_10', 'jp_inv_11');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_04' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_12', 'jp_inv_13', 'jp_inv_14');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_05' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_15', 'jp_inv_16', 'jp_inv_17', 'jp_inv_18');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_06' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_19', 'jp_inv_20', 'jp_inv_21');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_07' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_22', 'jp_inv_23', 'jp_inv_24', 'jp_inv_25');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_08' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_26', 'jp_inv_27', 'jp_inv_28');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_09' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_29', 'jp_inv_30', 'jp_inv_31', 'jp_inv_32');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_10' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_33', 'jp_inv_34', 'jp_inv_35');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_11' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_36', 'jp_inv_37', 'jp_inv_38', 'jp_inv_39');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_12' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_40', 'jp_inv_41', 'jp_inv_42');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_13' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_43', 'jp_inv_44', 'jp_inv_45', 'jp_inv_46');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_14' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_47', 'jp_inv_48', 'jp_inv_49');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_15' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_50', 'jp_inv_51', 'jp_inv_52', 'jp_inv_53');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_16' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_54', 'jp_inv_55', 'jp_inv_56');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_17' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_57', 'jp_inv_58', 'jp_inv_59', 'jp_inv_60');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_18' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_61', 'jp_inv_62', 'jp_inv_63');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_19' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_64', 'jp_inv_65', 'jp_inv_66');
UPDATE users u1 JOIN users u2 ON u2.username = 'jp_agt_20' SET u1.sponsor_id = u2.id WHERE u1.username IN ('jp_inv_67', 'jp_inv_68', 'jp_inv_69');

-- Investor 입금 데이터 생성 (xm=1000, ultima=1000)
-- 한국 Investor 69명 입금 (user_transactions)
INSERT INTO user_transactions (user_id, tx_date, xm_value, ultima_value, pair, code_value, deposit_chk, created_at)
SELECT u.id, CURDATE(), 1000, 1000, 'XM/Ultima', CONCAT('CODE_', u.username), 1, NOW()
FROM users u
WHERE u.username LIKE 'kr_inv_%';

-- 일본 Investor 69명 입금 (user_transactions)
INSERT INTO user_transactions (user_id, tx_date, xm_value, ultima_value, pair, code_value, deposit_chk, created_at)
SELECT u.id, CURDATE(), 1000, 1000, 'XM/Ultima', CONCAT('CODE_', u.username), 1, NOW()
FROM users u
WHERE u.username LIKE 'jp_inv_%';

-- 한국 korea_progressing 등록 (입금 완료 상태)
INSERT INTO korea_progressing (user_id, tx_date, pair, deposit_status, withdrawal_status, profit_loss, created_at)
SELECT ut.user_id, ut.tx_date, 'xm,ultima', 2000, 0, -2000, NOW()
FROM user_transactions ut
JOIN users u ON u.id = ut.user_id
WHERE u.username LIKE 'kr_inv_%' AND ut.code_value LIKE 'CODE_kr_inv_%';

-- 일본 japan_progressing 등록 (입금 완료 상태)
INSERT INTO japan_progressing (user_id, tx_date, pair, deposit_status, withdrawal_status, profit_loss, created_at)
SELECT ut.user_id, ut.tx_date, 'xm,ultima', 2000, 0, -2000, NOW()
FROM user_transactions ut
JOIN users u ON u.id = ut.user_id
WHERE u.username LIKE 'jp_inv_%' AND ut.code_value LIKE 'CODE_jp_inv_%';

-- Investor 거래 모두 외부 진행 확인 완료 처리 (external_done_chk=1)
UPDATE user_transactions 
SET external_done_chk = 1, external_done_date = CURDATE()
WHERE user_id IN (SELECT id FROM users WHERE username LIKE 'kr_inv_%' OR username LIKE 'jp_inv_%');

-- korea_ready_trading 등록 (Ready 페이지에 표시되도록)
INSERT INTO korea_ready_trading (user_id, tx_date, tx_id, status, created_at)
SELECT ut.user_id, ut.tx_date, ut.id, 'ready', NOW()
FROM user_transactions ut
JOIN users u ON u.id = ut.user_id
WHERE u.username LIKE 'kr_inv_%' AND ut.code_value LIKE 'CODE_kr_inv_%';

-- japan_ready_trading 등록 (Ready 페이지에 표시되도록)
INSERT INTO japan_ready_trading (user_id, tx_date, tx_id, status, created_at)
SELECT ut.user_id, ut.tx_date, ut.id, 'ready', NOW()
FROM user_transactions ut
JOIN users u ON u.id = ut.user_id
WHERE u.username LIKE 'jp_inv_%' AND ut.code_value LIKE 'CODE_jp_inv_%';
