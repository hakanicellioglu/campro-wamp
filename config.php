<?php
/**
 * Nexa Demo Uygulaması - config.php
 *
 * README (Kurulum Özeti):
 * 1. database.sql dosyasını MySQL 8 sunucunuza import edin (mysql -u root -p < database.sql).
 * 2. Aşağıdaki veritabanı bağlantı bilgilerini kendi ortamınıza göre düzenleyin.
 * 3. Demo amaçlı giriş kontrolü uygulanmadı; product.php sayfası doğrudan erişime açıktır.
 *    İleri aşamada kimlik doğrulama ekleneceğinden burada session tabanlı bir kontrol noktası bırakabilirsiniz.
 */

declare(strict_types=1);

// Oturum yönetimi; CSRF token üretimi için gerekli.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bağlantı yapılandırması (ihtiyaca göre güncelleyin).
$host = 'localhost';
$db   = 'nexa';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('DB Connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Veritabanına bağlanırken beklenmeyen bir hata oluştu.';
    exit;
}

// CSRF token üretimi; istemci tarafında meta tag ile paylaşılır.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
