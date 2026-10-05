<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) { 
    header("Location: ../views/event_attendance.php"); 
    exit; 
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM event_attendance_breakdowns WHERE breakdown_id = ?");
$stmt->execute([$id]);
$breakdown = $stmt->fetch();

if (!$breakdown) { 
    header("Location: ../views/event_attendance.php"); 
    exit; 
}

$events = $pdo->query("SELECT event_id, event_name, event_date FROM events ORDER BY event_date DESC")->fetchAll();
$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$areas = $pdo->query("SELECT area_id, area_name, conference_id FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
$churches = $pdo->query("SELECT church_id, local_church_name, area_id, conference_id FROM churches ORDER BY local_church_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Attendance Breakdown — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:900px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-user-edit me-2"></i>Edit Attendance Breakdown</h5>

  <form method="POST" action="../actions/update_attendance_breakdown.php">
    <input type="hidden" name="breakdown_id" value="<?= $breakdown['breakdown_id'] ?>">

    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Event <span class="text-danger">*</span></label>
        <select name="event_id" class="form-select" required>
          <option value="">-- Select --</option>
          <?php foreach ($events as $e): ?>
            <option value="<?= $e['event_id'] ?>" <?= (string)$breakdown['event_id'] === (string)$e['event_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['event_name'] . ' (' . $e['event_date'] . ')') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Conference</label>
        <select name="conference_id" id="editAttendanceConference" class="form-select" data-dynamic-select="true" data-choices="off">
          <option value="">-- Optional --</option>
          <?php foreach ($conferences as $c): ?>
            <option value="<?= $c['conference_id'] ?>" <?= (string)($breakdown['conference_id'] ?? '') === (string)$c['conference_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['conference_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Area</label>
        <select name="area_id" id="editAttendanceArea" class="form-select" data-dynamic-select="true" data-choices="off">
          <option value="">-- Optional --</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['area_id'] ?>" data-conf="<?= (int)$a['conference_id'] ?>" <?= (string)($breakdown['area_id'] ?? '') === (string)$a['area_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['area_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Church</label>
        <select name="church_id" id="editAttendanceChurch" class="form-select" data-dynamic-select="true" data-choices="off">
          <option value="">-- Optional --</option>
          <?php foreach ($churches as $ch): ?>
            <option value="<?= $ch['church_id'] ?>" data-conf="<?= (int)$ch['conference_id'] ?>" data-area="<?= (int)$ch['area_id'] ?>" <?= (string)($breakdown['church_id'] ?? '') === (string)$ch['church_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($ch['local_church_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">Count <span class="text-danger">*</span></label>
        <input type="number" min="0" name="attendance_count" class="form-control" value="<?= (int)$breakdown['attendance_count'] ?>" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($breakdown['notes'] ?? '') ?>" placeholder="Optional">
      </div>
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Breakdown</button>
    <a href="../views/event_attendance.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const conf = document.getElementById('editAttendanceConference');
  const area = document.getElementById('editAttendanceArea');
  const church = document.getElementById('editAttendanceChurch');
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