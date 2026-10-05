<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/pagination.php';
require_once '../includes/reference_data.php';

$search   = $_GET['search']   ?? '';
$area_id  = $_GET['area_id']  ?? '';
$activity = $_GET['activity'] ?? '';   // 'active' = ≥1 member, 'inactive' = 0 members
$conf_id  = $_GET['conference_id'] ?? '';

// OPTIMIZED with pre-aggregated summary table (replaces correlated subquery)
$baseSelect = "SELECT ch.church_id, ch.local_church_name, ch.local_church_president_name, ch.local_church_director_name,
                      ch.status, a.area_name, c.conference_name,
                      COALESCE(cms.member_count, 0) AS member_count";

// Pre-aggregate member counts per church once (replaces correlated subquery)

$fromWhere = " FROM churches ch
               LEFT JOIN areas a ON ch.area_id = a.area_id
               LEFT JOIN conferences c ON ch.conference_id = c.conference_id
               LEFT JOIN (
                   SELECT church_id, COUNT(*) AS member_count
                   FROM members
                   WHERE deleted_at IS NULL
                   GROUP BY church_id
               ) cms ON cms.church_id = ch.church_id
               WHERE 1=1";
$params = [];

if ($search)   { $fromWhere .= " AND (ch.local_church_name LIKE ? OR ch.local_church_president_name LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
if ($area_id)  { $fromWhere .= " AND ch.area_id = ?";       $params[] = $area_id; }
if ($conf_id)  { $fromWhere .= " AND ch.conference_id = ?"; $params[] = $conf_id; }

// Activity filter — no correlated subquery
if ($activity === 'active') {
    $fromWhere .= " AND cms.member_count >= 1";
} elseif ($activity === 'inactive') {
    $fromWhere .= " AND COALESCE(cms.member_count, 0) = 0";
}

// Count query (for pagination) — simple
$pager = paginate($pdo, "SELECT COUNT(*) FROM ($baseSelect $fromWhere ORDER BY ch.church_id) _sub", $params, 20);

// Main query — uses pre-aggregated member_count
$query = $baseSelect . $fromWhere
    . " ORDER BY c.conference_name, CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED), a.area_name, ch.local_church_name
        LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$churches = $stmt->fetchAll();

$areas       = get_areas($pdo);
$conferences = get_conferences($pdo);

// ── Summary counts — single aggregate query ───────────────────────────────
$summaryRow = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM churches) AS total_all,
        SUM(CASE WHEN cms.member_count >= 1 THEN 1 ELSE 0 END) AS total_active,
        SUM(CASE WHEN cms.member_count = 0 THEN 1 ELSE 0 END) AS total_inactive
    FROM churches ch
    LEFT JOIN (
        SELECT church_id, COUNT(*) AS member_count
        FROM members
        WHERE deleted_at IS NULL
        GROUP BY church_id
    ) cms ON cms.church_id = ch.church_id
")->fetch();
$totalAll      = (int)($summaryRow['total_all']      ?? 0);
$totalActive   = (int)($summaryRow['total_active']   ?? 0);
$totalInactive = (int)($summaryRow['total_inactive'] ?? 0);

// ── Inactive churches — single query using pre-aggregated summary ──────────
$inactiveChurches = $pdo->query("
    SELECT ch.church_id, ch.local_church_name, ch.status,
           a.area_name, c.conference_name,
           COALESCE(cms.member_count, 0) AS member_count
    FROM churches ch
    LEFT JOIN areas a  ON ch.area_id  = a.area_id
    LEFT JOIN conferences c ON ch.conference_id = c.conference_id
    LEFT JOIN (
        SELECT church_id, COUNT(*) AS member_count
        FROM members
        WHERE deleted_at IS NULL
        GROUP BY church_id
    ) cms ON cms.church_id = ch.church_id
    WHERE COALESCE(cms.member_count, 0) = 0
    ORDER BY c.conference_name, CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED), a.area_name, ch.local_church_name
")->fetchAll();

