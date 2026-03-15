<?php
/**
 * Controller: Todo — Adaugare task (pagina standalone, din Dashboard)
 *
 * GET: Afiseaza formular adaugare
 * POST adauga_task: Valideaza si creeaza taskul, redirect la sursa
 */
ob_start();
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/TaskService.php';

$eroare = '';

// --- POST: Creare task ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_task'])) {
    csrf_require_valid();

    $result = task_service_create($pdo, $_POST, $_SESSION['utilizator'] ?? 'Sistem');

    if ($result['success']) {
        $redirect = trim($_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php');
        if (empty($redirect) || strpos($redirect, '//') !== false) {
            $redirect = 'index.php';
        }
        $redirect_url = $redirect . '?succes_task=1';
        while (ob_get_level()) { ob_end_clean(); }
        if (!headers_sent()) {
            header('Location: ' . $redirect_url);
            exit;
        }
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><p>Redirecționare…</p></body></html>';
        exit;
    }
    $eroare = $result['error'];
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/todo/adauga.php';
