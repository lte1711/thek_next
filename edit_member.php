<?php
session_start();
$id = $_GET['id'] ?? ($_POST['id'] ?? null);
$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? 'member_list.php');
if ($id !== null && $id !== '' && ctype_digit((string)$id)) {
    $qs = "mode=edit&id=" . urlencode((string)$id);
    if ($redirect) $qs .= "&redirect=" . urlencode((string)$redirect);
    header("Location: create_account.php?$qs");
    exit;
}
// Fallback: if no id, go back
header("Location: " . ($redirect ?: "member_list.php"));
exit;
