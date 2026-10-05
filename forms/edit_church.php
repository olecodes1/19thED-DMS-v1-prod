<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) { header("Location: ../views/churches.php"); exit; }

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM churches WHERE church_id = ?");
$stmt->execute([$id]);
$ch = $stmt->fetch();
if (!$ch) { header("Location: ../views/churches.php"); exit; }

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$areas       = $pdo->query("SELECT area_id, area_name, conference_id FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Church — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:600px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-edit me-2"></i>Edit Church</h5>

  <form method="POST" action="../actions/update_church.php">
    <input type="hidden" name="church_id" value="<?= $ch['church_id'] ?>">

    <div class="mb-3">
      <label class="form-label">Conference</label>
      <select name="conference_id" id="confSelect" class="form-select">
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $ch['conference_id']==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Area</label>
      <select name="area_id" id="areaSelect" class="form-select">
        <?php foreach ($areas as $a): ?>
          <option value="<?= $a['area_id'] ?>" data-conf="<?= $a['conference_id'] ?>" <?= $ch['area_id']==$a['area_id']?'selected':'' ?>><?= htmlspecialchars($a['area_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Church Name</label>
      <input type="text" name="local_church_name" class="form-control" value="<?= htmlspecialchars($ch['local_church_name']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Local President</label>
      <input type="text" name="local_church_president_name" class="form-control" value="<?= htmlspecialchars($ch['local_church_president_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Local Director</label>
      <input type="text" name="local_church_director_name" class="form-control" value="<?= htmlspecialchars($ch['local_church_director_name'] ?? '') ?>">
    </div>
    <div class="mb-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="active"   <?= $ch['status']==='active'  ?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $ch['status']==='inactive'?'selected':'' ?>>Inactive</option>
      </select>
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Church</button>
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
});
</script>
</body>
</html>
