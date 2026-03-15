<?php
/**
 * Controller: Liste Prezenta — Creare lista de prezenta / tabel nominal
 *
 * POST salveaza_lista: Creaza lista cu participanti
 * GET: Formular creare lista
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ListePrezentaService.php';

$eroare = '';
$succes = '';
$din_activitate = isset($_GET['din_activitate']);
$activitate_nume = trim($_GET['nume'] ?? '');
$activitate_data = trim($_GET['data'] ?? date('Y-m-d'));
$activitate_ora = trim($_GET['ora'] ?? '09:00');
$activitate_locatie = trim($_GET['locatie'] ?? '');
$activitate_responsabili = trim($_GET['responsabili'] ?? ($_SESSION['utilizator'] ?? ''));

$activitati_select = liste_prezenta_activitati_select($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_lista'])) {
    csrf_require_valid();
    $user = $_SESSION['utilizator'] ?? 'Sistem';
    $result = liste_prezenta_create($pdo, $_POST, $user);

    if ($result['success']) {
        $lista_id = $result['lista_id'];
        $act = $_POST['actiune_dupa'] ?? '';
        if ($act === 'print') {
            header('Location: lista-prezenta-print.php?id=' . $lista_id);
            exit;
        }
        if ($act === 'pdf') {
            header('Location: lista-prezenta-pdf.php?id=' . $lista_id);
            exit;
        }
        header('Location: /activitati?succes_lista=1');
        exit;
    } else {
        $eroare = $result['error'];
    }
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/liste-prezenta/create.php';
