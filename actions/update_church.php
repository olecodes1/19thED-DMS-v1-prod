<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/churches.php"); exit;
}

// Get conference ID for authorization check
$conf_id = (int)($_POST['conference_id'] ?? 0);
if ($conf_id > 0) {
    require_conference_admin($conf_id);
} else {
    require_role('superadmin');
}

$id        = (int)$_POST['church_id'];
$area_id   = (int)($_POST['area_id']       ?? 0);
$name      = trim($_POST['local_church_name']           ?? '');
$president = trim($_POST['local_church_president_name'] ?? '');
$director  = trim($_POST['local_church_director_name']  ?? '');
$status    = $_POST['status'] ?? 'active';

$row = $pdo->prepare("SELECT district_id FROM conferences WHERE conference_id = ?");
$row->execute([$conf_id]);
$district_id = $row->fetchColumn();

try {
    $stmt = $pdo->prepare("UPDATE churches SET district_id=?, conference_id=?, area_id=?, local_church_name=?, local_church_president_name=?, local_church_director_name=?, status=? WHERE church_id=?");
    $stmt->execute([$district_id, $conf_id, $area_id, $name, $president ?: null, $director ?: null, $status, $id]);
    header("Location: ../views/churches.php?updated=1"); exit;
} catch (PDOException $e) {
    error_log("Church update failed: " . $e->getMessage());
    header("Location: ../forms/edit_church.php?id=$id&error=database_error"); exit;
}
