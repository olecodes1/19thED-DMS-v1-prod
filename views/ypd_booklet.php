<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/ypd_booklet_errors.log');

try {
    require_once __DIR__ . '/../includes/error_handler.php';
    require_once __DIR__ . '/../includes/auth.php';

    require_auth();

    require_once __DIR__ . '/../includes/database.php';

    if (!$pdo) {
        throw new Exception("Database connection failed");
    }

    // Get booklet meta information
    $bookletMeta = $pdo->query('SELECT * FROM ypd_booklet_meta ORDER BY id LIMIT 1')->fetch();

    // Get content counts
    $historyCount     = $pdo->query('SELECT COUNT(*) FROM ypd_history_entries')->fetchColumn();
    $officersCount    = $pdo->query('SELECT COUNT(*) FROM ypd_officers')->fetchColumn();
    $motherDirectorsCount = $pdo->query('SELECT COUNT(*) FROM ypd_mother_directors')->fetchColumn();
    $timelineCount    = $pdo->query('SELECT COUNT(*) FROM ypd_timeline_events')->fetchColumn();
    $achievementsCount = $pdo->query('SELECT COUNT(*) FROM ypd_achievements')->fetchColumn();
    $statisticsCount  = $pdo->query('SELECT COUNT(*) FROM ypd_statistics')->fetchColumn();
    $photos           = $pdo->query('SELECT * FROM photos ORDER BY uploaded_at DESC')->fetchAll();
    $photosCount      = count($photos);

    // Photo options for selects (officer photo, cover photo)
    $photoOptions = array_map(
        fn($p) => ['id' => (int)$p['id'], 'label' => ($p['caption'] ?: $p['filename'])],
        $photos
    );

} catch (Exception $e) {
    error_log("YPD Booklet ERROR: " . $e->getMessage());

    // Display user-friendly error
    echo "<div class='alert alert-danger'>";
    echo "<h4>Error loading YPD Booklet</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check the error log for details.</p>";
    echo "</div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>YPD History Booklet — Admin Panel</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-1 fw-bold text-primary">YPD History Booklet</h4>
      <p class="text-muted small mb-0">Manage the digital history booklet for the Young People's Division</p>
    </div>
    <div>
      <a href="../public_website/index.php?page=ypd_booklet" target="_blank" class="btn btn-outline-primary btn-sm me-2">
        <i class="fas fa-external-link-alt me-1"></i>View Booklet
      </a>
      <a href="../public_website/ypd_booklet_print.php" target="_blank" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-print me-1"></i>Print/PDF
      </a>
    </div>
  </div>

  <!-- Booklet Information Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold text-primary">
      <i class="fas fa-info-circle me-2"></i>Booklet Information
      <button class="btn btn-sm btn-outline-primary float-end" data-bs-toggle="modal" data-bs-target="#editMetaModal">
        <i class="fas fa-edit me-1"></i>Edit Info
      </button>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6 class="fw-semibold">District Name</h6>
          <p class="text-muted"><?= htmlspecialchars($bookletMeta['district_name'] ?? 'Not set') ?></p>
          
          <h6 class="fw-semibold">Booklet Title</h6>
          <p class="text-muted"><?= htmlspecialchars($bookletMeta['booklet_title'] ?? 'Not set') ?></p>
          
          <h6 class="fw-semibold">Subtitle</h6>
          <p class="text-muted"><?= htmlspecialchars($bookletMeta['subtitle'] ?? 'Not set') ?></p>
        </div>
        <div class="col-md-6">
          <h6 class="fw-semibold">Historiographer</h6>
          <p class="text-muted"><?= htmlspecialchars($bookletMeta['historiographer_name'] ?? 'Not set') ?></p>
          
          <h6 class="fw-semibold">Published Year</h6>
          <p class="text-muted"><?= htmlspecialchars($bookletMeta['published_year'] ?? 'Not set') ?></p>
          
          <h6 class="fw-semibold">Foreword Preview</h6>
          <p class="text-muted small"><?= substr(htmlspecialchars($bookletMeta['foreword'] ?? ''), 0, 200) ?>...</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Content Statistics -->
  <div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-primary border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">History Entries</div>
            <div class="fs-3 fw-bold"><?= $historyCount ?></div>
          </div>
          <i class="fas fa-history fa-2x text-primary opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-success border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Officers</div>
            <div class="fs-3 fw-bold"><?= $officersCount ?></div>
          </div>
          <i class="fas fa-user-tie fa-2x text-success opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-success border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Mother Directors</div>
            <div class="fs-3 fw-bold"><?= $motherDirectorsCount ?></div>
          </div>
          <i class="fas fa-hands fa-2x text-success opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-warning border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Timeline Events</div>
            <div class="fs-3 fw-bold"><?= $timelineCount ?></div>
          </div>
          <i class="fas fa-calendar-alt fa-2x text-warning opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-info border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Achievements</div>
            <div class="fs-3 fw-bold"><?= $achievementsCount ?></div>
          </div>
          <i class="fas fa-trophy fa-2x text-info opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-danger border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Statistics</div>
            <div class="fs-3 fw-bold"><?= $statisticsCount ?></div>
          </div>
          <i class="fas fa-chart-bar fa-2x text-danger opacity-50"></i>
        </div>
      </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
      <div class="card border-start border-secondary border-4 shadow-sm h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Photos</div>
            <div class="fs-3 fw-bold"><?= $photosCount ?></div>
          </div>
          <i class="fas fa-images fa-2x text-secondary opacity-50"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Content Management Tabs -->
  <div class="card shadow-sm">
    <div class="card-header bg-white">
      <ul class="nav nav-tabs card-header-tabs" id="ypdTabs" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">
            <i class="fas fa-history me-1"></i>History
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="officers-tab" data-bs-toggle="tab" data-bs-target="#officers" type="button">
            <i class="fas fa-user-tie me-1"></i>Officers
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="mother_directors-tab" data-bs-toggle="tab" data-bs-target="#mother_directors" type="button">
            <i class="fas fa-hands me-1"></i>Mother Directors
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button">
            <i class="fas fa-calendar-alt me-1"></i>Timeline
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="achievements-tab" data-bs-toggle="tab" data-bs-target="#achievements" type="button">
            <i class="fas fa-trophy me-1"></i>Achievements
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button">
            <i class="fas fa-chart-bar me-1"></i>Statistics
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="photos-tab" data-bs-toggle="tab" data-bs-target="#photos" type="button">
            <i class="fas fa-images me-1"></i>Photos
          </button>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="ypdTabsContent">
        <!-- History Tab -->
        <div class="tab-pane fade show active" id="history" role="tabpanel">
          <div class="d-flex justify-content-between mb-3">
            <h5 class="card-title">History Entries</h5>
            <button class="btn btn-primary btn-sm" onclick="openAddForm('history')">
              <i class="fas fa-plus me-1"></i>Add Entry
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Era</th>
                  <th>Title</th>
                  <th>Sort Order</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $historyEntries = $pdo->query('SELECT * FROM ypd_history_entries ORDER BY sort_order, created_at')->fetchAll();
                foreach ($historyEntries as $entry):
                ?>
                <tr>
                  <td><?= htmlspecialchars($entry['era_label'] ?? '') ?></td>
                  <td><?= htmlspecialchars($entry['title']) ?>
                    <?php if (($entry['status'] ?? 'published') !== 'published'): ?><span class="badge bg-secondary ms-1">draft</span><?php endif; ?>
                  </td>
                  <td><?= (int)$entry['sort_order'] ?></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('history', <?= (int)$entry['id'] ?>, -1)" title="Move up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('history', <?= (int)$entry['id'] ?>, 1)" title="Move down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('history', <?= (int)$entry['id'] ?>)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('history', <?= (int)$entry['id'] ?>, 'history entry')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Officers Tab -->
        <div class="tab-pane fade" id="officers" role="tabpanel">
          <div class="d-flex justify-content-between mb-3">
            <h5 class="card-title">YPD Officers</h5>
            <button class="btn btn-primary btn-sm" onclick="openAddForm('officers')">
              <i class="fas fa-plus me-1"></i>Add Officer
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Position</th>
                  <th>Term</th>
                  <th>Photo</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $officers = $pdo->query('SELECT * FROM ypd_officers ORDER BY sort_order, created_at')->fetchAll();
                foreach ($officers as $officer):
                ?>
                <tr>
                  <td><?= htmlspecialchars($officer['full_name']) ?>
                    <?php if (($officer['status'] ?? 'published') !== 'published'): ?><span class="badge bg-secondary ms-1">draft</span><?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($officer['position']) ?></td>
                  <td><?= htmlspecialchars($officer['term_start'] ?? '') ?> - <?= htmlspecialchars($officer['term_end'] ?? '') ?></td>
                  <td>
                    <?php if (!empty($officer['photo_id'])): ?>
                      <i class="fas fa-check text-success"></i>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('officers', <?= (int)$officer['id'] ?>, -1)" title="Move up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('officers', <?= (int)$officer['id'] ?>, 1)" title="Move down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('officers', <?= (int)$officer['id'] ?>)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('officers', <?= (int)$officer['id'] ?>, 'officer')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Mother Directors Tab -->
        <div class="tab-pane fade" id="mother_directors" role="tabpanel">
          <div class="d-flex justify-content-between mb-3">
            <h5 class="card-title">Mother Directors</h5>
            <button class="btn btn-primary btn-sm" onclick="openAddForm('mother_directors')">
              <i class="fas fa-plus me-1"></i>Add Mother Director
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Conference</th>
                  <th>Years of Service</th>
                  <th>Photo</th>
                  <th>Sort</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $motherDirectors = $pdo->query('SELECT * FROM ypd_mother_directors ORDER BY sort_order, id')->fetchAll();
                foreach ($motherDirectors as $md):
                ?>
                <tr>
                  <td><?= htmlspecialchars($md['full_name']) ?>
                    <?php if (($md['status'] ?? 'published') !== 'published'): ?><span class="badge bg-secondary ms-1">draft</span><?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($md['conference_name'] ?? '') ?></td>
                  <td><?= htmlspecialchars($md['years_of_service'] ?? '') ?></td>
                  <td>
                    <?php if (!empty($md['photo_id'])): ?>
                      <i class="fas fa-check text-success"></i>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td><?= (int)$md['sort_order'] ?></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('mother_directors', <?= (int)$md['id'] ?>, -1)" title="Move up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('mother_directors', <?= (int)$md['id'] ?>, 1)" title="Move down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('mother_directors', <?= (int)$md['id'] ?>)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('mother_directors', <?= (int)$md['id'] ?>, 'mother director')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if (!$motherDirectors): ?>
              <p class="text-muted">No mother directors added yet. Use "Add Mother Director" to add the first one.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Timeline Tab -->
        <div class="tab-pane fade" id="timeline" role="tabpanel">
          <div class="d-flex justify-content-between mb-3">
            <h5 class="card-title">Timeline Events</h5>
            <button class="btn btn-primary btn-sm" onclick="openAddForm('timeline')">
              <i class="fas fa-plus me-1"></i>Add Event
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Title</th>
                  <th>Sort Order</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $timelineEvents = $pdo->query('SELECT * FROM ypd_timeline_events ORDER BY event_date, sort_order')->fetchAll();
                foreach ($timelineEvents as $event):
                ?>
                <tr>
                  <td><?= htmlspecialchars($event['event_date']) ?></td>
                  <td><?= htmlspecialchars($event['title']) ?></td>
                  <td><?= (int)$event['sort_order'] ?></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('timeline', <?= (int)$event['id'] ?>, -1)" title="Move up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('timeline', <?= (int)$event['id'] ?>, 1)" title="Move down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('timeline', <?= (int)$event['id'] ?>)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('timeline', <?= (int)$event['id'] ?>, 'timeline event')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Achievements Tab -->
        <div class="tab-pane fade" id="achievements" role="tabpanel">
          <div class="d-flex justify-content-between mb-3">
            <h5 class="card-title">Achievements</h5>
            <button class="btn btn-primary btn-sm" onclick="openAddForm('achievements')">
              <i class="fas fa-plus me-1"></i>Add Achievement
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $achievements = $pdo->query('SELECT * FROM ypd_achievements ORDER BY sort_order, created_at')->fetchAll();
                foreach ($achievements as $achievement):
                ?>
                <tr>
                  <td><?= htmlspecialchars($achievement['title']) ?></td>
                  <td><?= htmlspecialchars($achievement['category'] ?? '') ?></td>
                  <td><?= htmlspecialchars($achievement['achievement_date'] ?? '') ?></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('achievements', <?= (int)$achievement['id'] ?>, -1)" title="Move up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('achievements', <?= (int)$achievement['id'] ?>, 1)" title="Move down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('achievements', <?= (int)$achievement['id'] ?>)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('achievements', <?= (int)$achievement['id'] ?>, 'achievement')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Statistics Tab -->
        <div class="tab-pane fade" id="statistics" role="tabpanel">
          <div class="d-flex justify-content-between mb-3">
            <h5 class="card-title">Statistics</h5>
            <button class="btn btn-primary btn-sm" onclick="openAddForm('statistics')">
              <i class="fas fa-plus me-1"></i>Add Statistic
            </button>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Label</th>
                  <th>Value</th>
                  <th>Year</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $statistics = $pdo->query('SELECT * FROM ypd_statistics ORDER BY sort_order, created_at')->fetchAll();
                foreach ($statistics as $stat):
                ?>
                <tr>
                  <td><?= htmlspecialchars($stat['category']) ?></td>
                  <td><?= htmlspecialchars($stat['label']) ?></td>
                  <td><?= htmlspecialchars($stat['value']) ?> <?= htmlspecialchars($stat['unit'] ?? '') ?></td>
                  <td><?= htmlspecialchars($stat['year'] ?? '') ?></td>
                  <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('statistics', <?= (int)$stat['id'] ?>, -1)" title="Move up"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="moveRow('statistics', <?= (int)$stat['id'] ?>, 1)" title="Move down"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('statistics', <?= (int)$stat['id'] ?>)">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('statistics', <?= (int)$stat['id'] ?>, 'statistic')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Photos Tab -->
        <div class="tab-pane fade" id="photos" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">Photo Gallery</h5>
          </div>

          <!-- Upload form -->
          <div class="card bg-light border mb-4">
            <div class="card-body">
              <h6 class="fw-semibold mb-3"><i class="fas fa-upload me-1"></i>Upload Photo</h6>
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label small mb-1">Image file (JPG, PNG or WEBP, max 5MB)</label>
                  <input type="file" class="form-control" id="photoFile" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="col-md-5">
                  <label class="form-label small mb-1">Caption</label>
                  <input type="text" class="form-control" id="photoCaption" placeholder="e.g. 2026 District YPD Convention group photo">
                </div>
                <div class="col-md-3">
                  <button class="btn btn-primary w-100" id="uploadPhotoBtn" onclick="uploadPhoto()">
                    <i class="fas fa-upload me-1"></i>Upload
                  </button>
                </div>
              </div>
            </div>
          </div>

          <?php if (!$photos): ?>
            <p class="text-muted">No photos uploaded yet. Use the form above to add the first one.</p>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:90px;">Preview</th>
                  <th>Caption</th>
                  <th>File</th>
                  <th>Uploaded</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($photos as $photo): ?>
                <tr>
                  <td>
                    <img src="../assets/uploads/ypd_photos/<?= htmlspecialchars($photo['filename']) ?>"
                         alt="" style="height:56px;max-width:80px;object-fit:cover;" class="rounded border">
                  </td>
                  <td><?= htmlspecialchars($photo['caption'] ?? '') ?: '<span class="text-muted small">No caption</span>' ?></td>
                  <td class="small text-muted"><?= htmlspecialchars($photo['filename']) ?></td>
                  <td class="small text-muted"><?= htmlspecialchars($photo['uploaded_at'] ?? '') ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditForm('photos', <?= (int)$photo['id'] ?>)" title="Edit caption">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteRow('photos', <?= (int)$photo['id'] ?>, 'photo')" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Edit Meta Modal -->
<div class="modal fade" id="editMetaModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Booklet Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="metaForm">
          <div class="mb-3">
            <label class="form-label">District Name</label>
            <input type="text" class="form-control" name="district_name" value="<?= htmlspecialchars($bookletMeta['district_name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Booklet Title</label>
            <input type="text" class="form-control" name="booklet_title" value="<?= htmlspecialchars($bookletMeta['booklet_title'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Subtitle</label>
            <input type="text" class="form-control" name="subtitle" value="<?= htmlspecialchars($bookletMeta['subtitle'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Historiographer Name</label>
            <input type="text" class="form-control" name="historiographer_name" value="<?= htmlspecialchars($bookletMeta['historiographer_name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Published Year</label>
            <input type="number" class="form-control" name="published_year" value="<?= htmlspecialchars($bookletMeta['published_year'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Cover Photo</label>
            <select class="form-select" name="cover_photo_id">
              <option value="">— None —</option>
              <?php foreach ($photoOptions as $opt): ?>
                <option value="<?= $opt['id'] ?>" <?= ((int)($bookletMeta['cover_photo_id'] ?? 0) === $opt['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($opt['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Upload photos in the Photos tab first, then pick one here.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Foreword</label>
            <textarea class="form-control" name="foreword" rows="5"><?= htmlspecialchars($bookletMeta['foreword'] ?? '') ?></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="saveMeta()">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Generic Add/Edit Record Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="recordModalTitle">Add Record</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="recordForm"></form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="recordSaveBtn" onclick="saveRecord()">Save</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
const API_BASE = '../api_ypd';
const PHOTO_OPTIONS = <?= json_encode($photoOptions) ?>;

// ---------- Form definitions per content type ----------
const FORMS = {
  history: {
    endpoint: 'history.php',
    label: 'History Entry',
    fields: [
      { name: 'era_label',  label: 'Era Label',  type: 'text', placeholder: 'e.g. Founding, Growth, Modern Era' },
      { name: 'title',      label: 'Title',      type: 'text', required: true },
      { name: 'body',       label: 'Body',       type: 'textarea', required: true, rows: 6 },
      { name: 'sort_order', label: 'Sort Order', type: 'number', value: 0 },
      { name: 'status', label: 'Status (publishing is superadmin-only)', type: 'select', options: ['draft', 'published'], value: 'published' }
    ]
  },
  officers: {
    endpoint: 'officers.php',
    label: 'Officer',
    fields: [
      { name: 'full_name',  label: 'Full Name',  type: 'text', required: true },
      { name: 'position',   label: 'Position',   type: 'text', required: true, placeholder: 'e.g. District President' },
      { name: 'term_start', label: 'Term Start', type: 'text', placeholder: 'e.g. 2023' },
      { name: 'term_end',   label: 'Term End',   type: 'text', placeholder: 'e.g. 2026 (leave blank for present)' },
      { name: 'bio',        label: 'Biography',  type: 'textarea', rows: 4 },
      { name: 'photo_id',   label: 'Photo',      type: 'photo-select' },
      { name: 'sort_order', label: 'Sort Order', type: 'number', value: 0 },
      { name: 'status', label: 'Status (publishing is superadmin-only)', type: 'select', options: ['draft', 'published'], value: 'published' }
    ]
  },
  mother_directors: {
    endpoint: 'mother_directors.php',
    label: 'Mother Director',
    fields: [
      { name: 'full_name',        label: 'Full Name',        type: 'text', required: true },
      { name: 'conference_name',  label: 'Conference',       type: 'text', placeholder: 'e.g. Central Conference' },
      { name: 'years_of_service', label: 'Years of Service', type: 'text', placeholder: 'e.g. 25 years' },
      { name: 'bio',              label: 'Biography',        type: 'textarea', rows: 4 },
      { name: 'achievements',     label: 'Achievements',     type: 'textarea', rows: 3 },
      { name: 'photo_id',         label: 'Photo',            type: 'photo-select' },
      { name: 'sort_order',       label: 'Sort Order',       type: 'number', value: 0 },
      { name: 'status', label: 'Status (publishing is superadmin-only)', type: 'select', options: ['draft', 'published'], value: 'published' }
    ]
  },
  timeline: {
    endpoint: 'timeline.php',
    label: 'Timeline Event',
    fields: [
      { name: 'event_date',  label: 'Date',        type: 'text', required: true, placeholder: 'e.g. 1915-10 or June 2024' },
      { name: 'title',       label: 'Title',       type: 'text', required: true },
      { name: 'description', label: 'Description', type: 'textarea', rows: 4 },
      { name: 'sort_order',  label: 'Sort Order',  type: 'number', value: 0 },
      { name: 'status', label: 'Status (publishing is superadmin-only)', type: 'select', options: ['draft', 'published'], value: 'published' }
    ]
  },
  achievements: {
    endpoint: 'achievements.php',
    label: 'Achievement',
    fields: [
      { name: 'title',            label: 'Title',    type: 'text', required: true },
      { name: 'category',         label: 'Category', type: 'text', placeholder: 'e.g. Awards, Programs, Community Service' },
      { name: 'achievement_date', label: 'Date',     type: 'text', placeholder: 'e.g. 2024-06' },
      { name: 'description',      label: 'Description', type: 'textarea', rows: 4 },
      { name: 'sort_order',       label: 'Sort Order',  type: 'number', value: 0 },
      { name: 'status', label: 'Status (publishing is superadmin-only)', type: 'select', options: ['draft', 'published'], value: 'published' }
    ]
  },
  statistics: {
    endpoint: 'statistics.php',
    label: 'Statistic',
    fields: [
      { name: 'category',   label: 'Category', type: 'text', required: true, placeholder: 'e.g. Membership, Events, Mission' },
      { name: 'label',      label: 'Label',    type: 'text', required: true, placeholder: 'e.g. Total Members' },
      { name: 'value',      label: 'Value',    type: 'number', required: true, step: 'any' },
      { name: 'unit',       label: 'Unit',     type: 'text', placeholder: 'e.g. members, events, %' },
      { name: 'year',       label: 'Year',     type: 'number' },
      { name: 'sort_order', label: 'Sort Order', type: 'number', value: 0 },
      { name: 'status', label: 'Status (publishing is superadmin-only)', type: 'select', options: ['draft', 'published'], value: 'published' }
    ]
  },
  photos: {
    endpoint: 'photos.php',
    label: 'Photo',
    fields: [
      { name: 'caption', label: 'Caption', type: 'textarea', rows: 3 }
    ]
  },
};

let recordModal = null;
let currentType = null;
let currentId = null;

document.addEventListener('DOMContentLoaded', () => {
  recordModal = new bootstrap.Modal(document.getElementById('recordModal'));
});

// ---------- API helper ----------
async function api(path, { method = 'GET', json, formData } = {}) {
  const opts = { method, credentials: 'same-origin' };
  if (json !== undefined) {
    opts.headers = { 'Content-Type': 'application/json' };
    opts.body = JSON.stringify(json);
  } else if (formData) {
    opts.body = formData;
  }
  let res;
  try {
    res = await fetch(`${API_BASE}/${path}`, opts);
  } catch (networkErr) {
    throw new Error('Could not reach the server. Is Apache running?');
  }
  const text = await res.text();
  let data = null;
  if (text) {
    try { data = JSON.parse(text); }
    catch (parseErr) { throw new Error(`Server returned an invalid response (HTTP ${res.status}).`); }
  }
  if (!res.ok) {
    throw new Error((data && data.error) || `Request failed (HTTP ${res.status}).`);
  }
  return data;
}

// ---------- Add / Edit ----------
function openAddForm(type) {
  currentType = type;
  currentId = null;
  buildRecordForm(type, {});
  document.getElementById('recordModalTitle').textContent = `Add ${FORMS[type].label}`;
  recordModal.show();
}

async function openEditForm(type, id) {
  try {
    const row = await api(`${FORMS[type].endpoint}?id=${id}`);
    currentType = type;
    currentId = id;
    buildRecordForm(type, row || {});
    document.getElementById('recordModalTitle').textContent = `Edit ${FORMS[type].label}`;
    recordModal.show();
  } catch (err) {
    alert('Error: ' + err.message);
  }
}

function buildRecordForm(type, row) {
  const form = document.getElementById('recordForm');
  form.innerHTML = '';
  for (const field of FORMS[type].fields) {
    const value = row[field.name] !== undefined && row[field.name] !== null ? row[field.name] : (field.value ?? '');
    const wrap = document.createElement('div');
    wrap.className = 'mb-3';

    const label = document.createElement('label');
    label.className = 'form-label';
    label.textContent = field.label + (field.required ? ' *' : '');

    let input;
    if (field.type === 'select') {
      input = document.createElement('select');
      for (const opt of field.options || []) {
        const o = document.createElement('option');
        o.value = opt;
        o.textContent = opt.charAt(0).toUpperCase() + opt.slice(1);
        if (String(value || field.value || '') === opt) o.selected = true;
        input.appendChild(o);
      }
    } else if (field.type === 'textarea') {
      input = document.createElement('textarea');
      input.rows = field.rows || 4;
      input.value = value;
    } else if (field.type === 'photo-select') {
      input = document.createElement('select');
      const none = document.createElement('option');
      none.value = '';
      none.textContent = '— None —';
      input.appendChild(none);
      for (const opt of PHOTO_OPTIONS) {
        const o = document.createElement('option');
        o.value = opt.id;
        o.textContent = opt.label;
        if (String(opt.id) === String(value)) o.selected = true;
        input.appendChild(o);
      }
      const hint = document.createElement('div');
      hint.className = 'form-text';
      hint.textContent = 'Upload photos in the Photos tab, then assign one here.';
      wrap.appendChild(label);
      wrap.appendChild(input);
      wrap.appendChild(hint);
      input.className = 'form-select';
      input.name = field.name;
      form.appendChild(wrap);
      continue;
    } else {
      input = document.createElement('input');
      input.type = field.type;
      if (field.step) input.step = field.step;
      input.value = value;
    }
    input.className = 'form-control';
    input.name = field.name;
    if (field.placeholder) input.placeholder = field.placeholder;
    if (field.required) input.required = true;

    wrap.appendChild(label);
    wrap.appendChild(input);
    form.appendChild(wrap);
  }
}

async function saveRecord() {
  const form = document.getElementById('recordForm');
  if (!form.reportValidity()) return;

  const data = {};
  for (const field of FORMS[currentType].fields) {
    const el = form.elements[field.name];
    if (!el) continue;
    let value = el.value.trim();
    if (value === '') {
      value = null;
    } else if (field.type === 'number') {
      value = Number(value);
    }
    data[field.name] = value;
  }

  const btn = document.getElementById('recordSaveBtn');
  btn.disabled = true;
  try {
    if (currentId === null) {
      await api(FORMS[currentType].endpoint, { method: 'POST', json: data });
    } else {
      await api(`${FORMS[currentType].endpoint}?id=${currentId}`, { method: 'PUT', json: data });
    }
    location.reload();
  } catch (err) {
    alert('Error: ' + err.message);
    btn.disabled = false;
  }
}

// ---------- Ordering (§2.5) ----------
// Swap sort_order with the neighbouring row so users don't have to edit
// numeric sort fields by hand. Works off the endpoint's default order.
async function moveRow(type, id, dir) {
  try {
    const rows = await api(FORMS[type].endpoint);
    if (!Array.isArray(rows) || rows.length < 2) { location.reload(); return; }
    const idx = rows.findIndex(r => String(r.id) === String(id));
    if (idx === -1) return;
    const swapIdx = idx + dir;
    if (swapIdx < 0 || swapIdx >= rows.length) return; // already at the edge
    const a = rows[idx], b = rows[swapIdx];
    const ao = Number(a.sort_order ?? 0), bo = Number(b.sort_order ?? 0);
    // If neighbours share an order value, shift apart instead of swapping
    const newA = ao === bo ? ao + dir : bo;
    const newB = ao === bo ? bo - dir : ao;
    await api(`${FORMS[type].endpoint}?id=${a.id}`, { method: 'PUT', json: { sort_order: newA } });
    await api(`${FORMS[type].endpoint}?id=${b.id}`, { method: 'PUT', json: { sort_order: newB } });
    location.reload();
  } catch (err) {
    alert('Error: ' + err.message);
  }
}

// ---------- Publish toggle (§2.3) ----------
async function togglePublish(type, id, currentStatus) {
  const target = currentStatus === 'published' ? 'draft' : 'published';
  if (target === 'published' && !confirm('Publish this item so it appears in the public booklet?')) return;
  try {
    await api(`${FORMS[type].endpoint}?id=${id}`, { method: 'PUT', json: { status: target } });
    location.reload();
  } catch (err) {
    alert('Error: ' + err.message);
  }
}

// ---------- Delete ----------
async function deleteRow(type, id, label) {
  if (!confirm(`Are you sure you want to delete this ${label}?`)) return;
  try {
    await api(`${FORMS[type].endpoint}?id=${id}`, { method: 'DELETE' });
    location.reload();
  } catch (err) {
    alert('Error: ' + err.message);
  }
}

// ---------- Photo upload ----------
async function uploadPhoto() {
  const fileInput = document.getElementById('photoFile');
  const file = fileInput.files[0];
  if (!file) {
    alert('Please choose an image file first.');
    return;
  }
  const caption = document.getElementById('photoCaption').value.trim();
  const fd = new FormData();
  fd.append('file', file);
  if (caption) fd.append('caption', caption);

  const btn = document.getElementById('uploadPhotoBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading...';
  try {
    await api('upload.php', { method: 'POST', formData: fd });
    location.reload();
  } catch (err) {
    alert('Error: ' + err.message);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload';
  }
}

// ---------- Booklet meta ----------
async function saveMeta() {
  const form = document.getElementById('metaForm');
  const data = Object.fromEntries(new FormData(form).entries());
  data.cover_photo_id = data.cover_photo_id === '' ? null : Number(data.cover_photo_id);
  data.published_year = data.published_year === '' ? null : Number(data.published_year);

  try {
    await api('meta.php', { method: 'PUT', json: data });
    alert('Booklet information updated successfully!');
    bootstrap.Modal.getInstance(document.getElementById('editMetaModal')).hide();
    location.reload();
  } catch (err) {
    alert('Error: ' + err.message);
  }
}
</script>
</body>
</html>
