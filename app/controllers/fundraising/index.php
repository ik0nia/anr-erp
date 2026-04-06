<?php
/**
 * Controller: Fundraising — Formular 230 + Setări
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/FundraisingService.php';

$fundraising_bootstrap_ok = true;
try {
    fundraising_f230_ensure_schema($pdo);
} catch (Throwable $e) {
    $fundraising_bootstrap_ok = false;
    error_log('Fundraising bootstrap error (admin): ' . $e->getMessage());
}

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['formular230', 'setari'], true) ? (string)$_GET['tab'] : 'formular230';
$eroare = '';
$succes = '';
$warning = '';
$manual_modal_open = false;
$edit_modal_open = false;
$edit_formular_id = 0;

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
$valori_editare = $valori_formular;

if ($tab === 'formular230' && isset($_GET['export']) && (string)$_GET['export'] === 'csv') {
    fundraising_f230_export_csv($pdo);
}

if ($fundraising_bootstrap_ok && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salveaza_template_f230'])) {
        csrf_require_valid();
        $tab = 'setari';
        $result = fundraising_f230_upload_template_file($pdo, $_FILES);
        if (!empty($result['success'])) {
            header('Location: /fundraising?tab=setari&succes_template=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Template-ul nu a putut fi salvat.');
    } elseif (isset($_POST['salveaza_setari_fundraising'])) {
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
    } elseif (isset($_POST['goleste_tabel_formulare_230'])) {
        csrf_require_valid();
        $tab = 'formular230';
        $result = fundraising_f230_clear_formulare($pdo);
        if (!empty($result['success'])) {
            header('Location: /fundraising?tab=formular230&succes_golire=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Tabelul de formulare nu a putut fi golit.');
    } elseif (isset($_POST['sterge_formular_230'])) {
        csrf_require_valid();
        $tab = 'formular230';
        $id = (int)($_POST['formular_id'] ?? 0);
        $result = fundraising_f230_delete_formular($pdo, $id);
        if (!empty($result['success'])) {
            header('Location: /fundraising?tab=formular230&succes_stergere=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Formularul nu a putut fi șters.');
    } elseif (isset($_POST['editeaza_formular_230'])) {
        csrf_require_valid();
        $tab = 'formular230';
        $edit_modal_open = true;
        $edit_formular_id = (int)($_POST['formular_id'] ?? 0);
        $row = fundraising_f230_get_formular($pdo, $edit_formular_id);
        if ($row) {
            $valori_editare = array_merge($valori_editare, [
                'nume' => (string)$row['nume'],
                'initiala_tatalui' => (string)($row['initiala_tatalui'] ?? ''),
                'prenume' => (string)$row['prenume'],
                'cnp' => (string)$row['cnp'],
                'localitate' => (string)$row['localitate'],
                'judet' => (string)$row['judet'],
                'cod_postal' => (string)($row['cod_postal'] ?? ''),
                'strada' => (string)$row['strada'],
                'numar' => (string)$row['numar'],
                'bloc' => (string)($row['bloc'] ?? ''),
                'scara' => (string)($row['scara'] ?? ''),
                'etaj' => (string)($row['etaj'] ?? ''),
                'apartament' => (string)($row['apartament'] ?? ''),
                'telefon' => (string)$row['telefon'],
                'email' => (string)$row['email'],
                'gdpr_acord' => (int)$row['gdpr_acord'],
            ]);
        }
    } elseif (isset($_POST['salveaza_modificari_formular_230'])) {
        csrf_require_valid();
        $tab = 'formular230';
        $edit_formular_id = (int)($_POST['formular_id'] ?? 0);
        $edit_modal_open = true;
        $valori_editare = array_merge($valori_editare, fundraising_f230_extract_data($_POST));
        $result = fundraising_f230_update_formular($pdo, $edit_formular_id, $_POST);
        if (!empty($result['success'])) {
            header('Location: /fundraising?tab=formular230&succes_editare=1');
            exit;
        }
        $eroare = (string)($result['error'] ?? 'Modificările nu au putut fi salvate.');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$fundraising_bootstrap_ok) {
    $eroare = 'Modulul Fundraising este temporar indisponibil (eroare internă de inițializare).';
}

if (isset($_GET['succes_setari'])) {
    $succes = 'Setările modulului Fundraising au fost salvate.';
}
if (isset($_GET['succes_template'])) {
    $succes = 'Template-ul PDF a fost salvat cu succes. Poți începe maparea (butonul "Deschide fereastra de mapare").';
}
if (isset($_GET['succes_mapare'])) {
    $succes = 'Maparea template-ului PDF a fost salvată.';
}
if (isset($_GET['succes_manual'])) {
    $succes = 'Formularul 230 a fost adăugat cu succes.';
}
if (isset($_GET['succes_golire'])) {
    $succes = 'Tabelul „Formulare 230 completate” a fost golit.';
}
if (isset($_GET['succes_stergere'])) {
    $succes = 'Formularul 230 a fost șters.';
}
if (isset($_GET['succes_editare'])) {
    $succes = 'Formularul 230 a fost actualizat.';
}

$setari_modul = [
    'template_rel' => '',
    'template_exists' => false,
    'template_sha256' => '',
    'template_preview_url' => '/util/f230-template-preview.php',
    'template_page_count' => 0,
    'template_mapat' => false,
    'template_map_missing_tags' => [],
    'template_map_items_by_tag' => [],
    'template_map_defaults_by_tag' => [],
    'template_uploaded_at' => '',
    'template_uploaded_at_display' => '',
    'template_fpdf_status' => 'direct',
    'template_fpdf_status_label' => 'Direct (template original compatibil FPDI)',
    'template_fpdf_fallback_active' => false,
    'confirm_html' => '',
    'public_url' => fundraising_f230_public_url(),
    'storage_folder' => 'F230PDF',
];
$taguri_f230 = fundraising_f230_taguri_display();
$lista_formulare = [];

if ($fundraising_bootstrap_ok) {
    try {
        $setari_modul = fundraising_f230_get_settings($pdo);
        $lista_formulare = fundraising_f230_list_formulare($pdo, 2000);
    } catch (Throwable $e) {
        error_log('Fundraising load error (admin): ' . $e->getMessage());
        if ($eroare === '') {
            $eroare = 'Modulul Fundraising nu a putut fi încărcat complet. Verifică logurile serverului.';
        }
    }
} else {
    if ($eroare === '') {
        $eroare = 'Modulul Fundraising este temporar indisponibil (eroare internă de inițializare).';
    }
}

if ($edit_formular_id > 0 && !$edit_modal_open) {
    $edit_formular = fundraising_f230_get_formular($pdo, $edit_formular_id);
    if ($edit_formular) {
        $edit_modal_open = true;
        $valori_editare = array_merge($valori_editare, [
            'nume' => (string)$edit_formular['nume'],
            'initiala_tatalui' => (string)($edit_formular['initiala_tatalui'] ?? ''),
            'prenume' => (string)$edit_formular['prenume'],
            'cnp' => (string)$edit_formular['cnp'],
            'localitate' => (string)$edit_formular['localitate'],
            'judet' => (string)$edit_formular['judet'],
            'cod_postal' => (string)($edit_formular['cod_postal'] ?? ''),
            'strada' => (string)$edit_formular['strada'],
            'numar' => (string)$edit_formular['numar'],
            'bloc' => (string)($edit_formular['bloc'] ?? ''),
            'scara' => (string)($edit_formular['scara'] ?? ''),
            'etaj' => (string)($edit_formular['etaj'] ?? ''),
            'apartament' => (string)($edit_formular['apartament'] ?? ''),
            'telefon' => (string)$edit_formular['telefon'],
            'email' => (string)$edit_formular['email'],
            'gdpr_acord' => (int)$edit_formular['gdpr_acord'],
        ]);
    }
}

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/fundraising/index.php';
