<?php
/**
 * Controller: Membri — Lista cu filtre, cautare, paginare
 *
 * GET: Afiseaza lista membri
 * POST save_mesaj_precompletat: Salveaza mesaj pentru WhatsApp/Email
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/MembriService.php';

$eroare = '';
$succes = '';

// --- Print View (pagina curata, fara layout ERP) ---
if (isset($_GET['print']) && $_GET['print'] === '1') {
    $all_members = membri_lista_all($pdo, $_GET);
    include APP_ROOT . '/app/views/membri/print.php';
    exit;
}

// --- CSV Export (inainte de orice output HTML) ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $all_members = membri_lista_all($pdo, $_GET);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="membri_export_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['Dosar Nr', 'Nume', 'Prenume', 'CNP', 'Data Nastere', 'Telefon', 'Email', 'Localitate', 'Status', 'Grad Handicap']);
    foreach ($all_members as $m) {
        fputcsv($out, [
            $m['dosarnr'] ?? '',
            $m['nume'] ?? '',
            $m['prenume'] ?? '',
            $m['cnp'] ?? '',
            $m['datanastere'] ?? '',
            $m['telefonnev'] ?? '',
            $m['email'] ?? '',
            $m['domloc'] ?? '',
            $m['status_dosar'] ?? '',
            $m['hgrad'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// --- POST handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adaugare membru nou (formular modal din pagina lista)
    if (isset($_POST['adauga_membru'])) {
        csrf_require_valid();
        $result = membri_create($pdo, $_POST, $_FILES);
        if (!empty($result['success'])) {
            header('Location: /membri?succes=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Membrul nu a putut fi salvat.');
    } elseif (isset($_POST['save_mesaj_precompletat'])) {
        csrf_require_valid();
        $_SESSION['membri_mesaj_subiect'] = trim($_POST['mesaj_subiect'] ?? '');
        $_SESSION['membri_mesaj_continut'] = trim($_POST['mesaj_continut'] ?? '');
        $params = [];
        if (isset($_POST['redirect_status'])) $params['status'] = $_POST['redirect_status'];
        if (!empty($_POST['redirect_sort'])) $params['sort'] = $_POST['redirect_sort'];
        if (!empty($_POST['redirect_dir'])) $params['dir'] = $_POST['redirect_dir'];
        if (!empty($_POST['redirect_per_page'])) $params['per_page'] = $_POST['redirect_per_page'];
        if (!empty($_POST['redirect_page'])) $params['page'] = $_POST['redirect_page'];
        if (isset($_POST['redirect_cautare'])) $params['cautare'] = $_POST['redirect_cautare'];
        if (!empty($_POST['redirect_avertizari'])) $params['avertizari'] = $_POST['redirect_avertizari'];
        if (!empty($_POST['redirect_actualizare_cnp_ci'])) $params['actualizare_cnp_ci'] = $_POST['redirect_actualizare_cnp_ci'];
        if (!empty($_POST['redirect_aniversari_azi'])) $params['aniversari_azi'] = $_POST['redirect_aniversari_azi'];
        if (!empty($_POST['redirect_cotizatie_neachitata'])) $params['cotizatie_neachitata'] = $_POST['redirect_cotizatie_neachitata'];
        if (!empty($_POST['redirect_fara_contact'])) $params['fara_contact'] = $_POST['redirect_fara_contact'];
        $redirect = '/membri' . (count($params) ? '?' . http_build_query($params) : '');
        header('Location: ' . $redirect);
        exit;
    }
}

// Reset mesaj precompletat
if (isset($_GET['reset_mesaj']) && $_GET['reset_mesaj'] == '1') {
    unset($_SESSION['membri_mesaj_subiect'], $_SESSION['membri_mesaj_continut']);
}

// Afisare mesaj succes dupa redirect
if (isset($_GET['succes']) && $_GET['succes'] == '1') {
    $succes = 'Membrul a fost adaugat cu succes.';
}

// --- GET: Parametri ---
$sort_col = $_GET['sort'] ?? 'dosarnr';
$sort_dir = $_GET['dir'] ?? 'asc';
$cautare = trim($_GET['cautare'] ?? '');
$per_page = (int)($_GET['per_page'] ?? 25);
$page = max(1, (int)($_GET['page'] ?? 1));
$status_filter = $_GET['status'] ?? 'toti';
$avertizari_filter = isset($_GET['avertizari']) && $_GET['avertizari'] == '1';
$aniversari_azi_filter = isset($_GET['aniversari_azi']) && $_GET['aniversari_azi'] == '1';
$actualizare_cnp_ci_filter = isset($_GET['actualizare_cnp_ci']) && $_GET['actualizare_cnp_ci'] == '1';
$cotizatie_neachitata_filter = isset($_GET['cotizatie_neachitata']) && $_GET['cotizatie_neachitata'] == '1';
$fara_contact_filter = isset($_GET['fara_contact']) && $_GET['fara_contact'] == '1';

// Filtrele speciale sunt mutual exclusive — doar unul poate fi activ
$special_filters_active = array_filter([
    'avertizari' => $avertizari_filter,
    'aniversari_azi' => $aniversari_azi_filter,
    'actualizare_cnp_ci' => $actualizare_cnp_ci_filter,
    'cotizatie_neachitata' => $cotizatie_neachitata_filter,
    'fara_contact' => $fara_contact_filter,
]);
if (count($special_filters_active) > 1) {
    // Pastreaza doar ultimul filtru setat (cel mai recent din URL)
    $last_key = array_key_last($special_filters_active);
    $avertizari_filter = ($last_key === 'avertizari');
    $aniversari_azi_filter = ($last_key === 'aniversari_azi');
    $actualizare_cnp_ci_filter = ($last_key === 'actualizare_cnp_ci');
    $cotizatie_neachitata_filter = ($last_key === 'cotizatie_neachitata');
    $fara_contact_filter = ($last_key === 'fara_contact');
}

$filters = [
    'status' => $status_filter,
    'cautare' => $cautare,
    'sort' => $sort_col,
    'dir' => $sort_dir,
    'avertizari' => $avertizari_filter,
    'aniversari_azi' => $aniversari_azi_filter,
    'actualizare_cnp_ci' => $actualizare_cnp_ci_filter,
    'cotizatie_neachitata' => $cotizatie_neachitata_filter,
    'fara_contact' => $fara_contact_filter,
    // Filtre avansate (status_dosar avansat override tab status)
    'sex' => $_GET['sex'] ?? '',
    'hgrad' => $_GET['hgrad'] ?? '',
    'status_dosar' => !empty($_GET['status_dosar']) ? $_GET['status_dosar'] : '',
    'localitate' => $_GET['localitate'] ?? '',
    'mediu' => $_GET['mediu'] ?? '',
    'data_nastere_de_la' => $_GET['data_nastere_de_la'] ?? '',
    'data_nastere_pana_la' => $_GET['data_nastere_pana_la'] ?? '',
];

// --- Date pentru view ---
$data = membri_list($pdo, $filters, $page, $per_page);

$membri = $data['membri'];
$total_membri = $data['total'];
$total_pages = $data['total_pages'];
$page = $data['page'];
$per_page = $data['per_page'];
$eroare_bd = $data['eroare_bd'];
$sort_col = $data['sort_col'];
$sort_dir = $data['sort_dir'];

// Indicatori
$indicatori = $data['indicatori'];
$total_activi = $indicatori['total_activi'];
$membri_activi_count = $indicatori['membri_activi_count'];
$membri_suspendati_expirati_count = $indicatori['membri_suspendati_expirati_count'];

// Avertizari / cotizatii
$membri_cu_avertizari = $data['membri_cu_avertizari'];
$membri_actualizare_cnp_ci = $data['membri_actualizare_cnp_ci'];
$membri_aniversari_azi_count = $data['membri_aniversari_azi_count'];
$membri_arhiva_count = $data['membri_arhiva_count'];
$membri_cotizatie_neachitata_count = $data['membri_cotizatie_neachitata_count'];
$membri_fara_contact_count = $data['membri_fara_contact_count'];
$membri_scutiti_cotizatie_ids = $data['membri_scutiti_cotizatie_ids'];
$membri_cotizatie_achitata_an_curent = $data['membri_cotizatie_achitata_an_curent'];
$valori_cotizatie_an_curent = $data['valori_cotizatie_an_curent'];

// Urmatorul numar de dosar disponibil (pentru formularul de adaugare membru)
$next_dosarnr = membri_next_dosar_nr($pdo);
$GLOBALS['next_dosarnr'] = $next_dosarnr;

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/membri/index.php';
