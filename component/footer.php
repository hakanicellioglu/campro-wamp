 
<?php
// component/footer.php — Nexa genel footer
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
  footer {
    background: #fff;
    border-top: 1px solid #e5e7eb;
    padding: 1rem 0;
    font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    font-size: .9rem;
    color: #6b7280;
  }
  footer .footer-logo {
    font-family: "Monoton", cursive;
    font-size: 1.2rem;
    letter-spacing: 0.1em;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-right: 1rem;
    font-weight: 600;
  }
  footer a {
    color: #6b7280;
    text-decoration: none;
    transition: color .2s ease;
  }
  footer a:hover {
    color: #374151;
    text-decoration: underline;
  }
</style>

<footer class="mt-auto">
  <div class="container-fluid d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-3">
    <div class="d-flex align-items-center">
      <span class="footer-logo">NEXA</span>
      <span>&copy; <?= date('Y') ?> Nexa. Tüm hakları saklıdır.</span>
    </div>
    <ul class="list-inline mb-0">
      <li class="list-inline-item"><a href="/terms.php">Hizmet Şartları</a></li>
      <li class="list-inline-item"><a href="/privacy.php">Gizlilik Politikası</a></li>
      <li class="list-inline-item"><a href="/contact.php">İletişim</a></li>
    </ul>
  </div>
</footer>
