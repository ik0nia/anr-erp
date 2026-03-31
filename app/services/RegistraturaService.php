<?php
/**
 * RegistraturaService — Business logic pentru modulul Registratura.
 *
 * CRUD inregistrari documente, alocare numar intern, creare task asociat.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/registratura_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Normalizeaza valoarea checkbox-ului task_deschis din formulare.
 */
function registratura_task_deschis_flag(array $data): int {
    return isset($data['task_deschis']) && (string)$data['task_deschis'] === '1' ? 1 : 0;
}

/**
 * Lista inregistrari cu paginare.
 *
 * @return array ['inregistrari'=>[], 'total'=>int, 'total_pages'=>int]
 */
function registratura_list(PDO $pdo, int $page, int $per_page): array {
    ensure_registratura_table($pdo);

    $stmt = $pdo->query('SELECT COUNT(*) as n FROM registratura');
    $total = (int) $stmt->fetch()['n'];

    $offset = ($page - 1) * $per_page;
    $stmt = $pdo->prepare('SELECT * FROM registratura ORDER BY data_ora DESC, id DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $inregistrari = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;

    return ['inregistrari' => $inregistrari, 'total' => $total, 'total_pages' => $total_pages];
}

/**
 * Preia o inregistrare dupa ID.
 */
function registratura_get(PDO $pdo, int $id): ?array {
    ensure_registratura_table($pdo);
    $stmt = $pdo->prepare('SELECT * FROM registratura WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Creeaza o inregistrare noua in registratura.
 *
 * @return array ['success'=>bool, 'id'=>int|null, 'nr_inregistrare'=>string|null, 'error'=>string|null]
 */
function registratura_create(PDO $pdo, array $data, string $utilizator = 'Sistem'): array {
    ensure_registratura_table($pdo);

    $tip_act_input = trim($data['tip_act'] ?? '');
    $tip_act = in_array($tip_act_input, ['Document primit', 'Document emis'], true)
        ? $tip_act_input
        : 'Înregistrare document';
    $nr_document = trim($data['nr_document'] ?? '') ?: null;
    $data_document = trim($data['data_document'] ?? '') ?: null;
    $provine_din = trim($data['provine_din'] ?? '') ?: null;
    $continut_document = trim($data['continut_document'] ?? '') ?: null;
    $destinatar_document = trim($data['destinatar_document'] ?? '') ?: null;
    $task_deschis = registratura_task_deschis_flag($data);

    try {
        // Retry în caz de conflict pe nr_intern (UNIQUE constraint)
        $max_incercari = 3;
        $id = null;
        $nr_intern = null;
        $nr_inregistrare = null;

        for ($incercare = 0; $incercare < $max_incercari; $incercare++) {
            $nr_intern = registratura_urmatorul_nr($pdo);
            $nr_inregistrare = (string) $nr_intern;

            try {
                $stmt = $pdo->prepare('INSERT INTO registratura (nr_intern, nr_inregistrare, utilizator, tip_act, nr_document, data_document, provine_din, continut_document, destinatar_document, task_deschis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $nr_intern, $nr_inregistrare, $utilizator, $tip_act,
                    $nr_document, $data_document, $provine_din, $continut_document, $destinatar_document, $task_deschis
                ]);
                $id = (int)$pdo->lastInsertId();
                break; // Insert reușit
            } catch (PDOException $e) {
                if ($incercare < $max_incercari - 1 && strpos($e->getMessage(), 'Duplicate') !== false) {
                    continue;
                }
                throw $e;
            }
        }

        // Creare task asociat
        $task_id = null;
        if ($task_deschis) {
            $nume_task = 'Registratura nr. ' . $nr_inregistrare . ': ' . ($continut_document ? mb_substr($continut_document, 0, 80) : 'Document');
            $detalii_task = "Nr. document: {$nr_document}\nProvine din: {$provine_din}\nDestinatar: {$destinatar_document}\nContinut: {$continut_document}";
            $stmt_t = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, NOW(), ?, ?)');
            $stmt_t->execute([$nume_task, $detalii_task ?: null, 'normal']);
            $task_id = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE registratura SET task_id = ? WHERE id = ?')->execute([$task_id, $id]);
            log_activitate($pdo, "Task creat din registratura nr. {$nr_inregistrare}", $utilizator);
        }

        log_activitate($pdo, "Înregistrare registratura nr. {$nr_inregistrare}", $utilizator);

        return ['success' => true, 'id' => $id, 'nr_inregistrare' => $nr_inregistrare, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'id' => null, 'nr_inregistrare' => null, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

/**
 * Actualizeaza o inregistrare existenta.
 */
function registratura_update(PDO $pdo, int $id, array $data, string $utilizator = 'Sistem'): array {
    $r = registratura_get($pdo, $id);
    if (!$r) return ['success' => false, 'error' => 'Înregistrare negăsită.'];

    $data_str = trim($data['data'] ?? '');
    if (empty($data_str)) return ['success' => false, 'error' => 'Data este obligatorie.'];

    $nr_document = trim($data['nr_document'] ?? '') ?: null;
    $data_document = trim($data['data_document'] ?? '') ?: null;
    $provine_din = trim($data['provine_din'] ?? '') ?: null;
    $continut_document = trim($data['continut_document'] ?? '') ?: null;
    $destinatar_document = trim($data['destinatar_document'] ?? '') ?: null;
    $task_deschis = registratura_task_deschis_flag($data);

    try {
        $data_ora = $data_str . ' ' . date('H:i:s', strtotime($r['data_ora']));

        // Gestionare task asociat
        $task_id_actual = $r['task_id'];
        if ($task_deschis && !$r['task_id']) {
            $nume_task = 'Registratura nr. ' . $r['nr_inregistrare'] . ': ' . ($continut_document ? mb_substr($continut_document, 0, 80) : 'Document');
            $detalii_task = "Nr. document: {$nr_document}\nProvine din: {$provine_din}\nDestinatar: {$destinatar_document}\nContinut: {$continut_document}";
            $stmt_t = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, NOW(), ?, ?)');
            $stmt_t->execute([$nume_task, $detalii_task ?: null, 'normal']);
            $task_id_actual = (int)$pdo->lastInsertId();
            log_activitate($pdo, "Task creat din registratura nr. {$r['nr_inregistrare']}", $utilizator);
        } elseif (!$task_deschis && $r['task_id']) {
            $task_id_actual = null;
        }

        // Log modificari
        $modificari = [];
        $campuri = ['nr_document' => 'Numar document', 'provine_din' => 'Provine din', 'destinatar_document' => 'Destinatar'];
        foreach ($campuri as $camp => $label) {
            if (($r[$camp] ?? '') !== (${$camp} ?? '')) {
                $modificari[] = log_format_modificare($label, $r[$camp] ?? '', ${$camp} ?? '');
            }
        }
        if (($r['task_deschis'] ?? 0) != $task_deschis) {
            $modificari[] = log_format_modificare('Task deschis', ($r['task_deschis'] ?? 0) ? 'Da' : 'Nu', $task_deschis ? 'Da' : 'Nu');
        }

        $stmt = $pdo->prepare('UPDATE registratura SET data_ora = ?, nr_document = ?, data_document = ?, provine_din = ?, continut_document = ?, destinatar_document = ?, task_deschis = ?, task_id = ? WHERE id = ?');
        $stmt->execute([$data_ora, $nr_document, $data_document, $provine_din, $continut_document, $destinatar_document, $task_deschis, $task_id_actual, $id]);

        if (!empty($modificari)) {
            log_activitate($pdo, "registratura: " . implode("; ", $modificari) . " / Nr. inregistrare: {$r['nr_inregistrare']}", $utilizator);
        } else {
            log_activitate($pdo, "registratura: Inregistrare nr. {$r['nr_inregistrare']} actualizata", $utilizator);
        }

        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizare: ' . $e->getMessage()];
    }
}
