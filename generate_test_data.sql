-- 한국 사용자 100명 생성
-- gm: 1명, admin: 5명, master: 5명, agent: 20명, investor: 69명
-- sponsor_id는 나중에 UPDATE로 설정
INSERT INTO users (name, username, email, country, role, password_hash, referral_code) VALUES
-- GM (1명)
('KR_GM_01', 'kr_gm_01', 'kr_gm_01@test.com', 'KR', 'gm', '$2y$10$dummy', 'KRGM01'),
-- Admin (5명)
('KR_Admin_01', 'kr_adm_01', 'kr_adm_01@test.com', 'KR', 'admin', '$2y$10$dummy', 'KRAD01'),
('KR_Admin_02', 'kr_adm_02', 'kr_adm_02@test.com', 'KR', 'admin', '$2y$10$dummy', 'KRAD02'),
('KR_Admin_03', 'kr_adm_03', 'kr_adm_03@test.com', 'KR', 'admin', '$2y$10$dummy', 'KRAD03'),
('KR_Admin_04', 'kr_adm_04', 'kr_adm_04@test.com', 'KR', 'admin', '$2y$10$dummy', 'KRAD04'),
('KR_Admin_05', 'kr_adm_05', 'kr_adm_05@test.com', 'KR', 'admin', '$2y$10$dummy', 'KRAD05'),
-- Master (5명) - Admin 1명당 Master 1명
('KR_Master_01', 'kr_mst_01', 'kr_mst_01@test.com', 'KR', 'master', '$2y$10$dummy', 'KRMST01'),
('KR_Master_02', 'kr_mst_02', 'kr_mst_02@test.com', 'KR', 'master', '$2y$10$dummy', 'KRMST02'),
('KR_Master_03', 'kr_mst_03', 'kr_mst_03@test.com', 'KR', 'master', '$2y$10$dummy', 'KRMST03'),
('KR_Master_04', 'kr_mst_04', 'kr_mst_04@test.com', 'KR', 'master', '$2y$10$dummy', 'KRMST04'),
('KR_Master_05', 'kr_mst_05', 'kr_mst_05@test.com', 'KR', 'master', '$2y$10$dummy', 'KRMST05'),
-- Agent (20명) - Master 1명당 Agent 4명
('KR_Agent_01', 'kr_agt_01', 'kr_agt_01@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT01'),
('KR_Agent_02', 'kr_agt_02', 'kr_agt_02@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT02'),
('KR_Agent_03', 'kr_agt_03', 'kr_agt_03@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT03'),
('KR_Agent_04', 'kr_agt_04', 'kr_agt_04@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT04'),
('KR_Agent_05', 'kr_agt_05', 'kr_agt_05@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT05'),
('KR_Agent_06', 'kr_agt_06', 'kr_agt_06@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT06'),
('KR_Agent_07', 'kr_agt_07', 'kr_agt_07@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT07'),
('KR_Agent_08', 'kr_agt_08', 'kr_agt_08@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT08'),
('KR_Agent_09', 'kr_agt_09', 'kr_agt_09@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT09'),
('KR_Agent_10', 'kr_agt_10', 'kr_agt_10@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT10'),
('KR_Agent_11', 'kr_agt_11', 'kr_agt_11@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT11'),
('KR_Agent_12', 'kr_agt_12', 'kr_agt_12@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT12'),
('KR_Agent_13', 'kr_agt_13', 'kr_agt_13@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT13'),
('KR_Agent_14', 'kr_agt_14', 'kr_agt_14@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT14'),
('KR_Agent_15', 'kr_agt_15', 'kr_agt_15@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT15'),
('KR_Agent_16', 'kr_agt_16', 'kr_agt_16@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT16'),
('KR_Agent_17', 'kr_agt_17', 'kr_agt_17@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT17'),
('KR_Agent_18', 'kr_agt_18', 'kr_agt_18@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT18'),
('KR_Agent_19', 'kr_agt_19', 'kr_agt_19@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT19'),
('KR_Agent_20', 'kr_agt_20', 'kr_agt_20@test.com', 'KR', 'agent', '$2y$10$dummy', 'KRAGT20'),
-- Investor (69명) - Agent가 랜덤하게 후원
('KR_Investor_01', 'kr_inv_01', 'kr_inv_01@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV01'),
('KR_Investor_02', 'kr_inv_02', 'kr_inv_02@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV02'),
('KR_Investor_03', 'kr_inv_03', 'kr_inv_03@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV03'),
('KR_Investor_04', 'kr_inv_04', 'kr_inv_04@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV04'),
('KR_Investor_05', 'kr_inv_05', 'kr_inv_05@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV05'),
('KR_Investor_06', 'kr_inv_06', 'kr_inv_06@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV06'),
('KR_Investor_07', 'kr_inv_07', 'kr_inv_07@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV07'),
('KR_Investor_08', 'kr_inv_08', 'kr_inv_08@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV08'),
('KR_Investor_09', 'kr_inv_09', 'kr_inv_09@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV09'),
('KR_Investor_10', 'kr_inv_10', 'kr_inv_10@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV10'),
('KR_Investor_11', 'kr_inv_11', 'kr_inv_11@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV11'),
('KR_Investor_12', 'kr_inv_12', 'kr_inv_12@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV12'),
('KR_Investor_13', 'kr_inv_13', 'kr_inv_13@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV13'),
('KR_Investor_14', 'kr_inv_14', 'kr_inv_14@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV14'),
('KR_Investor_15', 'kr_inv_15', 'kr_inv_15@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV15'),
('KR_Investor_16', 'kr_inv_16', 'kr_inv_16@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV16'),
('KR_Investor_17', 'kr_inv_17', 'kr_inv_17@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV17'),
('KR_Investor_18', 'kr_inv_18', 'kr_inv_18@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV18'),
('KR_Investor_19', 'kr_inv_19', 'kr_inv_19@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV19'),
('KR_Investor_20', 'kr_inv_20', 'kr_inv_20@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV20'),
('KR_Investor_21', 'kr_inv_21', 'kr_inv_21@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV21'),
('KR_Investor_22', 'kr_inv_22', 'kr_inv_22@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV22'),
('KR_Investor_23', 'kr_inv_23', 'kr_inv_23@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV23'),
('KR_Investor_24', 'kr_inv_24', 'kr_inv_24@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV24'),
('KR_Investor_25', 'kr_inv_25', 'kr_inv_25@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV25'),
('KR_Investor_26', 'kr_inv_26', 'kr_inv_26@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV26'),
('KR_Investor_27', 'kr_inv_27', 'kr_inv_27@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV27'),
('KR_Investor_28', 'kr_inv_28', 'kr_inv_28@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV28'),
('KR_Investor_29', 'kr_inv_29', 'kr_inv_29@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV29'),
('KR_Investor_30', 'kr_inv_30', 'kr_inv_30@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV30'),
('KR_Investor_31', 'kr_inv_31', 'kr_inv_31@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV31'),
('KR_Investor_32', 'kr_inv_32', 'kr_inv_32@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV32'),
('KR_Investor_33', 'kr_inv_33', 'kr_inv_33@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV33'),
('KR_Investor_34', 'kr_inv_34', 'kr_inv_34@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV34'),
('KR_Investor_35', 'kr_inv_35', 'kr_inv_35@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV35'),
('KR_Investor_36', 'kr_inv_36', 'kr_inv_36@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV36'),
('KR_Investor_37', 'kr_inv_37', 'kr_inv_37@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV37'),
('KR_Investor_38', 'kr_inv_38', 'kr_inv_38@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV38'),
('KR_Investor_39', 'kr_inv_39', 'kr_inv_39@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV39'),
('KR_Investor_40', 'kr_inv_40', 'kr_inv_40@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV40'),
('KR_Investor_41', 'kr_inv_41', 'kr_inv_41@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV41'),
('KR_Investor_42', 'kr_inv_42', 'kr_inv_42@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV42'),
('KR_Investor_43', 'kr_inv_43', 'kr_inv_43@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV43'),
('KR_Investor_44', 'kr_inv_44', 'kr_inv_44@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV44'),
('KR_Investor_45', 'kr_inv_45', 'kr_inv_45@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV45'),
('KR_Investor_46', 'kr_inv_46', 'kr_inv_46@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV46'),
('KR_Investor_47', 'kr_inv_47', 'kr_inv_47@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV47'),
('KR_Investor_48', 'kr_inv_48', 'kr_inv_48@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV48'),
('KR_Investor_49', 'kr_inv_49', 'kr_inv_49@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV49'),
('KR_Investor_50', 'kr_inv_50', 'kr_inv_50@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV50'),
('KR_Investor_51', 'kr_inv_51', 'kr_inv_51@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV51'),
('KR_Investor_52', 'kr_inv_52', 'kr_inv_52@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV52'),
('KR_Investor_53', 'kr_inv_53', 'kr_inv_53@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV53'),
('KR_Investor_54', 'kr_inv_54', 'kr_inv_54@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV54'),
('KR_Investor_55', 'kr_inv_55', 'kr_inv_55@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV55'),
('KR_Investor_56', 'kr_inv_56', 'kr_inv_56@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV56'),
('KR_Investor_57', 'kr_inv_57', 'kr_inv_57@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV57'),
('KR_Investor_58', 'kr_inv_58', 'kr_inv_58@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV58'),
('KR_Investor_59', 'kr_inv_59', 'kr_inv_59@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV59'),
('KR_Investor_60', 'kr_inv_60', 'kr_inv_60@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV60'),
('KR_Investor_61', 'kr_inv_61', 'kr_inv_61@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV61'),
('KR_Investor_62', 'kr_inv_62', 'kr_inv_62@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV62'),
('KR_Investor_63', 'kr_inv_63', 'kr_inv_63@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV63'),
('KR_Investor_64', 'kr_inv_64', 'kr_inv_64@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV64'),
('KR_Investor_65', 'kr_inv_65', 'kr_inv_65@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV65'),
('KR_Investor_66', 'kr_inv_66', 'kr_inv_66@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV66'),
('KR_Investor_67', 'kr_inv_67', 'kr_inv_67@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV67'),
('KR_Investor_68', 'kr_inv_68', 'kr_inv_68@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV68'),
('KR_Investor_69', 'kr_inv_69', 'kr_inv_69@test.com', 'KR', 'investor', '$2y$10$dummy', 'KRINV69'),

