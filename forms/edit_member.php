<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) { header("Location: ../views/members.php"); exit; }

$id = (int)$_GET['id'];
$member = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$member->execute([$id]);
$m = $member->fetch();

if (!$m) { header("Location: ../views/members.php"); exit; }

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();
$areas       = $pdo->query("SELECT area_id, area_name, conference_id FROM areas ORDER BY CAST(TRIM(SUBSTRING(area_name, LOCATE(' ', area_name) + 1)) AS UNSIGNED), area_name")->fetchAll();
$churches    = $pdo->query("SELECT church_id, local_church_name, area_id, conference_id FROM churches WHERE status='active' ORDER BY local_church_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Member — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container mt-4" style="max-width:700px">
  <h5 class="fw-bold text-success mb-4"><i class="fas fa-user-edit me-2"></i>Edit Member</h5>
  <?php if (isset($_GET['error'])): ?><div class="alert alert-danger py-2">Validation error: <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

  <form method="POST" action="../actions/update_member.php">
    <input type="hidden" name="member_id" value="<?= $m['member_id'] ?>">

    <h6 class="text-muted mb-3 border-bottom pb-1">Personal Information</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($m['name']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Surname <span class="text-danger">*</span></label>
        <input type="text" name="surname_name" class="form-control" value="<?= htmlspecialchars($m['surname_name']) ?>" required>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select">
          <option value="">--</option>
          <option value="M" <?= $m['gender']==='M'?'selected':'' ?>>Male</option>
          <option value="F" <?= $m['gender']==='F'?'selected':'' ?>>Female</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($m['dob'] ?? '') ?>" max="<?= date('Y-m-d') ?>" data-validate-date="true">
      </div>
      <div class="col-md-4">
        <label class="form-label">Contact</label>
        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($m['contact'] ?? '') ?>" pattern="[0-9+\s()\-]{7,20}" data-validate-phone="true">
        <div class="small text-muted">Use digits/+, spaces, brackets or dashes (7-20 chars).</div>
      </div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Member No.</label>
        <input type="text" name="member_no" class="form-control" value="<?= htmlspecialchars($m['member_no'] ?? '') ?>" placeholder="e.g. 1234">
      </div>
      <div class="col-md-4">
        <label class="form-label">Occupational Status</label>
        <select name="occupational_status" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach (['Employed','Unemployed','Student','Learner','Other'] as $os): ?>
            <option value="<?= $os ?>" <?= ($m['occupational_status'] ?? '') === $os ? 'selected' : '' ?>><?= $os ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Component</label>
        <select name="component" class="form-select" data-choices="off">
          <option value="">--</option>
          <?php foreach (['MB'=>'Mother Sunbeam','AS'=>'Allen Stars','Y'=>'Youth','YA'=>'Young Adults'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $m['component']===$val?'selected':'' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label">Joined YPD</label>
        <select name="joined_ypd" class="form-select">
          <option value="">--</option>
          <option value="Yes" <?= ($m['joined_ypd'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
          <option value="No"  <?= ($m['joined_ypd'] ?? '') === 'No'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Full Member of Church</label>
        <select name="full_member_of_church" class="form-select">
          <option value="">--</option>
          <option value="Yes" <?= ($m['full_member_of_church'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
          <option value="No"  <?= ($m['full_member_of_church'] ?? '') === 'No'  ? 'selected' : '' ?>>No</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Current Status <span class="text-muted small">(legacy)</span></label>
        <select name="current_status" class="form-select">
          <?php foreach (['Other','Learner','Student','Employed','Unemployed'] as $s): ?>
            <option value="<?= $s ?>" <?= $m['current_status']===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Church Membership</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label class="form-label">Conference</label>
        <select name="conference_id" id="confSelect" class="form-select" data-dynamic-select="true" data-choices="off">
          <option value="">--</option>
          <?php foreach ($conferences as $c): ?>
            <option value="<?= $c['conference_id'] ?>" <?= $m['conference_id']==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Area</label>
        <select name="area_id" id="areaSelect" class="form-select" data-dynamic-select="true" data-choices="off">
          <option value="">--</option>
          <?php foreach ($areas as $a): ?>
            <option value="<?= $a['area_id'] ?>" data-conf="<?= $a['conference_id'] ?>" <?= $m['area_id']==$a['area_id']?'selected':'' ?>><?= htmlspecialchars($a['area_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label">Church</label>
      <select name="church_id" id="churchSelect" class="form-select" data-dynamic-select="true" data-choices="off">
        <option value="">--</option>
        <?php foreach ($churches as $ch): ?>
          <option value="<?= $ch['church_id'] ?>" data-area="<?= $ch['area_id'] ?>" data-conf="<?= $ch['conference_id'] ?>" <?= $m['church_id']==$ch['church_id']?'selected':'' ?>><?= htmlspecialchars($ch['local_church_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <h6 class="text-muted mb-3 border-bottom pb-1">Voting & Robing</h6>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <label class="form-label">Vote at Conference</label>
        <select name="eligible_to_vote_conference" class="form-select">
          <option value="No"  <?= $m['eligible_to_vote_conference']==='No' ?'selected':'' ?>>No</option>
          <option value="Yes" <?= $m['eligible_to_vote_conference']==='Yes'?'selected':'' ?>>Yes</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Vote at Episcopal</label>
        <select name="eligible_to_vote_episcopal" class="form-select">
          <option value="No"  <?= $m['eligible_to_vote_episcopal']==='No' ?'selected':'' ?>>No</option>
          <option value="Yes" <?= $m['eligible_to_vote_episcopal']==='Yes'?'selected':'' ?>>Yes</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Robbed</label>
        <select name="robbed" id="robbedSelect" class="form-select">
          <option value="No"  <?= $m['robbed']==='No' ?'selected':'' ?>>No</option>
          <option value="Yes" <?= $m['robbed']==='Yes'?'selected':'' ?>>Yes</option>
        </select>
      </div>
    </div>

    <div class="mb-4" id="yearRobbedWrap" style="display:<?= $m['robbed']==='Yes'?'block':'none' ?>">
      <label class="form-label">Year Robbed</label>
      <input type="number" name="year_robbed" class="form-control" value="<?= htmlspecialchars($m['year_robbed'] ?? '') ?>" min="1900" max="<?= date('Y') ?>">
    </div>

    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Member</button>
    <a href="../views/members.php" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.getElementById('robbedSelect').addEventListener('change', function () {
    document.getElementById('yearRobbedWrap').style.display = this.value === 'Yes' ? 'block' : 'none';
});
document.getElementById('confSelect').addEventListener('change', function () {
    const confId = this.value;
    document.querySelectorAll('#areaSelect option[data-conf]').forEach(o => {
        o.style.display = (!confId || o.dataset.conf === confId) ? '' : 'none';
    });
});
document.getElementById('areaSelect').addEventListener('change', function () {
    const areaId = this.value;
    document.querySelectorAll('#churchSelect option[data-area]').forEach(o => {
        o.style.display = (!areaId || o.dataset.area === areaId) ? '' : 'none';
    });
});
</script>
</body>
</html>
