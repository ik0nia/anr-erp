<?php
/**
 * DashboardService — Business logic pentru modulul Dashboard.
 *
 * Toate operatiile de incarcare date + finalizare task.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/registru_interactiuni_v2_helper.php';
require_once APP_ROOT . '/includes/librarie_documente_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

/**
 * Incarca taskurile active si numarul de taskuri finalizate.
 *
 * @return array ['taskuri_active'=>[], 'taskuri_istoric_count'=>int]
 */
function dashboard_load_tasks(PDO $pdo, ?int $user_id = null): array {
    if ($user_id) {
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 AND (utilizator_id IS NULL OR utilizator_id = ?) ORDER BY data_ora ASC');
        $stmt->execute([$user_id]);
        $taskuri_active = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT COUNT(*) as n FROM taskuri WHERE finalizat = 1 AND (utilizator_id IS NULL OR utilizator_id = ?)');
        $stmt->execute([$user_id]);
        $taskuri_istoric_count = $stmt->fetch()['n'];
    } else {
        $stmt = $pdo->query('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 ORDER BY data_ora ASC');
        $taskuri_active = $stmt->fetchAll();
        $stmt = $pdo->query('SELECT COUNT(*) as n FROM taskuri WHERE finalizat = 1');
        $taskuri_istoric_count = $stmt->fetch()['n'];
    }

    return [
        'taskuri_active' => $taskuri_active,
        'taskuri_istoric_count' => (int) $taskuri_istoric_count,
    ];
}

/**
 * Incarca statistici: membri cu avertizari (CI/certificat care expira in <= 60 zile).
 *
 * @return array ['membri_cu_avertizari'=>int]
 */
function dashboard_load_stats(PDO $pdo): array {
    $membri_cu_avertizari = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE
            (status_dosar IS NULL OR status_dosar = 'Activ' OR status_dosar NOT IN ('Suspendat', 'Expirat', 'Retras', 'Decedat'))
            AND (
                (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND cidataexp > CURDATE() AND (expira_ci_notificat IS NULL OR expira_ci_notificat = 0))
                OR (ceexp IS NOT NULL AND ceexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND ceexp > CURDATE() AND (expira_ch_notificat IS NULL OR expira_ch_notificat = 0))
            )");
        $membri_cu_avertizari = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    return ['membri_cu_avertizari' => $membri_cu_avertizari];
}

/**
 * Finalizeaza un task (marcheaza ca finalizat).
 *
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function dashboard_finalize_task(PDO $pdo, int $task_id): array {
    if ($task_id <= 0) {
        return ['success' => false, 'error' => 'ID task invalid.'];
    }

    $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND finalizat = 0');
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    if (!$task) {
        return ['success' => false, 'error' => 'Task negasit sau deja finalizat.'];
    }

    // IMPORTANT: Finalizarea unui task NU afecteaza interactiunile din registru_interactiuni_v2
    // Taskurile sunt independente - doar se marcheaza ca finalizate in tabelul taskuri
    // Interactiunile raman permanent inregistrate in registru_interactiuni_v2
    $stmt = $pdo->prepare('UPDATE taskuri SET finalizat = 1, data_finalizare = NOW() WHERE id = ?');
    $stmt->execute([$task_id]);
    log_activitate($pdo, "Sarcina finalizata: {$task['nume']}");

    return ['success' => true, 'error' => null];
}

/**
 * Incarca interactiunile de azi (numar apeluri si vizite).
 *
 * @return array ['apel'=>int, 'vizita'=>int]
 */
function dashboard_load_interactiuni_azi(PDO $pdo): array {
    return registru_v2_interactiuni_azi($pdo);
}

