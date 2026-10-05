<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/pagination.php';
require_once '../includes/reference_data.php';

$type = $_GET['type'] ?? '';
$category = trim($_GET['category'] ?? '');
$tag = trim($_GET['tag'] ?? '');
$eventTag = trim($_GET['event_tag'] ?? '');
$personTag = trim($_GET['person_tag'] ?? '');
$year = (int)($_GET['year'] ?? 0) ?: null;

// Build WHERE clause for counting and data query
$whereSql = " WHERE deleted_at IS NULL";
$params = [];
if (in_array($type, ['image', 'video', 'audio'], true)) {
    $whereSql .= " AND media_type = ?";
    $params[] = $type;
}
if ($category !== '') {
    $whereSql .= " AND category = ?";
    $params[] = $category;
}
if ($tag !== '') {
    $whereSql .= " AND tags LIKE ?";
    $params[] = '%' . $tag . '%';
}
if ($eventTag !== '') {
    $whereSql .= " AND event_tag LIKE ?";
    $params[] = '%' . $eventTag . '%';
}
if ($personTag !== '') {
    $whereSql .= " AND person_tag LIKE ?";
    $params[] = '%' . $personTag . '%';
}
if ($year !== null) {
    $whereSql .= " AND media_year = ?";
    $params[] = $year;
}

// Add pagination
$pager = paginate($pdo, "SELECT COUNT(*) FROM media_items" . $whereSql, $params, 20);
$sql = "SELECT * FROM media_items" . $whereSql . " ORDER BY uploaded_at DESC LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$media = $stmt->fetchAll();

