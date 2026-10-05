<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';
require_once '../includes/pagination.php';
require_once '../includes/reference_data.php';

$search      = $_GET['search']               ?? '';
$gender      = $_GET['gender']               ?? '';
$component   = $_GET['component']            ?? '';
$conf_id     = $_GET['conference_id']        ?? '';
$joined_ypd  = $_GET['joined_ypd']           ?? '';
$full_church = $_GET['full_member_of_church'] ?? '';
$occ_status  = $_GET['occupational_status']  ?? '';

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
// Support filtering for empty/NULL component values using sentinel '__EMPTY__'
if ($component === '__EMPTY__') { $fromWhere .= " AND (m.component IS NULL OR m.component = '')"; }
elseif ($component)             { $fromWhere .= " AND m.component = ?";     $params[] = $component; }
if ($conf_id)     { $fromWhere .= " AND m.conference_id = ?"; $params[] = $conf_id; }
if ($joined_ypd)  { $fromWhere .= " AND m.joined_ypd = ?";            $params[] = $joined_ypd; }
if ($full_church) { $fromWhere .= " AND m.full_member_of_church = ?"; $params[] = $full_church; }
if ($occ_status)  { $fromWhere .= " AND m.occupational_status = ?";   $params[] = $occ_status; }

$pager = paginate($pdo, "SELECT COUNT(*)" . $fromWhere, $params, 200);

$selectCols = "m.member_id, m.name, m.surname_name, m.gender, m.component, m.contact,
               m.eligible_to_vote_conference, m.current_status, c.conference_name,
               m.joined_ypd, m.full_member_of_church, m.occupational_status";

$query = "SELECT $selectCols"
    . $fromWhere
    . " ORDER BY m.surname_name, m.name
        LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

$conferences = get_conferences($pdo);

// Distinct occupational statuses for filter dropdown
$occStatuses = $pdo->query("SELECT DISTINCT occupational_status FROM members WHERE occupational_status IS NOT NULL AND occupational_status != '' ORDER BY occupational_status")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Members — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-users me-2"></i>Members</h5>
    <div class="d-flex gap-2">
      <a href="../actions/export.php?<?= http_build_query([
          'type'                 => 'members',
          'conference_id'        => $conf_id,
          'search'               => $search,
          'gender'               => $gender,
          'component'            => $component,
          'joined_ypd'           => $joined_ypd,
          'full_member_of_church'=> $full_church,
          'occupational_status'  => $occ_status,
      ]) ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
      <a href="batch_members.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-tasks me-1"></i>Bulk Ops</a>
      <a href="../forms/add_member.php" class="btn btn-success btn-sm"><i class="fas fa-user-plus me-1"></i>Add Member</a>
    </div>
  </div>

  <?php if (isset($_GET['deleted']) && isset($_GET['deleted_item_id'])): ?>
    <div class="alert alert-warning py-2">Member deleted. <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$_GET['deleted_item_id'] ?>"><button type="submit" class="btn btn-link btn-sm p-0 align-baseline">Undo</button></form></div>
  <?php endif; ?>

  <?php if (isset($_GET['bulk_success'])): ?>
    <div class="alert alert-success py-2">Bulk update successful. Affected rows: <?= (int)($_GET['affected'] ?? 0) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['bulk_error'])): ?>
    <div class="alert alert-danger py-2">Bulk operation failed: <?= htmlspecialchars($_GET['msg'] ?? 'Unknown error') ?></div>
  <?php endif; ?>

  <!-- Filters -->
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
    <div class="col-md-2">
      <select name="conference_id" class="form-select form-select-sm">
        <option value="">-- Conference --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $conf_id==$c['conference_id']?'selected':'' ?>><?= htmlspecialchars($c['conference_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="joined_ypd" class="form-select form-select-sm">
        <option value="">-- Joined YPD --</option>
        <option value="Yes" <?= $joined_ypd==='Yes'?'selected':'' ?>>Yes</option>
        <option value="No"  <?= $joined_ypd==='No' ?'selected':'' ?>>No</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="full_member_of_church" class="form-select form-select-sm">
        <option value="">-- Full Church Member --</option>
        <option value="Yes" <?= $full_church==='Yes'?'selected':'' ?>>Yes</option>
        <option value="No"  <?= $full_church==='No' ?'selected':'' ?>>No</option>
      </select>
    </div>
    <?php if ($occStatuses): ?>
    <div class="col-md-2">
      <select name="occupational_status" class="form-select form-select-sm">
        <option value="">-- Occupation --</option>
        <?php foreach ($occStatuses as $os): ?>
          <option value="<?= htmlspecialchars($os) ?>" <?= $occ_status===$os?'selected':'' ?>><?= htmlspecialchars($os) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="members.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>

  <!-- Table -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-bordered table-hover table-sm mb-0">
        <thead class="table-success">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Surname</th>
            <th>Gender</th>
            <th>Component</th>
            <th>Conference</th>
            <th>Joined YPD</th>
            <th>Full Member</th>
            <th>Occupation</th>
            <th>Contact</th>
            <th>Vote (Conf)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($members)): ?>
            <tr><td colspan="13" class="text-center text-muted py-3">No members found.</td></tr>
          <?php else: foreach ($members as $i => $m): ?>
            <tr>
              <td><?= $pager['offset'] + $i + 1 ?></td>
              <td><?= htmlspecialchars($m['name']) ?></td>
              <td><?= htmlspecialchars($m['surname_name']) ?></td>
              <td><?= $m['gender'] ?? '—' ?></td>
              <td>
                <?php if ($m['component']): ?>
                  <span class="badge bg-success"><?= htmlspecialchars($m['component']) ?></span>
                  <span class="small text-muted d-none d-xl-inline"><?= htmlspecialchars($componentLabels[$m['component']] ?? '') ?></span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><?= htmlspecialchars($m['conference_name'] ?? '—') ?></td>
              <td><?= match($m['joined_ypd'] ?? '') { 'Yes' => '<span class="badge bg-success">Yes</span>', 'No' => '<span class="badge bg-secondary">No</span>', default => '—' } ?></td>
              <td><?= match($m['full_member_of_church'] ?? '') { 'Yes' => '<span class="badge bg-primary">Yes</span>', 'No' => '<span class="badge bg-secondary">No</span>', default => '—' } ?></td>
              <td><?= htmlspecialchars($m['occupational_status'] ?? '—') ?></td>
              <td><?= htmlspecialchars($m['contact'] ?? '—') ?></td>
              <td><?= $m['eligible_to_vote_conference'] ?></td>
              <td class="text-nowrap">
                <a href="../forms/edit_member.php?id=<?= $m['member_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                <form method="POST" action="../actions/delete_member.php" class="d-inline"><input type="hidden" name="id" value="<?= $m['member_id'] ?>"><button type="submit" class="btn btn-danger btn-sm js-confirm-delete">Del</button></form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <span class="text-muted small">Total: <?= $pager['total'] ?> member(s)</span>
      <?= render_pagination($pager) ?>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
