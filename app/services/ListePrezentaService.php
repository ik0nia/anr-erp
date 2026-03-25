<?php
/**
 * ListePrezentaService — Business logic pentru module Liste Prezenta.
 *
 * Gestioneaza CRUD liste prezenta, participanti, migrari schema.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/liste_helper.php';

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

    // Participanti manuali (fara membru_id)
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
        'coloane', 'membri_ids', 'participanti_manuali',
        'semn_st_n', 'semn_st_f', 'semn_c_n', 'semn_c_f', 'semn_d_n', 'semn_d_f'
    );
}

/**
 * Salveaza membrii si participantii manuali pentru o lista.
 */
function liste_prezenta_save_membri(PDO $pdo, int $lista_id, array $membri_ids, array $participanti_manuali): void {
    liste_prezenta_ensure_nume_manual($pdo);

    $stmt_m = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, ordine, nume_manual) VALUES (?, ?, ?, NULL)');
    $ordine_curenta = 1;
    foreach (array_values($membri_ids) as $mid) {
        if ($mid) {
            $stmt_m->execute([$lista_id, $mid, $ordine_curenta]);
            $ordine_curenta++;
        }
    }

    $stmt_m_manual = $pdo->prepare('INSERT INTO liste_prezenta_membri (lista_id, membru_id, ordine, nume_manual) VALUES (?, NULL, ?, ?)');
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
        $stmt = $pdo->prepare('INSERT INTO liste_prezenta (tip_titlu, detalii_activitate, data_lista, detalii_suplimentare_sus, coloane_selectate, detalii_suplimentare_jos, semnatura_stanga_nume, semnatura_stanga_functie, semnatura_centru_nume, semnatura_centru_functie, semnatura_dreapta_nume, semnatura_dreapta_functie, activitate_id, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$d['tip_titlu'], $d['detalii_activitate'], $d['data_lista'], $d['detalii_sus'], $coloane_json, $d['detalii_jos'], $d['semn_st_n'], $d['semn_st_f'], $d['semn_c_n'], $d['semn_c_f'], $d['semn_d_n'], $d['semn_d_f'], $activitate_id, $user]);
        $lista_id = $pdo->lastInsertId();

        liste_prezenta_save_membri($pdo, $lista_id, $d['membri_ids'], $d['participanti_manuali']);

        if ($activitate_id) {
            $pdo->prepare('UPDATE activitati SET lista_prezenta_id = ?, responsabili = COALESCE(responsabili, ?) WHERE id = ?')->execute([$lista_id, $user, $activitate_id]);
        }

        log_activitate($pdo, 'Lista prezenta creata: ' . $d['tip_titlu'] . ' - ' . $d['detalii_activitate']);

        // Prezenta in istoricul membrilor se logheaza la finalizarea activitatii (ActivitatiService)

        return ['success' => true, 'error' => '', 'lista_id' => (int)$lista_id, 'activitate_id' => $activitate_id];
    } catch (PDOException $e) {
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

        $stmt = $pdo->prepare('SELECT lm.membru_id, lm.ordine, lm.nume_manual, m.nume, m.prenume, m.datanastere, m.ciseria, m.cinumar, m.domloc FROM liste_prezenta_membri lm LEFT JOIN membri m ON lm.membru_id = m.id WHERE lm.lista_id = ? ORDER BY lm.ordine');
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

        liste_prezenta_save_membri($pdo, $id, $d['membri_ids'], $d['participanti_manuali']);

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
