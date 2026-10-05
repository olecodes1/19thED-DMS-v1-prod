<?php
/**
 * YPD History Booklet — Print Edition
 * Server-side rendered, styled like the reference ypd-booklet print page:
 * one 8.5x11in "sheet" per chapter, cover gradient, print.css handles both
 * the on-screen preview and the browser's Print > Save as PDF output.
 * 
 * OPTIMIZATION: Cache the full page output for 300 seconds (5 minutes)
 * as booklet content rarely changes.
 */

require_once __DIR__ . '/../api_ypd/config.php';
require_once __DIR__ . '/../api_ypd/db.php';

// OPTIMIZATION: Cache full page output for 5 minutes
$cacheTtl = 300; // 5 minutes
$cacheKey = 'ypd_booklet_print_' . md5($_SERVER['REQUEST_URI'] . $_SERVER['REQUEST_METHOD']);
$cacheFile = sys_get_temp_dir() . '/' . $cacheKey . '.html';

if (file_exists($cacheFile) && (filemtime($cacheFile) + $cacheTtl) > time()) {
    // Serve cached version
    header('Content-Type: text/html; charset=utf-8');
    header('X-Cache: HIT');
    readfile($cacheFile);
    exit;
}

// Start output buffering to capture content
ob_start();

function h($v): string {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * §5.4 — only accept plain, safe filenames for photo URLs.
 * Blocks traversal and injection via anything that may have been written
 * to the photos table outside the upload endpoint.
 */
function safe_photo_filename(?string $filename): string {
    if ($filename === null || $filename === '' || strpos($filename, '..') !== false) return '';
    return preg_match('/^[A-Za-z0-9._-]+$/', $filename) ? $filename : '';
}

function fmtNumber($v): string {
    $n = (float) $v;
    return (floor($n) == $n) ? number_format($n, 0) : number_format($n, 1);
}

try {
    $pdo = getDb();
    $meta = $pdo->query('SELECT * FROM ypd_booklet_meta ORDER BY id LIMIT 1')->fetch() ?: [];
    $coverPhoto = null;
    if (!empty($meta['cover_photo_id'])) {
        $stmt = $pdo->prepare('SELECT * FROM photos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $meta['cover_photo_id']]);
        $coverPhoto = $stmt->fetch() ?: null;
    }
    $history = $pdo->query('SELECT * FROM ypd_history_entries ORDER BY sort_order ASC, id ASC')->fetchAll();
    $officers = $pdo->query('SELECT * FROM ypd_officers ORDER BY sort_order ASC, id ASC')->fetchAll();
    $motherDirectors = $pdo->query('SELECT * FROM ypd_mother_directors ORDER BY sort_order ASC, id ASC')->fetchAll();
    $timeline = $pdo->query('SELECT * FROM ypd_timeline_events ORDER BY event_date ASC, sort_order ASC')->fetchAll();
    $achievements = $pdo->query('SELECT * FROM ypd_achievements ORDER BY sort_order ASC, id ASC')->fetchAll();
    $statistics = $pdo->query('SELECT * FROM ypd_statistics ORDER BY sort_order ASC, id ASC')->fetchAll();
    $photos = $pdo->query('SELECT * FROM photos ORDER BY uploaded_at DESC')->fetchAll();
} catch (Exception $e) {
    // §4.3 — log privately, show the public a generic message only
    error_log('YPD booklet print error: ' . $e->getMessage());
    $meta = [];
    $history = $officers = $motherDirectors = $timeline = $achievements = $statistics = $photos = [];
    $loadError = true;
}

// Clean up and output cached content
$output = ob_get_clean();

// Write cache file
if (!$loadError) {
    file_put_contents($cacheFile, $output);
}

// Serve response
header('Content-Type: text/html; charset=utf-8');
header('X-Cache: MISS');

// Set cache headers for browser
header('Cache-Control: public, max-age=300'); // 5 minutes

echo $output;

// Stop execution (no duplicate output)
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($meta['booklet_title'] ?? 'YPD History Booklet') ?> — Print Edition</title>
<link rel="icon" type="image/png" href="../19thDistrict.png">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Source+Serif+4:ital,wght@0,400;0,600;1,400&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="pages/ypd_booklet/css/print.css">
</head>
<body>

<div class="no-print toolbar">
  <button onclick="window.print()">Download PDF (opens print dialog)</button>
  <a href="index.php?page=ypd_booklet">&larr; Back to booklet viewer</a>
</div>

<?php if (!empty($loadError)): ?>
  <section class="sheet"><p class="empty">The booklet is temporarily unavailable. Please try again later.</p></section>
<?php endif; ?>

<section class="sheet cover">
  <?php if ($coverPhoto && !empty($coverPhoto['filename'])): ?>
    <div class="cover-photo" style="background-image:url('<?= h(UPLOAD_URL_BASE) ?>/<?= h($coverPhoto['filename']) ?>')"></div>
  <?php endif; ?>
  <div class="district-label"><?= h($meta['district_name'] ?? '19th Episcopal District') ?> &middot; AME Church</div>
  <h1><?= h($meta['booklet_title'] ?? 'History of the YPD') ?></h1>
  <p class="subtitle"><?= h($meta['subtitle'] ?? '') ?></p>
  <p class="motto">Grow &middot; Glow &middot; Go for Christ</p>
</section>

<section class="sheet">
  <span class="eyebrow">Foreword</span>
  <h2>From the Office of the Historiographer</h2>
  <p><?= nl2br(h($meta['foreword'] ?? '')) ?></p>
  <?php if (!empty($meta['historiographer_name'])): ?>
    <p class="signature">— <?= h($meta['historiographer_name']) ?>, Historiographer/Statistician</p>
  <?php endif; ?>
</section>

<section class="sheet">
  <span class="eyebrow">Chapter One</span>
  <h2>Our History</h2>
  <?php if (!$history): ?><p class="empty">No history entries recorded yet.</p><?php endif; ?>
  <?php foreach ($history as $r): ?>
    <div class="entry">
      <?php if ($r['era_label']): ?><div class="era"><?= h($r['era_label']) ?></div><?php endif; ?>
      <h3><?= h($r['title']) ?></h3>
      <p><?= nl2br(h($r['body'])) ?></p>
    </div>
  <?php endforeach; ?>
</section>

<section class="sheet">
  <span class="eyebrow">Chapter Two</span>
  <h2>Leadership</h2>
  <?php if (!$officers): ?><p class="empty">No leadership records yet.</p><?php endif; ?>
  <?php foreach ($officers as $o): ?>
    <div class="officer">
      <div class="name"><?= h($o['full_name']) ?> — <span class="position"><?= h($o['position']) ?></span></div>
      <div class="term"><?= h($o['term_start']) ?><?= $o['term_end'] ? ' – ' . h($o['term_end']) : ($o['term_start'] ? ' – present' : '') ?></div>
      <?php if ($o['bio']): ?><p><?= nl2br(h($o['bio'])) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="sheet">
  <span class="eyebrow">Chapter Three</span>
  <h2>Our Mother Directors</h2>
  <?php if (!$motherDirectors): ?><p class="empty">No mother director records yet.</p><?php endif; ?>
  <?php foreach ($motherDirectors as $md): ?>
    <div class="officer">
      <div class="name"><?= h($md['full_name']) ?><?php if ($md['conference_name']): ?> — <span class="position"><?= h($md['conference_name']) ?></span><?php endif; ?></div>
      <?php if ($md['years_of_service']): ?><div class="term"><?= h($md['years_of_service']) ?></div><?php endif; ?>
      <?php if ($md['bio']): ?><p><?= nl2br(h($md['bio'])) ?></p><?php endif; ?>
      <?php if ($md['achievements']): ?><p><em><?= nl2br(h($md['achievements'])) ?></em></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="sheet">
  <span class="eyebrow">Chapter Four</span>
  <h2>Timeline of Milestones</h2>
  <?php if (!$timeline): ?><p class="empty">No milestones recorded yet.</p><?php endif; ?>
  <?php foreach ($timeline as $t): ?>
    <div class="timeline-row">
      <div class="date"><?= h($t['event_date']) ?></div>
      <div><strong><?= h($t['title']) ?></strong><?php if ($t['description']): ?><p><?= nl2br(h($t['description'])) ?></p><?php endif; ?></div>
    </div>
  <?php endforeach; ?>
</section>

<section class="sheet">
  <span class="eyebrow">Chapter Five</span>
  <h2>Achievements &amp; Recognitions</h2>
  <?php if (!$achievements): ?><p class="empty">No achievements recorded yet.</p><?php endif; ?>
  <?php foreach ($achievements as $a): ?>
    <div class="entry">
      <?php if ($a['category']): ?><div class="era"><?= h($a['category']) ?></div><?php endif; ?>
      <h3><?= h($a['title']) ?><?php if ($a['achievement_date']): ?> <span class="date-inline">(<?= h($a['achievement_date']) ?>)</span><?php endif; ?></h3>
      <?php if ($a['description']): ?><p><?= nl2br(h($a['description'])) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</section>

<section class="sheet">
  <span class="eyebrow">Chapter Six</span>
  <h2>By the Numbers</h2>
  <?php if (!$statistics): ?><p class="empty">No statistics recorded yet.</p><?php endif; ?>
  <div class="stat-grid">
    <?php foreach ($statistics as $s): ?>
      <div class="stat">
        <div class="value"><?= fmtNumber($s['value']) ?><?= $s['unit'] ? ' ' . h($s['unit']) : '' ?></div>
        <div class="label"><?= h($s['label']) ?><?= $s['year'] ? ' · ' . h($s['year']) : '' ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($photos): ?>
<section class="sheet">
  <span class="eyebrow">Chapter Seven</span>
  <h2>Photo Gallery</h2>
  <div class="photo-grid">
    <?php foreach ($photos as $p): ?>
      <?php $safeFile = safe_photo_filename($p['filename'] ?? null); ?>
      <?php if ($safeFile === '') continue; ?>
      <figure>
        <img src="<?= h(UPLOAD_URL_BASE) ?>/<?= h($safeFile) ?>" alt="<?= h($p['caption'] ?? '') ?>">
        <?php if ($p['caption']): ?><figcaption><?= h($p['caption']) ?></figcaption><?php endif; ?>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

</body>
</html>
