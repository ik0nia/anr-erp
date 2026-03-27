<?php
/**
 * ListePrezentaService — Business logic pentru module Liste Prezenta.
 *
 * Gestioneaza CRUD liste prezenta, participanti, migrari schema.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/liste_helper.php';
require_once APP_ROOT . '/includes/registratura_helper.php';

/**
 * Incarca lista activitatilor pentru selectul din formular.
 */
function liste_prezenta_activitati_select(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, data_ora, nume FROM activitati ORDER BY data_ora DESC LIMIT 100");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * No-op: schema is managed by install/schema/migration.php
 */
function liste_prezenta_ensure_ora_finalizare(PDO $pdo): void {
    return;
}

/**
 * No-op: schema is managed by install/schema/migration.php
 */
function liste_prezenta_ensure_nume_manual(PDO $pdo): void {
    return;
}

/**
 * Asigură compatibilitatea tabelei liste_prezenta_membri pentru participanți non-membru:
 * - coloană opțională contact_id (pentru participanți din Contacte)
 * - membru_id nullable (permite manual/contact fără FK membru)
 */
function liste_prezenta_ensure_contact_support(PDO $pdo): void {
    try {
        $colsRaw = $pdo->query('SHOW COLUMNS FROM liste_prezenta_membri')->fetchAll(PDO::FETCH_ASSOC);
        if (!$colsRaw) {
            return;
        }
        $cols = [];
        foreach ($colsRaw as $c) {
            if (!empty($c['Field'])) {
                $cols[(string)$c['Field']] = $c;
            }
        }

        if (!isset($cols['contact_id'])) {
            $pdo->exec('ALTER TABLE liste_prezenta_membri ADD COLUMN contact_id INT NULL AFTER membru_id');
            try {
                $pdo->exec('CREATE INDEX idx_lpm_contact_id ON liste_prezenta_membri(contact_id)');
            } catch (PDOException $e) {
                // index existent / drepturi insuficiente - non critic
            }
        }

        if (isset($cols['membru_id']) && strtoupper((string)($cols['membru_id']['Null'] ?? 'YES')) === 'NO') {
            $pdo->exec('ALTER TABLE liste_prezenta_membri MODIFY membru_id INT NULL');
        }
    } catch (PDOException $e) {
        // Migrarea este best-effort; eventualele erori vor fi expuse la insert, dar nu blocăm pagina aici.
    }
}

/**
 * Asigura coloana pentru numarul alocat din Registratura.
 */
function liste_prezenta_ensure_registratura_support(PDO $pdo): void {
    try {
        $colsRaw = $pdo->query('SHOW COLUMNS FROM liste_prezenta')->fetchAll(PDO::FETCH_ASSOC);
        if (!$colsRaw) {
            return;
        }

        $cols = [];
        foreach ($colsRaw as $c) {
            if (!empty($c['Field'])) {
                $cols[(string)$c['Field']] = $c;
            }
        }

        if (!isset($cols['nr_registratura'])) {
            $pdo->exec('ALTER TABLE liste_prezenta ADD COLUMN nr_registratura VARCHAR(50) NULL AFTER data_lista');
            try {
                $pdo->exec('CREATE INDEX idx_liste_prezenta_nr_registratura ON liste_prezenta(nr_registratura)');
            } catch (PDOException $e) {
                // index existent / drepturi insuficiente - non critic
            }
        }
    } catch (PDOException $e) {
        // Migrare best-effort; nu blocam pagina daca schema nu poate fi alterata acum.
    }
}

/**
 * Parseaza si valideaza datele POST comune (create/edit).
 */
function liste_prezenta_parse_post(array $post): array {
    $tip_titlu = $post['tip_titlu'] ?? 'Lista prezenta';
    $detalii_activitate = trim($post['detalii_activitate'] ?? '');
    $data_lista = trim($post['data_lista'] ?? '');
    $detalii_sus = trim($post['detalii_suplimentare_sus'] ?? '');
    $detalii_jos = trim($post['detalii_suplimentare_jos'] ?? '');
    $coloane = $post['coloane'] ?? [];

    $membri_ids_raw = json_decode($post['membri_ids'] ?? '[]', true) ?: [];
    $membri_ids = [];
    foreach ($membri_ids_raw as $mid) {
        $mid_int = (int)$mid;
        if ($mid_int > 0) {
            $membri_ids[] = $mid_int;
        }
    }

    $contacte_ids_raw = json_decode($post['contacte_ids'] ?? '[]', true) ?: [];
    $contacte_ids = [];
    foreach ($contacte_ids_raw as $cid) {
        $cid_int = (int)$cid;
        if ($cid_int > 0) {
            $contacte_ids[] = $cid_int;
        }
    }

    // Participanti manuali (fără membru/contact id)
    $participanti_manuali_raw = json_decode($post['participanti_manuali'] ?? '[]', true) ?: [];
    $participanti_manuali = [];
    foreach ($participanti_manuali_raw as $pm) {
        if (!empty($pm['nume']) && is_string($pm['nume'])) {
            $participanti_manuali[] = [
                'nume' => trim($pm['nume']),
                'ordine' => isset($pm['ordine']) ? (int)$pm['ordine'] : 0
            ];
        }
    }

    $semn_st_n = trim($post['semn_stanga_nume'] ?? '');
    $semn_st_f = trim($post['semn_stanga_functie'] ?? '');
    $semn_c_n = trim($post['semn_centru_nume'] ?? '');
    $semn_c_f = trim($post['semn_centru_functie'] ?? '');
    $semn_d_n = trim($post['semn_dreapta_nume'] ?? '');
    $semn_d_f = trim($post['semn_dreapta_functie'] ?? '');

    return compact(
        'tip_titlu', 'detalii_activitate', 'data_lista', 'detalii_sus', 'detalii_jos',
        'coloane', 'membri_ids', 'contacte_ids', 'participanti_manuali',
        'semn_st_n', 'semn_st_f', 'semn_c_n', 'semn_c_f', 'semn_d_n', 'semn_d_f'
    );
}

/**
 * Salveaza membrii si participantii manuali pentru o lista.
 */
function liste_prezenta_save_membri(PDO $pdo, int $lista_id, array $membri_ids, array $contacte_ids, array $participanti_manuali): void {
    liste_prezenta_ensure_nume_manual($pdo);
    liste_prezenta_ensure_contact_support($pdo);

    $stmt_m = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, contact_id, ordine, nume_manual) VALUES (?, ?, NULL, ?, NULL)');
    $ordine_curenta = 1;
    foreach (array_values($membri_ids) as $mid) {
        if ($mid) {
            $stmt_m->execute([$lista_id, $mid, $ordine_curenta]);
            $ordine_curenta++;
        }
    }

    $stmt_c = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, contact_id, ordine, nume_manual) VALUES (?, NULL, ?, ?, NULL)');
    if (!empty($contacte_ids)) {
        $in = implode(',', array_fill(0, count($contacte_ids), '?'));
        $stmt_contacte = $pdo->prepare("SELECT id, nume, prenume FROM contacte WHERE id IN ($in)");
        $stmt_contacte->execute(array_values($contacte_ids));
        $mapContacte = [];
        foreach ($stmt_contacte->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $mapContacte[(int)$c['id']] = trim((string)($c['nume'] ?? '') . ' ' . (string)($c['prenume'] ?? ''));
        }
        foreach (array_values($contacte_ids) as $cid) {
            if (!isset($mapContacte[(int)$cid])) {
                continue;
            }
            $stmt_c->execute([$lista_id, (int)$cid, $ordine_curenta]);
            $ordine_curenta++;
        }
    }

    $stmt_m_manual = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, contact_id, ordine, nume_manual) VALUES (?, NULL, NULL, ?, ?)');
    foreach ($participanti_manuali as $pm) {
        $stmt_m_manual->execute([$lista_id, $ordine_curenta, $pm['nume']]);
        $ordine_curenta++;
    }
}

