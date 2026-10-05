<?php
// Shared data for dashboard
$config = require __DIR__ . '/../../config.php';

// Prefer the pre-aggregated row, but keep the public dashboard functional when
// the optional performance migration has not yet been run.
try {
    $districtAgg = $pdo->query("SELECT total_members, active_churches, inactive_churches, total_areas, total_conferences, total_events, total_media FROM district_aggregates WHERE id = 1")->fetch();
} catch (Throwable $e) {
    $districtAgg = null;
}
if (!$districtAgg) {
    $districtAgg = $pdo->query("SELECT
        (SELECT COUNT(*) FROM members WHERE deleted_at IS NULL) AS total_members,
        (SELECT COUNT(*) FROM areas) AS total_areas,
        (SELECT COUNT(*) FROM conferences) AS total_conferences,
        (SELECT COUNT(*) FROM events) AS total_events,
        (SELECT COUNT(*) FROM media_items WHERE deleted_at IS NULL) AS total_media,
        COALESCE(SUM(cms.member_count > 0), 0) AS active_churches,
        COALESCE(SUM(COALESCE(cms.member_count, 0) = 0), 0) AS inactive_churches
        FROM churches ch
        LEFT JOIN (
            SELECT church_id, COUNT(*) AS member_count
            FROM members WHERE deleted_at IS NULL GROUP BY church_id
        ) cms ON cms.church_id = ch.church_id")->fetch();
}
$totalMembers      = (int)($districtAgg['total_members']      ?? 0);
$totalActiveChurches   = (int)($districtAgg['active_churches']   ?? 0);
$totalInactiveChurches = (int)($districtAgg['inactive_churches'] ?? 0);
$totalAreas    = (int)($districtAgg['total_areas']    ?? 0);
$totalConfs    = (int)($districtAgg['total_conferences']    ?? 0);
$totalEvents   = (int)($districtAgg['total_events']   ?? 0);
$totalMedia    = (int)($districtAgg['total_media']    ?? 0);

// Component & gender aggregates
$components = $pdo->query(
    "SELECT
        SUM(component = 'MB') AS MB,
        SUM(component = 'AS') AS AS_,
        SUM(component = 'Y')  AS Y,
        SUM(component = 'YA') AS YA
    FROM members"
)->fetch();

$componentChartData = [
    'labels' => ['Mother Sunbeam', 'Allen Stars', 'Youth', 'Young Adults'],
    'datasets' => [[
        'data' => [(int)($components['MB'] ?? 0), (int)($components['AS_'] ?? 0), (int)($components['Y'] ?? 0), (int)($components['YA'] ?? 0)],
        'backgroundColor' => ['#0d6efd', '#198754', '#ffc107', '#0dcaf0'],
    ]],
];

$genders = $pdo->query(
    "SELECT
        SUM(gender = 'M') AS Male,
        SUM(gender = 'F') AS Female
    FROM members"
)->fetch();

$genderChartData = [
    'labels' => ['Male', 'Female'],
    'datasets' => [[
        'data' => [(int)($genders['Male'] ?? 0), (int)($genders['Female'] ?? 0)],
        'backgroundColor' => ['#0d6efd', '#dc3545'],
    ]],
];

// New field stats (use config-based feature flags to avoid expensive information_schema queries)
$hasJoinedYpd  = $config['features']['joined_ypd'];
$hasFullChurch = $config['features']['full_member_of_church'];
$hasOccStatus  = $config['features']['occupational_status'];

$ypdYes = $hasJoinedYpd  ? (int)$pdo->query("SELECT SUM(joined_ypd='Yes') FROM members")->fetchColumn() : 0;
$ypdNo  = $hasJoinedYpd  ? (int)$pdo->query("SELECT SUM(joined_ypd='No' OR joined_ypd IS NULL) FROM members")->fetchColumn() : 0;

$fullYes = $hasFullChurch ? (int)$pdo->query("SELECT SUM(full_member_of_church='Yes') FROM members")->fetchColumn() : 0;
$fullNo  = $hasFullChurch ? (int)$pdo->query("SELECT SUM(full_member_of_church='No' OR full_member_of_church IS NULL) FROM members")->fetchColumn() : 0;

$occRows = [];
if ($hasOccStatus) {
    $occRows = $pdo->query("
        SELECT occupational_status, COUNT(*) AS cnt
        FROM members
        WHERE occupational_status IS NOT NULL AND occupational_status != ''
        GROUP BY occupational_status
        ORDER BY cnt DESC
        LIMIT 7
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$latestEvents = $pdo->query("SELECT event_id, event_name, event_date, location FROM events ORDER BY event_date DESC LIMIT 5")->fetchAll();
$latestStories = $pdo->query("SELECT title, slug, story_year FROM story_pages WHERE deleted_at IS NULL AND status='published' ORDER BY COALESCE(story_year, 0) DESC, created_at DESC LIMIT 5")->fetchAll();

$bestEvent = $pdo->query("
    SELECT e.event_name, COALESCE(SUM(b.attendance_count), 0) AS total_attendance
    FROM events e
    LEFT JOIN event_attendance_breakdowns b ON b.event_id = e.event_id
    GROUP BY e.event_id, e.event_name
    ORDER BY total_attendance DESC, e.event_name
    LIMIT 1
")->fetch();

$lowestEvent = $pdo->query("
    SELECT e.event_name, COALESCE(SUM(b.attendance_count), 0) AS total_attendance
    FROM events e
    LEFT JOIN event_attendance_breakdowns b ON b.event_id = e.event_id
    GROUP BY e.event_id, e.event_name
    ORDER BY total_attendance ASC, e.event_name
    LIMIT 1
")->fetch();

$latestMedia = $pdo->query("
    SELECT title, media_type, uploaded_at
    FROM media_items
    WHERE deleted_at IS NULL
    ORDER BY uploaded_at DESC
    LIMIT 3
")->fetchAll();
?>
<h4 class="fw-bold text-success mb-1">District Dashboard</h4>
<p class="text-muted small mb-4">Statistical summaries and district analytics.</p>

<!-- Charts row (public read-only parity with AdminDash) -->
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold text-success">Members by Component</div>
      <div class="card-body">
        <div class="row text-center mb-3">
          <div class="col-6 col-md-3 mb-3">
            <div class="fs-2 fw-bold text-primary"><?= (int)($components['MB'] ?? 0) ?></div>
            <div class="small text-muted">Mother Sunbeam</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="fs-2 fw-bold text-success"><?= (int)($components['AS_'] ?? 0) ?></div>
            <div class="small text-muted">Allen Stars</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="fs-2 fw-bold text-warning"><?= (int)($components['Y'] ?? 0) ?></div>
            <div class="small text-muted">Youth</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="fs-2 fw-bold text-info"><?= (int)($components['YA'] ?? 0) ?></div>
            <div class="small text-muted">Young Adults</div>
          </div>
        </div>
        <div class="dashboard-chart-box dashboard-chart-box--bar">
          <canvas id="componentChart" class="chart-container" data-chart-type="bar" data-chart-data="<?= htmlspecialchars(json_encode($componentChartData), ENT_QUOTES, 'UTF-8') ?>"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold text-success">Members by Gender</div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center">
        <div class="dashboard-chart-box dashboard-chart-box--donut">
          <canvas id="genderChart" class="chart-container" data-chart-type="doughnut" data-chart-data="<?= htmlspecialchars(json_encode($genderChartData), ENT_QUOTES, 'UTF-8') ?>"></canvas>
        </div>
        <div class="d-flex gap-4 mt-3">
          <div class="text-center">
            <div class="fs-4 fw-bold text-primary"><?= (int)($genders['Male'] ?? 0) ?></div>
            <div class="small text-muted">Male</div>
          </div>
          <div class="text-center">
            <div class="fs-4 fw-bold text-danger"><?= (int)($genders['Female'] ?? 0) ?></div>
            <div class="small text-muted">Female</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

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
          <div class="text-muted small">Active Churches <span style="font-size:0.7rem;">(≥1 member)</span></div>
          <div class="fs-3 fw-bold text-primary"><?= $totalActiveChurches ?></div>
        </div>
        <i class="fas fa-church fa-2x text-primary opacity-50"></i>
      </div>
    </div>
  </div>

  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="card border-start border-warning border-4 shadow-sm h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Inactive Churches <span style="font-size:0.7rem;">(0 members)</span></div>
          <div class="fs-3 fw-bold text-warning"><?= $totalInactiveChurches ?></div>
        </div>
        <i class="fas fa-times-circle fa-2x text-warning opacity-50"></i>
      </div>
    </div>
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

  <div class="col-xl-2 col-md-4 col-sm-6">
    <div class="card border-start border-secondary border-4 shadow-sm h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Media Items</div>
          <div class="fs-3 fw-bold"><?= $totalMedia ?></div>
        </div>
        <i class="fas fa-photo-video fa-2x text-secondary opacity-50"></i>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold text-success">Attendance Snapshot</div>
      <div class="card-body small">
        <div class="mb-2"><span class="text-muted">Top event:</span> <strong><?= h($bestEvent['event_name'] ?? '—') ?></strong> (<?= (int)($bestEvent['total_attendance'] ?? 0) ?>)</div>
        <div><span class="text-muted">Lowest event:</span> <strong><?= h($lowestEvent['event_name'] ?? '—') ?></strong> (<?= (int)($lowestEvent['total_attendance'] ?? 0) ?>)</div>
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
          <div class="mb-1"><strong><?= h($m['title']) ?></strong> <span class="text-muted">(<?= h($m['media_type']) ?>)</span></div>
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
            <a href="?page=story&slug=<?= urlencode($s['slug']) ?>" class="text-decoration-none"><?= h($s['title']) ?></a>
            <span class="badge bg-<?= ($s['status'] ?? 'draft') === 'published' ? 'success' : 'secondary' ?> ms-1"><?= h($s['status'] ?? 'draft') ?></span>
          </div>
        <?php endforeach; endif; ?>
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
        <div class="dashboard-chart-box dashboard-chart-box--donut"><canvas id="ypdChart"></canvas></div>
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
        <div class="dashboard-chart-box dashboard-chart-box--donut"><canvas id="fullChurchChart"></canvas></div>
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
        <div class="dashboard-chart-box dashboard-chart-box--occupational"><canvas id="occChart"></canvas></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold text-success">Latest Events</div>
      <div class="card-body">
        <?php if (!$latestEvents): ?>
          <div class="text-muted">No events available.</div>
        <?php else: foreach ($latestEvents as $e): ?>
          <div class="mb-2">
            <a class="text-decoration-none fw-semibold" href="?page=event&id=<?= (int)$e['event_id'] ?>"><?= h($e['event_name']) ?></a>
            <div class="small text-muted"><?= h($e['event_date']) ?><?= !empty($e['location']) ? ' • ' . h($e['location']) : '' ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold text-success">Latest Published Stories</div>
      <div class="card-body">
        <?php if (!$latestStories): ?>
          <div class="text-muted">No published stories available.</div>
        <?php else: foreach ($latestStories as $s): ?>
          <div class="mb-2">
            <a class="text-decoration-none fw-semibold" href="?page=story&slug=<?= urlencode($s['slug']) ?>"><?= h($s['title']) ?></a>
            <div class="small text-muted"><?= h((string)($s['story_year'] ?? '')) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>  <script>
// OPTIMIZATION: Lazy-load charts with IntersectionObserver
// Charts only initialize when visible, reducing initial CPU usage
document.addEventListener('DOMContentLoaded', function() {
  const chartObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const chart = entry.target;
        const chartType = chart.dataset.chartType;
        const chartData = JSON.parse(chart.dataset.chartData || '{}');
        
        if (chartType === 'bar') {
          new Chart(chart, {
            type: 'bar',
            data: chartData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: { duration: 0 },
              plugins: { legend: { display: false } },
              scales: { y: { beginAtZero: true } }
            }
          });
        } else if (chartType === 'doughnut') {
          new Chart(chart, {
            type: 'doughnut',
            data: chartData,
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: { duration: 0 },
              plugins: { legend: { display: false } }
            }
          });
        }
        chartObserver.unobserve(chart);
      }
    });
  }, { threshold: 0.1 });
  
  // Observe all charts with data attributes
  document.querySelectorAll('.chart-container[data-chart-type]').forEach(chart => {
    chartObserver.observe(chart);
  });
});

// These charts also wait for deferred Chart.js to load.
document.addEventListener('DOMContentLoaded', function() {
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
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
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
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
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
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true } }
  }
});
<?php endif; ?>
});
</script>
