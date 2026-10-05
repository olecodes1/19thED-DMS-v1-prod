<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$members = [];
$churches = [];
$areas = [];
$events = [];
$media = [];

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT member_id, name, surname_name, contact FROM members WHERE name LIKE ? OR surname_name LIKE ? OR contact LIKE ? ORDER BY surname_name, name LIMIT 20");
    $stmt->execute([$like, $like, $like]);
    $members = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT church_id, local_church_name FROM churches WHERE local_church_name LIKE ? ORDER BY local_church_name LIMIT 20");
    $stmt->execute([$like]);
    $churches = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT area_id, area_name FROM areas WHERE area_name LIKE ? ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name LIMIT 20");
    $stmt->execute([$like]);
    $areas = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT event_id, event_name, event_date FROM events WHERE event_name LIKE ? OR location LIKE ? ORDER BY event_date DESC LIMIT 20");
    $stmt->execute([$like, $like]);
    $events = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT media_id, title, media_type, category FROM media_items WHERE title LIKE ? OR category LIKE ? OR tags LIKE ? OR event_tag LIKE ? OR person_tag LIKE ? ORDER BY uploaded_at DESC LIMIT 20");
    $stmt->execute([$like, $like, $like, $like, $like]);
    $media = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Global Search — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4 px-4">
  <h5 class="fw-bold text-success mb-3"><i class="fas fa-search me-2"></i>Global Search</h5>
  <form method="GET" class="mb-3" style="max-width:520px">
    <div class="input-group">
      <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Search members, churches, events, media...">
      <button class="btn btn-success" type="submit">Search</button>
    </div>
  </form>

  <?php if ($q === ''): ?>
    <p class="text-muted">Type a search term to begin.</p>
  <?php else: ?>
    <div class="row g-3">
      <div class="col-md-6"><div class="card shadow-sm"><div class="card-header">Members (<?= count($members) ?>)</div><div class="card-body small"><?php foreach ($members as $m): ?><div><a href="members.php?search=<?= urlencode($m['name']) ?>"><?= htmlspecialchars($m['name'] . ' ' . $m['surname_name']) ?></a> <?= htmlspecialchars($m['contact'] ?? '') ?></div><?php endforeach; if (!$members) echo '<div class="text-muted">No matches.</div>'; ?></div></div></div>
      <div class="col-md-6"><div class="card shadow-sm"><div class="card-header">Churches (<?= count($churches) ?>)</div><div class="card-body small"><?php foreach ($churches as $c): ?><div><a href="churches.php?search=<?= urlencode($c['local_church_name']) ?>"><?= htmlspecialchars($c['local_church_name']) ?></a></div><?php endforeach; if (!$churches) echo '<div class="text-muted">No matches.</div>'; ?></div></div></div>
      <div class="col-md-6"><div class="card shadow-sm"><div class="card-header">Areas (<?= count($areas) ?>)</div><div class="card-body small"><?php foreach ($areas as $a): ?><div><a href="areas.php?search=<?= urlencode($a['area_name']) ?>"><?= htmlspecialchars($a['area_name']) ?></a></div><?php endforeach; if (!$areas) echo '<div class="text-muted">No matches.</div>'; ?></div></div></div>
      <div class="col-md-6"><div class="card shadow-sm"><div class="card-header">Events (<?= count($events) ?>)</div><div class="card-body small"><?php foreach ($events as $e): ?><div><a href="events.php?search=<?= urlencode($e['event_name']) ?>"><?= htmlspecialchars($e['event_name']) ?></a> <?= htmlspecialchars($e['event_date']) ?></div><?php endforeach; if (!$events) echo '<div class="text-muted">No matches.</div>'; ?></div></div></div>
      <div class="col-12"><div class="card shadow-sm"><div class="card-header">Media (<?= count($media) ?>)</div><div class="card-body small"><?php foreach ($media as $m): ?><div><a href="media.php?category=<?= urlencode((string)$m['category']) ?>"><?= htmlspecialchars($m['title']) ?></a> <span class="text-muted">(<?= htmlspecialchars($m['media_type']) ?><?= $m['category'] ? ' • ' . htmlspecialchars($m['category']) : '' ?>)</span></div><?php endforeach; if (!$media) echo '<div class="text-muted">No matches.</div>'; ?></div></div></div>
    </div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>

