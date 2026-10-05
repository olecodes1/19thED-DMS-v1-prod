<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

$id = (int)($_POST['id'] ?? 0);
$requestedRedirect = $_POST['redirect'] ?? '';
$item = $id > 0 ? get_deleted_item($pdo, $id) : null;
$redirect = $requestedRedirect !== '' ? $requestedRedirect : (($item['source_path'] ?? '') ?: '../views/recycle_bin.php');

if ($id <= 0) {
    header('Location: ' . $redirect . '?error=invalid_restore_id');
    exit;
}

try {
    $ok = restore_deleted_row($pdo, $id);
    if ($ok) {
        header('Location: ' . $redirect . '?restored=1');
        exit;
    }
    header('Location: ' . $redirect . '?error=restore_failed');
    exit;
} catch (PDOException $e) {
    error_log('Restore failed: ' . $e->getMessage());
    header('Location: ' . $redirect . '?error=restore_failed');
    exit;
}
