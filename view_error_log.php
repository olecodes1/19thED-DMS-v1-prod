<?php
/**
 * Error Log Viewer
 * This page allows administrators to view the error log
 * ONLY FOR ADMINISTRATORS - REMOVE IN PRODUCTION OR ADD AUTHENTICATION
 */

// Simple authentication check
session_start();
if (!isset($_SESSION['auth_user']) || $_SESSION['auth_user']['role'] !== 'superadmin') {
    die('Access denied. Administrator access required.');
}

$errorLogFile = __DIR__ . '/logs/error.log';

// Handle log clearing
if (isset($_POST['clear_log'])) {
    file_put_contents($errorLogFile, '');
    header('Location: view_error_log.php?cleared=1');
    exit;
}

// Read error log
$logContent = file_exists($errorLogFile) ? file_get_contents($errorLogFile) : 'No errors logged yet.';
$logLines = explode("\n", $logContent);
$logLines = array_filter($logLines, function($line) {
    return trim($line) !== '';
});

// Show last 100 errors by default
$logLines = array_slice($logLines, -100);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Log Viewer — 19th Episcopal District</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/svg+xml" href="">
    <link rel="icon" type="image/png" href="19thDistrict.png">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .log-entry {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        .log-time {
            color: #6c757d;
            font-weight: bold;
        }
        .log-context {
            color: #0d6efd;
            font-weight: bold;
        }
        .log-error {
            color: #dc3545;
        }
        .log-info {
            color: #17a2b8;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bug me-2"></i>Error Log Viewer</h2>
            <div>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
                <form method="POST" class="d-inline">
                    <button type="submit" name="clear_log" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear the error log?')">
                        <i class="fas fa-trash me-2"></i>Clear Log
                    </button>
                </form>
            </div>
        </div>

        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert alert-success">Error log cleared successfully.</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Errors (Last 100 entries)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($logLines)): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>No errors logged!</div>
                <?php else: ?>
                    <?php foreach (array_reverse($logLines) as $line): ?>
                        <?php
                        // Highlight different parts of the log
                        $highlightedLine = $line;
                        $highlightedLine = preg_replace('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', '<span class="log-time">[$1]</span>', $highlightedLine);
                        $highlightedLine = preg_replace('/\[(\w+)\]/', '<span class="log-context">[$1]</span>', $highlightedLine);
                        $highlightedLine = preg_replace('/(ERROR|FATAL|EXCEPTION)/', '<span class="log-error">$1</span>', $highlightedLine);
                        $highlightedLine = preg_replace('/(DATABASE|LOGIN|SYSTEM)/', '<span class="log-info">$1</span>', $highlightedLine);
                        ?>
                        <div class="log-entry"><?= $highlightedLine ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Log file location: <?= htmlspecialchars($errorLogFile) ?><br>
                <i class="fas fa-shield-alt me-1"></i>
                This page is for administrator use only. Remove or protect this file in production.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
