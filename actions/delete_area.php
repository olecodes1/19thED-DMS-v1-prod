<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

if (!isset($_POST['id'])) { header("Location: ../views/areas.php"); exit; }

$id = (int)$_POST['id'];

// Get area's conference for authorization check
$stmt = $pdo->prepare("SELECT conference_id FROM areas WHERE area_id = ?");
$stmt->execute([$id]);
$conf_id = $stmt->fetchColumn();

if ($conf_id) {
    require_conference_admin($conf_id);
} else {
    require_role('superadmin');
}

try {
    $deletedId = soft_delete_row($pdo, 'areas', 'area_id', $id, '../views/areas.php');
    header("Location: ../views/areas.php?deleted=1&deleted_item_id=" . (int)$deletedId); exit;
} catch (PDOException $e) {
    error_log("Area delete failed: " . $e->getMessage());
    header("Location: ../views/areas.php?error=delete_failed"); exit;
}
