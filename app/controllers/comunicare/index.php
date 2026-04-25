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
$tip_etichete_selectat = (($_POST['tip_etichete'] ?? 'rola') === 'a4') ? 'a4' : 'rola';
$latime_mm_input = isset($_POST['latime_mm']) ? (float)$_POST['latime_mm'] : 89.0;
$inaltime_mm_input = isset($_POST['inaltime_mm']) ? (float)$_POST['inaltime_mm'] : 36.0;
$valid_a4_presets = ['custom', 'a4_2x5', 'a4_2x7', 'a4_3x7', 'a4_3x8', 'a4_3x10', 'a4_4x10'];
$a4_preset_input = (string)($_POST['a4_preset'] ?? 'custom');
if (!in_array($a4_preset_input, $valid_a4_presets, true)) {
    $a4_preset_input = 'custom';
}
$a4_margin_top_mm_input = isset($_POST['a4_margin_top_mm']) ? (float)$_POST['a4_margin_top_mm'] : 10.0;
$a4_margin_bottom_mm_input = isset($_POST['a4_margin_bottom_mm']) ? (float)$_POST['a4_margin_bottom_mm'] : 10.0;
$a4_margin_left_mm_input = isset($_POST['a4_margin_left_mm']) ? (float)$_POST['a4_margin_left_mm'] : 8.0;
$a4_margin_right_mm_input = isset($_POST['a4_margin_right_mm']) ? (float)$_POST['a4_margin_right_mm'] : 8.0;
$a4_cols_input = isset($_POST['a4_cols']) ? (int)$_POST['a4_cols'] : 3;
$a4_rows_input = isset($_POST['a4_rows']) ? (int)$_POST['a4_rows'] : 8;

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
        $tip_etichete = $tip_etichete_selectat;
        $latime_mm  = $latime_mm_input;
        $inaltime_mm = $inaltime_mm_input;
        $a4_presets = [
            'a4_2x5' => ['top' => 12.0, 'bottom' => 12.0, 'left' => 12.0, 'right' => 12.0, 'cols' => 2, 'rows' => 5],
            'a4_2x7' => ['top' => 10.0, 'bottom' => 10.0, 'left' => 10.0, 'right' => 10.0, 'cols' => 2, 'rows' => 7],
            'a4_3x7' => ['top' => 10.0, 'bottom' => 10.0, 'left' => 8.0, 'right' => 8.0, 'cols' => 3, 'rows' => 7],
            'a4_3x8' => ['top' => 8.0, 'bottom' => 8.0, 'left' => 7.0, 'right' => 7.0, 'cols' => 3, 'rows' => 8],
            'a4_3x10' => ['top' => 6.0, 'bottom' => 6.0, 'left' => 6.0, 'right' => 6.0, 'cols' => 3, 'rows' => 10],
            'a4_4x10' => ['top' => 6.0, 'bottom' => 6.0, 'left' => 5.0, 'right' => 5.0, 'cols' => 4, 'rows' => 10],
        ];
        if ($tip_etichete === 'a4' && isset($a4_presets[$a4_preset_input])) {
            $preset = $a4_presets[$a4_preset_input];
            $a4_margin_top_mm_input = $preset['top'];
            $a4_margin_bottom_mm_input = $preset['bottom'];
            $a4_margin_left_mm_input = $preset['left'];
            $a4_margin_right_mm_input = $preset['right'];
            $a4_cols_input = $preset['cols'];
            $a4_rows_input = $preset['rows'];
        }
        $etichete_options = [
            'tip' => $tip_etichete,
            'a4_preset' => $a4_preset_input,
            'a4_margin_top_mm' => $a4_margin_top_mm_input,
            'a4_margin_bottom_mm' => $a4_margin_bottom_mm_input,
            'a4_margin_left_mm' => $a4_margin_left_mm_input,
            'a4_margin_right_mm' => $a4_margin_right_mm_input,
            'a4_cols' => $a4_cols_input,
            'a4_rows' => $a4_rows_input,
        ];

        $membri = comunicare_filtreaza_membri($pdo, $filters);

        if (empty($membri)) {
            $eroare = 'Nu au fost gasiti membri cu filtrele selectate.';
        } else {
            $result = comunicare_genereaza_etichete_pdf($membri, $latime_mm, $inaltime_mm, $etichete_options);

            if ($result['success']) {
                comunicare_log_batch($pdo, $membri, 'etichete', $utilizator);
                log_activitate($pdo, 'Comunicare: Generat ' . count($membri) . ' etichete (' . strtoupper($tip_etichete) . ')', $utilizator);
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
