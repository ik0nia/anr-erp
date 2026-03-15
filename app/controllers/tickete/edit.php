<?php
/**
 * Controller: Tickete — Editare ticket
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/tickete_helper.php';

$eroare = '';
$succes = '';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: /tickete');
    exit;
}

tickete_ensure_tables($pdo);

$ticket = tickete_get($pdo, $id);
if (!$ticket) {
    header('Location: /tickete');
    exit;
}

// POST: Actualizeaza ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_ticket'])) {
    csrf_require_valid();
    $result = tickete_actualizeaza($pdo, $id, [
        'titlu' => $_POST['titlu'] ?? '',
        'departament' => $_POST['departament'] ?? '',
        'tip' => $_POST['tip'] ?? 'Solicitare',
        'prioritate' => $_POST['prioritate'] ?? 'Normal',
        'status' => $_POST['status'] ?? 'Nou',
        'note' => $_POST['note'] ?? '',
        'raspuns_final' => $_POST['raspuns_final'] ?? '',
        'nume_solicitant' => $_POST['nume_solicitant'] ?? '',
        'membru_id' => $_POST['membru_id'] ?? null,
    ]);
    if ($result['success']) {
        // Logging is handled by tickete_actualizeaza() in the helper
        header('Location: /tickete?actualizat=1');
        exit;
    }
    $eroare = $result['error'];
    // Re-load ticket after failed update
    $ticket = tickete_get($pdo, $id);
}

// POST: Trimite raspuns
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trimite_raspuns'])) {
    csrf_require_valid();
    $raspuns = trim($_POST['raspuns_final'] ?? '');
    if ($raspuns === '') {
        $eroare = 'Raspunsul nu poate fi gol.';
    } else {
        $result = tickete_trimite_raspuns($pdo, $id, $raspuns);
        if ($result['success']) {
            header('Location: /tickete?raspuns=1');
            exit;
        }
        $eroare = $result['error'];
    }
    // Re-load ticket
    $ticket = tickete_get($pdo, $id);
}

// Load departments for dropdown
$departamente = tickete_departamente_active($pdo);

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/tickete/edit.php';
