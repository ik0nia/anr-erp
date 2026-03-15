<?php
/**
 * Helper modul Voluntariat - nomenclator voluntari, activități, registru ore, integrare Registratură și Contacte
 */

/**
 * Asigură tabelele modulului Voluntariat și setarea mesajului zilei
 */
function voluntariat_ensure_tables(PDO $pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS voluntari (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS voluntariat_activitati (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(255) NOT NULL,
        data_activitate DATE NOT NULL,
        ora_inceput TIME DEFAULT NULL,
        ora_sfarsit TIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_data (data_activitate)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS voluntariat_participanti (
        id INT AUTO_INCREMENT PRIMARY KEY,
        activitate_id INT NOT NULL,
        voluntar_id INT NOT NULL,
        ore_prestate DECIMAL(5,2) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_act_vol (activitate_id, voluntar_id),
        INDEX idx_activitate (activitate_id),
        INDEX idx_voluntar (voluntar_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cheie VARCHAR(100) NOT NULL UNIQUE,
        valoare TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Citește mesajul pentru voluntari (Mesaj Zilei)
 */
function voluntariat_get_mesaj_zilei(PDO $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'voluntariat_mesaj_zilei'");
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r && $r['valoare'] !== null ? trim($r['valoare']) : '';
    } catch (PDOException $e) {
        return '';
    }
}

/**
 * Salvează mesajul pentru voluntari
 */
function voluntariat_set_mesaj_zilei(PDO $pdo, $mesaj) {
    voluntariat_ensure_tables($pdo);
    $mesaj = trim((string)$mesaj);
    $stmt = $pdo->prepare("INSERT INTO setari (cheie, valoare) VALUES ('voluntariat_mesaj_zilei', ?) ON DUPLICATE KEY UPDATE valoare = ?, updated_at = CURRENT_TIMESTAMP");
    $stmt->execute([$mesaj, $mesaj]);
}

/**
 * Șterge mesajul pentru voluntari
 */
function voluntariat_sterge_mesaj_zilei(PDO $pdo) {
    try {
        $pdo->prepare("UPDATE setari SET valoare = '' WHERE cheie = 'voluntariat_mesaj_zilei'")->execute();
    } catch (PDOException $e) {}
}

/**
 * Returnează id-ul template-ului de contract voluntariat din setări (din documente_template)
 */
function voluntariat_get_template_contract_id(PDO $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'voluntariat_template_contract_id'");
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r && $r['valoare'] !== null && $r['valoare'] !== '') {
            return (int)$r['valoare'];
        }
    } catch (PDOException $e) {}
    return null;
}

/**
 * Listează toți voluntarii
 */
function voluntariat_lista_voluntari(PDO $pdo) {
    voluntariat_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT * FROM voluntari ORDER BY nume, prenume");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Un singur voluntar după id
 */
function voluntariat_get_voluntar(PDO $pdo, $id) {
    $id = (int)$id;
    if ($id <= 0) return null;
    $stmt = $pdo->prepare("SELECT * FROM voluntari WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

/**
 * Creează contact Voluntar în modulul Contacte și returnează contact_id
 */
function voluntariat_creare_contact_voluntar(PDO $pdo, array $voluntar) {
    if (!function_exists('ensure_contacte_table')) {
        require_once __DIR__ . '/contacte_helper.php';
    }
    ensure_contacte_table($pdo);
    $nume = trim($voluntar['nume'] ?? '');
    if ($nume === '') return null;
    $prenume = trim($voluntar['prenume'] ?? '') ?: null;
    $cnp = trim($voluntar['cnp'] ?? '') ?: null;
    $telefon = trim($voluntar['telefon'] ?? '') ?: null;
    $email = trim($voluntar['email'] ?? '') ?: null;
    $data_nasterii = $cnp && function_exists('contacte_data_nasterii_din_cnp') ? contacte_data_nasterii_din_cnp($cnp) : null;
    $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii) VALUES (?, ?, ?, 'Voluntar', ?, ?, ?)");
    $stmt->execute([$nume, $prenume, $cnp, $telefon, $email, $data_nasterii]);
    return (int)$pdo->lastInsertId();
}

/**
 * Salvează voluntar nou: generează nr registratură, contract (dacă template setat), creează contact Voluntari, inserează în voluntari.
 * Returnează ['success'=>bool, 'id'=>int, 'error'=>string]
 */
function voluntariat_adauga_voluntar(PDO $pdo, array $date) {
    voluntariat_ensure_tables($pdo);
    if (!function_exists('registratura_urmatorul_nr')) require_once __DIR__ . '/registratura_helper.php';
    if (!function_exists('registratura_inregistreaza_document')) require_once __DIR__ . '/registratura_helper.php';
    if (!function_exists('log_activitate')) require_once __DIR__ . '/log_helper.php';

    $nume = trim($date['nume'] ?? '');
    if ($nume === '') {
        return ['success' => false, 'id' => 0, 'error' => 'Numele este obligatoriu.'];
    }

    $nr_intern = registratura_urmatorul_nr($pdo);
    $nr_registratura = (string)$nr_intern;

    $contract_path = null;
    $template_id = voluntariat_get_template_contract_id($pdo);
    if ($template_id) {
        require_once __DIR__ . '/document_helper.php';
        $voluntar_fake = [
            'nume' => $nume,
            'prenume' => trim($date['prenume'] ?? ''),
            'cnp' => trim($date['cnp'] ?? ''),
            'seria_ci' => trim($date['seria_ci'] ?? ''),
            'nr_ci' => trim($date['nr_ci'] ?? ''),
            'codpost' => trim($date['codpost'] ?? ''),
            'domloc' => trim($date['domloc'] ?? ''),
            'judet_domiciliu' => trim($date['judet_domiciliu'] ?? ''),
            'domstr' => trim($date['domstr'] ?? ''),
            'domnr' => trim($date['domnr'] ?? ''),
            'dombl' => trim($date['dombl'] ?? ''),
            'domsc' => trim($date['domsc'] ?? ''),
            'domet' => trim($date['domet'] ?? ''),
            'domap' => trim($date['domap'] ?? ''),
            'telefon' => trim($date['telefon'] ?? ''),
            'email' => trim($date['email'] ?? ''),
        ];
        $gen = @genereaza_document_voluntar_contract($pdo, $template_id, $voluntar_fake, $nr_registratura);
        if (is_array($gen) && !empty($gen['success']) && !empty($gen['filename'])) {
            $contract_path = $gen['filename'];
            $reg = registratura_inregistreaza_document($pdo, [
                'nr_intern' => $nr_intern,
                'tip_act' => 'Contract voluntariat',
                'detalii' => 'Contract voluntariat: ' . $nume . ' ' . trim($date['prenume'] ?? ''),
                'nr_document' => $nr_registratura,
                'data_document' => date('Y-m-d'),
                'continut_document' => 'Contract voluntariat nr. ' . $nr_registratura,
                'destinatar_document' => $nume . ' ' . trim($date['prenume'] ?? ''),
                'document_path' => $contract_path,
            ]);
            if (!empty($reg['success'])) {
                log_activitate($pdo, 'Voluntariat: Contract înregistrat în registratură nr. ' . $nr_registratura);
            }
        }
    } else {
        $reg = registratura_inregistreaza_document($pdo, [
            'nr_intern' => $nr_intern,
            'tip_act' => 'Voluntar înregistrat',
            'detalii' => 'Voluntar: ' . $nume . ' ' . trim($date['prenume'] ?? ''),
            'nr_document' => $nr_registratura,
            'data_document' => date('Y-m-d'),
            'continut_document' => 'Înregistrare voluntar nr. ' . $nr_registratura,
        ]);
    }

    $contact_id = voluntariat_creare_contact_voluntar($pdo, $date);
    if ($contact_id) {
        log_activitate($pdo, 'Voluntariat: Contact Voluntar creat în modulul Contacte (ID ' . $contact_id . ')');
    }

    $stmt = $pdo->prepare("INSERT INTO voluntari (nume, prenume, cnp, seria_ci, nr_ci, codpost, domloc, judet_domiciliu, domstr, domnr, dombl, domsc, domet, domap, telefon, email, contact_id, nr_registratura, contract_path) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $nume,
        trim($date['prenume'] ?? '') ?: null,
        trim($date['cnp'] ?? '') ?: null,
        trim($date['seria_ci'] ?? '') ?: null,
        trim($date['nr_ci'] ?? '') ?: null,
        trim($date['codpost'] ?? '') ?: null,
        trim($date['domloc'] ?? '') ?: null,
        trim($date['judet_domiciliu'] ?? '') ?: null,
        trim($date['domstr'] ?? '') ?: null,
        trim($date['domnr'] ?? '') ?: null,
        trim($date['dombl'] ?? '') ?: null,
        trim($date['domsc'] ?? '') ?: null,
        trim($date['domet'] ?? '') ?: null,
        trim($date['domap'] ?? '') ?: null,
        trim($date['telefon'] ?? '') ?: null,
        trim($date['email'] ?? '') ?: null,
        $contact_id ?: null,
        $nr_registratura,
        $contract_path,
    ]);
    $id = (int)$pdo->lastInsertId();
    log_activitate($pdo, 'Voluntariat: Voluntar adăugat - ' . $nume . ' ' . trim($date['prenume'] ?? '') . ' (nr. ' . $nr_registratura . ')');
    return ['success' => true, 'id' => $id, 'error' => null];
}

/**
 * Actualizează voluntar existent (fără regenerare contract)
 */
function voluntariat_actualizeaza_voluntar(PDO $pdo, $id, array $date) {
    $id = (int)$id;
    if ($id <= 0) return false;
    voluntariat_ensure_tables($pdo);
    $nume = trim($date['nume'] ?? '');
    if ($nume === '') return false;

    $stmt = $pdo->prepare("UPDATE voluntari SET nume=?, prenume=?, cnp=?, seria_ci=?, nr_ci=?, codpost=?, domloc=?, judet_domiciliu=?, domstr=?, domnr=?, dombl=?, domsc=?, domet=?, domap=?, telefon=?, email=? WHERE id=?");
    $stmt->execute([
        $nume,
        trim($date['prenume'] ?? '') ?: null,
        trim($date['cnp'] ?? '') ?: null,
        trim($date['seria_ci'] ?? '') ?: null,
        trim($date['nr_ci'] ?? '') ?: null,
        trim($date['codpost'] ?? '') ?: null,
        trim($date['domloc'] ?? '') ?: null,
        trim($date['judet_domiciliu'] ?? '') ?: null,
        trim($date['domstr'] ?? '') ?: null,
        trim($date['domnr'] ?? '') ?: null,
        trim($date['dombl'] ?? '') ?: null,
        trim($date['domsc'] ?? '') ?: null,
        trim($date['domet'] ?? '') ?: null,
        trim($date['domap'] ?? '') ?: null,
        trim($date['telefon'] ?? '') ?: null,
        trim($date['email'] ?? '') ?: null,
        $id,
    ]);

    if (!empty($date['contact_id'])) {
        $cid = (int)$date['contact_id'];
        $stmt2 = $pdo->prepare("UPDATE contacte SET nume=?, prenume=?, cnp=?, telefon=?, email=?, data_nasterii=? WHERE id=?");
        $data_nasterii = null;
        if (!empty($date['cnp']) && function_exists('contacte_data_nasterii_din_cnp')) {
            $data_nasterii = contacte_data_nasterii_din_cnp($date['cnp']);
        }
        $stmt2->execute([$nume, trim($date['prenume'] ?? '') ?: null, trim($date['cnp'] ?? '') ?: null, trim($date['telefon'] ?? '') ?: null, trim($date['email'] ?? '') ?: null, $data_nasterii, $cid]);
    }
    return true;
}

/**
 * Listează activitățile de voluntariat
 */
function voluntariat_lista_activitati(PDO $pdo) {
    voluntariat_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT * FROM voluntariat_activitati ORDER BY data_activitate DESC, nume");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Adaugă activitate
 */
function voluntariat_adauga_activitate(PDO $pdo, $nume, $data_activitate, $ora_inceput = null, $ora_sfarsit = null) {
    voluntariat_ensure_tables($pdo);
    $nume = trim($nume);
    if ($nume === '') return null;
    $stmt = $pdo->prepare("INSERT INTO voluntariat_activitati (nume, data_activitate, ora_inceput, ora_sfarsit) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nume, $data_activitate, $ora_inceput ?: null, $ora_sfarsit ?: null]);
    return (int)$pdo->lastInsertId();
}

/**
 * Asociază voluntar la activitate (cu opțional ore prestate)
 */
function voluntariat_adauga_participant(PDO $pdo, $activitate_id, $voluntar_id, $ore_prestate = null) {
    $activitate_id = (int)$activitate_id;
    $voluntar_id = (int)$voluntar_id;
    if ($activitate_id <= 0 || $voluntar_id <= 0) return false;
    try {
        $stmt = $pdo->prepare("INSERT INTO voluntariat_participanti (activitate_id, voluntar_id, ore_prestate) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ore_prestate = COALESCE(VALUES(ore_prestate), ore_prestate)");
        $stmt->execute([$activitate_id, $voluntar_id, $ore_prestate]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Participanții unei activități (cu nume voluntar, ore)
 */
function voluntariat_get_participanti(PDO $pdo, $activitate_id) {
    $activitate_id = (int)$activitate_id;
    if ($activitate_id <= 0) return [];
    $stmt = $pdo->prepare("SELECT p.id, p.voluntar_id, p.ore_prestate, v.nume, v.prenume, v.telefon, v.email FROM voluntariat_participanti p JOIN voluntari v ON v.id = p.voluntar_id WHERE p.activitate_id = ? ORDER BY v.nume, v.prenume");
    $stmt->execute([$activitate_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Registru ore: toate înregistrările activitate – voluntar – ore (pentru adeverințe)
 */
function voluntariat_registru_ore(PDO $pdo) {
    voluntariat_ensure_tables($pdo);
    $stmt = $pdo->query("
        SELECT p.id, p.activitate_id, p.voluntar_id, p.ore_prestate, p.created_at,
               a.nume AS activitate_nume, a.data_activitate, a.ora_inceput, a.ora_sfarsit,
               v.nume AS voluntar_nume, v.prenume AS voluntar_prenume, v.telefon, v.email
        FROM voluntariat_participanti p
        JOIN voluntariat_activitati a ON a.id = p.activitate_id
        JOIN voluntari v ON v.id = p.voluntar_id
        ORDER BY a.data_activitate DESC, v.nume, v.prenume
    ");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * O activitate după id
 */
function voluntariat_get_activitate(PDO $pdo, $id) {
    $id = (int)$id;
    if ($id <= 0) return null;
    $stmt = $pdo->prepare("SELECT * FROM voluntariat_activitati WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}
