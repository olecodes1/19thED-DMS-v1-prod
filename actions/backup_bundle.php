<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/soft_delete.php';
require_superadmin();

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('ZipArchive extension is required for backup bundle.');
}

function csv_from_query(PDO $pdo, string $sql, array $params = []): string
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $fp = fopen('php://temp', 'r+');

    $headers = [];
    $columnCount = $stmt->columnCount();
    for ($i = 0; $i < $columnCount; $i++) {
        $meta = $stmt->getColumnMeta($i);
        $headers[] = (string)($meta['name'] ?? ('column_' . ($i + 1)));
    }
    if ($headers) {
        fputcsv($fp, $headers);
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($fp, $row);
    }

    rewind($fp);
    return stream_get_contents($fp) ?: '';
}

$tmpDir = sys_get_temp_dir();
audit_log('BACKUP_DOWNLOAD', 'Full database backup bundle downloaded');
$backupName = 'ed19_backup_' . date('Ymd_His') . '.zip';
$zipPath = $tmpDir . '/' . $backupName;
$zip = new ZipArchive();
$openResult = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($openResult !== true) {
    http_response_code(500);
    exit('Unable to create backup zip file.');
}

$datasets = [
    'members.csv' => "SELECT * FROM members",
    'churches.csv' => "SELECT * FROM churches",
    'areas.csv' => "SELECT * FROM areas",
    'events.csv' => "SELECT * FROM events",
    'conferences.csv' => "SELECT * FROM conferences",
    'media_metadata.csv' => "SELECT * FROM media_items",
    'legacy_leaders.csv' => "SELECT * FROM legacy_leaders",
    'milestones.csv' => "SELECT * FROM milestones",
    'story_pages.csv' => "SELECT * FROM story_pages",
    'event_attendance_breakdowns.csv' => "SELECT * FROM event_attendance_breakdowns",
    'import_jobs.csv' => "SELECT * FROM import_jobs",
    'import_review_queue.csv' => "SELECT * FROM import_review_queue",
    'deleted_items.csv' => "SELECT * FROM deleted_items",
];

$manifest = [
    'generated_at' => date('c'),
    'bundle_name' => $backupName,
    'datasets' => [],
];

foreach ($datasets as $fileName => $sql) {
    $zip->addFromString($fileName, csv_from_query($pdo, $sql));
    $countStmt = $pdo->query('SELECT COUNT(*) AS total FROM (' . $sql . ') dataset_count');
    $manifest['datasets'][$fileName] = (int)$countStmt->fetchColumn();
}

$zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $backupName . '"');
header('Content-Length: ' . filesize($zipPath));
readfile($zipPath);
@unlink($zipPath);
exit;
