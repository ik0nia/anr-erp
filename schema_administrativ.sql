-- Modul Administrativ - CRM ANR Bihor
-- Necesar achiziții, Echipa, Calendar administrativ, CD, AG, Juridic ANR, Parteneriate, Proceduri interne

-- 1. Necesar achiziții (listă produse + istoric cumpărate)
CREATE TABLE IF NOT EXISTS administrativ_achizitii (
    id INT AUTO_INCREMENT PRIMARY KEY,
    denumire VARCHAR(500) NOT NULL,
    cumparat TINYINT(1) NOT NULL DEFAULT 0,
    data_cumparare DATE DEFAULT NULL,
    ordine INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cumparat (cumparat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS administrativ_achizitii_istoric (
    id INT AUTO_INCREMENT PRIMARY KEY,
    achizitie_id INT NOT NULL,
    denumire VARCHAR(500) NOT NULL,
    data_cumparare DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_achizitie (achizitie_id),
    INDEX idx_data (data_cumparare)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Echipa: angajați, Consiliul Director, Adunarea Generală
CREATE TABLE IF NOT EXISTS administrativ_angajati (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    prenume VARCHAR(255) NOT NULL,
    functie VARCHAR(255) DEFAULT NULL,
    data_angajare DATE DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    telefon VARCHAR(50) DEFAULT NULL,
    notificare_medicina_muncii TINYINT(1) NOT NULL DEFAULT 1,
    notificare_instruire_psi_ssm TINYINT(1) NOT NULL DEFAULT 1,
    observatii TEXT DEFAULT NULL,
    ordine INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nume (nume, prenume)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS administrativ_consiliu_director (
    id INT AUTO_INCREMENT PRIMARY KEY,
    membru_id INT DEFAULT NULL,
    nume_manual VARCHAR(255) DEFAULT NULL,
    prenume_manual VARCHAR(255) DEFAULT NULL,
    functie VARCHAR(255) DEFAULT NULL,
    ordine INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordine (ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS administrativ_adunare_generala (
    id INT AUTO_INCREMENT PRIMARY KEY,
    membru_id INT DEFAULT NULL,
    nume_manual VARCHAR(255) DEFAULT NULL,
    prenume_manual VARCHAR(255) DEFAULT NULL,
    ordine INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordine (ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Calendar administrativ - termene valabilitate documente
CREATE TABLE IF NOT EXISTS administrativ_calendar_termene (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(500) NOT NULL,
    data_inceput DATE NOT NULL,
    data_expirarii DATE NOT NULL,
    tip_document ENUM('hotarare_ag','decizie_cd','medicina_muncii','instructaj_psi_ssm','contract','alt_document') NOT NULL DEFAULT 'alt_document',
    observatii TEXT DEFAULT NULL,
    angajat_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expirarii (data_expirarii),
    INDEX idx_tip (tip_document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Sedințe Consiliul Director (programări + legătură activități)
CREATE TABLE IF NOT EXISTS administrativ_cd_sedinte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_sedinta DATE NOT NULL,
    ora TIME NOT NULL,
    loc VARCHAR(255) DEFAULT NULL,
    stare ENUM('programata','realizata','anulata') NOT NULL DEFAULT 'programata',
    activitate_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data (data_sedinta),
    INDEX idx_activitate (activitate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Sedințe Adunarea Generală
CREATE TABLE IF NOT EXISTS administrativ_ag_sedinte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_sedinta DATE NOT NULL,
    ora TIME NOT NULL,
    loc VARCHAR(255) DEFAULT NULL,
    stare ENUM('programata','realizata','anulata') NOT NULL DEFAULT 'programata',
    activitate_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_data (data_sedinta),
    INDEX idx_activitate (activitate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Juridic ANR - informații primite pe email
CREATE TABLE IF NOT EXISTS administrativ_juridic_anr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subiect VARCHAR(500) NOT NULL,
    categorie ENUM('legislativ','hotarari_anr_agn','decizii_anr_cdn','proiecte','comunicari','altele') NOT NULL DEFAULT 'altele',
    data_document DATE DEFAULT NULL,
    nr_document VARCHAR(100) DEFAULT NULL,
    continut LONGTEXT DEFAULT NULL,
    creaza_task_todo TINYINT(1) NOT NULL DEFAULT 0,
    trimite_notificare_platforma TINYINT(1) NOT NULL DEFAULT 0,
    task_id INT DEFAULT NULL,
    notificare_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categorie (categorie),
    INDEX idx_data (data_document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Parteneriate
CREATE TABLE IF NOT EXISTS administrativ_parteneriate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume_partener VARCHAR(500) NOT NULL,
    obiect_parteneriat TEXT DEFAULT NULL,
    data_inceput DATE DEFAULT NULL,
    data_sfarsit DATE DEFAULT NULL,
    observatii TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sfarsit (data_sfarsit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Proceduri interne
CREATE TABLE IF NOT EXISTS administrativ_proceduri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titlu VARCHAR(500) NOT NULL,
    continut LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT idx_cautare (titlu, continut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
