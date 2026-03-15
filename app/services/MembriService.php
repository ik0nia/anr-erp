<?php
/**
 * MembriService — Business logic pentru modulul Membri.
 *
 * Toate operatiile CRUD + validare + logging.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/cnp_validator.php';
require_once APP_ROOT . '/includes/file_helper.php';
require_once APP_ROOT . '/includes/membri_alerts.php';
require_once APP_ROOT . '/includes/cotizatii_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

/**
 * Lista membri cu filtrare, cautare si paginare.
 *
 * @return array ['membri'=>[], 'total'=>int, 'total_pages'=>int, 'indicatori'=>[], 'membri_cu_avertizari'=>int, ...]
 */
function membri_list(PDO $pdo, array $filters, int $page, int $per_page): array {
    $status_filter = $filters['status'] ?? 'activi';
    $cautare = trim($filters['cautare'] ?? '');
    $sort_col = $filters['sort'] ?? 'dosarnr';
    $sort_dir_input = strtolower($filters['dir'] ?? 'asc');
    $avertizari_filter = !empty($filters['avertizari']);
    $aniversari_azi_filter = !empty($filters['aniversari_azi']);
    $actualizare_cnp_ci_filter = !empty($filters['actualizare_cnp_ci']);
    $cotizatie_neachitata_filter = !empty($filters['cotizatie_neachitata']);
    $fara_contact_filter = !empty($filters['fara_contact']);

    // Validare per_page
    if (!in_array($per_page, [10, 25, 50])) {
        $per_page = 25;
    }

    // Validare coloana sortare
    $allowed_sort_cols = ['dosarnr', 'nume', 'prenume', 'datanastere', 'ciseria', 'cinumar', 'telefonnev', 'hgrad'];
    if (!in_array($sort_col, $allowed_sort_cols)) {
        $sort_col = 'dosarnr';
    }
    $sort_dir = $sort_dir_input === 'desc' ? 'DESC' : 'ASC';

    // Construire query cu filtrare dupa status
    $where_parts = [];
    $params = [];

    if ($status_filter === 'suspendati') {
        $where_parts[] = "status_dosar IN ('Suspendat', 'Expirat')";
    } elseif ($status_filter === 'arhiva') {
        $where_parts[] = "status_dosar = 'Decedat'";
    } else {
        $where_parts[] = "status_dosar = 'Activ'";
    }

    if ($avertizari_filter) {
        $where_parts[] = "(
            status_dosar = 'Activ'
            AND (
                (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND cidataexp > CURDATE() AND (expira_ci_notificat IS NULL OR expira_ci_notificat = 0))
                OR (ceexp IS NOT NULL AND ceexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND ceexp > CURDATE() AND (expira_ch_notificat IS NULL OR expira_ch_notificat = 0))
            )
        )";
    }

    if ($aniversari_azi_filter) {
        $where_parts[] = "datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE()) AND DAY(datanastere) = DAY(CURDATE())";
    }

    if ($actualizare_cnp_ci_filter) {
        $where_parts[] = "(
            status_dosar = 'Activ'
            AND (
                cidataelib IS NULL
                OR cielib IS NULL OR cielib = ''
                OR cidataexp IS NULL
                OR cnp IS NULL OR cnp = '' OR LENGTH(cnp) != 13
                OR (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            )
        )";
    }

    if ($fara_contact_filter) {
        $where_parts[] = "status_dosar = 'Activ' AND (telefonnev IS NULL OR telefonnev = '') AND (email IS NULL OR email = '')";
    }

    // ID-uri membri scutiti de cotizatie (needed before WHERE for cotizatie_neachitata filter)
    $membri_scutiti_cotizatie_ids = [];
    try {
        $membri_scutiti_cotizatie_ids = cotizatii_membri_scutiti_ids($pdo);
    } catch (PDOException $e) {}

    // ID-uri membri cotizatie achitata + valori cotizatie
    $membri_cotizatie_achitata_an_curent = [];
    $valori_cotizatie_an_curent = [];
    try {
        cotizatii_ensure_tables($pdo);
        $an_curent = (int)date('Y');
        $membri_cotizatie_achitata_an_curent = incasari_membri_cotizatie_achitata_an($pdo, $an_curent);
        $rows_cot = $pdo->query("SELECT grad_handicap, asistent_personal, valoare_cotizatie FROM cotizatii_anuale WHERE anul = " . $an_curent)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows_cot as $r) {
            $key = $r['grad_handicap'] . '|' . ($r['asistent_personal'] ?? '');
            $valori_cotizatie_an_curent[$key] = (float)$r['valoare_cotizatie'];
            // Also keep grad-only key as fallback
            if (!isset($valori_cotizatie_an_curent[$r['grad_handicap']])) {
                $valori_cotizatie_an_curent[$r['grad_handicap']] = (float)$r['valoare_cotizatie'];
            }
        }
    } catch (PDOException $e) {}

    if ($cotizatie_neachitata_filter) {
        $where_parts[] = "status_dosar = 'Activ'";
        $excluded_ids = array_unique(array_merge($membri_scutiti_cotizatie_ids, $membri_cotizatie_achitata_an_curent));
        if (!empty($excluded_ids)) {
            $placeholders = implode(',', array_fill(0, count($excluded_ids), '?'));
            $where_parts[] = "id NOT IN ($placeholders)";
            foreach ($excluded_ids as $eid) {
                $params[] = (int)$eid;
            }
        }
    }

    if ($cautare !== '') {
        $where_parts[] = "(nume LIKE ? OR prenume LIKE ? OR cnp LIKE ? OR dosarnr LIKE ? OR telefonnev LIKE ? OR email LIKE ? OR domloc LIKE ? OR CONCAT(COALESCE(nume,''),' ',COALESCE(prenume,'')) LIKE ?)";
        $search_term = '%' . $cautare . '%';
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term, $search_term]);
    }

    $where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

    // Total si paginare
    $eroare_bd = '';
    $total_membri = 0;
    $total_pages = 0;
    $offset = 0;
    try {
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM membri $where");
        $count_stmt->execute($params);
        $total_membri = (int)$count_stmt->fetch()['total'];
        $total_pages = max(1, (int)ceil($total_membri / $per_page));
        $page = min($page, $total_pages);
        $offset = ($page - 1) * $per_page;
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if (strpos($error_msg, "doesn't exist") !== false || strpos($error_msg, "Unknown column") !== false) {
            $eroare_bd = 'Tabelul membri sau o coloana necesara nu exista. Rulati schema.sql in baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . ' (panou MySQL / phpMyAdmin).';
        }
    }

    // Indicatori
    $indicatori = membri_indicatori($pdo);

    // Calculare numar membri cu avertizari
    $membri_cu_avertizari = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE
            status_dosar = 'Activ'
            AND (
                (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND cidataexp > CURDATE() AND (expira_ci_notificat IS NULL OR expira_ci_notificat = 0))
                OR (ceexp IS NOT NULL AND ceexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND ceexp > CURDATE() AND (expira_ch_notificat IS NULL OR expira_ch_notificat = 0))
            )");
        $membri_cu_avertizari = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    // Numar membri actualizare CNP/CI
    $membri_actualizare_cnp_ci = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE
            status_dosar = 'Activ'
            AND (
                cidataelib IS NULL
                OR cielib IS NULL OR cielib = ''
                OR cidataexp IS NULL
                OR cnp IS NULL OR cnp = '' OR LENGTH(cnp) != 13
                OR (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            )");
        $membri_actualizare_cnp_ci = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    // Numar aniversari azi
    $membri_aniversari_azi_count = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE()) AND DAY(datanastere) = DAY(CURDATE())");
        $membri_aniversari_azi_count = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    // Numar arhiva membri (decedati)
    $membri_arhiva_count = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE status_dosar = 'Decedat'");
        $membri_arhiva_count = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    // Numar cotizatie neachitata
    $membri_cotizatie_neachitata_count = 0;
    try {
        $excluded_ids = array_unique(array_merge($membri_scutiti_cotizatie_ids, $membri_cotizatie_achitata_an_curent));
        if (!empty($excluded_ids)) {
            $placeholders = implode(',', array_fill(0, count($excluded_ids), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) as n FROM membri WHERE status_dosar = 'Activ' AND id NOT IN ($placeholders)");
            $stmt->execute(array_map('intval', $excluded_ids));
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE status_dosar = 'Activ'");
        }
        $membri_cotizatie_neachitata_count = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    // Numar fara contact
    $membri_fara_contact_count = 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE status_dosar = 'Activ' AND (telefonnev IS NULL OR telefonnev = '') AND (email IS NULL OR email = '')");
        $membri_fara_contact_count = (int) $stmt->fetch()['n'];
    } catch (PDOException $e) {}

    // Incarcare membri
    $membri = [];
    try {
        $order_by = $sort_col . ' ' . $sort_dir;
        if ($cautare !== '') {
            $order_by = "(CASE WHEN status_dosar = 'Activ' OR status_dosar IS NULL THEN 0 ELSE 1 END), " . $order_by;
        }
        $order_by .= ", nume ASC, prenume ASC";

        $sql = "SELECT id, dosarnr, status_dosar, nume, prenume, datanastere, ciseria, cinumar, telefonnev, email, cidataelib, cidataexp, ceexp, gdpr, cnp, sex, hgrad, expira_ci_notificat, expira_ch_notificat, cielib
                FROM membri
                $where
                ORDER BY $order_by
                LIMIT $per_page OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $membri = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if (strpos($error_msg, "doesn't exist") !== false || strpos($error_msg, "Unknown column") !== false) {
            $eroare_bd = 'Tabelul membri sau o coloana necesara nu exista in baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '. Rulati schema.sql si, daca e cazul, schema_update.sql. Eroare: ' . htmlspecialchars($error_msg);
        } else {
            $eroare_bd = 'Eroare la incarcarea membrilor: ' . htmlspecialchars($error_msg);
        }
    }

    return [
        'membri' => $membri,
        'total' => $total_membri,
        'total_pages' => $total_pages,
        'page' => $page,
        'per_page' => $per_page,
        'eroare_bd' => $eroare_bd,
        'indicatori' => $indicatori,
        'membri_cu_avertizari' => $membri_cu_avertizari,
        'membri_actualizare_cnp_ci' => $membri_actualizare_cnp_ci,
        'membri_aniversari_azi_count' => $membri_aniversari_azi_count,
        'membri_arhiva_count' => $membri_arhiva_count,
        'membri_cotizatie_neachitata_count' => $membri_cotizatie_neachitata_count,
        'membri_fara_contact_count' => $membri_fara_contact_count,
        'membri_scutiti_cotizatie_ids' => $membri_scutiti_cotizatie_ids,
        'membri_cotizatie_achitata_an_curent' => $membri_cotizatie_achitata_an_curent,
        'valori_cotizatie_an_curent' => $valori_cotizatie_an_curent,
        'sort_col' => $sort_col,
        'sort_dir' => $sort_dir,
    ];
}

