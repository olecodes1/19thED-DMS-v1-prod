<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

if (!isset($_POST['id'])) {
    header("Location: ../views/events.php"); exit;
}

$id = (int)$_POST['id'];

try {
    $deletedId = soft_delete_row($pdo, 'events', 'event_id', $id, '../views/events.php');
    header("Location: ../views/events.php?deleted=1&deleted_item_id=" . (int)$deletedId); exit;
} catch (PDOException $e) {
    error_log("Event delete failed: " . $e->getMessage());
    header("Location: ../views/events.php?error=delete_failed"); exit;
}
