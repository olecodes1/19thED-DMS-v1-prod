<?php
// Public view-only page for Conferences (no CRUD)
// OPTIMIZED: Use index on conferences table for faster query
$conferences = $pdo->query("
    SELECT c.conference_id, c.conference_name, c.conference_president, c.conference_director,
           (SELECT COUNT(*) FROM areas a WHERE a.conference_id = c.conference_id) AS area_count,
           (SELECT COUNT(*) FROM churches ch WHERE ch.conference_id = c.conference_id AND ch.status='active') AS church_count,
           (SELECT COUNT(*) FROM members m WHERE m.conference_id = c.conference_id) AS member_count
    FROM conferences c
    ORDER BY c.conference_name
")->fetchAll();

$chartConfLabels = array_map(fn($r) => $r['conference_name'], $conferences);
$chartChurchData = array_map(fn($r) => (int)$r['church_count'], $conferences);
$chartMemberData = array_map(fn($r) => (int)$r['member_count'], $conferences);
?>
<h5 class="fw-bold text-success mb-1"><i class="fas fa-sitemap me-2"></i>Conferences</h5>
<p class="text-muted small mb-4">Overview of all conferences in the 19th Episcopal District.</p>

<div class="card shadow-sm mb-4">
  <div class="card-body p-0">
    <table class="table table-bordered table-hover table-sm mb-0">
      <thead class="table-success">
        <tr><th>#</th><th>Conference</th><th>President</th><th>Director</th><th>Areas</th><th>Active Churches</th><th>Members</th></tr>
      </thead>
      <tbody>
        <?php if (empty($conferences)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No conferences found.</td></tr>
        <?php else: foreach ($conferences as $i => $c): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td class="fw-semibold"><?= h($c['conference_name']) ?></td>
            <td><?= h($c['conference_president'] ?? '—') ?></td>
            <td><?= h($c['conference_director'] ?? '—') ?></td>
            <td><span class="badge bg-warning text-dark"><?= $c['area_count'] ?></span></td>
            <td><span class="badge bg-primary"><?= $c['church_count'] ?></span></td>
            <td><span class="badge bg-success"><?= $c['member_count'] ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer text-muted small">Total: <?= count($conferences) ?> conference(s)</div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold text-success">Active Churches by Conference</div>
      <div class="card-body" style="height:300px;">
        <canvas id="confChurchChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold text-success">Members by Conference</div>
      <div class="card-body" style="height:300px;">
        <canvas id="confMemberChart"></canvas>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
const confLabels = <?= json_encode($chartConfLabels) ?>;
const churchData = <?= json_encode($chartChurchData) ?>;
const memberData = <?= json_encode($chartMemberData) ?>;

new Chart(document.getElementById('confChurchChart'), {
  type: 'bar',
  data: {
    labels: confLabels,
    datasets: [{
      label: 'Active Churches',
      data: churchData,
      backgroundColor: 'rgba(54,162,235,0.6)',
      borderColor: 'rgba(54,162,235,1)',
      borderWidth: 1,
      maxBarThickness: 64
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

new Chart(document.getElementById('confMemberChart'), {
  type: 'line',
  data: {
    labels: confLabels,
    datasets: [{
      label: 'Members',
      data: memberData,
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
