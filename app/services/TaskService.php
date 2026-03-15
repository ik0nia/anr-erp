<?php
/**
 * TaskService — Business logic pentru modulul Taskuri.
 *
 * Centralizeaza toate operatiile CRUD pe tabelul taskuri.
 * Rezolva anti-pattern-ul SHOW COLUMNS repetat prin cache per-request.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Cache per-request: verifica o singura data daca exista coloana utilizator_id.
 * Elimina 5+ apeluri SHOW COLUMNS per request.
 */
function taskuri_has_user_id(PDO $pdo): bool {
    static $result = null;
    if ($result !== null) return $result;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
        $result = in_array('utilizator_id', $cols);
    } catch (PDOException $e) {
        $result = false;
    }
    return $result;
}

/**
 * Niveluri de urgenta valide.
 */
function task_niveluri_urgenta(): array {
    return ['normal', 'important', 'reprogramat'];
}

/**
 * Valideaza nivelul de urgenta.
 */
function task_nivel_valid(string $nivel): string {
    return in_array($nivel, task_niveluri_urgenta()) ? $nivel : 'normal';
}

/**
 * Construieste conditia WHERE pentru filtrare pe utilizator.
 * Returneaza [sql_fragment, params_array].
 */
function task_user_filter(PDO $pdo, ?int $user_id): array {
    if (taskuri_has_user_id($pdo) && $user_id) {
        return ['(utilizator_id IS NULL OR utilizator_id = ?)', [$user_id]];
    }
    return ['1=1', []];
}

/**
 * Creeaza un task nou.
 *
 * @return array ['success'=>bool, 'id'=>int, 'error'=>string|null]
 */
