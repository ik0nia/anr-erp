-- Adaugă tabelul log_activitate (pentru instalări existente)
-- Rulați acest script în phpMyAdmin selectând mai întâi baza de date corespunzătoare
USE `r74526anrb_internapp_crm`;

CREATE TABLE IF NOT EXISTS log_activitate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizator VARCHAR(100) NOT NULL,
    actiune TEXT NOT NULL,
    INDEX idx_data_ora (data_ora),
    INDEX idx_utilizator (utilizator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
