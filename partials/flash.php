<?php
// partials/flash.php – session flash mesajlarını gelişmiş toast olarak gösterir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$flashTypes = [
    'flash_success' => [
        'class' => 'success', 
        'icon' => 'check-circle-fill',
        'bg_color' => '#28a745',
        'accent_color' => '#1e7e34'
    ],
    'flash_error' => [
        'class' => 'danger', 
        'icon' => 'exclamation-triangle-fill',
        'bg_color' => '#dc3545',
        'accent_color' => '#c82333'
    ],
    'flash_warning' => [
        'class' => 'warning', 
        'icon' => 'exclamation-circle-fill',
        'bg_color' => '#ffc107',
        'accent_color' => '#e0a800'
    ],
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

<style>
.nexa-toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.nexa-toast {
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    margin-bottom: 16px;
    overflow: hidden;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    border: none;
    min-width: 350px;
}

.nexa-toast.show {
    opacity: 1;
    transform: translateX(0);
}

.nexa-toast.hide {
    opacity: 0;
    transform: translateX(100%);
}

.nexa-toast-header {
    background: linear-gradient(135deg, var(--toast-bg-color, #6c757d), var(--toast-accent-color, #5a6268));
    color: white;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: none;
}

.nexa-toast-brand {
    display: flex;
    align-items: center;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.nexa-toast-brand::before {
    content: '';
    width: 8px;
    height: 8px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    margin-right: 8px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.9; }
    50% { opacity: 0.6; }
}

.nexa-toast-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.8);
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.nexa-toast-close:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: scale(1.1);
}

.nexa-toast-body {
    padding: 16px;
    display: flex;
    align-items: flex-start;
    background: white;
    position: relative;
}

.nexa-toast-icon {
    font-size: 20px;
    margin-right: 12px;
    margin-top: 2px;
    color: var(--toast-bg-color, #6c757d);
}

.nexa-toast-message {
    flex: 1;
    color: #333;
    font-size: 14px;
    line-height: 1.5;
    word-wrap: break-word;
}

.nexa-progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--toast-bg-color, #6c757d), var(--toast-accent-color, #5a6268));
    border-radius: 0 0 12px 12px;
    animation: shrink 3000ms linear forwards;
    transform-origin: left;
}

.nexa-toast:hover .nexa-progress-bar {
    animation-play-state: paused;
}

@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}

/* Responsive tasarım */
@media (max-width: 480px) {
    .nexa-toast-container {
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .nexa-toast {
        min-width: auto;
    }
}

/* Tema renkleri */
.nexa-toast.success {
    --toast-bg-color: #28a745;
    --toast-accent-color: #1e7e34;
}

.nexa-toast.danger {
    --toast-bg-color: #dc3545;
    --toast-accent-color: #c82333;
}

.nexa-toast.warning {
    --toast-bg-color: #ffc107;
    --toast-accent-color: #e0a800;
}

.nexa-toast.warning .nexa-toast-icon {
    color: #856404;
}
</style>

<div class="nexa-toast-container">
  <?php foreach ($flashTypes as $key => $meta): ?>
    <?php if (!empty($_SESSION[$key])): ?>
      <div class="nexa-toast <?= $meta['class'] ?>" 
           role="alert" 
           aria-live="assertive" 
           aria-atomic="true"
           data-delay="3000">
        
        <!-- Toast Header -->
        <div class="nexa-toast-header">
          <div class="nexa-toast-brand">
            NEXA
          </div>
          <button type="button" class="nexa-toast-close" aria-label="Kapat">
            <i class="bi bi-x"></i>
          </button>
        </div>
        
        <!-- Toast Body -->
        <div class="nexa-toast-body">
          <i class="bi bi-<?= $meta['icon'] ?> nexa-toast-icon"></i>
          <div class="nexa-toast-message">
            <?= htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="nexa-progress-bar"></div>
      </div>
      <?php unset($_SESSION[$key]); ?>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const toasts = document.querySelectorAll('.nexa-toast');
    
    toasts.forEach((toast, index) => {
        const delay = parseInt(toast.dataset.delay) || 3000;
        const progressBar = toast.querySelector('.nexa-progress-bar');
        const closeBtn = toast.querySelector('.nexa-toast-close');
        
        let timeoutId;
        let startTime;
        let remainingTime = delay;
        let isPaused = false;
        
        // Toast'ı göster
        setTimeout(() => {
            toast.classList.add('show');
        }, index * 150); // Sıralı animasyon
        
        // Progress bar animasyonunu başlat
        const startProgressBar = () => {
            if (progressBar) {
                progressBar.style.animationDuration = remainingTime + 'ms';
                progressBar.style.animationPlayState = 'running';
            }
        };
        
        // Auto hide fonksiyonu
        const startAutoHide = () => {
            if (isPaused) return;
            
            startTime = Date.now();
            startProgressBar();
            
            timeoutId = setTimeout(() => {
                hideToast();
            }, remainingTime);
        };
        
        // Toast'ı gizle
        const hideToast = () => {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 400);
        };
        
        // Hover olayları
        toast.addEventListener('mouseenter', () => {
            isPaused = true;
            clearTimeout(timeoutId);
            
            if (progressBar) {
                const elapsed = Date.now() - startTime;
                remainingTime = Math.max(0, remainingTime - elapsed);
                progressBar.style.animationPlayState = 'paused';
            }
        });
        
        toast.addEventListener('mouseleave', () => {
            isPaused = false;
            if (remainingTime > 0) {
                startAutoHide();
            }
        });
        
        // Close button
        closeBtn.addEventListener('click', () => {
            clearTimeout(timeoutId);
            hideToast();
        });
        
        // İlk başlatma
        startAutoHide();
    });
});
</script>
<?php endif; ?>