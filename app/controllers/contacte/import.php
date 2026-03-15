<?php
/**
 * Controller: Import contacte din Excel/CSV cu mapare campuri
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/excel_import.php';

ensure_contacte_table($pdo);
$eroare = '';
$succes = '';
$excel_data = null;
$mapare_coloane = null;
$import_result = null;

$campuri_contacte = [
    'nume' => 'Nume',
    'prenume' => 'Prenume',
    'companie' => 'Companie',
    'tip_contact' => 'Tip contact',
    'telefon' => 'Telefon mobil',
    'telefon_personal' => 'Telefon personal',
    'email' => 'Email',
    'email_personal' => 'Email personal',
    'website' => 'Website',
    'data_nasterii' => 'Data nasterii',
    'notite' => 'Notite',
    'referinta_contact' => 'Referinta / Contact comun',
];
$tipuri = get_contacte_tipuri();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_contacte'])) {
    csrf_require_valid();
    $upload_dir = APP_ROOT . '/uploads/import/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

    if (isset($_POST['executa_import']) && isset($_SESSION['contacte_import_path']) && file_exists($_SESSION['contacte_import_path'])) {
        $file_path = $_SESSION['contacte_import_path'];
        $excel_data = citeste_fisier_excel($file_path);
        $mapare_coloane = [];
        if (isset($_POST['mapare_coloane']) && is_array($_POST['mapare_coloane'])) {
            foreach ($_POST['mapare_coloane'] as $index => $db_field) {
                if (!empty($db_field) && $db_field !== 'ignora') {
                    $mapare_coloane[$index] = $db_field;
                }
            }
        }
        if (!empty($mapare_coloane) && !empty($excel_data['headers'])) {
            $import_result = importa_contacte($pdo, $excel_data['headers'], $excel_data['rows'], $mapare_coloane);
            if ($import_result['importati'] > 0) {
                $succes = "Import reusit: {$import_result['importati']} contacte importate.";
            }
            if (!empty($import_result['eroare'])) {
                $eroare = "Erori: " . implode("; ", array_slice($import_result['eroare'], 0, 10));
                if (count($import_result['eroare']) > 10) {
                    $eroare .= " ... si " . (count($import_result['eroare']) - 10) . " altele";
                }
            }
        }
        unlink($file_path);
        unset($_SESSION['contacte_import_path']);
        $excel_data = null;
        $mapare_coloane = null;
    } elseif (isset($_FILES['fisier_excel']) && $_FILES['fisier_excel']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['fisier_excel'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            $eroare = 'Tipul fisierului nu este suportat. Folositi CSV sau Excel (.xlsx, .xls).';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $eroare = 'Fisierul depaseste 10 MB.';
        } else {
            $filename = 'import_contacte_' . time() . '_' . uniqid() . '.' . $extension;
            $file_path = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $_SESSION['contacte_import_path'] = $file_path;
                $excel_data = citeste_fisier_excel($file_path);
                if (empty($excel_data['headers'])) {
                    $eroare = 'Nu s-au putut citi header-urile din fisier.';
                    unlink($file_path);
                    unset($_SESSION['contacte_import_path']);
                    $excel_data = null;
                } else {
                    $mapare_coloane = [];
                    $mapare_std = contacte_mapare_coloane();
                    foreach ($excel_data['headers'] as $idx => $h) {
                        $h_clean = trim($h);
                        foreach ($mapare_std as $excel_name => $db_field) {
                            if (stripos($h_clean, $excel_name) !== false || stripos($excel_name, $h_clean) !== false) {
                                $mapare_coloane[$idx] = $db_field;
                                break;
                            }
                        }
                    }
                }
            } else {
                $eroare = 'Eroare la incarcarea fisierului.';
            }
        }
    } elseif (isset($_POST['executa_import'])) {
        $eroare = 'Sesiunea de import a expirat. Incarcati din nou fisierul.';
        unset($_SESSION['contacte_import_path']);
    } else {
        $eroare = 'Nu s-a selectat niciun fisier sau a aparut o eroare la incarcare.';
    }
}

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';
include APP_ROOT . '/app/views/contacte/import.php';
