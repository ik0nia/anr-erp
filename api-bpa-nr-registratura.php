<?php
/**
 * API: creează o înregistrare în registratură pentru Tabel distributie BPA și returnează numărul alocat.
 * Folosit de butonul "Adaugă număr de înregistrare" din formularul de creare tabel BPA.
 */
require_once __DIR__ . '/config.php';
require_once 'includes/registratura_helper.php';
require_once 'includes/log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['creaza_nr_registratura_bpa'])) {
    echo json_encode(['eroare' => 'Metodă invalidă.']);
    exit;
}
csrf_require_valid();

ensure_registratura_table($pdo);
$result = registratura_inregistreaza_document($pdo, [
    'tip_act' => 'Înregistrare document',
    'continut_document' => 'Tabel distributie BPA',
    'provine_din' => 'ANR Bihor',
    'destinatar_document' => null,
    'nr_document' => null,
    'data_document' => date('Y-m-d'),
    'task_deschis' => 0,
]);

if ($result['success']) {
    log_activitate($pdo, 'BPA: număr înregistrare alocat din registratură nr. ' . $result['nr_inregistrare']);
    echo json_encode(['nr_inregistrare' => $result['nr_inregistrare'], 'id' => $result['id']]);
} else {
    echo json_encode(['eroare' => $result['error'] ?? 'Eroare la creare înregistrare.']);
}
