#!/bin/bash
# ========================================
# Country Ready OK/Reject 로그 확인 스크립트
# 서버에서 실행: bash check_ok_logs.sh
# ========================================

echo "=== CHECKING GIT DEPLOYMENT ==="
cd /var/www/html
echo "Current branch: $(git rev-parse --abbrev-ref HEAD)"
echo "Latest commit: $(git log -1 --oneline)"
git status --short

echo ""
echo "=== CHECKING ok_save.php FILE ==="
head -20 _branches/jp/ok_save.php | grep -E "error_log|HIT"

echo ""
echo "=== CHECKING reject_save.php FILE ==="
head -20 _branches/jp/reject_save.php | grep -E "error_log|HIT"

echo ""
echo "=== RECENT OK_SAVE LOGS (last 50 lines) ==="
sudo tail -n 200 /var/log/apache2/error.log 2>/dev/null | grep "OK_SAVE" | tail -n 50

echo ""
echo "=== RECENT REJECT_SAVE LOGS (last 50 lines) ==="
sudo tail -n 200 /var/log/apache2/error.log 2>/dev/null | grep "REJECT_SAVE" | tail -n 50

echo ""
echo "=== CHECKING PHP ERROR LOG (alternative location) ==="
if [ -f /var/log/php_errors.log ]; then
  sudo tail -n 100 /var/log/php_errors.log | grep -E "OK_SAVE|REJECT_SAVE" | tail -n 20
fi

echo ""
echo "=== APACHE ERROR LOG FILE LOCATION ==="
sudo ls -lh /var/log/apache2/error.log 2>/dev/null || echo "Apache error log not found at default location"

echo ""
echo "=== INSTRUCTIONS ==="
echo "1. Open browser DevTools > Network tab"
echo "2. Go to http://15.164.165.240/_branches/jp/country_ready.php?region=korea"
echo "3. Click OK button on a row"
echo "4. Check Network tab for 'ok_save.php' request"
echo "5. Re-run this script to see new logs"
