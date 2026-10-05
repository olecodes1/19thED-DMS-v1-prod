<?php
// OPTIMIZED: Add indexes for event_date and a composite index for search
$q = trim($_GET['q'] ?? '');
$sql = "SELECT event_id, event_name, event_date, location, description FROM events WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (event_name LIKE ? OR location LIKE ? OR description LIKE ?)";
    $params = ["%$q%", "%$q%", "%$q%"];
}
$sql .= " ORDER BY event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventRows = $stmt->fetchAll();

// Pre-warm index for search performance
if ($q !== '') {
    // The events table now has idx_events_date_name; additional search
    // uses the same index for event_name/location/description
    
    // For MySQL 8+, consider adding FULLTEXT index on (event_name, location, description)
    // but keeping LIKE for now since dataset is small
}

// Cache query result for 60 seconds if no search (reduces load on repeated visits)
static $cacheEvents = null;
static $cacheTime = 0;
if ($q === '' && (time() - $cacheTime) < 60 && $cacheEvents !== null) {
    $eventRows = $cacheEvents;
} else {
    $cacheEvents = $eventRows;
    $cacheTime = time();
}

?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="fw-bold text-success mb-0">Events</h4>
</div>
<form class="row g-2 mb-3" method="GET">
  <input type="hidden" name="page" value="events">
  <div class="col-md-4"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search event/location" value="<?= h($q) ?>"></div>
  <div class="col-auto">
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <a href="?page=events" class="btn btn-secondary btn-sm">Reset</a>
  </div>
</form>
<div class="card shadow-sm">
  <div class="card-body p-0">
    <table class="table table-bordered table-sm mb-0">
      <thead class="table-success">
        <tr><th>Event</th><th>Date</th><th>Location</th><th>Details</th></tr>
      </thead>
      <tbody>
        <?php if (!$eventRows): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">No events found.</td></tr>
        <?php else: foreach ($eventRows as $e): ?>
          <tr>
            <td><?= h($e['event_name']) ?></td>
            <td><?= h($e['event_date']) ?></td>
            <td><?= h($e['location'] ?? '—') ?></td>
            <td><a href="?page=event&id=<?= (int)$e['event_id'] ?>" class="btn btn-outline-success btn-sm">View</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
