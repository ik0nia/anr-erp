<?php
/**
 * Controller: Registru Interacțiuni v2 — Vizualizare statistici și interacțiuni recente
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/RegistruInteractiuniService.php';

$eroare = '';
$succes = '';

// Obține anul pentru statistici (default: anul curent)
$an_selectat = isset($_GET['an']) ? (int)$_GET['an'] : date('Y');
if ($an_selectat < 2020 || $an_selectat > 2100) {
    $an_selectat = date('Y');
}

$data = registru_interactiuni_load_data($pdo, $an_selectat);

$statistici_lunare = $data['statistici_lunare'];
$statistici_subiecte = $data['statistici_subiecte'];
$interactiuni_recente_30_zile = $data['interactiuni_recente_30_zile'];
$interactiuni_azi = $data['interactiuni_azi'];

// Nume lunilor în română
$luni = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/registru-interactiuni-v2/index.php';
