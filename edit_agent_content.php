<?php
// edit_agent_content.php는 더 이상 사용되지 않습니다.
// edit_agent.php에서 create_account.php로 리다이렉트됩니다.
// 혹시 직접 접근했을 때를 대비해 안내 페이지를 출력합니다.
?>
<div style="max-width:720px;margin:40px auto;padding:20px;background:#fff;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,.08);text-align:center;">
  <h2 style="margin:0 0 12px;">수정 페이지가 변경되었습니다</h2>
  <p style="color:#555;line-height:1.6;margin:0 0 18px;">
    에이전트/회원 정보 수정은 공통 수정 화면(<b>create_account.php</b>)으로 통일되었습니다.<br>
    아래 버튼을 눌러 이동해주세요.
  </p>
  <a href="create_account.php?mode=edit&id=<?= (int)($_SESSION['user_id'] ?? 0) ?>"
     style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:8px;">
    공통 수정 화면으로 이동
  </a>
</div>