-- 일본 사용자 100명 생성
-- gm: 1명, admin: 5명, master: 5명, agent: 20명, investor: 69명
-- GM (1명)
('JP_GM_01', 'jp_gm_01', 'jp_gm_01@test.com', 'JP', 'gm', '$2y$10$dummy', 'JPGM01'),
-- Admin (5명)
('JP_Admin_01', 'jp_adm_01', 'jp_adm_01@test.com', 'JP', 'admin', '$2y$10$dummy', 'JPAD01'),
('JP_Admin_02', 'jp_adm_02', 'jp_adm_02@test.com', 'JP', 'admin', '$2y$10$dummy', 'JPAD02'),
('JP_Admin_03', 'jp_adm_03', 'jp_adm_03@test.com', 'JP', 'admin', '$2y$10$dummy', 'JPAD03'),
('JP_Admin_04', 'jp_adm_04', 'jp_adm_04@test.com', 'JP', 'admin', '$2y$10$dummy', 'JPAD04'),
('JP_Admin_05', 'jp_adm_05', 'jp_adm_05@test.com', 'JP', 'admin', '$2y$10$dummy', 'JPAD05'),
-- Master (5명)
('JP_Master_01', 'jp_mst_01', 'jp_mst_01@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST01'),
('JP_Master_02', 'jp_mst_02', 'jp_mst_02@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST02'),
('JP_Master_03', 'jp_mst_03', 'jp_mst_03@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST03'),
('JP_Master_04', 'jp_mst_04', 'jp_mst_04@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST04'),
('JP_Master_05', 'jp_mst_05', 'jp_mst_05@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST05'),
-- Agent (20명)
('JP_Agent_01', 'jp_agt_01', 'jp_agt_01@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT01'),
('JP_Agent_02', 'jp_agt_02', 'jp_agt_02@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT02'),
('JP_Agent_03', 'jp_agt_03', 'jp_agt_03@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT03'),
('JP_Agent_04', 'jp_agt_04', 'jp_agt_04@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT04'),
('JP_Agent_05', 'jp_agt_05', 'jp_agt_05@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT05'),
('JP_Agent_06', 'jp_agt_06', 'jp_agt_06@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT06'),
('JP_Agent_07', 'jp_agt_07', 'jp_agt_07@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT07'),
('JP_Agent_08', 'jp_agt_08', 'jp_agt_08@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT08'),
('JP_Agent_09', 'jp_agt_09', 'jp_agt_09@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT09'),
('JP_Agent_10', 'jp_agt_10', 'jp_agt_10@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT10'),
('JP_Agent_11', 'jp_agt_11', 'jp_agt_11@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT11'),
('JP_Agent_12', 'jp_agt_12', 'jp_agt_12@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT12'),
('JP_Agent_13', 'jp_agt_13', 'jp_agt_13@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT13'),
('JP_Agent_14', 'jp_agt_14', 'jp_agt_14@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT14'),
('JP_Agent_15', 'jp_agt_15', 'jp_agt_15@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT15'),
('JP_Agent_16', 'jp_agt_16', 'jp_agt_16@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT16'),
('JP_Agent_17', 'jp_agt_17', 'jp_agt_17@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT17'),
('JP_Agent_18', 'jp_agt_18', 'jp_agt_18@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT18'),
('JP_Agent_19', 'jp_agt_19', 'jp_agt_19@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT19'),
('JP_Agent_20', 'jp_agt_20', 'jp_agt_20@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPAGT20'),
-- Investor (69명)
('JP_Investor_01', 'jp_inv_01', 'jp_inv_01@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI01'),
('JP_Investor_02', 'jp_inv_02', 'jp_inv_02@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI02'),
('JP_Investor_03', 'jp_inv_03', 'jp_inv_03@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI03'),
('JP_Investor_04', 'jp_inv_04', 'jp_inv_04@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI04'),
('JP_Investor_05', 'jp_inv_05', 'jp_inv_05@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI05'),
('JP_Investor_06', 'jp_inv_06', 'jp_inv_06@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI06'),
('JP_Investor_07', 'jp_inv_07', 'jp_inv_07@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI07'),
('JP_Investor_08', 'jp_inv_08', 'jp_inv_08@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI08'),
('JP_Investor_09', 'jp_inv_09', 'jp_inv_09@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI09'),
('JP_Investor_10', 'jp_inv_10', 'jp_inv_10@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI10'),
('JP_Investor_11', 'jp_inv_11', 'jp_inv_11@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI11'),
('JP_Investor_12', 'jp_inv_12', 'jp_inv_12@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI12'),
('JP_Investor_13', 'jp_inv_13', 'jp_inv_13@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI13'),
('JP_Investor_14', 'jp_inv_14', 'jp_inv_14@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI14'),
('JP_Investor_15', 'jp_inv_15', 'jp_inv_15@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI15'),
('JP_Investor_16', 'jp_inv_16', 'jp_inv_16@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI16'),
('JP_Investor_17', 'jp_inv_17', 'jp_inv_17@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI17'),
('JP_Investor_18', 'jp_inv_18', 'jp_inv_18@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI18'),
('JP_Investor_19', 'jp_inv_19', 'jp_inv_19@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI19'),
('JP_Investor_20', 'jp_inv_20', 'jp_inv_20@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI20'),
('JP_Investor_21', 'jp_inv_21', 'jp_inv_21@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI21'),
('JP_Investor_22', 'jp_inv_22', 'jp_inv_22@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI22'),
('JP_Investor_23', 'jp_inv_23', 'jp_inv_23@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI23'),
('JP_Investor_24', 'jp_inv_24', 'jp_inv_24@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI24'),
('JP_Investor_25', 'jp_inv_25', 'jp_inv_25@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI25'),
('JP_Investor_26', 'jp_inv_26', 'jp_inv_26@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI26'),
('JP_Investor_27', 'jp_inv_27', 'jp_inv_27@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI27'),
('JP_Investor_28', 'jp_inv_28', 'jp_inv_28@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI28'),
('JP_Investor_29', 'jp_inv_29', 'jp_inv_29@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI29'),
('JP_Investor_30', 'jp_inv_30', 'jp_inv_30@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI30'),
('JP_Investor_31', 'jp_inv_31', 'jp_inv_31@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI31'),
('JP_Investor_32', 'jp_inv_32', 'jp_inv_32@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI32'),
('JP_Investor_33', 'jp_inv_33', 'jp_inv_33@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI33'),
('JP_Investor_34', 'jp_inv_34', 'jp_inv_34@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI34'),
('JP_Investor_35', 'jp_inv_35', 'jp_inv_35@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI35'),
('JP_Investor_36', 'jp_inv_36', 'jp_inv_36@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI36'),
('JP_Investor_37', 'jp_inv_37', 'jp_inv_37@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI37'),
('JP_Investor_38', 'jp_inv_38', 'jp_inv_38@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI38'),
('JP_Investor_39', 'jp_inv_39', 'jp_inv_39@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI39'),
('JP_Investor_40', 'jp_inv_40', 'jp_inv_40@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI40'),
('JP_Investor_41', 'jp_inv_41', 'jp_inv_41@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI41'),
('JP_Investor_42', 'jp_inv_42', 'jp_inv_42@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI42'),
('JP_Investor_43', 'jp_inv_43', 'jp_inv_43@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI43'),
('JP_Investor_44', 'jp_inv_44', 'jp_inv_44@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI44'),
('JP_Investor_45', 'jp_inv_45', 'jp_inv_45@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI45'),
('JP_Investor_46', 'jp_inv_46', 'jp_inv_46@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI46'),
('JP_Investor_47', 'jp_inv_47', 'jp_inv_47@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI47'),
('JP_Investor_48', 'jp_inv_48', 'jp_inv_48@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI48'),
('JP_Investor_49', 'jp_inv_49', 'jp_inv_49@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI49'),
('JP_Investor_50', 'jp_inv_50', 'jp_inv_50@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI50'),
('JP_Investor_51', 'jp_inv_51', 'jp_inv_51@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI51'),
('JP_Investor_52', 'jp_inv_52', 'jp_inv_52@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI52'),
('JP_Investor_53', 'jp_inv_53', 'jp_inv_53@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI53'),
('JP_Investor_54', 'jp_inv_54', 'jp_inv_54@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI54'),
('JP_Investor_55', 'jp_inv_55', 'jp_inv_55@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI55'),
('JP_Investor_56', 'jp_inv_56', 'jp_inv_56@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI56'),
('JP_Investor_57', 'jp_inv_57', 'jp_inv_57@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI57'),
('JP_Investor_58', 'jp_inv_58', 'jp_inv_58@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI58'),
('JP_Investor_59', 'jp_inv_59', 'jp_inv_59@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI59'),
('JP_Investor_60', 'jp_inv_60', 'jp_inv_60@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI60'),
('JP_Investor_61', 'jp_inv_61', 'jp_inv_61@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI61'),
('JP_Investor_62', 'jp_inv_62', 'jp_inv_62@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI62'),
('JP_Investor_63', 'jp_inv_63', 'jp_inv_63@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI63'),
('JP_Investor_64', 'jp_inv_64', 'jp_inv_64@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI64'),
('JP_Investor_65', 'jp_inv_65', 'jp_inv_65@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI65'),
('JP_Investor_66', 'jp_inv_66', 'jp_inv_66@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI66'),
('JP_Investor_67', 'jp_inv_67', 'jp_inv_67@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI67'),
('JP_Investor_68', 'jp_inv_68', 'jp_inv_68@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI68'),
('JP_Investor_69', 'jp_inv_69', 'jp_inv_69@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI69'),
('JP_Investor_70', 'jp_inv_70', 'jp_inv_70@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPI70'),
-- Agents (20명)
('JP_Agent_01', 'jp_agt_01', 'jp_agt_01@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA01'),
('JP_Agent_02', 'jp_agt_02', 'jp_agt_02@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA02'),
('JP_Agent_03', 'jp_agt_03', 'jp_agt_03@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA03'),
('JP_Agent_04', 'jp_agt_04', 'jp_agt_04@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA04'),
('JP_Agent_05', 'jp_agt_05', 'jp_agt_05@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA05'),
('JP_Agent_06', 'jp_agt_06', 'jp_agt_06@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA06'),
('JP_Agent_07', 'jp_agt_07', 'jp_agt_07@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA07'),
('JP_Agent_08', 'jp_agt_08', 'jp_agt_08@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA08'),
('JP_Agent_09', 'jp_agt_09', 'jp_agt_09@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA09'),
('JP_Agent_10', 'jp_agt_10', 'jp_agt_10@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA10'),
('JP_Agent_11', 'jp_agt_11', 'jp_agt_11@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA11'),
('JP_Agent_12', 'jp_agt_12', 'jp_agt_12@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA12'),
('JP_Agent_13', 'jp_agt_13', 'jp_agt_13@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA13'),
('JP_Agent_14', 'jp_agt_14', 'jp_agt_14@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA14'),
('JP_Agent_15', 'jp_agt_15', 'jp_agt_15@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA15'),
('JP_Agent_16', 'jp_agt_16', 'jp_agt_16@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA16'),
('JP_Agent_17', 'jp_agt_17', 'jp_agt_17@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA17'),
('JP_Agent_18', 'jp_agt_18', 'jp_agt_18@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA18'),
('JP_Agent_19', 'jp_agt_19', 'jp_agt_19@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA19'),
('JP_Agent_20', 'jp_agt_20', 'jp_agt_20@test.com', 'JP', 'agent', '$2y$10$dummy', 'JPA20'),
-- Masters (5명)
('JP_Master_01', 'jp_mst_01', 'jp_mst_01@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST01'),
('JP_Master_02', 'jp_mst_02', 'jp_mst_02@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST02'),
('JP_Master_03', 'jp_mst_03', 'jp_mst_03@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST03'),
('JP_Master_04', 'jp_mst_04', 'jp_mst_04@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST04'),
('JP_Master_05', 'jp_mst_05', 'jp_mst_05@test.com', 'JP', 'master', '$2y$10$dummy', 'JPMST05'),
-- Investor (69명)
('JP_Investor_01', 'jp_inv_01', 'jp_inv_01@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV01'),
('JP_Investor_02', 'jp_inv_02', 'jp_inv_02@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV02'),
('JP_Investor_03', 'jp_inv_03', 'jp_inv_03@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV03'),
('JP_Investor_04', 'jp_inv_04', 'jp_inv_04@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV04'),
('JP_Investor_05', 'jp_inv_05', 'jp_inv_05@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV05'),
('JP_Investor_06', 'jp_inv_06', 'jp_inv_06@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV06'),
('JP_Investor_07', 'jp_inv_07', 'jp_inv_07@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV07'),
('JP_Investor_08', 'jp_inv_08', 'jp_inv_08@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV08'),
('JP_Investor_09', 'jp_inv_09', 'jp_inv_09@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV09'),
('JP_Investor_10', 'jp_inv_10', 'jp_inv_10@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV10'),
('JP_Investor_11', 'jp_inv_11', 'jp_inv_11@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV11'),
('JP_Investor_12', 'jp_inv_12', 'jp_inv_12@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV12'),
('JP_Investor_13', 'jp_inv_13', 'jp_inv_13@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV13'),
('JP_Investor_14', 'jp_inv_14', 'jp_inv_14@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV14'),
('JP_Investor_15', 'jp_inv_15', 'jp_inv_15@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV15'),
('JP_Investor_16', 'jp_inv_16', 'jp_inv_16@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV16'),
('JP_Investor_17', 'jp_inv_17', 'jp_inv_17@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV17'),
('JP_Investor_18', 'jp_inv_18', 'jp_inv_18@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV18'),
('JP_Investor_19', 'jp_inv_19', 'jp_inv_19@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV19'),
('JP_Investor_20', 'jp_inv_20', 'jp_inv_20@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV20'),
('JP_Investor_21', 'jp_inv_21', 'jp_inv_21@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV21'),
('JP_Investor_22', 'jp_inv_22', 'jp_inv_22@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV22'),
('JP_Investor_23', 'jp_inv_23', 'jp_inv_23@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV23'),
('JP_Investor_24', 'jp_inv_24', 'jp_inv_24@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV24'),
('JP_Investor_25', 'jp_inv_25', 'jp_inv_25@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV25'),
('JP_Investor_26', 'jp_inv_26', 'jp_inv_26@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV26'),
('JP_Investor_27', 'jp_inv_27', 'jp_inv_27@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV27'),
('JP_Investor_28', 'jp_inv_28', 'jp_inv_28@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV28'),
('JP_Investor_29', 'jp_inv_29', 'jp_inv_29@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV29'),
('JP_Investor_30', 'jp_inv_30', 'jp_inv_30@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV30'),
('JP_Investor_31', 'jp_inv_31', 'jp_inv_31@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV31'),
('JP_Investor_32', 'jp_inv_32', 'jp_inv_32@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV32'),
('JP_Investor_33', 'jp_inv_33', 'jp_inv_33@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV33'),
('JP_Investor_34', 'jp_inv_34', 'jp_inv_34@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV34'),
('JP_Investor_35', 'jp_inv_35', 'jp_inv_35@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV35'),
('JP_Investor_36', 'jp_inv_36', 'jp_inv_36@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV36'),
('JP_Investor_37', 'jp_inv_37', 'jp_inv_37@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV37'),
('JP_Investor_38', 'jp_inv_38', 'jp_inv_38@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV38'),
('JP_Investor_39', 'jp_inv_39', 'jp_inv_39@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV39'),
('JP_Investor_40', 'jp_inv_40', 'jp_inv_40@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV40'),
('JP_Investor_41', 'jp_inv_41', 'jp_inv_41@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV41'),
('JP_Investor_42', 'jp_inv_42', 'jp_inv_42@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV42'),
('JP_Investor_43', 'jp_inv_43', 'jp_inv_43@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV43'),
('JP_Investor_44', 'jp_inv_44', 'jp_inv_44@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV44'),
('JP_Investor_45', 'jp_inv_45', 'jp_inv_45@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV45'),
('JP_Investor_46', 'jp_inv_46', 'jp_inv_46@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV46'),
('JP_Investor_47', 'jp_inv_47', 'jp_inv_47@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV47'),
('JP_Investor_48', 'jp_inv_48', 'jp_inv_48@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV48'),
('JP_Investor_49', 'jp_inv_49', 'jp_inv_49@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV49'),
('JP_Investor_50', 'jp_inv_50', 'jp_inv_50@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV50'),
('JP_Investor_51', 'jp_inv_51', 'jp_inv_51@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV51'),
('JP_Investor_52', 'jp_inv_52', 'jp_inv_52@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV52'),
('JP_Investor_53', 'jp_inv_53', 'jp_inv_53@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV53'),
('JP_Investor_54', 'jp_inv_54', 'jp_inv_54@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV54'),
('JP_Investor_55', 'jp_inv_55', 'jp_inv_55@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV55'),
('JP_Investor_56', 'jp_inv_56', 'jp_inv_56@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV56'),
('JP_Investor_57', 'jp_inv_57', 'jp_inv_57@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV57'),
('JP_Investor_58', 'jp_inv_58', 'jp_inv_58@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV58'),
('JP_Investor_59', 'jp_inv_59', 'jp_inv_59@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV59'),
('JP_Investor_60', 'jp_inv_60', 'jp_inv_60@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV60'),
('JP_Investor_61', 'jp_inv_61', 'jp_inv_61@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV61'),
('JP_Investor_62', 'jp_inv_62', 'jp_inv_62@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV62'),
('JP_Investor_63', 'jp_inv_63', 'jp_inv_63@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV63'),
('JP_Investor_64', 'jp_inv_64', 'jp_inv_64@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV64'),
('JP_Investor_65', 'jp_inv_65', 'jp_inv_65@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV65'),
('JP_Investor_66', 'jp_inv_66', 'jp_inv_66@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV66'),
('JP_Investor_67', 'jp_inv_67', 'jp_inv_67@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV67'),
('JP_Investor_68', 'jp_inv_68', 'jp_inv_68@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV68'),
('JP_Investor_69', 'jp_inv_69', 'jp_inv_69@test.com', 'JP', 'investor', '$2y$10$dummy', 'JPINV69');

