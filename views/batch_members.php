<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/pagination.php';

$search      = $_GET['search']               ?? '';
$gender      = $_GET['gender']               ?? '';
$component   = $_GET['component']            ?? '';
$conf_id     = $_GET['conference_id']        ?? '';

$componentLabels = ['MB' => 'Mother Sunbeam', 'AS' => 'Allen Stars', 'Y' => 'Youth', 'YA' => 'Young Adults'];

$fromWhere = " FROM members m
               LEFT JOIN conferences c ON m.conference_id = c.conference_id
               WHERE m.deleted_at IS NULL";
$params = [];

if ($search) {
    $fromWhere .= " AND (m.name LIKE ? OR m.surname_name LIKE ? OR m.contact LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($gender)      { $fromWhere .= " AND m.gender = ?";        $params[] = $gender; }
if ($component === '__EMPTY__') { $fromWhere .= " AND (m.component IS NULL OR m.component = '')"; }
elseif ($component)             { $fromWhere .= " AND m.component = ?";     $params[] = $component; }
if ($conf_id)     { $fromWhere .= " AND m.conference_id = ?"; $params[] = $conf_id; }

$pager = paginate($pdo, "SELECT COUNT(*)" . $fromWhere, $params, 200);

$selectCols = "m.member_id, m.name, m.surname_name, m.gender, m.component, m.contact, c.conference_name";
$query = "SELECT $selectCols" . $fromWhere . " ORDER BY m.surname_name, m.name LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bulk Member Operations — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-tasks me-2"></i>Bulk Member Operations</h5>
    <div>
      <a href="members.php" class="btn btn-secondary btn-sm">Back to Members</a>
    </div>
  </div>

  <?php if (isset($_GET['bulk_success'])): ?>
    <div class="alert alert-success py-2">Bulk update successful. Affected rows: <?= (int)($_GET['affected'] ?? 0) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['bulk_error'])): ?>
    <div class="alert alert-danger py-2">Bulk operation failed: <?= htmlspecialchars($_GET['msg'] ?? 'Unknown error') ?></div>
  <?php endif; ?>

  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name / contact" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
      <select name="gender" class="form-select form-select-sm">
        <option value="">-- Gender --</option>
        <option value="M" <?= $gender==='M'?'selected':'' ?>>Male</option>
        <option value="F" <?= $gender==='F'?'selected':'' ?>>Female</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="component" class="form-select form-select-sm">
        <option value="">-- Component --</option>
        <option value="__EMPTY__" <?= $component==='__EMPTY__'?'selected':'' ?>>Empty / Unknown</option>
        <option value="MB" <?= $component==='MB'?'selected':'' ?>>Mother Sunbeam</option>
        <option value="AS" <?= $component==='AS'?'selected':'' ?>>Allen Stars</option>
        <option value="Y"  <?= $component==='Y' ?'selected':'' ?>>Youth</option>
        <option value="YA" <?= $component==='YA'?'selected':'' ?>>Young Adults</option>
      </select>
    </div>
    <div class="col-md-3">
      <select name="conference_id" class="form-select form-select-sm">
        <option value="">-- Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $conf_id==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="batch_members.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>

  <form method="POST" action="../actions/bulk_members.php" id="bulkForm">
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
          <thead class="table-success">
            <tr>
              <th><input type="checkbox" id="selectAll"></th>
              <th>#</th>
              <th>Name</th>
              <th>Surname</th>
              <th>Gender</th>
              <th>Component</th>
              <th>Conference</th>
              <th>Contact</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($members)): ?>
              <tr><td colspan="8" class="text-center text-muted py-3">No members found.</td></tr>
            <?php else: foreach ($members as $i => $m): ?>
              <tr>
                <td><input type="checkbox" name="member_ids[]" value="<?= (int)$m['member_id'] ?>" class="member-checkbox"></td>
                <td><?= $pager['offset'] + $i + 1 ?></td>
                <td><?= htmlspecialchars($m['name']) ?></td>
                <td><?= htmlspecialchars($m['surname_name']) ?></td>
                <td><?= htmlspecialchars($m['gender'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['component'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['conference_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['contact'] ?? '—') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2 align-items-center">
          <select name="action" id="bulkAction" class="form-select form-select-sm" style="width:230px;">
            <option value="">-- Bulk action --</option>
            <option value="update_component">Update Component</option>
            <option value="update_conference">Update Conference</option>
            <option value="update_area">Update Area</option>
            <option value="delete">Delete (soft)</option>
          </select>

          <select name="component_value" id="componentValue" class="form-select form-select-sm d-none">
            <option value="">-- Select Component --</option>
            <option value="MB">Mother Sunbeam</option>
            <option value="AS">Allen Stars</option>
            <option value="Y">Youth</option>
            <option value="YA">Young Adults</option>
            <option value="">Clear Component</option>
          </select>

          <select name="conference_value" id="conferenceValue" class="form-select form-select-sm d-none">
            <option value="">-- Select Conference --</option>
            <?php foreach ($conferences as $c): ?>
              <option value="<?= $c['conference_id'] ?>"><?= htmlspecialchars($c['conference_name']) ?></option>
            <?php endforeach; ?>
            <option value="">Clear Conference</option>
          </select>

          <input type="text" name="area_value" id="areaValue" class="form-control form-control-sm d-none" placeholder="Area ID (number)">

          <button type="submit" class="btn btn-success btn-sm" id="applyBulk">Apply</button>
        </div>
        <div>
          <span class="text-muted small">Total matching: <?= $pager['total'] ?> member(s)</span>
          <?= render_pagination($pager) ?>
        </div>
      </div>
    </div>
  </form>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.getElementById('selectAll')?.addEventListener('change', function(e){
  document.querySelectorAll('.member-checkbox').forEach(cb => cb.checked = e.target.checked);
});

const bulkAction = document.getElementById('bulkAction');
const componentValue = document.getElementById('componentValue');
const conferenceValue = document.getElementById('conferenceValue');
const areaValue = document.getElementById('areaValue');

bulkAction?.addEventListener('change', function(){
  componentValue.classList.add('d-none');
  conferenceValue.classList.add('d-none');
  areaValue.classList.add('d-none');

  if (this.value === 'update_component') componentValue.classList.remove('d-none');
  if (this.value === 'update_conference') conferenceValue.classList.remove('d-none');
  if (this.value === 'update_area') conferenceValue.classList.remove('d-none'), areaValue.classList.remove('d-none');
});

// simple confirmation on delete
document.getElementById('bulkForm')?.addEventListener('submit', function(e){
  const act = bulkAction.value;
  if (!act) { e.preventDefault(); alert('Select a bulk action to perform.'); return; }
  if (act === 'delete') {
    if (!confirm('Delete selected members (soft delete)? This can be undone from the Recycle Bin.')) { e.preventDefault(); return; }
  }
});
</script>
</body>
</html>
