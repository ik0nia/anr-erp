<?php
/**
 * Helper modul Administrativ - CRM ANR Bihor
 * Achiziții, Echipa, Calendar termene, CD, AG, Juridic ANR, Parteneriate, Proceduri
 */

function administrativ_ensure_tables(PDO $pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS administrativ_achizitii (id INT AUTO_INCREMENT PRIMARY KEY, denumire VARCHAR(500) NOT NULL, cumparat TINYINT(1) NOT NULL DEFAULT 0, data_cumparare DATE DEFAULT NULL, ordine INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_cumparat (cumparat)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_achizitii_istoric (id INT AUTO_INCREMENT PRIMARY KEY, achizitie_id INT NOT NULL, denumire VARCHAR(500) NOT NULL, data_cumparare DATE NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_achizitie (achizitie_id), INDEX idx_data (data_cumparare)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_angajati (id INT AUTO_INCREMENT PRIMARY KEY, nume VARCHAR(255) NOT NULL, prenume VARCHAR(255) NOT NULL, functie VARCHAR(255) DEFAULT NULL, data_angajare DATE DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, telefon VARCHAR(50) DEFAULT NULL, notificare_medicina_muncii TINYINT(1) NOT NULL DEFAULT 1, notificare_instruire_psi_ssm TINYINT(1) NOT NULL DEFAULT 1, observatii TEXT DEFAULT NULL, ordine INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_nume (nume, prenume)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_consiliu_director (id INT AUTO_INCREMENT PRIMARY KEY, membru_id INT DEFAULT NULL, nume_manual VARCHAR(255) DEFAULT NULL, prenume_manual VARCHAR(255) DEFAULT NULL, functie VARCHAR(255) DEFAULT NULL, ordine INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_ordine (ordine)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_adunare_generala (id INT AUTO_INCREMENT PRIMARY KEY, membru_id INT DEFAULT NULL, nume_manual VARCHAR(255) DEFAULT NULL, prenume_manual VARCHAR(255) DEFAULT NULL, ordine INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_ordine (ordine)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_calendar_termene (id INT AUTO_INCREMENT PRIMARY KEY, nume VARCHAR(500) NOT NULL, data_inceput DATE NOT NULL, data_expirarii DATE NOT NULL, tip_document ENUM('hotarare_ag','decizie_cd','medicina_muncii','instructaj_psi_ssm','contract','alt_document') NOT NULL DEFAULT 'alt_document', observatii TEXT DEFAULT NULL, angajat_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_expirarii (data_expirarii), INDEX idx_tip (tip_document)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_cd_sedinte (id INT AUTO_INCREMENT PRIMARY KEY, data_sedinta DATE NOT NULL, ora TIME NOT NULL, loc VARCHAR(255) DEFAULT NULL, stare ENUM('programata','realizata','anulata') NOT NULL DEFAULT 'programata', activitate_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_data (data_sedinta), INDEX idx_activitate (activitate_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_ag_sedinte (id INT AUTO_INCREMENT PRIMARY KEY, data_sedinta DATE NOT NULL, ora TIME NOT NULL, loc VARCHAR(255) DEFAULT NULL, stare ENUM('programata','realizata','anulata') NOT NULL DEFAULT 'programata', activitate_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_data (data_sedinta), INDEX idx_activitate (activitate_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_juridic_anr (id INT AUTO_INCREMENT PRIMARY KEY, subiect VARCHAR(500) NOT NULL, categorie ENUM('legislativ','hotarari_anr_agn','decizii_anr_cdn','proiecte','comunicari','altele') NOT NULL DEFAULT 'altele', data_document DATE DEFAULT NULL, nr_document VARCHAR(100) DEFAULT NULL, continut LONGTEXT DEFAULT NULL, creaza_task_todo TINYINT(1) NOT NULL DEFAULT 0, trimite_notificare_platforma TINYINT(1) NOT NULL DEFAULT 0, task_id INT DEFAULT NULL, notificare_id INT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_categorie (categorie), INDEX idx_data (data_document)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_parteneriate (id INT AUTO_INCREMENT PRIMARY KEY, nume_partener VARCHAR(500) NOT NULL, obiect_parteneriat TEXT DEFAULT NULL, data_inceput DATE DEFAULT NULL, data_sfarsit DATE DEFAULT NULL, observatii TEXT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_sfarsit (data_sfarsit)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS administrativ_proceduri (id INT AUTO_INCREMENT PRIMARY KEY, titlu VARCHAR(500) NOT NULL, continut LONGTEXT DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {}
    }
    // Migrări: coloane noi achiziții, angajați, CD, AG
    $alter_achizitii = ["ALTER TABLE administrativ_achizitii ADD COLUMN locatie VARCHAR(50) DEFAULT NULL", "ALTER TABLE administrativ_achizitii ADD COLUMN urgenta VARCHAR(20) NOT NULL DEFAULT 'normal'", "ALTER TABLE administrativ_achizitii ADD COLUMN furnizor VARCHAR(255) DEFAULT NULL"];
    foreach ($alter_achizitii as $a) { try { $pdo->exec($a); } catch (PDOException $e) {} }
    $alter_angajati = ["ALTER TABLE administrativ_angajati ADD COLUMN data_inceput_medicina_muncii DATE DEFAULT NULL", "ALTER TABLE administrativ_angajati ADD COLUMN data_expirarii_medicina_muncii DATE DEFAULT NULL", "ALTER TABLE administrativ_angajati ADD COLUMN data_inceput_psi_ssm DATE DEFAULT NULL", "ALTER TABLE administrativ_angajati ADD COLUMN data_expirarii_psi_ssm DATE DEFAULT NULL"];
    foreach ($alter_angajati as $a) { try { $pdo->exec($a); } catch (PDOException $e) {} }
    $alter_cd = ["ALTER TABLE administrativ_consiliu_director ADD COLUMN email VARCHAR(255) DEFAULT NULL", "ALTER TABLE administrativ_consiliu_director ADD COLUMN telefon VARCHAR(50) DEFAULT NULL"];
    foreach ($alter_cd as $a) { try { $pdo->exec($a); } catch (PDOException $e) {} }
    $alter_ag = ["ALTER TABLE administrativ_adunare_generala ADD COLUMN functie VARCHAR(255) DEFAULT NULL", "ALTER TABLE administrativ_adunare_generala ADD COLUMN email VARCHAR(255) DEFAULT NULL", "ALTER TABLE administrativ_adunare_generala ADD COLUMN telefon VARCHAR(50) DEFAULT NULL"];
    foreach ($alter_ag as $a) { try { $pdo->exec($a); } catch (PDOException $e) {} }
}

// ---- Necesar achiziții ----
function administrativ_achizitii_lista(PDO $pdo, $doar_necumparate = false) {
    administrativ_ensure_tables($pdo);
    $sql = "SELECT * FROM administrativ_achizitii WHERE 1=1";
    if ($doar_necumparate) {
        $sql .= " AND cumparat = 0";
    }
    $sql .= " ORDER BY ordine ASC, id ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_achizitie_adauga(PDO $pdo, $denumire, $locatie = null, $urgenta = 'normal', $furnizor = null) {
    administrativ_ensure_tables($pdo);
    $locatie = in_array($locatie, ['Sediu', 'Centru', 'Alta']) ? $locatie : null;
    $urgenta = in_array($urgenta, ['normal', 'urgent', 'optional']) ? $urgenta : 'normal';
    $stmt = $pdo->prepare("INSERT INTO administrativ_achizitii (denumire, locatie, urgenta, furnizor, ordine) SELECT ?, ?, ?, ?, COALESCE(MAX(ordine),0)+1 FROM administrativ_achizitii");
    $stmt->execute([trim($denumire), $locatie, $urgenta, $furnizor ? trim($furnizor) : null]);
    return (int)$pdo->lastInsertId();
}

function administrativ_achizitie_marcheaza_cumparat(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT id, denumire FROM administrativ_achizitii WHERE id = ? AND cumparat = 0");
    $stmt->execute([(int)$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    $pdo->prepare("INSERT INTO administrativ_achizitii_istoric (achizitie_id, denumire, data_cumparare) VALUES (?, ?, CURDATE())")->execute([$id, $row['denumire']]);
    $pdo->prepare("UPDATE administrativ_achizitii SET cumparat = 1, data_cumparare = CURDATE() WHERE id = ?")->execute([$id]);
    return true;
}

function administrativ_achizitie_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_achizitii WHERE id = ?")->execute([(int)$id]);
    return true;
}

function administrativ_achizitii_istoric(PDO $pdo, $limit = 100) {
    administrativ_ensure_tables($pdo);
    return $pdo->query("SELECT * FROM administrativ_achizitii_istoric ORDER BY data_cumparare DESC, id DESC LIMIT " . (int)$limit)->fetchAll(PDO::FETCH_ASSOC);
}

// ---- Echipa: angajați ----
function administrativ_angajati_lista(PDO $pdo) {
    administrativ_ensure_tables($pdo);
    return $pdo->query("SELECT * FROM administrativ_angajati ORDER BY ordine ASC, nume, prenume")->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_angajat_salveaza(PDO $pdo, $id, $date) {
    administrativ_ensure_tables($pdo);
    $nume = trim($date['nume'] ?? ''); $prenume = trim($date['prenume'] ?? '');
    $functie = trim($date['functie'] ?? ''); $data_angajare = trim($date['data_angajare'] ?? '') ?: null;
    $email = trim($date['email'] ?? ''); $telefon = trim($date['telefon'] ?? '');
    $notif_med = !empty($date['notificare_medicina_muncii']) ? 1 : 0;
    $notif_psi = !empty($date['notificare_instruire_psi_ssm']) ? 1 : 0;
    $obs = trim($date['observatii'] ?? '');
    $d_med_inc = trim($date['data_inceput_medicina_muncii'] ?? '') ?: null;
    $d_med_exp = trim($date['data_expirarii_medicina_muncii'] ?? '') ?: null;
    $d_psi_inc = trim($date['data_inceput_psi_ssm'] ?? '') ?: null;
    $d_psi_exp = trim($date['data_expirarii_psi_ssm'] ?? '') ?: null;
    if (!$nume && !$prenume) return false;
    $cols_extra = "data_inceput_medicina_muncii=?, data_expirarii_medicina_muncii=?, data_inceput_psi_ssm=?, data_expirarii_psi_ssm=?";
    if ($id > 0) {
        $pdo->prepare("UPDATE administrativ_angajati SET nume=?, prenume=?, functie=?, data_angajare=?, email=?, telefon=?, notificare_medicina_muncii=?, notificare_instruire_psi_ssm=?, observatii=?, $cols_extra WHERE id=?")
            ->execute([$nume, $prenume, $functie, $data_angajare ?: null, $email ?: null, $telefon ?: null, $notif_med, $notif_psi, $obs ?: null, $d_med_inc, $d_med_exp, $d_psi_inc, $d_psi_exp, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO administrativ_angajati (nume, prenume, functie, data_angajare, email, telefon, notificare_medicina_muncii, notificare_instruire_psi_ssm, observatii, data_inceput_medicina_muncii, data_expirarii_medicina_muncii, data_inceput_psi_ssm, data_expirarii_psi_ssm) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$nume, $prenume, $functie, $data_angajare, $email ?: null, $telefon ?: null, $notif_med, $notif_psi, $obs ?: null, $d_med_inc, $d_med_exp, $d_psi_inc, $d_psi_exp]);
    return (int)$pdo->lastInsertId();
}

function administrativ_angajat_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_angajati WHERE id = ?")->execute([(int)$id]);
    return true;
}

function administrativ_angajat_get(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $s = $pdo->prepare("SELECT * FROM administrativ_angajati WHERE id = ?");
    $s->execute([(int)$id]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ---- Consiliul Director / Adunarea Generală (nomenclator) ----
function administrativ_cd_lista(PDO $pdo) {
    administrativ_ensure_tables($pdo);
    return $pdo->query("SELECT c.*, m.nume AS membru_nume, m.prenume AS membru_prenume FROM administrativ_consiliu_director c LEFT JOIN membri m ON m.id = c.membru_id ORDER BY c.ordine, c.id")->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_cd_salveaza(PDO $pdo, $id, $membru_id, $nume_manual, $prenume_manual, $functie, $ordine, $email = null, $telefon = null) {
    administrativ_ensure_tables($pdo);
    $membru_id = $membru_id ? (int)$membru_id : null;
    $nume_manual = trim($nume_manual ?? ''); $prenume_manual = trim($prenume_manual ?? '');
    $functie = trim($functie ?? ''); $ordine = (int)$ordine;
    $email = trim($email ?? ''); $telefon = trim($telefon ?? '');
    if ($id > 0) {
        $pdo->prepare("UPDATE administrativ_consiliu_director SET membru_id=?, nume_manual=?, prenume_manual=?, functie=?, ordine=?, email=?, telefon=? WHERE id=?")
            ->execute([$membru_id, $nume_manual ?: null, $prenume_manual ?: null, $functie ?: null, $ordine, $email ?: null, $telefon ?: null, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO administrativ_consiliu_director (membru_id, nume_manual, prenume_manual, functie, ordine, email, telefon) VALUES (?,?,?,?,?,?,?)")
        ->execute([$membru_id, $nume_manual ?: null, $prenume_manual ?: null, $functie ?: null, $ordine, $email ?: null, $telefon ?: null]);
    return (int)$pdo->lastInsertId();
}

function administrativ_cd_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_consiliu_director WHERE id = ?")->execute([(int)$id]);
    return true;
}

function administrativ_cd_get(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $s = $pdo->prepare("SELECT c.*, m.nume AS membru_nume, m.prenume AS membru_prenume FROM administrativ_consiliu_director c LEFT JOIN membri m ON m.id = c.membru_id WHERE c.id = ?");
    $s->execute([(int)$id]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

function administrativ_ag_lista(PDO $pdo) {
    administrativ_ensure_tables($pdo);
    return $pdo->query("SELECT a.*, m.nume AS membru_nume, m.prenume AS membru_prenume FROM administrativ_adunare_generala a LEFT JOIN membri m ON m.id = a.membru_id ORDER BY a.ordine, a.id")->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_ag_salveaza(PDO $pdo, $id, $membru_id, $nume_manual, $prenume_manual, $ordine, $functie = null, $email = null, $telefon = null) {
    administrativ_ensure_tables($pdo);
    $membru_id = $membru_id ? (int)$membru_id : null;
    $nume_manual = trim($nume_manual ?? ''); $prenume_manual = trim($prenume_manual ?? '');
    $ordine = (int)$ordine;
    $functie = trim($functie ?? ''); $email = trim($email ?? ''); $telefon = trim($telefon ?? '');
    if ($id > 0) {
        $pdo->prepare("UPDATE administrativ_adunare_generala SET membru_id=?, nume_manual=?, prenume_manual=?, ordine=?, functie=?, email=?, telefon=? WHERE id=?")
            ->execute([$membru_id, $nume_manual ?: null, $prenume_manual ?: null, $ordine, $functie ?: null, $email ?: null, $telefon ?: null, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO administrativ_adunare_generala (membru_id, nume_manual, prenume_manual, ordine, functie, email, telefon) VALUES (?,?,?,?,?,?,?)")
        ->execute([$membru_id, $nume_manual ?: null, $prenume_manual ?: null, $ordine, $functie ?: null, $email ?: null, $telefon ?: null]);
    return (int)$pdo->lastInsertId();
}

function administrativ_ag_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_adunare_generala WHERE id = ?")->execute([(int)$id]);
    return true;
}

function administrativ_ag_get(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $s = $pdo->prepare("SELECT a.*, m.nume AS membru_nume, m.prenume AS membru_prenume FROM administrativ_adunare_generala a LEFT JOIN membri m ON m.id = a.membru_id WHERE a.id = ?");
    $s->execute([(int)$id]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ---- Calendar termene valabilitate ----
function administrativ_calendar_lista(PDO $pdo, $data_start = null, $data_end = null) {
    administrativ_ensure_tables($pdo);
    $sql = "SELECT t.*, a.nume AS angajat_nume, a.prenume AS angajat_prenume FROM administrativ_calendar_termene t LEFT JOIN administrativ_angajati a ON a.id = t.angajat_id WHERE 1=1";
    $params = [];
    if ($data_start) { $sql .= " AND t.data_expirarii >= ?"; $params[] = $data_start; }
    if ($data_end) { $sql .= " AND t.data_inceput <= ?"; $params[] = $data_end; }
    $sql .= " ORDER BY t.data_expirarii ASC, t.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_calendar_salveaza(PDO $pdo, $id, $nume, $data_inceput, $data_expirarii, $tip_document, $observatii = null, $angajat_id = null) {
    administrativ_ensure_tables($pdo);
    $tip = in_array($tip_document, ['hotarare_ag','decizie_cd','medicina_muncii','instructaj_psi_ssm','contract','alt_document']) ? $tip_document : 'alt_document';
    $angajat_id = $angajat_id ? (int)$angajat_id : null;
    if ($id > 0) {
        $pdo->prepare("UPDATE administrativ_calendar_termene SET nume=?, data_inceput=?, data_expirarii=?, tip_document=?, observatii=?, angajat_id=? WHERE id=?")
            ->execute([trim($nume), $data_inceput, $data_expirarii, $tip, $observatii ?: null, $angajat_id, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO administrativ_calendar_termene (nume, data_inceput, data_expirarii, tip_document, observatii, angajat_id) VALUES (?,?,?,?,?,?)")
        ->execute([trim($nume), $data_inceput, $data_expirarii, $tip, $observatii ?: null, $angajat_id]);
    return (int)$pdo->lastInsertId();
}

function administrativ_calendar_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_calendar_termene WHERE id = ?")->execute([(int)$id]);
    return true;
}

// ---- Sedințe CD / AG + activitate în calendar ----
function administrativ_cd_sedinte_lista(PDO $pdo, $data_start = null, $data_end = null) {
    administrativ_ensure_tables($pdo);
    $sql = "SELECT s.* FROM administrativ_cd_sedinte s WHERE 1=1";
    $params = [];
    if ($data_start) { $sql .= " AND s.data_sedinta >= ?"; $params[] = $data_start; }
    if ($data_end) { $sql .= " AND s.data_sedinta <= ?"; $params[] = $data_end; }
    $sql .= " ORDER BY s.data_sedinta ASC, s.ora ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_cd_sedinta_adauga(PDO $pdo, $data_sedinta, $ora, $loc, $creaza_activitate = true) {
    administrativ_ensure_tables($pdo);
    $ora = strlen($ora) === 5 ? $ora . ':00' : $ora;
    $data_ora = $data_sedinta . ' ' . $ora;
    $activitate_id = null;
    if ($creaza_activitate) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activitati (data_ora, nume, locatie, info_suplimentare) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data_ora, 'Sedință Consiliul Director', $loc ?: null, 'Generat din modulul Administrativ']);
            $activitate_id = (int)$pdo->lastInsertId();
        } catch (PDOException $e) {}
    }
    $pdo->prepare("INSERT INTO administrativ_cd_sedinte (data_sedinta, ora, loc, activitate_id) VALUES (?, ?, ?, ?)")
        ->execute([$data_sedinta, $ora, $loc ?: null, $activitate_id]);
    return (int)$pdo->lastInsertId();
}

function administrativ_ag_sedinte_lista(PDO $pdo, $data_start = null, $data_end = null) {
    administrativ_ensure_tables($pdo);
    $sql = "SELECT s.* FROM administrativ_ag_sedinte s WHERE 1=1";
    $params = [];
    if ($data_start) { $sql .= " AND s.data_sedinta >= ?"; $params[] = $data_start; }
    if ($data_end) { $sql .= " AND s.data_sedinta <= ?"; $params[] = $data_end; }
    $sql .= " ORDER BY s.data_sedinta ASC, s.ora ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_ag_sedinta_adauga(PDO $pdo, $data_sedinta, $ora, $loc, $creaza_activitate = true) {
    administrativ_ensure_tables($pdo);
    $ora = strlen($ora) === 5 ? $ora . ':00' : $ora;
    $data_ora = $data_sedinta . ' ' . $ora;
    $activitate_id = null;
    if ($creaza_activitate) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activitati (data_ora, nume, locatie, info_suplimentare) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data_ora, 'Sedință Adunare Generală', $loc ?: null, 'Generat din modulul Administrativ']);
            $activitate_id = (int)$pdo->lastInsertId();
        } catch (PDOException $e) {}
        }
    $pdo->prepare("INSERT INTO administrativ_ag_sedinte (data_sedinta, ora, loc, activitate_id) VALUES (?, ?, ?, ?)")
        ->execute([$data_sedinta, $ora, $loc ?: null, $activitate_id]);
    return (int)$pdo->lastInsertId();
}

// ---- Juridic ANR ----
function administrativ_juridic_lista(PDO $pdo, $categorie = null) {
    administrativ_ensure_tables($pdo);
    $sql = "SELECT * FROM administrativ_juridic_anr WHERE 1=1";
    $params = [];
    if ($categorie && in_array($categorie, ['legislativ','hotarari_anr_agn','decizii_anr_cdn','proiecte','comunicari','altele'])) {
        $sql .= " AND categorie = ?";
        $params[] = $categorie;
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_juridic_adauga(PDO $pdo, $date, $creaza_task = false, $trimite_notificare = false, $user_id = null, $creaza_procedura = false) {
    administrativ_ensure_tables($pdo);
    require_once __DIR__ . '/notificari_helper.php';
    $subiect = trim($date['subiect'] ?? '');
    $categorie = in_array($date['categorie'] ?? '', ['legislativ','hotarari_anr_agn','decizii_anr_cdn','proiecte','comunicari','altele']) ? $date['categorie'] : 'altele';
    $data_doc = trim($date['data_document'] ?? '') ?: null;
    $nr_doc = trim($date['nr_document'] ?? '');
    $continut = $date['continut'] ?? '';
    $task_id = null;
    $notificare_id = null;
    if ($creaza_task && $subiect) {
        try {
            $data_ora = date('Y-m-d') . ' 09:00:00';
            $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
            $has_uid = in_array('utilizator_id', $cols);
            if ($has_uid) {
                $pdo->prepare("INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, ?, ?, 'normal', ?)")->execute([mb_substr($subiect, 0, 255), $data_ora, $continut ? mb_substr(strip_tags($continut), 0, 2000) : null, $user_id]);
            } else {
                $pdo->prepare("INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, ?, ?, 'normal')")->execute([mb_substr($subiect, 0, 255), $data_ora, $continut ? mb_substr(strip_tags($continut), 0, 2000) : null]);
            }
            $task_id = (int)$pdo->lastInsertId();
        } catch (PDOException $e) {}
    }
    if ($trimite_notificare && $subiect) {
        $notif_id = notificari_adauga($pdo, ['titlu' => $subiect, 'importanta' => 'Informativ', 'continut' => $continut ?: '(Fără conținut)', 'trimite_email' => 0], null, $user_id);
        $notificare_id = $notif_id ? $notif_id : null;
    }
    $pdo->prepare("INSERT INTO administrativ_juridic_anr (subiect, categorie, data_document, nr_document, continut, creaza_task_todo, trimite_notificare_platforma, task_id, notificare_id) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$subiect, $categorie, $data_doc, $nr_doc ?: null, $continut, $creaza_task ? 1 : 0, $trimite_notificare ? 1 : 0, $task_id, $notificare_id]);
    if ($creaza_procedura && $subiect) {
        try {
            administrativ_procedura_salveaza($pdo, 0, $subiect, $continut ?: '');
        } catch (PDOException $e) {}
    }
    return (int)$pdo->lastInsertId();
}

// ---- Parteneriate ----
function administrativ_parteneriate_lista(PDO $pdo) {
    administrativ_ensure_tables($pdo);
    return $pdo->query("SELECT * FROM administrativ_parteneriate ORDER BY data_sfarsit IS NULL DESC, data_sfarsit DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_parteneriat_salveaza(PDO $pdo, $id, $nume_partener, $obiect, $data_inceput, $data_sfarsit, $observatii = null) {
    administrativ_ensure_tables($pdo);
    if ($id > 0) {
        $pdo->prepare("UPDATE administrativ_parteneriate SET nume_partener=?, obiect_parteneriat=?, data_inceput=?, data_sfarsit=?, observatii=? WHERE id=?")
            ->execute([trim($nume_partener), $obiect ?: null, $data_inceput ?: null, $data_sfarsit ?: null, $observatii ?: null, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO administrativ_parteneriate (nume_partener, obiect_parteneriat, data_inceput, data_sfarsit, observatii) VALUES (?,?,?,?,?)")
        ->execute([trim($nume_partener), $obiect ?: null, $data_inceput ?: null, $data_sfarsit ?: null, $observatii ?: null]);
    return (int)$pdo->lastInsertId();
}

function administrativ_parteneriat_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_parteneriate WHERE id = ?")->execute([(int)$id]);
    return true;
}

// ---- Proceduri interne ----
function administrativ_proceduri_lista(PDO $pdo, $cautare = null) {
    administrativ_ensure_tables($pdo);
    if ($cautare !== null && $cautare !== '') {
        $stmt = $pdo->prepare("SELECT * FROM administrativ_proceduri WHERE titlu LIKE ? OR continut LIKE ? ORDER BY updated_at DESC");
        $term = '%' . $cautare . '%';
        $stmt->execute([$term, $term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $pdo->query("SELECT * FROM administrativ_proceduri ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}

function administrativ_procedura_salveaza(PDO $pdo, $id, $titlu, $continut) {
    administrativ_ensure_tables($pdo);
    if ($id > 0) {
        $pdo->prepare("UPDATE administrativ_proceduri SET titlu=?, continut=? WHERE id=?")->execute([trim($titlu), $continut ?: null, $id]);
        return $id;
    }
    $pdo->prepare("INSERT INTO administrativ_proceduri (titlu, continut) VALUES (?,?)")->execute([trim($titlu), $continut ?: null]);
    return (int)$pdo->lastInsertId();
}

function administrativ_procedura_sterge(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $pdo->prepare("DELETE FROM administrativ_proceduri WHERE id = ?")->execute([(int)$id]);
    return true;
}

function administrativ_procedura_get(PDO $pdo, $id) {
    administrativ_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM administrativ_proceduri WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Tipuri document calendar (etichete)
function administrativ_tipuri_document_calendar() {
    return [
        'hotarare_ag' => 'Hotarare A.G.',
        'decizie_cd' => 'Decizie C.D.',
        'medicina_muncii' => 'Medicina muncii',
        'instructaj_psi_ssm' => 'Instructaj PSI/SSM',
        'contract' => 'Contract',
        'alt_document' => 'Alt document',
    ];
}

function administrativ_categorii_juridic() {
    return [
        'legislativ' => 'Legislativ',
        'hotarari_anr_agn' => 'Hotărâri ANR AGN',
        'decizii_anr_cdn' => 'Decizii ANR CDN',
        'proiecte' => 'Proiecte',
        'comunicari' => 'Comunicări',
        'altele' => 'Altele',
    ];
}
