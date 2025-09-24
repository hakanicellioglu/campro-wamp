<?php
// terms.php — Hizmet Şartları (public)
// Bu sayfa giriş gerektirmez.
declare(strict_types=1);
session_start();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Nexa — Hizmet Şartları</title>
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
      <h1>Hizmet Şartları</h1>
    </header>

    <div class="card p-3 p-md-4 mb-3">
      <p class="muted mb-2">Yürürlük tarihi: <?= htmlspecialchars(date('d.m.Y')) ?></p>
      <p>Nexa’yı kullanarak bu Hizmet Şartları’nı (“Şartlar”) kabul etmiş olursunuz. Lütfen dikkatle okuyunuz.</p>

      <div class="toc mb-3">
        <strong>İçindekiler</strong>
        <ol class="mb-0">
          <li><a href="#accounts">Hesap Oluşturma ve Güvenlik</a></li>
          <li><a href="#acceptable">Kabul Edilebilir Kullanım</a></li>
          <li><a href="#orders">Siparişler ve İşlemler</a></li>
          <li><a href="#pricing">Fiyatlar ve Değişiklikler</a></li>
          <li><a href="#privacy">Gizlilik ve Çerezler</a></li>
          <li><a href="#liability">Sorumluluk Sınırı</a></li>
          <li><a href="#termination">Fesih</a></li>
          <li><a href="#changes">Şartlarda Değişiklik</a></li>
          <li><a href="#contact">İletişim</a></li>
        </ol>
      </div>

      <div id="accounts" class="section">
        <h2 class="h4">1) Hesap Oluşturma ve Güvenlik</h2>
        <p>Hesap oluştururken doğru, güncel ve eksiksiz bilgi vermekle yükümlüsünüz. Hesabınızın ve parolanızın güvenliğinden siz sorumlusunuz. Yetkisiz kullanım şüphesi halinde derhal bizi bilgilendiriniz.</p>
      </div>

      <div id="acceptable" class="section">
        <h2 class="h4">2) Kabul Edilebilir Kullanım</h2>
        <p>Platformu yürürlükteki yasaları ihlal edecek şekilde kullanamazsınız. Sistem güvenliğini aşmaya çalışma, zararlı yazılım yayma, başkalarının haklarını ihlal etme gibi davranışlar yasaktır.</p>
      </div>

      <div id="orders" class="section">
        <h2 class="h4">3) Siparişler ve İşlemler</h2>
        <p>Sipariş oluşturma, güncelleme ve iptal akışları kayıt altına alınır. Tedarikçi veya üçüncü taraf süreçlerinden kaynaklı gecikmeler Nexa’nın sorumluluğunda değildir; ancak sorunların çözümü için makul destek sağlanır.</p>
      </div>

      <div id="pricing" class="section">
        <h2 class="h4">4) Fiyatlar ve Değişiklikler</h2>
        <p>Fiyat kalemleri tedarikçi ve piyasa koşullarına bağlı olarak değişebilir. Platform üzerinde görünen fiyatlar güncelleme anına özeldir ve ön bildirim olmaksızın değiştirilebilir.</p>
      </div>

      <div id="privacy" class="section">
        <h2 class="h4">5) Gizlilik ve Çerezler</h2>
        <p>Kişisel verilerinizi nasıl işlediğimiz için <a href="privacy.php">Gizlilik Politikası</a>’nı inceleyin. Çerez kullanımıyla ilgili detaylar da aynı sayfada açıklanır.</p>
      </div>

      <div id="liability" class="section">
        <h2 class="h4">6) Sorumluluk Sınırı</h2>
        <p>Nexa, dolaylı, tesadüfi, özel veya netice kabilinden zararlar dahil olmak üzere, platformun kullanımından kaynaklanan zararlardan sorumlu tutulamaz. Zorunlu hukuk hükümleri saklıdır.</p>
      </div>

      <div id="termination" class="section">
        <h2 class="h4">7) Fesih</h2>
        <p>Şartların ihlali durumunda, hesabınıza erişimi askıya alma veya sonlandırma hakkımız saklıdır. Siz de dilediğiniz zaman hesabınızı kapatabilirsiniz.</p>
      </div>

      <div id="changes" class="section">
        <h2 class="h4">8) Şartlarda Değişiklik</h2>
        <p>Bu Şartlar zaman zaman güncellenebilir. Güncellemeler yayımlandığı anda yürürlüğe girer. Önemli değişikliklerde makul ölçüde bildirim yapmaya çalışırız.</p>
      </div>

      <div id="contact" class="section">
        <h2 class="h4">9) İletişim</h2>
        <p>Sorularınız için lütfen bizimle iletişime geçin: <a href="mailto:info@nexa.local">info@nexa.local</a></p>
      </div>
    </div>

    <p class="text-muted footer-links">
      Ayrıca bkz: <a href="privacy.php">Gizlilik Politikası</a>
    </p>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
