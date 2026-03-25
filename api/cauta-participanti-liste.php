<?php
/**
 * API căutare participanți pentru liste prezență:
 * - caută simultan în membri și contacte
 * - returnează rezultate unificate pentru autocomplete
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
$qLen = function_exists('mb_strlen') ? mb_strlen($q) : strlen($q);
if ($qLen < 2) {
    echo json_encode(['participanti' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$term = '%' . $q . '%';
$participanti = [];

try {
    $stmt_m = $pdo->prepare("
        SELECT id, nume, prenume, domloc
        FROM membri
        WHERE (
            nume LIKE ?
            OR prenume LIKE ?
            OR CONCAT(COALESCE(nume, ''), ' ', COALESCE(prenume, '')) LIKE ?
            OR cnp LIKE ?
            OR dosarnr LIKE ?
            OR telefonnev LIKE ?
            OR email LIKE ?
            OR domloc LIKE ?
        )
        AND (
            status_dosar IS NULL
            OR status_dosar = 'Activ'
            OR status_dosar NOT IN ('Decedat', 'Retras')
        )
        ORDER BY nume, prenume
        LIMIT 20
    ");
    $stmt_m->execute([$term, $term, $term, $term, $term, $term, $term, $term]);
    foreach ($stmt_m->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nume = trim((string)($row['nume'] ?? ''));
        $prenume = trim((string)($row['prenume'] ?? ''));
        $nume_complet = trim($nume . ' ' . $prenume);
        $participanti[] = [
            'tip' => 'membru',
            'id' => (int)$row['id'],
            'nume' => $nume,
            'prenume' => $prenume,
            'nume_complet' => $nume_complet,
            'domloc' => trim((string)($row['domloc'] ?? '')),
        ];
    }

    $stmt_c = $pdo->prepare("
        SELECT id, nume, prenume, companie, tip_contact, telefon, telefon_personal, email, email_personal
        FROM contacte
        WHERE (
            nume LIKE ?
            OR prenume LIKE ?
            OR CONCAT(COALESCE(nume, ''), ' ', COALESCE(prenume, '')) LIKE ?
            OR companie LIKE ?
            OR tip_contact LIKE ?
            OR telefon LIKE ?
            OR telefon_personal LIKE ?
            OR email LIKE ?
            OR email_personal LIKE ?
        )
        ORDER BY nume, prenume
        LIMIT 20
    ");
    $stmt_c->execute([$term, $term, $term, $term, $term, $term, $term, $term, $term]);
    foreach ($stmt_c->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nume = trim((string)($row['nume'] ?? ''));
        $prenume = trim((string)($row['prenume'] ?? ''));
        $nume_complet = trim($nume . ' ' . $prenume);
        $participanti[] = [
            'tip' => 'contact',
            'id' => (int)$row['id'],
            'nume' => $nume,
            'prenume' => $prenume,
            'nume_complet' => $nume_complet,
            'domloc' => trim((string)($row['companie'] ?? '')),
            'tip_contact' => trim((string)($row['tip_contact'] ?? '')),
        ];
    }
} catch (PDOException $e) {
    $participanti = [];
}

echo json_encode(['participanti' => $participanti], JSON_UNESCAPED_UNICODE);