$leaders = $pdo->query("SELECT * FROM legacy_leaders ORDER BY COALESCE(start_year, 9999), full_name")->fetchAll();
$milestones = $pdo->query("SELECT * FROM milestones ORDER BY milestone_year")->fetchAll();
$foundedYear = $pdo->query("SELECT MIN(start_year) FROM legacy_leaders")->fetchColumn();
$districtAge = ($foundedYear && (int)$foundedYear > 0) ? ((int)date('Y') - (int)$foundedYear) : null;
$categories = get_media_categories($pdo);
$timeline = [];
foreach ($leaders as $l) {
    $timeline[] = ['year' => (int)($l['start_year'] ?? 0), 'type' => 'Leader', 'title' => $l['full_name'], 'meta' => $l['role_type'], 'text' => $l['descriptions'] ?? $l['achievements'], 'id' => $l['leader_id'], 'end_year' => $l['end_year'], 'conference' => $l['conference_name']];
}
foreach ($milestones as $m) {
    $timeline[] = ['year' => (int)($m['milestone_year'] ?? 0), 'type' => 'Milestone', 'title' => $m['title'], 'meta' => 'District', 'text' => $m['descriptions'] ?? $m['achievements'], 'id' => $m['milestone_id']];
}
usort($timeline, fn($a, $b) => $a['year'] <=> $b['year']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Media & History — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-photo-video me-2"></i>Multimedia, History & Insights</h5>
    <a href="../forms/add_media.php" class="btn btn-success btn-sm"><i class="fas fa-upload me-1"></i>Upload Media</a>
  </div>

  <?php if (isset($_GET['uploaded'])): ?><div class="alert alert-success alert-dismissible py-2">Media uploaded.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['leader_added'])): ?><div class="alert alert-success alert-dismissible py-2">Legacy leader added.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['leader_updated'])): ?><div class="alert alert-success alert-dismissible py-2">Legacy leader updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['leader_deleted'])): ?><div class="alert alert-warning py-2">Leader deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>
  <?php if (isset($_GET['milestone_added'])): ?><div class="alert alert-success alert-dismissible py-2">Milestone added.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['milestone_updated'])): ?><div class="alert alert-success alert-dismissible py-2">Milestone updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['milestone_deleted'])): ?><div class="alert alert-warning py-2">Milestone deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>
  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?><div class="alert alert-warning py-2">Item deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>
  <?php if (isset($_GET['error'])): ?><div class="alert alert-danger alert-dismissible py-2"><?= htmlspecialchars($_GET['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Media Items</div><div class="fs-4 fw-bold"><?= $pager['total'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Legacy Leaders</div><div class="fs-4 fw-bold"><?= count($leaders) ?></div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">District Start Year</div><div class="fs-4 fw-bold"><?= $foundedYear ? (int)$foundedYear : '—' ?></div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">District Age</div><div class="fs-4 fw-bold"><?= $districtAge !== null ? $districtAge . ' yrs' : '—' ?></div></div></div></div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2">
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
              <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><input type="text" name="tag" class="form-control form-control-sm" placeholder="Tag" value="<?= htmlspecialchars($tag) ?>"></div>
        <div class="col-md-2"><input type="text" name="event_tag" class="form-control form-control-sm" placeholder="Event" value="<?= htmlspecialchars($eventTag) ?>"></div>
        <div class="col-md-2"><input type="text" name="person_tag" class="form-control form-control-sm" placeholder="Person" value="<?= htmlspecialchars($personTag) ?>"></div>
        <div class="col-md-1"><input type="number" name="year" class="form-control form-control-sm" placeholder="Year" value="<?= htmlspecialchars((string)($year ?? '')) ?>"></div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          <a href="media.php" class="btn btn-secondary btn-sm">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="table-success">
          <tr>
            <th>#</th>
            <th>Media</th>
            <th>Type</th>
            <th>Category</th>
            <th>Year</th>
            <th>Tags</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($media)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">No media found.</td></tr>
          <?php else: foreach ($media as $i => $m): ?>
            <tr>
              <td><?= $pager['offset'] + $i + 1 ?></td>
              <td>
                <?php if ($m['media_type'] === 'image'): ?>
                  <img src="../<?= htmlspecialchars($m['file_path']) ?>" alt="<?= htmlspecialchars($m['title']) ?>" class="media-thumb" style="max-width: 50px; max-height: 50px;" loading="lazy">
                <?php else: ?>
                  <span class="badge bg-secondary"><?= htmlspecialchars($m['media_type']) ?></span>
                <?php endif; ?>
                <?= htmlspecialchars($m['title']) ?>
              </td>
              <td><?= htmlspecialchars($m['media_type']) ?></td>
              <td><?= htmlspecialchars($m['category'] ?? 'General') ?></td>
              <td><?= (int)($m['media_year'] ?? 0) ?: '—' ?></td>
              <td><?= htmlspecialchars($m['tags'] ?? '—') ?></td>
              <td>
                <a href="../forms/edit_media.php?id=<?= $m['media_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="../actions/delete_media.php" class="d-inline"><input type="hidden" name="id" value="<?= $m['media_id'] ?>"><button type="submit" class="btn btn-danger btn-sm js-confirm-delete">Del</button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span class="text-muted small">Total: <?= $pager['total'] ?> media item(s)</span>
      <?= render_pagination($pager) ?>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Add Legacy Leader</div>
        <div class="card-body">
          <form method="POST" action="../actions/process_legacy_leader.php" class="row g-2" enctype="multipart/form-data">
            <div class="col-md-6">
              <label class="form-label">Role</label>
              <select name="role_type" class="form-select form-select-sm">
                <option>Bishop</option>
                <option>Director</option>
                <option>President</option>
                <option>Mother Director</option>
                <option selected>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Conference</label>
              <input type="text" name="conference_name" class="form-control form-control-sm" placeholder="Optional">
            </div>
            <div class="col-12">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Start Year</label>
              <input type="number" name="start_year" class="form-control form-control-sm" min="1800" max="<?= date('Y') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">End Year</label>
              <input type="number" name="end_year" class="form-control form-control-sm" min="1800" max="<?= date('Y') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Descriptions</label>
              <textarea name="descriptions" class="form-control form-control-sm" rows="2" placeholder="Historical context, tenure notes, etc."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Achievements</label>
              <textarea name="achievements" class="form-control form-control-sm" rows="2" placeholder="Major milestones"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Photo</label>
              <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Add Leader</button>
            </div>
          </form>
          <hr>
          <form method="POST" action="../actions/process_milestone.php" class="row g-2">
            <div class="col-12"><label class="form-label">Milestone Title</label><input type="text" name="title" class="form-control form-control-sm" required></div>
            <div class="col-md-4"><label class="form-label">Year</label><input type="number" name="milestone_year" class="form-control form-control-sm" min="1800" max="<?= date('Y') ?>" required></div>
            <div class="col-md-8"><label class="form-label">Descriptions</label><input type="text" name="descriptions" class="form-control form-control-sm"></div>
            <div class="col-12"><label class="form-label">Achievements</label><textarea name="achievements" class="form-control form-control-sm" rows="2"></textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-outline-success btn-sm">Add Milestone</button></div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
          <span>Timeline (Leaders + Milestones)</span>
          <a href="story_pages.php" class="btn btn-sm btn-outline-primary">Story Pages</a>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr><th>Year</th><th>Type</th><th>Title</th><th>Meta</th><th>Notes</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php if (empty($timeline)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No legacy entries yet.</td></tr>
              <?php else: foreach ($timeline as $t): ?>
                <tr>
                  <td><?= $t['year'] > 0 ? (int)$t['year'] : '—' ?></td>
                  <td><?= htmlspecialchars($t['type']) ?></td>
                  <td><?= htmlspecialchars($t['title']) ?></td>
                  <td><?= htmlspecialchars($t['meta']) ?></td>
                  <td><?= htmlspecialchars($t['text'] ?? '—') ?></td>
                  <td class="text-nowrap">
                    <?php if ($t['type'] === 'Leader'): ?>
                      <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#leaderModal<?= (int)$t['id'] ?>"><i class="fas fa-eye"></i></button>
                      <a href="../forms/edit_leader.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                      <form method="POST" action="../actions/delete_leader.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button type="submit" class="btn btn-sm btn-danger js-confirm-delete"><i class="fas fa-trash"></i></button></form>
                    <?php else: ?>
                      <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#milestoneModal<?= (int)$t['id'] ?>"><i class="fas fa-eye"></i></button>
                      <a href="../forms/edit_milestone.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                      <form method="POST" action="../actions/delete_milestone.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button type="submit" class="btn btn-sm btn-danger js-confirm-delete"><i class="fas fa-trash"></i></button></form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Leader Detail Modals -->
<?php foreach ($leaders as $l): ?>
<div class="modal fade" id="leaderModal<?= (int)$l['leader_id'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= htmlspecialchars($l['full_name']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (!empty($l['photo_path'])): ?>
          <div class="text-center mb-3">
            <img src="../<?= htmlspecialchars($l['photo_path']) ?>" alt="<?= htmlspecialchars($l['full_name']) ?>" class="img-fluid rounded" style="max-height: 200px;" loading="lazy">
          </div>
        <?php endif; ?>
        <table class="table table-sm">
          <tr><th>Role</th><td><?= htmlspecialchars($l['role_type']) ?></td></tr>
          <tr><th>Conference</th><td><?= htmlspecialchars($l['conference_name'] ?? '—') ?></td></tr>
          <tr><th>Start Year</th><td><?= (int)$l['start_year'] ?: '—' ?></td></tr>
          <tr><th>End Year</th><td><?= (int)$l['end_year'] ?: '—' ?></td></tr>
        </table>
        <?php if (!empty($l['descriptions'])): ?>
          <h6>Descriptions</h6>
          <p><?= nl2br(htmlspecialchars($l['descriptions'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($l['achievements'])): ?>
          <h6>Achievements</h6>
          <p><?= nl2br(htmlspecialchars($l['achievements'])) ?></p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Milestone Detail Modals -->
<?php foreach ($milestones as $m): ?>
<div class="modal fade" id="milestoneModal<?= (int)$m['milestone_id'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= htmlspecialchars($m['title']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-sm">
          <tr><th>Year</th><td><?= (int)$m['milestone_year'] ?></td></tr>
        </table>
        <?php if (!empty($m['descriptions'])): ?>
          <h6>Descriptions</h6>
          <p><?= nl2br(htmlspecialchars($m['descriptions'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($m['achievements'])): ?>
          <h6>Achievements</h6>
          <p><?= nl2br(htmlspecialchars($m['achievements'])) ?></p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
