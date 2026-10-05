<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) { header("Location: ../views/areas.php"); exit; }

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM areas WHERE area_id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) { header("Location: ../views/areas.php"); exit; }

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Area — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:550px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-edit me-2"></i>Edit Area</h5>

  <form method="POST" action="../actions/update_area.php">
    <input type="hidden" name="area_id" value="<?= $a['area_id'] ?>">

    <div class="mb-3">
      <label class="form-label">Conference</label>
      <select name="conference_id" class="form-select">
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $a['conference_id']==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Area Name</label>
      <input type="text" name="area_name" class="form-control" value="<?= htmlspecialchars($a['area_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Area President</label>
      <input type="text" name="area_president_name" class="form-control" value="<?= htmlspecialchars($a['area_president_name'] ?? '') ?>">
    </div>
    <div class="mb-4">
      <label class="form-label">Area Director</label>
      <input type="text" name="area_director_name" class="form-control" value="<?= htmlspecialchars($a['area_director_name'] ?? '') ?>">
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Area</button>
    <a href="../views/areas.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
