<?php
session_start();

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 권한 체크: master만 (gm/admin도 필요하면 조건을 확장하세요)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'master') {
    // 권한 없으면 각 역할 메인으로 돌리기
    header("Location: index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 마스터 수정은 마스터 생성/수정과 동일하게 create_account.php의 edit 모드를 재사용
header("Location: create_account.php?mode=edit&id={$user_id}&redirect=master_dashboard.php");
exit;
?>
