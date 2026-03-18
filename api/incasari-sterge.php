<?php
/**
 * API ștergere încasare. La ștergere, dacă încasarea avea chitanță cu ultimul nr din serie,
 * decrementează nr_curent din incasari_serii (refacerea indexului).
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

$seria = $inc['seria_chitanta'] ?? null;
$nr_chitanta = $inc['nr_chitanta'] ?? null;
$tip = $inc['tip'] ?? '';
$suma = $inc['suma'] ?? 0;
$nume = trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? ''));

// Ștergem încasarea
$stmt = $pdo->prepare("DELETE FROM incasari WHERE id = ?");
$stmt->execute([$id]);

// Refacem indexul: dacă nr chitanță șters era ultimul emis (nr_curent - 1), decrementăm
if ($seria !== null && $nr_chitanta !== null) {
    $tip_serie = incasari_tip_serie_pentru_tip($tip);
    $stmt = $pdo->prepare("SELECT nr_curent FROM incasari_serii WHERE tip_serie = ? FOR UPDATE");
    $stmt->execute([$tip_serie]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $nr_curent = (int)$row['nr_curent'];
        // nr_curent este deja incrementat (next number). Dacă chitanța ștearsă avea nr_curent - 1, decrementăm.
        // Verificăm recursiv: poate au fost șterse mai multe consecutive de la coadă.
        // Găsim cel mai mare nr chitanță rămas pentru seria respectivă.
        $stmt2 = $pdo->prepare("SELECT MAX(nr_chitanta) as max_nr FROM incasari WHERE seria_chitanta = ? AND tip = ?");
        $stmt2->execute([$seria, $tip]);
        $max_row = $stmt2->fetch(PDO::FETCH_ASSOC);
        $max_nr_ramas = $max_row && $max_row['max_nr'] !== null ? (int)$max_row['max_nr'] : 0;

        // Noul nr_curent = max_nr_ramas + 1 (sau nr_start dacă nu mai există chitanțe)
        $stmt3 = $pdo->prepare("SELECT nr_start FROM incasari_serii WHERE tip_serie = ?");
        $stmt3->execute([$tip_serie]);
        $serie_row = $stmt3->fetch(PDO::FETCH_ASSOC);
        $nr_start = $serie_row ? (int)$serie_row['nr_start'] : 1;

        $nou_nr_curent = max($nr_start, $max_nr_ramas + 1);
        if ($nou_nr_curent < $nr_curent) {
            $pdo->prepare("UPDATE incasari_serii SET nr_curent = ? WHERE tip_serie = ?")->execute([$nou_nr_curent, $tip_serie]);
        }
    }
}

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
$log_msg = "Încasări: ștearsă încasare ID {$id} – {$suma} RON";
if ($seria) {
    $log_msg .= ", chitanță {$seria} nr. {$nr_chitanta}";
}
$log_msg .= ", {$nume}";
log_activitate($pdo, $log_msg, null, $inc['membru_id'] ?? null);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
