<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$leader_id = (int)($_GET['id'] ?? 0);
if ($leader_id <= 0) {
    header('Location: ../views/media.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM legacy_leaders WHERE leader_id = ?");
$stmt->execute([$leader_id]);
$leader = $stmt->fetch();

if (!$leader) {
    header('Location: ../views/media.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <title>Edit Legacy Leader — 19th Episcopal District</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-user-edit me-2"></i>Edit Legacy Leader</h5>
    <a href="../views/media.php" class="btn btn-secondary btn-sm">Back to Media</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" action="../actions/edit_leader.php" class="row g-3" enctype="multipart/form-data">
        <input type="hidden" name="leader_id" value="<?= (int)$leader['leader_id'] ?>">
        
        <div class="col-md-6">
          <label class="form-label">Role</label>
          <select name="role_type" class="form-select">
            <option value="Bishop" <?= $leader['role_type'] === 'Bishop' ? 'selected' : '' ?>>Bishop</option>
            <option value="Director" <?= $leader['role_type'] === 'Director' ? 'selected' : '' ?>>Director</option>
            <option value="President" <?= $leader['role_type'] === 'President' ? 'selected' : '' ?>>President</option>
            <option value="Mother Director" <?= $leader['role_type'] === 'Mother Director' ? 'selected' : '' ?>>Mother Director</option>
            <option value="Other" <?= $leader['role_type'] === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Conference</label>
          <input type="text" name="conference_name" class="form-control" value="<?= htmlspecialchars($leader['conference_name'] ?? '') ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($leader['full_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Start Year</label>
          <input type="number" name="start_year" class="form-control" min="1800" max="<?= date('Y') ?>" value="<?= (int)$leader['start_year'] ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">End Year</label>
          <input type="number" name="end_year" class="form-control" min="1800" max="<?= date('Y') ?>" value="<?= (int)$leader['end_year'] ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Descriptions</label>
          <textarea name="descriptions" class="form-control" rows="3"><?= htmlspecialchars($leader['descriptions'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Achievements</label>
          <textarea name="achievements" class="form-control" rows="3"><?= htmlspecialchars($leader['achievements'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Photo</label>
          <input type="file" name="photo" class="form-control" accept="image/*">
          <?php if (!empty($leader['photo_path'])): ?>
            <div class="mt-2">
              <img src="../<?= htmlspecialchars($leader['photo_path']) ?>" alt="Current photo" class="img-thumbnail" style="max-height: 100px;">
              <div class="form-check mt-1">
                <input type="checkbox" name="remove_photo" value="1" class="form-check-input" id="removePhoto">
                <label for="removePhoto" class="form-check-label">Remove current photo</label>
              </div>
            </div>
          <?php endif; ?>
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