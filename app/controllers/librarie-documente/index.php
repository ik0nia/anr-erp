<?php
/**
 * Controller: Librărie documente — Lista + CRUD documente
 *
 * GET: Afișează lista documente
 * POST reordoneaza_ids: Reordonează (AJAX, JSON response)
 * POST incarca_document: Încarcă document nou
 * POST sterge_document: Șterge document
 * POST actualizeaza_document: Actualizează document
 */
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/LibrarieDocumenteService.php';

$eroare = '';
$succes = '';
librarie_documente_ensure_tables($pdo);

// --- POST: Acțiuni ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid();

    // Reordonare (AJAX)
    if (isset($_POST['reordoneaza_ids']) && is_array($_POST['reordoneaza_ids'])) {
        $result = librarie_documente_service_reordoneaza($pdo, $_POST['reordoneaza_ids']);
        if ($result['ok']) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
    }

    // Încărcare document
    if (isset($_POST['incarca_document'])) {
        $institutie = trim($_POST['institutie'] ?? '');
        $nume_document = trim($_POST['nume_document'] ?? '');
        $result = librarie_documente_service_incarca($pdo, $institutie, $nume_document, $_FILES['document'] ?? []);
        if ($result['success']) {
            header('Location: librarie-documente.php?succes=1');
            exit;
        }
        $eroare = $result['error'];
    }

    // Ștergere document
    if (isset($_POST['sterge_document'])) {
        $id = (int)($_POST['id'] ?? 0);
        $result = librarie_documente_service_sterge($pdo, $id);
        if ($result['success']) {
            header('Location: librarie-documente.php?succes=2');
            exit;
        }
        $eroare = $result['error'];
    }

    // Actualizare document
    if (isset($_POST['actualizeaza_document'])) {
        $id = (int)($_POST['id'] ?? 0);
        $institutie = trim($_POST['institutie'] ?? '');
        $nume_document = trim($_POST['nume_document'] ?? '');
        $result = librarie_documente_service_actualizeaza($pdo, $id, $institutie, $nume_document);
        if ($result['success']) {
            header('Location: librarie-documente.php?succes=3');
            exit;
        }
        $eroare = $result['error'];
    }
}

// --- GET: Date pentru view ---
$data = librarie_documente_load_data($pdo);
$lista = $data['lista'];
$base_url = $data['base_url'];
$succes_msg = isset($_GET['succes']) ? librarie_documente_succes_mesaj($_GET['succes']) : null;

// --- Render ---
include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/librarie-documente/index.php';
