<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();

if (!isset($_POST['id'])) {
    header("Location: ../views/event_attendance.php");
    exit;
}

$breakdown_id = (int)$_POST['id'];
if ($breakdown_id <= 0) {
    header("Location: ../views/event_attendance.php?error=Invalid+breakdown+ID");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM event_attendance_breakdowns WHERE breakdown_id = ?");
    $stmt->execute([$breakdown_id]);
    
    header("Location: ../views/event_attendance.php?deleted=1");
    exit;
} catch (PDOException $e) {
    header("Location: ../views/event_attendance.php?error=" . urlencode($e->getMessage()));
    exit;
}