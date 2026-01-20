SELECT '=== KOREA BEFORE TEST ===' AS info;
SELECT COUNT(*) AS ready_total FROM korea_ready_trading;
SELECT COUNT(*) AS ready_approved FROM korea_ready_trading WHERE status='approved';
SELECT COUNT(*) AS prog_total FROM korea_progressing;
SELECT 
  r.id AS ready_id, 
  r.user_id, 
  r.tx_id, 
  r.status,
  u.username,
  t.external_done_chk
FROM korea_ready_trading r
JOIN users u ON u.id = r.user_id
JOIN user_transactions t ON t.id = r.tx_id
WHERE r.status != 'rejected' 
  AND t.external_done_chk = 1
ORDER BY r.id DESC 
LIMIT 5;
