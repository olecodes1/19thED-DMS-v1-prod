<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) { header("Location: ../views/conferences.php"); exit; }

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM conferences WHERE conference_id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { header("Location: ../views/conferences.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Conference — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:500px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-edit me-2"></i>Edit Conference</h5>

  <form method="POST" action="../actions/update_conference.php">
    <input type="hidden" name="conference_id" value="<?= $c['conference_id'] ?>">
    <div class="mb-3">
      <label class="form-label">Conference Name</label>
      <input type="text" name="conference_name" class="form-control" value="<?= htmlspecialchars($c['conference_name']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Conference President</label>
      <input type="text" name="conference_president" class="form-control" value="<?= htmlspecialchars($c['conference_president'] ?? '') ?>">
    </div>
    <div class="mb-4">
      <label class="form-label">Conference Director</label>
      <input type="text" name="conference_director" class="form-control" value="<?= htmlspecialchars($c['conference_director'] ?? '') ?>">
    </div>
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Conference</button>
    <a href="../views/conferences.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
