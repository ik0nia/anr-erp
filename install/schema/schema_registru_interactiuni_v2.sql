-- Schema Registru Interacțiuni v2 - CRM ANR Bihor
-- Modul independent pentru gestionarea interacțiunilor (apeluri și vizite)
-- Rulați acest script în phpMyAdmin selectând mai întâi baza de date corespunzătoare

USE `r74526anrb_internapp_crm`;

-- Tabel pentru subiectele de interacțiuni (v2)
CREATE TABLE IF NOT EXISTS registru_interactiuni_v2_subiecte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    activ TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_activ (activ),
    INDEX idx_ordine (ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel principal pentru interacțiuni (v2)
CREATE TABLE IF NOT EXISTS registru_interactiuni_v2 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tip ENUM('apel', 'vizita') NOT NULL DEFAULT 'apel',
    persoana VARCHAR(255) NOT NULL,
    telefon VARCHAR(50) DEFAULT NULL,
    subiect_id INT DEFAULT NULL,
    subiect_alt VARCHAR(500) DEFAULT NULL,
    notite TEXT DEFAULT NULL,
    informatii_suplimentare TEXT DEFAULT NULL,
    task_activ TINYINT(1) NOT NULL DEFAULT 0,
    utilizator VARCHAR(100) NOT NULL,
    utilizator_id INT DEFAULT NULL,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    task_id INT DEFAULT NULL,
    INDEX idx_tip (tip),
    INDEX idx_data_ora (data_ora),
    INDEX idx_utilizator (utilizator),
    INDEX idx_utilizator_id (utilizator_id),
    INDEX idx_subiect_id (subiect_id),
    INDEX idx_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
