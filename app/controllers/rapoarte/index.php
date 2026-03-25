<?php
/**
 * Controller: Rapoarte — Indicatori, Interactiuni, Newsletter, Statistici
 *
 * GET: Afiseaza rapoarte read-only cu 4 tab-uri
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/RapoarteService.php';
require_once APP_ROOT . '/includes/membri_legitimatii_helper.php';
membri_legitimatii_ensure_table($pdo);

// --- Determinare tab activ ---
$tab_rapoarte = 'membri';
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'newsletter') $tab_rapoarte = 'newsletter';
    elseif ($_GET['tab'] === 'interactiuni') $tab_rapoarte = 'interactiuni';
    elseif ($_GET['tab'] === 'statistici') $tab_rapoarte = 'statistici';
    elseif ($_GET['tab'] === 'socializare') $tab_rapoarte = 'socializare';
    elseif ($_GET['tab'] === 'borderou-legitimatii') $tab_rapoarte = 'borderou-legitimatii';
}

// --- Incarcare date din service ---
$lista_newsletter_rapoarte = [];
$raport_interactiuni = ['total_apeluri' => 0, 'total_vizite' => 0, 'total_general' => 0, 'categorii' => []];
$statistici_membri = null;
$statistici_localitati = null;
$an_socializare_selectat = (int)($_GET['an'] ?? date('Y'));
$raport_socializare = null;
$ani_socializare_disponibili = [(int)date('Y')];
$borderou_legitimatii = null;
$borderou_legitimatii_data_de_la = date('Y-01-01');
$borderou_legitimatii_data_pana_la = date('Y-m-d');
$borderou_legitimatii_rows = [];
$borderou_legitimatii_stats = ['total' => 0, 'nou' => 0, 'plina' => 0, 'pierduta' => 0];

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

if ($tab_rapoarte === 'socializare') {
    if ($an_socializare_selectat < 2000 || $an_socializare_selectat > 2100) {
        $an_socializare_selectat = (int)date('Y');
    }
    $raport_socializare = rapoarte_socializare($pdo, $an_socializare_selectat);
    $an_socializare_selectat = (int)($raport_socializare['an_selectat'] ?? $an_socializare_selectat);
    $ani_socializare_disponibili = $raport_socializare['ani_disponibili'] ?? $ani_socializare_disponibili;
}

if ($tab_rapoarte === 'borderou-legitimatii') {
    $input_de_la = trim((string)($_GET['de_la'] ?? ''));
    $input_pana_la = trim((string)($_GET['pana_la'] ?? ''));
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_de_la)) {
        $borderou_legitimatii_data_de_la = $input_de_la;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_pana_la)) {
        $borderou_legitimatii_data_pana_la = $input_pana_la;
    }
    if ($borderou_legitimatii_data_de_la > $borderou_legitimatii_data_pana_la) {
        $tmp = $borderou_legitimatii_data_de_la;
        $borderou_legitimatii_data_de_la = $borderou_legitimatii_data_pana_la;
        $borderou_legitimatii_data_pana_la = $tmp;
    }
    $borderou_legitimatii = rapoarte_borderou_legitimatii($pdo, $borderou_legitimatii_data_de_la, $borderou_legitimatii_data_pana_la);
    $borderou_legitimatii_rows = $borderou_legitimatii['operatiuni'] ?? [];
    $borderou_legitimatii_stats = $borderou_legitimatii['statistici'] ?? $borderou_legitimatii_stats;
    log_activitate(
        $pdo,
        'rapoarte: vizualizare Borderou legitimatii de membru, interval ' .
        date(DATE_FORMAT, strtotime($borderou_legitimatii_data_de_la)) . ' - ' .
        date(DATE_FORMAT, strtotime($borderou_legitimatii_data_pana_la))
    );
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/rapoarte/index.php';
