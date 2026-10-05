<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
require_once '../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/media.php');
    exit;
}

$leader_id = (int)($_POST['leader_id'] ?? 0);
if ($leader_id <= 0) {
    header('Location: ../views/media.php?error=Invalid leader ID');
    exit;
}

$role = trim($_POST['role_type'] ?? 'Other');
$name = trim($_POST['full_name'] ?? '');
$conference = trim($_POST['conference_name'] ?? '');
$startYear = (int)($_POST['start_year'] ?? 0) ?: null;
$endYear = (int)($_POST['end_year'] ?? 0) ?: null;
$descriptions = trim($_POST['descriptions'] ?? '');
$achievements = trim($_POST['achievements'] ?? '');
$removePhoto = isset($_POST['remove_photo']);

if ($name === '') {
    header('Location: ../views/media.php?error=Leader name is required');
    exit;
}

// Get current photo path
$stmt = $pdo->prepare("SELECT photo_path FROM legacy_leaders WHERE leader_id = ?");
$stmt->execute([$leader_id]);
$currentLeader = $stmt->fetch();
$currentPhotoPath = $currentLeader['photo_path'] ?? null;

$photoPath = $currentPhotoPath;

// Handle photo removal
if ($removePhoto && $currentPhotoPath) {
    $photoPath = null;
    // Delete the file
    $filePath = __DIR__ . '/../' . $currentPhotoPath;
    if (is_file($filePath)) {
        @unlink($filePath);
    }
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

    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $validFile['tmp_name']);
    finfo_close($finfo);
    $ext = $extMap[$mime] ?? 'jpg';
    $filename = 'leader_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    if (move_uploaded_file($validFile['tmp_name'], $destination)) {
        // Delete old photo file if it exists
        if ($currentPhotoPath) {
            $oldFilePath = __DIR__ . '/../' . $currentPhotoPath;
            if (is_file($oldFilePath)) {
                @unlink($oldFilePath);
            }
        }
        $photoPath = 'assets/uploads/leaders/' . $filename;
    }
}

try {
    $stmt = $pdo->prepare("UPDATE legacy_leaders SET role_type = ?, full_name = ?, conference_name = ?, start_year = ?, end_year = ?, descriptions = ?, achievements = ?, photo_path = ? WHERE leader_id = ?");
    $stmt->execute([$role, $name, $conference ?: null, $startYear, $endYear, $descriptions ?: null, $achievements ?: null, $photoPath, $leader_id]);
} catch (PDOException $e) {
    error_log("Legacy leader update failed: " . $e->getMessage());
    header('Location: ../views/media.php?error=' . urlencode('Database error while updating leader'));
    exit;
}

header('Location: ../views/media.php?leader_updated=1');
exit;