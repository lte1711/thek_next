<?php
/**
 * ✅ GM Dashboard Unified UI Stylesheet
 * 
 * 모든 대시보드 파일에서 공통으로 사용하는 GM형식 레이아웃 CSS
 * - 2열 그리드 (모바일 1열 자동)
 * - 카드 스타일 통일 (패딩/라운드/그림자)
 * - 차트 높이 고정 (260px)
 * 
 * 사용법:
 *   <?php include 'includes/gm_dashboard_ui.php'; ?>
 */
?>
<style>
  /* ✅ 컨테이너 (최대 너비 + 중앙 정렬) */
  .gm-wrap { 
    max-width: 1200px; 
    margin: 0 auto; 
    padding: 18px; 
  }

  /* ✅ 2열 그리드 (gap 16px, 모바일 1열 자동) */
  .gm-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items: start;
  }

  @media (max-width: 980px){
    .gm-grid { 
      grid-template-columns: 1fr; 
    }
  }

  /* ✅ 카드 스타일 (백색 배경, 라운드, 그림자, 패딩) */
  .gm-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    padding: 14px;
  }

  /* ✅ 카드 제목 (굵은 글씨, 중앙 정렬) */
  .gm-card .gm-card-title {
    font-size: 16px;
    font-weight: 800;
    margin: 0 0 10px;
    text-align: center;
  }

  /* ✅ 카드 부제목 (옅은 회색, 작은 글씨) */
  .gm-card-subtitle {
    font-size: 12px;
    color: #999;
    text-align: center;
    margin-bottom: 10px;
  }

  /* ✅ 차트 컨테이너 (고정 높이 260px + 위치 기준점) */
  .gm-chart-box {
    width: 100%;
    height: 260px;
    position: relative;
  }

  /* ✅ 차트 내부 canvas/svg 크기 조절 (Chart.js용) */
  .gm-chart-box canvas,
  .gm-chart-box svg {
    width: 100% !important;
    height: 100% !important;
  }
</style>
