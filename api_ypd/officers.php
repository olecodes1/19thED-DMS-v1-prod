<?php
// /api_ypd/officers[/{id}] — leadership roster
// GET is public, POST/PUT/DELETE require authentication
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/crud_helper.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/auth_middleware.php';

ypd_send_json_headers();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $pdo = getDb();
    $id = $GLOBALS['ypd_route_id'] ?? ($_GET['id'] ?? null);
    $sql = "SELECT o.*, CONCAT('" . UPLOAD_URL_BASE . "/', p.filename) AS photo_url
            FROM ypd_officers o
            LEFT JOIN photos p ON p.id = o.photo_id";
    if ($id !== null) {
        $stmt = $pdo->prepare($sql . ' WHERE o.id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) ypd_error('Not found', 404);
        ypd_respond($row);
    }
    $rows = $pdo->query($sql . ' ORDER BY o.sort_order ASC, o.id ASC')->fetchAll();
    ypd_respond($rows);
}

ypd_handle_crud(
    table: 'ypd_officers',
    allowedFields: ['full_name', 'position', 'term_start', 'term_end', 'bio', 'photo_id', 'sort_order', 'status'],
    requiredOnCreate: ['full_name', 'position'],
    requireAuth: true
);
