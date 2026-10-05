<?php
// /api_ypd/achievements[/{id}] — notable accomplishments/recognitions
// GET is public, POST/PUT/DELETE require authentication
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/crud_helper.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/auth_middleware.php';

ypd_send_json_headers();

$method = $_SERVER['REQUEST_METHOD'];
$requireAuth = ($method !== 'GET');

ypd_handle_crud(
    table: 'ypd_achievements',
    allowedFields: ['title', 'description', 'achievement_date', 'category', 'sort_order', 'status'],
    requiredOnCreate: ['title'],
    requireAuth: $requireAuth
);
