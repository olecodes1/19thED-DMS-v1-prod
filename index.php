<?php
// Load error handler first (before anything else)
require_once 'includes/error_handler.php';
require_once 'includes/url_helper.php';

require_once 'includes/access_control.php';
require_once 'includes/auth.php';
require_auth();

// Seeded/default accounts must change their password before using the app (§1.7)
if (must_change_password()) {
    header('Location: ' . base_url('forms/change_password.php?required=1'));
    exit;
}

try {
    $districtCounts = safeQuery($pdo, "
        SELECT
            (SELECT COUNT(*) FROM members WHERE deleted_at IS NULL) AS total_members,
            (SELECT COUNT(*) FROM areas) AS total_areas,
            (SELECT COUNT(*) FROM conferences) AS total_confs,
            (SELECT COUNT(*) FROM events) AS total_events,
            COUNT(ch.church_id) AS total_churches,
            COALESCE(SUM(cms.member_count >= 1), 0) AS active_churches,
            COALESCE(SUM(COALESCE(cms.member_count, 0) = 0), 0) AS inactive_churches
        FROM churches ch
        LEFT JOIN (
            SELECT church_id, COUNT(*) AS member_count
            FROM members WHERE deleted_at IS NULL GROUP BY church_id
        ) cms ON cms.church_id = ch.church_id
    ")->fetch();
    $totalMembers = (int)$districtCounts['total_members'];
    $totalChurches = (int)$districtCounts['active_churches'];
    $totalInactiveChurch = (int)$districtCounts['inactive_churches'];
    $totalAreas = (int)$districtCounts['total_areas'];
    $totalConfs = (int)$districtCounts['total_confs'];
    $totalEvents = (int)$districtCounts['total_events'];

    $memberStats = safeQuery($pdo, "
        SELECT
            SUM(component = 'MB') AS MB,
            SUM(component = 'AS') AS AS_,
            SUM(component = 'Y')  AS Y,
            SUM(component = 'YA') AS YA
            , SUM(gender = 'M') AS Male,
            SUM(gender = 'F') AS Female,
            SUM(joined_ypd = 'Yes') AS ypd_yes,
            SUM(joined_ypd = 'No' OR joined_ypd IS NULL) AS ypd_no,
            SUM(full_member_of_church = 'Yes') AS full_yes,
            SUM(full_member_of_church = 'No' OR full_member_of_church IS NULL) AS full_no
        FROM members WHERE deleted_at IS NULL
    ")->fetch();
    $components = $memberStats;
    $genders = $memberStats;

    $eventAttendance = safeQuery($pdo, "
        SELECT e.event_name, COALESCE(SUM(b.attendance_count), 0) AS total_attendance
        FROM events e
        LEFT JOIN event_attendance_breakdowns b ON b.event_id = e.event_id
        GROUP BY e.event_id, e.event_name
        ORDER BY total_attendance DESC, e.event_name
    ")->fetchAll();
    $bestEvent = $eventAttendance[0] ?? ['event_name' => 'N/A', 'total_attendance' => 0];
    $lowestEvent = $eventAttendance ? $eventAttendance[count($eventAttendance) - 1] : ['event_name' => 'N/A', 'total_attendance' => 0];

    $latestMedia = safeQuery($pdo, "
        SELECT title, media_type, uploaded_at
        FROM media_items
        WHERE deleted_at IS NULL
        ORDER BY uploaded_at DESC
        LIMIT 3
    ")->fetchAll();

    $latestStories = safeQuery($pdo, "
        SELECT title, slug, status, created_at
        FROM story_pages
        WHERE deleted_at IS NULL
        ORDER BY created_at DESC
        LIMIT 3
    ")->fetchAll();

    // New field stats (use config-based feature flags to avoid expensive information_schema queries)
    $hasJoinedYpd  = $config['features']['joined_ypd'];
    $hasFullChurch = $config['features']['full_member_of_church'];
    $hasOccStatus  = $config['features']['occupational_status'];

    $ypdYes = $hasJoinedYpd ? (int)$memberStats['ypd_yes'] : 0;
    $ypdNo = $hasJoinedYpd ? (int)$memberStats['ypd_no'] : 0;
    $fullYes = $hasFullChurch ? (int)$memberStats['full_yes'] : 0;
    $fullNo = $hasFullChurch ? (int)$memberStats['full_no'] : 0;

    $occRows = [];
    if ($hasOccStatus) {
        $occRows = safeQuery($pdo, "
            SELECT occupational_status, COUNT(*) AS cnt
            FROM members
            WHERE occupational_status IS NOT NULL AND occupational_status != ''
            GROUP BY occupational_status
            ORDER BY cnt DESC
            LIMIT 7
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    logError("Error fetching dashboard stats: " . $e->getMessage(), "DASHBOARD");
    // Set default values to prevent page break
    $totalMembers = 0;
    $totalChurches = 0;
    $totalInactiveChurch = 0;
    $totalAreas = 0;
    $totalConfs = 0;
    $totalEvents = 0;
    $components = ['MB' => 0, 'AS_' => 0, 'Y' => 0, 'YA' => 0];
    $genders = ['Male' => 0, 'Female' => 0];
    $bestEvent = ['event_name' => 'N/A', 'total_attendance' => 0];
    $lowestEvent = ['event_name' => 'N/A', 'total_attendance' => 0];
    $latestMedia = [];
    $latestStories = [];
    $hasJoinedYpd = false;
    $hasFullChurch = false;
    $hasOccStatus = false;
    $ypdYes = 0;
    $ypdNo = 0;
    $fullYes = 0;
    $fullNo = 0;
    $occRows = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>19th Episcopal District — Admin Dashboard</title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="">
  <link rel="icon" type="image/svg+xml" href="">
  <link rel="icon" type="image/png" href="<?= base_url('19thDistrict.png') ?>">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= asset_url('assets/css/styles.css') ?>">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <h4 class="mb-1 fw-bold text-success">Dashboard Overview</h4>
  <p class="text-muted small mb-4">19th Episcopal District — YPD Statistics</p>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">

    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-success border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Members</div>
            <div class="fs-3 fw-bold"><?= $totalMembers ?></div>
          </div>
          <i class="fas fa-users fa-2x text-success opacity-50"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-primary border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Active Churches</div>
            <div class="fs-3 fw-bold"><?= $totalChurches ?></div>
            <div class="text-muted" style="font-size:0.72rem;">≥1 YPD member</div>
          </div>
          <i class="fas fa-church fa-2x text-primary opacity-50"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
      <a href="views/churches.php?activity=inactive" class="text-decoration-none">
        <div class="card border-start border-warning border-4 shadow-sm h-100">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted small">Inactive Churches</div>
              <div class="fs-3 fw-bold text-warning"><?= $totalInactiveChurch ?></div>
              <div class="text-muted" style="font-size:0.72rem;">0 YPD members</div>
            </div>
            <i class="fas fa-church fa-2x text-warning opacity-40"></i>
          </div>
        </div>
      </a>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-warning border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Areas</div>
            <div class="fs-3 fw-bold"><?= $totalAreas ?></div>
          </div>
          <i class="fas fa-map-marked-alt fa-2x text-warning opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-info border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Conferences</div>
            <div class="fs-3 fw-bold"><?= $totalConfs ?></div>
          </div>
          <i class="fas fa-sitemap fa-2x text-info opacity-50"></i>
        </div>
      </div>
    </div>

    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-danger border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Events</div>
            <div class="fs-3 fw-bold"><?= $totalEvents ?></div>
          </div>
          <i class="fas fa-calendar-alt fa-2x text-danger opacity-50"></i>
        </div>
      </div>
    </div>

  </div><!-- /Stats Cards -->

  <div class="row g-3 mb-4">
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Attendance Snapshot</div>
        <div class="card-body small">
          <div class="mb-2"><span class="text-muted">Top event:</span> <strong><?= htmlspecialchars($bestEvent['event_name'] ?? '—') ?></strong> (<?= (int)($bestEvent['total_attendance'] ?? 0) ?>)</div>
          <div><span class="text-muted">Lowest event:</span> <strong><?= htmlspecialchars($lowestEvent['event_name'] ?? '—') ?></strong> (<?= (int)($lowestEvent['total_attendance'] ?? 0) ?>)</div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Latest Media Uploads</div>
        <div class="card-body small">
          <?php if (!$latestMedia): ?>
            <div class="text-muted">No media uploads yet.</div>
          <?php else: foreach ($latestMedia as $m): ?>
            <div class="mb-1"><strong><?= htmlspecialchars($m['title']) ?></strong> <span class="text-muted">(<?= htmlspecialchars($m['media_type']) ?>)</span></div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Latest Story Pages</div>
        <div class="card-body small">
          <?php if (!$latestStories): ?>
            <div class="text-muted">No stories yet.</div>
          <?php else: foreach ($latestStories as $s): ?>
            <div class="mb-1">
              <a href="views/story_page.php?slug=<?= urlencode($s['slug']) ?>" class="text-decoration-none"><?= htmlspecialchars($s['title']) ?></a>
              <span class="badge bg-<?= ($s['status'] ?? 'draft') === 'published' ? 'success' : 'secondary' ?> ms-1"><?= htmlspecialchars($s['status'] ?? 'draft') ?></span>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Component & Gender Breakdown -->
  <div class="row g-3 mb-4">

    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Members by Component</div>
        <div class="card-body">
          <div class="row text-center">
            <?php
            $compLabels = ['MB' => 'Mother Sunbeam', 'AS_' => 'Allen Stars', 'Y' => 'Youth', 'YA' => 'Young Adults'];
            $compColors = ['MB' => 'primary', 'AS_' => 'success', 'Y' => 'warning', 'YA' => 'info'];
            foreach ($compLabels as $key => $label):
            ?>
            <div class="col-6 col-md-3 mb-3">
              <div class="fs-2 fw-bold text-<?= $compColors[$key] ?>"><?= $components[$key] ?? 0 ?></div>
              <div class="small text-muted"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
          </div>
          <canvas id="componentChart" height="120"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Members by Gender</div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="genderChart" height="160"></canvas>
          <div class="d-flex gap-4 mt-3">
            <div class="text-center">
              <div class="fs-4 fw-bold text-primary"><?= $genders['Male'] ?? 0 ?></div>
              <div class="small text-muted">Male</div>
            </div>
            <div class="text-center">
              <div class="fs-4 fw-bold text-danger"><?= $genders['Female'] ?? 0 ?></div>
              <div class="small text-muted">Female</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Quick Actions</div>
        <div class="card-body d-flex flex-column gap-2">
          <a href="views/members.php"    class="btn btn-outline-success btn-sm"><i class="fas fa-users me-2"></i>Members</a>
          <a href="views/areas.php"      class="btn btn-outline-warning btn-sm"><i class="fas fa-map-marked-alt me-2"></i>Areas</a>
          <a href="views/churches.php"   class="btn btn-outline-primary btn-sm"><i class="fas fa-church me-2"></i>Churches</a>
          <a href="views/church_list.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-list me-2"></i>Church List</a>
          <a href="views/events.php"     class="btn btn-outline-danger btn-sm"><i class="fas fa-calendar-alt me-2"></i>Events</a>
          <a href="views/statistical_reports.php" class="btn btn-outline-dark btn-sm"><i class="fas fa-chart-bar me-2"></i>Reports</a>
          <a href="views/media.php" class="btn btn-outline-info btn-sm"><i class="fas fa-photo-video me-2"></i>Media & History</a>
          <hr class="my-1">
          <a href="forms/add_member.php" class="btn btn-success btn-sm"><i class="fas fa-user-plus me-2"></i>Add Member</a>
          <a href="forms/add_event.php"  class="btn btn-primary btn-sm"><i class="fas fa-plus me-2"></i>Add Event</a>
        </div>
      </div>
    </div>

  </div>

  <?php if ($hasJoinedYpd || $hasFullChurch || $hasOccStatus): ?>
  <!-- ── New Charts Row ── -->
  <div class="row g-3 mb-4">

    <?php if ($hasJoinedYpd): ?>
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Joined YPD</div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="ypdChart" height="160"></canvas>
          <div class="d-flex gap-4 mt-3">
            <div class="text-center"><div class="fs-4 fw-bold text-success"><?= $ypdYes ?></div><div class="small text-muted">Yes</div></div>
            <div class="text-center"><div class="fs-4 fw-bold text-secondary"><?= $ypdNo ?></div><div class="small text-muted">No / Unknown</div></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($hasFullChurch): ?>
    <div class="col-lg-3 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Full Church Member</div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="fullChurchChart" height="160"></canvas>
          <div class="d-flex gap-4 mt-3">
            <div class="text-center"><div class="fs-4 fw-bold text-primary"><?= $fullYes ?></div><div class="small text-muted">Yes</div></div>
            <div class="text-center"><div class="fs-4 fw-bold text-secondary"><?= $fullNo ?></div><div class="small text-muted">No / Unknown</div></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($hasOccStatus && !empty($occRows)): ?>
    <div class="col-lg-6">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success">Occupational Status</div>
        <div class="card-body">
          <canvas id="occChart" height="120"></canvas>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
// Component bar chart
new Chart(document.getElementById('componentChart'), {
  type: 'bar',
  data: {
    labels: ['Mother Sunbeam', 'Allen Stars', 'Youth', 'Young Adults'],
    datasets: [{
      data: [<?= (int)($components['MB']??0) ?>, <?= (int)($components['AS_']??0) ?>, <?= (int)($components['Y']??0) ?>, <?= (int)($components['YA']??0) ?>],
      backgroundColor: ['#0d6efd','#198754','#ffc107','#0dcaf0']
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

// Gender doughnut chart
new Chart(document.getElementById('genderChart'), {
  type: 'doughnut',
  data: {
    labels: ['Male', 'Female'],
    datasets: [{
      data: [<?= (int)($genders['Male']??0) ?>, <?= (int)($genders['Female']??0) ?>],
      backgroundColor: ['#0d6efd','#dc3545']
    }]
  },
  options: { plugins: { legend: { display: false } } }
});

<?php if ($hasJoinedYpd): ?>
// Joined YPD doughnut
new Chart(document.getElementById('ypdChart'), {
  type: 'doughnut',
  data: {
    labels: ['Yes', 'No / Unknown'],
    datasets: [{
      data: [<?= $ypdYes ?>, <?= $ypdNo ?>],
      backgroundColor: ['#198754', '#adb5bd']
    }]
  },
  options: { plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($hasFullChurch): ?>
// Full Church Member doughnut
new Chart(document.getElementById('fullChurchChart'), {
  type: 'doughnut',
  data: {
    labels: ['Yes', 'No / Unknown'],
    datasets: [{
      data: [<?= $fullYes ?>, <?= $fullNo ?>],
      backgroundColor: ['#0d6efd', '#adb5bd']
    }]
  },
  options: { plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($hasOccStatus && !empty($occRows)): ?>
// Occupational Status bar chart
new Chart(document.getElementById('occChart'), {
  type: 'bar',
  data: {
    labels: [<?= implode(',', array_map(fn($r) => json_encode($r['occupational_status']), $occRows)) ?>],
    datasets: [{
      label: 'Members',
      data: [<?= implode(',', array_column($occRows, 'cnt')) ?>],
      backgroundColor: ['#0d6efd','#198754','#ffc107','#0dcaf0','#dc3545','#6f42c1','#fd7e14']
    }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true } }
  }
});
<?php endif; ?>
</script>
</body>
</html>
