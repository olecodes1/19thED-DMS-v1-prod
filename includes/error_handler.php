<?php

/**
 * Comprehensive Error Handling and Logging System
 * This file provides custom error handling, logging, and user-friendly error pages
 */

// Load configuration
$config = require __DIR__ . '/../config.php';

// Define error log file
$errorLogFile = __DIR__ . '/../logs/error.log';

// Ensure logs directory exists
if (!file_exists(dirname($errorLogFile))) {
    mkdir(dirname($errorLogFile), 0755, true);
}

/**
 * Custom error handler
 * Converts PHP errors to exceptions
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't handle suppressed errors (@ operator)
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/**
 * Custom exception handler
 * Catches unhandled exceptions and logs them
 */
set_exception_handler(function($exception) {
    global $errorLogFile, $config;
    
    // Log the error details
    $errorMessage = sprintf(
        "[%s] UNHANDLED EXCEPTION: %s in %s on line %d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    // Add request information
    $errorMessage .= sprintf(
        "Request: %s %s\nIP: %s\nUser Agent: %s\n",
        $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
        $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
    );
    
    file_put_contents($errorLogFile, $errorMessage . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
    
    // Show user-friendly error page
    showErrorPage(
        'Application Error',
        'An unexpected error occurred. The administrator has been notified.',
        $config['app']['debug'] ? $exception->getMessage() : null
    );
});

/**
 * Shutdown function to catch fatal errors
 */
register_shutdown_function(function() {
    global $errorLogFile, $config;
    
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        
        // Log the fatal error
        $errorMessage = sprintf(
            "[%s] FATAL ERROR: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );
        
        file_put_contents($errorLogFile, $errorMessage . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
        
        // Show user-friendly error page
        showErrorPage(
            'System Error',
            'A critical system error occurred. Please try again later.',
            $config['app']['debug'] ? $error['message'] : null
        );
    }
});

/**
 * Log custom errors
 */
function logError(string $message, string $context = 'GENERAL'): void
{
    global $errorLogFile;
    
    $logMessage = sprintf(
        "[%s] [%s] %s\nRequest: %s %s\nIP: %s\n",
        date('Y-m-d H:i:s'),
        $context,
        $message,
        $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
        $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
        $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
    );
    
    file_put_contents($errorLogFile, $logMessage . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}

/**
 * Log database errors
 */
function logDatabaseError(string $query, string $error, array $params = []): void
{
    global $errorLogFile;
    
    $logMessage = sprintf(
        "[%s] [DATABASE] Query failed: %s\nError: %s\nParams: %s\n",
        date('Y-m-d H:i:s'),
        $query,
        $error,
        json_encode($params)
    );
    
    file_put_contents($errorLogFile, $logMessage . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND);
}

/**
 * Show user-friendly error page
 */
function showErrorPage(string $title, string $message, ?string $debugInfo = null): void
{
    global $config;
    require_once __DIR__ . '/url_helper.php';
    
    http_response_code(500);
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> — 19th Episcopal District</title>
        
        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <link rel="icon" type="image/svg+xml" href="">
        <link rel="icon" type="image/png" href="19thDistrict.png">
        
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .error-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 500px;
                padding: 40px;
                text-align: center;
            }
            .error-icon {
                font-size: 80px;
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-title {
                color: #333;
                font-weight: bold;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 25px;
            }
            .debug-info {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 8px;
                padding: 15px;
                margin-top: 20px;
                text-align: left;
                font-size: 12px;
                font-family: monospace;
            }
            .btn-home {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                padding: 12px 30px;
                border-radius: 25px;
                color: white;
                font-weight: bold;
                transition: transform 0.3s;
            }
            .btn-home:hover {
                transform: scale(1.05);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="error-title"><?= htmlspecialchars($title) ?></h2>
            <p class="error-message"><?= htmlspecialchars($message) ?></p>
            
            <?php if ($debugInfo && $config['app']['debug']): ?>
                <div class="debug-info">
                    <strong>Debug Information:</strong><br>
                    <?= htmlspecialchars($debugInfo) ?>
                </div>
            <?php endif; ?>
            
            <a href="<?= base_url('index.php') ?>" class="btn btn-home">
                <i class="fas fa-home me-2"></i>Return to Dashboard
            </a>
            <a href="<?= base_url('login.php') ?>" class="btn btn-outline-secondary mt-2">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Wrap database operations with error handling
 */
function safeQuery(PDO $pdo, string $query, array $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        logDatabaseError($query, $e->getMessage(), $params);
        
        $config = require __DIR__ . '/../config.php';
        if ($config['app']['debug']) {
            showErrorPage(
                'Database Error',
                'A database error occurred. Please try again later.',
                $e->getMessage()
            );
        } else {
            showErrorPage(
                'Database Error',
                'A database error occurred. Please try again later.',
                null
            );
        }
    }
}

/**
 * Enable/disable error display based on config
 */
if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL); // Still log all errors, just don't display them
}
