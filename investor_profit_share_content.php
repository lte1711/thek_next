<div class="form-container">

  <!-- ✅ 1) Profit Share Summary를 위로 올림 -->
  <h2>📊 Profit Share Summary (<span id="summaryDate"><?= htmlspecialchars($summary_label ?? ($latest_date ?? '')) ?></span>)</h2>

  <style>
    /* ✅ 복사 버튼이 화면 밖으로 안 나가게 */
    .wallet-wrap{
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
    }
    .wallet-wrap input{
      flex:1;
      min-width:220px;
      max-width:100%;
      box-sizing:border-box;
    }
    .wallet-wrap .copy-btn{
      white-space:nowrap;
    }
  </style>

  <table class="form-table" id="summaryTable">
    <tbody>
      <!-- ✅ Summary 금액: 000,000 형식(소수점 없이 콤마) -->
      <tr><th>Total Deposit</th><td id="sumDeposit"><?= number_format((float)$deposit, 2) ?> USDT</td></tr>
      <tr><th>Total Withdrawal</th><td id="sumWithdrawal"><?= number_format((float)$withdrawal, 2) ?> USDT</td></tr>
      <tr><th>Total Profit</th><td id="sumProfit"><?= number_format((float)$profit, 2) ?> USDT</td></tr>
      <tr><th>Profit Share (75%)</th><td id="sumShare75"><?= number_format((float)$share80, 2) ?> USDT</td></tr>
      <tr><th>My Profit (25%)</th><td><strong class="highlight" id="sumShare25"><?= number_format((float)$share20, 2) ?> USDT</strong></td></tr>

      <tr>
        <th>Wallet Address</th>
        <td>
          <div class="wallet-wrap">
            <input type="text" id="wallet_address" value="<?= htmlspecialchars($wallet ?? '') ?>" readonly>
            <button type="button" onclick="copyWallet()" class="copy-btn">📋 복사</button>
          </div>
        </td>
      </tr>
      <tr><th>오늘 날짜</th><td><?= date("Y-m-d") ?></td></tr>
    </tbody>
  </table>

  <!-- ✅ 정산 버튼 조건부 표시(그대로 유지) -->
  <div id="dividendBtnArea">
    <?php if (!empty($finance['dividend_chk'])): ?>
      <p><strong>✅ 이미 정산 완료되었습니다.</strong></p>
    <?php else: ?>
