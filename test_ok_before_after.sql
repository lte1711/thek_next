-- ========================================
-- Country Ready OK 버튼 테스트 전/후 비교 SQL
-- ========================================

-- 1) 테스트 전 실행: 현재 상태 기록
SELECT '=== KOREA BEFORE ===' AS stage;
SELECT COUNT(*) AS ready_total FROM korea_ready_trading;
SELECT COUNT(*) AS ready_approved FROM korea_ready_trading WHERE status='approved';
SELECT COUNT(*) AS prog_total FROM korea_progressing;
SELECT 
  r.id AS ready_id,
  r.user_id,
  r.tx_id,
  r.status,
  u.username,
  IFNULL(p.id, 'NO_PROG') AS prog_id
FROM korea_ready_trading r
JOIN users u ON u.id = r.user_id
LEFT JOIN korea_progressing p ON p.tx_id = r.tx_id
WHERE r.status != 'rejected'
ORDER BY r.id DESC
LIMIT 5;

-- 2) OK 버튼 클릭 후 실행: 변화 확인
SELECT '=== KOREA AFTER ===' AS stage;
SELECT COUNT(*) AS ready_total FROM korea_ready_trading;
SELECT COUNT(*) AS ready_approved FROM korea_ready_trading WHERE status='approved';
SELECT COUNT(*) AS prog_total FROM korea_progressing;
SELECT 
  r.id AS ready_id,
  r.user_id,
  r.tx_id,
  r.status,
  u.username,
  IFNULL(p.id, 'NO_PROG') AS prog_id
FROM korea_ready_trading r
JOIN users u ON u.id = r.user_id
LEFT JOIN korea_progressing p ON p.tx_id = r.tx_id
WHERE r.status != 'rejected'
ORDER BY r.id DESC
LIMIT 5;

-- 3) Japan도 동일
SELECT '=== JAPAN BEFORE ===' AS stage;
SELECT COUNT(*) AS ready_total FROM japan_ready_trading;
SELECT COUNT(*) AS ready_approved FROM japan_ready_trading WHERE status='approved';
SELECT COUNT(*) AS prog_total FROM japan_progressing;

-- 4) 최근 user_transactions 확인 (external_done_chk=1인 것들)
SELECT '=== RECENT TRANSACTIONS ===' AS stage;
SELECT 
  t.id AS tx_id,
  t.user_id,
  u.username,
  DATE(t.tx_date) AS tx_date,
  t.external_done_chk,
  t.settle_chk,
  IFNULL(kr.id, 'NO_READY') AS korea_ready_id,
  IFNULL(kp.id, 'NO_PROG') AS korea_prog_id
FROM user_transactions t
JOIN users u ON u.id = t.user_id
LEFT JOIN korea_ready_trading kr ON kr.tx_id = t.id
LEFT JOIN korea_progressing kp ON kp.tx_id = t.id
WHERE t.external_done_chk = 1
  AND t.settle_chk != 2
ORDER BY t.id DESC
LIMIT 10;
