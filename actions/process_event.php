<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forms/add_event.php"); exit;
}

$name        = trim($_POST['event_name']   ?? '');
$date        = $_POST['event_date']        ?: null;
$location    = trim($_POST['location']     ?? '');
$description = trim($_POST['description'] ?? '');
$conferenceId       = (int)($_POST['conference_id']        ?? 0) ?: null;
$episcopalDistrictId = (int)($_POST['episcopal_district_id'] ?? 0) ?: null;
$attendance = (int)($_POST['attendance_count'] ?? 0);

if (!$name || !$date) {
    header("Location: ../forms/add_event.php?error=missing_fields"); exit;
}

try {
    $data = [
        'event_name'          => $name,
        'event_date'          => $date,
        'location'            => $location ?: null,
        'description'         => $description ?: null,
        'conference_id'       => $conferenceId,
        'episcopal_district_id' => $episcopalDistrictId,
        'attendance_count'    => $attendance,
    ];
    
    $cols = array_keys($data);
    $placeholders = array_map(fn($c) => ':' . $c, $cols);
    $stmt = $pdo->prepare("INSERT INTO events (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")");
    $stmt->execute($data);
    header("Location: ../views/events.php?success=1"); exit;
} catch (PDOException $e) {
    error_log("Event insert failed: " . $e->getMessage());
    header("Location: ../forms/add_event.php?error=database_error"); exit;
}
