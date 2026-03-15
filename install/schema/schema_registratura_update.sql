-- Actualizare tabel registratura pentru modulul Registratura extins
-- Rulați fiecare ALTER separat - ignorați erorile pentru coloane existente
-- Alternativ, PHP ensure_registratura_table() din includes/registratura_helper.php face migrarea automat

USE `r74526anrb_internapp_crm`;

-- Adăugare coloane noi (eliminați liniile pentru coloane care există deja)
ALTER TABLE registratura ADD COLUMN nr_intern INT NOT NULL DEFAULT 0 AFTER id;
ALTER TABLE registratura ADD COLUMN nr_document VARCHAR(100) DEFAULT NULL AFTER detalii;
ALTER TABLE registratura ADD COLUMN data_document DATE DEFAULT NULL AFTER nr_document;
ALTER TABLE registratura ADD COLUMN provine_din VARCHAR(500) DEFAULT NULL AFTER data_document;
ALTER TABLE registratura ADD COLUMN continut_document TEXT DEFAULT NULL AFTER provine_din;
ALTER TABLE registratura ADD COLUMN destinatar_document VARCHAR(500) DEFAULT NULL AFTER continut_document;
ALTER TABLE registratura ADD COLUMN task_deschis TINYINT(1) NOT NULL DEFAULT 0 AFTER destinatar_document;
ALTER TABLE registratura ADD COLUMN task_id INT DEFAULT NULL AFTER task_deschis;

CREATE TABLE IF NOT EXISTS setari (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cheie VARCHAR(100) NOT NULL UNIQUE,
  valoare TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO setari (cheie, valoare) VALUES ('registratura_nr_pornire', '1') ON DUPLICATE KEY UPDATE cheie = cheie;
