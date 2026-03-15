<?php
/**
 * Controller: Modul Comunicare > Printing
 * Etichete si Scrisori batch pentru membri.
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ComunicareService.php';

$eroare = '';
$succes = '';
$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
$user_id = $_SESSION['user_id'] ?? null;

// --- Tab selection ---
$valid_tabs = ['etichete', 'scrisori'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $valid_tabs) ? $_GET['tab'] : 'etichete';

// Rezultat generare
$rezultat_generare = null;

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();

    // Colecteaza filtrele din POST
    $filters = [
        'status'              => $_POST['status'] ?? '',
        'localitate'          => $_POST['localitate'] ?? '',
        'sex'                 => $_POST['sex'] ?? '',
        'mediu'               => $_POST['mediu'] ?? '',
        'hgrad'               => $_POST['hgrad'] ?? '',
        'data_nastere_de_la'  => $_POST['data_nastere_de_la'] ?? '',
        'data_nastere_pana_la'=> $_POST['data_nastere_pana_la'] ?? '',
        'cotizatie_neachitata'=> !empty($_POST['cotizatie_neachitata']),
    ];

    if (isset($_POST['genereaza_etichete'])) {
        $latime_mm  = (float)($_POST['latime_mm'] ?? 89);
        $inaltime_mm = (float)($_POST['inaltime_mm'] ?? 36);

        $membri = comunicare_filtreaza_membri($pdo, $filters);

        if (empty($membri)) {
            $eroare = 'Nu au fost gasiti membri cu filtrele selectate.';
        } else {
            $result = comunicare_genereaza_etichete_pdf($membri, $latime_mm, $inaltime_mm);

            if ($result['success']) {
                comunicare_log_batch($pdo, $membri, 'etichete', $utilizator);
                log_activitate($pdo, 'Comunicare: Generat ' . count($membri) . ' etichete', $utilizator);
                $rezultat_generare = $result;
                $succes = 'Au fost generate ' . count($membri) . ' etichete cu succes.';
            } else {
                $eroare = $result['error'] ?? 'Eroare la generarea etichetelor.';
            }
        }
    } elseif (isset($_POST['genereaza_scrisori'])) {
        $template_id = (int)($_POST['template_id'] ?? 0);

        if ($template_id <= 0) {
            $eroare = 'Selectati un template pentru scrisori.';
        } else {
            $membri = comunicare_filtreaza_membri($pdo, $filters);

            if (empty($membri)) {
                $eroare = 'Nu au fost gasiti membri cu filtrele selectate.';
            } else {
                $result = comunicare_genereaza_scrisori_pdf($pdo, $membri, $template_id);

                if ($result['success']) {
                    comunicare_log_batch($pdo, $membri, 'scrisoare', $utilizator);
                    log_activitate($pdo, 'Comunicare: Generat ' . ($result['count'] ?? count($membri)) . ' scrisori din template #' . $template_id, $utilizator);
                    $rezultat_generare = $result;
                    $succes = 'Au fost generate ' . ($result['count'] ?? count($membri)) . ' scrisori cu succes.';
                } else {
                    $eroare = $result['error'] ?? 'Eroare la generarea scrisorilor.';
                }
            }
        }
        $tab = 'scrisori';
    }
}

// --- Prepare view data ---
$filterData = comunicare_load_filter_data($pdo);
$localitati = $filterData['localitati'];
$graduri = $filterData['graduri'];
$templates = $filterData['templates'];

// Preview count (AJAX-like, dar si la load initial)
$preview_filters = [
    'status'              => $_POST['status'] ?? ($_GET['status'] ?? ''),
    'localitate'          => $_POST['localitate'] ?? ($_GET['localitate'] ?? ''),
    'sex'                 => $_POST['sex'] ?? ($_GET['sex'] ?? ''),
    'mediu'               => $_POST['mediu'] ?? ($_GET['mediu'] ?? ''),
    'hgrad'               => $_POST['hgrad'] ?? ($_GET['hgrad'] ?? ''),
    'data_nastere_de_la'  => $_POST['data_nastere_de_la'] ?? ($_GET['data_nastere_de_la'] ?? ''),
    'data_nastere_pana_la'=> $_POST['data_nastere_pana_la'] ?? ($_GET['data_nastere_pana_la'] ?? ''),
    'cotizatie_neachitata'=> !empty($_POST['cotizatie_neachitata'] ?? ($_GET['cotizatie_neachitata'] ?? '')),
];
$preview_count = comunicare_count_membri($pdo, $preview_filters);

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/comunicare/index.php';
