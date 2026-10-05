<?php

/**
 * Database Connection Wrapper
 * This file provides backward compatibility while using the new separated structure
 * For new code, use includes/bootstrap.php directly (§6.1/§6.3)
 */

// Load database connection (shared via the central bootstrap)
$pdo = require __DIR__ . '/includes/bootstrap.php';
