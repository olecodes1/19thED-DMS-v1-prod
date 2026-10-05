<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/event_attendance.php');
    exit;
}

$eventId = (int)($_POST['event_id'] ?? 0);
$conferenceId = (int)($_POST['conference_id'] ?? 0) ?: null;
$areaId = (int)($_POST['area_id'] ?? 0) ?: null;
$churchId = (int)($_POST['church_id'] ?? 0) ?: null;
$attendance = max(0, (int)($_POST['attendance_count'] ?? 0));
$notes = trim($_POST['notes'] ?? '');

if ($eventId <= 0) {
    header('Location: ../views/event_attendance.php?error=missing_event');
    exit;
}

$conferenceIdResolved = $conferenceId;
$areaIdResolved = $areaId;
$churchIdResolved = $churchId;

if ($conferenceIdResolved !== null) {
    $confCheck = $pdo->prepare("SELECT conference_id FROM conferences WHERE conference_id = ? LIMIT 1");
    $confCheck->execute([$conferenceIdResolved]);
    if (!$confCheck->fetchColumn()) {
        header('Location: ../views/event_attendance.php?error=invalid_conference');
        exit;
    }
}

if ($areaIdResolved !== null) {
    $areaCheck = $pdo->prepare("SELECT area_id, conference_id FROM areas WHERE area_id = ? LIMIT 1");
    $areaCheck->execute([$areaIdResolved]);
    $areaRow = $areaCheck->fetch(PDO::FETCH_ASSOC);
    if (!$areaRow) {
        header('Location: ../views/event_attendance.php?error=invalid_area');
        exit;
    }
    if ($conferenceIdResolved === null) {
        $conferenceIdResolved = (int)$areaRow['conference_id'];
    } elseif ((int)$areaRow['conference_id'] !== $conferenceIdResolved) {
        header('Location: ../views/event_attendance.php?error=area_not_in_conference');
        exit;
    }
}

if ($churchIdResolved !== null) {
    $churchCheck = $pdo->prepare("SELECT church_id, area_id, conference_id FROM churches WHERE church_id = ? LIMIT 1");
    $churchCheck->execute([$churchIdResolved]);
    $churchRow = $churchCheck->fetch(PDO::FETCH_ASSOC);
    if (!$churchRow) {
        header('Location: ../views/event_attendance.php?error=invalid_church');
        exit;
    }
    if ($conferenceIdResolved === null) {
        $conferenceIdResolved = (int)$churchRow['conference_id'];
    } elseif ((int)$churchRow['conference_id'] !== $conferenceIdResolved) {
        header('Location: ../views/event_attendance.php?error=church_not_in_conference');
        exit;
    }
    if ($areaIdResolved === null) {
        $areaIdResolved = (int)$churchRow['area_id'];
    } elseif ((int)$churchRow['area_id'] !== $areaIdResolved) {
        header('Location: ../views/event_attendance.php?error=church_not_in_area');
        exit;
    }
}

$stmt = $pdo->prepare("
    INSERT INTO event_attendance_breakdowns (event_id, conference_id, area_id, church_id, attendance_count, notes)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$eventId, $conferenceIdResolved, $areaIdResolved, $churchIdResolved, $attendance, $notes ?: null]);
header('Location: ../views/event_attendance.php?saved=1');
exit;