/**
 * Adauga o interactiune v2 si optional creeaza un task.
 *
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function dashboard_add_interactiune_v2(PDO $pdo, array $data, string $user, ?int $user_id): array {
    ensure_registru_v2_tables($pdo);

    $tip = in_array($data['tip_interactiune_v2'] ?? '', ['apel', 'vizita']) ? $data['tip_interactiune_v2'] : 'apel';
    $persoana = trim($data['persoana_v2'] ?? '');
    $telefon = trim($data['telefon_v2'] ?? '');
    $subiect_id = (int)($data['subiect_id_v2'] ?? 0);
    $subiect_alt = trim($data['subiect_alt_v2'] ?? '');
    $informatii_suplimentare = trim($data['informatii_suplimentare_v2'] ?? '');
    $task_activ = isset($data['task_activ_v2']) ? 1 : 0;

    if (empty($persoana)) {
        $persoana = 'Fara nume';
    }

    // Validare: subiect sau subiect_alt obligatoriu
    if ($subiect_id <= 0 && empty($subiect_alt)) {
        return ['success' => false, 'error' => 'Selectati un subiect din lista sau completati campul "Alt subiect".'];
    }

    $subiect_id_val = $subiect_id > 0 ? $subiect_id : null;
    $subiect_alt_val = !empty($subiect_alt) ? $subiect_alt : null;

    $stmt = $pdo->prepare('INSERT INTO registru_interactiuni_v2 (tip, persoana, telefon, subiect_id, subiect_alt, informatii_suplimentare, task_activ, utilizator, utilizator_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$tip, $persoana, $telefon ?: null, $subiect_id_val, $subiect_alt_val, $informatii_suplimentare ?: null, $task_activ, $user, $user_id]);
    $interact_id = $pdo->lastInsertId();

    if ($task_activ) {
        // Construieste numele taskului
        $tip_label = $tip === 'apel' ? '[Apel]' : '[Vizita]';
        $telefon_display = $telefon ? "({$telefon})" : "(fara telefon)";

        $subiect_display = '';
        if ($subiect_alt_val) {
            $subiect_display = $subiect_alt_val;
        } elseif ($subiect_id_val) {
            $stmt_sn = $pdo->prepare('SELECT nume FROM registru_interactiuni_v2_subiecte WHERE id = ?');
            $stmt_sn->execute([$subiect_id_val]);
            $nume_subiect = $stmt_sn->fetchColumn();
            if ($nume_subiect) {
                $subiect_display = $nume_subiect;
            }
        }

        $nume_task_parts = [];
        $nume_task_parts[] = $tip_label . ' ' . $persoana . ' ' . $telefon_display;
        if ($subiect_display) {
            $nume_task_parts[] = $subiect_display;
        }
        if ($informatii_suplimentare) {
            $nume_task_parts[] = $informatii_suplimentare;
        }
        $nume_task = implode(' - ', $nume_task_parts);

        $detalii_task = $informatii_suplimentare ?: '';

        $stmt_t = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, NOW(), ?, ?, ?)');
        $stmt_t->execute([$nume_task, $detalii_task ?: null, 'normal', $user_id]);
        $task_id_val = $pdo->lastInsertId();
        // IMPORTANT: Taskurile sunt independente de interactiuni
        $pdo->prepare('UPDATE registru_interactiuni_v2 SET task_id = ? WHERE id = ?')->execute([$task_id_val, $interact_id]);
        log_activitate($pdo, "Task creat din interactiune v2: {$nume_task}");
    }

    log_activitate($pdo, "registru_interactiuni_v2: " . ($tip === 'apel' ? 'Apel telefonic' : 'Vizita sediu') . " inregistrat: {$persoana}");

    return ['success' => true, 'error' => null];
}

/**
 * Cauta membri dupa nume, prenume, CNP sau numar dosar.
 *
 * @return array Lista de membri gasiti (max 10)
 */
function dashboard_search_membri(PDO $pdo, string $cautare): array {
    if (empty($cautare)) return [];

    $search_term = '%' . $cautare . '%';
    $stmt = $pdo->prepare('SELECT id, nume, prenume, cnp, dosarnr FROM membri
                           WHERE nume LIKE ? OR prenume LIKE ? OR cnp LIKE ? OR dosarnr LIKE ?
                           ORDER BY nume, prenume LIMIT 10');
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
