<?php
// config.php
declare(strict_types=1);

$host = 'localhost';   // veya 'localhost'
$db   = 'campro';
$user = 'root';        // WAMP varsayılan kullanıcı
$pass = '';            // WAMP varsayılan şifre genelde boştur
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hata yakalama
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Varsayılan fetch
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Kullanıcıya güvenli mesaj göster
    echo "Veritabanına bağlanırken hata oluştu.";
    // Hata detayını loglamak için
    error_log("DB Connection failed: " . $e->getMessage());
    exit;
}
