<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

if (!isset($_POST['id'])) {
    header("Location: ../views/members.php"); exit;
}

$id = (int)$_POST['id'];

try {
    $deletedId = soft_delete_row($pdo, 'members', 'member_id', $id, '../views/members.php');
    header("Location: ../views/members.php?deleted=1&deleted_item_id=" . (int)$deletedId); exit;
} catch (PDOException $e) {
    error_log("Member delete failed: " . $e->getMessage());
    header("Location: ../views/members.php?error=delete_failed"); exit;
}
