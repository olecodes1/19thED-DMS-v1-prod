<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/pagination.php';
require_once '../includes/reference_data.php';

$conf_id = $_GET['conference_id'] ?? '';
$search  = $_GET['search'] ?? '';

$fromWhere = " FROM areas a
               LEFT JOIN conferences c ON a.conference_id = c.conference_id
               WHERE 1=1";
$params = [];

if ($search)  { $fromWhere .= " AND (a.area_name LIKE ? OR a.area_president_name LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
if ($conf_id) { $fromWhere .= " AND a.conference_id = ?"; $params[] = $conf_id; }

$pager = paginate($pdo, "SELECT COUNT(*)" . $fromWhere, $params, 20);

// Numeric sort: extract the leading integer after the first space in area_name (e.g. "Area 1" → 1, "Area 12" → 12)
$query = "SELECT a.area_id, a.area_name, a.area_president_name, a.area_director_name,
                 c.conference_name,
                 (SELECT COUNT(*) FROM churches ch WHERE ch.area_id = a.area_id
                      AND (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) >= 1) AS church_count,
                 (SELECT COUNT(*) FROM churches ch WHERE ch.area_id = a.area_id
                      AND (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) = 0)  AS inactive_church_count,
                 (SELECT COUNT(*) FROM members m WHERE m.area_id = a.area_id) AS member_count"
    . $fromWhere
    . " ORDER BY c.conference_name,
                 CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED),
                 a.area_name
        LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$areas = $stmt->fetchAll();

$conferences = get_conferences($pdo);

// Chart data — all areas sorted numerically (not paginated), for full chart view
$chartStmt = $pdo->prepare(
    "SELECT a.area_name,
            (SELECT COUNT(*) FROM churches ch WHERE ch.area_id = a.area_id
                 AND (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) >= 1) AS church_count,
            (SELECT COUNT(*) FROM churches ch WHERE ch.area_id = a.area_id
                 AND (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) = 0)  AS inactive_church_count,
            (SELECT COUNT(*) FROM members m WHERE m.area_id = a.area_id) AS member_count
     FROM areas a
     LEFT JOIN conferences c ON a.conference_id = c.conference_id
     ORDER BY c.conference_name,
              CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED),
              a.area_name"
);
$chartStmt->execute();
$chartRows = $chartStmt->fetchAll();

$chart_area_labels   = array_map(fn($r) => $r['area_name'], $chartRows);
$chart_church_data   = array_map(fn($r) => (int)$r['church_count'], $chartRows);
$chart_inactive_data = array_map(fn($r) => (int)$r['inactive_church_count'], $chartRows);
$chart_member_data   = array_map(fn($r) => (int)$r['member_count'], $chartRows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Areas — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-map-marked-alt me-2"></i>Areas</h5>
    <div class="d-flex gap-2">
      <a href="../actions/export.php?<?= http_build_query(['type' => 'areas', 'conference_id' => $conf_id]) ?>" class="btn btn-outline-success btn-sm js-confirm-export"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
      <a href="../forms/add_area.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Add Area</a>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?><div class="alert alert-success alert-dismissible py-2">Area added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['updated'])): ?><div class="alert alert-info alert-dismissible py-2">Area updated. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?><div class="alert alert-warning py-2">Area deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>

  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search area / president" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3">
      <select name="conference_id" class="form-select form-select-sm">
        <option value="">-- Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $conf_id==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="areas.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="table-success">
          <tr>
            <th>#</th>
            <th>Area</th>
            <th>Conference</th>
            <th>President</th>
            <th>Director</th>
            <th>Active Churches</th>
            <th title="Churches with 0 YPD members">Inactive Churches</th>
            <th>Members</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($areas)): ?>
            <tr><td colspan="9" class="text-center text-muted py-3">No areas found.</td></tr>
          <?php else: foreach ($areas as $i => $a): ?>
            <tr>
              <td><?= $pager['offset'] + $i + 1 ?></td>
              <td><?= htmlspecialchars($a['area_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($a['conference_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($a['area_president_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($a['area_director_name'] ?? '—') ?></td>
              <td><?= $a['church_count'] ?></td>
              <td>
                <?php if ((int)$a['inactive_church_count'] > 0): ?>
                  <a href="churches.php?area_id=<?= $a['area_id'] ?>&activity=inactive" class="badge bg-warning text-dark text-decoration-none" title="View inactive churches">
                    <?= $a['inactive_church_count'] ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= $a['member_count'] ?></td>
              <td>
                <a href="../forms/edit_area.php?id=<?= $a['area_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="../actions/delete_area.php" class="d-inline"><input type="hidden" name="id" value="<?= $a['area_id'] ?>"><button type="submit" class="btn btn-danger btn-sm js-confirm-delete">Del</button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span class="text-muted small">Total: <?= $pager['total'] ?> area(s)</span>
      <?= render_pagination($pager) ?>
    </div>
  </div>
</div>

<div class="container-fluid px-4">
  <div class="row mb-3 mt-3">
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:300px;">
          <h6 class="card-title small mb-2">Churches by Area (Active vs Inactive)</h6>
          <canvas id="areaChurchChart" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:260px;">
          <h6 class="card-title small mb-2">Members by Area</h6>
          <canvas id="areaMemberChart" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
const areaLabels       = <?= json_encode($chart_area_labels) ?>;
const areaChurchData   = <?= json_encode($chart_church_data) ?>;
const areaInactiveData = <?= json_encode($chart_inactive_data) ?>;
const areaMemberData   = <?= json_encode($chart_member_data) ?>;

const areaChurchCtx = document.getElementById('areaChurchChart');
if (areaChurchCtx) {
  new Chart(areaChurchCtx, {
    type: 'bar',
    data: {
      labels: areaLabels,
      datasets: [
        {
          label: 'Active Churches',
          data: areaChurchData,
          backgroundColor: 'rgba(54,162,235,0.7)',
          borderColor: 'rgba(54,162,235,1)',
          borderWidth: 1,
          maxBarThickness: 32
        },
        {
          label: 'Inactive (0 members)',
          data: areaInactiveData,
          backgroundColor: 'rgba(255,159,64,0.7)',
          borderColor: 'rgba(255,159,64,1)',
          borderWidth: 1,
          maxBarThickness: 32
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 0 },
      plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
      scales: {
        x: { display: true, ticks: { font: { size: 10 }, maxRotation: 45 } },
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
}

const areaMemberCtx = document.getElementById('areaMemberChart');
if (areaMemberCtx) {
  new Chart(areaMemberCtx, {
    type: 'line',
    data: {
      labels: areaLabels,
      datasets: [{
        label: 'Members',
        data: areaMemberData,
        borderColor: 'rgba(75,192,192,0.9)',
        backgroundColor: 'rgba(75,192,192,0.2)',
        tension: 0.2,
        pointRadius: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 0 },
      plugins: { legend: { display: false } },
      scales: {
        x: { display: true, ticks: { font: { size: 10 }, maxRotation: 45 } },
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
}
</script>
</body>
</html>
