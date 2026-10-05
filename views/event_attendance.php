<?php
require_once '../includes/access_control.php';

$events = $pdo->query("SELECT event_id, event_name, event_date FROM events ORDER BY event_date DESC")->fetchAll();
$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$areas = $pdo->query("SELECT area_id, area_name, conference_id FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
$churches = $pdo->query("SELECT church_id, local_church_name, area_id, conference_id FROM churches ORDER BY local_church_name")->fetchAll();

$rows = $pdo->query("
    SELECT b.breakdown_id, e.event_name, e.event_date, c.conference_name, a.area_name, ch.local_church_name, b.attendance_count, b.notes
    FROM event_attendance_breakdowns b
    LEFT JOIN events e ON b.event_id = e.event_id
    LEFT JOIN conferences c ON b.conference_id = c.conference_id
    LEFT JOIN areas a ON b.area_id = a.area_id
    LEFT JOIN churches ch ON b.church_id = ch.church_id
    ORDER BY e.event_date DESC, e.event_name
")->fetchAll();

$bestWorst = $pdo->query("
    SELECT e.event_id, e.event_name, e.event_date, COALESCE(SUM(b.attendance_count), 0) AS total_attendance
    FROM events e
    LEFT JOIN event_attendance_breakdowns b ON b.event_id = e.event_id
    GROUP BY e.event_id, e.event_name, e.event_date
    ORDER BY total_attendance DESC
")->fetchAll();

$best = array_slice($bestWorst, 0, 3);
$worst = array_slice(array_reverse($bestWorst), 0, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Event Attendance — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4 px-4">
  <h5 class="fw-bold text-success mb-3"><i class="fas fa-user-check me-2"></i>Event Attendance Breakdown</h5>
  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success py-2">Attendance breakdown saved.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success py-2">Attendance breakdown deleted.</div><?php endif; ?>
  <?php if (isset($_GET['error'])): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Best Engagement (Top 3)</div>
        <div class="card-body small">
          <?php foreach ($best as $b): ?>
            <div><?= htmlspecialchars($b['event_name']) ?> — <strong><?= (int)$b['total_attendance'] ?></strong></div>
          <?php endforeach; if (!$best) echo '<div class="text-muted">No data.</div>'; ?>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Lowest Engagement (Bottom 3)</div>
        <div class="card-body small">
          <?php foreach ($worst as $w): ?>
            <div><?= htmlspecialchars($w['event_name']) ?> — <strong><?= (int)$w['total_attendance'] ?></strong></div>
          <?php endforeach; if (!$worst) echo '<div class="text-muted">No data.</div>'; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Add Breakdown Entry</div>
    <div class="card-body">
      <form method="POST" action="../actions/process_event_attendance.php" class="row g-2">
        <div class="col-md-3">
          <label class="form-label">Event</label>
          <select name="event_id" class="form-select form-select-sm" required>
            <option value="">-- Select --</option>
            <?php foreach ($events as $e): ?>
              <option value="<?= $e['event_id'] ?>"><?= htmlspecialchars($e['event_name'] . ' (' . $e['event_date'] . ')') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Conference</label>
          <select name="conference_id" id="attendanceConference" class="form-select form-select-sm" data-dynamic-select="true" data-choices="off">
            <option value="">-- Optional --</option>
            <?php foreach ($conferences as $c): ?>
              <option value="<?= $c['conference_id'] ?>"><?= htmlspecialchars($c['conference_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Area</label>
          <select name="area_id" id="attendanceArea" class="form-select form-select-sm" data-dynamic-select="true" data-choices="off">
            <option value="">-- Optional --</option>
            <?php foreach ($areas as $a): ?>
              <option value="<?= $a['area_id'] ?>" data-conf="<?= (int)$a['conference_id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Church</label>
          <select name="church_id" id="attendanceChurch" class="form-select form-select-sm" data-dynamic-select="true" data-choices="off">
            <option value="">-- Optional --</option>
            <?php foreach ($churches as $ch): ?>
              <option value="<?= $ch['church_id'] ?>" data-conf="<?= (int)$ch['conference_id'] ?>" data-area="<?= (int)$ch['area_id'] ?>"><?= htmlspecialchars($ch['local_church_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <label class="form-label">Count</label>
          <input type="number" min="0" name="attendance_count" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Notes</label>
          <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Save Breakdown</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-success"><tr><th>#</th><th>Event</th><th>Date</th><th>Conference</th><th>Area</th><th>Church</th><th>Attendance</th><th>Notes</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-3">No entries yet.</td></tr>
          <?php else: foreach ($rows as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($r['event_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['event_date'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['conference_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['area_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($r['local_church_name'] ?? '—') ?></td>
              <td><?= (int)$r['attendance_count'] ?></td>
              <td><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
              <td>
                <a href="../forms/edit_attendance_breakdown.php?id=<?= $r['breakdown_id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></a>
                <form method="POST" action="../actions/delete_attendance_breakdown.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this attendance breakdown?');"><input type="hidden" name="id" value="<?= $r['breakdown_id'] ?>"><button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const conf = document.getElementById('attendanceConference');
  const area = document.getElementById('attendanceArea');
  const church = document.getElementById('attendanceChurch');
  if (!conf || !area || !church) return;

  function filterAreas() {
    const confId = conf.value;
    area.querySelectorAll('option[data-conf]').forEach(function (opt) {
      opt.hidden = !!confId && opt.dataset.conf !== confId;
    });
    if (area.selectedOptions[0] && area.selectedOptions[0].hidden) {
      area.value = '';
    }
  }

  function filterChurches() {
    const confId = conf.value;
    const areaId = area.value;
    church.querySelectorAll('option[data-conf]').forEach(function (opt) {
      const confMismatch = !!confId && opt.dataset.conf !== confId;
      const areaMismatch = !!areaId && opt.dataset.area !== areaId;
      opt.hidden = confMismatch || areaMismatch;
    });
    if (church.selectedOptions[0] && church.selectedOptions[0].hidden) {
      church.value = '';
    }
  }

  conf.addEventListener('change', function () {
    filterAreas();
    filterChurches();
  });
  area.addEventListener('change', filterChurches);

  filterAreas();
  filterChurches();
});
</script>
</body>
</html>
