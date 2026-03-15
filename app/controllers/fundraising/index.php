<?php
/**
 * Controller: Fundraising — Formular 230 si Lista donatori
 *
 * GET: Afiseaza tab-urile Formular 230 (link) si Lista donatori
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['formular230', 'donatori'], true) ? $_GET['tab'] : 'formular230';

$lista_donatori = [];
if ($tab === 'donatori') {
    incasari_ensure_tables($pdo);
    $lista_donatori = incasari_lista_donatori($pdo, 1000);
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/fundraising/index.php';
