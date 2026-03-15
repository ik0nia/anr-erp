<?php
/**
 * Controller: Todo — Editare task + trimitere notificare
 *
 * GET: Afiseaza formularul de editare pre-populat
 * POST actualizeaza_task: Actualizeaza taskul
 * POST trimite_notificare: Creeaza notificare din task
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/TaskService.php';
require_once APP_ROOT . '/includes/notificari_helper.php';

$eroare = '';
$succes = '';
$task_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? null;
$utilizator = $_SESSION['utilizator'] ?? 'Sistem';

if ($task_id <= 0) {
    header('Location: /todo');
    exit;
}

// Incarca taskul
$task = task_get($pdo, $task_id, $user_id);
if (!$task) {
    header('Location: /todo');
    exit;
}

// --- POST: Actualizare task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_task'])) {
    csrf_require_valid();

    $result = task_update($pdo, $task_id, $_POST, $user_id, $utilizator);

    if ($result['success']) {
        $succes = 'Taskul a fost actualizat cu succes.';
        // Reincarca taskul actualizat
        $task = task_get($pdo, $task_id, $user_id);
    } else {
        $eroare = $result['error'];
    }
}

// --- POST: Trimitere notificare ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trimite_notificare'])) {
    csrf_require_valid();
    notificari_ensure_tables($pdo);

    $titlu = 'Task: ' . $task['nume'];
    $continut = "Task: " . $task['nume'] . "\n\n";
    $continut .= "Data și ora: " . date(DATETIME_FORMAT, strtotime($task['data_ora'])) . "\n";
    if (!empty($task['detalii'])) {
        $continut .= "\nDetalii:\n" . $task['detalii'] . "\n";
    }
    $continut .= "\nNivel urgență: " . ucfirst($task['nivel_urgenta']);

    $importanta_map = ['important' => 'Important', 'reprogramat' => 'Informativ'];
    $importanta = $importanta_map[$task['nivel_urgenta']] ?? 'Normal';

    $notif_id = notificari_adauga($pdo, [
        'titlu' => $titlu,
        'importanta' => $importanta,
        'continut' => $continut,
        'trimite_email' => 0,
    ], null, $user_id);

    if ($notif_id > 0) {
        log_activitate($pdo, "Notificare creată din task: {$task['nume']} (ID notificare: {$notif_id})", $utilizator);
        $succes = 'Notificarea a fost creată cu succes pentru toți utilizatorii.';
    } else {
        $eroare = 'Eroare la crearea notificării.';
    }
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/todo/edit.php';
