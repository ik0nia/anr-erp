<?php
/**
 * API căutare contacte pentru autocomplete în modulul Contacte.
 *
 * Caută în toate câmpurile relevante și returnează rezultate indiferent de tip/categorie.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/ContacteService.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
    echo json_encode(['contacte' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$term = '%' . $q . '%';

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.nume,
            c.prenume,
            c.companie,
            c.tip_contact,
            c.telefon,
            c.telefon_personal,
            c.email,
            c.email_personal,
            c.website,
            c.cnp,
            c.notite,
            c.referinta_contact,
            c.data_nasterii
        FROM contacte c
        WHERE (
            c.nume LIKE ?
            OR c.prenume LIKE ?
            OR CONCAT(COALESCE(c.nume, ''), ' ', COALESCE(c.prenume, '')) LIKE ?
            OR c.companie LIKE ?
            OR c.tip_contact LIKE ?
            OR c.telefon LIKE ?
            OR c.telefon_personal LIKE ?
            OR c.email LIKE ?
            OR c.email_personal LIKE ?
            OR c.website LIKE ?
            OR c.cnp LIKE ?
            OR c.notite LIKE ?
            OR c.referinta_contact LIKE ?
            OR DATE_FORMAT(c.data_nasterii, '%d.%m.%Y') LIKE ?
            OR DATE_FORMAT(c.data_nasterii, '%Y-%m-%d') LIKE ?
        )
        ORDER BY c.nume, c.prenume
        LIMIT 20
    ");
    $stmt->execute([
        $term, $term, $term, $term, $term,
        $term, $term, $term, $term, $term,
        $term, $term, $term, $term, $term,
    ]);
    $contacte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tipuri = contacte_tipuri();
    foreach ($contacte as &$contact) {
        $tip = (string)($contact['tip_contact'] ?? '');
        $contact['tip_contact_label'] = $tip !== '' ? ($tipuri[$tip] ?? $tip) : '';
    }
    unset($contact);
} catch (PDOException $e) {
    $contacte = [];
}

echo json_encode(['contacte' => $contacte], JSON_UNESCAPED_UNICODE);
