<?php
$eventId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT event_id, event_name, event_date, location, description, attendance_count, booklet_path FROM events WHERE event_id = ? LIMIT 1");
$stmt->execute([$eventId]);
$event = $stmt->fetch();
?>
<?php if (!$event): ?>
  <div class="alert alert-warning">Event not found.</div>
<?php else: ?>
  <h4 class="fw-bold text-success mb-3"><?= h($event['event_name']) ?></h4>
  <div class="card shadow-sm">
    <div class="card-body">
      <div class="mb-2"><strong>Date:</strong> <?= h($event['event_date']) ?></div>
      <div class="mb-2"><strong>Location:</strong> <?= h($event['location'] ?? '—') ?></div>
      <div class="mb-2"><strong>Attendance:</strong> <?= (int)($event['attendance_count'] ?? 0) ?></div>
      <div><strong>Description:</strong><br><?= nl2br(h($event['description'] ?? '')) ?></div>
      <?php if (!empty($event['booklet_path'])): ?>
        <div class="mt-3">
          <a href="../event_booklet_download.php?id=<?= (int)$event['event_id'] ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-pdf me-1"></i>View Event Booklet (PDF)
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<a href="?page=events" class="btn btn-secondary btn-sm mt-3">Back to Events</a>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
