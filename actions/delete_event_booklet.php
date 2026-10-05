<?php
/**
 * Remove the PDF booklet attached to an event.
 * Deletes the physical file and clears events.booklet_path.
 */
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_post();

$eventId = (int)($_POST['id'] ?? 0);
if (!$eventId) {
    header("Location: ../views/events.php"); exit;
}

try {
    $stmt = $pdo->prepare("SELECT booklet_path FROM events WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $path = $stmt->fetchColumn();

    if ($path) {
        $absolute = __DIR__ . '/..' . '/' . $path;
        if (is_file($absolute)) {
            @unlink($absolute);
        }
        $stmt = $pdo->prepare("UPDATE events SET booklet_path = NULL WHERE event_id = ?");
        $stmt->execute([$eventId]);
    }

    header("Location: ../forms/edit_event.php?id=$eventId&success=booklet_removed"); exit;
} catch (PDOException $e) {
    error_log("Event booklet delete failed: " . $e->getMessage());
    header("Location: ../forms/edit_event.php?id=$eventId&error=delete_failed"); exit;
}
