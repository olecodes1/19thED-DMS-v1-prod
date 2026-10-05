<?php

/**
 * Session Configuration and Initialization
 * This file handles session setup and security
 * No database connection - pure session management
 */

// Load configuration
$config = require __DIR__ . '/../config.php';

// Session security configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', $config['security']['session_timeout']);

session_set_cookie_params([
    'lifetime' => $config['security']['session_timeout'],
    'path' => '/',
    'domain' => '',
    // Set $config['app']['secure_cookies'] to true once the site is served over HTTPS.
    'secure' => !empty($config['app']['secure_cookies']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Enforce idle timeout: expire sessions that have been inactive too long.
// session_set_cookie_params(lifetime) alone does not destroy server-side state.
$lastActivity = $_SESSION['last_activity'] ?? null;
if ($lastActivity !== null && (time() - (int)$lastActivity) > (int)$config['security']['session_timeout']) {
    $_SESSION = [];
    session_destroy();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}
$_SESSION['last_activity'] = time();
