-- Schema modul Ajutoare BPA (Banca pentru Alimente)
-- Evidență stoc: o singură poziție „Produse BPA”, UM: KG

-- Gestiune: mișcări de stoc (aviz = încărcare, tabel distributie = descărcare)
CREATE TABLE IF NOT EXISTS bpa_gestiune (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_document VARCHAR(100) NOT NULL,
    data_document DATE NOT NULL,
    tip_document ENUM('aviz','tabel_distributie') NOT NULL,
    cantitate DECIMAL(12,2) NOT NULL COMMENT 'Kg - pozitiv la aviz, negativ la tabel',
    loc_distributie VARCHAR(255) DEFAULT NULL COMMENT 'Pentru tabele completate pe hârtie',
    nr_beneficiari INT DEFAULT NULL COMMENT 'Pentru tabele completate pe hârtie',
    tabel_distributie_id INT DEFAULT NULL COMMENT 'FK la bpa_tabele_distributie dacă e tabel creat în platformă',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizator VARCHAR(100) DEFAULT NULL,
    INDEX idx_data (data_document),
    INDEX idx_tip (tip_document),
    INDEX idx_tabel_id (tabel_distributie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabele de distributie create în platformă
CREATE TABLE IF NOT EXISTS bpa_tabele_distributie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_tabel VARCHAR(50) NOT NULL,
    data_tabel DATE NOT NULL,
    predare_sediul TINYINT(1) NOT NULL DEFAULT 0,
    predare_centru TINYINT(1) NOT NULL DEFAULT 0,
    livrare_domiciliu TINYINT(1) NOT NULL DEFAULT 0,
    cantitate_totala DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(100) DEFAULT NULL,
    INDEX idx_data (data_tabel),
    INDEX idx_nr (nr_tabel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Randuri într-un tabel de distributie (membru din DB sau persoană manuală)
CREATE TABLE IF NOT EXISTS bpa_tabel_distributie_randuri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabel_id INT NOT NULL,
    nr_crt INT NOT NULL DEFAULT 0,
    membru_id INT DEFAULT NULL,
    nume_manual VARCHAR(255) DEFAULT NULL,
    prenume_manual VARCHAR(255) DEFAULT NULL,
    localitate VARCHAR(255) DEFAULT NULL,
    seria_nr_ci VARCHAR(100) DEFAULT NULL,
    data_nastere DATE DEFAULT NULL,
    greutate_pachet DECIMAL(10,2) NOT NULL DEFAULT 0,
    semnatura_note VARCHAR(255) DEFAULT NULL,
    ordine INT NOT NULL DEFAULT 0,
    INDEX idx_tabel (tabel_id),
    FOREIGN KEY (tabel_id) REFERENCES bpa_tabele_distributie(id) ON DELETE CASCADE,
    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adăugare FK pentru tabel_distributie_id după crearea bpa_tabele_distributie (opțional, dacă tabelele există deja)
-- ALTER TABLE bpa_gestiune ADD CONSTRAINT fk_bpa_gestiune_tabel FOREIGN KEY (tabel_distributie_id) REFERENCES bpa_tabele_distributie(id) ON DELETE SET NULL;
