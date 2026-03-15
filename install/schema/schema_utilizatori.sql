-- Schema utilizatori și recuperare parolă - CRM ANR Bihor
-- Rulați în phpMyAdmin sau MySQL după schema principală

USE `r74526anrb_internapp_crm`;

CREATE TABLE IF NOT EXISTS utilizatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume_complet VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    functie VARCHAR(255) DEFAULT NULL COMMENT 'Funcția din cadrul organizației',
    username VARCHAR(100) NOT NULL UNIQUE,
    parola_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrator', 'operator') NOT NULL DEFAULT 'operator',
    activ TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_activ (activ)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizator_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_la DATETIME NOT NULL,
    folosit TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_expira (expira_la),
    FOREIGN KEY (utilizator_id) REFERENCES utilizatori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Primul utilizator administrator se creează rulând o singură dată: install-auth.php (în browser)
-- sau din Setări → Management utilizatori după ce un admin există deja.
