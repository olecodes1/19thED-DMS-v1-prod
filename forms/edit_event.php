<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) { header("Location: ../views/events.php"); exit; }

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$id]);
$e = $stmt->fetch();
$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$districts   = $pdo->query("SELECT district_id, district_name FROM episcopal_districts ORDER BY district_name")->fetchAll();

if (!$e) { header("Location: ../views/events.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Event — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:700px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-calendar-edit me-2"></i>Edit Event</h5>

  <?php if (isset($_GET['success'])): ?>
    <?php $successMap = ['booklet_uploaded' => 'Event booklet uploaded successfully.', 'booklet_removed' => 'Event booklet removed.']; ?>
    <?php if (isset($successMap[$_GET['success']])): ?>
      <div class="alert alert-success alert-dismissible"><?= htmlspecialchars($successMap[$_GET['success']]) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <?php $errorMap = [
      'booklet_upload'    => 'The booklet upload failed. Please try again.',
      'booklet_too_large' => 'The booklet is too large. Maximum size is 25MB.',
      'booklet_not_pdf'   => 'Only PDF files are allowed for event booklets.',
      'booklet_storage'   => 'Could not store the booklet on the server. Please try again.',
      'database_error'    => 'A database error occurred. Please try again.',
      'delete_failed'     => 'Could not remove the booklet. Please try again.',
    ]; ?>
    <?php if (isset($errorMap[$_GET['error']])): ?>
      <div class="alert alert-danger alert-dismissible"><?= htmlspecialchars($errorMap[$_GET['error']]) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
  <?php endif; ?>

  <form method="POST" action="../actions/update_event.php" enctype="multipart/form-data">
    <input type="hidden" name="event_id" value="<?= $e['event_id'] ?>">

    <h6 class="text-muted mb-3 border-bottom pb-1">Scope</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Episcopal District</label>
        <select name="episcopal_district_id" class="form-select">
          <option value="">-- District-wide --</option>
          <?php foreach ($districts as $d): ?>
            <option value="<?= $d['district_id'] ?>" <?= (string)($e['episcopal_district_id'] ?? 19) === (string)$d['district_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['district_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Conference <span class="text-muted small">(optional)</span></label>
        <select name="conference_id" class="form-select">
          <option value="">-- All Conferences --</option>
          <?php foreach ($conferences as $c): ?>
            <option value="<?= $c['conference_id'] ?>" <?= (string)($e['conference_id'] ?? '') === (string)$c['conference_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Event Details</h6>
    <div class="mb-3">
      <label class="form-label">Event Name <span class="text-danger">*</span></label>
      <input type="text" name="event_name" class="form-control" value="<?= htmlspecialchars($e['event_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Event Date <span class="text-danger">*</span></label>
      <input type="date" name="event_date" class="form-control" value="<?= htmlspecialchars($e['event_date']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Location</label>
      <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($e['location'] ?? '') ?>">
    </div>
    <div class="mb-4">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($e['description'] ?? '') ?></textarea>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Attendance</h6>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label">Total Attendance</label>
        <input type="number" min="0" name="attendance_count" class="form-control" value="<?= (int)($e['attendance_count'] ?? 0) ?>">
      </div>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Event Booklet (PDF)</h6>
    <?php if (!empty($e['booklet_path'])): ?>
      <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 mb-3">
        <span>
          <i class="fas fa-file-pdf text-danger me-2"></i>
          Booklet on file
          <a href="../public_website/event_booklet_download.php?id=<?= (int)$e['event_id'] ?>" class="ms-2" target="_blank" rel="noopener"><i class="fas fa-eye me-1"></i>Preview</a>
        </span>
        <form method="POST" action="../actions/delete_event_booklet.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$e['event_id'] ?>"><button type="submit" class="btn btn-outline-danger btn-sm js-confirm-delete"><i class="fas fa-trash me-1"></i>Remove</button></form>
      </div>
    <?php endif; ?>
    <div class="mb-4">
      <label class="form-label"><?= !empty($e['booklet_path']) ? 'Replace booklet' : 'Upload booklet' ?> <span class="text-muted small">(PDF only, max 25MB — shown to the public on the event page)</span></label>
      <input type="file" name="booklet" accept="application/pdf" class="form-control">
      <div class="form-text">Uploading a new booklet replaces the existing one. Leave empty to keep the current file.</div>
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Event</button>
    <button type="submit" class="btn btn-outline-primary" formaction="../actions/upload_event_booklet.php" formnovalidate><i class="fas fa-file-pdf me-1"></i><?= !empty($e['booklet_path']) ? 'Replace Booklet' : 'Upload Booklet' ?></button>
    <a href="../views/events.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
