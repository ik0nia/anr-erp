<?php
/**
 * Controller: Încasări — taburi „Încasări numerar” și „Toate încasările”
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/csrf_helper.php';

$tipuri_afisare = incasari_tipuri_afisare();
$moduri_plata_afisare = incasari_moduri_plata_afisare();

$tab = trim((string)($_GET['tab'] ?? 'numerar'));
if (!in_array($tab, ['numerar', 'toate'], true)) {
    $tab = 'numerar';
}

$per_page = (int)($_GET['per_page'] ?? 50);
if (!in_array($per_page, [25, 50, 100], true)) {
    $per_page = 50;
}
$page = max(1, (int)($_GET['page'] ?? 1));

$azi = date('Y-m-d');
$prima_zi_luna = date('Y-m-01');

// Filtre tab „Încasări numerar”
$tip_filtru = trim((string)($_GET['tip'] ?? ''));
$serie_filtru = trim((string)($_GET['serie'] ?? ''));
$data_de_la = trim((string)($_GET['data_de_la'] ?? ''));
$data_pana_la = trim((string)($_GET['data_pana_la'] ?? ''));
$cautare = trim((string)($_GET['q'] ?? ''));
if ($data_de_la === '') $data_de_la = $prima_zi_luna;
if ($data_pana_la === '') $data_pana_la = $azi;
$afiseaza_resetare_filtre = isset($_GET['tip']) || isset($_GET['serie']) || isset($_GET['data_de_la']) || isset($_GET['data_pana_la']) || isset($_GET['q']) || isset($_GET['per_page']) || isset($_GET['page']);

// Filtre tab „Toate încasările”
$all_tip_filtru = trim((string)($_GET['all_tip'] ?? ''));
$all_user_filtru = trim((string)($_GET['all_user'] ?? ''));
$all_mod_filtru = trim((string)($_GET['all_mod'] ?? ''));
$all_data_de_la = trim((string)($_GET['all_data_de_la'] ?? ''));
$all_data_pana_la = trim((string)($_GET['all_data_pana_la'] ?? ''));
if ($all_data_de_la === '') $all_data_de_la = $prima_zi_luna;
if ($all_data_pana_la === '') $all_data_pana_la = $azi;
$afiseaza_resetare_filtre_toate = isset($_GET['all_tip']) || isset($_GET['all_user']) || isset($_GET['all_mod']) || isset($_GET['all_data_de_la']) || isset($_GET['all_data_pana_la']) || isset($_GET['per_page']) || isset($_GET['page']);

$incasari = [];
$incasari_toate = [];
$total = 0;
$total_pages = 1;
$total_suma_afisata = 0.0;
$total_chitante_afisate = 0;
$total_toate = 0;
$total_pages_toate = 1;
$total_suma_toate = 0.0;
$total_documente_toate = 0;
$serie_options = [];
$utilizator_options = [];

try {
    incasari_ensure_tables($pdo);
    ensure_contacte_table($pdo);

    $serie_donatii = trim((string)((incasari_get_serie($pdo, 'donatii')['serie'] ?? '')));
    $serie_incasari = trim((string)((incasari_get_serie($pdo, 'incasari')['serie'] ?? '')));
    foreach ([$serie_donatii, $serie_incasari] as $serie_cfg) {
        if ($serie_cfg !== '' && !in_array($serie_cfg, $serie_options, true)) {
            $serie_options[] = $serie_cfg;
        }
    }
    if ($serie_filtru !== '' && !in_array($serie_filtru, $serie_options, true)) {
        $serie_filtru = '';
    }

    $stmt_users = $pdo->query("SELECT DISTINCT created_by FROM incasari WHERE created_by IS NOT NULL AND created_by <> '' ORDER BY created_by ASC");
    $utilizator_options = $stmt_users ? $stmt_users->fetchAll(PDO::FETCH_COLUMN) : [];

    $tipuri_permise = [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE];

    if ($tab === 'numerar') {
        $where = [];
        $params = [];

        // Borderou încasări numerar + mandat poștal (metode care emit chitanță ERP în fluxul curent)
        $where[] = '(i.mod_plata = ? OR i.mod_plata = ?)';
        $params[] = INCASARI_MOD_NUMERAR;
        $params[] = INCASARI_MOD_MANDAT_POSTAL;

        if ($tip_filtru !== '' && in_array($tip_filtru, $tipuri_permise, true)) {
            $where[] = 'i.tip = ?';
            $params[] = $tip_filtru;
        }
        if ($serie_filtru !== '') {
            $where[] = 'i.seria_chitanta = ?';
            $params[] = $serie_filtru;
        }
        if ($data_de_la !== '') {
            $where[] = 'i.data_incasare >= ?';
            $params[] = $data_de_la;
        }
        if ($data_pana_la !== '') {
            $where[] = 'i.data_incasare <= ?';
            $params[] = $data_pana_la;
        }
        if ($cautare !== '') {
            $where[] = '(COALESCE(m.nume, c.nume, \'\') LIKE ? OR COALESCE(m.prenume, c.prenume, \'\') LIKE ? OR i.seria_chitanta LIKE ?)';
            $params[] = "%{$cautare}%";
            $params[] = "%{$cautare}%";
            $params[] = "%{$cautare}%";
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $count_sql = "SELECT COUNT(*) FROM incasari i LEFT JOIN membri m ON m.id = i.membru_id LEFT JOIN contacte c ON c.id = i.contact_id {$where_sql}";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $total_pages = max(1, (int)ceil($total / $per_page));
        if ($page > $total_pages) $page = $total_pages;

        $offset = ($page - 1) * $per_page;
        $sql = "
            SELECT i.*,
                   COALESCE(m.nume, c.nume) AS nume,
                   COALESCE(m.prenume, c.prenume) AS prenume
            FROM incasari i
            LEFT JOIN membri m ON m.id = i.membru_id
            LEFT JOIN contacte c ON c.id = i.contact_id
            {$where_sql}
            ORDER BY i.data_incasare DESC, i.id DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $incasari = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($incasari as $incasare) {
            $total_suma_afisata += (float)($incasare['suma'] ?? 0);
            if (!empty($incasare['seria_chitanta'])) {
                $total_chitante_afisate++;
            }
        }
    } else {
        $where_toate = [];
        $params_toate = [];

        if ($all_tip_filtru !== '' && in_array($all_tip_filtru, $tipuri_permise, true)) {
            $where_toate[] = 'i.tip = ?';
            $params_toate[] = $all_tip_filtru;
        }
        if ($all_user_filtru !== '' && in_array($all_user_filtru, $utilizator_options, true)) {
            $where_toate[] = 'i.created_by = ?';
            $params_toate[] = $all_user_filtru;
        }
        if ($all_mod_filtru !== '' && array_key_exists($all_mod_filtru, $moduri_plata_afisare)) {
            $where_toate[] = 'i.mod_plata = ?';
            $params_toate[] = $all_mod_filtru;
        }
        if ($all_data_de_la !== '') {
            $where_toate[] = 'i.data_incasare >= ?';
            $params_toate[] = $all_data_de_la;
        }
        if ($all_data_pana_la !== '') {
            $where_toate[] = 'i.data_incasare <= ?';
            $params_toate[] = $all_data_pana_la;
        }

        $where_sql_toate = !empty($where_toate) ? 'WHERE ' . implode(' AND ', $where_toate) : '';

        if (($_GET['export'] ?? '') === 'csv') {
            $sql_export = "
                SELECT i.*,
                       COALESCE(m.nume, c.nume) AS nume,
                       COALESCE(m.prenume, c.prenume) AS prenume
                FROM incasari i
                LEFT JOIN membri m ON m.id = i.membru_id
                LEFT JOIN contacte c ON c.id = i.contact_id
                {$where_sql_toate}
                ORDER BY i.data_incasare DESC, i.id DESC
            ";
            $stmt_export = $pdo->prepare($sql_export);
            $stmt_export->execute($params_toate);
            $rows = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="incasari_filtrate_' . date('Ymd_His') . '.csv"');
            $out = fopen('php://output', 'w');
            if ($out !== false) {
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Data', 'Tip încasare', 'Persoană', 'Sumă (RON)', 'Metodă plată', 'Chitanță', 'Reprezentând', 'Înregistrat de'], ',');
                foreach ($rows as $row) {
                    $nume = trim((string)($row['nume'] ?? '') . ' ' . (string)($row['prenume'] ?? ''));
                    $chitanta = !empty($row['seria_chitanta']) ? ((string)$row['seria_chitanta'] . ' nr. ' . (int)$row['nr_chitanta']) : '';
                    fputcsv($out, [
                        (string)($row['data_incasare'] ?? ''),
                        (string)($tipuri_afisare[$row['tip']] ?? $row['tip']),
                        $nume,
                        number_format((float)($row['suma'] ?? 0), 2, '.', ''),
                        (string)($moduri_plata_afisare[$row['mod_plata']] ?? $row['mod_plata']),
                        $chitanta,
                        (string)($row['reprezentand'] ?? ''),
                        (string)($row['created_by'] ?? ''),
                    ], ',');
                }
                fclose($out);
            }
            exit;
        }

        $count_sql_toate = "SELECT COUNT(*) FROM incasari i {$where_sql_toate}";
        $stmt_count_toate = $pdo->prepare($count_sql_toate);
        $stmt_count_toate->execute($params_toate);
        $total_toate = (int)$stmt_count_toate->fetchColumn();
        $total_pages_toate = max(1, (int)ceil($total_toate / $per_page));
        if ($page > $total_pages_toate) $page = $total_pages_toate;

        $offset_toate = ($page - 1) * $per_page;
        $sql_toate = "
            SELECT i.*,
                   COALESCE(m.nume, c.nume) AS nume,
                   COALESCE(m.prenume, c.prenume) AS prenume
            FROM incasari i
            LEFT JOIN membri m ON m.id = i.membru_id
            LEFT JOIN contacte c ON c.id = i.contact_id
            {$where_sql_toate}
            ORDER BY i.data_incasare DESC, i.id DESC
            LIMIT {$per_page} OFFSET {$offset_toate}
        ";
        $stmt_toate = $pdo->prepare($sql_toate);
        $stmt_toate->execute($params_toate);
        $incasari_toate = $stmt_toate->fetchAll(PDO::FETCH_ASSOC);
        foreach ($incasari_toate as $incasare) {
            $total_suma_toate += (float)($incasare['suma'] ?? 0);
            if (!empty($incasare['seria_chitanta'])) {
                $total_documente_toate++;
            }
        }
    }
} catch (PDOException $e) {
    error_log('Eroare încasări: ' . $e->getMessage());
}

function build_incasari_url($params = []) {
    $p = array_merge($_GET, $params);
    $p['page'] = $p['page'] ?? 1;
    return '/incasari?' . http_build_query($p);
}

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/incasari/index.php';