/**
 * Creaza o lista de prezenta.
 *
 * @return array ['success' => bool, 'error' => string, 'lista_id' => int|null, 'activitate_id' => int|null]
 */
function liste_prezenta_create(PDO $pdo, array $post, string $user): array {
    $d = liste_prezenta_parse_post($post);

    if (empty($d['data_lista'])) {
        return ['success' => false, 'error' => 'Data listei este obligatorie.', 'lista_id' => null, 'activitate_id' => null];
    }

    try {
        liste_prezenta_ensure_registratura_support($pdo);
        $started_tx = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $started_tx = true;
        }

        $activitate_id = !empty($post['activitate_id']) ? (int)$post['activitate_id'] : null;
        if ($activitate_id !== null && $activitate_id <= 0) {
            $activitate_id = null;
        }
        $creaza_activitate = !empty($post['creaza_activitate']);
        if ($creaza_activitate && empty($post['activitate_nume'])) {
            $post['activitate_nume'] = !empty(trim($post['detalii_activitate'] ?? '')) ? trim($post['detalii_activitate']) : ($post['tip_titlu'] ?? 'Lista prezenta');
            $post['activitate_data'] = $post['data_lista'] ?? '';
            $post['activitate_ora'] = $post['ora_lista'] ?? '09:00';
        }

        // Creare automata activitate daca exista data si ora
        if (!empty($d['data_lista'])) {
            $ora_lista = trim($post['ora_lista'] ?? '09:00');
            if (strlen($ora_lista) === 5) {
                $ora_lista .= ':00';
            }
            $ora_finalizare = trim($post['ora_finalizare'] ?? '');
            if (!empty($ora_finalizare) && strlen($ora_finalizare) === 5) {
                $ora_finalizare .= ':00';
            }
            $data_ora_activitate = $d['data_lista'] . ' ' . $ora_lista;
            $act_nume = ($d['detalii_activitate'] ? 'Activitate: ' . mb_substr($d['detalii_activitate'], 0, 100) : $d['tip_titlu']);
            $act_info = 'Activitate Generata automat din Lista de Participare';

            liste_prezenta_ensure_ora_finalizare($pdo);

            // Creaza activitate doar daca utilizatorul a bifat
            if ($creaza_activitate && !$activitate_id) {
                $act_nume = trim($post['activitate_nume'] ?? '') ?: ($d['detalii_activitate'] ?: $d['tip_titlu']);
                $act_data = trim($post['activitate_data'] ?? $d['data_lista']);
                $act_ora = trim($post['activitate_ora'] ?? '09:00');
                if (strlen($act_ora) === 5) $act_ora .= ':00';
                $data_ora_activitate = $act_data . ' ' . $act_ora;
                if (!empty($ora_finalizare)) {
                    $stmt_act = $pdo->prepare('INSERT INTO activitati (data_ora, ora_finalizare, nume, responsabili, info_suplimentare) VALUES (?,?,?,?,?)');
                    $stmt_act->execute([$data_ora_activitate, $ora_finalizare, $act_nume, $user, $act_info]);
                } else {
                    $stmt_act = $pdo->prepare('INSERT INTO activitati (data_ora, nume, responsabili, info_suplimentare) VALUES (?,?,?,?)');
                    $stmt_act->execute([$data_ora_activitate, $act_nume, $user, $act_info]);
                }
                $activitate_id = $pdo->lastInsertId();
                log_activitate($pdo, "activitati: Activitate creata automat din lista participare - {$act_nume}");
            }
        }

        $coloane_json = json_encode(array_values($d['coloane']));
        $stmt = $pdo->prepare('INSERT INTO liste_prezenta (tip_titlu, detalii_activitate, data_lista, nr_registratura, detalii_suplimentare_sus, coloane_selectate, detalii_suplimentare_jos, semnatura_stanga_nume, semnatura_stanga_functie, semnatura_centru_nume, semnatura_centru_functie, semnatura_dreapta_nume, semnatura_dreapta_functie, activitate_id, created_by) VALUES (?,?,?,NULL,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$d['tip_titlu'], $d['detalii_activitate'], $d['data_lista'], $d['detalii_sus'], $coloane_json, $d['detalii_jos'], $d['semn_st_n'], $d['semn_st_f'], $d['semn_c_n'], $d['semn_c_f'], $d['semn_d_n'], $d['semn_d_f'], $activitate_id, $user]);
        $lista_id = $pdo->lastInsertId();

        $titlu_document = trim((string)$d['tip_titlu']);
        $detalii_document = trim((string)$d['detalii_activitate']);
        $nume_document = $titlu_document;
        if ($detalii_document !== '') {
            $nume_document .= ' - ' . mb_substr($detalii_document, 0, 180);
        }
        if ($nume_document === '') {
            $nume_document = 'Lista prezenta';
        }

        $reg = registratura_inregistreaza_document($pdo, [
            'tip_act' => 'Lista prezenta',
            'detalii' => 'Lista prezenta ID ' . (int)$lista_id . ': ' . $nume_document,
            'nr_document' => 'LP-' . (int)$lista_id,
            'data_document' => $d['data_lista'],
            'provine_din' => 'ANR Bihor',
            'continut_document' => $nume_document,
            'destinatar_document' => 'ANR Bihor',
        ]);
        if (empty($reg['success']) || empty($reg['nr_inregistrare'])) {
            $reg_err = (string)($reg['error'] ?? 'Nu s-a putut aloca numar in registratura.');
            throw new RuntimeException($reg_err);
        }
        $nr_registratura = (string)$reg['nr_inregistrare'];
        $pdo->prepare('UPDATE liste_prezenta SET nr_registratura = ? WHERE id = ?')->execute([$nr_registratura, $lista_id]);

        liste_prezenta_save_membri($pdo, $lista_id, $d['membri_ids'], $d['contacte_ids'], $d['participanti_manuali']);

        if ($activitate_id) {
            $pdo->prepare('UPDATE activitati SET lista_prezenta_id = ?, responsabili = COALESCE(responsabili, ?) WHERE id = ?')->execute([$lista_id, $user, $activitate_id]);
        }

        log_activitate($pdo, 'Lista prezenta creata: ' . $d['tip_titlu'] . ' - ' . $d['detalii_activitate']);

        // Prezenta in istoricul membrilor se logheaza la finalizarea activitatii (ActivitatiService)

        if (!empty($started_tx) && $pdo->inTransaction()) {
            $pdo->commit();
        }

        return ['success' => true, 'error' => '', 'lista_id' => (int)$lista_id, 'activitate_id' => $activitate_id];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage(), 'lista_id' => null, 'activitate_id' => null];
    }
}

