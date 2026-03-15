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

// --- POST: Salvare mesaj precompletat ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mesaj_precompletat'])) {
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
    $redirect = '/membri' . (count($params) ? '?' . http_build_query($params) : '');
    header('Location: ' . $redirect);
    exit;
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
$status_filter = $_GET['status'] ?? 'activi';
$avertizari_filter = isset($_GET['avertizari']) && $_GET['avertizari'] == '1';
$aniversari_azi_filter = isset($_GET['aniversari_azi']) && $_GET['aniversari_azi'] == '1';
$actualizare_cnp_ci_filter = isset($_GET['actualizare_cnp_ci']) && $_GET['actualizare_cnp_ci'] == '1';

$filters = [
    'status' => $status_filter,
    'cautare' => $cautare,
    'sort' => $sort_col,
    'dir' => $sort_dir,
    'avertizari' => $avertizari_filter,
    'aniversari_azi' => $aniversari_azi_filter,
    'actualizare_cnp_ci' => $actualizare_cnp_ci_filter,
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
$membri_scutiti_cotizatie_ids = $data['membri_scutiti_cotizatie_ids'];
$membri_cotizatie_achitata_an_curent = $data['membri_cotizatie_achitata_an_curent'];
$valori_cotizatie_an_curent = $data['valori_cotizatie_an_curent'];

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/membri/index.php';
