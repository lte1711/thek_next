<section class="content-area">
    <h2>거래 입력</h2>
    <form method="POST" action="investor_withdrawal.php">
        <div class="form-group">
            <label for="tx_date">거래일</label>
            <input type="date" id="tx_date" name="tx_date" 
                   value="<?= htmlspecialchars($selected_date) ?>" required>
        </div>
        <div class="form-group">
            <label for="xm_value">XM Value</label>
            <input type="number" step="0.01" min="0" id="xm_value" name="xm_value" required>
        </div>
        <div class="form-group">
            <label for="ultima_value">Ultima Value</label>
            <input type="number" step="0.01" min="0" id="ultima_value" name="ultima_value" required>
        </div>
        <button type="submit">거래 저장</button>
    </form>
</section>

<style>
    .content-area {
        max-width: 400px;
        margin: 0 auto;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .form-group { margin-bottom: 15px; }
    label { display:block; margin-bottom:5px; font-weight:bold; }
    input { width:100%; padding:8px; }
    button { background:#4CAF50; color:#fff; padding:10px 15px; border:none; cursor:pointer; }
    button:hover { background:#45a049; }
</style>