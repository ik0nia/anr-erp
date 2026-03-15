<?php
/**
 * Controller: Todo — Lista taskuri + operatii POST
 *
 * POST adauga_task: Creeaza task (din modal)
 * POST finalizeaza_task: Finalizeaza task
 * POST actualizeaza_task: Actualizeaza task (din modal edit)
 * POST reactivare_task: Reactiveaza task din istoric
 * GET: Afiseaza lista taskuri active + istoric
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/TaskService.php';

$eroare = '';
$succes = '';
$user_id = $_SESSION['user_id'] ?? null;
$utilizator = $_SESSION['utilizator'] ?? 'Sistem';

// --- POST: Adauga task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_task'])) {
    csrf_require_valid();
    $result = task_service_create($pdo, $_POST, $utilizator);
    if ($result['success']) {
        header('Location: /todo?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

// --- POST: Finalizeaza task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizeaza_task'])) {
    csrf_require_valid();
    $id = (int)($_POST['task_id'] ?? 0);
    if ($id > 0) {
        $result = task_finalize($pdo, $id, $user_id, $utilizator);
        if ($result['success']) {
            header('Location: /todo?succes=2');
            exit;
        }
        $eroare = $result['error'] ?? 'Eroare la finalizare.';
    }
}

// --- POST: Actualizeaza task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_task'])) {
    csrf_require_valid();
    $id = (int)($_POST['task_id'] ?? 0);
    if ($id > 0) {
        $result = task_update($pdo, $id, $_POST, $user_id, $utilizator);
        if ($result['success']) {
            $redirect = $_POST['redirect_after'] ?? '/todo';
            $target = (strpos($redirect, 'index') !== false || strpos($redirect, 'dashboard') !== false ? '/dashboard' : '/todo') . '?succes=4';
            header('Location: ' . $target);
            exit;
        }
        $eroare = $result['error'];
    }
}

// --- POST: Reactivare task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivare_task'])) {
    csrf_require_valid();
    $id = (int)($_POST['task_id'] ?? 0);
    if ($id > 0) {
        $result = task_reactivate($pdo, $id, $user_id, $utilizator);
        if ($result['success']) {
            header('Location: /todo?succes=3');
            exit;
        }
        $eroare = $result['error'] ?? 'Eroare la reactivare.';
    }
}

// --- GET: Mesaje succes ---
if (isset($_GET['succes'])) {
    $msg = [
        '1' => 'Taskul a fost adăugat.',
        '2' => 'Taskul a fost marcat ca finalizat.',
        '3' => 'Taskul a fost reactivat și trecut la sarcini active.',
        '4' => 'Taskul a fost actualizat cu succes.',
    ];
    $succes = $msg[$_GET['succes']] ?? 'Operațiune reușită.';
}

// --- GET: Incarca date ---
$eroare_bd = '';
$taskuri_active = [];
$taskuri_istoric = [];

try {
    $taskuri_active = task_list_active($pdo, $user_id);
    $taskuri_istoric = task_list_istoric($pdo, $user_id);
} catch (PDOException $e) {
    $eroare_bd = 'Tabelul taskuri nu există. Rulați schema_taskuri.sql.';
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/todo/index.php';
