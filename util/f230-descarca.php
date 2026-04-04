<?php
/**
 * Descarcă PDF Formular 230 (Fundraising).
 * Acces permis doar utilizatorilor autentificați în ERP.
 */
require_once __DIR__ . '/../config.php';
require_once APP_ROOT . '/app/services/FundraisingService.php';

require_login();
fundraising_f230_ensure_schema($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'ID invalid.';
    exit;
}

$row = fundraising_f230_get_formular($pdo, $id);
if (!$row) {
    http_response_code(404);
    echo 'Formularul nu a fost găsit.';
    exit;
}

$pdf_rel = trim((string)($row['pdf_path'] ?? ''));
if ($pdf_rel === '') {
    http_response_code(404);
    echo 'PDF indisponibil pentru acest formular.';
    exit;
}

$pdf_abs = fundraising_f230_abs_path($pdf_rel);
if (!is_file($pdf_abs)) {
    http_response_code(404);
    echo 'Fișierul PDF nu există pe server.';
    exit;
}

$filename = trim((string)($row['pdf_filename'] ?? ''));
if ($filename === '') {
    $filename = 'formular-230-' . (int)$row['id'] . '.pdf';
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('Content-Length: ' . filesize($pdf_abs));
readfile($pdf_abs);
exit;
