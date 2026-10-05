<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
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

$stmt = $pdo->prepare("INSERT INTO milestones (title, milestone_year, descriptions, achievements) VALUES (?, ?, ?, ?)");
$stmt->execute([$title, $year, $descriptions ?: null, $achievements ?: null]);
header('Location: ../views/media.php?milestone_added=1');
exit;

