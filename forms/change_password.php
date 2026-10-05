<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:500px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-key me-2"></i>Change Password</h5>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible">Password changed successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible">
      <?php
      $error = $_GET['error'];
      if ($error === 'current_wrong') echo 'Current password is incorrect.';
      elseif ($error === 'mismatch') echo 'New passwords do not match.';
      elseif ($error === 'too_short') echo 'New password must be at least 8 characters.';
      else echo 'An error occurred. Please try again.';
      ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form method="POST" action="../actions/change_password.php">
    <div class="mb-3">
      <label class="form-label">Current Password <span class="text-danger">*</span></label>
      <input type="password" name="current_password" class="form-control" required>
    </div>
    
    <div class="mb-3">
      <label class="form-label">New Password <span class="text-danger">*</span></label>
      <input type="password" name="new_password" class="form-control" required minlength="8">
      <div class="small text-muted">Minimum 8 characters</div>
    </div>
    
    <div class="mb-4">
      <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
      <input type="password" name="confirm_password" class="form-control" required minlength="8">
    </div>
    
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Change Password</button>
    <a href="../index.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>