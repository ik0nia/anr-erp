<?php
/**
 * Schema Migration - CRM ANR Bihor
 *
 * Single consolidated migration file containing ALL table definitions
 * and ALL ALTER TABLE statements as versioned migrations.
 *
 * Usage:
 *   php install/schema/migration.php
 *   Or include from setup script.
 *
 * This file:
 *   - Creates a schema_version table to track current version
 *   - Runs migrations only when needed (idempotent)
 *   - Contains ALL table definitions in one place
 *   - Contains ALL ALTER TABLE statements as versioned migrations
 */

// Allow running from CLI or inclusion from setup
if (php_sapi_name() === 'cli') {
    $config_path = __DIR__ . '/../../config.php';
    if (!file_exists($config_path)) {
        echo "ERROR: config.php not found. Run from project root.\n";
        exit(1);
    }
    require_once $config_path;
    if (!isset($pdo)) {
        echo "ERROR: \$pdo not available from config.php.\n";
        exit(1);
    }
    run_migrations($pdo);
    exit(0);
}

/**
 * Run all pending migrations.
 *
 * @param PDO $pdo
 * @return array ['applied' => int, 'errors' => string[]]
 */
function run_migrations(PDO $pdo): array {
    $result = ['applied' => 0, 'errors' => []];

    // 1. Ensure schema_version table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_version (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version INT NOT NULL,
        description VARCHAR(500) NOT NULL,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_version (version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Get current version
    $stmt = $pdo->query("SELECT COALESCE(MAX(version), 0) AS v FROM schema_version");
    $current = (int) $stmt->fetch(PDO::FETCH_ASSOC)['v'];

    // 3. Define migrations
    $migrations = get_migrations();

    // 4. Apply pending migrations
    foreach ($migrations as $version => $migration) {
        if ($version <= $current) {
            continue;
        }
        try {
            $pdo->beginTransaction();
            foreach ($migration['sql'] as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    // Many migrations are idempotent (IF NOT EXISTS, duplicate column, etc.)
                    // Log but continue
                    $msg = $e->getMessage();
                    if (strpos($msg, 'Duplicate column') !== false
                        || strpos($msg, 'Duplicate key name') !== false
                        || strpos($msg, 'already exists') !== false
                        || strpos($msg, 'Can\'t DROP') !== false
                        || strpos($msg, 'check that column/key exists') !== false
                        || strpos($msg, 'check that it exists') !== false
                    ) {
                        // Expected for idempotent migrations - skip silently
                        continue;
                    }
                    $result['errors'][] = "v{$version}: {$msg}";
                }
            }
            $pdo->prepare("INSERT INTO schema_version (version, description) VALUES (?, ?)")
                ->execute([$version, $migration['description']]);
            $pdo->commit();
            $result['applied']++;
            if (php_sapi_name() === 'cli') {
                echo "  Applied migration v{$version}: {$migration['description']}\n";
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $result['errors'][] = "v{$version} FAILED: {$e->getMessage()}";
            if (php_sapi_name() === 'cli') {
                echo "  FAILED migration v{$version}: {$e->getMessage()}\n";
            }
            // Stop on critical failure
            break;
        }
    }

    if (php_sapi_name() === 'cli') {
        echo "\nMigrations applied: {$result['applied']}\n";
        if ($result['errors']) {
            echo "Errors:\n";
            foreach ($result['errors'] as $err) {
                echo "  - {$err}\n";
            }
        }
    }

    return $result;
}

/**
 * All migrations in order. Each migration has:
 *   'description' => string
 *   'sql' => string[]  (array of SQL statements)
 *
 * @return array<int, array{description: string, sql: string[]}>
 */
function get_migrations(): array {
    return [

        // =====================================================================
        // VERSION 1: Base schema - all tables
        // =====================================================================
        1 => [
            'description' => 'Base schema: all tables',
            'sql' => [

                // ---- Core tables ----

                "CREATE TABLE IF NOT EXISTS setari (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cheie VARCHAR(100) NOT NULL UNIQUE,
                    valoare TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS membri (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS log_activitate (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    utilizator VARCHAR(100) NOT NULL,
                    actiune TEXT NOT NULL,
                    membru_id INT DEFAULT NULL,
                    INDEX idx_data_ora (data_ora),
                    INDEX idx_utilizator (utilizator),
                    INDEX idx_membru_id (membru_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Auth ----

                "CREATE TABLE IF NOT EXISTS utilizatori (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume_complet VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    functie VARCHAR(255) DEFAULT NULL,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    parola_hash VARCHAR(255) NOT NULL,
                    rol ENUM('administrator', 'operator') NOT NULL DEFAULT 'operator',
                    activ TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_email (email),
                    INDEX idx_activ (activ)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    utilizator_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    expira_la DATETIME NOT NULL,
                    folosit TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_token (token),
                    INDEX idx_expira (expira_la)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Taskuri ----

                "CREATE TABLE IF NOT EXISTS taskuri (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(255) NOT NULL,
                    data_ora DATETIME NOT NULL,
                    detalii TEXT DEFAULT NULL,
                    nivel_urgenta VARCHAR(20) NOT NULL DEFAULT 'normal',
                    finalizat TINYINT(1) NOT NULL DEFAULT 0,
                    data_finalizare DATETIME DEFAULT NULL,
                    utilizator_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_finalizat (finalizat),
                    INDEX idx_data_ora (data_ora),
                    INDEX idx_utilizator_id (utilizator_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Activitati ----

                "CREATE TABLE IF NOT EXISTS activitati (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    data_ora DATETIME NOT NULL,
                    ora_finalizare TIME DEFAULT NULL,
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Liste prezenta ----

                "CREATE TABLE IF NOT EXISTS liste_prezenta (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS liste_prezenta_membri (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lista_id INT NOT NULL,
                    membru_id INT DEFAULT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    nume_manual VARCHAR(255) DEFAULT NULL,
                    INDEX idx_lista (lista_id),
                    INDEX idx_membru (membru_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Documente template ----

                "CREATE TABLE IF NOT EXISTS documente_template (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume_afisare VARCHAR(255) NOT NULL,
                    nume_fisier VARCHAR(255) NOT NULL,
                    activ TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_activ (activ)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Registratura ----

                "CREATE TABLE IF NOT EXISTS registratura (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nr_intern INT NOT NULL DEFAULT 0,
                    nr_inregistrare VARCHAR(50) NOT NULL,
                    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    utilizator VARCHAR(100) NOT NULL,
                    tip_act VARCHAR(100) DEFAULT NULL,
                    detalii TEXT DEFAULT NULL,
                    nr_document VARCHAR(100) DEFAULT NULL,
                    data_document DATE DEFAULT NULL,
                    provine_din VARCHAR(500) DEFAULT NULL,
                    continut_document TEXT DEFAULT NULL,
                    destinatar_document VARCHAR(500) DEFAULT NULL,
                    task_deschis TINYINT(1) NOT NULL DEFAULT 0,
                    task_id INT DEFAULT NULL,
                    membru_id INT DEFAULT NULL,
                    document_path VARCHAR(500) DEFAULT NULL,
                    INDEX idx_data_ora (data_ora),
                    INDEX idx_utilizator (utilizator),
                    INDEX idx_nr_inregistrare (nr_inregistrare),
                    INDEX idx_nr_intern (nr_intern)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Registru Interactiuni v1 ----

                "CREATE TABLE IF NOT EXISTS registru_interactiuni_subiecte (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(255) NOT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    activ TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ordine (ordine)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS registru_interactiuni (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Registru Interactiuni v2 ----

                "CREATE TABLE IF NOT EXISTS registru_interactiuni_v2_subiecte (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(255) NOT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    activ TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_activ (activ),
                    INDEX idx_ordine (ordine)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS registru_interactiuni_v2 (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Contacte ----

                "CREATE TABLE IF NOT EXISTS contacte (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(100) NOT NULL,
                    prenume VARCHAR(100) DEFAULT NULL,
                    cnp VARCHAR(20) DEFAULT NULL,
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Voluntariat ----

                "CREATE TABLE IF NOT EXISTS voluntari (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(100) NOT NULL,
                    prenume VARCHAR(100) DEFAULT NULL,
                    cnp VARCHAR(20) DEFAULT NULL,
                    seria_ci VARCHAR(10) DEFAULT NULL,
                    nr_ci VARCHAR(20) DEFAULT NULL,
                    codpost VARCHAR(10) DEFAULT NULL,
                    domloc VARCHAR(100) DEFAULT NULL,
                    judet_domiciliu VARCHAR(50) DEFAULT NULL,
                    domstr VARCHAR(255) DEFAULT NULL,
                    domnr VARCHAR(20) DEFAULT NULL,
                    dombl VARCHAR(20) DEFAULT NULL,
                    domsc VARCHAR(20) DEFAULT NULL,
                    domet VARCHAR(20) DEFAULT NULL,
                    domap VARCHAR(20) DEFAULT NULL,
                    telefon VARCHAR(50) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    contact_id INT DEFAULT NULL,
                    nr_registratura VARCHAR(50) DEFAULT NULL,
                    contract_path VARCHAR(500) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_contact_id (contact_id),
                    INDEX idx_nume (nume),
                    INDEX idx_cnp (cnp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS voluntariat_activitati (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(255) NOT NULL,
                    data_activitate DATE NOT NULL,
                    ora_inceput TIME DEFAULT NULL,
                    ora_sfarsit TIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_data (data_activitate)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS voluntariat_participanti (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    activitate_id INT NOT NULL,
                    voluntar_id INT NOT NULL,
                    ore_prestate DECIMAL(5,2) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_act_vol (activitate_id, voluntar_id),
                    INDEX idx_activitate (activitate_id),
                    INDEX idx_voluntar (voluntar_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- BPA ----

                "CREATE TABLE IF NOT EXISTS bpa_gestiune (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nr_document VARCHAR(100) NOT NULL,
                    data_document DATE NOT NULL,
                    tip_document ENUM('aviz','tabel_distributie','tabel_cristal') NOT NULL,
                    cantitate DECIMAL(12,2) NOT NULL,
                    loc_distributie VARCHAR(255) DEFAULT NULL,
                    nr_beneficiari INT DEFAULT NULL,
                    tabel_distributie_id INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    utilizator VARCHAR(100) DEFAULT NULL,
                    INDEX idx_data (data_document),
                    INDEX idx_tip (tip_document),
                    INDEX idx_tabel_id (tabel_distributie_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS bpa_tabele_distributie (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS bpa_tabel_distributie_randuri (
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
                    INDEX idx_membru (membru_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Newsletter ----

                "CREATE TABLE IF NOT EXISTS newsletter (
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
                    INDEX idx_categoria (categoria_contacte),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Librarie Documente ----

                "CREATE TABLE IF NOT EXISTS librarie_documente (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    institutie VARCHAR(255) NOT NULL,
                    nume_document VARCHAR(500) NOT NULL,
                    nume_fisier VARCHAR(255) NOT NULL,
                    cale_fisier VARCHAR(500) NOT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_institutie (institutie),
                    INDEX idx_nume (nume_document),
                    INDEX idx_ordine (ordine)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Administrativ ----

                "CREATE TABLE IF NOT EXISTS administrativ_achizitii (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    denumire VARCHAR(500) NOT NULL,
                    cumparat TINYINT(1) NOT NULL DEFAULT 0,
                    data_cumparare DATE DEFAULT NULL,
                    locatie VARCHAR(50) DEFAULT NULL,
                    urgenta VARCHAR(20) NOT NULL DEFAULT 'normal',
                    furnizor VARCHAR(255) DEFAULT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_cumparat (cumparat)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_achizitii_istoric (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    achizitie_id INT NOT NULL,
                    denumire VARCHAR(500) NOT NULL,
                    data_cumparare DATE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_achizitie (achizitie_id),
                    INDEX idx_data (data_cumparare)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_angajati (
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
                    data_inceput_medicina_muncii DATE DEFAULT NULL,
                    data_expirarii_medicina_muncii DATE DEFAULT NULL,
                    data_inceput_psi_ssm DATE DEFAULT NULL,
                    data_expirarii_psi_ssm DATE DEFAULT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_nume (nume, prenume)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_consiliu_director (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    membru_id INT DEFAULT NULL,
                    nume_manual VARCHAR(255) DEFAULT NULL,
                    prenume_manual VARCHAR(255) DEFAULT NULL,
                    functie VARCHAR(255) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    telefon VARCHAR(50) DEFAULT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ordine (ordine)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_adunare_generala (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    membru_id INT DEFAULT NULL,
                    nume_manual VARCHAR(255) DEFAULT NULL,
                    prenume_manual VARCHAR(255) DEFAULT NULL,
                    functie VARCHAR(255) DEFAULT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    telefon VARCHAR(50) DEFAULT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ordine (ordine)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_calendar_termene (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_cd_sedinte (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_ag_sedinte (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_juridic_anr (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_parteneriate (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume_partener VARCHAR(500) NOT NULL,
                    obiect_parteneriat TEXT DEFAULT NULL,
                    data_inceput DATE DEFAULT NULL,
                    data_sfarsit DATE DEFAULT NULL,
                    observatii TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_sfarsit (data_sfarsit)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS administrativ_proceduri (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    titlu VARCHAR(500) NOT NULL,
                    continut LONGTEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Incasari ----

                "CREATE TABLE IF NOT EXISTS incasari (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    membru_id INT DEFAULT NULL,
                    contact_id INT DEFAULT NULL,
                    tip VARCHAR(30) NOT NULL,
                    anul SMALLINT UNSIGNED DEFAULT NULL,
                    suma DECIMAL(12,2) NOT NULL,
                    mod_plata VARCHAR(30) NOT NULL,
                    data_incasare DATE NOT NULL,
                    seria_chitanta VARCHAR(20) DEFAULT NULL,
                    nr_chitanta INT UNSIGNED DEFAULT NULL,
                    reprezentand VARCHAR(255) DEFAULT NULL,
                    observatii TEXT DEFAULT NULL,
                    created_by VARCHAR(100) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_membru (membru_id),
                    INDEX idx_contact (contact_id),
                    INDEX idx_tip (tip),
                    INDEX idx_anul (anul),
                    INDEX idx_data (data_incasare),
                    INDEX idx_created_by (created_by)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS incasari_serii (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    tip_serie VARCHAR(20) NOT NULL UNIQUE,
                    serie VARCHAR(20) NOT NULL,
                    nr_start INT UNSIGNED NOT NULL DEFAULT 1,
                    nr_curent INT UNSIGNED NOT NULL DEFAULT 1,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS incasari_setari (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cheie VARCHAR(80) NOT NULL UNIQUE,
                    valoare TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Cotizatii ----

                "CREATE TABLE IF NOT EXISTS cotizatii_opts_grad_handicap (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(100) NOT NULL,
                    ordine INT NOT NULL DEFAULT 0,
                    UNIQUE KEY uk_nume (nume)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS cotizatii_opts_asistent_personal (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nume VARCHAR(100) NOT NULL,
                    ordine INT NOT NULL DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS cotizatii_anuale (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    anul SMALLINT UNSIGNED NOT NULL,
                    grad_handicap VARCHAR(50) NOT NULL,
                    asistent_personal VARCHAR(100) NOT NULL DEFAULT '',
                    valoare_cotizatie DECIMAL(10,2) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_anul_grad_asistent (anul, grad_handicap, asistent_personal),
                    INDEX idx_anul (anul)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS cotizatii_scutiri (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    membru_id INT NOT NULL,
                    data_scutire_pana_la DATE DEFAULT NULL,
                    scutire_permanenta TINYINT(1) NOT NULL DEFAULT 0,
                    motiv TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_membru (membru_id),
                    INDEX idx_scutire_activa (membru_id, scutire_permanenta, data_scutire_pana_la)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // ---- Mailer ----

                "CREATE TABLE IF NOT EXISTS settings_email (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    smtp_host VARCHAR(255) DEFAULT NULL,
                    smtp_port INT DEFAULT 587,
                    smtp_user VARCHAR(255) DEFAULT NULL,
                    smtp_pass VARCHAR(255) DEFAULT NULL,
                    smtp_encryption VARCHAR(20) DEFAULT 'tls',
                    from_name VARCHAR(255) DEFAULT NULL,
                    from_email VARCHAR(255) DEFAULT NULL,
                    email_signature TEXT DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                // Insert default settings_email row
                "INSERT IGNORE INTO settings_email (id) VALUES (1)",

                // Insert default incasari_serii
                "INSERT IGNORE INTO incasari_serii (tip_serie, serie, nr_start, nr_curent) VALUES ('donatii','D',1,1)",
                "INSERT IGNORE INTO incasari_serii (tip_serie, serie, nr_start, nr_curent) VALUES ('incasari','INC',1,1)",
            ],
        ],

        // =====================================================================
        // VERSION 2: Existing ALTER TABLE migrations (consolidated from runtime)
        // =====================================================================
        2 => [
            'description' => 'Runtime ALTER TABLE migrations consolidated',
            'sql' => [

                // -- administrativ_achizitii columns --
                "ALTER TABLE administrativ_achizitii ADD COLUMN locatie VARCHAR(50) DEFAULT NULL",
                "ALTER TABLE administrativ_achizitii ADD COLUMN urgenta VARCHAR(20) NOT NULL DEFAULT 'normal'",
                "ALTER TABLE administrativ_achizitii ADD COLUMN furnizor VARCHAR(255) DEFAULT NULL",

                // -- administrativ_angajati columns --
                "ALTER TABLE administrativ_angajati ADD COLUMN data_inceput_medicina_muncii DATE DEFAULT NULL",
                "ALTER TABLE administrativ_angajati ADD COLUMN data_expirarii_medicina_muncii DATE DEFAULT NULL",
                "ALTER TABLE administrativ_angajati ADD COLUMN data_inceput_psi_ssm DATE DEFAULT NULL",
                "ALTER TABLE administrativ_angajati ADD COLUMN data_expirarii_psi_ssm DATE DEFAULT NULL",

                // -- administrativ_consiliu_director columns --
                "ALTER TABLE administrativ_consiliu_director ADD COLUMN email VARCHAR(255) DEFAULT NULL",
                "ALTER TABLE administrativ_consiliu_director ADD COLUMN telefon VARCHAR(50) DEFAULT NULL",

                // -- administrativ_adunare_generala columns --
                "ALTER TABLE administrativ_adunare_generala ADD COLUMN functie VARCHAR(255) DEFAULT NULL",
                "ALTER TABLE administrativ_adunare_generala ADD COLUMN email VARCHAR(255) DEFAULT NULL",
                "ALTER TABLE administrativ_adunare_generala ADD COLUMN telefon VARCHAR(50) DEFAULT NULL",

                // -- bpa_gestiune ENUM update --
                "ALTER TABLE bpa_gestiune MODIFY COLUMN tip_document ENUM('aviz','tabel_distributie','tabel_cristal') NOT NULL",

                // -- librarie_documente ordine column --
                "ALTER TABLE librarie_documente ADD COLUMN ordine INT NOT NULL DEFAULT 0 AFTER cale_fisier",

                // -- registratura columns --
                "ALTER TABLE registratura ADD COLUMN nr_intern INT NOT NULL DEFAULT 0 AFTER id",
                "ALTER TABLE registratura ADD COLUMN nr_document VARCHAR(100) DEFAULT NULL AFTER detalii",
                "ALTER TABLE registratura ADD COLUMN data_document DATE DEFAULT NULL AFTER nr_document",
                "ALTER TABLE registratura ADD COLUMN provine_din VARCHAR(500) DEFAULT NULL AFTER data_document",
                "ALTER TABLE registratura ADD COLUMN continut_document TEXT DEFAULT NULL AFTER provine_din",
                "ALTER TABLE registratura ADD COLUMN destinatar_document VARCHAR(500) DEFAULT NULL AFTER continut_document",
                "ALTER TABLE registratura ADD COLUMN task_deschis TINYINT(1) NOT NULL DEFAULT 0 AFTER destinatar_document",
                "ALTER TABLE registratura ADD COLUMN task_id INT DEFAULT NULL AFTER task_deschis",

                // -- incasari columns --
                "ALTER TABLE incasari ADD COLUMN contact_id INT DEFAULT NULL AFTER membru_id",
                "ALTER TABLE incasari ADD COLUMN reprezentand VARCHAR(255) DEFAULT NULL AFTER nr_chitanta",
                "ALTER TABLE incasari MODIFY COLUMN membru_id INT DEFAULT NULL",

                // -- contacte cnp column --
                "ALTER TABLE contacte ADD COLUMN cnp VARCHAR(20) DEFAULT NULL AFTER prenume",

                // -- cotizatii_anuale asistent_personal column --
                "ALTER TABLE cotizatii_anuale ADD COLUMN asistent_personal VARCHAR(100) NOT NULL DEFAULT '' AFTER grad_handicap",

                // -- taskuri utilizator_id column --
                "ALTER TABLE taskuri ADD COLUMN utilizator_id INT DEFAULT NULL",

                // -- activitati columns --
                "ALTER TABLE activitati ADD COLUMN recurenta VARCHAR(20) DEFAULT NULL",
                "ALTER TABLE activitati ADD COLUMN lista_prezenta_id INT DEFAULT NULL",
                "ALTER TABLE activitati ADD COLUMN ora_finalizare TIME DEFAULT NULL AFTER data_ora",

                // -- liste_prezenta_membri columns --
                "ALTER TABLE liste_prezenta_membri ADD COLUMN nume_manual VARCHAR(255) DEFAULT NULL AFTER membru_id",
                "ALTER TABLE liste_prezenta_membri MODIFY COLUMN membru_id INT DEFAULT NULL",
            ],
        ],

        // =====================================================================
        // VERSION 3: Missing indexes
        // =====================================================================
        3 => [
            'description' => 'Add missing indexes',
            'sql' => [
                "ALTER TABLE bpa_tabel_distributie_randuri ADD INDEX idx_membru_id (membru_id)",
                "ALTER TABLE registratura ADD INDEX idx_task_id (task_id)",
                "ALTER TABLE administrativ_calendar_termene ADD INDEX idx_angajat_id (angajat_id)",
                "ALTER TABLE incasari ADD INDEX idx_created_by (created_by)",
                "ALTER TABLE newsletter ADD INDEX idx_created_at (created_at)",
            ],
        ],

        // =====================================================================
        // VERSION 4: Foreign keys
        // =====================================================================
        4 => [
            'description' => 'Add foreign keys',
            'sql' => [
                "ALTER TABLE voluntariat_participanti ADD CONSTRAINT fk_vol_part_activitate FOREIGN KEY (activitate_id) REFERENCES voluntariat_activitati(id) ON DELETE CASCADE",
                "ALTER TABLE voluntariat_participanti ADD CONSTRAINT fk_vol_part_voluntar FOREIGN KEY (voluntar_id) REFERENCES voluntari(id) ON DELETE CASCADE",
                "ALTER TABLE voluntari ADD CONSTRAINT fk_voluntari_contact FOREIGN KEY (contact_id) REFERENCES contacte(id) ON DELETE SET NULL",
                "ALTER TABLE incasari ADD CONSTRAINT fk_incasari_contact FOREIGN KEY (contact_id) REFERENCES contacte(id) ON DELETE SET NULL",
                "ALTER TABLE taskuri ADD CONSTRAINT fk_taskuri_utilizator FOREIGN KEY (utilizator_id) REFERENCES utilizatori(id) ON DELETE SET NULL",
            ],
        ],

        // =====================================================================
        // VERSION 5: Attachment history for members (certificates, ID cards, etc.)
        // =====================================================================
        5 => [
            'description' => 'Create membri_atasamente table for attachment history',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS membri_atasamente (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    membru_id INT NOT NULL,
                    tip ENUM('certificat_handicap', 'act_identitate', 'alt_document') NOT NULL DEFAULT 'certificat_handicap',
                    fisier VARCHAR(500) NOT NULL,
                    nota TEXT DEFAULT NULL,
                    uploaded_by VARCHAR(100) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_membru (membru_id),
                    INDEX idx_tip (tip),
                    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ],
        ],

        // =====================================================================
        // VERSION 6: Add insotitor column to membri table
        // =====================================================================
        6 => [
            'description' => 'Add insotitor column to membri table for Asistent Personal tracking',
            'sql' => [
                "ALTER TABLE membri ADD COLUMN insotitor VARCHAR(100) DEFAULT NULL AFTER hdur",
            ],
        ],

        7 => [
            'description' => 'Seed cotizatii options tables with grad handicap and asistent personal values',
            'sql' => [
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Grav cu insotitor', 1)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Grav', 2)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Accentuat', 3)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Mediu', 4)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Usor', 5)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Alt handicap', 6)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Asociat', 7)",
                "INSERT IGNORE INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES ('Fara handicap', 8)",
                "INSERT IGNORE INTO cotizatii_opts_asistent_personal (nume, ordine) VALUES ('Cu asistent personal', 1)",
                "INSERT IGNORE INTO cotizatii_opts_asistent_personal (nume, ordine) VALUES ('Fara asistent personal', 2)",
            ],
        ],

    ];
}
