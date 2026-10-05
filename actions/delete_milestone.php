<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

$milestone_id = (int)($_POST['id'] ?? 0);
if ($milestone_id <= 0) {
    header('Location: ../views/media.php');
    exit;
}

$deleted = soft_delete_row($pdo, 'milestones', 'milestone_id', $milestone_id, '../views/media.php');
if ($deleted) {
    header('Location: ../views/media.php?milestone_deleted=1&deleted_item_id=' . $milestone_id);
} else {
    header('Location: ../views/media.php?error=Failed to delete milestone');
}
exit;