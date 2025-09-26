<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../config.php';

$csrfToken = $_SESSION['csrf_token'];

$company = null;
$companyDescriptions = [];
$companyIbans = [];

try {
    $companyStmt = $pdo->query('SELECT id, name, address, phone, email, website, fax FROM companies ORDER BY id ASC LIMIT 1');
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    error_log('Company page fetch failed: ' . $e->getMessage());
    $company = null;
}

$companyId = $company['id'] ?? null;

if ($companyId !== null) {
    try {
        $descStmt = $pdo->prepare('SELECT position, description FROM company_descriptions WHERE company_id = :id ORDER BY position ASC');
        $descStmt->execute([':id' => $companyId]);
        $companyDescriptions = $descStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Company descriptions fetch failed: ' . $e->getMessage());
        $companyDescriptions = [];
    }

    try {
        $ibanStmt = $pdo->prepare('SELECT bank_name, iban, currency FROM company_ibans WHERE company_id = :id ORDER BY id ASC');
        $ibanStmt->execute([':id' => $companyId]);
        $companyIbans = $ibanStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('Company ibans fetch failed: ' . $e->getMessage());
        $companyIbans = [];
    }
}

$hasCompany = $company !== null;

$displayName = 'şirket';
if ($hasCompany) {
    $trimmed = trim((string) ($company['name'] ?? ''));
    if ($trimmed !== '') {
        $displayName = $trimmed;
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Şirket — Nexa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
        }

        main.main-with-sidebar {
            min-height: 100vh;
        }

        .card + .card {
            margin-top: 1.5rem;
        }

        textarea.form-control {
            min-height: 120px;
        }

        .company-meta dt {
            font-weight: 600;
            color: #1f2937;
        }

        .company-meta dd {
            margin-bottom: .75rem;
            color: #4b5563;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../partials/flash.php'; ?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../component/sidebar.php'; ?>
    <main class="main-with-sidebar flex-grow-1 p-4">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10 col-xxl-8">
                    <header class="mb-4">
                        <h1 class="h3 mb-1">Şirket Yönetimi</h1>
                        <p class="text-muted mb-0">Aktif şirket kaydınızı yönetin, güncelleyin ve güvenli şekilde silin.</p>
                    </header>

                    <section class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0">
                            <h2 class="h5 mb-0">Genel Bilgiler</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($hasCompany): ?>
                                <dl class="company-meta">
                                    <dt>Şirket Adı</dt>
                                    <dd><?= e($displayName); ?></dd>
                                    <dt>Adres</dt>
                                    <dd><?= $company['address'] !== null && $company['address'] !== '' ? nl2br(e($company['address'])) : '<span class="text-muted">Belirtilmemiş</span>'; ?></dd>
                                    <dt>Telefon</dt>
                                    <dd><?= $company['phone'] !== null && $company['phone'] !== '' ? e($company['phone']) : '<span class="text-muted">Belirtilmemiş</span>'; ?></dd>
                                    <dt>E-posta</dt>
                                    <dd><?= $company['email'] !== null && $company['email'] !== '' ? e($company['email']) : '<span class="text-muted">Belirtilmemiş</span>'; ?></dd>
                                    <dt>Web Sitesi</dt>
                                    <dd>
                                        <?php if ($company['website'] !== null && $company['website'] !== ''): ?>
                                            <a href="<?= e($company['website']); ?>" class="link-primary" target="_blank" rel="noopener">Siteyi Aç</a>
                                        <?php else: ?>
                                            <span class="text-muted">Belirtilmemiş</span>
                                        <?php endif; ?>
                                    </dd>
                                    <dt>Fax</dt>
                                    <dd><?= $company['fax'] !== null && $company['fax'] !== '' ? e($company['fax']) : '<span class="text-muted">Belirtilmemiş</span>'; ?></dd>
                                </dl>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0" role="alert">
                                    Henüz tanımlı bir şirket kaydınız yok. Aşağıdaki formu kullanarak hızlıca oluşturabilirsiniz.
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom-0 d-flex align-items-center justify-content-between">
                            <h2 class="h5 mb-0">Şirket Kaydı <?= $hasCompany ? 'Düzenle' : 'Ekle'; ?></h2>
                            <span class="badge bg-secondary">Güvenli Form</span>
                        </div>
                        <div class="card-body">
                            <form action="api/company/<?= $hasCompany ? 'edit' : 'add'; ?>.php" method="post" class="row g-3" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                <?php if ($hasCompany): ?>
                                    <input type="hidden" name="id" value="<?= e((string) $companyId); ?>">
                                <?php endif; ?>
                                <div class="col-12">
                                    <label for="company-name" class="form-label">Şirket Adı <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company-name" name="name" maxlength="150" required value="<?= $hasCompany ? e((string) $company['name']) : ''; ?>" autocomplete="organization">
                                </div>
                                <div class="col-12">
                                    <label for="company-address" class="form-label">Adres</label>
                                    <textarea class="form-control" id="company-address" name="address" maxlength="5000" placeholder="Adres bilgisi"><?= $hasCompany ? e((string) ($company['address'] ?? '')) : ''; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="company-phone" class="form-label">Telefon</label>
                                    <input type="text" class="form-control" id="company-phone" name="phone" maxlength="30" value="<?= $hasCompany ? e((string) ($company['phone'] ?? '')) : ''; ?>" autocomplete="tel">
                                </div>
                                <div class="col-md-6">
                                    <label for="company-fax" class="form-label">Fax</label>
                                    <input type="text" class="form-control" id="company-fax" name="fax" maxlength="30" value="<?= $hasCompany ? e((string) ($company['fax'] ?? '')) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="company-email" class="form-label">E-posta</label>
                                    <input type="email" class="form-control" id="company-email" name="email" maxlength="120" value="<?= $hasCompany ? e((string) ($company['email'] ?? '')) : ''; ?>" autocomplete="email">
                                </div>
                                <div class="col-md-6">
                                    <label for="company-website" class="form-label">Web Sitesi</label>
                                    <input type="url" class="form-control" id="company-website" name="website" maxlength="150" placeholder="https://" value="<?= $hasCompany ? e((string) ($company['website'] ?? '')) : ''; ?>" autocomplete="url">
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <button type="reset" class="btn btn-outline-secondary">Temizle</button>
                                    <button type="submit" class="btn btn-primary">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <?php if ($hasCompany): ?>
                        <section class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom-0 d-flex align-items-center justify-content-between">
                                <h2 class="h5 mb-0">İçerik Açıklamaları</h2>
                                <span class="badge bg-light text-dark"><?= count($companyDescriptions); ?> kayıt</span>
                            </div>
                            <div class="card-body">
                                <?php if ($companyDescriptions === []): ?>
                                    <p class="text-muted mb-0">Henüz açıklama bulunmuyor.</p>
                                <?php else: ?>
                                    <ol class="mb-0 ps-3">
                                        <?php foreach ($companyDescriptions as $description): ?>
                                            <li class="mb-2">
                                                <strong>Pozisyon <?= e((string) $description['position']); ?>:</strong>
                                                <span><?= nl2br(e((string) ($description['description'] ?? ''))); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="card shadow-sm border-0">
                            <div class="card-header bg-white border-bottom-0 d-flex align-items-center justify-content-between">
                                <h2 class="h5 mb-0">IBAN Bilgileri</h2>
                                <span class="badge bg-light text-dark"><?= count($companyIbans); ?> kayıt</span>
                            </div>
                            <div class="card-body">
                                <?php if ($companyIbans === []): ?>
                                    <p class="text-muted mb-0">IBAN kaydı bulunamadı.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Banka</th>
                                                    <th scope="col">IBAN</th>
                                                    <th scope="col">Para Birimi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($companyIbans as $iban): ?>
                                                    <tr>
                                                        <td><?= e((string) ($iban['bank_name'] ?? '')); ?></td>
                                                        <td><code><?= e((string) ($iban['iban'] ?? '')); ?></code></td>
                                                        <td><?= e((string) ($iban['currency'] ?? '')); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="card border-0 shadow-sm bg-danger-subtle">
                            <div class="card-header bg-danger text-white">
                                <h2 class="h5 mb-0">Tehlikeli İşlem</h2>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">Şirket kaydını silmek tüm ilişkili açıklamaları ve IBAN bilgilerini geri döndürülemez şekilde kaldıracaktır.</p>
                                <form action="api/company/delete.php" method="post" onsubmit="return confirm('Bu şirket kaydını silmek istediğinize emin misiniz?');" class="d-flex flex-column flex-sm-row gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $companyId); ?>">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-shield-lock me-1" aria-hidden="true"></i>
                                        Şirket Kaydını Sil
                                    </button>
                                </form>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
