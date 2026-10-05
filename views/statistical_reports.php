<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$conf_id = $_GET['conference_id'] ?? '';

// ── Main per-conference stats ──────────────────────────────────────────────
// OPTIMIZED: Single pass with LEFT JOINs instead of correlated subqueries

// Pre-aggregated membership data per conference (one pass over members)
$memberSub = "SELECT conference_id,
                 COUNT(*) AS total_members,
                 SUM(component = 'MB') AS MB,
                 SUM(component = 'AS') AS AS_,
                 SUM(component = 'Y')  AS Y,
                 SUM(component = 'YA') AS YA,
                 SUM(gender = 'M') AS male,
                 SUM(gender = 'F') AS female,
                 SUM(eligible_to_vote_conference = 'Yes') AS vote_conf_yes,
                 SUM(eligible_to_vote_episcopal  = 'Yes') AS vote_epi_yes,
                 SUM(joined_ypd = 'Yes') AS joined_ypd_yes,
                 SUM(full_member_of_church = 'Yes') AS full_church_yes
                 FROM members GROUP BY conference_id";

// Pre-aggregated events per conference
$eventSub = "SELECT conference_id,
                 COUNT(*) AS events_count,
                 COALESCE(SUM(attendance_count),0) AS total_attendance
          FROM events
          GROUP BY conference_id";

// Pre-aggregated church activity per conference (uses church_member_summary)
$churchSub = "SELECT conference_id,
                     SUM(CASE WHEN cms.member_count >= 1 THEN 1 ELSE 0 END) AS active_churches,
                     SUM(CASE WHEN cms.member_count = 0 THEN 1 ELSE 0 END) AS inactive_churches
              FROM churches ch
              LEFT JOIN church_member_summary cms ON cms.church_id = ch.church_id
              GROUP BY conference_id";

// Main query — efficient joins instead of correlated subqueries
$sql = "SELECT c.conference_id, c.conference_name,
               COALESCE(m.total_members, 0) AS total_members,
               COALESCE(m.MB, 0) AS MB,
               COALESCE(m.AS_, 0) AS AS_,
               COALESCE(m.Y, 0) AS Y,
               COALESCE(m.YA, 0) AS YA,
               COALESCE(m.male, 0) AS male,
               COALESCE(m.female, 0) AS female,
               COALESCE(m.vote_conf_yes, 0) AS vote_conf_yes,
               COALESCE(m.vote_epi_yes, 0) AS vote_epi_yes,
               COALESCE(m.joined_ypd_yes, 0) AS joined_ypd_yes,
               COALESCE(m.full_church_yes, 0) AS full_church_yes,
               COALESCE(ev.events_count, 0) AS events_count,
               COALESCE(ev.total_attendance, 0) AS total_attendance,
               COALESCE(ch.active_churches, 0) AS active_churches,
               COALESCE(ch.inactive_churches, 0) AS inactive_churches
        FROM conferences c
        LEFT JOIN ($memberSub) m ON m.conference_id = c.conference_id
        LEFT JOIN ($eventSub) ev ON ev.conference_id = c.conference_id
        LEFT JOIN ($churchSub) ch ON ch.conference_id = c.conference_id
        WHERE 1=1";
$params = [];
if ($conf_id) { $sql .= " AND c.conference_id = ?"; $params[] = $conf_id; }
$sql .= " ORDER BY c.conference_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats = $stmt->fetchAll();

// ── Occupational status breakdown ─────────────────────────────────────────
$occBreakdown = [];
$occSql = "SELECT c.conference_name, m.occupational_status, COUNT(*) AS cnt
           FROM members m
           LEFT JOIN conferences c ON m.conference_id = c.conference_id
           WHERE m.occupational_status IS NOT NULL AND m.occupational_status != ''";
$occParams = [];
if ($conf_id) { $occSql .= " AND m.conference_id = ?"; $occParams[] = $conf_id; }
$occSql .= " GROUP BY c.conference_name, m.occupational_status ORDER BY c.conference_name, cnt DESC";
$occBreakdown = $pdo->prepare($occSql);
$occBreakdown->execute($occParams);
$occBreakdown = $occBreakdown->fetchAll();

// District-wide totals by occupational status
$occTotalsSql = "SELECT occupational_status, COUNT(*) AS cnt FROM members
                 WHERE occupational_status IS NOT NULL AND occupational_status != ''";
$occTotalsParams = [];
if ($conf_id) { $occTotalsSql .= " AND conference_id = ?"; $occTotalsParams[] = $conf_id; }
$occTotalsSql .= " GROUP BY occupational_status ORDER BY cnt DESC";
$occTotals = $pdo->prepare($occTotalsSql);
$occTotals->execute($occTotalsParams);
$occTotals = $occTotals->fetchAll();

