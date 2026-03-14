<?php
/**
 * API căutare membri pentru liste prezență
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['membri' => []]);
    exit;
}

$term = '%' . $q . '%';
try {
    $stmt = $pdo->prepare("SELECT id, nume, prenume, datanastere, ciseria, cinumar, domloc, codpost, judet_domiciliu 
                           FROM membri 
                           WHERE (nume LIKE ? OR prenume LIKE ? OR CONCAT(nume,' ',prenume) LIKE ? OR cnp LIKE ? OR dosarnr LIKE ?)
                           AND (status_dosar IS NULL OR status_dosar = 'Activ' OR status_dosar NOT IN ('Decedat','Retras'))
                           ORDER BY nume, prenume 
                           LIMIT 30");
    $stmt->execute([$term, $term, $term, $term, $term]);
    $membri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $membri = [];
}

echo json_encode(['membri' => $membri], JSON_UNESCAPED_UNICODE);