-- 후원 관계 설정
-- 한국: Admin(1-5) → Master(1-5) 1:1 매칭
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_adm_01') WHERE username = 'kr_mst_01';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_adm_02') WHERE username = 'kr_mst_02';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_adm_03') WHERE username = 'kr_mst_03';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_adm_04') WHERE username = 'kr_mst_04';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_adm_05') WHERE username = 'kr_mst_05';

-- 한국: Master(1-5) → Agent(1-20) 1:4 매칭
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_mst_01') WHERE username IN ('kr_agt_01', 'kr_agt_02', 'kr_agt_03', 'kr_agt_04');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_mst_02') WHERE username IN ('kr_agt_05', 'kr_agt_06', 'kr_agt_07', 'kr_agt_08');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_mst_03') WHERE username IN ('kr_agt_09', 'kr_agt_10', 'kr_agt_11', 'kr_agt_12');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_mst_04') WHERE username IN ('kr_agt_13', 'kr_agt_14', 'kr_agt_15', 'kr_agt_16');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_mst_05') WHERE username IN ('kr_agt_17', 'kr_agt_18', 'kr_agt_19', 'kr_agt_20');

-- 한국: Agent(1-20) → Investor(1-69) 랜덤 매칭 (각 agent당 3-4명씩)
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_01') WHERE username IN ('kr_inv_01', 'kr_inv_02', 'kr_inv_03', 'kr_inv_04');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_02') WHERE username IN ('kr_inv_05', 'kr_inv_06', 'kr_inv_07');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_03') WHERE username IN ('kr_inv_08', 'kr_inv_09', 'kr_inv_10', 'kr_inv_11');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_04') WHERE username IN ('kr_inv_12', 'kr_inv_13', 'kr_inv_14');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_05') WHERE username IN ('kr_inv_15', 'kr_inv_16', 'kr_inv_17', 'kr_inv_18');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_06') WHERE username IN ('kr_inv_19', 'kr_inv_20', 'kr_inv_21');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_07') WHERE username IN ('kr_inv_22', 'kr_inv_23', 'kr_inv_24', 'kr_inv_25');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_08') WHERE username IN ('kr_inv_26', 'kr_inv_27', 'kr_inv_28');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_09') WHERE username IN ('kr_inv_29', 'kr_inv_30', 'kr_inv_31', 'kr_inv_32');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_10') WHERE username IN ('kr_inv_33', 'kr_inv_34', 'kr_inv_35');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_11') WHERE username IN ('kr_inv_36', 'kr_inv_37', 'kr_inv_38', 'kr_inv_39');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_12') WHERE username IN ('kr_inv_40', 'kr_inv_41', 'kr_inv_42');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_13') WHERE username IN ('kr_inv_43', 'kr_inv_44', 'kr_inv_45', 'kr_inv_46');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_14') WHERE username IN ('kr_inv_47', 'kr_inv_48', 'kr_inv_49');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_15') WHERE username IN ('kr_inv_50', 'kr_inv_51', 'kr_inv_52', 'kr_inv_53');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_16') WHERE username IN ('kr_inv_54', 'kr_inv_55', 'kr_inv_56');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_17') WHERE username IN ('kr_inv_57', 'kr_inv_58', 'kr_inv_59', 'kr_inv_60');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_18') WHERE username IN ('kr_inv_61', 'kr_inv_62', 'kr_inv_63');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_19') WHERE username IN ('kr_inv_64', 'kr_inv_65', 'kr_inv_66');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'kr_agt_20') WHERE username IN ('kr_inv_67', 'kr_inv_68', 'kr_inv_69');

