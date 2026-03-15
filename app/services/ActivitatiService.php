<?php
/**
 * ActivitatiService — Business logic pentru modulul Activitati.
 *
 * Gestioneaza CRUD activitati, expansiune recurenta, migrari schema.
 * Cache per-request pentru SHOW COLUMNS.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/activitati_helper.php';

/**
 * Coloanele tabelului activitati. All columns exist in production.
 */
function activitati_columns(PDO $pdo): array {
    // All columns exist - schema is managed by install/schema/migration.php
    return ['id', 'data_ora', 'ora_finalizare', 'nume', 'locatie', 'responsabili', 'info_suplimentare', 'status', 'recurenta', 'lista_prezenta_id', 'created_at', 'updated_at'];
}

/**
 * No-op: schema is managed by install/schema/migration.php
 */
function activitati_ensure_schema(PDO $pdo): void {
    return;
}

/**
 * Recurente valide.
 */
function activitati_recurente_valide(): array {
    return ['zilnic', 'saptamanal', 'lunar', 'anual'];
}

/**
 * Statusuri valide pentru actualizare.
 */
function activitati_statusuri_valide(): array {
    return ['Finalizata', 'Reprogramata', 'Anulata'];
}

/**
 * Status badge CSS class.
 */
function activitati_status_class(string $status): string {
    $classes = [
        'Planificata'  => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
        'Finalizata'   => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
        'Reprogramata' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200',
        'Anulata'      => 'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-400',
    ];
    return $classes[$status] ?? $classes['Planificata'];
}

/**
 * Incarca activitati pentru un interval, cu expansiune recurenta.
 *
 * @return array ['activitati'=>[], 'eroare'=>string|null]
 */
function activitati_list(PDO $pdo, string $data_start, string $data_end): array {
    $cols = activitati_columns($pdo);
    $sel = 'id, data_ora, nume, locatie, responsabili, status';
    if (in_array('recurenta', $cols)) $sel .= ', recurenta';
    if (in_array('lista_prezenta_id', $cols)) $sel .= ', lista_prezenta_id';
    if (in_array('ora_finalizare', $cols)) $sel .= ', ora_finalizare';

    try {
        $stmt = $pdo->prepare("SELECT {$sel} FROM activitati WHERE DATE(data_ora) >= ? AND DATE(data_ora) <= ? ORDER BY data_ora ASC");
        $stmt->execute([$data_start, $data_end]);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw as &$a) {
            if (!isset($a['recurenta'])) $a['recurenta'] = null;
            if (!isset($a['lista_prezenta_id'])) $a['lista_prezenta_id'] = null;
        }

        $expanded = expandeaza_activitati_recurente($raw, $data_start, $data_end);
        return ['activitati' => $expanded, 'eroare' => null];
    } catch (PDOException $e) {
        return ['activitati' => [], 'eroare' => 'Eroare la încărcarea activităților.'];
    }
}

/**
 * Incarca activitati trecute pentru un an specific (istoric).
 */
function activitati_istoric(PDO $pdo, int $an): array {
    $cols = activitati_columns($pdo);
    $sel = 'id, data_ora, nume, locatie, responsabili, status, info_suplimentare';
    if (in_array('recurenta', $cols)) $sel .= ', recurenta';

    $azi = date('Y-m-d');

    try {
        $stmt = $pdo->prepare("SELECT {$sel} FROM activitati WHERE DATE(data_ora) < ? ORDER BY data_ora DESC");
        $stmt->execute([$azi]);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw as &$a) {
            if (!isset($a['recurenta'])) $a['recurenta'] = null;
        }

        $ieri = date('Y-m-d', strtotime($azi . ' -1 day'));
        $expanded = expandeaza_activitati_recurente($raw, '2000-01-01', $ieri);

        // Filtreaza pe an
        $result = array_filter($expanded, function ($a) use ($azi, $an) {
            $d = (new DateTime($a['data_ora']))->format('Y-m-d');
            return $d < $azi && substr($d, 0, 4) == $an;
        });
        $result = array_values($result);
        usort($result, function ($a, $b) {
            return strcmp($b['data_ora'], $a['data_ora']);
        });

        return ['activitati' => $result, 'eroare' => null];
    } catch (PDOException $e) {
        $msg = strpos($e->getMessage(), "doesn't exist") !== false
            ? 'Tabelul activități nu există. Rulați schema_activitati.sql.'
            : 'Eroare la încărcarea activităților.';
        return ['activitati' => [], 'eroare' => $msg];
    }
}

/**
 * Creeaza o activitate noua.
 *
 * @return array ['success'=>bool, 'id'=>int|null, 'error'=>string|null]
 */
