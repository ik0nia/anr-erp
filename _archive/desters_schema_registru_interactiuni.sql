-- Schema Registru Interacțiuni - CRM ANR Bihor (fișier arhivat; tabelele se creează din includes/registru_interactiuni_helper.php)

CREATE TABLE IF NOT EXISTS registru_interactiuni_subiecte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    activ TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordine (ordine),
    INDEX idx_activ (activ)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registru_interactiuni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tip ENUM('apel', 'vizita') NOT NULL,
    persoana VARCHAR(255) NOT NULL,
    telefon VARCHAR(50) DEFAULT NULL,
    subiect_id INT DEFAULT NULL,
    subiect_alt VARCHAR(500) DEFAULT NULL,
    notite TEXT DEFAULT NULL,
    task_activ TINYINT(1) NOT NULL DEFAULT 0,
    utilizator VARCHAR(100) NOT NULL,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    task_id INT DEFAULT NULL,
    INDEX idx_tip (tip),
    INDEX idx_data_ora (data_ora),
    INDEX idx_utilizator (utilizator),
    INDEX idx_subiect (subiect_id),
    FOREIGN KEY (subiect_id) REFERENCES registru_interactiuni_subiecte(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