<form method="POST" action="investor_profit_share.php?user_id=<?= (int)($view_user_id ?? $user_id ?? 0) ?>">
        <input type="hidden" name="dividend" value="1">
        <input type="hidden" id="sel_tx_date" name="tx_date" value="<?= htmlspecialchars($latest_date ?? '') ?>">
        <input type="hidden" id="sel_code_value" name="code_value" value="<?= htmlspecialchars($latest_code ?? '') ?>">
        <button type="submit" class="btn btn-primary">✅ 정산하기</button>
      </form>
    <?php endif; ?>
  </div>

  <!-- ✅ A안: 기본은 전체 코드 노출 + 날짜 범위(일/주/월) 선택 -->
  <div style="margin-top:18px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
    <form method="GET" action="investor_profit_share.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
      <input type="hidden" name="user_id" value="<?= (int)($view_user_id ?? $user_id ?? 0) ?>">

      <div>
        <label style="display:block; font-size:12px; margin-bottom:4px;">기간</label>
        <select name="period" id="periodSel" onchange="togglePeriodInputs()">
          <option value="recent" <?= (($current_period ?? 'recent')==='recent') ? 'selected' : '' ?>>최근 10개</option>
          <option value="day" <?= (($current_period ?? '')==='day') ? 'selected' : '' ?>>일별</option>
          <option value="week" <?= (($current_period ?? '')==='week') ? 'selected' : '' ?>>주별</option>
          <option value="month" <?= (($current_period ?? '')==='month') ? 'selected' : '' ?>>월별</option>
        </select>
      </div>

      <div id="inpDayWrap" style="display:none;">
        <label style="display:block; font-size:12px; margin-bottom:4px;">날짜(일별)</label>
        <input type="date" name="day" value="<?= htmlspecialchars($current_day ?? '') ?>">
      </div>

      <div id="inpWeekWrap" style="display:none;">
        <label style="display:block; font-size:12px; margin-bottom:4px;">기준 날짜(주별)</label>
        <input type="date" name="week" value="<?= htmlspecialchars($current_week ?? '') ?>">
      </div>

      <div id="inpMonthWrap" style="display:none;">
        <label style="display:block; font-size:12px; margin-bottom:4px;">월(월별)</label>
        <input type="month" name="month" value="<?= htmlspecialchars($current_month ?? '') ?>">
      </div>

      <div>
        <label style="display:block; font-size:12px; margin-bottom:4px;">코드</label>
        <select name="code_value">
          <option value="">전체</option>
          <?php foreach (($filter_codes ?? []) as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= (!empty($current_code) && $current_code===$c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="btn btn-primary" style="height:36px;">적용</button>
    </form>
  </div>

  <!-- ✅ 2) 거래내역 테이블을 아래로 내림 -->
  <h2 style="margin-top:24px;">📦 거래 내역</h2>
  <table class="form-table">
    <thead>
      <tr>
        <th>날짜</th>
        <th>코드</th>
        <th>XM 입금</th>
        <th>Ultima 입금</th>
        <th>XM 출금</th>
        <th>Ultima 출금</th>
        <th>Profit</th>
        <th>Profit Share (75%)</th>
        <th>My Profit (25%)</th>
        <th>상태/선택</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($transactions_for_list ?? []) as $row):
        $depositRow    = ($row['xm_value'] ?? 0) + ($row['ultima_value'] ?? 0);
        $withdrawalRow = ($row['xm_total'] ?? 0) + ($row['ultima_total'] ?? 0);
        $profitRow     = $withdrawalRow - $depositRow;
        $share75Row    = $profitRow * 0.75;
        $share25Row    = $profitRow * 0.25;
      ?>
        <tr>
          <td><?= htmlspecialchars($row['tx_date'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['code_value'] ?? '') ?></td>
          <td><?= number_format($row['xm_value'], 2) ?></td>
          <td><?= number_format($row['ultima_value'], 2) ?></td>
          <td><?= number_format($row['xm_total'], 2) ?></td>
          <td><?= number_format($row['ultima_total'], 2) ?></td>
          <td><strong><?= number_format($profitRow, 2) ?></strong></td>
          <td><?= number_format($share75Row, 2) ?></td>
          <td><strong class="highlight"><?= number_format($share25Row, 2) ?></strong></td>
          <td>
            <?php if (!empty($row['dividend_chk'])): ?>
              <span class="check">정산 완료</span>
            <?php endif; ?>
            <button type="button" onclick="loadSummary('<?= $row['tx_date'] ?>','<?= $row['code_value'] ?>')">선택</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</div>

<script>
function copyWallet() {
  const walletInput = document.getElementById("wallet_address");
  walletInput.select();
  walletInput.setSelectionRange(0, 99999);
  document.execCommand("copy");
  alert("지갑 주소가 복사되었습니다.");
}

/* ✅ Summary 숫자: 000,000 형식 (소수점 없이) */
function fmt2(v){
  const n = Number(v);
  if (!isFinite(n)) return v;
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
}

// ✅ Summary + 버튼 + 숨은 입력 갱신
function loadSummary(tx_date, code_value) {
const uid = <?= (int)($view_user_id ?? $user_id ?? 0) ?>;
fetch("load_summary.php?user_id=" + uid + "&date=" + encodeURIComponent(tx_date) + "&code=" + encodeURIComponent(code_value))
    .then(response => response.json())
    .then(data => {
      if (data.error) { alert("❌ 오류: " + data.error); return; }

      // ✅ 콤마 형식으로 표시
      document.getElementById("sumDeposit").innerText    = fmt2(data.deposit) + " USDT";
      document.getElementById("sumWithdrawal").innerText = fmt2(data.withdrawal) + " USDT";
      document.getElementById("sumProfit").innerText     = fmt2(data.profit) + " USDT";
      document.getElementById("sumShare75").innerText    = fmt2(data.share75) + " USDT";
      document.getElementById("sumShare25").innerText    = fmt2(data.share25) + " USDT";

      // ✅ 선택된 tx_date를 Summary 제목에 반영
      document.getElementById("summaryDate").innerText = tx_date;

      // 버튼 표시 상태 갱신
      const btnArea = document.getElementById("dividendBtnArea");
      if (data.dividend_chk === 1) {
        btnArea.innerHTML = "<p><strong>✅ 이미 정산 완료되었습니다.</strong></p>";
      } else {
        btnArea.innerHTML = '<form method="POST" action="investor_profit_share.php" style="margin-top:20px;">' +
                            '<input type="hidden" name="dividend" value="1">' +
                            '<input type="hidden" id="sel_tx_date" name="tx_date" value="' + tx_date + '">' +
                            '<input type="hidden" id="sel_code_value" name="code_value" value="' + code_value + '">' +
                            '<button type="submit" class="btn btn-primary">✅ 정산하기</button>' +
                            '</form>';
      }

      // 폼 hidden 값 동기화
      const hDate = document.getElementById('sel_tx_date');
      const hCode = document.getElementById('sel_code_value');
      if (hDate) hDate.value = tx_date;
      if (hCode) hCode.value = code_value;
    })
    .catch(err => alert("❌ Summary 불러오기 오류: " + err));
}

function togglePeriodInputs(){
  const v = document.getElementById('periodSel')?.value || 'recent';
  document.getElementById('inpDayWrap').style.display   = (v==='day') ? 'block' : 'none';
  document.getElementById('inpWeekWrap').style.display  = (v==='week') ? 'block' : 'none';
  document.getElementById('inpMonthWrap').style.display = (v==='month') ? 'block' : 'none';
}

// 초기 렌더링
togglePeriodInputs();
</script>
