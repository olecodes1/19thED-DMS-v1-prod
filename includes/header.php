<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/url_helper.php';
require_auth();

// Seeded/default accounts must change their password first (§1.7)
// — except on the change-password page itself to avoid a redirect loop.
if (must_change_password() && basename($_SERVER['PHP_SELF'] ?? '') !== 'change_password.php') {
    header('Location: ' . base_url('forms/change_password.php?required=1'));
    exit;
}

$authUser = current_auth_user();

// Chart pages contain inline initialization scripts after the page markup.
// Load Chart.js synchronously here so it is available before those scripts run.
// Keeping this shared prevents the admin dashboard and structure/report pages
// from racing their chart initialization against a deferred CDN request.
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php

// Determine active section for nav highlight
$currentPath = $_SERVER['PHP_SELF'] ?? '';

if (!function_exists('nav_active')) {
    function nav_active(string $path, array $patterns): string {
        foreach ($patterns as $p) {
            if (strpos($path, $p) !== false) return 'active';
        }
        return '';
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="<?= base_url('index.php') ?>">
      <img src="<?= base_url('19thDistrict.png') ?>" width="38" height="38" class="me-2" alt="19th District Logo">
      <span class="fw-semibold">19th Episcopal District</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <!-- Global search -->
      <form class="d-flex ms-2 me-3" method="GET" action="<?= base_url('views/search.php') ?>">
        <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Global search…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <button class="btn btn-light btn-sm" type="submit"><i class="fas fa-search"></i></button>
      </form>

      <ul class="navbar-nav ms-auto align-items-lg-center">

        <!-- Dashboard -->
        <li class="nav-item">
          <a class="nav-link <?= nav_active($currentPath, ['index.php']) ?>" href="<?= base_url('index.php') ?>">
            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
          </a>
        </li>

        <!-- Members dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= nav_active($currentPath, ['members']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-users me-1"></i>Members
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('views/members.php') ?>"><i class="fas fa-users fa-fw me-2 text-success"></i>All Members</a></li>
            <li><a class="dropdown-item" href="<?= base_url('forms/add_member.php') ?>"><i class="fas fa-user-plus fa-fw me-2 text-success"></i>Add Member</a></li>
          </ul>
        </li>

        <!-- Structure dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= nav_active($currentPath, ['conferences', 'areas', 'churches']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-sitemap me-1"></i>Structure
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('views/conferences.php') ?>"><i class="fas fa-sitemap fa-fw me-2 text-success"></i>Conferences</a></li>
            <li><a class="dropdown-item" href="<?= base_url('views/areas.php') ?>"><i class="fas fa-map-marked-alt fa-fw me-2 text-success"></i>Areas</a></li>
            <li><a class="dropdown-item" href="<?= base_url('views/churches.php') ?>"><i class="fas fa-church fa-fw me-2 text-success"></i>Churches</a></li>
          </ul>
        </li>

        <!-- Events dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= nav_active($currentPath, ['events', 'event_attendance']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-calendar-alt me-1"></i>Events
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('views/events.php') ?>"><i class="fas fa-calendar-alt fa-fw me-2 text-success"></i>All Events</a></li>
            <li><a class="dropdown-item" href="<?= base_url('forms/add_event.php') ?>"><i class="fas fa-calendar-plus fa-fw me-2 text-success"></i>Add Event</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= base_url('views/event_attendance.php') ?>"><i class="fas fa-user-check fa-fw me-2 text-primary"></i>Attendance</a></li>
          </ul>
        </li>

        <!-- Content dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= nav_active($currentPath, ['media', 'story_page', 'ypd_booklet']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-photo-video me-1"></i>Content
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('views/media.php') ?>"><i class="fas fa-photo-video fa-fw me-2 text-success"></i>Media Library</a></li>
            <li><a class="dropdown-item" href="<?= base_url('views/story_pages.php') ?>"><i class="fas fa-book-open fa-fw me-2 text-success"></i>Story Pages</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= base_url('views/ypd_booklet.php') ?>"><i class="fas fa-book fa-fw me-2 text-primary"></i>YPD History Booklet</a></li>
          </ul>
        </li>

        <!-- Reports & Admin dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= nav_active($currentPath, ['statistical_reports', 'recycle_bin', 'backup']) ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-chart-bar me-1"></i>Reports &amp; Admin
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= base_url('views/statistical_reports.php') ?>"><i class="fas fa-chart-bar fa-fw me-2 text-success"></i>Reports</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= base_url('views/recycle_bin.php') ?>"><i class="fas fa-trash-restore fa-fw me-2 text-danger"></i>Recycle Bin</a></li>
            <li><a class="dropdown-item" href="<?= base_url('actions/backup_bundle.php') ?>"><i class="fas fa-file-archive fa-fw me-2 text-secondary"></i>Backup</a></li>
          </ul>
        </li>

        <li class="nav-item ms-lg-2">
          <span class="navbar-text small text-light me-2">
            <i class="fas fa-user-shield me-1"></i><?= htmlspecialchars($authUser['username'] ?? 'Admin') ?>
          </span>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light btn-sm me-1" href="<?= base_url('forms/change_password.php') ?>">
            <i class="fas fa-key me-1"></i>Change Password
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-light btn-sm" href="<?= base_url('actions/logout.php') ?>">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>
