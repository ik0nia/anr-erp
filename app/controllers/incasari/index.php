<?php
/**
 * Controller: Încasări — taburi „Încasări numerar” și „Toate încasările”
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/csrf_helper.php';
require_once APP_ROOT . '/includes/incasari_import_helper.php';

$tipuri_afisare = incasari_tipuri_afisare();
$moduri_plata_afisare = incasari_moduri_plata_afisare();

$tab = trim((string)($_GET['tab'] ?? 'numerar'));
if (!in_array($tab, ['numerar', 'toate', 'import_csv'], true)) {
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

$import_eroare = '';
$import_succes = '';
$import_step = 'upload'; // upload | map | done
$import_csv_data = null;
$import_mapare = null;
$campuri_import_incasari = incasari_import_available_fields();
$tip_import_implicit = incasari_import_normalize_tip((string)($_POST['tip_implicit'] ?? ''), null);
$an_cotizatie_implicit = isset($_POST['an_cotizatie_implicit']) ? (int)$_POST['an_cotizatie_implicit'] : (int)date('Y');
if (!in_array($an_cotizatie_implicit, [2025, 2026], true)) {
    $an_cotizatie_implicit = (int)date('Y');
}
if (!in_array($an_cotizatie_implicit, [2025, 2026], true)) {
    $an_cotizatie_implicit = 2026;
}
$skip_cotizatii_achitate = isset($_POST['skip_cotizatii_achitate']) ? !empty($_POST['skip_cotizatii_achitate']) : true;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($tab === 'import_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['incasari_import_upload'])) {
        csrf_require_valid();

        if (!isset($_FILES['fisier_csv']) || $_FILES['fisier_csv']['error'] !== UPLOAD_ERR_OK) {
            $import_eroare = 'Nu s-a selectat niciun fișier CSV sau a apărut o eroare la încărcare.';
        } else {
            $file = $_FILES['fisier_csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $import_eroare = 'Se acceptă doar fișiere CSV.';
            } elseif ((int)$file['size'] > 10 * 1024 * 1024) {
                $import_eroare = 'Fișierul depășește limita de 10 MB.';
            } else {
                $upload_dir = APP_ROOT . '/uploads/import/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0755, true);
                }
                $filename = 'incasari_import_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', uniqid()) . '.csv';
                $file_path = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['incasari_import_csv_path'] = $file_path;
                    $import_csv_data = incasari_import_parse_csv($file_path);
                    if (empty($import_csv_data['headers'])) {
                        $import_eroare = 'Nu s-au putut citi coloanele din fișierul CSV.';
                        @unlink($file_path);
                        unset($_SESSION['incasari_import_csv_path']);
                    } else {
                        $import_step = 'map';
                    }
                } else {
                    $import_eroare = 'Eroare la salvarea fișierului pe server.';
                }
            }
        }
    } elseif (isset($_POST['incasari_import_execute'])) {
        csrf_require_valid();

        $path = $_SESSION['incasari_import_csv_path'] ?? null;
        if (!$path || !file_exists($path)) {
            $import_eroare = 'Sesiunea de import a expirat. Încarcă din nou fișierul CSV.';
        } else {
            $import_csv_data = incasari_import_parse_csv($path);
            if (empty($import_csv_data['headers'])) {
                $import_eroare = 'Nu s-au putut citi datele din fișier.';
            } else {
                $import_mapare = [];
                if (isset($_POST['mapare_coloane']) && is_array($_POST['mapare_coloane'])) {
                    foreach ($_POST['mapare_coloane'] as $index => $db_field) {
                        $index = (int)$index;
                        $db_field = trim((string)$db_field);
                        if ($db_field !== '' && $db_field !== 'ignora') {
                            $import_mapare[$index] = $db_field;
                        }
                    }
                }

                if (empty($import_mapare)) {
                    $import_eroare = 'Mapează cel puțin o coloană din CSV.';
                    $import_step = 'map';
                } elseif (!in_array('dosarnr', $import_mapare, true)) {
                    $import_eroare = 'Maparea coloanei „Nr. dosar” este obligatorie.';
                    $import_step = 'map';
                } elseif (!in_array('tip_incasare', $import_mapare, true) && $tip_import_implicit === null) {
                    $import_eroare = 'Mapează „Tip încasare” sau selectează un tip implicit.';
                    $import_step = 'map';
                } else {
                    $utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
                    $result = incasari_import_execute($pdo, $import_csv_data['rows'], $import_mapare, [
                        'utilizator' => $utilizator,
                        'tip_implicit' => $tip_import_implicit,
                        'an_cotizatie_implicit' => $an_cotizatie_implicit,
                        'skip_cotizatii_achitate' => $skip_cotizatii_achitate,
                    ]);

                    $importate = (int)($result['importate'] ?? 0);
                    $negasite = (int)($result['negasite'] ?? 0);
                    $skip_achitate = (int)($result['skip_achitate'] ?? 0);
                    $erori_import = $result['erori'] ?? [];

                    if ($importate > 0) {
                        $import_succes = 'Import finalizat: ' . $importate . ' încasări importate.';
                        if ($negasite > 0) {
                            $import_succes .= ' ' . $negasite . ' dosare nu au fost găsite.';
                        }
                        if ($skip_achitate > 0) {
                            $import_succes .= ' ' . $skip_achitate . ' cotizații au fost omise deoarece erau deja achitate.';
                        }
                    } elseif ($negasite > 0 || $skip_achitate > 0) {
                        $import_eroare = 'Nu s-a importat nicio încasare. '
                            . ($negasite > 0 ? ($negasite . ' dosare negăsite. ') : '')
                            . ($skip_achitate > 0 ? ($skip_achitate . ' cotizații deja achitate.') : '');
                    }

                    if (!empty($erori_import)) {
                        $msg = implode('; ', array_slice($erori_import, 0, 10));
                        $extra = count($erori_import) - 10;
                        if ($extra > 0) {
                            $msg .= ' ... și încă ' . $extra . ' erori.';
                        }
                        $import_eroare = ($import_eroare !== '' ? $import_eroare . ' | ' : '') . 'Erori import: ' . $msg;
                    }

                    @unlink($path);
                    unset($_SESSION['incasari_import_csv_path']);
                    $import_step = 'done';
                }
            }
        }
    }
}

if ($tab === 'import_csv'
    && $import_step === 'upload'
    && empty($import_eroare)
    && !empty($_SESSION['incasari_import_csv_path'])
    && file_exists($_SESSION['incasari_import_csv_path'])
) {
    $import_csv_data = incasari_import_parse_csv((string)$_SESSION['incasari_import_csv_path']);
    if (!empty($import_csv_data['headers'])) {
        $import_step = 'map';
    }
}

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
    } elseif ($tab === 'toate') {
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
