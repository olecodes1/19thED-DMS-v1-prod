<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$media = $pdo->query("SELECT media_id, title, media_type, file_path FROM media_items WHERE deleted_at IS NULL ORDER BY uploaded_at DESC")->fetchAll();
$stories = $pdo->query("
    SELECT s.*, m.file_path AS cover_file_path
    FROM story_pages s
    LEFT JOIN media_items m ON m.media_id = s.cover_media_id
    WHERE s.deleted_at IS NULL
    ORDER BY COALESCE(s.story_year, 9999), s.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Story Pages — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4 px-4">
  <h5 class="fw-bold text-success mb-3"><i class="fas fa-book-open me-2"></i>Story Pages</h5>
  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Story page created.</div><?php endif; ?>
  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?><div class="alert alert-warning py-2">Story deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Create Story Page</div>
        <div class="card-body">
          <form method="POST" action="../actions/process_story_page.php">
            <div class="mb-2"><label class="form-label">Title</label><input type="text" name="title" class="form-control form-control-sm" required></div>
            <div class="mb-2"><label class="form-label">Year</label><input type="number" name="story_year" class="form-control form-control-sm" min="1800" max="<?= date('Y') ?>"></div>
            <div class="mb-2">
              <label class="form-label">Status</label>
              <select name="status" class="form-select form-select-sm">
                <option value="draft" selected>Draft</option>
                <option value="published">Published</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Cover Image (optional)</label>
              <select name="cover_media_id" class="form-select form-select-sm">
                <option value="">-- No cover --</option>
                <?php foreach ($media as $m): ?>
                  <?php if ($m['media_type'] === 'image'): ?>
                    <option value="<?= (int)$m['media_id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2"><label class="form-label">Story Content</label><textarea name="content" rows="6" class="form-control form-control-sm"></textarea></div>
            <div class="mb-3">
              <label class="form-label">Attach Media</label>
              <select name="media_ids[]" class="form-select form-select-sm" multiple size="8">
                <?php foreach ($media as $m): ?>
                  <option value="<?= (int)$m['media_id'] ?>"><?= htmlspecialchars($m['title']) ?> (<?= htmlspecialchars($m['media_type']) ?>)</option>
                <?php endforeach; ?>
              </select>
              <div class="small text-muted mt-1">Optional media order: enter IDs (comma-separated) to control display order, e.g. 15,12,18.</div>
              <input type="text" name="media_order" class="form-control form-control-sm mt-1" placeholder="Media ID order (optional)">
            </div>
            <button type="submit" class="btn btn-success btn-sm">Save Story</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Stories</div>
        <div class="card-body">
          <?php if (!$stories): ?>
            <p class="text-muted mb-0">No stories yet.</p>
          <?php else: foreach ($stories as $s): ?>
            <div class="border rounded p-2 mb-2">
              <div class="d-flex justify-content-between">
                <div>
                  <strong><?= htmlspecialchars($s['title']) ?></strong>
                  <span class="text-muted small"><?= htmlspecialchars((string)($s['story_year'] ?? '')) ?></span>
                  <span class="badge bg-<?= ($s['status'] ?? 'draft') === 'published' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($s['status'] ?? 'draft') ?></span>
                </div>
                <div>
                  <a class="btn btn-sm btn-outline-primary" href="story_page.php?slug=<?= urlencode($s['slug']) ?>">Open</a>
                  <form method="POST" action="../actions/delete_story_page.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$s['story_id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger js-confirm-delete">Delete</button></form>
                </div>
              </div>
              <?php if (!empty($s['cover_file_path'])): ?>
                <img src="../<?= htmlspecialchars($s['cover_file_path']) ?>" class="media-thumb my-2" alt="<?= htmlspecialchars($s['title']) ?>" loading="lazy">
              <?php endif; ?>
              <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth($s['content'] ?? '', 0, 160, '…')) ?></div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
