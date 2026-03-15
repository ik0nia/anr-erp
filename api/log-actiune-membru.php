<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

// Auth check
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok' => false]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$membru_id = (int)($input['membru_id'] ?? 0);
$actiune = mb_substr(trim($input['actiune'] ?? ''), 0, 500);

if (!$membru_id || !$actiune) { echo json_encode(['ok' => false]); exit; }

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['username'] ?? 'sistem';
log_activitate($pdo, $actiune, $utilizator, $membru_id);
echo json_encode(['ok' => true]);
