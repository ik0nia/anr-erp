-- Adaugă tabelul taskuri (pentru instalări existente)
-- Rulați în phpMyAdmin selectând baza de date corespunzătoare

CREATE TABLE IF NOT EXISTS taskuri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    data_ora DATETIME NOT NULL,
    detalii TEXT DEFAULT NULL,
    nivel_urgenta VARCHAR(20) NOT NULL DEFAULT 'normal',
    finalizat TINYINT(1) NOT NULL DEFAULT 0,
    data_finalizare DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_finalizat (finalizat),
    INDEX idx_data_ora (data_ora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
