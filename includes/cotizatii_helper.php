<?php
/**
 * Helper cotizații anuale și scutiri de la plata cotizației - CRM ANR Bihor
 */

function cotizatii_ensure_tables($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS cotizatii_opts_grad_handicap (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        ordine INT NOT NULL DEFAULT 0,
        UNIQUE KEY uk_nume (nume)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cotizatii_opts_asistent_personal (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        ordine INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cotizatii_anuale (
        id INT AUTO_INCREMENT PRIMARY KEY,
        anul SMALLINT UNSIGNED NOT NULL,
        grad_handicap VARCHAR(50) NOT NULL,
        asistent_personal VARCHAR(100) NOT NULL DEFAULT '',
        valoare_cotizatie DECIMAL(10,2) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_anul_grad_asistent (anul, grad_handicap, asistent_personal),
        INDEX idx_anul (anul)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->query("SHOW COLUMNS FROM cotizatii_anuale LIKE 'asistent_personal'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE cotizatii_anuale ADD COLUMN asistent_personal VARCHAR(100) NOT NULL DEFAULT '' AFTER grad_handicap");
        try {
            $pdo->exec("ALTER TABLE cotizatii_anuale DROP INDEX uk_anul_grad");
        } catch (PDOException $e) {}
        $pdo->exec("ALTER TABLE cotizatii_anuale ADD UNIQUE KEY uk_anul_grad_asistent (anul, grad_handicap, asistent_personal)");
    }

    $n = $pdo->query("SELECT COUNT(*) FROM cotizatii_opts_grad_handicap")->fetchColumn();
    if ((int)$n === 0) {
        $seed = [
            'Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap'
        ];
        $stmt = $pdo->prepare("INSERT INTO cotizatii_opts_grad_handicap (nume, ordine) VALUES (?, ?)");
        foreach ($seed as $i => $nume) {
            $stmt->execute([$nume, $i]);
        }
    }

    $n = $pdo->query("SELECT COUNT(*) FROM cotizatii_opts_asistent_personal")->fetchColumn();
    if ((int)$n === 0) {
        $pdo->exec("INSERT INTO cotizatii_opts_asistent_personal (nume, ordine) VALUES ('Cu asistent personal', 0), ('Fara asistent personal', 1)");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS cotizatii_scutiri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        membru_id INT NOT NULL,
        data_scutire_pana_la DATE DEFAULT NULL,
        scutire_permanenta TINYINT(1) NOT NULL DEFAULT 0,
        motiv TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE CASCADE,
        INDEX idx_membru (membru_id),
        INDEX idx_scutire_activa (membru_id, scutire_permanenta, data_scutire_pana_la)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Returnează lista cotizațiilor anuale (anul, grad_handicap, asistent_personal, valoare).
 */
function cotizatii_lista_anuale($pdo) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT id, anul, grad_handicap, asistent_personal, valoare_cotizatie FROM cotizatii_anuale ORDER BY anul DESC, grad_handicap, asistent_personal");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Adaugă sau actualizează o cotizație anuală. Returnează id la succes.
 * $asistent_personal: valoare din dropdown (ex. "Cu asistent personal", "Fara asistent personal").
 */
function cotizatii_salveaza_anuala($pdo, $id, $anul, $grad_handicap, $asistent_personal, $valoare) {
    cotizatii_ensure_tables($pdo);
    $anul = (int) $anul;
    $valoare = (float) str_replace(',', '.', $valoare);
    $grad_handicap = trim($grad_handicap);
    $asistent_personal = trim((string) $asistent_personal);
    if ($anul < 1900 || $anul > 2100 || $grad_handicap === '' || $valoare < 0) {
        return false;
    }
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE cotizatii_anuale SET anul=?, grad_handicap=?, asistent_personal=?, valoare_cotizatie=? WHERE id=?");
        $stmt->execute([$anul, $grad_handicap, $asistent_personal, $valoare, $id]);
        return $id;
    }
    $stmt = $pdo->prepare("INSERT INTO cotizatii_anuale (anul, grad_handicap, asistent_personal, valoare_cotizatie) VALUES (?, ?, ?, ?)");
    $stmt->execute([$anul, $grad_handicap, $asistent_personal, $valoare]);
    return (int) $pdo->lastInsertId();
}

function cotizatii_sterge_anuala($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM cotizatii_anuale WHERE id = ?");
    $stmt->execute([(int) $id]);
    return $stmt->rowCount() > 0;
}

function cotizatii_get_anuala($pdo, $id) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT id, anul, grad_handicap, asistent_personal, valoare_cotizatie FROM cotizatii_anuale WHERE id = ?");
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !array_key_exists('asistent_personal', $row)) {
        $row['asistent_personal'] = '';
    }
    return $row ?: null;
}

/**
 * Lista scutiri cu nume membru (join membri).
 */
function cotizatii_lista_scutiri($pdo) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->query("
        SELECT s.id, s.membru_id, s.data_scutire_pana_la, s.scutire_permanenta, s.motiv, s.created_at,
               m.nume, m.prenume
        FROM cotizatii_scutiri s
        LEFT JOIN membri m ON m.id = s.membru_id
        ORDER BY s.created_at DESC
    ");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Verifică dacă un membru este în prezent scutit (scutire permanentă sau data_scutire_pana_la >= azi).
 * Returnează rândul scutirii active sau null.
 */
function cotizatii_membru_este_scutit($pdo, $membru_id) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->prepare("
        SELECT id, membru_id, data_scutire_pana_la, scutire_permanenta, motiv
        FROM cotizatii_scutiri
        WHERE membru_id = ?
        AND (scutire_permanenta = 1 OR data_scutire_pana_la IS NULL OR data_scutire_pana_la >= CURDATE())
        ORDER BY scutire_permanenta DESC, data_scutire_pana_la DESC
        LIMIT 1
    ");
    $stmt->execute([(int) $membru_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Returnează ID-uri membri care sunt în prezent scutiți (pentru listă membri).
 */
function cotizatii_membri_scutiti_ids($pdo) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->query("
        SELECT DISTINCT membru_id FROM cotizatii_scutiri
        WHERE scutire_permanenta = 1 OR data_scutire_pana_la IS NULL OR data_scutire_pana_la >= CURDATE()
    ");
    if (!$stmt) return [];
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'membru_id');
}

function cotizatii_get_scutire($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.membru_id, s.data_scutire_pana_la, s.scutire_permanenta, s.motiv,
               m.nume, m.prenume
        FROM cotizatii_scutiri s
        LEFT JOIN membri m ON m.id = s.membru_id
        WHERE s.id = ?
    ");
    $stmt->execute([(int) $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function cotizatii_adauga_scutire($pdo, $membru_id, $data_pana_la, $scutire_permanenta, $motiv) {
    cotizatii_ensure_tables($pdo);
    $membru_id = (int) $membru_id;
    if ($membru_id <= 0) return false;
    $scutire_permanenta = $scutire_permanenta ? 1 : 0;
    $data_pana_la = $scutire_permanenta ? null : ($data_pana_la ?: null);
    $motiv = trim($motiv) ?: null;
    $stmt = $pdo->prepare("INSERT INTO cotizatii_scutiri (membru_id, data_scutire_pana_la, scutire_permanenta, motiv) VALUES (?, ?, ?, ?)");
    $stmt->execute([$membru_id, $data_pana_la, $scutire_permanenta, $motiv]);
    return (int) $pdo->lastInsertId();
}

function cotizatii_actualizeaza_scutire($pdo, $id, $data_pana_la, $scutire_permanenta, $motiv) {
    $id = (int) $id;
    if ($id <= 0) return false;
    $scutire_permanenta = $scutire_permanenta ? 1 : 0;
    $data_pana_la = $scutire_permanenta ? null : ($data_pana_la ?: null);
    $motiv = trim($motiv) ?: null;
    $stmt = $pdo->prepare("UPDATE cotizatii_scutiri SET data_scutire_pana_la=?, scutire_permanenta=?, motiv=? WHERE id=?");
    $stmt->execute([$data_pana_la, $scutire_permanenta, $motiv, $id]);
    return $stmt->rowCount() >= 0;
}

function cotizatii_sterge_scutire($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM cotizatii_scutiri WHERE id = ?");
    $stmt->execute([(int) $id]);
    return $stmt->rowCount() > 0;
}

/**
 * Valori posibile pentru grad handicap – din baza de date (cotizatii_opts_grad_handicap).
 * Returnează [ valoare => etichetă ] pentru dropdown.
 */
function cotizatii_graduri_handicap($pdo) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT nume FROM cotizatii_opts_grad_handicap ORDER BY ordine, nume");
    if (!$stmt) return [];
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $n = $row['nume'];
        $out[$n] = $n;
    }
    return $out;
}

/**
 * Valori posibile pentru asistent personal – din baza de date (cotizatii_opts_asistent_personal).
 * Returnează [ valoare => etichetă ] pentru dropdown.
 */
function cotizatii_asistent_personal_lista($pdo) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT nume FROM cotizatii_opts_asistent_personal ORDER BY ordine, id");
    if (!$stmt) return [];
    $out = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $n = $row['nume'];
        $out[$n] = $n;
    }
    return $out;
}
