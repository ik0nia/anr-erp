-- Schema modul Notificări - CRM ANR Bihor
-- Notificări platformă pentru toți utilizatorii

CREATE TABLE IF NOT EXISTS notificari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titlu VARCHAR(500) NOT NULL,
    importanta ENUM('Normal', 'Important', 'Informativ') NOT NULL DEFAULT 'Normal',
    continut TEXT NOT NULL,
    link_extern VARCHAR(2000) DEFAULT NULL,
    atasament_nume VARCHAR(255) DEFAULT NULL,
    atasament_path VARCHAR(500) DEFAULT NULL,
    trimite_email TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_importanta (importanta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status per utilizator: nou, citit, arhivat
CREATE TABLE IF NOT EXISTS notificari_utilizatori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notificare_id INT NOT NULL,
    utilizator_id INT NOT NULL,
    status ENUM('nou', 'citit', 'arhivat') NOT NULL DEFAULT 'nou',
    citit_la DATETIME DEFAULT NULL,
    arhivat_la DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_notif_user (notificare_id, utilizator_id),
    INDEX idx_utilizator_status (utilizator_id, status),
    INDEX idx_notificare (notificare_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
