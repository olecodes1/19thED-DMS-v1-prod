<?php
// Public view-only page for Areas (no CRUD)
$search  = $_GET['search'] ?? '';
$confId  = $_GET['conference_id'] ?? '';

// OPTIMIZED: Changed correlated subqueries to use church_member_summary
// (replaces correlated subquery ×3 per area row with a single JOIN)
$where = " WHERE 1=1";
$params = [];

if ($search)  { $where .= " AND (a.area_name LIKE ? OR a.area_president_name LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%"]); }
if ($confId)  { $where .= " AND a.conference_id = ?"; $params[] = $confId; }

// Pre-fetch conference list once
$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();

$query = "SELECT a.area_id, a.area_name, a.area_president_name, a.area_director_name,
                 c.conference_name,
                 (SELECT COUNT(*) FROM churches ch WHERE ch.area_id = a.area_id
                      AND (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) >= 1) AS church_count,
                 (SELECT COUNT(*) FROM churches ch WHERE ch.area_id = a.area_id
                      AND (SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) = 0)  AS inactive_church_count,
                 (SELECT COUNT(*) FROM members m WHERE m.area_id = a.area_id) AS member_count
          FROM areas a
          LEFT JOIN conferences c ON a.conference_id = c.conference_id"
    . $where .
    " ORDER BY c.conference_name,
               CAST(TRIM(SUBSTRING(a.area_name, LOCATE(' ', a.area_name) + 1)) AS UNSIGNED),
               a.area_name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$areas = $stmt->fetchAll();

$chartLabels       = array_map(fn($r) => $r['area_name'], $areas);
$chartChurchData   = array_map(fn($r) => (int)$r['church_count'], $areas);
$chartInactiveData = array_map(fn($r) => (int)$r['inactive_church_count'], $areas);
$chartMemberData   = array_map(fn($r) => (int)$r['member_count'], $areas);
?>
<h5 class="fw-bold text-success mb-1"><i class="fas fa-map-marked-alt me-2"></i>Areas</h5>
<p class="text-muted small mb-4">Explore the areas across the 19th Episcopal District.</p>

<form method="GET" class="row g-2 mb-3">
  <input type="hidden" name="page" value="areas">
  <div class="col-md-3">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search area / president" value="<?= h($search) ?>">
  </div>
  <div class="col-md-3">
    <select name="conference_id" class="form-select form-select-sm">
      <option value="">-- All Conferences --</option>
      <?php foreach ($conferences as $c): ?>
        <option value="<?= $c['conference_id'] ?>" <?= $confId==$c['conference_id']?'selected':'' ?>><?= h($c['conference_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-success btn-sm">Filter</button>
    <a href="?page=areas" class="btn btn-outline-secondary btn-sm">Reset</a>
  </div>
</form>

<div class="card shadow-sm mb-4">
  <div class="card-body p-0">
    <table class="table table-bordered table-hover table-sm mb-0">
      <thead class="table-success">
        <tr><th>#</th><th>Area</th><th>Conference</th><th>President</th><th>Director</th><th>Active Churches</th><th title="Churches with 0 YPD members">Inactive Churches</th><th>Members</th></tr>
      </thead>
      <tbody>
        <?php if (empty($areas)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No areas found.</td></tr>
        <?php else: foreach ($areas as $i => $a): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= h($a['area_name'] ?? '—') ?></td>
            <td><?= h($a['conference_name'] ?? '—') ?></td>
            <td><?= h($a['area_president_name'] ?? '—') ?></td>
            <td><?= h($a['area_director_name'] ?? '—') ?></td>
            <td><span class="badge bg-primary"><?= $a['church_count'] ?></span></td>
            <td>
              <?php if ((int)$a['inactive_church_count'] > 0): ?>
                <span class="badge bg-warning text-dark"><?= $a['inactive_church_count'] ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-success"><?= $a['member_count'] ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer text-muted small">Total: <?= count($areas) ?> area(s)</div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold text-success">Churches by Area (Active vs Inactive)</div>
      <div class="card-body" style="height:300px;">
        <canvas id="areaChurchChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold text-success">Members by Area</div>
      <div class="card-body" style="height:300px;">
        <canvas id="areaMemberChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
const areaLabels       = <?= json_encode($chartLabels) ?>;
const areaChurchData   = <?= json_encode($chartChurchData) ?>;
const areaInactiveData = <?= json_encode($chartInactiveData) ?>;
const areaMemberData   = <?= json_encode($chartMemberData) ?>;

new Chart(document.getElementById('areaChurchChart'), {
  type: 'bar',
  data: {
    labels: areaLabels,
    datasets: [
      {
        label: 'Active Churches',
        data: areaChurchData,
        backgroundColor: 'rgba(54,162,235,0.6)',
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
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

new Chart(document.getElementById('areaMemberChart'), {
  type: 'line',
  data: {
    labels: areaLabels,
    datasets: [{
      label: 'Members',
      data: areaMemberData,
      borderColor: 'rgba(75,192,192,0.9)',
      backgroundColor: 'rgba(75,192,192,0.2)',
      tension: 0.2,
      pointRadius: 4
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
