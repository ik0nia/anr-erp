<?php
/**
 * Controller: Tickete — Lista tickete + adaugare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/tickete_helper.php';

$eroare = '';
$succes = '';
$eroare_bd = '';

// Ensure tables
try {
    tickete_ensure_tables($pdo);
} catch (Throwable $e) {
    $eroare_bd = 'Eroare la initializarea tabelelor.';
}

// POST: Adauga ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_ticket'])) {
    csrf_require_valid();
    $result = tickete_adauga($pdo, [
        'titlu' => $_POST['titlu'] ?? '',
        'departament' => $_POST['departament'] ?? '',
        'tip' => $_POST['tip'] ?? 'Solicitare',
        'prioritate' => $_POST['prioritate'] ?? 'Normal',
        'membru_id' => $_POST['membru_id'] ?? null,
        'nume_solicitant' => $_POST['nume_solicitant'] ?? '',
        'note' => $_POST['note'] ?? '',
        'creare_task' => !empty($_POST['creare_task']),
        'notifica_utilizatori' => !empty($_POST['notifica_utilizatori']),
    ]);
    if ($result['success']) {
        header('Location: /tickete?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

// Success messages from redirects
if (isset($_GET['succes'])) $succes = 'Ticketul a fost creat cu succes.';
if (isset($_GET['actualizat'])) $succes = 'Ticketul a fost actualizat cu succes.';
if (isset($_GET['raspuns'])) $succes = 'Raspunsul a fost inregistrat cu succes.';

// Filters
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['departament'])) $filters['departament'] = $_GET['departament'];
if (!empty($_GET['prioritate'])) $filters['prioritate'] = $_GET['prioritate'];
if (!empty($_GET['tip'])) $filters['tip'] = $_GET['tip'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

// Load data
try {
    $tickete = tickete_lista($pdo, $filters);
    $departamente = tickete_departamente_active($pdo);
} catch (Throwable $e) {
    $tickete = [];
    $departamente = [];
    $eroare_bd = 'Eroare la incarcarea ticketelor.';
}

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/tickete/index.php';
