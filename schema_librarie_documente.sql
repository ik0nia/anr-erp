-- Schema modul Librărie documente - CRM ANR Bihor
-- Documente model pentru membri (Word, Excel, PDF)

CREATE TABLE IF NOT EXISTS librarie_documente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institutie VARCHAR(255) NOT NULL,
    nume_document VARCHAR(500) NOT NULL,
    nume_fisier VARCHAR(255) NOT NULL,
    cale_fisier VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_institutie (institutie),
    INDEX idx_nume (nume_document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
