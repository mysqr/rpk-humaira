<?php
// includes/alerts.php
$flash = get_flash();
if ($flash):
?>
<div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow-sm border-0 d-flex align-items-center gap-2 mb-4 no-print" role="alert">
  <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> fs-5"></i>
  <div><?= $flash['message'] ?></div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
