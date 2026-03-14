<?php
/**
 * Helper modul Ajutoare BPA - Banca pentru Alimente
 * Evidență stoc Produse BPA (kg), avizuri și tabele de distributie
 */

/**
 * Asigură existența tabelelor BPA
 */
function bpa_ensure_tables(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bpa_gestiune (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nr_document VARCHAR(100) NOT NULL,
        data_document DATE NOT NULL,
        tip_document ENUM('aviz','tabel_distributie') NOT NULL,
        cantitate DECIMAL(12,2) NOT NULL,
        loc_distributie VARCHAR(255) DEFAULT NULL,
        nr_beneficiari INT DEFAULT NULL,
        tabel_distributie_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        utilizator VARCHAR(100) DEFAULT NULL,
        INDEX idx_data (data_document),
        INDEX idx_tip (tip_document),
        INDEX idx_tabel_id (tabel_distributie_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    try {
        $pdo->exec("ALTER TABLE bpa_gestiune MODIFY COLUMN tip_document ENUM('aviz','tabel_distributie','tabel_cristal') NOT NULL");
    } catch (PDOException $e) { /* coloana poate avea deja noul ENUM */ }

    $pdo->exec("CREATE TABLE IF NOT EXISTS bpa_tabele_distributie (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bpa_tabel_distributie_randuri (
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
        INDEX idx_tabel (tabel_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Stoc curent (kg) = sum(aviz) + sum(tabel_distributie/tabel_cristal); avizuri pozitive, tabelele stocate negative
 */
function bpa_stoc_curent(PDO $pdo) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT COALESCE(SUM(cantitate), 0) AS stoc FROM bpa_gestiune");
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float)($r['stoc'] ?? 0);
}

/**
 * Greutate totală preluată (avizuri)
 */
function bpa_total_preluat(PDO $pdo) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT COALESCE(SUM(cantitate), 0) AS total FROM bpa_gestiune WHERE tip_document = 'aviz'");
    return (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

/**
 * Greutate totală distribuită (tabel_distributie + tabel_cristal, cantități stocate negative → returnăm valoare pozitivă)
 */
function bpa_total_distribuit(PDO $pdo) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT COALESCE(SUM(ABS(cantitate)), 0) AS total FROM bpa_gestiune WHERE tip_document IN ('tabel_distributie','tabel_cristal')");
    return (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

/**
 * Număr total pachete distribuite (randuri tabele platformă + nr_beneficiari tabele pe hârtie + nr_beneficiari Tabel Cristal)
 */
function bpa_nr_pachete_distribuite(PDO $pdo) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT COUNT(*) AS n FROM bpa_tabel_distributie_randuri");
    $din_tabele = (int)$stmt->fetch(PDO::FETCH_ASSOC)['n'];
    $stmt = $pdo->query("SELECT COALESCE(SUM(nr_beneficiari), 0) AS n FROM bpa_gestiune WHERE tip_document IN ('tabel_distributie','tabel_cristal') AND nr_beneficiari IS NOT NULL");
    $pe_hartie_si_cristal = (int)$stmt->fetch(PDO::FETCH_ASSOC)['n'];
    return $din_tabele + $pe_hartie_si_cristal;
}

/**
 * Număr beneficiari unici (membri distincți care apar în tabele distributie)
 */
function bpa_nr_beneficiari_unici(PDO $pdo) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT COUNT(DISTINCT membru_id) AS n FROM bpa_tabel_distributie_randuri WHERE membru_id IS NOT NULL");
    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['n'];
}

/**
 * Lista mișcări gestiune (pentru tabel)
 */
function bpa_lista_gestiune(PDO $pdo, $limit = 500) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->prepare("
        SELECT id, nr_document, data_document, tip_document, cantitate, loc_distributie, nr_beneficiari, created_at
        FROM bpa_gestiune
        ORDER BY data_document DESC, id DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Citește o înregistrare din gestiune
 */
function bpa_get_gestiune(PDO $pdo, $id) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT id, nr_document, data_document, tip_document, cantitate, loc_distributie, nr_beneficiari, tabel_distributie_id FROM bpa_gestiune WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Actualizează o înregistrare din gestiune (doar pentru documente nelegate de un tabel creat în platformă, sau orice câmp)
 */
function bpa_update_gestiune(PDO $pdo, $id, $nr_document, $data_document, $tip_document, $cantitate, $loc_distributie = null, $nr_beneficiari = null) {
    bpa_ensure_tables($pdo);
    $cantitate = (float)$cantitate;
    if (in_array($tip_document, ['tabel_distributie', 'tabel_cristal'], true)) {
        $cantitate = -abs($cantitate);
    }
    $stmt = $pdo->prepare("UPDATE bpa_gestiune SET nr_document = ?, data_document = ?, tip_document = ?, cantitate = ?, loc_distributie = ?, nr_beneficiari = ? WHERE id = ?");
    $stmt->execute([
        $nr_document,
        $data_document,
        $tip_document,
        $cantitate,
        $loc_distributie ?: null,
        $nr_beneficiari !== null && $nr_beneficiari !== '' ? (int)$nr_beneficiari : null,
        (int)$id
    ]);
    return $stmt->rowCount() > 0;
}

/**
 * Șterge o înregistrare din gestiune
 */
function bpa_delete_gestiune(PDO $pdo, $id) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->prepare("DELETE FROM bpa_gestiune WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->rowCount() > 0;
}

/**
 * Adaugă document în gestiune (aviz sau tabel pe hârtie)
 * Returnează id sau false
 */
function bpa_adauga_document(PDO $pdo, $nr_document, $data_document, $tip_document, $cantitate, $loc_distributie = null, $nr_beneficiari = null, $utilizator = null) {
    bpa_ensure_tables($pdo);
    $cantitate = (float)$cantitate;
    if (in_array($tip_document, ['tabel_distributie', 'tabel_cristal'], true)) {
        $cantitate = -abs($cantitate); // stocul se scade
    }
    $stmt = $pdo->prepare("
        INSERT INTO bpa_gestiune (nr_document, data_document, tip_document, cantitate, loc_distributie, nr_beneficiari, utilizator)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $nr_document,
        $data_document,
        $tip_document,
        $cantitate,
        $loc_distributie ?: null,
        $nr_beneficiari !== null && $nr_beneficiari !== '' ? (int)$nr_beneficiari : null,
        $utilizator ?: null
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * Salvează tabel distributie (creare sau actualizare) și înregistrează mișcare în gestiune
 * La creare: inserează în bpa_tabele_distributie, bpa_tabel_distributie_randuri, bpa_gestiune (cantitate negativă)
 * La actualizare: actualizează randurile și diferența de cantitate în gestiune
 */
function bpa_salveaza_tabel(PDO $pdo, $tabel_id, $nr_tabel, $data_tabel, $predare_sediul, $predare_centru, $livrare_domiciliu, $randuri, $utilizator = null) {
    bpa_ensure_tables($pdo);
    $cantitate_totala = 0;
    foreach ($randuri as $r) {
        $cantitate_totala += (float)($r['greutate_pachet'] ?? 0);
    }
    $predare_sediul = $predare_sediul ? 1 : 0;
    $predare_centru = $predare_centru ? 1 : 0;
    $livrare_domiciliu = $livrare_domiciliu ? 1 : 0;

    if ($tabel_id > 0) {
        // Actualizare
        $stmt = $pdo->prepare("SELECT cantitate_totala FROM bpa_tabele_distributie WHERE id = ?");
        $stmt->execute([$tabel_id]);
        $existent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existent) return false;
        $cantitate_veche = (float)$existent['cantitate_totala'];
        $diff = $cantitate_totala - $cantitate_veche; // dacă e mai mare, trebuie să scădem mai mult din stoc

        $pdo->prepare("
            UPDATE bpa_tabele_distributie SET nr_tabel=?, data_tabel=?, predare_sediul=?, predare_centru=?, livrare_domiciliu=?, cantitate_totala=?
            WHERE id=?
        ")->execute([$nr_tabel, $data_tabel, $predare_sediul, $predare_centru, $livrare_domiciliu, $cantitate_totala, $tabel_id]);

        $pdo->prepare("DELETE FROM bpa_tabel_distributie_randuri WHERE tabel_id = ?")->execute([$tabel_id]);

        if (abs($diff) > 0.001) {
            // Ajustare gestiune: inserăm o mișcare de corecție sau actualizăm (simplu: inserăm o linie cu -diff dacă diff>0)
            $sem = $diff > 0 ? -1 : 1;
            $pdo->prepare("
                INSERT INTO bpa_gestiune (nr_document, data_document, tip_document, cantitate, tabel_distributie_id, utilizator)
                VALUES (?, ?, 'tabel_distributie', ?, ?, ?)
            ")->execute([$nr_tabel . '-ajust', $data_tabel, $sem * abs($diff), $tabel_id, $utilizator]);
        }
    } else {
        // Creare
        $pdo->prepare("
            INSERT INTO bpa_tabele_distributie (nr_tabel, data_tabel, predare_sediul, predare_centru, livrare_domiciliu, cantitate_totala, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$nr_tabel, $data_tabel, $predare_sediul, $predare_centru, $livrare_domiciliu, $cantitate_totala, $utilizator]);
        $tabel_id = (int)$pdo->lastInsertId();
        $pdo->prepare("
            INSERT INTO bpa_gestiune (nr_document, data_document, tip_document, cantitate, tabel_distributie_id, utilizator)
            VALUES (?, ?, 'tabel_distributie', ?, ?, ?)
        ")->execute([$nr_tabel, $data_tabel, -$cantitate_totala, $tabel_id, $utilizator]);
    }

    $ordine = 0;
    foreach ($randuri as $r) {
        $membru_id = isset($r['membru_id']) && (int)$r['membru_id'] > 0 ? (int)$r['membru_id'] : null;
        $nume_manual = isset($r['nume_manual']) ? trim($r['nume_manual']) : null;
        $prenume_manual = isset($r['prenume_manual']) ? trim($r['prenume_manual']) : null;
        $localitate = isset($r['localitate']) ? trim($r['localitate']) : null;
        $seria_nr_ci = isset($r['seria_nr_ci']) ? trim($r['seria_nr_ci']) : null;
        $data_nastere = !empty($r['data_nastere']) ? $r['data_nastere'] : null;
        $greutate_pachet = (float)($r['greutate_pachet'] ?? 0);
        $semnatura_note = isset($r['semnatura_note']) ? trim($r['semnatura_note']) : null;
        $ordine++;
        $nr_crt = $ordine;
        $pdo->prepare("
            INSERT INTO bpa_tabel_distributie_randuri (tabel_id, nr_crt, membru_id, nume_manual, prenume_manual, localitate, seria_nr_ci, data_nastere, greutate_pachet, semnatura_note, ordine)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$tabel_id, $nr_crt, $membru_id, $nume_manual, $prenume_manual, $localitate, $seria_nr_ci, $data_nastere, $greutate_pachet, $semnatura_note, $ordine]);
    }
    return $tabel_id;
}

/**
 * Detalii tabel distributie + randuri
 */
function bpa_get_tabel(PDO $pdo, $id) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM bpa_tabele_distributie WHERE id = ?");
    $stmt->execute([$id]);
    $tabel = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tabel) return null;
    $stmt = $pdo->prepare("
        SELECT r.*, m.nume, m.prenume, m.datanastere, m.domloc, m.ciseria, m.cinumar
        FROM bpa_tabel_distributie_randuri r
        LEFT JOIN membri m ON r.membru_id = m.id
        WHERE r.tabel_id = ?
        ORDER BY r.ordine
    ");
    $stmt->execute([$id]);
    $tabel['randuri'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $tabel;
}

/**
 * Lista tabele distributie (pentru coloana stânga)
 */
function bpa_lista_tabele(PDO $pdo) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT id, nr_tabel, data_tabel, cantitate_totala, created_at FROM bpa_tabele_distributie ORDER BY data_tabel DESC, id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Indicatorii pentru perioadă (pentru rapoarte)
 */
function bpa_indicatori_perioada(PDO $pdo, $data_inceput = null, $data_sfarsit = null) {
    bpa_ensure_tables($pdo);
    $where = [];
    $params = [];
    if ($data_inceput) {
        $where[] = "data_document >= ?";
        $params[] = $data_inceput;
    }
    if ($data_sfarsit) {
        $where[] = "data_document <= ?";
        $params[] = $data_sfarsit;
    }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tip_document = 'aviz' THEN cantitate ELSE 0 END), 0) AS total_preluat FROM bpa_gestiune" . $sql_where);
    $stmt->execute($params);
    $total_preluat = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total_preluat'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN tip_document = 'tabel_distributie' THEN ABS(cantitate) ELSE 0 END), 0) AS total_distribuit FROM bpa_gestiune" . $sql_where);
    $stmt->execute($params);
    $total_distribuit = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total_distribuit'];

    $where_t = [];
    $params_t = [];
    if ($data_inceput) { $where_t[] = "data_tabel >= ?"; $params_t[] = $data_inceput; }
    if ($data_sfarsit) { $where_t[] = "data_tabel <= ?"; $params_t[] = $data_sfarsit; }
    $sql_where_t = $where_t ? ' WHERE ' . implode(' AND ', $where_t) : '';
    $stmt = $pdo->prepare("SELECT id FROM bpa_tabele_distributie" . $sql_where_t);
    $stmt->execute($params_t);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $nr_beneficiari_unici = 0;
    $nr_pachete = 0;
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT membru_id) AS n FROM bpa_tabel_distributie_randuri WHERE tabel_id IN ($placeholders) AND membru_id IS NOT NULL");
        $stmt->execute($ids);
        $nr_beneficiari_unici = (int)$stmt->fetch(PDO::FETCH_ASSOC)['n'];
        $stmt = $pdo->prepare("SELECT COUNT(*) AS n FROM bpa_tabel_distributie_randuri WHERE tabel_id IN ($placeholders)");
        $stmt->execute($ids);
        $nr_pachete = (int)$stmt->fetch(PDO::FETCH_ASSOC)['n'];
    }
    $sql_extra = $where ? ' AND ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nr_beneficiari), 0) AS n FROM bpa_gestiune WHERE tip_document = 'tabel_distributie' AND nr_beneficiari IS NOT NULL" . $sql_extra);
    $stmt->execute($params);
    $nr_pachete += (int)$stmt->fetch(PDO::FETCH_ASSOC)['n'];

    return [
        'total_preluat' => $total_preluat,
        'total_distribuit' => $total_distribuit,
        'nr_beneficiari_unici' => $nr_beneficiari_unici,
        'nr_pachete' => $nr_pachete,
    ];
}

/**
 * Localități beneficiari (din randuri cu membru sau manual)
 */
function bpa_localitati_beneficiari(PDO $pdo, $data_inceput = null, $data_sfarsit = null) {
    bpa_ensure_tables($pdo);
    $where = [];
    $params = [];
    if ($data_inceput) { $where[] = "t.data_tabel >= ?"; $params[] = $data_inceput; }
    if ($data_sfarsit) { $where[] = "t.data_tabel <= ?"; $params[] = $data_sfarsit; }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("
        SELECT COALESCE(NULLIF(TRIM(r.localitate), ''), m.domloc, 'Nespecificat') AS localitate, COUNT(*) AS nr
        FROM bpa_tabel_distributie_randuri r
        JOIN bpa_tabele_distributie t ON t.id = r.tabel_id
        LEFT JOIN membri m ON m.id = r.membru_id
        $sql_where
        GROUP BY COALESCE(NULLIF(TRIM(r.localitate), ''), m.domloc)
        ORDER BY nr DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Vârste beneficiari (pentru grafic)
 */
function bpa_varste_beneficiari(PDO $pdo, $data_inceput = null, $data_sfarsit = null) {
    bpa_ensure_tables($pdo);
    $where = [];
    $params = [];
    if ($data_inceput) { $where[] = "t.data_tabel >= ?"; $params[] = $data_inceput; }
    if ($data_sfarsit) { $where[] = "t.data_tabel <= ?"; $params[] = $data_sfarsit; }
    $sql_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("
        SELECT r.data_nastere, m.datanastere
        FROM bpa_tabel_distributie_randuri r
        JOIN bpa_tabele_distributie t ON t.id = r.tabel_id
        LEFT JOIN membri m ON m.id = r.membru_id
        $sql_where
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $varste = [];
    $today = new DateTime();
    foreach ($rows as $row) {
        $dn = $row['data_nastere'] ?? $row['datanastere'] ?? null;
        if (!$dn) continue;
        $birth = DateTime::createFromFormat('Y-m-d', $dn);
        if (!$birth) continue;
        $varsta = $today->diff($birth)->y;
        $bucket = $varsta < 18 ? '0-17' : ($varsta < 30 ? '18-29' : ($varsta < 50 ? '30-49' : ($varsta < 65 ? '50-64' : '65+')));
        $varste[$bucket] = ($varste[$bucket] ?? 0) + 1;
    }
    return $varste;
}

/**
 * Sex beneficiari (doar cei cu membru_id)
 */
function bpa_sex_beneficiari(PDO $pdo, $data_inceput = null, $data_sfarsit = null) {
    bpa_ensure_tables($pdo);
    $where = ["r.membru_id IS NOT NULL"];
    $params = [];
    if ($data_inceput) { $where[] = "t.data_tabel >= ?"; $params[] = $data_inceput; }
    if ($data_sfarsit) { $where[] = "t.data_tabel <= ?"; $params[] = $data_sfarsit; }
    $sql_where = ' WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare("
        SELECT m.sex, COUNT(*) AS nr
        FROM bpa_tabel_distributie_randuri r
        JOIN bpa_tabele_distributie t ON t.id = r.tabel_id
        JOIN membri m ON m.id = r.membru_id
        $sql_where
        GROUP BY m.sex
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Evoluție lunară: cantități preluate, distribuite, nr beneficiari (pentru grafic)
 */
function bpa_evolutie_lunara(PDO $pdo, $data_inceput, $data_sfarsit) {
    bpa_ensure_tables($pdo);
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(data_document, '%Y-%m') AS luna,
               SUM(CASE WHEN tip_document = 'aviz' THEN cantitate ELSE 0 END) AS preluat,
               SUM(CASE WHEN tip_document = 'tabel_distributie' THEN ABS(cantitate) ELSE 0 END) AS distribuit
        FROM bpa_gestiune
        WHERE data_document >= ? AND data_document <= ?
        GROUP BY DATE_FORMAT(data_document, '%Y-%m')
        ORDER BY luna
    ");
    $stmt->execute([$data_inceput, $data_sfarsit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
