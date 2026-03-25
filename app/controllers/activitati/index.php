<?php
/**
 * Controller: Activitati — Calendar activitati + adaugare + status
 *
 * POST adauga_activitate: Creeaza activitate
 * POST actualizeaza_status: Schimba status activitate
 * GET: Calendar activitati cu liste prezenta
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ActivitatiService.php';
require_once APP_ROOT . '/app/services/ListePrezentaService.php';

$eroare = '';
$succes = '';
$eroare_bd = '';
$utilizator = $_SESSION['utilizator'] ?? 'Sistem';
$afiseaza_tot = isset($_GET['afiseaza_tot']);

// Migrare schema
activitati_ensure_schema($pdo);

// Utilizatori pentru select responsabili
$utilizatori_platforma = activitati_utilizatori($pdo);

// --- POST: Adauga activitate ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_activitate'])) {
    csrf_require_valid();
    $result = activitati_create($pdo, $_POST, $utilizator);
    if ($result['success']) {
        $redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? '/activitati');
        if (empty($redirect) || strpos($redirect, '//') !== false) $redirect = '/activitati';
        header('Location: ' . $redirect . '?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

// --- POST: Actualizeaza status ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_status'])) {
    csrf_require_valid();
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id > 0 && $status) {
        $result = activitati_update_status($pdo, $id, $status, $utilizator);
        if ($result['success']) {
            header('Location: /activitati' . ($afiseaza_tot ? '?afiseaza_tot=1' : '') . '&succes_status=1');
            exit;
        }
        $eroare = $result['error'];
    }
}

// --- POST: Stergere lista prezenta ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_lista_prezenta'])) {
    csrf_require_valid();
    $lista_id = (int)($_POST['lista_id'] ?? 0);
    $redirect_afiseaza_tot = !empty($_POST['afiseaza_tot']) ? '?afiseaza_tot=1' : '';
    if ($lista_id > 0) {
        $result_sterge = liste_prezenta_delete($pdo, $lista_id, $utilizator);
        if ($result_sterge['success']) {
            $sep = $redirect_afiseaza_tot ? '&' : '?';
            header('Location: /activitati' . $redirect_afiseaza_tot . $sep . 'succes_lista_stearsa=1');
            exit;
        }
        $eroare = $result_sterge['error'] ?: 'Nu s-a putut șterge lista.';
    }
}

// --- GET: Incarca date ---
$ziua_curenta = date('Y-m-d');
if ($afiseaza_tot) {
    $data_start = date('Y-m-d', strtotime('-10 years'));
    $data_end = date('Y-m-d', strtotime('+365 days'));
} else {
    $data_start = $ziua_curenta;
    $data_end = date('Y-m-d', strtotime('+365 days'));
}

$data_result = activitati_list($pdo, $data_start, $data_end);
$activitati = $data_result['activitati'];
if ($data_result['eroare']) $eroare_bd = $data_result['eroare'];

$liste_prezenta = activitati_liste_prezenta($pdo);
$luni_ro = activitati_luni_ro();
$deschide_formular = (!empty($eroare) && isset($_POST['adauga_activitate'])) || isset($_GET['adauga']);

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/activitati/index.php';
