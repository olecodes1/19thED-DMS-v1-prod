<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/pagination.php';

// Define feature flags
$hasAttendance = true;
$hasDistrict = true;
$hasConference = true;

$search    = $_GET['search'] ?? '';
$conf_id   = $_GET['conference_id'] ?? '';
$dist_id   = $_GET['episcopal_district_id'] ?? '';

$fromWhere = " FROM events e
              LEFT JOIN conferences c ON c.conference_id = e.conference_id
              LEFT JOIN episcopal_districts ed ON ed.district_id = e.episcopal_district_id";
$fromWhere .= " WHERE 1=1";
$params = [];
if ($search) { $fromWhere .= " AND (e.event_name LIKE ? OR e.location LIKE ?)"; $params = ["%$search%", "%$search%"]; }
if ($conf_id) { $fromWhere .= " AND e.conference_id = ?"; $params[] = $conf_id; }
if ($dist_id)  { $fromWhere .= " AND e.episcopal_district_id = ?"; $params[] = $dist_id; }

$pager = paginate($pdo, "SELECT COUNT(*)" . $fromWhere, $params, 20);
$selectCols = "e.*, c.conference_name, ed.district_name";
$query  = "SELECT $selectCols" . $fromWhere . " ORDER BY e.event_date DESC LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

$sumSql = "SELECT COUNT(*) AS total_events"
    . ($hasAttendance ? ", COALESCE(SUM(e.attendance_count),0) AS total_attendance" : ", 0 AS total_attendance")
    . $fromWhere;
$sumStmt = $pdo->prepare($sumSql);
$sumStmt->execute($params);
$summary = $sumStmt->fetch();

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$districts   = $pdo->query("SELECT district_id, district_name FROM episcopal_districts ORDER BY district_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Events — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-calendar-alt me-2"></i>Events</h5>
    <div class="d-flex gap-2">
      <a href="event_attendance.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-check me-1"></i>Attendance Breakdown</a>
      <a href="../forms/add_event.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Add Event</a>
    </div>
  </div>
  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?><div class="alert alert-warning py-2">Event deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div><?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Events</div><div class="fs-4 fw-bold"><?= (int)$summary['total_events'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Attendance</div><div class="fs-4 fw-bold"><?= (int)$summary['total_attendance'] ?></div></div></div></div>
  </div>

  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search event / location" value="<?= htmlspecialchars($search) ?>">
    </div>
    <?php if ($hasDistrict): ?>
    <div class="col-md-3">
      <select name="episcopal_district_id" class="form-select form-select-sm">
        <option value="">-- Episcopal District --</option>
        <?php foreach ($districts as $d): ?>
          <option value="<?= $d['district_id'] ?>" <?= (string)$dist_id === (string)$d['district_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['district_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <?php if ($hasConference): ?>
    <div class="col-md-3">
      <select name="conference_id" class="form-select form-select-sm">
        <option value="">-- Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= (string)$conf_id === (string)$c['conference_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
      <a href="events.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="table-success">
          <tr>
            <th>#</th>
            <th>Event</th>
            <?php if ($hasDistrict): ?><th>Episcopal District</th><?php endif; ?>
            <?php if ($hasConference): ?><th>Conference</th><?php endif; ?>
            <th>Date</th>
            <th>Location</th>
            <?php if ($hasAttendance): ?><th>Attendance</th><?php endif; ?>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($events)): ?>
            <tr><td colspan="<?= 6 + ($hasDistrict ? 1 : 0) + ($hasConference ? 1 : 0) + ($hasAttendance ? 1 : 0) ?>" class="text-center text-muted py-3">No events found.</td></tr>
          <?php else: foreach ($events as $i => $e): ?>
            <tr>
              <td><?= $pager['offset'] + $i + 1 ?></td>
              <td><?= htmlspecialchars($e['event_name']) ?></td>
              <?php if ($hasDistrict): ?><td><?= htmlspecialchars($e['district_name'] ?? '19th Episcopal District') ?></td><?php endif; ?>
              <?php if ($hasConference): ?><td><?= htmlspecialchars($e['conference_name'] ?? 'General') ?></td><?php endif; ?>
              <td><?= htmlspecialchars($e['event_date']) ?></td>
              <td><?= htmlspecialchars($e['location'] ?? '—') ?></td>
              <?php if ($hasAttendance): ?><td><?= (int)($e['attendance_count'] ?? 0) ?></td><?php endif; ?>
              <td><?= htmlspecialchars(mb_strimwidth($e['description'] ?? '', 0, 60, '…')) ?></td>
              <td>
                <a href="../forms/edit_event.php?id=<?= $e['event_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="../actions/delete_event.php" class="d-inline"><input type="hidden" name="id" value="<?= $e['event_id'] ?>"><button type="submit" class="btn btn-danger btn-sm js-confirm-delete">Del</button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span class="text-muted small">Total: <?= $pager['total'] ?> event(s)</span>
      <?= render_pagination($pager) ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
