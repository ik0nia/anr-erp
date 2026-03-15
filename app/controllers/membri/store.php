<?php
/**
 * Controller: Membri — Adaugare (Create)
 *
 * GET: Afiseaza formularul de adaugare
 * POST adauga_membru: Valideaza si creeaza membrul
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/MembriService.php';

$eroare = '';

// --- POST: Creare membru ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_membru'])) {
    csrf_require_valid();

    $result = membri_create($pdo, $_POST, $_FILES);

    if ($result['success']) {
        header('Location: /membri?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

// --- GET: Date pentru view ---
$next_dosar_nr = membri_next_dosar_nr($pdo);
$membru = $_POST ?: [];

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/membri/form.php';
