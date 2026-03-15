<?php
/**
 * Helper modul Administrativ - CRM ANR Bihor
 * Achiziții, Echipa, Calendar termene, CD, AG, Juridic ANR, Parteneriate, Proceduri
 */

function administrativ_ensure_tables(PDO $pdo) {
    // No-op: schema is managed by install/schema/migration.php
    return;
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
            $pdo->prepare("INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, ?, ?, 'normal', ?)")->execute([mb_substr($subiect, 0, 255), $data_ora, $continut ? mb_substr(strip_tags($continut), 0, 2000) : null, $user_id]);
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
