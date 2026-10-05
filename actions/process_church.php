<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forms/add_church.php"); exit;
}

// Get conference ID for authorization check
$conf_id = (int)($_POST['conference_id'] ?? 0);
if ($conf_id > 0) {
    require_conference_admin($conf_id);
} else {
    require_role('superadmin');
}

$area_id   = (int)($_POST['area_id']       ?? 0);
$name      = trim($_POST['local_church_name']              ?? '');
$president = trim($_POST['local_church_president_name']    ?? '');
$director  = trim($_POST['local_church_director_name']     ?? '');
$status    = $_POST['status'] ?? 'active';

if (!$conf_id || !$area_id || !$name) {
    header("Location: ../forms/add_church.php?error=missing_fields"); exit;
}

$row = $pdo->prepare("SELECT district_id FROM conferences WHERE conference_id = ?");
$row->execute([$conf_id]);
$district_id = $row->fetchColumn();

try {
    $stmt = $pdo->prepare("INSERT INTO churches (district_id, conference_id, area_id, local_church_name, local_church_president_name, local_church_director_name, status) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$district_id, $conf_id, $area_id, $name, $president ?: null, $director ?: null, $status]);
    header("Location: ../views/churches.php?success=1"); exit;
} catch (PDOException $e) {
    error_log("Church insert failed: " . $e->getMessage());
    header("Location: ../forms/add_church.php?error=database_error"); exit;
}
