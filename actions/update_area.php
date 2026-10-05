<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/areas.php"); exit;
}

// Get conference ID for authorization check
$conf_id = (int)($_POST['conference_id'] ?? 0);
if ($conf_id > 0) {
    require_conference_admin($conf_id);
} else {
    require_role('superadmin');
}

$id        = (int)$_POST['area_id'];
$name      = trim($_POST['area_name']           ?? '');
$president = trim($_POST['area_president_name'] ?? '');
$director  = trim($_POST['area_director_name']  ?? '');

// Sync district_id from conference
$row = $pdo->prepare("SELECT district_id FROM conferences WHERE conference_id = ?");
$row->execute([$conf_id]);
$district_id = $row->fetchColumn();

try {
    $stmt = $pdo->prepare("UPDATE areas SET district_id=?, conference_id=?, area_name=?, area_president_name=?, area_director_name=? WHERE area_id=?");
    $stmt->execute([$district_id, $conf_id, $name ?: null, $president ?: null, $director ?: null, $id]);
    header("Location: ../views/areas.php?updated=1"); exit;
} catch (PDOException $e) {
    error_log("Area update failed: " . $e->getMessage());
    header("Location: ../forms/edit_area.php?id=$id&error=database_error"); exit;
}
