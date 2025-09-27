<?php
require_once __DIR__ . '/../component/header.php';
?>
<div class="row g-4">
    <div class="col-xl-3">
        <?php include __DIR__ . '/../component/sidebar.php'; ?>
    </div>
    <div class="col-xl-9">
        <header class="mb-4">
            <h1 class="h3 fw-bold">Ürün &amp; Varyant Yönetimi</h1>
            <p class="text-muted mb-0">Ürün bilgilerini sol panelden kaydettikten sonra sağ panelden varyant oluşturabilir, aşağıdaki listeden reçeteleri görüntüleyebilirsiniz.</p>
        </header>
        <div id="client-alerts"></div>
        <?php include __DIR__ . '/../partials/flash.php'; ?>
        <div class="row g-4">
            <div class="col-lg-5">
                <section class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="card-title h5">Ürün Bilgileri</h2>
                        <form id="product-form" class="mt-3" autocomplete="off">
                            <input type="hidden" name="product_id" id="product-id" value="">
                            <div class="mb-3">
                                <label for="category-id" class="form-label">Kategori</label>
                                <select id="category-id" name="category_id" class="form-select" required></select>
                            </div>
                            <div class="mb-3">
                                <label for="system-type-id" class="form-label">Sistem Tipi</label>
                                <select id="system-type-id" name="system_type_id" class="form-select" required></select>
                            </div>
                            <div class="mb-3">
                                <label for="product-name" class="form-label">Ürün Adı</label>
                                <input type="text" id="product-name" name="name" class="form-control" maxlength="160" required>
                            </div>
                            <div class="mb-3">
                                <label for="product-description" class="form-label">Açıklama</label>
                                <textarea id="product-description" name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">Ürünü Kaydet</button>
                                <button type="button" id="reset-product" class="btn btn-outline-secondary">Yeni Ürün Kartı</button>
                            </div>
                        </form>
                        <p class="mt-3 text-muted small">Not: Gelecekte fiyat tarihçesi için varyant tablosuna ilişkili yeni bir tablo eklenebilir.</p>
                    </div>
                </section>
            </div>
            <div class="col-lg-7">
                <section class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="card-title h5">Varyant Oluştur</h2>
                        <form id="variant-form" class="mt-3" autocomplete="off">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="variant-sku" class="form-label">SKU</label>
                                    <input type="text" id="variant-sku" name="sku" class="form-control" maxlength="60" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="variant-name" class="form-label">Varyant Adı</label>
                                    <input type="text" id="variant-name" name="variant_name" class="form-control" maxlength="160" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="variant-price" class="form-label">Baz Fiyat</label>
                                    <input type="number" id="variant-price" name="base_price" class="form-control" step="0.01" min="0" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="variant-currency" class="form-label">Para Birimi</label>
                                    <input type="text" id="variant-currency" name="currency" class="form-control" maxlength="3" value="TRY">
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-template="4-12-4">4-12-4 Şablonu</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-template="6-12-6">6-12-6 Şablonu</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="add-layer">Katman Ekle</button>
                            </div>
                            <div class="table-responsive mb-3">
                                <table class="table align-middle" id="layer-table">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">Sıra</th>
                                            <th>Katman Tipi</th>
                                            <th>Malzeme / Hava</th>
                                            <th>Açıklama</th>
                                            <th class="text-end">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Varyantı Kaydet</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
        <section class="card shadow-sm mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h2 class="card-title h5 mb-0">Varyant Listesi</h2>
                    <div class="input-group w-auto">
                        <span class="input-group-text">Ara</span>
                        <input type="search" id="variant-search" class="form-control" placeholder="Reçete veya SKU ara">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped" id="variant-list">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Varyant Adı</th>
                                <th>Reçete</th>
                                <th>Fiyat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="placeholder-row">
                                <td colspan="4" class="text-center text-muted">Önce bir ürün seçin veya oluşturun.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
<?php
require_once __DIR__ . '/../component/footer.php';
?>
