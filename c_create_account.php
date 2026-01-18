<?php
// Agent -> Investor 생성 페이지 (기존 URL 유지: c_create_account.php)
// 기존 단독 INSERT 로직은 사용하지 않고, 공통 2-step 생성 폼(create_account.php)을 재사용합니다.
// - preset_role=investor 로 역할을 고정
// - create_account.php 내부 권한(Agent는 Investor만 생성)을 그대로 적용

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

/**
 * ✅ 문제 원인
 * - create_account.php는 1단계 입력값을 $_SESSION['create_account_step1']에 저장하고,
 *   다음 진입 시 해당 세션값을 prefill로 화면에 다시 채웁니다.
 * - 그래서 "투자자 등록" 메뉴로 들어왔을 때도 이전에 작성/수정하던 값이 남아 있으면
 *   신규 등록 페이지가 "수정처럼" 값이 채워진 상태로 보일 수 있습니다.
 *
 * ✅ 해결
 * - c_create_account.php는 "신규 투자자 등록 시작점"이므로,
 *   진입 시 이전 단계 세션을 정리해서 항상 빈 폼으로 시작하게 합니다.
 */
unset($_SESSION['create_account_step1']);
unset($_SESSION['edit_account_step1']);

// 역할 고정 (투자자 생성)
$_GET['preset_role'] = 'investor';

// 신규 생성 강제(혹시라도 mode/id가 붙어오는 경우 방지)
unset($_GET['mode'], $_GET['id']);
$_GET['step'] = 1;

require_once __DIR__ . '/create_account.php';
