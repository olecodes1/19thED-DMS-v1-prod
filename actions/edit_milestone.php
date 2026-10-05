<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/media.php');
    exit;
}

$milestone_id = (int)($_POST['milestone_id'] ?? 0);
if ($milestone_id <= 0) {
    header('Location: ../views/media.php?error=Invalid milestone ID');
    exit;
}

$title = trim($_POST['title'] ?? '');
$year = (int)($_POST['milestone_year'] ?? 0);
$descriptions = trim($_POST['descriptions'] ?? '');
$achievements = trim($_POST['achievements'] ?? '');

if ($title === '' || $year <= 0) {
    header('Location: ../views/media.php?error=Milestone title and year are required');
    exit;
}

$stmt = $pdo->prepare("UPDATE milestones SET title = ?, milestone_year = ?, descriptions = ?, achievements = ? WHERE milestone_id = ?");
$stmt->execute([$title, $year, $descriptions ?: null, $achievements ?: null, $milestone_id]);

header('Location: ../views/media.php?milestone_updated=1');
exit;