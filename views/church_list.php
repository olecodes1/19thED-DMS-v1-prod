<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

// Church list grouped by conference > area
$churches = $pdo->query("
    SELECT ch.church_id, ch.local_church_name, ch.status,
           a.area_name, c.conference_name,
           (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) AS member_count
    FROM churches ch
    LEFT JOIN areas a ON ch.area_id = a.area_id
    LEFT JOIN conferences c ON ch.conference_id = c.conference_id
    ORDER BY c.conference_name, CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED), a.area_name, ch.local_church_name
")->fetchAll();

// Group by conference > area
$grouped = [];
foreach ($churches as $ch) {
    $grouped[$ch['conference_name'] ?? 'Unassigned'][$ch['area_name'] ?? 'No Area'][] = $ch;
}

// Summary counts
$totalActive   = 0;
$totalInactive = 0;
$totalZero     = 0;
foreach ($churches as $ch) {
    if ($ch['status'] === 'active') {
        $totalActive++;
        if ((int)$ch['member_count'] === 0) $totalZero++;
    } else {
        $totalInactive++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Church List — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-list me-2"></i>Church List by Conference &amp; Area</h5>
    <a href="churches.php" class="btn btn-outline-success btn-sm"><i class="fas fa-table me-1"></i>Full Church View</a>
  </div>

  <!-- Summary Stat Cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
      <div class="card border-start border-success border-4 shadow-sm">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
          <div><div class="text-muted small">Total Churches</div><div class="fs-4 fw-bold"><?= count($churches) ?></div></div>
          <i class="fas fa-church fa-lg text-success opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card border-start border-primary border-4 shadow-sm">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
          <div><div class="text-muted small">Active</div><div class="fs-4 fw-bold text-primary"><?= $totalActive ?></div></div>
          <i class="fas fa-check-circle fa-lg text-primary opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card border-start border-secondary border-4 shadow-sm">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
          <div><div class="text-muted small">Inactive</div><div class="fs-4 fw-bold text-secondary"><?= $totalInactive ?></div></div>
          <i class="fas fa-times-circle fa-lg text-secondary opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card border-start border-warning border-4 shadow-sm">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
          <div><div class="text-muted small">Active / 0 Members</div><div class="fs-4 fw-bold text-warning"><?= $totalZero ?></div></div>
          <i class="fas fa-exclamation-triangle fa-lg text-warning opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Legend -->
  <div class="d-flex gap-3 mb-3 small text-muted align-items-center">
    <span><span class="badge bg-secondary">inactive</span> = Church marked inactive</span>
    <span><span class="badge bg-warning text-dark">0 members</span> = Active church with no YPD members</span>
  </div>

  <?php foreach ($grouped as $confName => $areas): ?>
    <h6 class="text-primary fw-semibold mt-3"><?= htmlspecialchars($confName) ?></h6>
    <?php foreach ($areas as $areaName => $chs): ?>
      <div class="ms-3 mb-3">
        <div class="text-muted small fw-semibold mb-1"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($areaName ?? 'No Area') ?></div>
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light"><tr><th>#</th><th>Church</th><th>Members</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($chs as $i => $ch): ?>
              <tr class="<?= ($ch['status'] !== 'active') ? 'table-secondary' : (((int)$ch['member_count'] === 0) ? 'table-warning' : '') ?>">
                <td><?= $i + 1 ?></td>
                <td>
                  <?= htmlspecialchars($ch['local_church_name']) ?>
                  <?php if ($ch['status'] === 'active' && (int)$ch['member_count'] === 0): ?>
                    <span class="badge bg-warning text-dark ms-1">0 members</span>
                  <?php endif; ?>
                </td>
                <td><?= $ch['member_count'] ?></td>
                <td><span class="badge bg-<?= $ch['status']==='active'?'success':'secondary' ?>"><?= htmlspecialchars($ch['status']) ?></span></td>
                <td><a href="../forms/edit_church.php?id=<?= $ch['church_id'] ?>" class="btn btn-warning btn-sm py-0 px-1">Edit</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>

  <?php if (empty($grouped)): ?>
    <p class="text-muted">No churches found.</p>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
