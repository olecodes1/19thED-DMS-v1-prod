<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/story_pages.php');
    exit;
}

$title = trim($_POST['title'] ?? '');
$year = (int)($_POST['story_year'] ?? 0) ?: null;
$status = $_POST['status'] ?? 'draft';
$status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
$coverMediaId = (int)($_POST['cover_media_id'] ?? 0) ?: null;
$content = trim($_POST['content'] ?? '');
$mediaIds = $_POST['media_ids'] ?? [];
if (!is_array($mediaIds)) $mediaIds = [];
$mediaIds = array_values(array_filter(array_map('intval', $mediaIds), fn($v) => $v > 0));
$mediaOrderInput = trim((string)($_POST['media_order'] ?? ''));

if ($title === '') {
    header('Location: ../views/story_pages.php?error=title_required');
    exit;
}

$slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
if ($slug === '') $slug = 'story-' . time();
$baseSlug = $slug;
$suffix = 2;
while (true) {
    $dup = $pdo->prepare("SELECT COUNT(*) FROM story_pages WHERE slug = ?");
    $dup->execute([$slug]);
    if ((int)$dup->fetchColumn() === 0) {
        break;
    }
    $slug = $baseSlug . '-' . $suffix;
    $suffix++;
}

if ($mediaOrderInput !== '') {
    $ordered = [];
    foreach (explode(',', $mediaOrderInput) as $part) {
        $id = (int)trim($part);
        if ($id > 0 && in_array($id, $mediaIds, true) && !in_array($id, $ordered, true)) {
            $ordered[] = $id;
        }
    }
    foreach ($mediaIds as $id) {
        if (!in_array($id, $ordered, true)) {
            $ordered[] = $id;
        }
    }
    $mediaIds = $ordered;
}

if ($coverMediaId !== null && !in_array($coverMediaId, $mediaIds, true)) {
    $mediaIds[] = $coverMediaId;
}

$stmt = $pdo->prepare("INSERT INTO story_pages (title, slug, story_year, status, cover_media_id, content, media_ids_json) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$title, $slug, $year, $status, $coverMediaId, $content ?: null, json_encode($mediaIds)]);
header('Location: ../views/story_pages.php?saved=1');
exit;
