<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: story_pages.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM story_pages WHERE slug = ? AND deleted_at IS NULL LIMIT 1");
$stmt->execute([$slug]);
$story = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$story) {
    header('Location: story_pages.php');
    exit;
}

$mediaRows = [];
$coverMedia = null;
$ids = json_decode((string)($story['media_ids_json'] ?? '[]'), true);
if (is_array($ids) && !empty($ids)) {
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $orderExpr = implode(',', $ids);
        $mStmt = $pdo->prepare("SELECT * FROM media_items WHERE media_id IN ({$placeholders}) AND deleted_at IS NULL ORDER BY FIELD(media_id, {$orderExpr})");
        $mStmt->execute($ids);
        $mediaRows = $mStmt->fetchAll();
    }
}

if (!empty($story['cover_media_id'])) {
    $coverStmt = $pdo->prepare("SELECT media_id, title, file_path, media_type FROM media_items WHERE media_id = ? AND deleted_at IS NULL LIMIT 1");
    $coverStmt->execute([(int)$story['cover_media_id']]);
    $coverMedia = $coverStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($story['title']) ?> — Story</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold text-success mb-0">
      <?= htmlspecialchars($story['title']) ?>
      <span class="badge bg-<?= ($story['status'] ?? 'draft') === 'published' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($story['status'] ?? 'draft') ?></span>
    </h4>
    <a href="story_pages.php" class="btn btn-secondary btn-sm">Back</a>
  </div>
  <div class="mb-3 text-muted small">Year: <?= htmlspecialchars((string)($story['story_year'] ?? '—')) ?></div>
  <?php if ($coverMedia && ($coverMedia['media_type'] ?? '') === 'image'): ?>
    <div class="card shadow-sm mb-3"><div class="card-body"><img src="../<?= htmlspecialchars($coverMedia['file_path']) ?>" class="media-thumb" alt="<?= htmlspecialchars($coverMedia['title']) ?>" loading="lazy"></div></div>
  <?php endif; ?>
  <div class="card shadow-sm mb-3"><div class="card-body" style="white-space: pre-wrap;"><?= htmlspecialchars($story['content'] ?? '') ?></div></div>
  <?php if ($mediaRows): ?>
    <div class="row g-3">
      <?php foreach ($mediaRows as $m): ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h6><?= htmlspecialchars($m['title']) ?></h6>
              <?php if ($m['media_type'] === 'image'): ?>
                <img src="../<?= htmlspecialchars($m['file_path']) ?>" class="media-thumb" alt="<?= htmlspecialchars($m['title']) ?>" loading="lazy">
              <?php elseif ($m['media_type'] === 'video'): ?>
                <video controls class="media-thumb"><source src="../<?= htmlspecialchars($m['file_path']) ?>"></video>
              <?php else: ?>
                <audio controls class="w-100"><source src="../<?= htmlspecialchars($m['file_path']) ?>"></audio>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