$conferences = $pdo->query("SELECT conference_id, conference_name FROM conferences ORDER BY conference_name")->fetchAll();

// Totals row
$totals = ['total_members'=>0,'MB'=>0,'AS_'=>0,'Y'=>0,'YA'=>0,'male'=>0,'female'=>0,
           'vote_conf_yes'=>0,'vote_epi_yes'=>0,'joined_ypd_yes'=>0,'full_church_yes'=>0,
           'events_count'=>0,'total_attendance'=>0,
           'active_churches'=>0,'inactive_churches'=>0];
foreach ($stats as $row) {
    foreach (array_keys($totals) as $k) {
        $totals[$k] += (int)($row[$k] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Statistical Reports — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <style>
    @media print {
      nav, footer, .no-print { display: none !important; }
      .container-fluid { margin: 0 !important; padding: 0 !important; }
    }
  </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-chart-bar me-2"></i>Statistical Reports</h5>
    <div class="d-flex gap-2 no-print">
      <a href="../actions/export.php?<?= http_build_query(['type' => 'stats', 'conference_id' => $conf_id]) ?>" class="btn btn-outline-success btn-sm">
        <i class="fas fa-file-csv me-1"></i>Export CSV
      </a>
      <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Print
      </button>
    </div>
  </div>

  <form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-3">
      <select name="conference_id" class="form-select form-select-sm">
        <option value="">-- All Conferences --</option>
        <?php foreach ($conferences as $c): ?>
          <option value="<?= $c['conference_id'] ?>" <?= $conf_id == $c['conference_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['conference_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <a href="statistical_reports.php" class="btn btn-secondary btn-sm">Reset</a>
    </div>
  </form>

  <!-- ── Section 1: Per-Conference Summary ── -->
  <h6 class="text-muted fw-semibold mb-2 border-bottom pb-1">Conference Breakdown</h6>
  <div class="card shadow-sm mb-4">
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-bordered table-sm mb-0">
        <thead class="table-success">
          <tr>
            <th>Conference</th>
            <th>Total</th>
            <th>MB</th><th>AS</th><th>Y</th><th>YA</th>
            <th>Male</th><th>Female</th>
            <th>Vote Conf</th><th>Vote Epis</th>
            <th>Joined YPD</th>
            <th>Full Church</th>
            <th title="Active churches (≥1 YPD member)">Active Ch.</th>
            <th title="Inactive churches (0 YPD members)">Inactive Ch.</th>
            <th>Events</th><th>Attendance</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($stats)): ?>
            <tr><td colspan="16" class="text-center text-muted py-3">No data found.</td></tr>
          <?php else: foreach ($stats as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['conference_name']) ?></td>
              <td class="fw-bold"><?= (int)$row['total_members'] ?></td>
              <td><?= (int)$row['MB'] ?></td>
              <td><?= (int)$row['AS_'] ?></td>
              <td><?= (int)$row['Y'] ?></td>
              <td><?= (int)$row['YA'] ?></td>
              <td><?= (int)$row['male'] ?></td>
              <td><?= (int)$row['female'] ?></td>
              <td><?= (int)$row['vote_conf_yes'] ?></td>
              <td><?= (int)$row['vote_epi_yes'] ?></td>
              <td><?= (int)($row['joined_ypd_yes'] ?? 0) ?></td>
              <td><?= (int)($row['full_church_yes'] ?? 0) ?></td>
              <td><?= (int)($row['active_churches'] ?? 0) ?></td>
              <td>
                <?php $inact = (int)($row['inactive_churches'] ?? 0); ?>
                <?php if ($inact > 0): ?>
                  <span class="badge bg-warning text-dark"><?= $inact ?></span>
                <?php else: ?>
                  0
                <?php endif; ?>
              </td>
              <td><?= (int)$row['events_count'] ?></td>
              <td><?= (int)$row['total_attendance'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
        <tfoot class="table-light fw-bold">
          <tr>
            <td>TOTAL</td>
            <td><?= $totals['total_members'] ?></td>
            <td><?= $totals['MB'] ?></td>
            <td><?= $totals['AS_'] ?></td>
            <td><?= $totals['Y'] ?></td>
            <td><?= $totals['YA'] ?></td>
            <td><?= $totals['male'] ?></td>
            <td><?= $totals['female'] ?></td>
            <td><?= $totals['vote_conf_yes'] ?></td>
            <td><?= $totals['vote_epi_yes'] ?></td>
            <td><?= $totals['joined_ypd_yes'] ?></td>
            <td><?= $totals['full_church_yes'] ?></td>
            <td><?= $totals['active_churches'] ?></td>
            <td><?= $totals['inactive_churches'] > 0 ? '<span class="badge bg-warning text-dark">'.$totals['inactive_churches'].'</span>' : '0' ?></td>
            <td><?= $totals['events_count'] ?></td>
            <td><?= $totals['total_attendance'] ?></td>
          </tr>
        </tfoot>
      </table>
      </div>
    </div>
    <div class="card-footer text-muted small">
      MB = Mother Sunbeam &nbsp;|&nbsp; AS = Allen Stars &nbsp;|&nbsp; Y = Youth &nbsp;|&nbsp; YA = Young Adults
    </div>
  </div>


  <!-- ── Section 2: YPD & Full-Church Summary ── -->
  <?php if ($totals['total_members'] > 0): ?>
  <h6 class="text-muted fw-semibold mb-2 border-bottom pb-1">YPD &amp; Church Membership</h6>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success small">Joined YPD</div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="ypdChart" height="160"></canvas>
          <div class="d-flex gap-4 mt-2 text-center small">
            <div><div class="fw-bold fs-5 text-success"><?= $totals['joined_ypd_yes'] ?></div><div class="text-muted">Yes</div></div>
            <div><div class="fw-bold fs-5 text-secondary"><?= $totals['total_members'] - $totals['joined_ypd_yes'] ?></div><div class="text-muted">No / Unknown</div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success small">Full Church Member</div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <canvas id="fullChurchChart" height="160"></canvas>
          <div class="d-flex gap-4 mt-2 text-center small">
            <div><div class="fw-bold fs-5 text-primary"><?= $totals['full_church_yes'] ?></div><div class="text-muted">Yes</div></div>
            <div><div class="fw-bold fs-5 text-secondary"><?= $totals['total_members'] - $totals['full_church_yes'] ?></div><div class="text-muted">No / Unknown</div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($occTotals)): ?>
  <!-- ── Section 3: Occupational Status ── -->
  <h6 class="text-muted fw-semibold mb-2 border-bottom pb-1">Occupational Status</h6>
  <div class="row g-3 mb-4">
    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success small">District-wide Totals</div>
        <div class="card-body p-0">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light"><tr><th>Status</th><th>Count</th><th>%</th></tr></thead>
            <tbody>
              <?php foreach ($occTotals as $ot): ?>
              <tr>
                <td><?= htmlspecialchars($ot['occupational_status']) ?></td>
                <td><?= (int)$ot['cnt'] ?></td>
                <td><?= $totals['total_members'] > 0 ? round($ot['cnt'] / $totals['total_members'] * 100, 1) : 0 ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success small">Occupational Distribution</div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <canvas id="occChart" height="200"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-3">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold text-success small">By Conference</div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:260px; overflow-y:auto">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light"><tr><th>Conference</th><th>Status</th><th>#</th></tr></thead>
            <tbody>
              <?php foreach ($occBreakdown as $ob): ?>
              <tr>
                <td class="small"><?= htmlspecialchars($ob['conference_name'] ?? '—') ?></td>
                <td class="small"><?= htmlspecialchars($ob['occupational_status']) ?></td>
                <td><?= (int)$ob['cnt'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
<?php if ($totals['total_members'] > 0): ?>
new Chart(document.getElementById('ypdChart'), {
  type: 'doughnut',
  data: {
    labels: ['Joined YPD', 'Not / Unknown'],
    datasets: [{ data: [<?= $totals['joined_ypd_yes'] ?>, <?= $totals['total_members'] - $totals['joined_ypd_yes'] ?>],
      backgroundColor: ['#198754','#dee2e6'] }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});

new Chart(document.getElementById('fullChurchChart'), {
  type: 'doughnut',
  data: {
    labels: ['Full Member', 'Not / Unknown'],
    datasets: [{ data: [<?= $totals['full_church_yes'] ?>, <?= $totals['total_members'] - $totals['full_church_yes'] ?>],
      backgroundColor: ['#0d6efd','#dee2e6'] }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
});
<?php endif; ?>

<?php if (!empty($occTotals)): ?>
const occLabels = <?= json_encode(array_column($occTotals, 'occupational_status')) ?>;
const occData   = <?= json_encode(array_map(fn($r) => (int)$r['cnt'], $occTotals)) ?>;
const occColors = ['#198754','#0d6efd','#ffc107','#dc3545','#6c757d','#0dcaf0','#fd7e14','#6f42c1'];
new Chart(document.getElementById('occChart'), {
  type: 'doughnut',
  data: {
    labels: occLabels,
    datasets: [{ data: occData, backgroundColor: occColors.slice(0, occLabels.length) }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
});
<?php endif; ?>
</script>
</body>
</html>
