<?php
$leaders = $pdo->query("SELECT * FROM legacy_leaders ORDER BY COALESCE(start_year, 9999), full_name")->fetchAll();
$milestones = $pdo->query("SELECT * FROM milestones ORDER BY milestone_year")->fetchAll();
$foundedYear = $pdo->query("SELECT MIN(start_year) FROM legacy_leaders")->fetchColumn();
$districtAge = ($foundedYear && (int)$foundedYear > 0) ? ((int)date('Y') - (int)$foundedYear) : null;

$timeline = [];
foreach ($leaders as $l) {
    $timeline[] = ['year' => (int)($l['start_year'] ?? 0), 'type' => 'Leader', 'title' => $l['full_name'], 'meta' => $l['role_type'], 'text' => $l['descriptions'] ?? $l['achievements'], 'photo' => $l['photo_path'], 'end_year' => $l['end_year'], 'conference' => $l['conference_name'], 'id' => $l['leader_id']];
}
foreach ($milestones as $m) {
    $timeline[] = ['year' => (int)($m['milestone_year'] ?? 0), 'type' => 'Milestone', 'title' => $m['title'], 'meta' => 'District', 'text' => $m['descriptions'] ?? $m['achievements'], 'id' => $m['milestone_id']];
}
usort($timeline, fn($a, $b) => $a['year'] <=> $b['year']);
?>
<h4 class="fw-bold text-success mb-3">District History & Timeline</h4>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Legacy Leaders</div><div class="fs-4 fw-bold"><?= count($leaders) ?></div></div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Milestones</div><div class="fs-4 fw-bold"><?= count($milestones) ?></div></div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">District Start Year</div><div class="fs-4 fw-bold"><?= $foundedYear ? (int)$foundedYear : '—' ?></div></div></div></div>
  <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">District Age</div><div class="fs-4 fw-bold"><?= $districtAge !== null ? $districtAge . ' yrs' : '—' ?></div></div></div></div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold">Timeline (Leaders + Milestones)</div>
  <div class="card-body p-0">
    <table class="table table-sm table-bordered mb-0">
      <thead class="table-light">
        <tr><th>Year</th><th>Type</th><th>Title</th><th>Meta</th><th>Notes</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($timeline)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">No historical entries yet.</td></tr>
        <?php else: foreach ($timeline as $t): ?>
          <tr>
            <td><?= $t['year'] > 0 ? (int)$t['year'] : '—' ?></td>
            <td><?= h($t['type']) ?></td>
            <td><?= h($t['title']) ?></td>
            <td><?= h($t['meta']) ?></td>
            <td><?= h($t['text'] ?? '—') ?></td>
            <td class="text-nowrap">
              <?php if ($t['type'] === 'Leader'): ?>
                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#leaderModal<?= (int)$t['id'] ?>"><i class="fas fa-eye"></i></button>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#milestoneModal<?= (int)$t['id'] ?>"><i class="fas fa-eye"></i></button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Leader Detail Modals -->
<?php foreach ($leaders as $l): ?>
<div class="modal fade" id="leaderModal<?= (int)$l['leader_id'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= h($l['full_name']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (!empty($l['photo_path'])): ?>
          <div class="text-center mb-3">
            <img src="../<?= h($l['photo_path']) ?>" alt="<?= h($l['full_name']) ?>" class="img-fluid rounded" style="max-height: 200px;" loading="lazy">
          </div>
        <?php endif; ?>
        <table class="table table-sm">
          <tr><th>Role</th><td><?= h($l['role_type']) ?></td></tr>
          <tr><th>Conference</th><td><?= h($l['conference_name'] ?? '—') ?></td></tr>
          <tr><th>Start Year</th><td><?= (int)$l['start_year'] ?: '—' ?></td></tr>
          <tr><th>End Year</th><td><?= (int)$l['end_year'] ?: '—' ?></td></tr>
        </table>
        <?php if (!empty($l['descriptions'])): ?>
          <h6>Descriptions</h6>
          <p><?= nl2br(h($l['descriptions'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($l['achievements'])): ?>
          <h6>Achievements</h6>
          <p><?= nl2br(h($l['achievements'])) ?></p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Milestone Detail Modals -->
<?php foreach ($milestones as $m): ?>
<div class="modal fade" id="milestoneModal<?= (int)$m['milestone_id'] ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?= h($m['title']) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-sm">
          <tr><th>Year</th><td><?= (int)$m['milestone_year'] ?></td></tr>
        </table>
        <?php if (!empty($m['descriptions'])): ?>
          <h6>Descriptions</h6>
          <p><?= nl2br(h($m['descriptions'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($m['achievements'])): ?>
          <h6>Achievements</h6>
          <p><?= nl2br(h($m['achievements'])) ?></p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
