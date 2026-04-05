<?php
/**
 * Upload securizat PDF pentru editorul TinyMCE din Fundraising > Setări.
 * Returnează JSON cu URL public de download pentru inserare în email.
 */
require_once __DIR__ . '/../app/bootstrap.php';
if (!function_exists('require_login')) {
    require_once APP_ROOT . '/includes/auth_helper.php';
}
if (!function_exists('csrf_validate_token')) {
    require_once APP_ROOT . '/includes/csrf_helper.php';
}

require_login();
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodă nepermisă.']);
    exit;
}

$csrf = (string)($_POST['_csrf_token'] ?? '');
if (!csrf_validate_token($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token CSRF invalid.']);
    exit;
}

$file = $_FILES['file'] ?? null;
if (!is_array($file)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fișierul lipsește din request.']);
    exit;
}

$upload_error = (int)($file['error'] ?? UPLOAD_ERR_OK);
if ($upload_error !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Eroare la upload (cod ' . $upload_error . ').']);
    exit;
}

$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > 20 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fișierul PDF trebuie să aibă între 1 byte și 20MB.']);
    exit;
}

$tmp_name = (string)($file['tmp_name'] ?? '');
if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fișier upload invalid.']);
    exit;
}

$orig_name = (string)($file['name'] ?? 'document.pdf');
$ext = strtolower((string)pathinfo($orig_name, PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Se acceptă doar fișiere PDF.']);
    exit;
}

$finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
$mime = $finfo ? (string)@finfo_file($finfo, $tmp_name) : '';
if ($finfo) {
    @finfo_close($finfo);
}
if ($mime !== '' && stripos($mime, 'pdf') === false && strtolower($mime) !== 'application/octet-stream') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tip MIME invalid. Se acceptă doar PDF.']);
    exit;
}

$check_header = @file_get_contents($tmp_name, false, null, 0, 5);
if ($check_header === false || strpos((string)$check_header, '%PDF-') !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Fișierul nu are antet PDF valid.']);
    exit;
}

$upload_dir = APP_ROOT . '/uploads/fundraising/email-attachments/';
if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Nu s-a putut crea directorul de upload.']);
    exit;
}

$safe_base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo($orig_name, PATHINFO_FILENAME));
$safe_base = trim((string)$safe_base, '_');
if ($safe_base === '') {
    $safe_base = 'document';
}
$safe_base = substr($safe_base, 0, 80);
$filename = 'f230-email-' . date('Ymd-His') . '-' . substr(md5((string)uniqid('', true)), 0, 8) . '-' . $safe_base . '.pdf';
$dest_abs = $upload_dir . $filename;

if (!@move_uploaded_file($tmp_name, $dest_abs)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Nu s-a putut salva PDF-ul pe server.']);
    exit;
}

@chmod($dest_abs, 0644);

$download_url = '/uploads/fundraising/email-attachments/' . rawurlencode($filename);
echo json_encode([
    'success' => true,
    'url' => $download_url,
    'filename' => $filename,
]);
