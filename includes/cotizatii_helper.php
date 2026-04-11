<?php
/**
 * Helper cotizații anuale și scutiri de la plata cotizației - CRM ANR Bihor
 */

function cotizatii_ensure_tables($pdo) {
    // Ensure incremental compatibility for existing installations.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS cotizatii_scutiri (
            id INT AUTO_INCREMENT PRIMARY KEY,
            membru_id INT NOT NULL,
            tip_scutire ENUM('nu', 'temporar', 'permanent') NOT NULL DEFAULT 'temporar',
            data_scutire_de_la DATE DEFAULT NULL,
            data_scutire_pana_la DATE DEFAULT NULL,
            scutire_permanenta TINYINT(1) NOT NULL DEFAULT 0,
            motiv TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_membru_id (membru_id),
            INDEX idx_tip_scutire (tip_scutire),
            INDEX idx_interval_scutire (data_scutire_de_la, data_scutire_pana_la)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $cols = $pdo->query("SHOW COLUMNS FROM cotizatii_scutiri")->fetchAll(PDO::FETCH_ASSOC);
        $existingCols = [];
        foreach ($cols as $c) {
            if (!empty($c['Field'])) {
                $existingCols[(string)$c['Field']] = true;
            }
        }
        if (!isset($existingCols['tip_scutire'])) {
            $pdo->exec("ALTER TABLE cotizatii_scutiri ADD COLUMN tip_scutire ENUM('nu', 'temporar', 'permanent') NOT NULL DEFAULT 'temporar' AFTER membru_id");
            $pdo->exec("UPDATE cotizatii_scutiri SET tip_scutire = CASE WHEN scutire_permanenta = 1 THEN 'permanent' ELSE 'temporar' END");
        }
        if (!isset($existingCols['data_scutire_de_la'])) {
            $pdo->exec("ALTER TABLE cotizatii_scutiri ADD COLUMN data_scutire_de_la DATE DEFAULT NULL AFTER tip_scutire");
            $pdo->exec("UPDATE cotizatii_scutiri SET data_scutire_de_la = CURDATE() WHERE data_scutire_de_la IS NULL");
        }

        $idxRows = $pdo->query("SHOW INDEX FROM cotizatii_scutiri")->fetchAll(PDO::FETCH_ASSOC);
        $idxNames = [];
        foreach ($idxRows as $idx) {
            if (!empty($idx['Key_name'])) {
                $idxNames[(string)$idx['Key_name']] = true;
            }
        }
        if (!isset($idxNames['idx_tip_scutire'])) {
            $pdo->exec("ALTER TABLE cotizatii_scutiri ADD INDEX idx_tip_scutire (tip_scutire)");
        }
        if (!isset($idxNames['idx_interval_scutire'])) {
            $pdo->exec("ALTER TABLE cotizatii_scutiri ADD INDEX idx_interval_scutire (data_scutire_de_la, data_scutire_pana_la)");
        }
    } catch (PDOException $e) {
        // Keep non-blocking behavior for legacy installs.
    }
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
        SELECT s.id, s.membru_id, s.tip_scutire, s.data_scutire_de_la, s.data_scutire_pana_la, s.scutire_permanenta, s.motiv, s.created_at,
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
        SELECT id, membru_id, tip_scutire, data_scutire_de_la, data_scutire_pana_la, scutire_permanenta, motiv
        FROM cotizatii_scutiri
        WHERE membru_id = ?
        AND (
            (tip_scutire = 'permanent')
            OR (
                tip_scutire = 'temporar'
                AND (data_scutire_de_la IS NULL OR data_scutire_de_la <= CURDATE())
                AND (data_scutire_pana_la IS NULL OR data_scutire_pana_la >= CURDATE())
            )
            OR (
                tip_scutire IS NULL
                AND (scutire_permanenta = 1 OR data_scutire_pana_la IS NULL OR data_scutire_pana_la >= CURDATE())
            )
        )
        ORDER BY (tip_scutire = 'permanent') DESC, scutire_permanenta DESC, data_scutire_pana_la DESC
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
        WHERE
            tip_scutire = 'permanent'
            OR (
                tip_scutire = 'temporar'
                AND (data_scutire_de_la IS NULL OR data_scutire_de_la <= CURDATE())
                AND (data_scutire_pana_la IS NULL OR data_scutire_pana_la >= CURDATE())
            )
            OR (
                tip_scutire IS NULL
                AND (scutire_permanenta = 1 OR data_scutire_pana_la IS NULL OR data_scutire_pana_la >= CURDATE())
            )
    ");
    if (!$stmt) return [];
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'membru_id');
}

