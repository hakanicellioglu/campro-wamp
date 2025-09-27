-- vehicle.sql
-- Araç yönetimi için temel tablo yapıları

CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plate_number VARCHAR(20) NOT NULL UNIQUE,
  type VARCHAR(60) NOT NULL,
  brand VARCHAR(80) NULL,
  model VARCHAR(80) NULL,
  production_year SMALLINT NULL,
  capacity_weight DECIMAL(10,2) NULL,
  capacity_volume DECIMAL(10,2) NULL,
  status ENUM('active', 'maintenance', 'passive', 'retired') NOT NULL DEFAULT 'active',
  last_service_at DATE NULL,
  next_service_at DATE NULL,
  inspection_expiry DATE NULL,
  insurance_expiry DATE NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE vehicle_routes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  route_date DATE NOT NULL,
  origin VARCHAR(120) NOT NULL,
  destination VARCHAR(120) NOT NULL,
  departure_time TIME NULL,
  arrival_time TIME NULL,
  cargo_summary VARCHAR(255) NULL,
  status ENUM('planned', 'in_transit', 'completed', 'cancelled') NOT NULL DEFAULT 'planned',
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicle_routes_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vehicle_maintenance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_id INT NOT NULL,
  maintenance_date DATE NOT NULL,
  maintenance_type VARCHAR(120) NOT NULL,
  description TEXT NULL,
  mileage INT NULL,
  cost DECIMAL(12,2) NULL,
  service_center VARCHAR(150) NULL,
  next_due_date DATE NULL,
  status ENUM('planned', 'in_progress', 'completed') NOT NULL DEFAULT 'planned',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicle_maintenance_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shipments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shipment_code VARCHAR(60) NOT NULL UNIQUE,
  ship_date DATE NOT NULL,
  origin VARCHAR(150) NOT NULL,
  destination VARCHAR(150) NOT NULL,
  status ENUM('planned', 'in_transit', 'delayed', 'delivered', 'cancelled') NOT NULL DEFAULT 'planned',
  cargo_description TEXT NULL,
  vehicle_id INT NULL,
  assigned_driver VARCHAR(120) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipments_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Örnek veri ekleri
INSERT INTO vehicles
  (plate_number, type, brand, model, production_year, capacity_weight, capacity_volume, status, last_service_at, next_service_at, inspection_expiry, insurance_expiry, notes)
VALUES
  ('38 ABC 123', 'Kamyon', 'Mercedes-Benz', 'Actros 1845', 2022, 24000, 56.5, 'active', '2025-01-15', '2025-07-15', '2025-06-30', '2025-12-31', 'Kayseri hattı ana sevkiyat aracı'),
  ('34 NXA 845', 'Kamyonet', 'Ford', 'Transit', 2021, 3500, 15.2, 'maintenance', '2024-12-20', '2025-03-01', '2025-02-28', '2025-05-15', 'İstanbul içi dağıtım');

INSERT INTO vehicle_routes
  (vehicle_id, route_date, origin, destination, departure_time, arrival_time, cargo_summary, status, notes)
VALUES
  (1, CURDATE(), 'Kayseri Depo', 'Ankara Şantiye', '07:30:00', '11:45:00', 'Isıcam paketleri', 'in_transit', 'Sabah yüklemesi tamamlandı'),
  (1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Ankara Şantiye', 'Kayseri Depo', '17:00:00', NULL, 'Boş dönüş', 'planned', 'Akşam dönüş planı'),
  (2, CURDATE(), 'İstanbul Depo', 'Bursa Proje', '09:00:00', '12:15:00', 'Alüminyum profil', 'completed', 'Teslimat başarıyla tamamlandı');

INSERT INTO vehicle_maintenance
  (vehicle_id, maintenance_date, maintenance_type, description, mileage, cost, service_center, next_due_date, status)
VALUES
  (1, '2025-01-15', 'Periyodik Bakım', 'Yağ ve filtre değişimi yapıldı.', 58000, 12500.00, 'Yetkili Servis Kayseri', '2025-07-15', 'completed'),
  (2, '2025-02-25', 'Fren Kontrolü', 'Arka balatalar değiştirilecek.', 41000, NULL, 'Ford Servis Maslak', '2025-03-01', 'planned');

INSERT INTO shipments
  (shipment_code, ship_date, origin, destination, status, cargo_description, vehicle_id, assigned_driver, notes)
VALUES
  ('SHP-20250301-001', '2025-03-01', 'Kayseri Depo', 'Ankara Şantiye', 'in_transit', 'Isıcam paketleri sevkiyatı', 1, 'Mehmet Yılmaz', 'Sabah teslimatı için yola çıktı'),
  ('SHP-20250302-002', '2025-03-02', 'İstanbul Depo', 'Bursa Proje', 'planned', 'Alüminyum profil', 2, 'Ayşe Demir', 'Yükleme saat 08:30 planlandı'),
  ('SHP-20250301-003', '2025-03-01', 'İzmir Depo', 'Antalya Şube', 'delayed', 'PVC doğrama malzemesi', NULL, NULL, 'Araç arızası nedeniyle alternatif plan bekleniyor');
