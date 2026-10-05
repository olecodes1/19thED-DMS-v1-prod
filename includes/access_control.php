<?php

/**
 * Access Control Wrapper
 * Loads the central bootstrap (config, error handling, session, database).
 * Authentication should still be enforced with require_auth()/require_role().
 */

// Load everything through the single bootstrap (§6.1/§6.3)
$pdo = require __DIR__ . '/bootstrap.php';

// Check if this is being accessed directly
if (basename($_SERVER['PHP_SELF']) === 'access_control.php') {
    http_response_code(403);
    die('Access denied: access_control.php is a configuration file and cannot be accessed directly.');
}
