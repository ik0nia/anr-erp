-- Schema modul Newsletter - CRM ANR Bihor
-- Tabel newsletter: drafturi, trimise, programate

CREATE TABLE IF NOT EXISTS newsletter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subiect VARCHAR(500) NOT NULL,
    continut LONGTEXT NOT NULL,
    nume_expeditor VARCHAR(255) DEFAULT NULL,
    categoria_contacte VARCHAR(100) NOT NULL DEFAULT '',
    nr_recipienti INT NOT NULL DEFAULT 0,
    atasament_nume VARCHAR(255) DEFAULT NULL,
    atasament_path VARCHAR(500) DEFAULT NULL,
    status ENUM('draft', 'trimis', 'programat') NOT NULL DEFAULT 'draft',
    data_trimiterii DATETIME DEFAULT NULL,
    data_programata DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_data_trimiterii (data_trimiterii),
    INDEX idx_categoria (categoria_contacte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
