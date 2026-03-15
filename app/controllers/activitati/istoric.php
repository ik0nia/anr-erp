<?php
/**
 * Controller: Activitati — Istoric activitati trecute
 *
 * GET: Afiseaza activitati trecute filtrate pe an
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ActivitatiService.php';

$eroare_bd = '';
$an = (int)($_GET['an'] ?? date('Y'));
$an = max(2020, min(2100, $an));

$data_result = activitati_istoric($pdo, $an);
$activitati = $data_result['activitati'];
$eroare_bd = $data_result['eroare'] ?? '';
$luni_ro = activitati_luni_ro();

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/activitati/istoric.php';
