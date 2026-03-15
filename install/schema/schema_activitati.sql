-- Tabel activități pentru calendar
-- Rulați în phpMyAdmin: selectați baza r74526anrb_internapp_crm, apoi rulați acest script
USE `r74526anrb_internapp_crm`;

CREATE TABLE IF NOT EXISTS activitati (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_ora DATETIME NOT NULL,
    nume VARCHAR(255) NOT NULL,
    locatie VARCHAR(255) DEFAULT NULL,
    responsabili TEXT DEFAULT NULL,
    info_suplimentare TEXT DEFAULT NULL,
    status ENUM('Planificata', 'Finalizata', 'Reprogramata', 'Anulata') DEFAULT 'Planificata',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_ora (data_ora),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