function cotizatii_get_scutire($pdo, $id) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->prepare("
        SELECT s.id, s.membru_id, s.tip_scutire, s.data_scutire_de_la, s.data_scutire_pana_la, s.scutire_permanenta, s.motiv,
               m.nume, m.prenume
        FROM cotizatii_scutiri s
        LEFT JOIN membri m ON m.id = s.membru_id
        WHERE s.id = ?
    ");
    $stmt->execute([(int) $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function cotizatii_adauga_scutire($pdo, $membru_id, $tip_scutire, $data_de_la, $data_pana_la, $scutire_permanenta, $motiv) {
    cotizatii_ensure_tables($pdo);
    $membru_id = (int) $membru_id;
    if ($membru_id <= 0) return false;
    $tip_scutire = trim((string)$tip_scutire);
    if (!in_array($tip_scutire, ['temporar', 'permanent'], true)) {
        $tip_scutire = $scutire_permanenta ? 'permanent' : 'temporar';
    }
    $scutire_permanenta = ($tip_scutire === 'permanent' || $scutire_permanenta) ? 1 : 0;
    $data_de_la = $tip_scutire === 'temporar' ? ($data_de_la ?: date('Y-m-d')) : null;
    $data_pana_la = $tip_scutire === 'temporar' ? ($data_pana_la ?: null) : null;
    $motiv = trim($motiv) ?: null;
    $stmt = $pdo->prepare("INSERT INTO cotizatii_scutiri (membru_id, tip_scutire, data_scutire_de_la, data_scutire_pana_la, scutire_permanenta, motiv) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$membru_id, $tip_scutire, $data_de_la, $data_pana_la, $scutire_permanenta, $motiv]);
    return (int) $pdo->lastInsertId();
}

function cotizatii_actualizeaza_scutire($pdo, $id, $tip_scutire, $data_de_la, $data_pana_la, $scutire_permanenta, $motiv) {
    cotizatii_ensure_tables($pdo);
    $id = (int) $id;
    if ($id <= 0) return false;
    $tip_scutire = trim((string)$tip_scutire);
    if (!in_array($tip_scutire, ['temporar', 'permanent'], true)) {
        $tip_scutire = $scutire_permanenta ? 'permanent' : 'temporar';
    }
    $scutire_permanenta = ($tip_scutire === 'permanent' || $scutire_permanenta) ? 1 : 0;
    $data_de_la = $tip_scutire === 'temporar' ? ($data_de_la ?: date('Y-m-d')) : null;
    $data_pana_la = $tip_scutire === 'temporar' ? ($data_pana_la ?: null) : null;
    $motiv = trim($motiv) ?: null;
    $stmt = $pdo->prepare("UPDATE cotizatii_scutiri SET tip_scutire=?, data_scutire_de_la=?, data_scutire_pana_la=?, scutire_permanenta=?, motiv=? WHERE id=?");
    $stmt->execute([$tip_scutire, $data_de_la, $data_pana_la, $scutire_permanenta, $motiv, $id]);
    return $stmt->rowCount() >= 0;
}

function cotizatii_get_scutire_membru($pdo, $membru_id) {
    cotizatii_ensure_tables($pdo);
    $stmt = $pdo->prepare("
        SELECT id, membru_id, tip_scutire, data_scutire_de_la, data_scutire_pana_la, scutire_permanenta, motiv
        FROM cotizatii_scutiri
        WHERE membru_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([(int)$membru_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function cotizatii_set_scutire_membru($pdo, $membru_id, $tip_scutire, $data_de_la, $data_pana_la, $motiv) {
    cotizatii_ensure_tables($pdo);
    $membru_id = (int)$membru_id;
    if ($membru_id <= 0) return false;
    $tip_scutire = trim((string)$tip_scutire);
    if ($tip_scutire === 'nu' || $tip_scutire === '') {
        $stmt = $pdo->prepare("DELETE FROM cotizatii_scutiri WHERE membru_id = ?");
        $stmt->execute([$membru_id]);
        return true;
    }
    $is_permanent = $tip_scutire === 'permanent';
    if (!in_array($tip_scutire, ['temporar', 'permanent'], true)) {
        $tip_scutire = 'temporar';
    }
    $data_de_la = $tip_scutire === 'temporar' ? ($data_de_la ?: date('Y-m-d')) : null;
    $data_pana_la = $tip_scutire === 'temporar' ? ($data_pana_la ?: null) : null;
    if ($tip_scutire === 'temporar' && $data_de_la && $data_pana_la && $data_de_la > $data_pana_la) {
        [$data_de_la, $data_pana_la] = [$data_pana_la, $data_de_la];
    }
    $motiv = trim((string)$motiv) ?: null;
    $existing = cotizatii_get_scutire_membru($pdo, $membru_id);
    if ($existing) {
        return cotizatii_actualizeaza_scutire($pdo, (int)$existing['id'], $tip_scutire, $data_de_la, $data_pana_la, $is_permanent, (string)$motiv);
    }
    return cotizatii_adauga_scutire($pdo, $membru_id, $tip_scutire, $data_de_la, $data_pana_la, $is_permanent, (string)$motiv) !== false;
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
    $out = [];
    try {
        $stmt = $pdo->query("SELECT nume FROM cotizatii_opts_grad_handicap ORDER BY ordine, nume");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $n = $row['nume'];
                $out[$n] = $n;
            }
        }
    } catch (PDOException $e) {}
    // Fallback: if opts table is empty or missing, use hardcoded values matching membri.hgrad ENUM
    if (empty($out)) {
        $defaults = ['Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap'];
        foreach ($defaults as $d) {
            $out[$d] = $d;
        }
    }
    return $out;
}

/**
 * Valori posibile pentru asistent personal – din baza de date (cotizatii_opts_asistent_personal).
 * Returnează [ valoare => etichetă ] pentru dropdown.
 */
function cotizatii_asistent_personal_lista($pdo) {
    cotizatii_ensure_tables($pdo);
    $out = [];
    try {
        $stmt = $pdo->query("SELECT nume FROM cotizatii_opts_asistent_personal ORDER BY ordine, id");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $n = $row['nume'];
                $out[$n] = $n;
            }
        }
    } catch (PDOException $e) {}
    // Fallback: if opts table is empty or missing, use hardcoded values
    if (empty($out)) {
        $out = [
            'Cu asistent personal' => 'Cu asistent personal',
            'Fara asistent personal' => 'Fara asistent personal',
        ];
    }
    return $out;
}

/**
 * Mapare din valoarea coloanei insotitor din membri la valoarea asistent_personal din cotizatii_anuale.
 * insotitor din membri: 'INDEMNIZATIE INSOTITOR', 'ASISTENT PERSONAL', 'FARA', 'NESPECIFICAT', '0', null
 * asistent_personal din cotizatii_anuale: 'Cu asistent personal', 'Fara asistent personal'
 */
function cotizatii_map_insotitor_to_asistent($insotitor) {
    $insotitor = strtoupper(trim((string)$insotitor));
    if (in_array($insotitor, ['INDEMNIZATIE INSOTITOR', 'ASISTENT PERSONAL'])) {
        return 'Cu asistent personal';
    }
    return 'Fara asistent personal';
}
