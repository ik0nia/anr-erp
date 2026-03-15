<?php
/**
 * Helper pentru modulul Tickete
 */
require_once __DIR__ . '/registratura_helper.php';
require_once __DIR__ . '/log_helper.php';

/**
 * Asigura existenta tabelelor tickete si tickete_departamente.
 */
function tickete_ensure_tables(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS tickete (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nr_inregistrare VARCHAR(50),
        titlu VARCHAR(255) NOT NULL,
        departament VARCHAR(100),
        tip ENUM('Solicitare','Sugestie','Reclamatie','Alt tip') NOT NULL DEFAULT 'Solicitare',
        prioritate ENUM('Urgent','Normal','Optional') NOT NULL DEFAULT 'Normal',
        status ENUM('Nou','Deschis','In lucru','Finalizat favorabil','Finalizat respins','Irelevant') NOT NULL DEFAULT 'Nou',
        membru_id INT,
        nume_solicitant VARCHAR(255),
        note TEXT,
        raspuns_final TEXT,
        nr_inregistrare_raspuns VARCHAR(50),
        creat_de VARCHAR(100),
        creat_de_id INT,
        data_creare DATETIME DEFAULT CURRENT_TIMESTAMP,
        data_actualizare DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_membru (membru_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tickete_departamente (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        activ TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Lista tickete cu filtre optionale.
 * @param PDO $pdo
 * @param array $filters Chei posibile: status, departament, prioritate, tip, search
 * @return array
 */
function tickete_lista(PDO $pdo, array $filters = []): array {
    tickete_ensure_tables($pdo);
    $where = [];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 't.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['departament'])) {
        $where[] = 't.departament = ?';
        $params[] = $filters['departament'];
    }
    if (!empty($filters['prioritate'])) {
        $where[] = 't.prioritate = ?';
        $params[] = $filters['prioritate'];
    }
    if (!empty($filters['tip'])) {
        $where[] = 't.tip = ?';
        $params[] = $filters['tip'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(t.titlu LIKE ? OR t.nume_solicitant LIKE ? OR t.nr_inregistrare LIKE ?)';
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }

    $sql = "SELECT t.*, m.nume AS membru_nume, m.prenume AS membru_prenume, m.telefonnev AS membru_telefon, m.email AS membru_email
            FROM tickete t
            LEFT JOIN membri m ON m.id = t.membru_id
            " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
            ORDER BY
                CASE t.status
                    WHEN 'Nou' THEN 1
                    WHEN 'Deschis' THEN 2
                    WHEN 'In lucru' THEN 3
                    WHEN 'Finalizat favorabil' THEN 4
                    WHEN 'Finalizat respins' THEN 5
                    WHEN 'Irelevant' THEN 6
                END,
                CASE t.prioritate
                    WHEN 'Urgent' THEN 1
                    WHEN 'Normal' THEN 2
                    WHEN 'Optional' THEN 3
                END,
                t.data_creare DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtine un ticket dupa ID.
 */
function tickete_get(PDO $pdo, int $id): ?array {
    tickete_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT t.*, m.nume AS membru_nume, m.prenume AS membru_prenume, m.telefonnev AS membru_telefon, m.email AS membru_email
                           FROM tickete t
                           LEFT JOIN membri m ON m.id = t.membru_id
                           WHERE t.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Adauga un ticket nou.
 * @param PDO $pdo
 * @param array $data Chei: titlu, departament, tip, prioritate, membru_id, nume_solicitant, note, creare_task, notifica_utilizatori
 * @return array ['success'=>bool, 'id'=>int, 'error'=>string]
 */
function tickete_adauga(PDO $pdo, array $data): array {
    tickete_ensure_tables($pdo);

    $titlu = trim($data['titlu'] ?? '');
    if ($titlu === '') {
        return ['success' => false, 'error' => 'Titlul este obligatoriu.'];
    }

    $departament = trim($data['departament'] ?? '');
    $tip = in_array($data['tip'] ?? '', ['Solicitare','Sugestie','Reclamatie','Alt tip']) ? $data['tip'] : 'Solicitare';
    $prioritate = in_array($data['prioritate'] ?? '', ['Urgent','Normal','Optional']) ? $data['prioritate'] : 'Normal';
    $membru_id = !empty($data['membru_id']) ? (int)$data['membru_id'] : null;
    $nume_solicitant = trim($data['nume_solicitant'] ?? '');
    $note = trim($data['note'] ?? '');
    $creat_de = $data['creat_de'] ?? ($_SESSION['utilizator'] ?? 'Sistem');
    $creat_de_id = $data['creat_de_id'] ?? ($_SESSION['user_id'] ?? null);

    // Inregistreaza in registratura
    $reg = registratura_inregistreaza_document($pdo, [
        'tip_act' => 'Ticket',
        'detalii' => $titlu,
        'continut_document' => $titlu . ($note ? "\n" . $note : ''),
        'provine_din' => $nume_solicitant ?: null,
        'destinatar_document' => $departament ?: null,
        'membru_id' => $membru_id,
        'task_deschis' => !empty($data['creare_task']) ? 1 : 0,
    ]);

    $nr_inregistrare = $reg['success'] ? $reg['nr_inregistrare'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO tickete (nr_inregistrare, titlu, departament, tip, prioritate, status, membru_id, nume_solicitant, note, creat_de, creat_de_id) VALUES (?, ?, ?, ?, ?, 'Nou', ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nr_inregistrare,
            $titlu,
            $departament ?: null,
            $tip,
            $prioritate,
            $membru_id,
            $nume_solicitant ?: null,
            $note ?: null,
            $creat_de,
            $creat_de_id,
        ]);
        $id = (int)$pdo->lastInsertId();

        // Log activitate
        log_activitate($pdo, log_format_creare('Ticket', '#' . $nr_inregistrare . ' ' . $titlu), $creat_de, $membru_id);

        // Notifica utilizatorii daca este bifat
        if (!empty($data['notifica_utilizatori'])) {
            try {
                require_once __DIR__ . '/../app/services/NotificariService.php';
                notificari_adauga($pdo, [
                    'titlu' => 'Ticket nou: ' . $titlu,
                    'importanta' => $prioritate === 'Urgent' ? 'Important' : 'Normal',
                    'continut' => "S-a creat un ticket nou.\n\nTitlu: {$titlu}\nTip: {$tip}\nPrioritate: {$prioritate}\nDepartament: {$departament}\nSolicitant: {$nume_solicitant}\n\nNr. inregistrare: {$nr_inregistrare}",
                    'link_extern' => '',
                    'trimite_email' => 0,
                ], null, $creat_de_id);
            } catch (Throwable $e) {
                // Nu blocam daca notificarea esueaza
            }
        }

        return ['success' => true, 'id' => $id, 'nr_inregistrare' => $nr_inregistrare];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

/**
 * Actualizeaza un ticket existent.
 */
function tickete_actualizeaza(PDO $pdo, int $id, array $data): array {
    tickete_ensure_tables($pdo);

    $ticket = tickete_get($pdo, $id);
    if (!$ticket) {
        return ['success' => false, 'error' => 'Ticketul nu a fost gasit.'];
    }

    $titlu = trim($data['titlu'] ?? $ticket['titlu']);
    $departament = trim($data['departament'] ?? $ticket['departament'] ?? '');
    $tip = in_array($data['tip'] ?? '', ['Solicitare','Sugestie','Reclamatie','Alt tip']) ? $data['tip'] : $ticket['tip'];
    $prioritate = in_array($data['prioritate'] ?? '', ['Urgent','Normal','Optional']) ? $data['prioritate'] : $ticket['prioritate'];
    $status = in_array($data['status'] ?? '', ['Nou','Deschis','In lucru','Finalizat favorabil','Finalizat respins','Irelevant']) ? $data['status'] : $ticket['status'];
    $note = array_key_exists('note', $data) ? trim($data['note'] ?? '') : ($ticket['note'] ?? '');
    $raspuns_final = array_key_exists('raspuns_final', $data) ? trim($data['raspuns_final'] ?? '') : ($ticket['raspuns_final'] ?? '');
    $nume_solicitant = trim($data['nume_solicitant'] ?? $ticket['nume_solicitant'] ?? '');
    $membru_id = array_key_exists('membru_id', $data) ? (!empty($data['membru_id']) ? (int)$data['membru_id'] : null) : $ticket['membru_id'];

    try {
        $stmt = $pdo->prepare("UPDATE tickete SET titlu = ?, departament = ?, tip = ?, prioritate = ?, status = ?, note = ?, raspuns_final = ?, nume_solicitant = ?, membru_id = ? WHERE id = ?");
        $stmt->execute([
            $titlu,
            $departament ?: null,
            $tip,
            $prioritate,
            $status,
            $note ?: null,
            $raspuns_final ?: null,
            $nume_solicitant ?: null,
            $membru_id,
            $id,
        ]);

        // Log modificari
        $utilizator = $_SESSION['utilizator'] ?? 'Sistem';
        if ($ticket['status'] !== $status) {
            log_activitate($pdo, log_format_modificare('Ticket #' . $ticket['nr_inregistrare'] . ' status', $ticket['status'], $status), $utilizator, $membru_id);
        }
        if ($ticket['titlu'] !== $titlu) {
            log_activitate($pdo, log_format_modificare('Ticket #' . $ticket['nr_inregistrare'] . ' titlu', $ticket['titlu'], $titlu), $utilizator, $membru_id);
        }

        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizare: ' . $e->getMessage()];
    }
}

/**
 * Numara ticketele deschise (status != Finalizat favorabil, Finalizat respins, Irelevant).
 */
function tickete_count_deschise(PDO $pdo): int {
    try {
        tickete_ensure_tables($pdo);
        $stmt = $pdo->query("SELECT COUNT(*) FROM tickete WHERE status NOT IN ('Finalizat favorabil', 'Finalizat respins', 'Irelevant')");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Lista departamente.
 */
function tickete_departamente_lista(PDO $pdo): array {
    tickete_ensure_tables($pdo);
    try {
        $stmt = $pdo->query("SELECT * FROM tickete_departamente ORDER BY activ DESC, nume ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Lista departamente active (pentru dropdown).
 */
function tickete_departamente_active(PDO $pdo): array {
    tickete_ensure_tables($pdo);
    try {
        $stmt = $pdo->query("SELECT * FROM tickete_departamente WHERE activ = 1 ORDER BY nume ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Adauga un departament.
 */
function tickete_departament_adauga(PDO $pdo, string $nume): array {
    $nume = trim($nume);
    if ($nume === '') {
        return ['success' => false, 'error' => 'Numele departamentului este obligatoriu.'];
    }
    tickete_ensure_tables($pdo);
    try {
        $stmt = $pdo->prepare("INSERT INTO tickete_departamente (nume, activ) VALUES (?, 1)");
        $stmt->execute([$nume]);
        return ['success' => true, 'id' => (int)$pdo->lastInsertId()];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

/**
 * Toggle activ/inactiv departament.
 */
function tickete_departament_toggle(PDO $pdo, int $id): array {
    tickete_ensure_tables($pdo);
    try {
        $pdo->prepare("UPDATE tickete_departamente SET activ = NOT activ WHERE id = ?")->execute([$id]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Inregistreaza raspunsul in registratura si actualizeaza ticketul.
 */
function tickete_trimite_raspuns(PDO $pdo, int $id, string $raspuns): array {
    $ticket = tickete_get($pdo, $id);
    if (!$ticket) {
        return ['success' => false, 'error' => 'Ticketul nu a fost gasit.'];
    }

    // Blocheaza raspunsul daca ticketul e deja finalizat
    if (in_array($ticket['status'], ['Finalizat favorabil', 'Finalizat respins', 'Irelevant'])) {
        return ['success' => false, 'error' => 'Ticketul este deja finalizat. Nu se poate trimite un raspuns.'];
    }

    try {
        $pdo->beginTransaction();

        // Inregistreaza raspunsul in registratura
        $reg = registratura_inregistreaza_document($pdo, [
            'tip_act' => 'Raspuns Ticket',
            'detalii' => 'Raspuns la Ticket #' . $ticket['nr_inregistrare'] . ': ' . $ticket['titlu'],
            'continut_document' => $raspuns,
            'provine_din' => null,
            'destinatar_document' => $ticket['nume_solicitant'] ?: null,
            'membru_id' => $ticket['membru_id'],
            'task_deschis' => 0,
        ]);

        $nr_inregistrare_raspuns = $reg['success'] ? $reg['nr_inregistrare'] : null;

        $stmt = $pdo->prepare("UPDATE tickete SET raspuns_final = ?, nr_inregistrare_raspuns = ?, status = 'Finalizat favorabil' WHERE id = ?");
        $stmt->execute([$raspuns, $nr_inregistrare_raspuns, $id]);

        $pdo->commit();

        $utilizator = $_SESSION['utilizator'] ?? 'Sistem';
        log_activitate($pdo, 'Ticket #' . $ticket['nr_inregistrare'] . ': Raspuns trimis (nr. ' . $nr_inregistrare_raspuns . ')', $utilizator, $ticket['membru_id']);

        return ['success' => true, 'nr_inregistrare_raspuns' => $nr_inregistrare_raspuns];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
