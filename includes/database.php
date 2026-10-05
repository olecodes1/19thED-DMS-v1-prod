<?php

/**
 * Database Connection Handler — now a thin delegate to the central
 * bootstrap (§6.3). The bootstrap creates one shared PDO instance per
 * request; this file keeps the existing `require database.php` contract
 * working for all current callers.
 */

$pdo = require __DIR__ . '/bootstrap.php';

return $pdo;
