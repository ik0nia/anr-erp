<?php
/**
 * Controller: Fundraising — Formular 230 + Setări
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/FundraisingService.php';

fundraising_f230_ensure_schema($pdo);

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['formular230', 'setari'], true) ? (string)$_GET['tab'] : 'formular230';
$eroare = '';
$succes = '';
$warning = '';
$manual_modal_open = false;

$valori_formular = [
    'nume' => '',
    'initiala_tatalui' => '',
    'prenume' => '',
    'cnp' => '',
    'localitate' => '',
    'judet' => '',
    'cod_postal' => '',
    'strada' => '',
    'numar' => '',
    'bloc' => '',
    'scara' => '',
    'etaj' => '',
    'apartament' => '',
    'telefon' => '',
    'email' => '',
    'gdpr_acord' => 0,
    'signature_data' => '',
];

if ($tab === 'formular230' && isset($_GET['export']) && (string)$_GET['export'] === 'csv') {
    fundraising_f230_export_csv($pdo);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salveaza_setari_fundraising'])) {
        csrf_require_valid();
        $tab = 'setari';
        $result = fundraising_f230_save_settings($pdo, $_POST, $_FILES);
        if (!empty($result['success'])) {
            $redirect = '/fundraising?tab=setari&succes_setari=1';
            header('Location: ' . $redirect);
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Setările nu au putut fi salvate.');
    } elseif (isset($_POST['salveaza_mapare_template_f230'])) {
        csrf_require_valid();
        $tab = 'setari';
        $result = fundraising_f230_save_template_map($pdo, $_POST);
        if (!empty($result['success'])) {
            header('Location: /fundraising?tab=setari&succes_mapare=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Maparea template-ului nu a putut fi salvată.');
    } elseif (isset($_POST['adauga_formular_manual'])) {
        csrf_require_valid();
        $tab = 'formular230';
        $manual_modal_open = true;
        $valori_formular = array_merge($valori_formular, fundraising_f230_extract_data($_POST));
        $result = fundraising_f230_process_submission($pdo, $_POST, [
            'sursa' => 'manual',
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
        if (!empty($result['success'])) {
            header('Location: /fundraising?tab=formular230&succes_manual=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Formularul nu a putut fi salvat.');
    }
}

if (isset($_GET['succes_setari'])) {
    $succes = 'Setările modulului Fundraising au fost salvate.';
}
if (isset($_GET['succes_mapare'])) {
    $succes = 'Maparea template-ului PDF a fost salvată.';
}
if (isset($_GET['succes_manual'])) {
    $succes = 'Formularul 230 a fost adăugat cu succes.';
}

$setari_modul = fundraising_f230_get_settings($pdo);
$taguri_f230 = fundraising_f230_taguri_display();
$lista_formulare = fundraising_f230_list_formulare($pdo, 2000);

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/fundraising/index.php';
