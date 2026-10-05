<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: ../views/story_pages.php?error=invalid_story_id');
    exit;
}

try {
    $deletedId = soft_delete_row($pdo, 'story_pages', 'story_id', $id, '../views/story_pages.php');
    header('Location: ../views/story_pages.php?deleted=1&deleted_item_id=' . (int)$deletedId);
    exit;
} catch (PDOException $e) {
    error_log('Story page delete failed: ' . $e->getMessage());
    header('Location: ../views/story_pages.php?error=delete_failed');
    exit;
}
