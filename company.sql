-- company.sql
-- Şirket temel bilgileri
CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address TEXT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  website VARCHAR(150) NULL,
  fax VARCHAR(30) NULL
) ENGINE=InnoDB;

-- Şirket açıklamaları
CREATE TABLE company_descriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  position INT NOT NULL,
  description TEXT NULL,
  CONSTRAINT fk_company_desc_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- Şirket IBAN'ları
CREATE TABLE company_ibans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  bank_name VARCHAR(150) NOT NULL,
  iban VARCHAR(34) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'TRY',
  CONSTRAINT fk_company_ibans_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
