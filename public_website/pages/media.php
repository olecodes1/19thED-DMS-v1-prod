<?php
// OPTIMIZED with result caching
$type = $_GET['type'] ?? '';
$category = trim($_GET['category'] ?? '');
$year = (int)($_GET['year'] ?? 0) ?: null;

require_once __DIR__ . '/../../includes/query_cache.php';

// Cache survives separate requests for a short period, which is useful for
// public read-heavy pages while keeping newly uploaded media reasonably fresh.
{
    $mediaSql = "SELECT media_id, title, media_type, category, media_year, file_path, description FROM media_items WHERE deleted_at IS NULL";
    $mediaParams = [];
    if (in_array($type, ['image', 'video', 'audio'], true)) {
        $mediaSql .= " AND media_type = ?";
        $mediaParams[] = $type;
    }
    if ($category !== '') {
        $mediaSql .= " AND category = ?";
        $mediaParams[] = $category;
    }
    if ($year !== null) {
        $mediaSql .= " AND media_year = ?";
        $mediaParams[] = $year;
    }
    $mediaSql .= " ORDER BY uploaded_at DESC";
    $mediaRows = cached_query($pdo, $mediaSql, $mediaParams, 60);
    $categories = array_column(cached_query($pdo, "SELECT DISTINCT category FROM media_items WHERE deleted_at IS NULL AND category IS NOT NULL AND category <> '' ORDER BY category", [], 300), 'category');
}
?>
<h4 class="fw-bold text-success mb-3">Media Gallery</h4>
<form class="row g-2 mb-3" method="GET">
  <input type="hidden" name="page" value="media">
  <div class="col-md-2">
    <select name="type" class="form-select form-select-sm">
      <option value="">-- Type --</option>
      <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Image</option>
      <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Video</option>
      <option value="audio" <?= $type === 'audio' ? 'selected' : '' ?>>Audio</option>
    </select>
  </div>
  <div class="col-md-3">
    <select name="category" class="form-select form-select-sm">
      <option value="">-- Category --</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><input type="number" name="year" class="form-control form-control-sm" placeholder="Year" value="<?= h($year !== null ? (string)$year : '') ?>"></div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="?page=media" class="btn btn-secondary btn-sm">Reset</a>
  </div>
</form>
<div class="row g-3">
  <?php if (!$mediaRows): ?>
    <div class="col-12"><div class="alert alert-light border">No media found.</div></div>
  <?php else: foreach ($mediaRows as $m): ?>
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <?php if ($m['media_type'] === 'image'): ?>
            <img src="../<?= h($m['file_path']) ?>" alt="<?= h($m['title']) ?>" class="media-thumb mb-2" loading="lazy">
          <?php elseif ($m['media_type'] === 'video'): ?>
            <video controls class="media-thumb mb-2" loading="lazy"><source src="../<?= h($m['file_path']) ?>"></video>
          <?php else: ?>
            <audio controls class="w-100 mb-2" loading="lazy"><source src="../<?= h($m['file_path']) ?>"></audio>
          <?php endif; ?>
          <h6 class="mb-1"><?= h($m['title']) ?></h6>
          <div class="small text-muted"><?= h($m['category'] ?? 'General') ?><?= !empty($m['media_year']) ? ' • ' . (int)$m['media_year'] : '' ?></div>
          <div class="small mt-2"><?= h($m['description'] ?? '') ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>
