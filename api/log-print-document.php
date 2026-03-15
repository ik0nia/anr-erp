<?php
/**
 * Endpoint pentru logging printare documente
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodă invalidă.']);
    exit;
}

csrf_require_valid();

$membru_id = (int)($_POST['membru_id'] ?? 0);
$template_id = (int)($_POST['template_id'] ?? 0);
$template_nume = trim($_POST['template_nume'] ?? '');
$membru_nume = trim($_POST['membru_nume'] ?? '');

if ($template_id <= 0 || empty($template_nume)) {
    echo json_encode(['success' => false, 'error' => 'Parametri invalizi.']);
    exit;
}

$context = $membru_nume ? "{$membru_nume}" : "Template ID: {$template_id}";
log_activitate($pdo, "documente: Document printat - {$template_nume} / {$context}", null, $membru_id > 0 ? $membru_id : null);

echo json_encode(['success' => true]);
