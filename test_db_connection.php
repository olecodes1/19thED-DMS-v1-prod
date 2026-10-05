<?php
// Test database connection
$config = require __DIR__ . '/config.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    echo "Database connection successful!\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "Users table exists.\n";
        
        // Check users
        $stmt = $pdo->query("SELECT user_id, username, role, is_active, must_change_password FROM users");
        $users = $stmt->fetchAll();
        
        echo "Found " . count($users) . " user(s):\n";
        foreach ($users as $user) {
            echo "- ID: {$user['user_id']}, Username: {$user['username']}, Role: {$user['role']}, Active: {$user['is_active']}, Must Change Password: {$user['must_change_password']}\n";
        }
    } else {
        echo "Users table does NOT exist.\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
