-- Tabel contacte - CRM ANR Bihor
-- Rulați în phpMyAdmin sau folosiți ensure_contacte_table() din PHP

USE `r74526anrb_internapp_crm`;

CREATE TABLE IF NOT EXISTS contacte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    prenume VARCHAR(100) DEFAULT NULL,
    companie VARCHAR(255) DEFAULT NULL,
    tip_contact VARCHAR(50) NOT NULL DEFAULT 'alte contacte',
    telefon VARCHAR(50) DEFAULT NULL,
    telefon_personal VARCHAR(50) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    email_personal VARCHAR(255) DEFAULT NULL,
    website VARCHAR(500) DEFAULT NULL,
    data_nasterii DATE DEFAULT NULL,
    notite TEXT DEFAULT NULL,
    referinta_contact VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tip_contact (tip_contact),
    INDEX idx_nume (nume),
    INDEX idx_companie (companie),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
