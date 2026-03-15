<?php
/**
 * Controller: Rapoarte — Indicatori, Interactiuni, Newsletter, Statistici
 *
 * GET: Afiseaza rapoarte read-only cu 4 tab-uri
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/RapoarteService.php';

// --- Determinare tab activ ---
$tab_rapoarte = 'membri';
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'newsletter') $tab_rapoarte = 'newsletter';
    elseif ($_GET['tab'] === 'interactiuni') $tab_rapoarte = 'interactiuni';
    elseif ($_GET['tab'] === 'statistici') $tab_rapoarte = 'statistici';
}

// --- Incarcare date din service ---
$lista_newsletter_rapoarte = [];
$raport_interactiuni = ['total_apeluri' => 0, 'total_vizite' => 0, 'total_general' => 0, 'categorii' => []];
$statistici_membri = null;
$statistici_localitati = null;

// Indicatori membri (necesari intotdeauna)
$indicatori = rapoarte_indicatori_membri($pdo);
$total_activi = $indicatori['total_activi'];
$grad_grav = $indicatori['grad_grav'];
$grad_grav_cu_asistent = $indicatori['grad_grav_cu_asistent'];
$grad_accentuat = $indicatori['grad_accentuat'];
$grad_mediu = $indicatori['grad_mediu'];
$femei = $indicatori['femei'];
$barbati = $indicatori['barbati'];
$membri_activi = $indicatori['membri_activi'];
$membri_suspendati = $indicatori['membri_suspendati'];
$membri_arhiva = $indicatori['membri_arhiva'];

if ($tab_rapoarte === 'newsletter') {
    $lista_newsletter_rapoarte = rapoarte_newsletter($pdo);
}

if ($tab_rapoarte === 'interactiuni') {
    $raport_interactiuni = rapoarte_interactiuni($pdo);
}

if ($tab_rapoarte === 'statistici') {
    $stat = rapoarte_statistici($pdo);
    $statistici_membri = $stat['statistici_membri'];
    $statistici_localitati = $stat['statistici_localitati'];
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/rapoarte/index.php';
