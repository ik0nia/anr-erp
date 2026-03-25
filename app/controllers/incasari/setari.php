<?php
/**
 * Controller: Încasări — Setări serii chitanțe
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/csrf_helper.php';

$eroare = '';
$succes = '';

incasari_ensure_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_serii_incasari'])) {
    csrf_require_valid();

    $nr_start_donatii = max(1, (int)($_POST['nr_start_donatii'] ?? 1));
    $nr_start_incasari = max(1, (int)($_POST['nr_start_incasari'] ?? 1));

    $nr_curent_donatii = max($nr_start_donatii, (int)($_POST['nr_curent_donatii'] ?? $nr_start_donatii));
    $nr_curent_incasari = max($nr_start_incasari, (int)($_POST['nr_curent_incasari'] ?? $nr_start_incasari));

    $nr_final_donatii = max($nr_start_donatii, (int)($_POST['nr_final_donatii'] ?? $nr_curent_donatii));
    $nr_final_incasari = max($nr_start_incasari, (int)($_POST['nr_final_incasari'] ?? $nr_curent_incasari));

    incasari_salveaza_serie(
        $pdo,
        'donatii',
        trim((string)($_POST['serie_donatii'] ?? 'D')),
        $nr_start_donatii,
        $nr_curent_donatii,
        $nr_final_donatii
    );
    incasari_salveaza_serie(
        $pdo,
        'incasari',
        trim((string)($_POST['serie_incasari'] ?? 'INC')),
        $nr_start_incasari,
        $nr_curent_incasari,
        $nr_final_incasari
    );

    log_activitate($pdo, 'Încasări: serii și intervale chitanțe actualizate din pagina Încasări > Setări.');
    header('Location: /incasari/setari?succes_incasari=1');
    exit;
}

if (isset($_GET['succes_incasari'])) {
    $succes = 'Setările modulului Încasări au fost salvate.';
}

$incasari_serie_donatii = array_merge(
    incasari_get_serie($pdo, 'donatii') ?: [],
    ['nr_final' => incasari_get_serie_nr_final($pdo, 'donatii') ?: 0]
);
$incasari_serie_incasari = array_merge(
    incasari_get_serie($pdo, 'incasari') ?: [],
    ['nr_final' => incasari_get_serie_nr_final($pdo, 'incasari') ?: 0]
);

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/incasari/setari.php';

