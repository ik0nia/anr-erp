<?php
/**
 * Căutare membri pentru modalul Încasează cotizație (Dashboard).
 * Returnează JSON: lista membri cu id, nume, prenume, cotizatie_achitata (bool), valoare_cotizatie (pentru anul curent).
 */
require_once __DIR__ . '/config.php';
require_once 'includes/cotizatii_helper.php';
require_once 'includes/incasari_helper.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));

if ($q === '') {
    echo json_encode(['membri' => []]);
    exit;
}

$anul = (int)date('Y');
cotizatii_ensure_tables($pdo);
incasari_ensure_tables($pdo);

$membri_scutiti = cotizatii_membri_scutiti_ids($pdo);
$membri_cot_achitata = incasari_membri_cotizatie_achitata_an($pdo, $anul);

$stmt = $pdo->prepare("
    SELECT m.id, m.nume, m.prenume, m.hgrad
    FROM membri m
    WHERE (m.nume LIKE ? OR m.prenume LIKE ? OR m.cnp LIKE ? OR m.dosarnr LIKE ?)
    AND (m.status_dosar IS NULL OR m.status_dosar = '' OR m.status_dosar = 'Activ')
    ORDER BY m.nume, m.prenume
    LIMIT ?
");
$term = '%' . $q . '%';
$stmt->execute([$term, $term, $term, $term, $limit]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$lista = [];
foreach ($rows as $m) {
    $id = (int)$m['id'];
    $scutit = in_array($id, $membri_scutiti);
    $achitata = in_array($id, $membri_cot_achitata);
    $cotizatie_achitata = $scutit || $achitata;
    $valoare_cotizatie = 0;
    if (!$scutit) {
        $valoare_cotizatie = incasari_valoare_cotizatie_anuala($pdo, $anul, $m['hgrad'] ?? 'Fara handicap');
    }
    $lista[] = [
        'id' => $id,
        'nume' => $m['nume'],
        'prenume' => $m['prenume'],
        'cotizatie_achitata' => $cotizatie_achitata,
        'valoare_cotizatie' => (float)$valoare_cotizatie,
    ];
}

echo json_encode(['membri' => $lista], JSON_UNESCAPED_UNICODE);
