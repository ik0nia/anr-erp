<?php
/**
 * Controller: Notificare View — Vizualizare, Arhivare, Marcheaza necitit, Adauga task
 *
 * GET: Afiseaza notificarea si o marcheaza ca citita
 * POST arhiveaza: Arhiveaza notificarea si redirecteaza
 * POST marcheaza_necitit: Marcheaza ca necitita si redirecteaza
 * POST adauga_task: Creeaza task din notificare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/NotificariService.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($id <= 0 || $user_id <= 0) {
    header('Location: /notificari');
    exit;
}

notificari_ensure_tables($pdo);
$notif = notificari_get_by_id($pdo, $id, $user_id);
if (!$notif) {
    header('Location: /notificari');
    exit;
}

$succes = '';
$eroare = '';
$redirect_after = '/notificari/view?id=' . $id;

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();
    if (isset($_POST['arhiveaza'])) {
        notificari_arhiveaza($pdo, $id, $user_id);
        log_activitate($pdo, "Notificare arhivată: {$notif['titlu']}");
        $redirect_after = trim($_POST['redirect'] ?? '/notificari');
        header('Location: ' . $redirect_after . '?arhivat=1');
        exit;
    }
    if (isset($_POST['marcheaza_necitit'])) {
        notificari_marcheaza_necitita($pdo, $id, $user_id);
        log_activitate($pdo, "Notificare marcată ca necitită: {$notif['titlu']}");
        header('Location: /notificari?necitit=1');
        exit;
    }
    if (isset($_POST['adauga_task'])) {
        $task_id = notificari_adauga_la_taskuri($pdo, $id, $user_id);
        if ($task_id) {
            log_activitate($pdo, "Task creat din notificare: {$notif['titlu']}");
            $succes = 'Taskul a fost adăugat la lista de taskuri.';
        } else {
            $eroare = 'Eroare la crearea taskului.';
        }
    }
}

// La deschidere (GET): marcheaza ca citita daca era noua
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    notificari_marcheaza_citita($pdo, $id, $user_id);
}
$notif = notificari_get_by_id($pdo, $id, $user_id);

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/notificari/view.php';
