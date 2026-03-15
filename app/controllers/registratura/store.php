<?php
/**
 * Controller: Registratura — Adaugare inregistrare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/RegistraturaService.php';

$eroare = '';
$redirect_param = isset($_GET['redirect']) && $_GET['redirect'] === 'dashboard' ? 'dashboard' : 'registratura';
$redirect_url = $redirect_param === 'dashboard' ? 'index.php' : 'registratura.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_registratura'])) {
    csrf_require_valid();

    $result = registratura_create($pdo, $_POST, $_SESSION['utilizator'] ?? 'Utilizator');

    if ($result['success']) {
        header('Location: registratura-sumar.php?id=' . (int)$result['id'] . '&redirect=' . urlencode($redirect_param));
        exit;
    }
    $eroare = $result['error'];
}

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/registratura/adauga.php';
