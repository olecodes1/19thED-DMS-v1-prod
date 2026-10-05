<?php
// Shared data for landing page
$totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
// Active = ≥1 YPD member; Inactive = 0 members
$churchCounts = $pdo->query("
    SELECT
        SUM((SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) >= 1) AS active,
        SUM((SELECT COUNT(*) FROM members m WHERE m.church_id = ch.church_id) = 0)  AS inactive
    FROM churches ch
")->fetch();
$totalActiveChurches   = (int)($churchCounts['active']   ?? 0);
$totalInactiveChurches = (int)($churchCounts['inactive'] ?? 0);
$totalConfs = (int)$pdo->query("SELECT COUNT(*) FROM conferences")->fetchColumn();
$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
?>
<!-- New Landing Page -->
<div class="text-center mb-5">
  <div class="row justify-content-center mb-4">
    <div class="col-md-8">
      <img src="../19thDistrict.png" alt="19th Episcopal District Logo" class="img-fluid mb-4" style="max-height: 150px;">
      <h1 class="fw-bold text-success mb-2">19th Episcopal District</h1>
      <h3 class="text-muted mb-3">Young People's And Children's Division</h3>
      <p class="lead">.</p>
      <div class="mt-4">
        <a href="?page=about" class="btn btn-success btn-lg me-2">Learn More</a>
        <a href="?page=dashboard" class="btn btn-outline-success btn-lg">View Dashboard</a>
      </div>
    </div>
  </div>
</div>

<!-- Feature Cards -->
<div class="row g-4 mb-5">
  <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center">
        <div class="text-success mb-3"><i class="fas fa-users fa-3x"></i></div>
        <h5 class="fw-bold">Our Community</h5>
        <p class="text-muted">Connect with members across conferences, areas, and churches throughout the district.</p>
        <a href="?page=dashboard" class="btn btn-outline-success btn-sm">Explore</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center">
        <div class="text-success mb-3"><i class="fas fa-calendar-alt fa-3x"></i></div>
        <h5 class="fw-bold">Events & Activities</h5>
        <p class="text-muted">Stay updated with district events, conferences, and youth activities.</p>
        <a href="?page=events" class="btn btn-outline-success btn-sm">View Events</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center">
        <div class="text-success mb-3"><i class="fas fa-history fa-3x"></i></div>
        <h5 class="fw-bold">Our History</h5>
        <p class="text-muted">Discover our rich legacy through stories, media, and historical milestones.</p>
        <a href="?page=history" class="btn btn-outline-success btn-sm">Our Story</a>
      </div>
    </div>
  </div>
</div>

<!-- Quick Stats Preview -->
<?php
$totalAreas = (int)$pdo->query("SELECT COUNT(*) FROM areas")->fetchColumn();
?>
<div class="row g-3 mb-5">
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
    <div class="card border-start border-secondary border-4 shadow-sm h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="text-muted small">Areas</div>
          <div class="fs-3 fw-bold"><?= $totalAreas ?></div>
        </div>
        <i class="fas fa-map-marked-alt fa-2x text-secondary opacity-50"></i>
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
</div>


<!-- Quick Links -->
<div class="row g-3 mb-5">
  <div class="col-12">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold text-success">Quick Links</div>
      <div class="card-body d-flex flex-wrap gap-2">
        <a href="?page=areas" class="btn btn-outline-warning btn-sm"><i class="fas fa-map-marked-alt me-1"></i>Explore Areas</a>
        <a href="?page=churches" class="btn btn-outline-primary btn-sm"><i class="fas fa-church me-1"></i>View Churches</a>
        <a href="?page=conferences" class="btn btn-outline-info btn-sm"><i class="fas fa-sitemap me-1"></i>View Conferences</a>
        <a href="?page=dashboard" class="btn btn-outline-success btn-sm"><i class="fas fa-chart-bar me-1"></i>Dashboard</a>
        <a href="?page=events" class="btn btn-outline-danger btn-sm"><i class="fas fa-calendar-alt me-1"></i>Events</a>
        <a href="?page=stories" class="btn btn-outline-secondary btn-sm"><i class="fas fa-book-open me-1"></i>Stories</a>
      </div>
    </div>
  </div>
</div>
