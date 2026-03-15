<?php
/**
 * Controller: Actualizare membri din CSV
 *
 * Wizard cu upload + mapare coloane + actualizare membri dupa Nr. Dosar
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/membri_import_helper.php';

$eroare = '';
$succes = '';
$rezultat = null;
$excel_data = null;
$mapare_coloane = null;

$step = 'upload';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();

    if (isset($_POST['actualizare_upload'])) {
        if (!isset($_FILES['fisier_csv']) || $_FILES['fisier_csv']['error'] !== UPLOAD_ERR_OK) {
            $eroare = 'Nu s-a selectat niciun fisier CSV sau a aparut o eroare la incarcare.';
        } else {
            $file = $_FILES['fisier_csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $eroare = 'Se accepta doar fisiere CSV (separate cu virgula).';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $eroare = 'Fisierul depaseste 10 MB.';
            } else {
                $upload_dir = APP_ROOT . '/uploads/import/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'actualizare_membri_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', uniqid()) . '.csv';
                $file_path = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['actualizare_csv_path'] = $file_path;
                    $excel_data = membri_import_parse_csv($file_path);
                    if (empty($excel_data['headers'])) {
                        $eroare = 'Nu s-au putut citi coloanele din fisierul CSV.';
                        @unlink($file_path);
                        unset($_SESSION['actualizare_csv_path']);
                    } else {
                        $step = 'map';
                    }
                } else {
                    $eroare = 'Eroare la salvarea fisierului pe server.';
                }
            }
        }
    } elseif (isset($_POST['actualizare_execute'])) {
        $path = $_SESSION['actualizare_csv_path'] ?? null;
        if (!$path || !file_exists($path)) {
            $eroare = 'Sesiunea a expirat. Incarca din nou fisierul.';
        } else {
            $excel_data = membri_import_parse_csv($path);
            if (empty($excel_data['headers'])) {
                $eroare = 'Nu s-au putut citi datele din fisierul CSV.';
            } else {
                $mapare_coloane = [];
                if (isset($_POST['mapare_coloane']) && is_array($_POST['mapare_coloane'])) {
                    foreach ($_POST['mapare_coloane'] as $index => $db_field) {
                        $index = (int)$index;
                        $db_field = trim((string)$db_field);
                        if ($db_field !== '' && $db_field !== 'ignora') {
                            $mapare_coloane[$index] = $db_field;
                        }
                    }
                }

                if (!isset($mapare_coloane) || !in_array('dosarnr', $mapare_coloane, true)) {
                    $eroare = 'Coloana Nr. Dosar (dosarnr) trebuie mapata obligatoriu – este campul de potrivire.';
                    $step = 'map';
                } else {
                    $rezultat = membri_actualizare_execute($pdo, $excel_data['rows'], $mapare_coloane);
                    $actualizati = $rezultat['actualizati'] ?? 0;
                    $negasiti = $rezultat['negasiti'] ?? 0;

                    if ($actualizati > 0) {
                        $succes = "Actualizare reusita: {$actualizati} membri actualizati.";
                        if ($negasiti > 0) {
                            $succes .= " {$negasiti} randuri nesincronizate (Nr. Dosar negasit in baza de date).";
                        }
                    } elseif ($negasiti > 0 && empty($rezultat['eroare'])) {
                        $eroare = "Niciun membru actualizat. Toate cele {$negasiti} randuri au Nr. Dosar negasit in baza de date.";
                    }

                    if (!empty($rezultat['eroare'])) {
                        $eroare = ($eroare ? $eroare . ' ' : '') . 'Erori: ' . implode('; ', array_slice($rezultat['eroare'], 0, 10));
                        if (count($rezultat['eroare']) > 10) {
                            $eroare .= ' ... si inca ' . (count($rezultat['eroare']) - 10) . ' erori.';
                        }
                    }

                    @unlink($path);
                    unset($_SESSION['actualizare_csv_path']);
                    $step = 'done';
                }
            }
        }
    }
}

if ($step === 'map' && $excel_data === null && !empty($_SESSION['actualizare_csv_path']) && file_exists($_SESSION['actualizare_csv_path'])) {
    $excel_data = membri_import_parse_csv($_SESSION['actualizare_csv_path']);
}

$campuri_membri = membri_import_available_fields($pdo);

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
include APP_ROOT . '/app/views/import/actualizeaza-csv.php';
