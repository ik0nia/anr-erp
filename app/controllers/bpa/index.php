<?php
/**
 * Controller: Ajutoare BPA — Management distributie, gestiune stoc, rapoarte
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/BpaService.php';

$eroare = '';
$succes = '';
$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';

// --- POST actions ---

// Stergere document gestiune
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_gestiune_bpa'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_gestiune'] ?? 0);
    if (bpa_service_sterge_gestiune($pdo, $id)) {
        header('Location: /ajutoare-bpa?succes=sterge_gestiune');
        exit;
    }
}

// Actualizare document gestiune
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editeaza_gestiune_bpa'])) {
    csrf_require_valid();
    $id = (int)($_POST['id_gestiune'] ?? 0);
    $nr = trim($_POST['nr_document'] ?? '');
    $data_doc = trim($_POST['data_document'] ?? '');
    $tip = in_array($_POST['tip_document'] ?? '', ['aviz', 'tabel_distributie', 'tabel_cristal']) ? $_POST['tip_document'] : '';
    $cantitate = (float)($_POST['cantitate'] ?? 0);
    $loc = trim($_POST['loc_distributie'] ?? '');
    $nr_benef = isset($_POST['nr_beneficiari']) && $_POST['nr_beneficiari'] !== '' ? (int)$_POST['nr_beneficiari'] : null;
    if (bpa_service_editeaza_gestiune($pdo, $id, $nr, $data_doc, $tip, $cantitate, $loc ?: null, $nr_benef)) {
        header('Location: /ajutoare-bpa?succes=editeaza_gestiune');
        exit;
    }
}

// Adaugare document (aviz sau tabel pe hartie)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_document'])) {
    csrf_require_valid();
    $tip = in_array($_POST['tip_document'] ?? '', ['aviz', 'tabel_distributie', 'tabel_cristal']) ? $_POST['tip_document'] : '';
    $nr = trim($_POST['nr_document'] ?? '');
    $data_doc = trim($_POST['data_document'] ?? '');
    $cantitate = (float)($_POST['cantitate'] ?? 0);
    $loc = trim($_POST['loc_distributie'] ?? '');
    $nr_benef = isset($_POST['nr_beneficiari']) && $_POST['nr_beneficiari'] !== '' ? (int)$_POST['nr_beneficiari'] : null;
    $result = bpa_service_adauga_document($pdo, $tip, $nr, $data_doc, $cantitate, $loc ?: null, $nr_benef, $utilizator);
    if ($result['success']) {
        header('Location: /ajutoare-bpa?succes=document');
        exit;
    } else {
        $eroare = $result['error'];
    }
}

// Salvare tabel distributie (creare sau actualizare)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_tabel'])) {
    csrf_require_valid();
    $tabel_id = (int)($_POST['tabel_id'] ?? 0);
    $nr_tabel = trim($_POST['nr_tabel'] ?? '');
    $data_tabel = trim($_POST['data_tabel'] ?? date('Y-m-d'));
    $predare_sediul = !empty($_POST['predare_sediul']);
    $predare_centru = !empty($_POST['predare_centru']);
    $livrare_domiciliu = !empty($_POST['livrare_domiciliu']);
    $randuri_raw = $_POST['randuri'] ?? [];
    $result = bpa_service_salveaza_tabel($pdo, $tabel_id, $nr_tabel, $data_tabel, $predare_sediul, $predare_centru, $livrare_domiciliu, $randuri_raw, $utilizator);
    if ($result['success']) {
        header('Location: /ajutoare-bpa?succes=tabel&id=' . (int)$result['id']);
        exit;
    } else {
        $eroare = $result['error'];
    }
}

// --- Prepare view data ---

$tab = isset($_GET['tab']) && $_GET['tab'] === 'rapoarte' ? 'rapoarte' : 'management';
$edit_id = (int)($_GET['edit'] ?? 0);
$perioada = $_GET['perioada'] ?? 'an';

$pageData = bpa_service_load_page_data($pdo, $perioada, $edit_id);
extract($pageData);

// Success messages from redirect
if (isset($_GET['succes'])) {
    $messages = bpa_success_messages();
    $succes = $messages[$_GET['succes']] ?? '';
}

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/bpa/index.php';
