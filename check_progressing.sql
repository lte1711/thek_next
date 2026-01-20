SELECT '=== PROGRESSING ROW CHECK ===' AS info;
SELECT id, tx_id, user_id, tx_date, pair, deposit_status, withdrawal_status, notes
FROM korea_progressing 
WHERE user_id=2182 AND tx_date='2026-01-20'
ORDER BY id DESC 
LIMIT 5;

SELECT '=== CHECK tx_id COLUMN ===' AS info;
SHOW COLUMNS FROM korea_progressing LIKE 'tx_id';
