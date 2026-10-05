<?php
require_once '../includes/access_control.php';
require_once '../includes/auth.php';

$items = $pdo->query("
    SELECT deleted_id, entity_table, entity_id, source_path, deleted_at, restored_at
    FROM deleted_items
    ORDER BY deleted_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Recycle Bin — 19th Episcopal District</title>
  <link rel="icon" type="image/png" href="../19thDistrict.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container-fluid mt-4 px-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold text-success mb-0"><i class="fas fa-trash-restore me-2"></i>Recycle Bin</h5>
  </div>
  <?php if (isset($_GET['restored'])): ?><div class="alert alert-success py-2">Record restored successfully.</div><?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <table class="table table-bordered table-sm mb-0">
        <thead class="table-success"><tr><th>#</th><th>Entity</th><th>Record ID</th><th>Deleted From</th><th>Deleted At</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($items)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">Recycle bin is empty.</td></tr>
        <?php else: foreach ($items as $i => $it): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($it['entity_table']) ?></td>
            <td><?= (int)$it['entity_id'] ?></td>
            <td><?= htmlspecialchars($it['source_path'] ?: '—') ?></td>
            <td><?= htmlspecialchars($it['deleted_at']) ?></td>
            <td><?= $it['restored_at'] ? '<span class="badge bg-secondary">Restored</span>' : '<span class="badge bg-warning text-dark">Deleted</span>' ?></td>
            <td>
              <?php if (!$it['restored_at']): ?>
                <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$it['deleted_id'] ?>"><input type="hidden" name="redirect" value="../views/recycle_bin.php"><button type="submit" class="btn btn-success btn-sm">Restore Here</button></form>
                <?php if (!empty($it['source_path'])): ?>
                  <form method="POST" action="../actions/restore_deleted.php" class="d-inline"><input type="hidden" name="id" value="<?= (int)$it['deleted_id'] ?>"><button type="submit" class="btn btn-outline-success btn-sm">Restore to Source</button></form>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
</body>
</html>
