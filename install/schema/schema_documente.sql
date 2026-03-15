-- Tabele pentru modulul de generare documente
-- Rulați în phpMyAdmin: selectați baza r74526anrb_internapp_crm
USE `r74526anrb_internapp_crm`;

CREATE TABLE IF NOT EXISTS documente_template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume_afisare VARCHAR(255) NOT NULL,
    nume_fisier VARCHAR(255) NOT NULL,
    activ TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activ (activ)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registratura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_inregistrare VARCHAR(50) NOT NULL,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizator VARCHAR(100) NOT NULL,
    tip_act VARCHAR(100) NOT NULL,
    detalii TEXT DEFAULT NULL,
    membru_id INT DEFAULT NULL,
    document_path VARCHAR(500) DEFAULT NULL,
    INDEX idx_data_ora (data_ora),
    INDEX idx_utilizator (utilizator),
    INDEX idx_nr_inregistrare (nr_inregistrare),
    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
