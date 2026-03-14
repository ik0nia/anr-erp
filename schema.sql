-- Schema bază de date CRM ANR Bihor
-- Rulați acest script în phpMyAdmin sau MySQL pentru a crea baza de date și tabelul membri

CREATE DATABASE IF NOT EXISTS `r74526anrb_internapp_crm` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `r74526anrb_internapp_crm`;

CREATE TABLE IF NOT EXISTS membri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosarnr VARCHAR(50) DEFAULT NULL,
    dosardata DATE DEFAULT NULL,
    status_dosar ENUM('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat') DEFAULT 'Activ',
    nume VARCHAR(100) NOT NULL,
    prenume VARCHAR(100) NOT NULL,
    telefonnev VARCHAR(20) DEFAULT NULL,
    telefonapartinator VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    datanastere DATE DEFAULT NULL,
    locnastere VARCHAR(100) DEFAULT NULL,
    judnastere VARCHAR(50) DEFAULT NULL,
    ciseria VARCHAR(2) DEFAULT NULL,
    cinumar VARCHAR(7) DEFAULT NULL,
    cielib VARCHAR(100) DEFAULT NULL,
    cidataelib DATE DEFAULT NULL,
    cidataexp DATE DEFAULT NULL,
    gdpr TINYINT(1) DEFAULT 0,
    codpost VARCHAR(10) DEFAULT NULL,
    tipmediuur ENUM('Urban', 'Rural') DEFAULT NULL,
    domloc VARCHAR(100) DEFAULT NULL,
    judet_domiciliu VARCHAR(50) DEFAULT NULL,
    domstr VARCHAR(100) DEFAULT NULL,
    domnr VARCHAR(20) DEFAULT NULL,
    dombl VARCHAR(20) DEFAULT NULL,
    domsc VARCHAR(10) DEFAULT NULL,
    domet VARCHAR(10) DEFAULT NULL,
    domap VARCHAR(10) DEFAULT NULL,
    sex ENUM('Masculin', 'Feminin') DEFAULT NULL,
    hgrad ENUM('Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap') DEFAULT NULL,
    hmotiv VARCHAR(255) DEFAULT NULL,
    hdur ENUM('Permanent', 'Revizuibil') DEFAULT NULL,
    cnp VARCHAR(13) NOT NULL UNIQUE,
    cenr VARCHAR(50) DEFAULT NULL,
    cedata DATE DEFAULT NULL,
    ceexp DATE DEFAULT NULL,
    primaria VARCHAR(255) DEFAULT NULL,
    doc_ci VARCHAR(255) DEFAULT NULL,
    doc_ch VARCHAR(255) DEFAULT NULL,
    notamembru TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dosarnr (dosarnr),
    INDEX idx_cnp (cnp),
    INDEX idx_nume_prenume (nume, prenume),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS log_activitate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizator VARCHAR(100) NOT NULL,
    actiune TEXT NOT NULL,
    membru_id INT DEFAULT NULL,
    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE SET NULL,
    INDEX idx_data_ora (data_ora),
    INDEX idx_utilizator (utilizator),
    INDEX idx_membru_id (membru_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS activitati (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_ora DATETIME NOT NULL,
    nume VARCHAR(255) NOT NULL,
    locatie VARCHAR(255) DEFAULT NULL,
    responsabili TEXT DEFAULT NULL,
    info_suplimentare TEXT DEFAULT NULL,
    status ENUM('Planificata', 'Finalizata', 'Reprogramata', 'Anulata') DEFAULT 'Planificata',
    recurenta VARCHAR(20) DEFAULT NULL,
    lista_prezenta_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data_ora (data_ora),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liste_prezenta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tip_titlu VARCHAR(50) NOT NULL DEFAULT 'Lista prezenta',
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
    INDEX idx_activitate (activitate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS liste_prezenta_membri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lista_id INT NOT NULL,
    membru_id INT NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    INDEX idx_lista (lista_id),
    INDEX idx_membru (membru_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documente_template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume_afisare VARCHAR(255) NOT NULL,
    nume_fisier VARCHAR(255) NOT NULL,
    activ TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activ (activ)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registratura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_inregistrare VARCHAR(50) NOT NULL,
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilizator VARCHAR(100) NOT NULL,
    tip_act VARCHAR(100) NOT NULL,
    detalii TEXT DEFAULT NULL,
    membru_id INT DEFAULT NULL,
    document_path VARCHAR(500) DEFAULT NULL,
    INDEX idx_data_ora (data_ora),
    INDEX idx_utilizator (utilizator),
    INDEX idx_nr_inregistrare (nr_inregistrare),
    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registru Interacțiuni (apeluri și vizite)
CREATE TABLE IF NOT EXISTS registru_interactiuni_subiecte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    ordine INT NOT NULL DEFAULT 0,
    activ TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordine (ordine)
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
    INDEX idx_utilizator (utilizator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