/**
 * Incarca o lista de prezenta cu participantii ei.
 *
 * @return array|null ['lista' => array, 'participanti' => array] sau null daca nu exista.
 */
function liste_prezenta_load(PDO $pdo, int $id): ?array {
    try {
        $stmt = $pdo->prepare('SELECT * FROM liste_prezenta WHERE id = ?');
        $stmt->execute([$id]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lista) return null;

        liste_prezenta_ensure_nume_manual($pdo);

        liste_prezenta_ensure_contact_support($pdo);
        $stmt = $pdo->prepare('SELECT lm.membru_id, lm.contact_id, lm.ordine, lm.nume_manual, COALESCE(m.nume, c.nume) AS nume, COALESCE(m.prenume, c.prenume) AS prenume, m.datanastere, m.ciseria, m.cinumar, m.domloc FROM liste_prezenta_membri lm LEFT JOIN membri m ON lm.membru_id = m.id LEFT JOIN contacte c ON lm.contact_id = c.id WHERE lm.lista_id = ? ORDER BY lm.ordine');
        $stmt->execute([$id]);
        $participanti = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ['lista' => $lista, 'participanti' => $participanti];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Actualizeaza o lista de prezenta existenta.
 *
 * @return array ['success' => bool, 'error' => string]
 */
function liste_prezenta_update(PDO $pdo, int $id, array $post): array {
    $d = liste_prezenta_parse_post($post);

    try {
        $stmt = $pdo->prepare('UPDATE liste_prezenta SET tip_titlu=?, detalii_activitate=?, data_lista=?, detalii_suplimentare_sus=?, coloane_selectate=?, detalii_suplimentare_jos=?, semnatura_stanga_nume=?, semnatura_stanga_functie=?, semnatura_centru_nume=?, semnatura_centru_functie=?, semnatura_dreapta_nume=?, semnatura_dreapta_functie=? WHERE id=?');
        $stmt->execute([$d['tip_titlu'], $d['detalii_activitate'], $d['data_lista'], $d['detalii_sus'], json_encode(array_values($d['coloane'])), $d['detalii_jos'], $d['semn_st_n'], $d['semn_st_f'], $d['semn_c_n'], $d['semn_c_f'], $d['semn_d_n'], $d['semn_d_f'], $id]);

        $pdo->prepare('DELETE FROM liste_prezenta_membri WHERE lista_id = ?')->execute([$id]);

        liste_prezenta_save_membri($pdo, $id, $d['membri_ids'], $d['contacte_ids'], $d['participanti_manuali']);

        log_activitate($pdo, 'Lista prezenta modificata ID ' . $id);

        // Log prezenta in istoricul fiecarui membru
        // Prezenta in istoricul membrilor se logheaza la finalizarea activitatii (ActivitatiService)

        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare: ' . $e->getMessage()];
    }
}

/**
 * Șterge o listă de prezență și participanții aferenți.
 *
 * @return array ['success' => bool, 'error' => string]
 */
function liste_prezenta_delete(PDO $pdo, int $id, string $user = 'Sistem'): array {
    if ($id <= 0) {
        return ['success' => false, 'error' => 'ID listă invalid.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT id, tip_titlu, detalii_activitate FROM liste_prezenta WHERE id = ?');
        $stmt->execute([$id]);
        $lista = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lista) {
            return ['success' => false, 'error' => 'Lista nu a fost găsită.'];
        }

        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM liste_prezenta_membri WHERE lista_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE activitati SET lista_prezenta_id = NULL WHERE lista_prezenta_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM liste_prezenta WHERE id = ?')->execute([$id]);

        $pdo->commit();

        $detalii = trim((string)($lista['detalii_activitate'] ?? ''));
        $log_label = (string)($lista['tip_titlu'] ?? 'Lista prezenta');
        if ($detalii !== '') {
            $log_label .= ' - ' . mb_substr($detalii, 0, 120);
        }
        log_activitate($pdo, 'Lista prezenta stearsa: ' . $log_label . ' (ID ' . $id . ')', $user);

        return ['success' => true, 'error' => ''];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => 'Eroare la ștergerea listei: ' . $e->getMessage()];
    }
}
