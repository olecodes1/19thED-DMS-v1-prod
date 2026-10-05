<?php
$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare("SELECT title, story_year, content, status, cover_media_id, media_ids_json FROM story_pages WHERE slug = ? AND deleted_at IS NULL AND status='published' LIMIT 1");
$stmt->execute([$slug]);
$story = $stmt->fetch(PDO::FETCH_ASSOC);
$cover = null;
$storyMedia = [];
if ($story && !empty($story['cover_media_id'])) {
    $c = $pdo->prepare("SELECT file_path, media_type, title FROM media_items WHERE media_id = ? AND deleted_at IS NULL LIMIT 1");
    $c->execute([(int)$story['cover_media_id']]);
    $cover = $c->fetch(PDO::FETCH_ASSOC) ?: null;
}
if ($story) {
    $ids = json_decode((string)($story['media_ids_json'] ?? '[]'), true);
    if (is_array($ids) && !empty($ids)) {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $orderExpr = implode(',', $ids);
            $m = $pdo->prepare("SELECT media_id, title, media_type, file_path FROM media_items WHERE media_id IN ($placeholders) AND deleted_at IS NULL ORDER BY FIELD(media_id, $orderExpr)");
            $m->execute($ids);
            $storyMedia = $m->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}
?>
<?php if (!$story): ?>
  <div class="alert alert-warning">Published story not found.</div>
<?php else: ?>
  <h4 class="fw-bold text-success mb-2"><?= h($story['title']) ?></h4>
  <div class="small text-muted mb-3">Year: <?= h((string)($story['story_year'] ?? '—')) ?></div>
  <?php if ($cover && ($cover['media_type'] ?? '') === 'image'): ?>
    <div class="card shadow-sm mb-3"><div class="card-body"><img src="../<?= h($cover['file_path']) ?>" class="media-thumb" alt="<?= h($cover['title']) ?>" loading="lazy"></div></div>
  <?php endif; ?>
  <div class="card shadow-sm mb-3"><div class="card-body" style="white-space: pre-wrap;"><?= h($story['content'] ?? '') ?></div></div>
  <?php if ($storyMedia): ?>
    <div class="row g-3 mb-3">
      <?php foreach ($storyMedia as $m): ?>
        <div class="col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h6><?= h($m['title']) ?></h6>
              <?php if ($m['media_type'] === 'image'): ?>
                <img src="../<?= h($m['file_path']) ?>" class="media-thumb" alt="<?= h($m['title']) ?>" loading="lazy">
              <?php elseif ($m['media_type'] === 'video'): ?>
                <video controls class="media-thumb"><source src="../<?= h($m['file_path']) ?>"></video>
              <?php else: ?>
                <audio controls class="w-100"><source src="../<?= h($m['file_path']) ?>"></audio>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<a href="?page=stories" class="btn btn-secondary btn-sm mt-3">Back to Stories</a>
