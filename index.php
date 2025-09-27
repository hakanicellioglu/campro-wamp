<?php
require_once __DIR__ . '/component/header.php';
?>
<div class="row">
    <div class="col-lg-3">
        <?php include __DIR__ . '/component/sidebar.php'; ?>
    </div>
    <div class="col-lg-9">
        <section class="mb-4">
            <h1 class="h3 fw-bold">Nexa Ürün Platformu</h1>
            <p class="text-muted">CamPro altyapısı üzerine inşa edilen Nexa, cam ürünleri ve varyantlarını modern bir arayüz ile yönetmenizi sağlar. Aşağıdaki bağlantı üzerinden ürün kartlarını oluşturup katman bazlı reçeteleri tanımlayabilirsiniz.</p>
            <a href="/public/product.php" class="btn btn-primary">Ürün &amp; Varyant Yönetimi</a>
        </section>
        <section class="bg-light border rounded p-4">
            <h2 class="h5">Hızlı Başlangıç</h2>
            <ul>
                <li>database.sql dosyasını veritabanınıza aktarın.</li>
                <li>config.php içerisindeki veritabanı bilgilerini güncelleyin.</li>
                <li>product.php üzerinden yeni ürün oluşturup varyant katmanlarını düzenleyin.</li>
            </ul>
        </section>
    </div>
</div>
<?php
require_once __DIR__ . '/component/footer.php';
?>
