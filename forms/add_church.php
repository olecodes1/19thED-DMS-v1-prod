<?php require_once '../includes/access_control.php';
require_once '../includes/auth.php';
$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$areas       = $pdo->query("SELECT area_id, area_name, conference_id FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Church — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:600px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-church me-2"></i>Add Church</h5>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible">Church added! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <form method="POST" action="../actions/process_church.php">
    <div class="mb-3">
      <label class="form-label">Conference <span class="text-danger">*</span></label>
      <select name="conference_id" id="confSelect" class="form-select" required>
        <option value="">-- Select Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>"><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Area <span class="text-danger">*</span></label>
      <select name="area_id" id="areaSelect" class="form-select" required>
        <option value="">-- Select Area --</option>
        <?php foreach ($areas as $a): ?>
          <option value="<?= $a['area_id'] ?>" data-conf="<?= $a['conference_id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Church Name <span class="text-danger">*</span></label>
      <input type="text" name="local_church_name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Local President</label>
      <input type="text" name="local_church_president_name" class="form-control">
    </div>
    <div class="mb-3">
      <label class="form-label">Local Director</label>
      <input type="text" name="local_church_director_name" class="form-control">
    </div>
    <div class="mb-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Church</button>
    <a href="../views/churches.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.getElementById('confSelect').addEventListener('change', function () {
    const confId = this.value;
    document.querySelectorAll('#areaSelect option[data-conf]').forEach(o => {
        o.style.display = (!confId || o.dataset.conf === confId) ? '' : 'none';
    });
    document.getElementById('areaSelect').value = '';
});
</script>
</body>
</html>
