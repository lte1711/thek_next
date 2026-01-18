<?php
session_start();

// 로그인 확인
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

$role = strtolower(trim((string)$_SESSION['role']));

// admin 전용(필요시 superadmin도 허용)
if ($role !== 'admin' && $role !== 'superadmin') {
    header('Location: login.php');
    exit;
}

$self_id = (int)$_SESSION['user_id'];

// ✅ "마스터 생성"과 동일한 2-step(create_account.php) 수정 화면으로 연결
// - 본인 계정만 수정
// - redirect는 어드민 메인으로
$qs = http_build_query([
    'mode' => 'edit',
    'id' => $self_id,
    'redirect' => 'admin_dashboard.php',
]);

header('Location: create_account.php?' . $qs);
exit;
// ✅ "마스터 생성"과 동일한 2-step(create_account.php) 화면을 본인 수정에 재사용
// - sponsor(상위) 노출 여부, 권한, 기존 값 prefill 등은 create_account.php가 처리
// - redirect 파라미터로 저장 후 돌아갈 페이지를 지정
$redirect = 'admin_dashboard.php';

$qs = http_build_query([
    'mode' => 'edit',
    'id' => $self_id,
    'redirect' => $redirect,
]);

header('Location: create_account.php?' . $qs);
exit;
