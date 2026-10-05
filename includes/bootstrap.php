<?php

/**
 * Central application bootstrap (§6.1)
 * One require that guarantees consistent initialization order:
 * configuration -> error handling -> session (via auth.php) -> database.
 * 
 * Also acts as the single database provider (§6.3): legacy entry points
 * (db.php, access_control.php) load this file, which reuses one PDO
 * instance project-wide instead of each file opening its own.
 * 
 * OPTIMIZATION: Query profiling — counts all queries per request
 * and optionally logs them (for admin pages only).
 */

if (defined('APP_BOOTSTRAPPED')) {
    // Return the existing PDO instance if already bootstrapped
    global $pdo;
    if (isset($pdo)) {
        return $pdo;
    }
    return true;
}
define('APP_BOOTSTRAPPED', true);

if (!function_exists('app_query_profile')) {
    function app_query_profile(?string $sql = null): array
    {
        static $profile = ['count' => 0, 'queries' => [], 'started_at' => null];
        if ($profile['started_at'] === null) $profile['started_at'] = microtime(true);
        if ($sql !== null) {
            $profile['count']++;
            $profile['queries'][] = trim(preg_replace('/\s+/', ' ', $sql));
        }
        return $profile;
    }
}

if (!class_exists('AdminDashPDO', false)) {
    class AdminDashPDOStatement extends PDOStatement
    {
        protected function __construct() {}
        public function execute(?array $params = null): bool
        {
            app_query_profile((string)$this->queryString);
            return $params === null ? parent::execute() : parent::execute($params);
        }
    }

    class AdminDashPDO extends PDO
    {
        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
        {
            app_query_profile($query);
            return $fetchMode === null
                ? parent::query($query)
                : parent::query($query, $fetchMode, ...$fetchModeArgs);
        }
        public function exec(string $statement): int|false
        {
            app_query_profile($statement);
            return parent::exec($statement);
        }
    }
}

// 1. Configuration
require_once __DIR__ . '/../config.php';

// 2. Error handling (safe to load once; handlers set at first include)
require_once __DIR__ . '/error_handler.php';

// 3. Session + auth helpers (auth.php requires session.php, which starts
//    the session with hardened settings)
require_once __DIR__ . '/auth.php';

// 4. Database — single shared PDO instance for the whole request
$bootstrapConfig = require __DIR__ . '/../config.php';
$dbConfig = $bootstrapConfig['db'];
global $pdo;
if (!isset($pdo)) {
    // Try XAMPP MySQL first (port 3307), then fallback to standard port (3306)
    try {
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
        $pdo = new AdminDashPDO($dsn, $dbConfig['user'], $dbConfig['pass'], [PDO::ATTR_STATEMENT_CLASS => ['AdminDashPDOStatement', []]]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $e) {
        // Fallback to standard MySQL port
        try {
            $fallbackPort = $dbConfig['fallback_port'] ?? '3306';
            $dsn = "mysql:host={$dbConfig['host']};port=$fallbackPort;dbname={$dbConfig['name']};charset={$dbConfig['charset']}";
            $pdo = new AdminDashPDO($dsn, $dbConfig['user'], $dbConfig['pass'], [PDO::ATTR_STATEMENT_CLASS => ['AdminDashPDOStatement', []]]);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e2) {
            error_log('Database connection failed: ' . $e2->getMessage());
            $message = 'Database connection failed. Please check configuration.';
            if (!empty($bootstrapConfig['app']['debug'])) {
                $message .= ' Error: ' . $e2->getMessage();
            }
            die($message);
        }
    }
}

return $pdo;
