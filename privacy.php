<?php
// privacy.php — Gizlilik Politikası (public)
// Bu sayfa giriş gerektirmez.
declare(strict_types=1);
session_start();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Nexa — Gizlilik Politikası</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php require_once __DIR__ . '/assets/fonts/monoton.php'; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <!-- Inter (metin fontu) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--ink:#0f1419;--muted:#536471;--line:#e6e6e6;--radius:14px;}
    body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);}
    .page{max-width: 960px; margin: 0 auto; padding: 24px;}
    .hero{display:flex; align-items:center; gap:12px; margin: 12px 0 24px;}
    .logo{font-family:"Monoton",sans-serif; letter-spacing:.05em; font-size:1.6rem;}
    h1{font-weight:800; letter-spacing:-.02em; margin:0;}
    .card{border:1px solid var(--line); border-radius: var(--radius);}
    .muted{color:var(--muted);}
    .toc a{color:inherit; text-decoration:none;}
    .toc a:hover{text-decoration:underline;}
    .section + .section{margin-top: 24px;}
    .footer-links a{color:inherit;}
  </style>
</head>
<body>
  <main class="page">
    <header class="hero">
      <div class="logo">NEXA</div>
      <h1>Gizlilik Politikası</h1>
    </header>

    <div class="card p-3 p-md-4 mb-3">
      <p class="muted mb-2">Yürürlük tarihi: <?= htmlspecialchars(date('d.m.Y')) ?></p>
      <p>Bu Gizlilik Politikası, Nexa’nın (“biz”) kişisel verilerinizi nasıl topladığını, kullandığını ve koruduğunu açıklar.</p>

      <div class="toc mb-3">
        <strong>İçindekiler</strong>
        <ol class="mb-0">
          <li><a href="#collect">Topladığımız Veriler</a></li>
          <li><a href="#purpose">Veri Kullanım Amaçları</a></li>
          <li><a href="#cookies">Çerezler ve Benzeri Teknolojiler</a></li>
          <li><a href="#legal">Hukuki Dayanaklar</a></li>
          <li><a href="#share">Veri Paylaşımı</a></li>
          <li><a href="#retention">Saklama Süreleri</a></li>
          <li><a href="#security">Güvenlik</a></li>
          <li><a href="#rights">Haklarınız</a></li>
          <li><a href="#changes">Değişiklikler</a></li>
          <li><a href="#contact">İletişim</a></li>
        </ol>
      </div>

      <div id="collect" class="section">
        <h2 class="h4">1) Topladığımız Veriler</h2>
        <ul>
          <li><strong>Hesap verileri:</strong> Ad, soyad, e-posta, kullanıcı adı, parola (hash’lenmiş).</li>
          <li><strong>İşlem/Sipariş verileri:</strong> Sipariş içerikleri, fiyat kalemleri, tedarikçi bilgileri.</li>
          <li><strong>Kullanım verileri:</strong> Giriş tarih/saatleri, etkileşimler, hata kayıtları.</li>
          <li><strong>Teknik bilgiler:</strong> IP adresi, tarayıcı ve cihaz bilgileri (güvenlik/analiz amaçlı).</li>
        </ul>
      </div>

      <div id="purpose" class="section">
        <h2 class="h4">2) Veri Kullanım Amaçları</h2>
        <ul>
          <li>Hizmetin sunulması ve hesabınızın yönetimi,</li>
          <li>Güvenlik, doğrulama ve dolandırıcılık önleme,</li>
          <li>Performans analizi, hata tespiti ve iyileştirme,</li>
          <li>Yasal yükümlülüklere uyum ve talep/şikâyet yönetimi.</li>
        </ul>
      </div>

      <div id="cookies" class="section">
        <h2 class="h4">3) Çerezler ve Benzeri Teknolojiler</h2>
        <p>Oturum yönetimi, tercihlerinizin hatırlanması ve analitik amaçlarla çerezler kullanılır. Tarayıcınızdan çerez ayarlarını değiştirebilirsiniz; ancak bazı çerezler kapatıldığında hizmetin belirli bölümleri çalışmayabilir.</p>
      </div>

      <div id="legal" class="section">
        <h2 class="h4">4) Hukuki Dayanaklar</h2>
        <p>Verilerinizi meşru menfaatlerimiz, sözleşmenin kurulması/ifası, hukuki yükümlülükler ve gerektiğinde açık rızanıza dayanarak işleriz.</p>
      </div>

      <div id="share" class="section">
        <h2 class="h4">5) Veri Paylaşımı</h2>
        <p>Veriler, yalnızca aşağıdaki durumlarda üçüncü taraflarla paylaşılır:</p>
        <ul>
          <li>Yasal zorunluluklar veya yetkili makam talepleri,</li>
          <li>Hizmet sağlayıcılar (barındırma, bakım, analitik) — veri işleyen sıfatıyla, sözleşmesel korumalar eşliğinde,</li>
          <li>Hak ve güvenliğin korunması (dolandırıcılık/istismar önleme).</li>
        </ul>
      </div>

      <div id="retention" class="section">
        <h2 class="h4">6) Saklama Süreleri</h2>
        <p>Kişisel veriler, amaçla sınırlı ve gerekli süre boyunca saklanır. Yasal saklama yükümlülükleri mevcutsa bu süreler önceliklidir. Süre dolduğunda veriler güvenli şekilde anonimleştirilir veya silinir.</p>
      </div>

      <div id="security" class="section">
        <h2 class="h4">7) Güvenlik</h2>
        <p>Veri güvenliği için uygun teknik ve idari önlemler uyguluyoruz (erişim kontrolü, şifreleme, kayıt izleme). Hiçbir yöntem tamamen güvenli olmamakla birlikte, riskleri azaltmak için sürekli iyileştirme yapmaktayız.</p>
      </div>

      <div id="rights" class="section">
        <h2 class="h4">8) Haklarınız</h2>
        <p>Yürürlükteki mevzuata göre; erişim, düzeltme, silme, işlemeyi kısıtlama, itiraz, veriyi taşınabilirlik ve rıza geri çekme haklarına sahipsiniz. Talepleriniz için iletişim kanallarımızı kullanabilirsiniz.</p>
      </div>

      <div id="changes" class="section">
        <h2 class="h4">9) Değişiklikler</h2>
        <p>Bu politika zaman zaman güncellenebilir. Güncellemeler yayımlandığı anda yürürlüğe girer. Önemli değişikliklerde makul ölçüde bildirim yapmaya çalışırız.</p>
      </div>

      <div id="contact" class="section">
        <h2 class="h4">10) İletişim</h2>
        <p>Sorularınız için: <a href="mailto:privacy@nexa.local">privacy@nexa.local</a></p>
      </div>
    </div>

    <p class="text-muted footer-links">
      Ayrıca bkz: <a href="terms.php">Hizmet Şartları</a>
    </p>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <?php require_once __DIR__ . '/component/footer.php'; ?>
</body>
</html>