/**
 * Calculeaza indicatorii (total, activi, grade handicap etc.)
 */
function membri_indicatori(PDO $pdo): array {
    $result = [
        'total_activi' => 0,
        'membri_activi_count' => 0,
        'membri_suspendati_expirati_count' => 0,
        'grad_grav' => 0,
        'grad_accentuat' => 0,
        'grad_mediu' => 0,
        'femei' => 0,
        'barbati' => 0,
    ];

    try {
        $result['total_activi'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri")->fetch()['total'];
        $result['membri_activi_count'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ'")->fetch()['total'];
        $result['membri_suspendati_expirati_count'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar IN ('Suspendat', 'Expirat')")->fetch()['total'];
        $result['grad_grav'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Grav'")->fetch()['total'];
        $result['grad_accentuat'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Accentuat'")->fetch()['total'];
        $result['grad_mediu'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Mediu'")->fetch()['total'];
        $result['femei'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Feminin'")->fetch()['total'];
        $result['barbati'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Masculin'")->fetch()['total'];
    } catch (PDOException $e) {
        try {
            $result['total_activi'] = (int)$pdo->query("SELECT COUNT(*) as total FROM membri")->fetch()['total'];
        } catch (PDOException $e2) {}
    }

    return $result;
}

/**
 * Obtine un membru dupa ID.
 *
 * @return array|null Datele membrului sau null daca nu exista
 */
function membri_get(PDO $pdo, int $id): ?array {
    if ($id <= 0) return null;

    try {
        $stmt = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
        $stmt->execute([$id]);
        $membru = $stmt->fetch(PDO::FETCH_ASSOC);
        return $membru ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Creeaza un membru nou.
 *
 * @return array ['success' => bool, 'error' => string|null, 'membru_id' => int|null]
 */
function membri_create(PDO $pdo, array $data, array $files = []): array {
    return membri_save($pdo, $data, $files, false);
}

/**
 * Actualizeaza un membru existent.
 *
 * @return array ['success' => bool, 'error' => string|null, 'membru_id' => int|null]
 */
function membri_update(PDO $pdo, int $id, array $data, array $files = []): array {
    $data['membru_id'] = $id;
    return membri_save($pdo, $data, $files, true);
}

/**
 * Sterge un membru (hard delete).
 *
 * @return array ['success' => bool, 'error' => string|null]
 */
function membri_delete(PDO $pdo, int $id): array {
    if ($id <= 0) {
        return ['success' => false, 'error' => 'ID membru invalid.'];
    }

    try {
        $membru = membri_get($pdo, $id);
        if (!$membru) {
            return ['success' => false, 'error' => 'Membrul nu exista.'];
        }

        $stmt = $pdo->prepare('DELETE FROM membri WHERE id = ?');
        $stmt->execute([$id]);

        $nume_complet = trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? ''));
        log_activitate($pdo, "membri: Sters membru ({$nume_complet})", null, $id);

        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la stergere: ' . $e->getMessage()];
    }
}

/**
 * Genereaza alertele pentru un membru (wrapper).
 *
 * @return array Lista de alerte
 */
function membri_alerts(PDO $pdo, int $id): array {
    $membru = membri_get($pdo, $id);
    if (!$membru) return [];

    return genereaza_alerts_membru_pentru_profil($membru, $pdo);
}

/**
 * Cauta membri (pentru API search).
 *
 * @return array Lista de membri gasiti
 */
function membri_search(PDO $pdo, string $query): array {
    $query = trim($query);
    if ($query === '') return [];

    try {
        $search_term = '%' . $query . '%';
        $stmt = $pdo->prepare("
            SELECT id, nume, prenume, cnp, dosarnr, telefonnev, email, hgrad
            FROM membri
            WHERE nume LIKE ? OR prenume LIKE ? OR cnp LIKE ? OR dosarnr LIKE ?
            ORDER BY nume ASC, prenume ASC
            LIMIT 20
        ");
        $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Obtine urmatorul numar de dosar disponibil.
 */
function membri_next_dosar_nr(PDO $pdo): string {
    try {
        $stmt = $pdo->query("SELECT MAX(CAST(dosarnr AS UNSIGNED)) AS max_dosar FROM membri");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (string)max(1, ((int)($row['max_dosar'] ?? 0)) + 1);
    } catch (PDOException $e) {
        return '1';
    }
}

/**
 * Obtine istoricul de modificari pentru un membru.
 */
function membri_istoric(PDO $pdo, int $id, ?array $membru = null): array {
    if ($id <= 0) return [];

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM log_activitate")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('membru_id', $cols, true)) {
            $stmt = $pdo->prepare("
                SELECT data_ora, utilizator, actiune
                FROM log_activitate
                WHERE membru_id = ?
                ORDER BY data_ora DESC
                LIMIT 50
            ");
            $stmt->execute([$id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Fallback: filtrare dupa nume
            if ($membru) {
                $nume_complet = trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? ''));
                if ($nume_complet !== '') {
                    $stmt = $pdo->prepare("
                        SELECT data_ora, utilizator, actiune
                        FROM log_activitate
                        WHERE actiune LIKE ?
                        ORDER BY data_ora DESC
                        LIMIT 50
                    ");
                    $stmt->execute(['%' . $nume_complet . '%']);
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
    } catch (PDOException $e) {}

    return [];
}

/**
 * Marcheaza/demarcheaza un alert ca informat.
 *
 * @return array ['success' => bool, 'error' => string|null]
 */
function membri_toggle_alert_informat(PDO $pdo, int $membru_id, string $alert_tip, bool $debifa): array {
    if ($membru_id <= 0 || !in_array($alert_tip, ['ci', 'ch', 'cotizatie'])) {
        return ['success' => false, 'error' => 'Date invalide.'];
    }

    try {
        if ($debifa) {
            $stmt = $pdo->prepare('DELETE FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
            $stmt->execute([$membru_id, $alert_tip]);
            if ($stmt->rowCount() > 0) {
                log_activitate($pdo, "membri: Avertisment {$alert_tip} debifat (membru nu mai e marcat ca informat) pentru membru ID {$membru_id}");
            }
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS membri_alerts_dismissed (
                id INT AUTO_INCREMENT PRIMARY KEY,
                membru_id INT NOT NULL,
                alert_tip VARCHAR(10) NOT NULL,
                data_informat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_membru_alert (membru_id, alert_tip),
                FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt_check = $pdo->prepare('SELECT id FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
            $stmt_check->execute([$membru_id, $alert_tip]);

            if (!$stmt_check->fetch()) {
                $stmt = $pdo->prepare('INSERT INTO membri_alerts_dismissed (membru_id, alert_tip) VALUES (?, ?)');
                $stmt->execute([$membru_id, $alert_tip]);
                log_activitate($pdo, "membri: Avertisment {$alert_tip} marcat ca informat pentru membru ID {$membru_id}");
            }
        }

        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        $msg = $debifa ? 'Eroare la debifarea avertismentului.' : 'Eroare la marcarea avertismentului.';
        return ['success' => false, 'error' => $msg . ' ' . $e->getMessage()];
    }
}

/**
 * Obtine date cotizatie si scutire pentru un membru.
 */
function membri_cotizatie_info(PDO $pdo, int $membru_id, ?array $membru = null): array {
    $scutire_cotizatie = null;
    $cotizatie_achitata_an_curent = false;
    $valoare_cotizatie_an = 0;

    try {
        $scutire_cotizatie = cotizatii_membru_este_scutit($pdo, $membru_id);
    } catch (Exception $e) {}

    try {
        $cotizatie_achitata_an_curent = !empty($scutire_cotizatie) || incasari_cotizatie_achitata_an($pdo, $membru_id, (int)date('Y'));
        $hgrad = $membru['hgrad'] ?? 'Fara handicap';
        $insotitor = $membru['insotitor'] ?? '';
        $asistent_personal = cotizatii_map_insotitor_to_asistent($insotitor);
        $valoare_cotizatie_an = incasari_valoare_cotizatie_anuala($pdo, (int)date('Y'), $hgrad, $asistent_personal);
    } catch (Exception $e) {}

    return [
        'scutire_cotizatie' => $scutire_cotizatie,
        'cotizatie_achitata_an_curent' => $cotizatie_achitata_an_curent,
        'valoare_cotizatie_an' => $valoare_cotizatie_an,
    ];
}

// =========================================================================
// INTERNAL: Salvare membru (creare sau actualizare)
// =========================================================================

/**
 * Logica interna de salvare (create/update).
 * Migrata din app/views/partials/membri_processing.php
 */
function membri_save(PDO $pdo, array $post_data, array $files, bool $is_update): array {
    $eroare = '';
    $membru_id = null;

    // Validare campuri obligatorii
    $nume = trim($post_data['nume'] ?? '');
    $prenume = trim($post_data['prenume'] ?? '');
    $cnp = preg_replace('/\D/', '', $post_data['cnp'] ?? '');

    if (empty($nume) || empty($prenume)) {
        return ['success' => false, 'error' => 'Numele si prenumele sunt obligatorii.', 'membru_id' => null];
    }

    // La actualizare: daca CNP-ul din formular e gol, pastram CNP-ul existent
    if ($is_update) {
        try {
            $stmt_cnp = $pdo->prepare('SELECT cnp FROM membri WHERE id = ?');
            $stmt_cnp->execute([(int)$post_data['membru_id']]);
            $cnp_existent = $stmt_cnp->fetchColumn();
            if (empty($cnp) && $cnp_existent !== null && $cnp_existent !== '') {
                $cnp = preg_replace('/\D/', '', $cnp_existent);
            }
        } catch (PDOException $e) {}
    }

    // CNP obligatoriu doar la adaugare
    if (!$is_update && empty($cnp)) {
        return ['success' => false, 'error' => 'CNP-ul este obligatoriu.', 'membru_id' => null];
    }

    // Validare CNP
    if ($is_update && empty($cnp)) {
        // Actualizare fara CNP - fara validare
    } elseif ($is_update) {
        try {
            $stmt_cnp = $pdo->prepare('SELECT cnp FROM membri WHERE id = ?');
            $stmt_cnp->execute([(int)$post_data['membru_id']]);
            $cnp_vechi = $stmt_cnp->fetchColumn();
            if ($cnp_vechi !== $cnp) {
                $validare_cnp = valideaza_cnp($cnp);
                if (!$validare_cnp['valid']) {
                    return ['success' => false, 'error' => $validare_cnp['error'], 'membru_id' => null];
                }
            }
        } catch (PDOException $e) {
            $validare_cnp = valideaza_cnp($cnp);
            if (!$validare_cnp['valid']) {
                return ['success' => false, 'error' => $validare_cnp['error'], 'membru_id' => null];
            }
        }
    } else {
        $validare_cnp = valideaza_cnp($cnp);
        if (!$validare_cnp['valid']) {
            return ['success' => false, 'error' => $validare_cnp['error'], 'membru_id' => null];
        }
    }

    // Extrage informatii din CNP
    $info_cnp = extrage_info_cnp($cnp);

    // For partial (per-card) updates: detect which card was submitted
    $card_submitted = $post_data['card'] ?? null;

    // For partial updates, load existing member data to merge
    $membru_existent_data = null;
    if ($is_update && $card_submitted) {
        $stmt_ex = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
        $stmt_ex->execute([(int)$post_data['membru_id']]);
        $membru_existent_data = $stmt_ex->fetch(PDO::FETCH_ASSOC);
        if (!$membru_existent_data) {
            return ['success' => false, 'error' => 'Membru nu exista in baza de date.', 'membru_id' => null];
        }
        // Merge: use POST data for submitted fields, keep existing data for the rest
        $all_fields = ['dosarnr','dosardata','status_dosar','nume','prenume','telefonnev','telefonapartinator',
            'nume_apartinator','prenume_apartinator','email','datanastere','locnastere','judnastere',
            'ciseria','cinumar','cielib','cidataelib','cidataexp','gdpr','codpost','tipmediuur',
            'domloc','judet_domiciliu','domstr','domnr','dombl','domsc','domet','domap','sex',
            'hgrad','hmotiv','diagnostic','hdur','insotitor','cnp','cenr','cedata','ceexp','primaria','notamembru'];
        foreach ($all_fields as $f) {
            if (!array_key_exists($f, $post_data)) {
                $post_data[$f] = $membru_existent_data[$f] ?? '';
            }
        }
        // Re-read merged values for validation
        $nume = trim($post_data['nume'] ?? '');
        $prenume = trim($post_data['prenume'] ?? '');
        $cnp = preg_replace('/\D/', '', $post_data['cnp'] ?? '');
        $info_cnp = extrage_info_cnp($cnp);
    }

    // Pregateste datele
    $dosarnr = trim($post_data['dosarnr'] ?? '') ?: null;
    $dosardata = !empty($post_data['dosardata']) ? date('Y-m-d', strtotime($post_data['dosardata'])) : null;
    $status_dosar = in_array($post_data['status_dosar'] ?? '', ['Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat']) ? $post_data['status_dosar'] : 'Activ';
    $telefonnev = trim($post_data['telefonnev'] ?? '') ?: null;
    $telefonapartinator = trim($post_data['telefonapartinator'] ?? '') ?: null;
    $nume_apartinator = trim($post_data['nume_apartinator'] ?? '') ?: null;
    $prenume_apartinator = trim($post_data['prenume_apartinator'] ?? '') ?: null;
    $email = trim($post_data['email'] ?? '') ?: null;
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Adresa de email nu este valida.', 'membru_id' => null];
    }

    $datanastere = !empty($post_data['datanastere']) ? date('Y-m-d', strtotime($post_data['datanastere'])) : ($info_cnp ? $info_cnp['data_nastere'] : null);
    $locnastere = trim($post_data['locnastere'] ?? '') ?: null;
    $judnastere = trim($post_data['judnastere'] ?? '') ?: null;
    $ciseria = trim($post_data['ciseria'] ?? '') ?: null;
    $cinumar_raw = preg_replace('/\D/', '', (string)($post_data['cinumar'] ?? ''));
    $cinumar_raw = $cinumar_raw !== '' ? substr($cinumar_raw, 0, 7) : '';
    $cinumar = $cinumar_raw !== '' ? $cinumar_raw : null;
    $cielib = trim($post_data['cielib'] ?? '') ?: null;
    $cidataelib = !empty($post_data['cidataelib']) ? date('Y-m-d', strtotime($post_data['cidataelib'])) : null;
    $cidataexp = !empty($post_data['cidataexp']) ? date('Y-m-d', strtotime($post_data['cidataexp'])) : null;
    $gdpr = isset($post_data['gdpr']) ? 1 : 0;
    $codpost = trim($post_data['codpost'] ?? '') ?: null;
    $tipmediuur = in_array($post_data['tipmediuur'] ?? '', ['Urban', 'Rural']) ? $post_data['tipmediuur'] : null;
    $domloc = trim($post_data['domloc'] ?? '') ?: null;
    $judet_domiciliu = trim($post_data['judet_domiciliu'] ?? '') ?: null;
    $domstr = trim($post_data['domstr'] ?? '') ?: null;
    $domnr = trim($post_data['domnr'] ?? '') ?: null;
    $dombl = trim($post_data['dombl'] ?? '') ?: null;
    $domsc = trim($post_data['domsc'] ?? '') ?: null;
    $domet = trim($post_data['domet'] ?? '') ?: null;
    $domap = trim($post_data['domap'] ?? '') ?: null;
    $sex = in_array($post_data['sex'] ?? '', ['Masculin', 'Feminin']) ? $post_data['sex'] : ($info_cnp ? $info_cnp['sex'] : null);
    $hgrad = in_array($post_data['hgrad'] ?? '', ['Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap']) ? $post_data['hgrad'] : null;
    $hmotiv = trim($post_data['hmotiv'] ?? '') ?: null;
    $diagnostic = trim($post_data['diagnostic'] ?? '') ?: null;
    $hdur = in_array($post_data['hdur'] ?? '', ['Permanent', 'Revizuibil']) ? $post_data['hdur'] : null;
    $insotitor_allowed = ['INDEMNIZATIE INSOTITOR', 'ASISTENT PERSONAL', 'FARA', 'NESPECIFICAT', '0'];
    $insotitor = in_array($post_data['insotitor'] ?? '', $insotitor_allowed) ? $post_data['insotitor'] : null;
    $cenr = trim($post_data['cenr'] ?? '') ?: null;
    $cedata = !empty($post_data['cedata']) ? date('Y-m-d', strtotime($post_data['cedata'])) : null;
    $ceexp = !empty($post_data['ceexp']) ? date('Y-m-d', strtotime($post_data['ceexp'])) : null;
    $primaria = trim($post_data['primaria'] ?? '') ?: null;
    $notamembru = trim($post_data['notamembru'] ?? '') ?: null;

    try {
        if ($is_update) {
            $membru_id = (int)$post_data['membru_id'];
            if ($membru_id <= 0) {
                return ['success' => false, 'error' => 'ID membru invalid.', 'membru_id' => null];
            }

            $stmt_old = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
            $stmt_old->execute([$membru_id]);
            $membru_vechi = $stmt_old->fetch(PDO::FETCH_ASSOC);

            if (!$membru_vechi) {
                return ['success' => false, 'error' => 'Membru nu exista in baza de date.', 'membru_id' => null];
            }

            $sql = 'UPDATE membri SET
                dosarnr = ?, dosardata = ?, status_dosar = ?, nume = ?, prenume = ?, telefonnev = ?, telefonapartinator = ?,
                nume_apartinator = ?, prenume_apartinator = ?, email = ?, datanastere = ?, locnastere = ?, judnastere = ?, ciseria = ?, cinumar = ?,
                cielib = ?, cidataelib = ?, cidataexp = ?, gdpr = ?, codpost = ?, tipmediuur = ?, domloc = ?, judet_domiciliu = ?, domstr = ?,
                domnr = ?, dombl = ?, domsc = ?, domet = ?, domap = ?, sex = ?, hgrad = ?, hmotiv = ?, diagnostic = ?,
                hdur = ?, insotitor = ?, cnp = ?, cenr = ?, cedata = ?, ceexp = ?, primaria = ?, notamembru = ?
                WHERE id = ?';

            $params = [
                $dosarnr, $dosardata, $status_dosar, $nume, $prenume, $telefonnev, $telefonapartinator,
                $nume_apartinator, $prenume_apartinator, $email, $datanastere, $locnastere, $judnastere, $ciseria, $cinumar,
                $cielib, $cidataelib, $cidataexp, $gdpr, $codpost, $tipmediuur, $domloc, $judet_domiciliu, $domstr,
                $domnr, $dombl, $domsc, $domet, $domap, $sex, $hgrad, $hmotiv, $diagnostic,
                $hdur, $insotitor, $cnp, $cenr, $cedata, $ceexp, $primaria, $notamembru, $membru_id
            ];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Log modificari
            $modificari = [];
            $nume_complet = trim($nume . ' ' . $prenume);
            if ($membru_vechi) {
                if (($membru_vechi['telefonnev'] ?? '') !== ($telefonnev ?? '')) {
                    $modificari[] = log_format_modificare('Numar de telefon', $membru_vechi['telefonnev'] ?? '', $telefonnev ?? '');
                }
                if (($membru_vechi['telefonapartinator'] ?? '') !== ($telefonapartinator ?? '')) {
                    $modificari[] = log_format_modificare('Telefon apartinator', $membru_vechi['telefonapartinator'] ?? '', $telefonapartinator ?? '');
                }
                if (($membru_vechi['email'] ?? '') !== ($email ?? '')) {
                    $modificari[] = log_format_modificare('Email', $membru_vechi['email'] ?? '', $email ?? '');
                }
                if (($membru_vechi['status_dosar'] ?? '') !== ($status_dosar ?? '')) {
                    $modificari[] = log_format_modificare('Status dosar', $membru_vechi['status_dosar'] ?? '', $status_dosar ?? '');
                }
                if (($membru_vechi['domloc'] ?? '') !== ($domloc ?? '')) {
                    $modificari[] = log_format_modificare('Locatie', $membru_vechi['domloc'] ?? '', $domloc ?? '');
                }
            }

            // Procesare fisiere
            if (isset($files['doc_ci']) && $files['doc_ci']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ci'], 'ci', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_old = $pdo->prepare('SELECT doc_ci FROM membri WHERE id = ?');
                    $stmt_old->execute([$membru_id]);
                    $old_file = $stmt_old->fetchColumn();
                    if ($old_file) {
                        sterge_fisier($old_file, 'ci');
                        log_activitate($pdo, "membri: Fisier CI sters: {$old_file} > {$result['filename']} / {$nume_complet}", null, $membru_id);
                    }
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ci = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                    log_activitate($pdo, "membri: Fisier CI incarcat: {$result['filename']} / {$nume_complet}", null, $membru_id);
                } elseif (!$result['success']) {
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => $membru_id];
                }
            }

            if (isset($files['doc_ch']) && $files['doc_ch']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ch'], 'ch', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_old = $pdo->prepare('SELECT doc_ch FROM membri WHERE id = ?');
                    $stmt_old->execute([$membru_id]);
                    $old_file = $stmt_old->fetchColumn();
                    if ($old_file) {
                        sterge_fisier($old_file, 'ch');
                        log_activitate($pdo, "membri: Fisier CH sters: {$old_file} > {$result['filename']} / {$nume_complet}", null, $membru_id);
                    }
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ch = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                    log_activitate($pdo, "membri: Fisier CH incarcat: {$result['filename']} / {$nume_complet}", null, $membru_id);
                } elseif (!$result['success']) {
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => $membru_id];
                }
            }

            if (!empty($modificari)) {
                log_activitate($pdo, "membri: " . implode("; ", $modificari) . " / {$nume_complet}", null, $membru_id);
            } else {
                log_activitate($pdo, "membri: Actualizat membru ({$nume_complet})", null, $membru_id);
            }

            // Returnam modificarile si numele complet pentru logging extern (registru_interactiuni_v2)
            $GLOBALS['_membri_save_modificari'] = $modificari;
            $GLOBALS['_membri_save_nume_complet'] = $nume_complet;
        } else {
            // Verificare CNP unic
            $stmt = $pdo->prepare('SELECT id FROM membri WHERE cnp = ?');
            $stmt->execute([$cnp]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Un membru cu acest CNP exista deja in baza de date.', 'membru_id' => null];
            }

            $sql = 'INSERT INTO membri (
                dosarnr, dosardata, status_dosar, nume, prenume, telefonnev, telefonapartinator,
                nume_apartinator, prenume_apartinator, email, datanastere, locnastere, judnastere, ciseria, cinumar,
                cielib, cidataelib, cidataexp, gdpr, codpost, tipmediuur, domloc, judet_domiciliu, domstr,
                domnr, dombl, domsc, domet, domap, sex, hgrad, hmotiv, diagnostic,
                hdur, insotitor, cnp, cenr, cedata, ceexp, primaria, notamembru
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $params = [
                $dosarnr, $dosardata, $status_dosar, $nume, $prenume, $telefonnev, $telefonapartinator,
                $nume_apartinator, $prenume_apartinator, $email, $datanastere, $locnastere, $judnastere, $ciseria, $cinumar,
                $cielib, $cidataelib, $cidataexp, $gdpr, $codpost, $tipmediuur, $domloc, $judet_domiciliu, $domstr,
                $domnr, $dombl, $domsc, $domet, $domap, $sex, $hgrad, $hmotiv, $diagnostic,
                $hdur, $insotitor, $cnp, $cenr, $cedata, $ceexp, $primaria, $notamembru
            ];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $membru_id = $pdo->lastInsertId();

            // Procesare fisiere pentru membru nou
            if (isset($files['doc_ci']) && $files['doc_ci']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ci'], 'ci', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ci = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                } elseif (!$result['success']) {
                    $pdo->prepare('DELETE FROM membri WHERE id = ?')->execute([$membru_id]);
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => null];
                }
            }

            if (isset($files['doc_ch']) && $files['doc_ch']['error'] === UPLOAD_ERR_OK) {
                $result = salveaza_fisier($files['doc_ch'], 'ch', $membru_id);
                if ($result['success'] && $result['filename']) {
                    $stmt_file = $pdo->prepare('UPDATE membri SET doc_ch = ? WHERE id = ?');
                    $stmt_file->execute([$result['filename'], $membru_id]);
                } elseif (!$result['success']) {
                    $pdo->prepare('DELETE FROM membri WHERE id = ?')->execute([$membru_id]);
                    return ['success' => false, 'error' => $result['error'], 'membru_id' => null];
                }
            }

            log_activitate($pdo, log_format_creare('membri', trim($nume . ' ' . $prenume)), null, $membru_id);
        }

        return ['success' => true, 'error' => null, 'membru_id' => $membru_id];

    } catch (PDOException $e) {
        error_log('Eroare MembriService: ' . $e->getMessage());
        return ['success' => false, 'error' => 'A aparut o eroare la salvare: ' . $e->getMessage(), 'membru_id' => $membru_id];
    } catch (Exception $e) {
        error_log('Eroare MembriService (Exception): ' . $e->getMessage());
        return ['success' => false, 'error' => 'A aparut o eroare neasteptata: ' . $e->getMessage(), 'membru_id' => $membru_id];
    }
}

/**
 * Obtine jurnalul de activitate complet pentru un membru.
 * Cauta dupa membru_id si dupa numele membrului in text.
 *
 * @return array Lista de intrari din jurnal
 */
function membri_jurnal_activitate(PDO $pdo, int $membru_id, int $limit = 100): array {
    if ($membru_id <= 0) return [];

    try {
        $membru = membri_get($pdo, $membru_id);
        if (!$membru) return [];

        $nume = trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? ''));
        $jurnal = [];

        // 1. Log activitate (editări, salvări, documente generate, etc.)
        $stmt = $pdo->prepare("
            SELECT 'log' AS sursa, actiune, utilizator, data_ora, NULL AS detalii_extra
            FROM log_activitate
            WHERE membru_id = ?
               OR actiune LIKE ?
               OR actiune LIKE ?
            ORDER BY data_ora DESC
            LIMIT " . (int)$limit);
        $stmt->execute([$membru_id, '%membru ID ' . $membru_id . '%', '%' . $pdo->quote($nume) . '%']);

        // Safer: use name directly
        $stmt2 = $pdo->prepare("
            SELECT 'log' AS sursa, actiune, utilizator, data_ora, NULL AS detalii_extra
            FROM log_activitate
            WHERE membru_id = ?
               OR actiune LIKE ?
            ORDER BY data_ora DESC
            LIMIT " . (int)$limit);
        $stmt2->execute([$membru_id, '%' . $nume . '%']);
        $jurnal = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 2. Interacțiuni din registru (apeluri, vizite) — căutare după nume persoană
        if ($nume !== '') {
            $stmt_int = $pdo->prepare("
                SELECT 'interactiune' AS sursa,
                       CONCAT(UPPER(tip), ': ', persoana, ' — ', COALESCE(s.nume, subiect_alt, '')) AS actiune,
                       r.utilizator,
                       r.data_ora,
                       CONCAT('Tip: ', tip, ' | Subiect: ', COALESCE(s.nume, r.subiect_alt, '-'), ' | Note: ', COALESCE(r.notite, '-')) AS detalii_extra
                FROM registru_interactiuni_v2 r
                LEFT JOIN registru_interactiuni_v2_subiecte s ON r.subiect_id = s.id
                WHERE r.persoana LIKE ?
                ORDER BY r.data_ora DESC
                LIMIT " . (int)$limit);
            $stmt_int->execute(['%' . $nume . '%']);
            $interactiuni = $stmt_int->fetchAll(PDO::FETCH_ASSOC);
            $jurnal = array_merge($jurnal, $interactiuni);
        }

        // 3. Documente generate — extrage din log cu link
        foreach ($jurnal as &$entry) {
            if ($entry['sursa'] === 'log' && stripos($entry['actiune'], 'Document generat') !== false) {
                $entry['sursa'] = 'document';
                // Extrage numele fișierului din actiune dacă e posibil
                if (preg_match('/Document generat\s*-\s*(.+?)(?:\s*\/|$)/', $entry['actiune'], $m)) {
                    $entry['detalii_extra'] = trim($m[1]);
                }
            }
        }
        unset($entry);

        // Sortează cronologic descendent
        usort($jurnal, function($a, $b) {
            return strtotime($b['data_ora'] ?? '0') - strtotime($a['data_ora'] ?? '0');
        });

        // Limită finală
        return array_slice($jurnal, 0, $limit);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Returneaza lista de documente generate pentru un membru.
 * Cauta in log_activitate dupa "Document generat" si in directorul documentegenerate/.
 *
 * @return array [['nume'=>string, 'data'=>string, 'url'=>string|null, 'utilizator'=>string], ...]
 */
function membri_documente_generate(PDO $pdo, int $membru_id, array $membru): array {
    if ($membru_id <= 0) return [];
    $documente = [];

    // 1. Search log_activitate for "Document generat" entries for this member
    try {
        $stmt = $pdo->prepare("
            SELECT actiune, utilizator, data_ora
            FROM log_activitate
            WHERE membru_id = ?
              AND actiune LIKE '%Document generat%'
            ORDER BY data_ora DESC
            LIMIT 100
        ");
        $stmt->execute([$membru_id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as $log) {
            $doc_name = $log['actiune'];
            // Try to extract filename from action text
            if (preg_match('/Document generat\s*[-:]\s*(.+?)(?:\s*\/\s*|$)/i', $log['actiune'], $m)) {
                $doc_name = trim($m[1]);
            }

            // Check if the file exists in documentegenerate/
            $file_url = null;
            $doc_dir = defined('APP_ROOT') ? APP_ROOT . '/documentegenerate' : '';
            if ($doc_dir && $doc_name) {
                // Try matching by the extracted name
                $possible_files = glob($doc_dir . '/*' . preg_replace('/[^a-zA-Z0-9_.-]/', '*', $doc_name) . '*.pdf');
                if (!empty($possible_files)) {
                    $file_url = 'documentegenerate/' . rawurlencode(basename($possible_files[0]));
                }
            }

            $documente[] = [
                'nume' => $doc_name,
                'data' => $log['data_ora'],
                'utilizator' => $log['utilizator'] ?? '',
                'url' => $file_url,
            ];
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    // 2. Also scan documentegenerate/ directory for files matching this member
    $doc_dir = defined('APP_ROOT') ? APP_ROOT . '/documentegenerate' : '';
    if ($doc_dir && is_dir($doc_dir)) {
        $nume = preg_replace('/\s+/', '', ($membru['nume'] ?? ''));
        $prenume = preg_replace('/\s+/', '', ($membru['prenume'] ?? ''));
        if ($nume || $prenume) {
            $pattern = $doc_dir . '/*' . $nume . $prenume . '*.pdf';
            foreach (glob($pattern) as $file_path) {
                $basename = basename($file_path);
                // Check if we already have this from the log
                $already_found = false;
                foreach ($documente as $d) {
                    if (strpos($d['nume'], $basename) !== false || strpos($basename, $d['nume']) !== false) {
                        $already_found = true;
                        break;
                    }
                }
                if (!$already_found) {
                    $documente[] = [
                        'nume' => $basename,
                        'data' => date('Y-m-d H:i:s', filemtime($file_path)),
                        'utilizator' => '',
                        'url' => 'documentegenerate/' . rawurlencode($basename),
                    ];
                }
            }
        }
    }

    return $documente;
}

/**
 * Calculeaza varsta din data nasterii.
 */
function membri_calculeaza_varsta($data_nastere) {
    if (empty($data_nastere)) return '-';
    $birth = new DateTime($data_nastere);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

// =========================================================================
// ATASAMENTE (attachment history)
// =========================================================================

/**
 * Lista atasamente pentru un membru, optional filtrate dupa tip.
 *
 * @return array Lista de atasamente
 */
function membri_atasamente_lista(PDO $pdo, int $membru_id, string $tip = null): array {
    if ($membru_id <= 0) return [];

    try {
        $sql = "SELECT * FROM membri_atasamente WHERE membru_id = ?";
        $params = [$membru_id];
        if ($tip !== null) {
            $sql .= " AND tip = ?";
            $params[] = $tip;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Adauga un atasament pentru un membru.
 *
 * @param PDO $pdo
 * @param int $membru_id
 * @param string $tip  certificat_handicap|act_identitate|alt_document
 * @param array $file  $_FILES element
 * @param string $nota  Nota/detalii
 * @param string $uploaded_by  Utilizatorul care a incarcat
 * @return array ['success' => bool, 'error' => string|null, 'id' => int|null]
 */
function membri_atasament_adauga(PDO $pdo, int $membru_id, string $tip, array $file, string $nota = '', string $uploaded_by = 'Sistem'): array {
    if ($membru_id <= 0) {
        return ['success' => false, 'error' => 'ID membru invalid.', 'id' => null];
    }

    $tipuri_valide = ['certificat_handicap', 'act_identitate', 'alt_document'];
    if (!in_array($tip, $tipuri_valide)) {
        return ['success' => false, 'error' => 'Tip atasament invalid.', 'id' => null];
    }

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Eroare la incarcarea fisierului.', 'id' => null];
    }

    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Fisierul depaseste limita de 5 MB.', 'id' => null];
    }

    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    if (!in_array($ext, $allowed_ext)) {
        return ['success' => false, 'error' => 'Tip de fisier nepermis. Permise: PDF, JPG, JPEG, PNG, DOC, DOCX.', 'id' => null];
    }

    // Create directory if not exists
    $upload_dir = APP_ROOT . '/uploads/membri_atasamente/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate filename: {membru_id}_{tip}_{timestamp}_{original_name}
    $original_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $filename = $membru_id . '_' . $tip . '_' . time() . '_' . $original_name;
    $destination = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Eroare la salvarea fisierului pe disc.', 'id' => null];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO membri_atasamente (membru_id, tip, fisier, nota, uploaded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$membru_id, $tip, $filename, $nota ?: null, $uploaded_by]);
        $id = (int)$pdo->lastInsertId();

        // Log activity
        $tip_label = ['certificat_handicap' => 'Certificat Handicap', 'act_identitate' => 'Act Identitate', 'alt_document' => 'Alt Document'][$tip] ?? $tip;
        log_activitate($pdo, "membri: Atasament incarcat ({$tip_label}): {$filename}", $uploaded_by, $membru_id);

        return ['success' => true, 'error' => null, 'id' => $id];
    } catch (PDOException $e) {
        // Clean up uploaded file on DB error
        if (file_exists($destination)) {
            unlink($destination);
        }
        return ['success' => false, 'error' => 'Eroare la salvarea in baza de date: ' . $e->getMessage(), 'id' => null];
    }
}

/**
 * Sterge un atasament dupa ID.
 *
 * @return bool True daca stergerea a reusit
 */
function membri_atasament_sterge(PDO $pdo, int $id): bool {
    if ($id <= 0) return false;

    try {
        // Get file info before deleting
        $stmt = $pdo->prepare("SELECT * FROM membri_atasamente WHERE id = ?");
        $stmt->execute([$id]);
        $atasament = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$atasament) return false;

        // Delete from DB
        $stmt = $pdo->prepare("DELETE FROM membri_atasamente WHERE id = ?");
        $stmt->execute([$id]);

        // Delete file from disk
        $filepath = APP_ROOT . '/uploads/membri_atasamente/' . $atasament['fisier'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Log activity
        $tip_label = ['certificat_handicap' => 'Certificat Handicap', 'act_identitate' => 'Act Identitate', 'alt_document' => 'Alt Document'][$atasament['tip']] ?? $atasament['tip'];
        log_activitate($pdo, "membri: Atasament sters ({$tip_label}): {$atasament['fisier']}", null, (int)$atasament['membru_id']);

        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Genereaza link de sortare pentru tabel.
 */
function membri_sort_link(string $col, string $label, string $current_col, string $current_dir, array $extra_params = []): string {
    $new_dir = ($current_col === $col && strtoupper($current_dir) === 'ASC') ? 'desc' : 'asc';
    $icon = '';
    if ($current_col === $col) {
        $icon = strtoupper($current_dir) === 'ASC' ? ' <i data-lucide="chevron-up" class="inline w-3 h-3"></i>' : ' <i data-lucide="chevron-down" class="inline w-3 h-3"></i>';
    }

    $params = array_merge($extra_params, ['sort' => $col, 'dir' => $new_dir]);
    $url = '/membri?' . http_build_query($params);
    return "<a href=\"{$url}\" class=\"hover:text-amber-600 dark:hover:text-amber-400\">{$label}{$icon}</a>";
}
