<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_once '../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$role = trim($_POST['role_type'] ?? 'Other');
$name = trim($_POST['full_name'] ?? '');
$conference = trim($_POST['conference_name'] ?? '');
$startYear = (int)($_POST['start_year'] ?? 0) ?: null;
$endYear = (int)($_POST['end_year'] ?? 0) ?: null;
$descriptions = trim($_POST['descriptions'] ?? '');
$achievements = trim($_POST['achievements'] ?? '');
$photoPath = null;

if ($name === '') {
    header('Location: ../views/media.php?error=Leader name is required');
    exit;
}

// Handle photo upload — validated MIME, size cap, server-generated filename (§1.6)
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $validFile = validate_file_upload($_FILES['photo'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 52428800); // 50MB
    if (!$validFile) {
        header('Location: ../views/media.php?error=' . urlencode('Invalid photo: must be a JPG, PNG, GIF or WEBP image up to 50MB'));
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/leaders';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        header('Location: ../views/media.php?error=' . urlencode('Could not create upload folder'));
        exit;
    }
    if (!is_writable($uploadDir)) {
        header('Location: ../views/media.php?error=' . urlencode('Upload folder is not writable'));
        exit;
    }

    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $validFile['tmp_name']);
    finfo_close($finfo);
    $ext = $extMap[$mime] ?? 'jpg';
    $filename = 'leader_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (move_uploaded_file($validFile['tmp_name'], $destination)) {
        $photoPath = 'assets/uploads/leaders/' . $filename;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO legacy_leaders (role_type, full_name, conference_name, start_year, end_year, descriptions, achievements, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$role, $name, $conference ?: null, $startYear, $endYear, $descriptions ?: null, $achievements ?: null, $photoPath]);
} catch (PDOException $e) {
    error_log("Legacy leader insert failed: " . $e->getMessage());
    // Remove the just-uploaded file so no orphan is left behind
    if ($photoPath) {
        $uploaded = __DIR__ . '/../' . $photoPath;
        if (is_file($uploaded)) {
            @unlink($uploaded);
        }
    }
    header('Location: ../views/media.php?error=' . urlencode('Database error while saving leader'));
    exit;
}

header('Location: ../views/media.php?leader_added=1');
exit;
