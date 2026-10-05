<?php

/**
 * Database Connection Test
 * Tests the new database connection structure
 */

// Load configuration
$config = require __DIR__ . '/config.php';

// Test database connection only
try {
    $pdo = require __DIR__ . '/includes/database.php';
    echo "✓ Database connection successful!\n";
    echo "✓ Connected to: " . $config['db']['name'] . "\n";
    echo "✓ Host: " . $config['db']['host'] . ":" . $config['db']['port'] . "\n";
} catch (Exception $e) {
    die("✗ Database connection failed: " . $e->getMessage());
}
