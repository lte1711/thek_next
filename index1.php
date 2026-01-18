<?php
session_start();


// index.php
// THEK-NEXT.COM 메인 포털
// 요구사항: 버튼은 해당 페이지로 링크되지만, "연결주소" 텍스트(URL)는 화면에 표시되지 않음.
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>THEK-NEXT.COM</title>
  <style>

    :root {
      --bg: #0f172a;
      --card: #111827;
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --text: #e5e7eb;
      --muted: #94a3b8;
      --accent: #38bdf8;
    }
    * { box-sizing: border-box; }
.header {
      padding: 28px 28px 8px 28px;
      text-align: center;
    }
    .title {
      font-weight: 700;
      font-size: 28px;
      letter-spacing: 0.6px;
      color: #ffffff;
    }
    .subtitle {
      margin-top: 6px;
      font-size: 13px;
      color: var(--muted);
    }
    .grid {
      display: grid;
      gap: 14px;
      padding: 20px;
    }
    .row {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 12px;
    }
    .row.regions {
      grid-template-columns: repeat(4, 1fr);
    }

    /* 버튼 (링크 텍스트/URL은 화면에 표시하지 않고, 버튼으로만 보이도록) */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 48px;
      padding: 0 18px;
      border-radius: 12px;
      background: linear-gradient(180deg, #1f2937, #111827);
      color: #eaf1ff;
      text-decoration: none;   /* 링크 밑줄 제거 */
      font-weight: 600;
      font-size: 15px;
      letter-spacing: 0.2px;
      border: 1px solid rgba(255,255,255,0.08);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
      transition: transform 0.05s ease, background 0.2s ease, box-shadow 0.2s ease;
      position: relative;
      overflow: hidden;
    }
    .btn:hover {
      background: linear-gradient(180deg, #263244, #162033);
      box-shadow: 0 6px 18px rgba(31, 97, 255, 0.18);
    }
    .btn:active {
      transform: translateY(1px);
    }

    /* 포커스 표시 (접근성) */
    .btn:focus {
      outline: 2px solid var(--accent);
      outline-offset: 2px;
    }

    /* GM/ADMIN 등 역할 버튼 강조 */
    .btn.role {
      background: linear-gradient(180deg, #1f3a6d, #182b53);
      border-color: rgba(56, 189, 248, 0.25);
    }
    .btn.role:hover {
      background: linear-gradient(180deg, #234176, #1a325d);
      box-shadow: 0 6px 18px rgba(56, 189, 248, 0.25);
    }

    /* 푸터 */
    .footer {
      padding: 16px 20px 22px 20px;
      text-align: center;
      font-size: 12px;
      color: var(--muted);
      border-top: 1px solid rgba(255,255,255,0.08);
    }

    /* 반응형 */
    @media (max-width: 860px) {
      .row { grid-template-columns: repeat(3, 1fr); }
      .row.regions { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 520px) {
      .row { grid-template-columns: repeat(2, 1fr); }
      .row.regions { grid-template-columns: repeat(2, 1fr); }
      .title { font-size: 22px; }
    }
  
</style>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
  <main class="container" role="main">
    <header class="header">
      <div class="title">THEK-NEXT.COM</div>
      <div class="subtitle">포털 홈 (index.php)</div>
    </header>

    <section class="grid" aria-label="사용자 진입 및 대시보드">
      <!-- 상단: 회원 관련 -->
      <div class="row" aria-label="회원 섹션">
        <a class="btn" href="register.php">회원가입</a>
        <a class="btn" href="register_details.php">회원세부입력</a>
        <a class="btn" href="login.php">로그인</a>
        <a class="btn role" href="gm_dashboard.php">GM-대시보드</a>
        <!-- 빈칸 균형 조정 (옵션) -->
        <!-- <span></span> -->
      </div>

      <!-- 중단: 역할 대시보드 -->
      <div class="row" aria-label="역할 대시보드">
        <a class="btn role" href="admin_dashboard.php">ADMIN-대시보드</a>
        <a class="btn role" href="master_dashboard.php">MASTER-대시보드</a>
        <a class="btn role" href="agent_dashboard.php">AGENT-대시보드</a>
        <a class="btn role" href="investor_dashboard.php">INVESTOR-대시보드</a>
        <!-- 균형용 빈칸 -->
        <!-- <span></span> -->
      </div>

      <!-- 하단: 지역 -->
      <div class="row regions" aria-label="지역 접속">
        <a class="btn" href="country.php?region=korea">KOREA</a>
      </div>
    </section>

    <footer class="footer">
      © THEK-NEXT.COM. 모든 권리 보유.
    </footer>
  </main>
</body>
</html>