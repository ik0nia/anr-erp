<?php
/**
 * Controller: Newsletter — Vizualizare newsletter trimis
 *
 * GET: Afiseaza continutul unui newsletter trimis (pe baza id-ului)
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/newsletter_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /rapoarte');
    exit;
}

$nl = newsletter_get_by_id($pdo, $id);
if (!$nl) {
    header('Location: /rapoarte');
    exit;
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/newsletter/view.php';
