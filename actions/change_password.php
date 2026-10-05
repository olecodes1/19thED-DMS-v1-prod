<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forms/change_password.php"); exit;
}

require_auth();
$user = current_auth_user();

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate current password
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$storedHash = $stmt->fetchColumn();

if (!password_verify($currentPassword, $storedHash)) {
    header("Location: ../forms/change_password.php?error=current_wrong"); exit;
}

// Validate new password
if (strlen($newPassword) < 8) {
    header("Location: ../forms/change_password.php?error=too_short"); exit;
}

if ($newPassword !== $confirmPassword) {
    header("Location: ../forms/change_password.php?error=mismatch"); exit;
}

// Update password and clear must_change_password flag
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?");
$stmt->execute([$newHash, $user['user_id']]);

// Clear the force password change session variable if it exists
if (isset($_SESSION['force_password_change'])) {
    unset($_SESSION['force_password_change']);
}

header("Location: ../index.php"); exit;