function activitati_create(PDO $pdo, array $data, string $utilizator = 'Sistem'): array {
    $nume = trim($data['nume'] ?? '');
    $data_str = trim($data['data'] ?? '');
    $ora = trim($data['ora'] ?? '');

    if (empty($nume)) {
        return ['success' => false, 'id' => null, 'error' => 'Numele activității este obligatoriu.'];
    }
    if (empty($data_str) || empty($ora)) {
        return ['success' => false, 'id' => null, 'error' => 'Data și ora de început sunt obligatorii.'];
    }

    $data_ora = $data_str . ' ' . $ora;
    $dt = DateTime::createFromFormat('Y-m-d H:i', $data_ora);
    if (!$dt) {
        return ['success' => false, 'id' => null, 'error' => 'Format invalid pentru data sau ora.'];
    }

    // Ora finalizare
    $ora_fin_sql = null;
    $ora_fin_raw = trim($data['ora_finalizare'] ?? '');
    if ($ora_fin_raw !== '') {
        $ora_fin_dt = DateTime::createFromFormat('H:i', $ora_fin_raw);
        if ($ora_fin_dt) {
            $ora_fin_sql = $ora_fin_dt->format('H:i:s');
        }
    }

    // Responsabili
    $responsabili_array = isset($data['responsabili']) && is_array($data['responsabili']) ? $data['responsabili'] : [];
    $responsabili = !empty($responsabili_array) ? implode(', ', array_filter($responsabili_array)) : null;

    $locatie = trim($data['locatie'] ?? '') ?: null;
    $info = trim($data['info_suplimentare'] ?? '') ?: null;
    $rec_valide = activitati_recurente_valide();
    $rec = in_array($data['recurenta'] ?? '', $rec_valide) ? $data['recurenta'] : null;

    activitati_ensure_schema($pdo);

    try {
        $stmt = $pdo->prepare('INSERT INTO activitati (data_ora, ora_finalizare, nume, locatie, responsabili, info_suplimentare, recurenta) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$dt->format('Y-m-d H:i'), $ora_fin_sql, $nume, $locatie, $responsabili, $info, $rec]);
        $id = (int)$pdo->lastInsertId();

        $log_msg = 'Activitate adăugată: ' . $nume . ' (' . $dt->format(DATETIME_FORMAT) . ')';
        if ($rec) $log_msg .= ' recurentă ' . $rec;
        if ($responsabili) $log_msg .= ' / Responsabili: ' . $responsabili;
        log_activitate($pdo, $log_msg, $utilizator);

        return ['success' => true, 'id' => $id, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'id' => null, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

/**
 * Actualizeaza statusul unei activitati.
 */
function activitati_update_status(PDO $pdo, int $id, string $status, string $utilizator = 'Sistem'): array {
    if (!in_array($status, activitati_statusuri_valide())) {
        return ['success' => false, 'error' => 'Status invalid.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, lista_prezenta_id FROM activitati WHERE id = ?');
        $stmt->execute([$id]);
        $activitate = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$activitate) {
            return ['success' => false, 'error' => 'Activitate negăsită.'];
        }
        $nume = $activitate['nume'];

        $pdo->prepare('UPDATE activitati SET status = ? WHERE id = ?')->execute([$status, $id]);
        log_activitate($pdo, 'Status activitate modificat: ' . $nume . ' -> ' . $status, $utilizator);

        // Cand activitatea este finalizata, log prezenta pentru toti membrii din lista de prezenta asociata
        if ($status === 'Finalizata' && !empty($activitate['lista_prezenta_id'])) {
            $data_activitate = date('Y-m-d', strtotime($activitate['data_ora']));
            $stmt_membri = $pdo->prepare('SELECT membru_id FROM liste_prezenta_membri WHERE lista_id = ? AND membru_id IS NOT NULL');
            $stmt_membri->execute([$activitate['lista_prezenta_id']]);
            $membri = $stmt_membri->fetchAll(PDO::FETCH_COLUMN);
            foreach ($membri as $membru_id) {
                if ($membru_id > 0) {
                    log_activitate($pdo, "Activitate: Prezent la - {$nume} ({$data_activitate})", $utilizator, (int)$membru_id);
                }
            }
        }

        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizarea statusului.'];
    }
}

/**
 * Incarca lista de utilizatori activi (pentru responsabili).
 */
function activitati_utilizatori(PDO $pdo): array {
    try {
        require_once APP_ROOT . '/includes/auth_helper.php';
        auth_ensure_tables($pdo);
        $stmt = $pdo->query('SELECT id, nume_complet, username FROM utilizatori WHERE activ = 1 ORDER BY nume_complet');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Incarca liste de prezenta recente.
 */
function activitati_liste_prezenta(PDO $pdo, int $limit = 20): array {
    try {
        $stmt = $pdo->prepare("SELECT l.id, l.tip_titlu, l.detalii_activitate, l.data_lista, l.activitate_id, l.created_by FROM liste_prezenta l ORDER BY l.data_lista DESC, l.updated_at DESC LIMIT " . (int)$limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Luni in romana.
 */
function activitati_luni_ro(): array {
    return ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
}
