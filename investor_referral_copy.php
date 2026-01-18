<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'investor') {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

$referral = '';
$stmt = $conn->prepare("SELECT referral_code FROM users WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($referral);
    $stmt->fetch();
    $stmt->close();
}
$referral = $referral ?? '';
$referral_js = json_encode($referral, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>레퍼럴 복사</title>
  <style>
    body { margin:0; font-family: Arial, sans-serif; background:#0f172a; }
    .overlay { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; padding:16px; }
    .modal { width:min(520px, 95vw); background:#ffffff; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.35); padding:22px; }
    .title { font-size:18px; font-weight:700; margin:0 0 12px; }
    .code { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:10px; padding:14px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; word-break:break-all; }
    .msg { margin:12px 0 0; color:#334155; }
    .actions { margin-top:18px; display:flex; justify-content:flex-end; gap:10px; }
    .btn { border:0; border-radius:10px; padding:10px 14px; font-weight:700; cursor:pointer; }
    .btn-primary { background:#2563eb; color:#fff; }
  </style>
</head>
<body>
  <div class="overlay">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="t">
      <p class="title" id="t">레퍼럴 코드</p>
      <div class="code" id="ref"></div>
      <p class="msg">확인을 누르면 레퍼럴 코드가 클립보드에 저장되고, 투자자 대시보드로 돌아갑니다.</p>
      <p class="msg"><strong>복사되었습니다.</strong></p>
      <div class="actions">
        <button class="btn btn-primary" id="okBtn">확인</button>
      </div>
    </div>
  </div>

<script>
(function(){
  const code = <?php echo $referral_js; ?> || "";
  document.getElementById('ref').textContent = code || "(레퍼럴 코드 없음)";

  const go = () => { window.location.href = "investor_dashboard.php"; };

  document.getElementById('okBtn').addEventListener('click', async function(){
    try{
      if (code) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(code);
        } else {
          const ta = document.createElement('textarea');
          ta.value = code;
          ta.style.position='fixed'; ta.style.left='-9999px';
          document.body.appendChild(ta);
          ta.focus(); ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        }
      }
    } catch(e) {
      // ignore
    } finally {
      go();
    }
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') go();
  });
})();
</script>
</body>
</html>
