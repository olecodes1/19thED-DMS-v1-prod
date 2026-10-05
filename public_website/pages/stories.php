<?php
$storyRows = $pdo->query("
  SELECT title, slug, story_year, content
  FROM story_pages
  WHERE deleted_at IS NULL AND status = 'published'
  ORDER BY COALESCE(story_year, 0) DESC, created_at DESC
")->fetchAll();
?>
<h4 class="fw-bold text-success mb-3">Stories & History</h4>
<div class="card shadow-sm">
  <div class="card-body">
    <?php if (!$storyRows): ?>
      <div class="text-muted">No published stories available.</div>
    <?php else: foreach ($storyRows as $s): ?>
      <div class="border rounded p-2 mb-2">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <a class="fw-semibold text-decoration-none" href="?page=story&slug=<?= urlencode($s['slug']) ?>"><?= h($s['title']) ?></a>
            <div class="small text-muted"><?= h((string)($s['story_year'] ?? '')) ?></div>
          </div>
        </div>
        <div class="small text-muted mt-1"><?= h(mb_strimwidth($s['content'] ?? '', 0, 180, '…')) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