function task_service_create(PDO $pdo, array $data, string $utilizator = 'Sistem'): array {
    $nume = trim($data['nume'] ?? '');
    if ($nume === '') {
        return ['success' => false, 'id' => 0, 'error' => 'Numele taskului este obligatoriu.'];
    }

    $data_str = trim($data['data'] ?? '');
    $ora_str = trim($data['ora'] ?? '');

    if (empty($data_str)) {
        return ['success' => false, 'id' => 0, 'error' => 'Data este obligatorie.'];
    }

    // Construieste data_ora
    if (empty($ora_str)) $ora_str = '09:00';
    if (strlen($ora_str) === 5) $ora_str .= ':00';
    $data_ora = $data_str . ' ' . $ora_str;

    // Daca avem deja data_ora complet (din task_create vechi)
    if (!empty($data['data_ora'])) {
        $data_ora = $data['data_ora'];
    }

    $detalii = trim($data['detalii'] ?? '') ?: null;
    $nivel = task_nivel_valid($data['nivel_urgenta'] ?? 'normal');
    $uid = isset($data['utilizator_id']) ? (int)$data['utilizator_id'] : null;

    try {
        if (taskuri_has_user_id($pdo)) {
            $stmt = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nume, $data_ora, $detalii, $nivel, $uid]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nume, $data_ora, $detalii, $nivel]);
        }

        $id = (int)$pdo->lastInsertId();
        log_activitate($pdo, "Sarcină creată: {$nume} (nivel: {$nivel})", $utilizator);
        return ['success' => true, 'id' => $id, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'id' => 0, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

/**
 * Preia un task dupa ID, filtrat pe utilizator.
 */
function task_get(PDO $pdo, int $id, ?int $user_id = null): ?array {
    list($uf_sql, $uf_params) = task_user_filter($pdo, $user_id);
    $stmt = $pdo->prepare("SELECT * FROM taskuri WHERE id = ? AND {$uf_sql}");
    $stmt->execute(array_merge([$id], $uf_params));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Lista taskuri active (nefinalizate), filtrate pe utilizator.
 */
function task_list_active(PDO $pdo, ?int $user_id = null): array {
    list($uf_sql, $uf_params) = task_user_filter($pdo, $user_id);
    $stmt = $pdo->prepare("SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 AND {$uf_sql} ORDER BY data_ora ASC");
    $stmt->execute($uf_params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lista taskuri finalizate (istoric), filtrate pe utilizator.
 */
function task_list_istoric(PDO $pdo, ?int $user_id = null): array {
    list($uf_sql, $uf_params) = task_user_filter($pdo, $user_id);
    $stmt = $pdo->prepare("SELECT id, nume, data_ora, detalii, nivel_urgenta, data_finalizare FROM taskuri WHERE finalizat = 1 AND {$uf_sql} ORDER BY data_finalizare DESC");
    $stmt->execute($uf_params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Finalizeaza un task.
 */
function task_finalize(PDO $pdo, int $id, ?int $user_id = null, string $utilizator = 'Sistem'): array {
    $task = task_get($pdo, $id, $user_id);
    if (!$task || !empty($task['finalizat'])) {
        return ['success' => false, 'error' => 'Task negăsit sau deja finalizat.'];
    }

    $pdo->prepare('UPDATE taskuri SET finalizat = 1, data_finalizare = NOW() WHERE id = ?')->execute([$id]);
    log_activitate($pdo, "Sarcină finalizată: {$task['nume']}", $utilizator);
    return ['success' => true, 'error' => null];
}

/**
 * Actualizeaza un task.
 */
function task_update(PDO $pdo, int $id, array $data, ?int $user_id = null, string $utilizator = 'Sistem'): array {
    $nume = trim($data['nume'] ?? '');
    if ($nume === '') {
        return ['success' => false, 'error' => 'Numele taskului este obligatoriu.'];
    }

    $data_str = trim($data['data'] ?? '');
    if (empty($data_str)) {
        return ['success' => false, 'error' => 'Data este obligatorie.'];
    }

    $ora_str = trim($data['ora'] ?? '');
    if (empty($ora_str)) $ora_str = '09:00';
    if (strlen($ora_str) === 5) $ora_str .= ':00';
    $data_ora = $data_str . ' ' . $ora_str;

    $detalii = trim($data['detalii'] ?? '') ?: null;
    $nivel = task_nivel_valid($data['nivel_urgenta'] ?? 'normal');

    $task = task_get($pdo, $id, $user_id);
    if (!$task) {
        return ['success' => false, 'error' => 'Task negăsit.'];
    }

    try {
        $stmt = $pdo->prepare('UPDATE taskuri SET nume = ?, data_ora = ?, detalii = ?, nivel_urgenta = ? WHERE id = ?');
        $stmt->execute([$nume, $data_ora, $detalii, $nivel, $id]);
        log_activitate($pdo, "Sarcină actualizată: {$nume} (nivel: {$nivel})", $utilizator);
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizare: ' . $e->getMessage()];
    }
}

/**
 * Reactiveaza un task finalizat.
 */
function task_reactivate(PDO $pdo, int $id, ?int $user_id = null, string $utilizator = 'Sistem'): array {
    list($uf_sql, $uf_params) = task_user_filter($pdo, $user_id);
    $stmt = $pdo->prepare("SELECT nume FROM taskuri WHERE id = ? AND finalizat = 1 AND {$uf_sql}");
    $stmt->execute(array_merge([$id], $uf_params));
    $task = $stmt->fetch();

    if (!$task) {
        return ['success' => false, 'error' => 'Task negăsit sau nu este finalizat.'];
    }

    $pdo->prepare('UPDATE taskuri SET finalizat = 0, data_finalizare = NULL WHERE id = ?')->execute([$id]);
    log_activitate($pdo, "Sarcină reactivată: {$task['nume']}", $utilizator);
    return ['success' => true, 'error' => null];
}

/**
 * Badge CSS class pentru nivel urgenta.
 */
function task_badge_class(string $nivel): string {
    $badges = [
        'normal'      => 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200',
        'important'   => 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100',
        'reprogramat' => 'bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100',
    ];
    return $badges[$nivel] ?? $badges['normal'];
}

// ---- Compatibilitate cu task_helper.php vechi ----
// task_create() ramane definita in includes/task_helper.php
// pentru compatibilitate cu index.php, registratura, administrativ etc.
