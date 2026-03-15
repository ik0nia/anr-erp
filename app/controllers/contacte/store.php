<?php
/**
 * Controller: Contacte — Adaugare (Create)
 *
 * GET: Afiseaza formularul de adaugare
 * POST salveaza_contact: Valideaza si creeaza contactul
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ContacteService.php';

contacte_ensure_table($pdo);
$eroare = '';

// --- POST: Creare contact ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_contact'])) {
    csrf_require_valid();

    $result = contacte_create($pdo, $_POST, $_SESSION['utilizator'] ?? 'Sistem');

    if ($result['success']) {
        header('Location: /contacte?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

// --- GET: Date pentru view ---
$tipuri = contacte_tipuri();
$contact = $_POST ?: []; // repopuleaza formularul la eroare

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/contacte/adauga.php';
