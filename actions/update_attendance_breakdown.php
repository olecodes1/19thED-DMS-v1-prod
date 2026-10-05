<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/event_attendance.php");
    exit;
}

$breakdown_id = (int)($_POST['breakdown_id'] ?? 0);
if ($breakdown_id <= 0) {
    header("Location: ../views/event_attendance.php?error=Invalid+breakdown+ID");
    exit;
}

$event_id = (int)($_POST['event_id'] ?? 0);
$conference_id = !empty($_POST['conference_id']) ? (int)$_POST['conference_id'] : null;
$area_id = !empty($_POST['area_id']) ? (int)$_POST['area_id'] : null;
$church_id = !empty($_POST['church_id']) ? (int)$_POST['church_id'] : null;
$attendance_count = max(0, (int)($_POST['attendance_count'] ?? 0));
$notes = trim($_POST['notes'] ?? '');

if ($event_id <= 0) {
    header("Location: ../views/event_attendance.php?error=Event+is+required");
    exit;
}

$conferenceIdResolved = $conference_id;
$areaIdResolved = $area_id;
$churchIdResolved = $church_id;

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

try {
    $stmt = $pdo->prepare("
        UPDATE event_attendance_breakdowns 
        SET event_id = ?, conference_id = ?, area_id = ?, church_id = ?, attendance_count = ?, notes = ?
        WHERE breakdown_id = ?
    ");
    $stmt->execute([$event_id, $conferenceIdResolved, $areaIdResolved, $churchIdResolved, $attendance_count, $notes ?: null, $breakdown_id]);
    
    header("Location: ../views/event_attendance.php?saved=1");
    exit;
} catch (PDOException $e) {
    header("Location: ../views/event_attendance.php?error=" . urlencode($e->getMessage()));
    exit;
}