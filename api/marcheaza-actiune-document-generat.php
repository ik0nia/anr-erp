<?php
/**
 * Marchează o acțiune pe un document generat (ex: whatsapp/email).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/document_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodă invalidă.']);
    exit;
}

if (function_exists('csrf_require_valid')) {
    csrf_require_valid();
}

$document_id = (int)($_POST['document_generat_id'] ?? 0);
$actiune = strtolower(trim((string)($_POST['actiune'] ?? '')));

if ($document_id <= 0 || !in_array($actiune, ['email', 'whatsapp'], true)) {
    echo json_encode(['success' => false, 'error' => 'Parametri invalizi.']);
    exit;
}

$ok = documente_marcheaza_actiune($pdo, $document_id, $actiune);
if (!$ok) {
    echo json_encode(['success' => false, 'error' => 'Nu s-a putut salva acțiunea.']);
    exit;
}

echo json_encode(['success' => true]);
