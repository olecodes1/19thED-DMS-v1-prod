<?php
/**
 * Upload (or replace) the PDF booklet attached to an event.
 * POST multipart/form-data: event_id (required), booklet (PDF file).
 * Validates MIME + size, stores under assets/uploads/event_booklets/
 * with a server-generated filename, and records the path in events.booklet_path.
 */
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

$config = require __DIR__ . '/../config.php';

$eventId = (int)($_POST['event_id'] ?? 0);
if (!$eventId) {
    header("Location: ../views/events.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forms/edit_event.php?id=$eventId"); exit;
}

$file = $_FILES['booklet'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../forms/edit_event.php?id=$eventId&error=booklet_upload"); exit;
}

$maxBytes = (int)($config['uploads']['max_doc_size'] ?? 26214400);
if ($file['size'] > $maxBytes) {
    header("Location: ../forms/edit_event.php?id=$eventId&error=booklet_too_large"); exit;
}

// Validate real MIME type — never trust the client-supplied name
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if ($mime !== 'application/pdf') {
    header("Location: ../forms/edit_event.php?id=$eventId&error=booklet_not_pdf"); exit;
}

$dir = $config['uploads']['event_booklet_dir'];
if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
    header("Location: ../forms/edit_event.php?id=$eventId&error=booklet_storage"); exit;
}

$safeName = 'event_' . $eventId . '_' . bin2hex(random_bytes(8)) . '.pdf';
$destination = $dir . '/' . $safeName;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    header("Location: ../forms/edit_event.php?id=$eventId&error=booklet_storage"); exit;
}

$relativePath = 'assets/uploads/event_booklets/' . $safeName;

try {
    // Remove the previous booklet file if we are replacing one
    $stmt = $pdo->prepare("SELECT booklet_path FROM events WHERE event_id = ?");
    $stmt->execute([$eventId]);
    $oldPath = $stmt->fetchColumn();
    if ($oldPath) {
        $oldAbsolute = __DIR__ . '/..' . '/' . $oldPath;
        if (is_file($oldAbsolute)) {
            @unlink($oldAbsolute);
        }
    }

    $stmt = $pdo->prepare("UPDATE events SET booklet_path = ? WHERE event_id = ?");
    $stmt->execute([$relativePath, $eventId]);
    header("Location: ../forms/edit_event.php?id=$eventId&success=booklet_uploaded"); exit;
} catch (PDOException $e) {
    error_log("Event booklet upload failed: " . $e->getMessage());
    // Don't leave an orphaned file behind if the DB update failed
    if (is_file($destination)) {
        @unlink($destination);
    }
    header("Location: ../forms/edit_event.php?id=$eventId&error=database_error"); exit;
}
