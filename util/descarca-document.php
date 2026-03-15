<?php
/**
 * Descarcă document generat (DOCX sau PDF)
 */
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? 'docx'; // docx sau pdf
$inline = isset($_GET['inline']) && $_GET['inline'] === '1'; // previzualizare în browser (ex. pentru print)

if (empty($token)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parametru lipsă.');
}

$filename = base64_decode($token, true);
if ($filename === false || $filename === '') {
    header('HTTP/1.1 400 Bad Request');
    exit('Token invalid.');
}
// Permite nume cu spații și diacritice; interzice doar caractere periculoase (path traversal etc.)
if (preg_match('/[\/\\\\:*?"<>|\x00-\x1f]|\.\./', $filename)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Token invalid.');
}

$base_dir = __DIR__ . '/../uploads/documente_generate/';
$path = realpath($base_dir . $filename);

if (!$path || strpos($path, realpath($base_dir)) !== 0 || !file_exists($path)) {
    header('HTTP/1.1 404 Not Found');
    exit('Fișierul nu a fost găsit.');
}

$mime = $type === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
$ext = $type === 'pdf' ? 'pdf' : 'docx';
// inline=1: afișare în browser (ex. pentru print); altfel descărcare
$disposition = $inline ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . basename($path) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache, must-revalidate');
readfile($path);
exit;
