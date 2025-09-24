<style>
    .custom-alert {
        border-radius: 0.75rem;
        font-size: 0.95rem;
        padding: 0.75rem 1rem;
    }

    .custom-alert i {
        font-size: 1.2rem;
    }
</style>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flashMessages = [
    'flash_success' => 'success',
    'flash_error'   => 'danger',
    'flash_warning' => 'warning',
];

foreach ($flashMessages as $sessionKey => $alertType) {
    if (!empty($_SESSION[$sessionKey])) {
        $message = $_SESSION[$sessionKey];
        unset($_SESSION[$sessionKey]);
?>
        <div class="custom-alert alert alert-<?php echo $alertType; ?> alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
            <div class="d-flex align-items-center">
                <!-- İkon -->
                <?php if ($alertType === 'success'): ?>
                    <i class="bi bi-check-circle-fill me-2"></i>
                <?php elseif ($alertType === 'danger'): ?>
                    <i class="bi bi-x-circle-fill me-2"></i>
                <?php elseif ($alertType === 'warning'): ?>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php endif; ?>

                <!-- Mesaj -->
                <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
<?php
    }
}
?>