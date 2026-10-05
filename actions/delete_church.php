<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();
require_once '../includes/soft_delete.php';

if (!isset($_POST['id'])) { header("Location: ../views/churches.php"); exit; }

$id = (int)$_POST['id'];

// Get church's conference for authorization check
$stmt = $pdo->prepare("SELECT conference_id FROM churches WHERE church_id = ?");
$stmt->execute([$id]);
$conf_id = $stmt->fetchColumn();

if ($conf_id) {
    require_conference_admin($conf_id);
} else {
    require_role('superadmin');
}

try {
    $deletedId = soft_delete_row($pdo, 'churches', 'church_id', $id, '../views/churches.php');
    header("Location: ../views/churches.php?deleted=1&deleted_item_id=" . (int)$deletedId); exit;
} catch (PDOException $e) {
    error_log("Church delete failed: " . $e->getMessage());
    header("Location: ../views/churches.php?error=delete_failed"); exit;
}
