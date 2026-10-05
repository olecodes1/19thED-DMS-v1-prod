<?php
define('ALLOW_GUEST', true);
require_once '../includes/access_control.php';

// Helper function for htmlspecialchars
function h(?string $value): string {
    return htmlspecialchars((string)$value);
}

// Nav active helper
$isActive = fn(string $p): string => ($page ?? '') === $p ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>19th Episcopal District — Public Website</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css?v=<?= (int)@filemtime(__DIR__ . '/../../assets/css/styles.css') ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="?page=landing">
        <img src="../19thDistrict.png" width="36" height="36" class="me-2" alt="19th District Logo">
        <span class="fw-semibold">19th Episcopal District</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="publicNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link <?= $isActive('landing') ?>" href="?page=landing">Home</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('dashboard') ?>" href="?page=dashboard">Dashboard</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Explore</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="?page=areas"><i class="fas fa-map-marked-alt me-2 text-warning"></i>Areas</a></li>
              <li><a class="dropdown-item" href="?page=churches"><i class="fas fa-church me-2 text-primary"></i>Churches</a></li>
              <li><a class="dropdown-item" href="?page=conferences"><i class="fas fa-sitemap me-2 text-info"></i>Conferences</a></li>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link <?= $isActive('about') ?>" href="?page=about">About</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('events') ?>" href="?page=events">Events</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('media') ?>" href="?page=media">Media</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('stories') ?>" href="?page=stories">Stories</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('history') ?>" href="?page=history">History</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('records') ?>" href="?page=records"><i class="fas fa-box-archive me-1"></i>Records</a></li>
          <li class="nav-item"><a class="nav-link <?= $isActive('ypd_booklet') ?>" href="?page=ypd_booklet"><i class="fas fa-book me-1"></i>YPD Booklet</a></li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-light btn-sm mt-1 mt-lg-0" href="../login.php"><i class="fas fa-lock me-1"></i>Login</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container-fluid mt-4 px-4">
