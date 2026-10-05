<?php
/**
 * Public download endpoint for event booklet PDFs.
 * Serves only files registered in events.booklet_path, with a
 * Content-Disposition header so browsers download rather than render.
 */
define('ALLOW_GUEST', true);
require_once __DIR__ . '/../includes/access_control.php';

$eventId = (int)($_GET['id'] ?? 0);
if (!$eventId) {
    http_response_code(404);
    exit('Event not found.');
}

$stmt = $pdo->prepare("SELECT event_name, booklet_path FROM events WHERE event_id = ? AND booklet_path IS NOT NULL LIMIT 1");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    exit('No booklet available for this event.');
}

// Only registered paths under the booklet upload dir can be served
$allowedDir = realpath(__DIR__ . '/../assets/uploads/event_booklets');
$absolute   = realpath(__DIR__ . '/../' . $event['booklet_path']);
if (!$allowedDir || !$absolute || strpos($absolute, $allowedDir . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit('Booklet file not found.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9 _-]/', '', $event['event_name']) . ' - Booklet.pdf"');
header('Content-Length: ' . filesize($absolute));
header('Cache-Control: public, max-age=3600');
readfile($absolute);
exit;
