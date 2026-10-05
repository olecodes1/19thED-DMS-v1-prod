<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$areas       = $pdo->query("SELECT area_id, area_name, conference_id FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
$churches    = $pdo->query("SELECT church_id, local_church_name, area_id, conference_id FROM churches WHERE status='active' ORDER BY local_church_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Member — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:700px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-user-plus me-2"></i>Add Member</h5>
  <?php if (isset($_GET['error'])): ?><div class="alert alert-danger py-2">Validation error: <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible">Member added successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>

  <form method="POST" action="../actions/process_member.php">
    <h6 class="text-muted mb-3 border-bottom pb-1">Personal Information</h6>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Surname <span class="text-danger">*</span></label>
        <input type="text" name="surname_name" class="form-control" required>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">-- Select --</option>
          <option value="M">Male</option>
          <option value="F">Female</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="dob" class="form-control" max="<?= date('Y-m-d') ?>" data-validate-date="true">
      </div>
      <div class="col-md-4">
        <label class="form-label">Contact</label>
        <input type="text" name="contact" class="form-control" placeholder="e.g. 0821234567" pattern="[0-9+\s()\-]{7,20}" data-validate-phone="true">
        <div class="small text-muted">Use digits/+, spaces, brackets or dashes (7-20 chars).</div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">

      <div class="col-md-4">
        <label class="form-label">Occupational Status</label>
        <select name="occupational_status" class="form-select">
          <option value="">-- Select --</option>
          <option value="Employed">Employed</option>
          <option value="Unemployed">Unemployed</option>
          <option value="Student">Student</option>
          <option value="Learner">Learner</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Component</label>
        <select name="component" class="form-select" data-choices="off">
          <option value="">-- Select --</option>
          <option value="MB">Mother Sunbeam (MB)</option>
          <option value="AS">Allen Stars (AS)</option>
          <option value="Y">Youth (Y)</option>
          <option value="YA">Young Adults (YA)</option>
        </select>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label">Joined YPD</label>
        <select name="joined_ypd" class="form-select">
          <option value="">-- Select --</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Full Member of Church</label>
        <select name="full_member_of_church" class="form-select">
          <option value="">-- Select --</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Current Status <span class="text-muted small">(legacy)</span></label>
        <select name="current_status" class="form-select">
          <option value="Other">Other</option>
          <option value="Learner">Learner</option>
          <option value="Student">Student</option>
          <option value="Employed">Employed</option>
          <option value="Unemployed">Unemployed</option>
        </select>
      </div>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Church Membership</h6>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Conference <span class="text-danger">*</span></label>
        <select name="conference_id" id="confSelect" class="form-select" data-dynamic-select="true" data-choices="off" required>
          <option value="">-- Select Conference --</option>
          <?php foreach ($conferences as $c): ?>
            <option value="<?= $c['conference_id'] ?>"><?= htmlspecialchars($c['conference_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Area</label>
        <select name="area_id" id="areaSelect" class="form-select" data-dynamic-select="true" data-choices="off">
          <option value="">-- Select Area --</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['area_id'] ?>" data-conf="<?= $a['conference_id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label">Church</label>
      <select name="church_id" id="churchSelect" class="form-select" data-dynamic-select="true" data-choices="off">
        <option value="">-- Select Church --</option>
        <?php foreach ($churches as $ch): ?>
          <option value="<?= $ch['church_id'] ?>" data-area="<?= $ch['area_id'] ?>" data-conf="<?= $ch['conference_id'] ?>"><?= htmlspecialchars($ch['local_church_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Voting & Robing</h6>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Vote at Conference</label>
        <select name="eligible_to_vote_conference" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Vote at Episcopal</label>
        <select name="eligible_to_vote_episcopal" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Robbed</label>
        <select name="robbed" id="robbedSelect" class="form-select">
          <option value="No">No</option>
          <option value="Yes">Yes</option>
        </select>
      </div>
    </div>

    <div class="mb-4" id="yearRobbedWrap" style="display:none">
      <label class="form-label">Year Robbed</label>
      <input type="number" name="year_robbed" class="form-control" placeholder="e.g. 2022" min="1900" max="<?= date('Y') ?>">
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Member</button>
    <a href="../views/members.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
// Show/hide year robbed
document.getElementById('robbedSelect').addEventListener('change', function () {
    document.getElementById('yearRobbedWrap').style.display = this.value === 'Yes' ? 'block' : 'none';
});

// Filter areas by conference
document.getElementById('confSelect').addEventListener('change', function () {
    const confId = this.value;
    document.querySelectorAll('#areaSelect option[data-conf]').forEach(o => {
        o.style.display = (!confId || o.dataset.conf === confId) ? '' : 'none';
    });
    document.getElementById('areaSelect').value = '';
});

// Filter churches by area
document.getElementById('areaSelect').addEventListener('change', function () {
    const areaId = this.value;
    document.querySelectorAll('#churchSelect option[data-area]').forEach(o => {
        o.style.display = (!areaId || o.dataset.area === areaId) ? '' : 'none';
    });
    document.getElementById('churchSelect').value = '';
});
</script>
</body>
</html>
