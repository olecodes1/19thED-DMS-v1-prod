<?php require_once '../includes/access_control.php';
require_once '../includes/auth.php';
$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Area — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:550px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-map-marked-alt me-2"></i>Add Area</h5>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible">Area added! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <form method="POST" action="../actions/process_area.php">
    <div class="mb-3">
      <label class="form-label">Conference <span class="text-danger">*</span></label>
      <select name="conference_id" class="form-select" required>
        <option value="">-- Select Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>"><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Area Name</label>
      <input type="text" name="area_name" class="form-control" placeholder="e.g. Area 1">
    </div>
    <div class="mb-3">
      <label class="form-label">Area President</label>
      <input type="text" name="area_president_name" class="form-control">
    </div>
    <div class="mb-4">
      <label class="form-label">Area Director</label>
      <input type="text" name="area_director_name" class="form-control">
    </div>
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Area</button>
    <a href="../views/areas.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