-- 일본: Admin(1-5) → Master(1-5) 1:1 매칭
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_adm_01') WHERE username = 'jp_mst_01';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_adm_02') WHERE username = 'jp_mst_02';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_adm_03') WHERE username = 'jp_mst_03';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_adm_04') WHERE username = 'jp_mst_04';
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_adm_05') WHERE username = 'jp_mst_05';

-- 일본: Master(1-5) → Agent(1-20) 1:4 매칭
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_mst_01') WHERE username IN ('jp_agt_01', 'jp_agt_02', 'jp_agt_03', 'jp_agt_04');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_mst_02') WHERE username IN ('jp_agt_05', 'jp_agt_06', 'jp_agt_07', 'jp_agt_08');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_mst_03') WHERE username IN ('jp_agt_09', 'jp_agt_10', 'jp_agt_11', 'jp_agt_12');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_mst_04') WHERE username IN ('jp_agt_13', 'jp_agt_14', 'jp_agt_15', 'jp_agt_16');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_mst_05') WHERE username IN ('jp_agt_17', 'jp_agt_18', 'jp_agt_19', 'jp_agt_20');

-- 일본: Agent(1-20) → Investor(1-69) 랜덤 매칭 (각 agent당 3-4명씩)
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_01') WHERE username IN ('jp_inv_01', 'jp_inv_02', 'jp_inv_03', 'jp_inv_04');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_02') WHERE username IN ('jp_inv_05', 'jp_inv_06', 'jp_inv_07');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_03') WHERE username IN ('jp_inv_08', 'jp_inv_09', 'jp_inv_10', 'jp_inv_11');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_04') WHERE username IN ('jp_inv_12', 'jp_inv_13', 'jp_inv_14');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_05') WHERE username IN ('jp_inv_15', 'jp_inv_16', 'jp_inv_17', 'jp_inv_18');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_06') WHERE username IN ('jp_inv_19', 'jp_inv_20', 'jp_inv_21');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_07') WHERE username IN ('jp_inv_22', 'jp_inv_23', 'jp_inv_24', 'jp_inv_25');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_08') WHERE username IN ('jp_inv_26', 'jp_inv_27', 'jp_inv_28');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_09') WHERE username IN ('jp_inv_29', 'jp_inv_30', 'jp_inv_31', 'jp_inv_32');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_10') WHERE username IN ('jp_inv_33', 'jp_inv_34', 'jp_inv_35');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_11') WHERE username IN ('jp_inv_36', 'jp_inv_37', 'jp_inv_38', 'jp_inv_39');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_12') WHERE username IN ('jp_inv_40', 'jp_inv_41', 'jp_inv_42');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_13') WHERE username IN ('jp_inv_43', 'jp_inv_44', 'jp_inv_45', 'jp_inv_46');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_14') WHERE username IN ('jp_inv_47', 'jp_inv_48', 'jp_inv_49');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_15') WHERE username IN ('jp_inv_50', 'jp_inv_51', 'jp_inv_52', 'jp_inv_53');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_16') WHERE username IN ('jp_inv_54', 'jp_inv_55', 'jp_inv_56');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_17') WHERE username IN ('jp_inv_57', 'jp_inv_58', 'jp_inv_59', 'jp_inv_60');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_18') WHERE username IN ('jp_inv_61', 'jp_inv_62', 'jp_inv_63');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_19') WHERE username IN ('jp_inv_64', 'jp_inv_65', 'jp_inv_66');
UPDATE users SET sponsor_id = (SELECT id FROM users u2 WHERE u2.username = 'jp_agt_20') WHERE username IN ('jp_inv_67', 'jp_inv_68', 'jp_inv_69');
