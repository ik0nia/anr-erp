<?php
/**
 * API căutare voluntari pentru autocomplete (Gestiune Activități)
 */
require_once __DIR__ . '/config.php';
require_once 'includes/voluntariat_helper.php';

header('Content-Type: application/json; charset=utf-8');

voluntariat_ensure_tables($pdo);

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode(['voluntari' => []]);
    exit;
}

$term = '%' . $q . '%';
try {
    $stmt = $pdo->prepare("SELECT id, nume, prenume, telefon, email FROM voluntari WHERE (nume LIKE ? OR prenume LIKE ? OR CONCAT(COALESCE(nume,''),' ',COALESCE(prenume,'')) LIKE ?) ORDER BY nume, prenume LIMIT 25");
    $stmt->execute([$term, $term, $term]);
    $voluntari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $voluntari = [];
}

echo json_encode(['voluntari' => $voluntari], JSON_UNESCAPED_UNICODE);
