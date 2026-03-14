<?php
/**
 * Procesare schimbare parolă (formular din meniul utilizator) - CRM ANR Bihor
 */
require_once __DIR__ . '/config.php';
require_once 'includes/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

csrf_require_valid();

$redirect = trim($_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php');
if ($redirect === '' || strpos($redirect, '//') !== false) {
    $redirect = 'index.php';
}

$parola_actuala = (string)($_POST['parola_actuala'] ?? '');
$parola_noua = (string)($_POST['parola_noua'] ?? '');
$parola_noua2 = (string)($_POST['parola_noua2'] ?? '');

if ($parola_noua !== $parola_noua2) {
    $_SESSION['schimba_parola_eroare'] = 'Parola nouă și confirmarea nu coincid.';
    header('Location: ' . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . 'deschide_schimba_parola=1');
    exit;
}

$rez = auth_schimba_parola($pdo, $_SESSION['user_id'], $parola_actuala, $parola_noua);
if ($rez['ok']) {
    $_SESSION['schimba_parola_succes'] = 'Parola a fost schimbată cu succes.';
    unset($_SESSION['schimba_parola_eroare']);
} else {
    $_SESSION['schimba_parola_eroare'] = $rez['mesaj'];
}
header('Location: ' . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . 'deschide_schimba_parola=1');
exit;
