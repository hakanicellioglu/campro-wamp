-- Nexa (CamPro) - MySQL 8 Şema ve Örnek Veri
-- Bu dosyayı MySQL 8+ sürümüne import ederek tablo ve başlangıç verilerini oluşturun.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

DROP VIEW IF EXISTS v_variant_recipe;
DROP TABLE IF EXISTS variant_layers;
DROP TABLE IF EXISTS product_variants;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS materials;
DROP TABLE IF EXISTS layer_types;
DROP TABLE IF EXISTS system_types;
DROP TABLE IF EXISTS glass_categories;

CREATE TABLE glass_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE system_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE layer_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code ENUM('glass','air_gap','film') NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO layer_types (id, code, label) VALUES
    (1, 'glass', 'Cam'),
    (2, 'air_gap', 'Hava Boşluğu'),
    (3, 'film', 'Film/Bonding');

CREATE TABLE materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_code VARCHAR(40) NOT NULL,
    name VARCHAR(180) NOT NULL,
    base_kind ENUM('glass','film') NOT NULL,
    thickness_mm DECIMAL(6,2) NOT NULL,
    color VARCHAR(80) DEFAULT NULL,
    surface_finish VARCHAR(120) DEFAULT NULL,
    is_tempered TINYINT(1) NOT NULL DEFAULT 0,
    is_laminated TINYINT(1) NOT NULL DEFAULT 0,
    edge_finish VARCHAR(120) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_material UNIQUE (material_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    system_type_id INT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES glass_categories(id),
    CONSTRAINT fk_products_system FOREIGN KEY (system_type_id) REFERENCES system_types(id),
    CONSTRAINT uq_products_name UNIQUE (category_id, system_type_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(60) NOT NULL,
    variant_name VARCHAR(160) NOT NULL,
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT uq_variants_sku UNIQUE (sku),
    CONSTRAINT uq_variants_name UNIQUE (product_id, variant_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE TABLE variant_layers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variant_id INT UNSIGNED NOT NULL,
    sequence_no INT NOT NULL,
    layer_type_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED DEFAULT NULL,
    air_gap_mm DECIMAL(6,2) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_layers_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    CONSTRAINT fk_layers_type FOREIGN KEY (layer_type_id) REFERENCES layer_types(id),
    CONSTRAINT fk_layers_material FOREIGN KEY (material_id) REFERENCES materials(id),
    CONSTRAINT uq_layer_sequence UNIQUE (variant_id, sequence_no),
    CONSTRAINT chk_layer_gap CHECK (
        (layer_type_id = 2 AND air_gap_mm IS NOT NULL AND material_id IS NULL)
        OR
        (layer_type_id <> 2 AND air_gap_mm IS NULL AND material_id IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

CREATE INDEX idx_layers_variant ON variant_layers (variant_id);
CREATE INDEX idx_layers_layer_type ON variant_layers (layer_type_id);

CREATE OR REPLACE VIEW v_variant_recipe AS
SELECT
    pv.id AS variant_id,
    pv.variant_name,
    GROUP_CONCAT(
        CASE
            WHEN lt.code = 'air_gap' THEN CONCAT(TRIM(TRAILING '.00' FROM CAST(vl.air_gap_mm AS CHAR)), ' mm Hava Boşluğu')
            ELSE CONCAT(
                TRIM(TRAILING '.00' FROM CAST(m.thickness_mm AS CHAR)), ' mm ',
                m.name,
                CASE WHEN m.is_tempered = 1 THEN ' Temperli' ELSE '' END,
                CASE WHEN m.edge_finish IS NOT NULL AND m.edge_finish <> '' THEN CONCAT(' ', m.edge_finish) ELSE '' END
            )
        END
        ORDER BY vl.sequence_no SEPARATOR ' / '
    ) AS recipe
FROM product_variants pv
JOIN variant_layers vl ON vl.variant_id = pv.id
JOIN layer_types lt ON lt.id = vl.layer_type_id
LEFT JOIN materials m ON m.id = vl.material_id
GROUP BY pv.id, pv.variant_name;

-- Örnek sözlük verileri
INSERT INTO glass_categories (name) VALUES
    ('Isıcam Standart'),
    ('Isıcam Konfor');

INSERT INTO system_types (name) VALUES
    ('PVC'),
    ('Alüminyum');

-- Örnek material kayıtları (şeffaf, füme, satina, temperli, rodajlı kombinasyonlar)
INSERT INTO materials (material_code, name, base_kind, thickness_mm, color, surface_finish, is_tempered, is_laminated, edge_finish, notes) VALUES
    ('GLS-CLR-4', 'Şeffaf Cam', 'glass', 4.00, 'Şeffaf', NULL, 0, 0, 'Düz', 'Standart 4 mm düz cam'),
    ('GLS-CLR-4T', 'Şeffaf Cam Temperli', 'glass', 4.00, 'Şeffaf', NULL, 1, 0, 'Rodajlı', 'Temperli rodajlı cam'),
    ('GLS-FUM-4', 'Füme Cam', 'glass', 4.00, 'Füme', NULL, 0, 0, 'Rodajsız', NULL),
    ('GLS-SAT-4', 'Satina Cam', 'glass', 4.00, 'Opak', 'Satina', 0, 0, 'Düz', NULL),
    ('GLS-CLR-6', 'Şeffaf Cam', 'glass', 6.00, 'Şeffaf', NULL, 0, 0, 'Düz', '6 mm kalınlık'),
    ('GLS-CLR-6T', 'Şeffaf Cam Temperli', 'glass', 6.00, 'Şeffaf', NULL, 1, 0, 'Rodajlı', 'Temperli rodajlı cam'),
    ('FIL-LOWE-1', 'Low-E Kaplama', 'film', 0.76, 'Şeffaf', 'Kaplama', 0, 0, NULL, 'Enerji verimli kaplama'),
    ('FIL-SAF-1', 'Güvenlik Filmi', 'film', 1.14, 'Şeffaf', 'Lamine', 0, 1, NULL, 'Lamine güvenlik filmi');

-- Örnek ürün ve varyant
INSERT INTO products (category_id, system_type_id, name, description)
VALUES (1, 1, 'Isıcam 4-12-4', 'Demo ürün tanımı');

INSERT INTO product_variants (product_id, sku, variant_name, base_price)
VALUES (1, 'ICAM-4-12-4T', '4-12-4 Temperli Rodaj', 0.00);

INSERT INTO variant_layers (variant_id, sequence_no, layer_type_id, material_id, air_gap_mm)
VALUES
    (1, 1, 1, 2, NULL),
    (1, 2, 2, NULL, 12.00),
    (1, 3, 1, 1, NULL);
