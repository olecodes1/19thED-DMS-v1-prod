<?php
/**
 * api_ypd_router.php — router for YPD History Booklet API within AdminDash
 * 
 * This router handles /api_ypd/* requests and routes them to the appropriate YPD API files
 * while using AdminDash's authentication and database systems
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Only intercept /api_ypd/... requests
if (strpos($uri, '/api_ypd/') !== 0) {
    return false; // Let PHP built-in server handle other requests
}

require_once __DIR__ . '/api_ypd/config.php';
require_once __DIR__ . '/api_ypd/db.php';

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ypd_send_json_headers();
    http_response_code(204);
    exit;
}

ypd_send_json_headers();

// Map /api_ypd/{resource}[/{id}] -> api_ypd/{resource}.php
$path = trim(substr($uri, strlen('/api_ypd/')), '/');
$segments = $path === '' ? [] : explode('/', $path);
$resource = $segments[0] ?? '';

$routes = [
    'meta'             => 'meta.php',
    'history'          => 'history.php',
    'officers'         => 'officers.php',
    'timeline'         => 'timeline.php',
    'achievements'     => 'achievements.php',
    'statistics'       => 'statistics.php',
    'photos'           => 'photos.php',
    'mother_directors' => 'mother_directors.php',
    'upload'           => 'upload.php',
];

if (!isset($routes[$resource])) {
    ypd_error('Unknown YPD API resource: ' . $resource, 404);
}

// Expose the remaining path segment (e.g. the {id}) to the endpoint file
$GLOBALS['ypd_route_id'] = $segments[1] ?? null;

require __DIR__ . '/api_ypd/' . $routes[$resource];