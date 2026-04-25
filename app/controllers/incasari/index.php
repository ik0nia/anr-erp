<?php
/**
 * Controller: Încasări — Lista tuturor încasărilor cu paginare și filtrare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/incasari_import_helper.php';

// --- GET: Parametri ---
$valid_tabs = ['lista', 'import_csv'];
$tab = isset($_GET['tab']) && in_array((string)$_GET['tab'], $valid_tabs, true) ? (string)$_GET['tab'] : 'lista';
$per_page = (int)($_GET['per_page'] ?? 50);
if (!in_array($per_page, [25, 50, 100])) $per_page = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$tip_filtru = trim($_GET['tip'] ?? '');
$serie_filtru = trim($_GET['serie'] ?? '');
$data_de_la = trim($_GET['data_de_la'] ?? '');
$data_pana_la = trim($_GET['data_pana_la'] ?? '');
$cautare = trim($_GET['q'] ?? '');
$azi = date('Y-m-d');
$prima_zi_luna = date('Y-m-01');
if ($data_de_la === '') {
    $data_de_la = $prima_zi_luna;
}
if ($data_pana_la === '') {
    $data_pana_la = $azi;
}

$incasari = [];
$total = 0;
$total_pages = 1;
$total_suma_afisata = 0.0;
$total_chitante_afisate = 0;
$afiseaza_resetare_filtre = isset($_GET['tip']) || isset($_GET['serie']) || isset($_GET['data_de_la']) || isset($_GET['data_pana_la']) || isset($_GET['q']) || isset($_GET['per_page']) || isset($_GET['page']);
$serie_options = [];
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

    $where = [];
    $params = [];

    if ($tip_filtru !== '' && in_array($tip_filtru, [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE])) {
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

    // Count total
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
} catch (PDOException $e) {
    error_log('Eroare încasări: ' . $e->getMessage());
}

$tipuri_afisare = incasari_tipuri_afisare();
$moduri_plata_afisare = incasari_moduri_plata_afisare();

// Helper URL paginare
function build_incasari_url($params = []) {
    $p = array_merge($_GET, $params);
    $p['page'] = $p['page'] ?? 1;
    return '/incasari?' . http_build_query($p);
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/incasari/index.php';
