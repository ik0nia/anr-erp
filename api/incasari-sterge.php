<?php
/**
 * API ștergere încasare. La ștergere, dacă încasarea avea chitanță cu ultimul nr din serie,
 * decrementează nr_curent din incasari_serii (refacerea indexului).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/mailer_functions.php';

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

try {
    $pdo->beginTransaction();

    // Ștergem încasarea
    $stmt = $pdo->prepare("DELETE FROM incasari WHERE id = ?");
    $stmt->execute([$id]);

    // Refacem indexul: dacă nr chitanță șters era ultimul emis (nr_curent - 1), decrementăm
    if ($seria !== null && $nr_chitanta !== null) {
        $tip_serie = incasari_tip_serie_pentru_tip($tip);
        $serie_cfg_donatii = (string)((incasari_get_serie($pdo, 'donatii')['serie'] ?? ''));
        $serie_cfg_incasari = (string)((incasari_get_serie($pdo, 'incasari')['serie'] ?? ''));
        if ($seria === $serie_cfg_incasari && $serie_cfg_incasari !== '') {
            $tip_serie = 'incasari';
        } elseif ($seria === $serie_cfg_donatii && $serie_cfg_donatii !== '') {
            $tip_serie = 'donatii';
        }
        $stmt = $pdo->prepare("SELECT nr_curent, nr_start FROM incasari_serii WHERE tip_serie = ? FOR UPDATE");
        $stmt->execute([$tip_serie]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $nr_curent = (int)$row['nr_curent'];

            // Recalcul pe seria efectivă a chitanței (acoperă și înregistrările istorice)
            $stmt2 = $pdo->prepare("SELECT MAX(nr_chitanta) as max_nr FROM incasari WHERE seria_chitanta = ?");
            $stmt2->execute([$seria]);
            $max_row = $stmt2->fetch(PDO::FETCH_ASSOC);
            $max_nr_ramas = $max_row && $max_row['max_nr'] !== null ? (int)$max_row['max_nr'] : 0;

            // Noul nr_curent = max_nr_ramas + 1 (sau nr_start dacă nu mai există chitanțe)
            $nr_start = $row ? (int)$row['nr_start'] : 1;
            $nou_nr_curent = max($nr_start, $max_nr_ramas + 1);
            if ($nou_nr_curent < $nr_curent) {
                $pdo->prepare("UPDATE incasari_serii SET nr_curent = ? WHERE tip_serie = ?")->execute([$nou_nr_curent, $tip_serie]);
            }
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('incasari-sterge: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'eroare' => 'Eroare la ștergerea chitanței. Reîncercați.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
$log_msg = "Încasări: ștearsă încasare ID {$id} – {$suma} RON";
if ($seria) {
    $log_msg .= ", chitanță {$seria} nr. {$nr_chitanta}";
}
$log_msg .= ", {$nume}";
log_activitate($pdo, $log_msg, null, $inc['membru_id'] ?? null);

$email_notificare = trim((string)(incasari_get_setare($pdo, 'email_notificari_stergere_chitanta') ?? ''));
if ($email_notificare !== '' && filter_var($email_notificare, FILTER_VALIDATE_EMAIL)) {
    $subiect = 'Notificare ștergere chitanță - CRM ANR Bihor';
    $mesaj = "A fost ștearsă o chitanță din modulul Încasări.\n"
        . "ID încasare: {$id}\n"
        . "Tip: {$tip}\n"
        . "Persoană: {$nume}\n"
        . "Sumă: {$suma} RON\n"
        . "Chitanță: " . ($seria ? ($seria . ' nr. ' . $nr_chitanta) : 'fără serie') . "\n"
        . "Șters de: " . ($_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator') . "\n"
        . "Data/ora: " . date('d.m.Y H:i:s');
    sendAutomatedEmail($pdo, $email_notificare, $subiect, $mesaj);
}

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
