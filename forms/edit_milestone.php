<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$milestone_id = (int)($_GET['id'] ?? 0);
if ($milestone_id <= 0) {
    header('Location: ../views/media.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM milestones WHERE milestone_id = ?");
$stmt->execute([$milestone_id]);
$milestone = $stmt->fetch();

if (!$milestone) {
    header('Location: ../views/media.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Milestone — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-flag me-2"></i>Edit Milestone</h5>
    <a href="../views/media.php" class="btn btn-secondary btn-sm">Back to Media</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" action="../actions/edit_milestone.php" class="row g-3">
        <input type="hidden" name="milestone_id" value="<?= (int)$milestone['milestone_id'] ?>">
        
        <div class="col-12">
          <label class="form-label">Milestone Title</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($milestone['title']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Year</label>
          <input type="number" name="milestone_year" class="form-control" min="1800" max="<?= date('Y') ?>" value="<?= (int)$milestone['milestone_year'] ?>" required>
        </div>
        <div class="col-md-8">
          <label class="form-label">Descriptions</label>
          <input type="text" name="descriptions" class="form-control" value="<?= htmlspecialchars($milestone['descriptions'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Achievements</label>
          <textarea name="achievements" class="form-control" rows="3"><?= htmlspecialchars($milestone['achievements'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Changes</button>
          <a href="../views/media.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>