<?php
define('ALLOW_GUEST', true);
// Load error handler first
require_once '../includes/error_handler.php';
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: ../login.php?error=' . urlencode('Username and password are required'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role, conference_id, is_active, must_change_password FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || (int)$user['is_active'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
        logError("Failed login attempt for username: $username", "LOGIN");
        header('Location: ../login.php?error=' . urlencode('Invalid credentials'));
        exit;
    }
} catch (PDOException $e) {
    logDatabaseError("SELECT user_id, username, password_hash, role, conference_id, is_active FROM users WHERE username = ?", $e->getMessage(), [$username]);
    header('Location: ../login.php?error=' . urlencode('System error during login. Please try again.'));
    exit;
}

session_regenerate_id(true);
$_SESSION['auth_user'] = [
    'user_id' => (int)$user['user_id'],
    'username' => (string)$user['username'],
    'role' => (string)$user['role'],
    'conference_id' => $user['conference_id'] !== null ? (int)$user['conference_id'] : null,
];

// Seeded/default accounts must set a new password before proceeding (§1.7)
if (!empty($user['must_change_password'])) {
    $_SESSION['force_password_change'] = true;
    header('Location: ../forms/change_password.php?required=1');
    exit;
}

header('Location: ../index.php');
exit;
