-- Schema liste de prezență și tabele nominale
-- Rulați în phpMyAdmin

-- Adăugare coloană recurență în activități
ALTER TABLE activitati ADD COLUMN IF NOT EXISTS recurenta ENUM('', 'zilnic', 'saptamanal', 'lunar', 'anual') DEFAULT '' AFTER status;
ALTER TABLE activitati ADD COLUMN IF NOT EXISTS lista_prezenta_id INT DEFAULT NULL AFTER recurenta;
ALTER TABLE activitati ADD INDEX IF NOT EXISTS idx_lista_prezenta (lista_prezenta_id);

-- Tabel liste prezență
CREATE TABLE IF NOT EXISTS liste_prezenta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tip_titlu ENUM('Lista prezenta', 'Tabel nominal') NOT NULL DEFAULT 'Lista prezenta',
    detalii_activitate VARCHAR(500) DEFAULT NULL,
    data_lista DATE NOT NULL,
    detalii_suplimentare_sus TEXT DEFAULT NULL,
    coloane_selectate JSON DEFAULT NULL,
    detalii_suplimentare_jos TEXT DEFAULT NULL,
    semnatura_stanga_nume VARCHAR(100) DEFAULT NULL,
    semnatura_stanga_functie VARCHAR(100) DEFAULT NULL,
    semnatura_centru_nume VARCHAR(100) DEFAULT NULL,
    semnatura_centru_functie VARCHAR(100) DEFAULT NULL,
    semnatura_dreapta_nume VARCHAR(100) DEFAULT NULL,
    semnatura_dreapta_functie VARCHAR(100) DEFAULT NULL,
    activitate_id INT DEFAULT NULL,
    created_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_lista (data_lista),
    INDEX idx_activitate (activitate_id),
    FOREIGN KEY (activitate_id) REFERENCES activitati(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participanți la liste
CREATE TABLE IF NOT EXISTS liste_prezenta_membri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    membru_id INT NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    INDEX idx_lista (lista_id),
    INDEX idx_membru (membru_id),
    FOREIGN KEY (lista_id) REFERENCES liste_prezenta(id) ON DELETE CASCADE,
    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
