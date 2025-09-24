<?php
// partials/flash.php — session flash mesajlarını toast olarak gösterir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flashTypes = [
    'flash_success' => ['class' => 'success', 'icon' => 'check-circle-fill'],
    'flash_error'   => ['class' => 'danger',  'icon' => 'exclamation-triangle-fill'],
    'flash_warning' => ['class' => 'warning', 'icon' => 'exclamation-circle-fill'],
];

$hasFlash = false;
foreach ($flashTypes as $key => $meta) {
    if (!empty($_SESSION[$key])) {
        $hasFlash = true;
        break;
    }
}
?>

<?php if ($hasFlash): ?>
<!-- Bootstrap Icons CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:3000;">
  <?php foreach ($flashTypes as $key => $meta): ?>
    <?php if (!empty($_SESSION[$key])): ?>
      <div class="toast align-items-center text-bg-<?= $meta['class'] ?> border-0 mb-2" role="alert"
           aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
          <div class="toast-body d-flex align-items-center">
            <i class="bi bi-<?= $meta['icon'] ?> me-2"></i>
            <?= htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8') ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
                  data-bs-dismiss="toast" aria-label="Kapat"></button>
        </div>
      </div>
      <?php unset($_SESSION[$key]); ?>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.toast').forEach(el => {
      new bootstrap.Toast(el, { autohide: true, delay: 3000 }).show();
    });
  });
</script>
<?php endif; ?>
