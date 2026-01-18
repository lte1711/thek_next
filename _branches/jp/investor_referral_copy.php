<?php
session_start();

// Safe initialization
if (!function_exists('t')) {
    $i18n_path = __DIR__ . '/includes/i18n.php';
    if (file_exists($i18n_path)) {
        require_once $i18n_path;
    } else {
        function t($key, $fallback = null) {
            return $fallback ?? $key;
        }
        function current_lang() {
            return 'ko';
        }
    }
}

// Basic authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'investor') {
    header("Location: dashboard.php");
    exit;
}

// i18n (try both possible locations)
$i18n_loaded = false;
foreach ([__DIR__ . '/includes/i18n.php', __DIR__ . '/includes/i18n.php'] as $p) {
    if (is_file($p)) {
        require_once $p;
        $i18n_loaded = true;
        break;
    }
}

require_once __DIR__ . '/db_connect.php';

// Ensure lang is carried through this standalone page
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? null);
if (is_string($lang) && $lang !== '') {
    $_SESSION['lang'] = $lang;
}


// Referral Copy: language-safe fallback (prevents rollback if lang files are overwritten)
function __ref_t(string $key, string $ko, string $ja, string $en): string {
    $lang = function_exists('current_lang') ? current_lang() : ($_SESSION['lang'] ?? 'ko');
    $fallback = $ko;
    if ($lang === 'ja') $fallback = $ja;
    if ($lang === 'en') $fallback = $en;
    return function_exists('t') ? t($key, $fallback) : $fallback;
}

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

// Safe translation helper if i18n wasn't loaded for some reason
if (!function_exists('t')) {
    function t($key, $default = '') {
        return $default !== '' ? $default : $key;
    }
}

$page_lang_attr = htmlspecialchars($_SESSION['lang'] ?? 'ko');
$return_url = 'investor_dashboard.php' . (!empty($_SESSION['lang']) ? ('?lang=' . urlencode($_SESSION['lang'])) : '');
?>
<!DOCTYPE html>
<html lang="<?= $page_lang_attr ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars(__ref_t('title.investor.referral_copy','레퍼럴 복사','紹介コードコピー','Referral Copy')) ?></title>
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
      <p class="title" id="t"><?= htmlspecialchars(__ref_t('investor.referral_copy.modal_title','레퍼럴 코드','紹介コード','Referral Code')) ?></p>
      <div class="code" id="ref"></div>
      <p class="msg"><?= htmlspecialchars(__ref_t('investor.referral_copy.help','확인을 누르면 레퍼럴 코드가 클립보드에 저장되고, 투자자 대시보드로 돌아갑니다.','「確認」を押すと紹介コードがクリップボードに保存され、投資家ダッシュボードに戻ります。','Press Confirm to copy the referral code to the clipboard and return to the Investor Dashboard.')) ?></p>
      <p class="msg"><strong><?= htmlspecialchars(__ref_t('investor.referral_copy.copied','복사되었습니다.','コピーしました。','Copied.')) ?></strong></p>
      <div class="actions">
        <button class="btn btn-primary" id="okBtn"><?= t('common.confirm','Confirm') ?></button>
      </div>
    </div>
  </div>

<script>
(function(){
  const code = <?php echo $referral_js; ?> || "";
  const emptyLabel = <?= json_encode(__ref_t('investor.referral_copy.no_code','(레퍼럴 코드 없음)','（紹介コードなし）','(No referral code)'), JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById('ref').textContent = code || emptyLabel;

  const go = () => { window.location.href = <?= json_encode($return_url, JSON_UNESCAPED_UNICODE) ?>; };

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
