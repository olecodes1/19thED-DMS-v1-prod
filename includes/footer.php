<?php
require_once __DIR__ . '/url_helper.php';
require_once __DIR__ . '/auth.php';
?>
<?php if (must_change_password()): ?>
  <div class="alert alert-warning text-center mb-0" style="border-radius:0;">
    <i class="fas fa-key me-2"></i>You are using a seeded default password.
    <a href="<?= base_url('forms/change_password.php?required=1') ?>" class="fw-bold">Change your password now</a>.
  </div>
<?php endif; ?>
<footer class="text-center text-muted py-4 mt-5 border-top small">

    <span class="badge bg-success">19th Episcopal District YPD Admin Dashboard</span>
    <hr>
    <span class="ms-2"><?= date('Y-m-d H:i:s') ?></span>

    <div class="mb-1">&copy; <?= date('Y') ?> Olebogeng Itumeleng Leketi </div>
    <div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<script src="<?= base_url('assets/js/scripts.js') ?>?v=<?= (int)@filemtime(__DIR__ . '/../assets/js/scripts.js') ?>" defer></script>
<?php $footerConfig = require __DIR__ . '/../config.php'; ?>
<?php if (!empty($footerConfig['app']['debug']) && function_exists('app_query_profile')): $profile = app_query_profile(); ?>
<details class="container-fluid small text-muted mt-3 no-print">
  <summary>Debug: <?= (int)$profile['count'] ?> database queries</summary>
  <pre class="mb-0" style="max-height:12rem;overflow:auto;"><?= htmlspecialchars(implode("\n", $profile['queries'])) ?></pre>
</details>
<?php endif; ?>
