<?php
/**
 * Controller: Contacte — Editare (Update)
 *
 * GET: Afiseaza formularul de editare pre-populat
 * POST actualizeaza_contact: Valideaza si actualizeaza contactul
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ContacteService.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: /contacte');
    exit;
}

contacte_ensure_table($pdo);
$eroare = '';

// Incarca contactul
$contact = contacte_get($pdo, $id);
if (!$contact) {
    header('Location: /contacte');
    exit;
}

// --- POST: Actualizare ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_contact'])) {
    csrf_require_valid();

    $result = contacte_update($pdo, $id, $_POST, $_SESSION['utilizator'] ?? 'Sistem');

    if ($result['success']) {
        header('Location: /contacte?succes=1');
        exit;
    }
    $eroare = $result['error'];
    // Repopuleaza cu datele trimise
    $contact = array_merge($contact, $_POST);
}

// --- GET: Date pentru view ---
$tipuri = contacte_tipuri();

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/contacte/edit.php';