// ── Chart data ─────────────────────────────────────────────────────────────
// Churches per conference
$churches_by_conference = $pdo->query(
    "SELECT COALESCE(c.conference_name, 'Unassigned') AS conference_name, COUNT(ch.church_id) AS cnt
     FROM churches ch
     LEFT JOIN conferences c ON ch.conference_id = c.conference_id
     GROUP BY c.conference_id, c.conference_name
     ORDER BY cnt DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Active vs inactive (member-count based) for doughnut
$chart_status_labels = ['Active (≥1 member)', 'Inactive (0 members)'];
$chart_status_data   = [$totalActive, $totalInactive];

$chart_conf_labels = array_column($churches_by_conference, 'conference_name');
$chart_conf_data   = array_map(fn($r)=> (int)$r['cnt'], $churches_by_conference);

// All churches member count for bar chart — capped at 50 for perf
$all_churches_rows = $pdo->query(
    "SELECT ch.local_church_name AS name,
            COALESCE(cms.member_count, 0) AS member_count
     FROM churches ch
     LEFT JOIN (
         SELECT church_id, COUNT(*) AS member_count
         FROM members
         WHERE deleted_at IS NULL
         GROUP BY church_id
     ) cms ON cms.church_id = ch.church_id
     ORDER BY ch.local_church_name ASC
     LIMIT 50"
)->fetchAll(PDO::FETCH_ASSOC);

$chart_all_labels = array_map(fn($r)=> $r['name'], $all_churches_rows);
$chart_all_data   = array_map(fn($r)=> (int)$r['member_count'], $all_churches_rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Churches — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-church me-2"></i>Churches</h5>
    <div class="d-flex gap-2">
      <a href="../actions/export.php?<?= http_build_query(['type' => 'churches', 'conference_id' => $conf_id]) ?>" class="btn btn-outline-success btn-sm js-confirm-export"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
      <a href="../forms/add_church.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Add Church</a>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?><div class="alert alert-success alert-dismissible py-2">Church added! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['updated'])): ?><div class="alert alert-info alert-dismissible py-2">Church updated. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?><div class="alert alert-warning py-2">Church deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>

  <!-- Summary Stat Cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-4 col-sm-6">
      <a href="churches.php" class="text-decoration-none">
        <div class="card border-start border-success border-4 shadow-sm">
          <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">All Churches</div>
              <div class="fs-4 fw-bold"><?= $totalAll ?></div>
            </div>
            <i class="fas fa-church fa-lg text-success opacity-50"></i>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4 col-sm-6">
      <a href="churches.php?activity=active" class="text-decoration-none">
        <div class="card border-start border-primary border-4 shadow-sm <?= $activity === 'active' ? 'border-3' : '' ?>">
          <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">Active <span class="fw-normal text-muted" style="font-size:0.72rem;">(≥1 YPD member)</span></div>
              <div class="fs-4 fw-bold text-primary"><?= $totalActive ?></div>
            </div>
            <i class="fas fa-check-circle fa-lg text-primary opacity-50"></i>
          </div>
        </div>
      </a>
    </div>
    <div class="col-md-4 col-sm-6">
      <a href="churches.php?activity=inactive" class="text-decoration-none">
        <div class="card border-start border-warning border-4 shadow-sm <?= $activity === 'inactive' ? 'border-3' : '' ?>" data-bs-toggle="collapse" data-bs-target="#inactivePanel">
          <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">Inactive <span class="fw-normal text-muted" style="font-size:0.72rem;">(0 YPD members)</span></div>
              <div class="fs-4 fw-bold text-warning"><?= $totalInactive ?></div>
            </div>
            <i class="fas fa-exclamation-triangle fa-lg text-warning opacity-50"></i>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Collapsible: Inactive Churches Detail Panel -->
  <?php if (!empty($inactiveChurches)): ?>
  <div class="collapse mb-3 <?= $activity === 'inactive' ? 'show' : '' ?>" id="inactivePanel">
    <div class="card border-warning shadow-sm">
      <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-2">
        <span><i class="fas fa-exclamation-triangle me-1"></i>Inactive Churches — 0 YPD Members (<?= count($inactiveChurches) ?>)</span>
        <button class="btn btn-sm btn-outline-dark" data-bs-toggle="collapse" data-bs-target="#inactivePanel">Close</button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light"><tr><th>#</th><th>Church</th><th>Conference</th><th>Area</th><th>DB Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($inactiveChurches as $i => $ic): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($ic['local_church_name']) ?></td>
                  <td><?= htmlspecialchars($ic['conference_name'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($ic['area_name'] ?? '—') ?></td>
                  <td><span class="badge bg-<?= $ic['status']==='active'?'success':'secondary' ?>"><?= htmlspecialchars($ic['status']) ?></span></td>
                  <td><a href="../forms/edit_church.php?id=<?= $ic['church_id'] ?>" class="btn btn-warning btn-sm py-0 px-1">Edit</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search church / president" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
      <select name="area_id" class="form-select form-select-sm">
        <option value="">-- Area --</option>
        <?php foreach ($areas as $a): ?>
          <option value="<?= $a['area_id'] ?>" <?= $area_id==$a['area_id']?'selected':'' ?>><?= htmlspecialchars($a['area_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="conference_id" class="form-select form-select-sm">
        <option value="">-- Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $conf_id==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="activity" class="form-select form-select-sm">
        <option value="">-- Activity --</option>
        <option value="active"   <?= $activity==='active'  ?'selected':'' ?>>Active (≥1 member)</option>
        <option value="inactive" <?= $activity==='inactive'?'selected':'' ?>>Inactive (0 members)</option>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="churches.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="table-success">
          <tr><th>#</th><th>Church</th><th>Conference</th><th>Area</th><th>President</th><th>Director</th><th>Members</th><th>Activity</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($churches)): ?>
            <tr><td colspan="9" class="text-center text-muted py-3">No churches found.</td></tr>
          <?php else: foreach ($churches as $i => $ch):
            $isActive = (int)$ch['member_count'] >= 1;
          ?>
            <tr class="<?= !$isActive ? 'table-warning' : '' ?>">
              <td><?= $pager['offset'] + $i + 1 ?></td>
              <td><?= htmlspecialchars($ch['local_church_name']) ?></td>
              <td><?= htmlspecialchars($ch['conference_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($ch['area_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($ch['local_church_president_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($ch['local_church_director_name'] ?? '—') ?></td>
              <td><?= $ch['member_count'] ?></td>
              <td>
                <?php if ($isActive): ?>
                  <span class="badge bg-success">Active</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="../forms/edit_church.php?id=<?= $ch['church_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="../actions/delete_church.php" class="d-inline"><input type="hidden" name="id" value="<?= $ch['church_id'] ?>"><button type="submit" class="btn btn-danger btn-sm js-confirm-delete">Del</button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span class="text-muted small">
        Total: <?= $pager['total'] ?> church(es)
        <?php if (!$activity): ?> — <span class="text-primary"><?= $totalActive ?> active</span>, <span class="text-warning"><?= $totalInactive ?> inactive</span><?php endif; ?>
      </span>
      <?= render_pagination($pager) ?>
    </div>
  </div>
</div>

<div class="container-fluid px-4 mt-3">
  <div class="row mb-3">
    <div class="col-md-6">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:220px;">
          <h6 class="card-title small mb-2">Churches by Conference</h6>
          <canvas id="chartConf" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:220px;">
          <h6 class="card-title small mb-2">Active vs Inactive Churches</h6>
          <canvas id="chartStatus" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:300px;">
          <h6 class="card-title small mb-2">All Churches — YPD Member Count <small class="text-muted">(orange = inactive)</small></h6>
          <canvas id="chartAllChurches" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
// Churches by conference bar chart
const confLabels = <?= json_encode($chart_conf_labels) ?>;
const confData   = <?= json_encode($chart_conf_data) ?>;
const ctxConf = document.getElementById('chartConf');
if (ctxConf) {
  new Chart(ctxConf, {
    type: 'bar',
    data: {
      labels: confLabels,
      datasets: [{
        label: 'Churches',
        data: confData,
        backgroundColor: 'rgba(54,162,235,0.6)',
        borderColor: 'rgba(54,162,235,1)',
        borderWidth: 1,
        maxBarThickness: 56
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, animation: { duration: 0 },
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
}

// Active vs Inactive doughnut chart
const ctxStatus = document.getElementById('chartStatus');
if (ctxStatus) {
  new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
      labels: ['Active (≥1 member)', 'Inactive (0 members)'],
      datasets: [{
        data: [<?= $totalActive ?>, <?= $totalInactive ?>],
        backgroundColor: ['rgba(25,135,84,0.8)', 'rgba(255,193,7,0.85)'],
        borderWidth: 2
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, animation: { duration: 0 },
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    }
  });
}

// All churches member count — orange if 0
const allLabels = <?= json_encode($chart_all_labels) ?>;
const allData   = <?= json_encode($chart_all_data) ?>;
const ctxAll = document.getElementById('chartAllChurches');
if (ctxAll) {
  new Chart(ctxAll, {
    type: 'bar',
    data: {
      labels: allLabels,
      datasets: [{
        label: 'Members',
        data: allData,
        backgroundColor: allData.map(v => v === 0 ? 'rgba(255,193,7,0.85)' : 'rgba(75,192,192,0.65)'),
        borderColor:     allData.map(v => v === 0 ? 'rgba(255,193,7,1)'    : 'rgba(75,192,192,1)'),
        borderWidth: 1,
        maxBarThickness: 20
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, animation: { duration: 0 },
      scales: {
        x: { display: false },
        y: { beginAtZero: true, ticks: { precision: 0 } }
      },
      plugins: {
        legend: { display: false },
        tooltip: { mode: 'index', intersect: false }
      }
    }
  });
}
</script>
</body>
</html>
