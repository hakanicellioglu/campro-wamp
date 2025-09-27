-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 27 Eyl 2025, 07:08:47
-- Sunucu sürümü: 9.1.0
-- PHP Sürümü: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `campro`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE IF NOT EXISTS `companies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_turkish_ci NOT NULL,
  `address` text COLLATE utf8mb4_turkish_ci,
  `phone` varchar(30) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `website` varchar(150) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `fax` varchar(30) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `companies`
--

INSERT INTO `companies` (`id`, `name`, `address`, `phone`, `email`, `website`, `fax`) VALUES
(1, 'Yılmaz Alüminyum', 'Mimarsinan Organize Sanayi Bölgesi 9. Cadde No: 17 Melikgazi / Kayseri', '+90 352 320 09 09', 'info@yilmazcephe.com.tr', 'https://yilmazcephe.com.tr/', '+90 352 322 17 46'),
(2, 'Yılmaz Alüminyum', 'Mimarsinan Organize Sanayi Bölgesi 9. Cadde No: 17 Melikgazi / Kayseri', '+90 352 320 09 09', 'info@yilmazcephe.com.tr', 'https://yilmazcephe.com.tr/', '+90 352 322 17 46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `company_descriptions`
--

DROP TABLE IF EXISTS `company_descriptions`;
CREATE TABLE IF NOT EXISTS `company_descriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `position` int NOT NULL,
  `description` text COLLATE utf8mb4_turkish_ci,
  PRIMARY KEY (`id`),
  KEY `fk_company_desc_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `company_ibans`
--

DROP TABLE IF EXISTS `company_ibans`;
CREATE TABLE IF NOT EXISTS `company_ibans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `company_id` int NOT NULL,
  `bank_name` varchar(150) COLLATE utf8mb4_turkish_ci NOT NULL,
  `iban` varchar(34) COLLATE utf8mb4_turkish_ci NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'TRY',
  PRIMARY KEY (`id`),
  KEY `fk_company_ibans_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'view_dashboard', 'Gösterge panelini görüntüleme'),
(2, 'view_reports', 'Raporları görüntüleme'),
(3, 'manage_users', 'Kullanıcı ekleme/düzenleme/silme'),
(4, 'manage_orders', 'Sipariş ekleme/düzenleme/silme'),
(5, 'manage_products', 'Ürün ekleme/düzenleme/silme'),
(6, 'manage_suppliers', 'Tedarikçi ekleme/düzenleme/silme'),
(7, 'manage_prices', 'Fiyat kalemlerini yönetme'),
(8, 'manage_shipments', 'Sevkiyat/araç planlama'),
(9, 'manage_settings', 'Genel ayarları değiştirme');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Sistemde tüm yetkiler'),
(2, 'manager', 'Sipariş/ürün yönetimi ve raporlar'),
(3, 'user', 'Temel görüntüleme ve işlemler');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_perm` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `granted`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(1, 4, 1),
(1, 5, 1),
(1, 6, 1),
(1, 7, 1),
(1, 8, 1),
(1, 9, 1),
(2, 1, 1),
(2, 2, 1),
(2, 4, 1),
(2, 5, 1),
(2, 6, 1),
(2, 7, 1),
(2, 8, 1),
(3, 1, 1),
(3, 2, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `shipments`
--

DROP TABLE IF EXISTS `shipments`;
CREATE TABLE IF NOT EXISTS `shipments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shipment_code` varchar(60) COLLATE utf8mb4_turkish_ci NOT NULL,
  `ship_date` date NOT NULL,
  `origin` varchar(150) COLLATE utf8mb4_turkish_ci NOT NULL,
  `destination` varchar(150) COLLATE utf8mb4_turkish_ci NOT NULL,
  `status` enum('planned','in_transit','delayed','delivered','cancelled') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'planned',
  `cargo_description` text COLLATE utf8mb4_turkish_ci,
  `vehicle_id` int DEFAULT NULL,
  `assigned_driver` varchar(120) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_turkish_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipment_code` (`shipment_code`),
  KEY `fk_shipments_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `surname` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_turkish_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `firstname`, `surname`, `email`, `username`, `password`) VALUES
(1, 'Hakan Berke', 'İÇELLİOĞLU', 'hakanicellioglu@gmail.com', 'berkeicellioglu', '$2y$10$2YpbVAlq.pVRwC3WWUvjrub0bofgR3z5aqvljsImbOP3rfUSv5fcy'),
(2, 'Hakan Berke', 'İÇELLİOĞLU', 'berkeicellioglu@gmail.com', 'admin', '$2y$10$fCAr.58640TXO.UbUinD1OqAzsS0HMjs.r6vZmfSYIdwIRx.cCdcy');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_ur_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(1, 3);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) COLLATE utf8mb4_turkish_ci NOT NULL,
  `type` varchar(60) COLLATE utf8mb4_turkish_ci NOT NULL,
  `brand` varchar(80) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `model` varchar(80) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `production_year` smallint DEFAULT NULL,
  `capacity_weight` decimal(10,2) DEFAULT NULL,
  `capacity_volume` decimal(10,2) DEFAULT NULL,
  `status` enum('active','maintenance','passive','retired') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'active',
  `last_service_at` date DEFAULT NULL,
  `next_service_at` date DEFAULT NULL,
  `inspection_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_turkish_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plate_number` (`plate_number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `vehicles`
--

INSERT INTO `vehicles` (`id`, `plate_number`, `type`, `brand`, `model`, `production_year`, `capacity_weight`, `capacity_volume`, `status`, `last_service_at`, `next_service_at`, `inspection_expiry`, `insurance_expiry`, `notes`, `created_at`, `updated_at`) VALUES
(1, '38ANU835', 'Sedan', 'Fiat', 'Linea', 2014, 2000.00, NULL, 'active', NULL, NULL, NULL, NULL, NULL, '2025-09-27 10:08:10', '2025-09-27 10:08:10');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `vehicle_maintenance`
--

DROP TABLE IF EXISTS `vehicle_maintenance`;
CREATE TABLE IF NOT EXISTS `vehicle_maintenance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `maintenance_date` date NOT NULL,
  `maintenance_type` varchar(120) COLLATE utf8mb4_turkish_ci NOT NULL,
  `description` text COLLATE utf8mb4_turkish_ci,
  `mileage` int DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT NULL,
  `service_center` varchar(150) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'planned',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_vehicle_maintenance_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `vehicle_routes`
--

DROP TABLE IF EXISTS `vehicle_routes`;
CREATE TABLE IF NOT EXISTS `vehicle_routes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int NOT NULL,
  `route_date` date NOT NULL,
  `origin` varchar(120) COLLATE utf8mb4_turkish_ci NOT NULL,
  `destination` varchar(120) COLLATE utf8mb4_turkish_ci NOT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `cargo_summary` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `status` enum('planned','in_transit','completed','cancelled') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'planned',
  `notes` text COLLATE utf8mb4_turkish_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_vehicle_routes_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `company_descriptions`
--
ALTER TABLE `company_descriptions`
  ADD CONSTRAINT `fk_company_desc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `company_ibans`
--
ALTER TABLE `company_ibans`
  ADD CONSTRAINT `fk_company_ibans_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `fk_shipments_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `vehicle_maintenance`
--
ALTER TABLE `vehicle_maintenance`
  ADD CONSTRAINT `fk_vehicle_maintenance_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `vehicle_routes`
--
ALTER TABLE `vehicle_routes`
  ADD CONSTRAINT `fk_vehicle_routes_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
