<?php
/**
 * API actualizare încasare - permite editarea câmpurilor unei încasări existente.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'eroare' => 'Metodă neacceptată']);
    exit;
}
csrf_require_valid();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'eroare' => 'ID invalid.']);
    exit;
}

$inc = incasari_get($pdo, $id);
if (!$inc) {
    echo json_encode(['ok' => false, 'eroare' => 'Încasarea nu a fost găsită.']);
    exit;
}

$reprezentand = isset($_POST['reprezentand']) ? trim($_POST['reprezentand']) : null;
$observatii = isset($_POST['observatii']) ? trim($_POST['observatii']) : null;

$updates = [];
$params = [];

if ($reprezentand !== null) {
    $updates[] = 'reprezentand = ?';
    $params[] = $reprezentand ?: null;
}
if ($observatii !== null) {
    $updates[] = 'observatii = ?';
    $params[] = $observatii ?: null;
}

if (empty($updates)) {
    echo json_encode(['ok' => false, 'eroare' => 'Nimic de actualizat.']);
    exit;
}

$params[] = $id;
$sql = "UPDATE incasari SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
log_activitate($pdo, "Încasări: editată încasare ID {$id} – reprezentând: " . ($reprezentand ?: '(gol)'), null, $inc['membru_id']);

echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
