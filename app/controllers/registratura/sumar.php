<?php
/**
 * Controller: Registratura — Sumar dupa salvare inregistrare
 *
 * GET: Afiseaza detaliile unei inregistrari din registratura (parametri: id, redirect)
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/db_helper.php';

$id = (int)($_GET['id'] ?? 0);
$redirect_param = isset($_GET['redirect']) && $_GET['redirect'] === 'dashboard' ? 'dashboard' : 'registratura';
$redirect_url = $redirect_param === 'dashboard' ? '/dashboard' : '/registratura';

if ($id <= 0) {
    header('Location: ' . $redirect_url);
    exit;
}

try {
    $r = db_fetch_one($pdo, 'SELECT * FROM registratura WHERE id = ?', [$id]);
} catch (PDOException $e) {
    $r = null;
}

if (!$r) {
    header('Location: ' . $redirect_url);
    exit;
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/registratura/sumar.php';
