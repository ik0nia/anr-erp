<?php
/**
 * Controller: Import membri CSV — Wizard cu upload + mapare coloane + import/actualizare
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/membri_import_helper.php';

$eroare = '';
$succes = '';
$import_result = null;
$excel_data = null;
$mapare_coloane = null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$step = 'upload'; // upload | map | done

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['membri_import_upload'])) {
        csrf_require_valid();
        if (!isset($_FILES['fisier_csv']) || $_FILES['fisier_csv']['error'] !== UPLOAD_ERR_OK) {
            $eroare = 'Nu s-a selectat niciun fisier CSV sau a aparut o eroare la incarcare.';
        } else {
            $file = $_FILES['fisier_csv'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $eroare = 'Modulul nou accepta doar fisiere CSV.';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $eroare = 'Fisierul depaseste 10 MB.';
            } else {
                $upload_dir = APP_ROOT . '/uploads/import/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'membri_import_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', uniqid()) . '.csv';
                $file_path = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $_SESSION['membri_import_csv_path'] = $file_path;
                    $excel_data = membri_import_parse_csv($file_path);
                    if (empty($excel_data['headers'])) {
                        $eroare = 'Nu s-au putut citi coloanele din fisierul CSV.';
                        unlink($file_path);
                        unset($_SESSION['membri_import_csv_path']);
                    } else {
                        $step = 'map';
                    }
                } else {
                    $eroare = 'Eroare la salvarea fisierului pe server.';
                }
            }
        }
    } elseif (isset($_POST['membri_import_execute'])) {
        csrf_require_valid();
        $path = $_SESSION['membri_import_csv_path'] ?? null;
        if (!$path || !file_exists($path)) {
            $eroare = 'Sesiunea de import a expirat. Incarca din nou fisierul.';
        } else {
            $excel_data = membri_import_parse_csv($path);
            if (empty($excel_data['headers'])) {
                $eroare = 'Nu s-au putut citi din nou datele din fisierul CSV.';
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
                if (empty($mapare_coloane)) {
                    $eroare = 'Nu a fost mapata nicio coloana catre baza de date.';
                    $step = 'map';
                } else {
                    $actiune = $_POST['actiune_import'] ?? 'adauga';

                    if ($actiune === 'actualizeaza_dosarnr') {
                        $index_dosarnr = array_search('dosarnr', $mapare_coloane, true);
                        if ($index_dosarnr === false) {
                            $eroare = 'Pentru actualizare dupa Nr. Dosar trebuie sa mapezi o coloana la campul "Nr. Dosar".';
                            $step = 'map';
                        } else {
                            $result = membri_actualizare_execute($pdo, $excel_data['rows'], $mapare_coloane);
                            $actualizati = $result['actualizati'] ?? 0;
                            $negasiti = $result['negasiti'] ?? 0;

                            if ($actualizati > 0) {
                                $succes = "Actualizare reusita: {$actualizati} membri actualizati dupa Nr. Dosar.";
                                if ($negasiti > 0) {
                                    $succes .= " {$negasiti} randuri cu Nr. Dosar care nu exista in baza de date.";
                                }
                            } elseif ($negasiti > 0) {
                                $eroare = "Nu s-a actualizat niciun membru. {$negasiti} randuri au avut Nr. Dosar care nu exista in baza de date.";
                            }

                            if (!empty($result['eroare'])) {
                                $eroare_extra = 'Erori la actualizare: ' . implode('; ', array_slice($result['eroare'], 0, 10));
                                $extra = count($result['eroare']) - 10;
                                if ($extra > 0) {
                                    $eroare_extra .= " ... si inca {$extra} erori.";
                                }
                                $eroare = empty($eroare) ? $eroare_extra : ($eroare . ' | ' . $eroare_extra);
                            }

                            unlink($path);
                            unset($_SESSION['membri_import_csv_path']);
                            $step = 'done';
                        }
                    } else {
                        $skip_duplicates = !empty($_POST['skip_duplicates']);
                        $import_result = membri_import_execute($pdo, $excel_data['rows'], $mapare_coloane, $skip_duplicates);
                        $importati = $import_result['importati'] ?? 0;
                        $skipati = $import_result['skipati'] ?? 0;

                        if ($importati > 0) {
                            $succes = "Import reusit: {$importati} membri importati.";
                            if ($skipati > 0) {
                                $succes .= " {$skipati} membri au fost sariti (CNP existent).";
                            }
                        }
                        if (!empty($import_result['eroare'])) {
                            $eroare = 'Erori la import: ' . implode('; ', array_slice($import_result['eroare'], 0, 10));
                            $extra = count($import_result['eroare']) - 10;
                            if ($extra > 0) {
                                $eroare .= " ... si inca {$extra} erori.";
                            }
                        }

                        unlink($path);
                        unset($_SESSION['membri_import_csv_path']);
                        $step = 'done';
                    }
                }
            }
        }
    }
}

// Reload CSV data for map step after GET
if ($step === 'map' && $excel_data === null && !empty($_SESSION['membri_import_csv_path']) && file_exists($_SESSION['membri_import_csv_path'])) {
    $excel_data = membri_import_parse_csv($_SESSION['membri_import_csv_path']);
}

$campuri_membri = membri_import_available_fields($pdo);

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
include APP_ROOT . '/app/views/import/membri-csv.php';
