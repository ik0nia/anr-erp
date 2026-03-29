<?php
/**
 * Controller: Generare Documente — Management templateuri
 *
 * GET: Lista templateuri, taguri disponibile
 * POST upload_template: Upload template nou
 * POST sterge_template: Sterge un template
 * POST actualizeaza_template: Actualizare nume/activ
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/DocumenteService.php';

$eroare = '';
$succes = '';

// Initializare tabela si director
$init_err = documente_ensure_table($pdo);
if ($init_err) $eroare = $init_err;

// --- POST: Upload template ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    csrf_require_valid();
    $result = documente_upload_template(
        $pdo,
        trim($_POST['nume_afisare'] ?? ''),
        $_FILES['fisier_template'] ?? ['error' => UPLOAD_ERR_NO_FILE],
        !empty($_POST['foloseste_antet_platforma_erp']) ? 1 : 0
    );
    if ($result === null) {
        header('Location: /generare-documente?succes=1');
        exit;
    }
    $eroare = $result;
}

// --- POST: Stergere template ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_template'])) {
    csrf_require_valid();
    $result = documente_delete_template($pdo, (int)($_POST['id'] ?? 0));
    if ($result === null) {
        header('Location: /generare-documente?succes=3');
        exit;
    }
    $eroare = $result;
}

// --- POST: Actualizare template ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_template'])) {
    csrf_require_valid();
    $result = documente_update_template(
        $pdo,
        (int)($_POST['id'] ?? 0),
        trim($_POST['nume_afisare'] ?? ''),
        isset($_POST['activ']) ? 1 : 0
    );
    if ($result === null) {
        header('Location: /generare-documente?succes=2');
        exit;
    }
    $eroare = $result;
}

// --- POST: Salvare mapari manuale PDF ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_mapari_pdf'])) {
    csrf_require_valid();
    $template_id_map = (int)($_POST['template_id_map'] ?? 0);
    $mapari_pdf = trim((string)($_POST['mapari_pdf'] ?? ''));
    $updated_by = (string)($_SESSION['utilizator'] ?? 'Sistem');
    $result = documente_save_pdf_mapari($pdo, $template_id_map, $mapari_pdf, $updated_by);
    if ($result === null) {
        header('Location: /generare-documente?succes=4');
        exit;
    }
    $eroare = $result;
}

// --- GET: Date pentru view ---
$templates = [];
try {
    $templates = documente_list_templates($pdo);
    foreach ($templates as &$tpl) {
        $tpl['mapari_pdf'] = documente_get_pdf_mapari($pdo, (int)($tpl['id'] ?? 0));
    }
    unset($tpl);
} catch (PDOException $e) {
    $eroare = 'Eroare la incarcarea templateurilor.';
}

$taguri = get_taguri_disponibile();

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/generare-documente/index.php';
