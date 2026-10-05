<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_once '../includes/soft_delete.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/members.php'); exit;
}

$ids = $_POST['member_ids'] ?? [];
$action = $_POST['action'] ?? '';
$component_value = $_POST['component_value'] ?? null;
$conference_value = $_POST['conference_value'] ?? null;
$area_value = $_POST['area_value'] ?? null;

if (!is_array($ids) || empty($ids)) {
    header('Location: ../views/members.php?bulk_error=1&msg=' . urlencode('No members selected')); exit;
}

// sanitize ids
$ids = array_map('intval', $ids);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
    $pdo->beginTransaction();
    $affected = 0;

    if ($action === 'update_component') {
        // allow clearing component by sending empty string
        $sql = "UPDATE members SET component = ? WHERE member_id IN ($placeholders)";
        $params = array_merge([ $component_value === '' ? null : $component_value ], $ids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
    } elseif ($action === 'update_conference') {
        $sql = "UPDATE members SET conference_id = ? WHERE member_id IN ($placeholders)";
        $params = array_merge([ $conference_value === '' ? null : (int)$conference_value ], $ids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
    } elseif ($action === 'update_area') {
        $sql = "UPDATE members SET area_id = ? WHERE member_id IN ($placeholders)";
        $params = array_merge([ $area_value === '' ? null : (int)$area_value ], $ids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
    } elseif ($action === 'delete') {
        // soft-delete each member via soft_delete_row
        $deleted = 0;
        foreach ($ids as $id) {
            $did = soft_delete_row($pdo, 'members', 'member_id', (int)$id, '../views/members.php');
            if ($did) $deleted++;
        }
        $affected = $deleted;
    }

    $pdo->commit();
    header('Location: ../views/members.php?bulk_success=1&affected=' . (int)$affected); exit;
} catch (Exception $e) {
    // include the SQL / exception message in redirect so the UI can show it (URL-encoded)
    $pdo->rollBack();
    $msg = substr($e->getMessage(), 0, 200);
    header('Location: ../views/members.php?bulk_error=1&msg=' . urlencode($msg)); exit;
}
