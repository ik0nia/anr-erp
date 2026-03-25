<?php
/**
 * Controller: Contacte — Lista + Stergere
 *
 * GET: Afiseaza lista contacte cu filtrare, cautare, paginare
 * POST sterge_contact: Sterge un contact si redirecteaza
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ContacteService.php';

contacte_ensure_table($pdo);

// --- POST: Sincronizare membri -> contacte ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_membri'])) {
    csrf_require_valid();
    $sync_result = contacte_sync_membri($pdo, $_SESSION['utilizator'] ?? 'Sistem');
    if ($sync_result['success']) {
        header('Location: /contacte?succes_sync=1&created=' . $sync_result['created'] . '&updated=' . $sync_result['updated']);
    } else {
        header('Location: /contacte?eroare_sync=1');
    }
    exit;
}

// --- POST: Stergere contact ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_contact'])) {
    csrf_require_valid();
    $id = (int)($_POST['contact_id'] ?? 0);
    $redirect_tab = isset($_POST['redirect_tab']) && $_POST['redirect_tab'] !== '' ? $_POST['redirect_tab'] : null;

    if ($id > 0) {
        contacte_delete($pdo, $id, $_SESSION['utilizator'] ?? 'Sistem');
    }

    $url = '/contacte';
    if ($redirect_tab) $url .= '?tab=' . urlencode($redirect_tab);
    header('Location: ' . $url);
    exit;
}

// --- GET: Parametri ---
$per_page = (int)($_GET['per_page'] ?? 25);
if (!in_array($per_page, [10, 25, 50])) $per_page = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$cautare = trim($_GET['cautare'] ?? '');
$tab = $_GET['tab'] ?? 'toate';
if ($cautare !== '') $tab = 'toate';

// --- Date pentru view ---
$eroare_bd = '';
try {
    $data = contacte_list($pdo, $tab, $cautare, $page, $per_page);
} catch (PDOException $e) {
    $eroare_bd = 'Eroare la încărcare.';
    $data = ['contacte' => [], 'total' => 0, 'total_pages' => 1, 'counts' => ['toate' => 0], 'tipuri' => contacte_tipuri()];
}

// Validare tab dupa incarcare tipuri
$tipuri = $data['tipuri'];
if ($tab !== 'toate' && !isset($tipuri[$tab])) $tab = 'toate';

// Helper URL paginare
function build_contacte_url($params = []) {
    $p = array_merge($_GET, $params);
    $p['page'] = $p['page'] ?? 1;
    return '/contacte?' . http_build_query($p);
}

// Variabile pentru view
$contacte = $data['contacte'];
$total = $data['total'];
$total_pages = $data['total_pages'];
$counts = $data['counts'];
$tipuri_ordered = ['toate' => 'Toate'] + $tipuri;
$succes_msg = isset($_GET['succes']) ? 'Contactul a fost salvat cu succes.' : null;
if (isset($_GET['succes_sync'])) {
    $created = (int)($_GET['created'] ?? 0);
    $updated = (int)($_GET['updated'] ?? 0);
    $succes_msg = "Sincronizare finalizată: {$created} contacte noi create, {$updated} contacte actualizate.";
}
if (isset($_GET['eroare_sync'])) {
    $eroare_bd = 'Eroare la sincronizarea membrilor în contacte.';
}

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/contacte/index.php';
