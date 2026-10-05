<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_once '../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/media.php');
    exit;
}

$title = validate_string($_POST['title'] ?? '', 1, 255);
$type = validate_enum($_POST['media_type'] ?? '', ['image', 'video', 'audio']);
$category = validate_string($_POST['category'] ?? '', 0, 120);
$customCategory = validate_string($_POST['category_custom'] ?? '', 0, 120);

// Use custom category if selected
if ($category === 'custom' && !empty($customCategory)) {
    $category = $customCategory;
}

$tags = validate_string($_POST['tags'] ?? '', 0, 255);
$eventTag = validate_string($_POST['event_tag'] ?? '', 0, 120);
$personTag = validate_string($_POST['person_tag'] ?? '', 0, 120);
$mediaYear = validate_int($_POST['media_year'] ?? 0, 1800, (int)date('Y'));
$description = validate_string($_POST['description'] ?? '', 0, 65535);

if (!$title || !$type || !isset($_FILES['media_file'])) {
    header('Location: ../views/media.php?error=Invalid media submission');
    exit;
}

// Check for double extensions
if (has_double_extension($_FILES['media_file']['name'])) {
    header('Location: ../views/media.php?error=File with double extensions are not allowed');
    exit;
}

$allowedTypes = [
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'video' => ['mp4', 'webm', 'ogg', 'mov'],
    'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
];

$validFile = validate_file_upload($_FILES['media_file'], $allowedTypes[$type], 52428800); // 50MB
if (!$validFile) {
    header('Location: ../views/media.php?error=Invalid file upload');
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/media';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true)) {
        error_log("Failed to create upload directory: " . $uploadDir);
        header('Location: ../views/media.php?error=Could not create upload folder - check directory permissions');
        exit;
    }
}
if (!is_writable($uploadDir)) {
    error_log("Upload directory not writable: " . $uploadDir);
    header('Location: ../views/media.php?error=Upload folder not writable - check directory permissions');
    exit;
}

$fileName = generate_safe_filename($_FILES['media_file']['name']);
$target = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($validFile['tmp_name'], $target)) {
    header('Location: ../views/media.php?error=Upload failed');
    exit;
}

$relative = 'assets/uploads/media/' . $fileName;

$data = [
    'title' => $title,
    'media_type' => $type,
    'category' => $category ?: null,
    'tags' => $tags ?: null,
    'event_tag' => $eventTag ?: null,
    'person_tag' => $personTag ?: null,
    'media_year' => $mediaYear,
    'description' => $description ?: null,
    'file_path' => $relative,
];
$columns = array_keys($data);
$placeholders = array_map(fn($c) => ':' . $c, $columns);
$stmt = $pdo->prepare("INSERT INTO media_items (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")");
$stmt->execute($data);

header('Location: ../views/media.php?uploaded=1');
exit;
