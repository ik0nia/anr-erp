<?php
/**
 * API căutare voluntari pentru autocomplete (Gestiune Activități)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/voluntariat_helper.php';
require_once __DIR__ . '/../includes/db_helper.php';

header('Content-Type: application/json; charset=utf-8');

voluntariat_ensure_tables($pdo);

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode(['voluntari' => []]);
    exit;
}

$term = '%' . $q . '%';
try {
    $voluntari = db_fetch_all($pdo, "SELECT id, nume, prenume, telefon, email FROM voluntari WHERE (nume LIKE ? OR prenume LIKE ? OR CONCAT(COALESCE(nume,''),' ',COALESCE(prenume,'')) LIKE ?) ORDER BY nume, prenume LIMIT 25", [$term, $term, $term]);
} catch (PDOException $e) {
    $voluntari = [];
}

echo json_encode(['voluntari' => $voluntari], JSON_UNESCAPED_UNICODE);
