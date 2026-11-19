<?php
/**
 * API: trimite chitanța unei încasări pe email, cu PDF-ul atașat.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/incasari_partajare.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'eroare' => 'Metodă neacceptată']);
    exit;
}
csrf_require_valid();

$id = (int)($_POST['id'] ?? 0);
$email = trim((string)($_POST['email'] ?? ''));
$format = isset($_POST['format']) ? (string)$_POST['format'] : null;

if ($id <= 0) {
    echo json_encode(['ok' => false, 'eroare' => 'Încasare inexistentă.'], JSON_UNESCAPED_UNICODE);
    exit;
}

incasari_ensure_tables($pdo);
$inc = incasari_get($pdo, $id);
if (!$inc) {
    echo json_encode(['ok' => false, 'eroare' => 'Încasare inexistentă.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email === '') {
    $destinatar = incasari_destinatar($pdo, $inc);
    $email = (string)($destinatar['email'] ?? '');
}

$rezultat = incasari_trimite_chitanta_email($pdo, $inc, $email, $format);

echo json_encode([
    'ok' => $rezultat['ok'],
    'eroare' => $rezultat['eroare'],
    'email' => $rezultat['email'],
], JSON_UNESCAPED_UNICODE);
