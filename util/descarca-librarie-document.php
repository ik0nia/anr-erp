<?php
/**
 * Descarcă sau afișează pentru print document din librărie. Înregistrează acțiunea în log.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/librarie_documente_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';

$id = (int)($_GET['id'] ?? 0);
$print = isset($_GET['print']) && $_GET['print'] === '1';

if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parametru invalid.');
}

librarie_documente_ensure_tables($pdo);
$doc = librarie_documente_get($pdo, $id);
if (!$doc) {
    header('HTTP/1.1 404 Not Found');
    exit('Document inexistent.');
}

$cale_abs = rtrim(__DIR__ . '/..', '/\\') . '/' . $doc['cale_fisier'];
if (!is_file($cale_abs) || !is_readable($cale_abs)) {
    header('HTTP/1.1 404 Not Found');
    exit('Fișier inexistent.');
}

if ($print) {
    log_activitate($pdo, "Librărie documente: Print – {$doc['nume_document']} / {$doc['institutie']}");
    $disposition = 'inline';
} else {
    log_activitate($pdo, "Librărie documente: Descărcare – {$doc['nume_document']} / {$doc['institutie']}");
    $disposition = 'attachment';
}

$mime = mime_content_type($cale_abs);
if (!$mime || $mime === 'application/octet-stream') {
    $ext = strtolower(pathinfo($doc['nume_fisier'], PATHINFO_EXTENSION));
    $mime = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ][$ext] ?? 'application/octet-stream';
}

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . basename($doc['nume_fisier']) . '"');
header('Content-Length: ' . filesize($cale_abs));
header('Cache-Control: no-cache, must-revalidate');
readfile($cale_abs);
exit;
