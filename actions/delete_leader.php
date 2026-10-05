<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

$leader_id = (int)($_POST['id'] ?? 0);
if ($leader_id <= 0) {
    header('Location: ../views/media.php');
    exit;
}

$deleted = soft_delete_row($pdo, 'legacy_leaders', 'leader_id', $leader_id, '../views/media.php');
if ($deleted) {
    header('Location: ../views/media.php?leader_deleted=1&deleted_item_id=' . $leader_id);
} else {
    header('Location: ../views/media.php?error=Failed to delete leader');
}
exit;