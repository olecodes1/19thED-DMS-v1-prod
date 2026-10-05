<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$conferences = $pdo->query("
    SELECT c.conference_id, c.conference_name, c.conference_president, c.conference_director,
           (SELECT COUNT(*) FROM areas a WHERE a.conference_id = c.conference_id) AS area_count,
           (SELECT COUNT(*) FROM churches ch WHERE ch.conference_id = c.conference_id AND ch.status='active') AS church_count,
           (SELECT COUNT(*) FROM members m WHERE m.conference_id = c.conference_id) AS member_count
    FROM conferences c
    ORDER BY c.conference_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Conferences — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-sitemap me-2"></i>Conferences</h5>
    <a href="../forms/add_conference.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Add Conference</a>
  </div>

  <?php if (isset($_GET['success'])): ?><div class="alert alert-success alert-dismissible py-2">Conference added! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['updated'])): ?><div class="alert alert-info alert-dismissible py-2">Conference updated. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?><div class="alert alert-warning py-2">Conference deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="table-success">
          <tr><th>#</th><th>Conference</th><th>President</th><th>Director</th><th>Areas</th><th>Active Churches</th><th>Members</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($conferences)): ?>
            <tr><td colspan="8" class="text-center text-muted py-3">No conferences found.</td></tr>
          <?php else: foreach ($conferences as $i => $c): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($c['conference_name']) ?></td>
              <td><?= htmlspecialchars($c['conference_president'] ?? '—') ?></td>
              <td><?= htmlspecialchars($c['conference_director'] ?? '—') ?></td>
              <td><?= $c['area_count'] ?></td>
              <td><?= $c['church_count'] ?></td>
              <td><?= $c['member_count'] ?></td>
              <td>
                <a href="../forms/edit_conference.php?id=<?= $c['conference_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="../actions/delete_conference.php" class="d-inline"><input type="hidden" name="id" value="<?= $c['conference_id'] ?>"><button type="submit" class="btn btn-danger btn-sm js-confirm-delete">Del</button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer text-muted small">Total: <?= count($conferences) ?> conference(s)</div>
  </div>
</div>

  <!-- Charts -->
  <?php
    // Prepare chart arrays from $conferences
    $chart_conf_labels = array_map(fn($r) => $r['conference_name'], $conferences);
    $chart_church_data = array_map(fn($r) => (int)$r['church_count'], $conferences);
    $chart_member_data = array_map(fn($r) => (int)$r['member_count'], $conferences);
  ?>
  <div class="row mb-3 px-4">
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:300px;">
          <h6 class="card-title small mb-2">Active Churches by Conference</h6>
          <canvas id="confChurchChart" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="row mb-3 px-4">
    <div class="col-12">
      <div class="card shadow-sm chart-card">
        <div class="card-body p-2" style="height:260px;">
          <h6 class="card-title small mb-2">Members by Conference</h6>
          <canvas id="confMemberChart" style="width:100%;height:100%;"></canvas>
        </div>
      </div>
    </div>
  </div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
const confLabels = <?= json_encode($chart_conf_labels) ?>;
const churchData = <?= json_encode($chart_church_data) ?>;
const memberData = <?= json_encode($chart_member_data) ?>;

// Bar chart - active churches
const ctxConfChurch = document.getElementById('confChurchChart');
if (ctxConfChurch) {
  new Chart(ctxConfChurch, {
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
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 0 },
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
}

// Line chart - members
const ctxConfMember = document.getElementById('confMemberChart');
if (ctxConfMember) {
  new Chart(ctxConfMember, {
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
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 0 },
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
}
</script>
</body>
</html>
