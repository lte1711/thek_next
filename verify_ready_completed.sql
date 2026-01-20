-- ========================================
-- Ready → Completed 이동 검증 SQL
-- ========================================

-- 1) 현재 Ready 페이지에 표시되는 데이터 (status='ready'만)
SELECT '=== READY PAGE (should be status=ready only) ===' AS info;
SELECT 
  r.id AS ready_id,
  r.user_id,
  r.tx_id,
  r.status,
  u.username,
  t.external_done_chk
FROM korea_ready_trading r
JOIN users u ON u.id = r.user_id
LEFT JOIN user_transactions t ON t.id = r.tx_id
WHERE (r.status IS NULL OR r.status = 'ready')
  AND (COALESCE(t.withdrawal_chk,0) = 0 AND COALESCE(t.settle_chk,0) <> 2)
ORDER BY r.id DESC
LIMIT 10;

-- 2) approved/rejected 상태 (Ready에서 보이면 안됨)
SELECT '=== SHOULD NOT APPEAR ON READY ===' AS info;
SELECT 
  r.id AS ready_id,
  r.user_id,
  r.tx_id,
  r.status,
  u.username
FROM korea_ready_trading r
JOIN users u ON u.id = r.user_id
WHERE r.status IN ('approved','rejected')
ORDER BY r.id DESC
LIMIT 5;

-- 3) Completed 페이지에 표시되는 데이터
SELECT '=== COMPLETED PAGE (approved/rejected/settle_chk=2) ===' AS info;
SELECT 
  t.id AS tx_id,
  t.user_id,
  r.id AS ready_id,
  r.status,
  u.username,
  t.settle_chk
FROM user_transactions t
JOIN korea_ready_trading r ON r.tx_id = t.id
JOIN users u ON u.id = t.user_id
WHERE (
  r.status IN ('approved','rejected','rejecting')
  OR COALESCE(t.settle_chk,0) = 2
)
AND r.id IS NOT NULL
ORDER BY t.id DESC
LIMIT 10;
