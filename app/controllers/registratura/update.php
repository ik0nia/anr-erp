<?php
/**
 * Controller: Registratura — Editare inregistrare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/RegistraturaService.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: registratura.php');
    exit;
}

$eroare = '';
$r = registratura_get($pdo, $id);
if (!$r) {
    header('Location: registratura.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_registratura'])) {
    csrf_require_valid();

    $result = registratura_update($pdo, $id, $_POST, $_SESSION['utilizator'] ?? 'Sistem');

    if ($result['success']) {
        header('Location: registratura.php?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

$r = $r ?: [];
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/registratura/edit.php';
