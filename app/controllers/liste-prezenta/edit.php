<?php
/**
 * Controller: Liste Prezenta — Editare lista de prezenta
 *
 * POST salveaza_lista: Actualizeaza lista cu participanti
 * GET: Formular editare lista
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ListePrezentaService.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /activitati'); exit; }

$data = liste_prezenta_load($pdo, $id);
if (!$data) { header('Location: /activitati'); exit; }

$lista = $data['lista'];
$participanti = $data['participanti'];
$coloane = json_decode($lista['coloane_selectate'] ?? '[]', true) ?: ['nr_crt','nume_prenume','semnatura'];

$eroare = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_lista'])) {
    csrf_require_valid();
    $result = liste_prezenta_update($pdo, $id, $_POST);

    if ($result['success']) {
        header('Location: /activitati?succes_lista=1');
        exit;
    } else {
        $eroare = $result['error'];
    }
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/liste-prezenta/edit.php';
