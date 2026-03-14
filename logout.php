<?php
/**
 * Deconectare - CRM ANR Bihor
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';

// Log logout înainte de distrugerea sesiunii
if (!empty($_SESSION['username']) && !empty($_SESSION['utilizator'])) {
    log_activitate($pdo, "autentificare: Utilizator deconectat - {$_SESSION['username']} ({$_SESSION['utilizator']})", $_SESSION['username']);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
