<?php
/**
 * API salvare încasare (cotizație, donație, taxă participare, alte). Returnează id și eventual seria/nr chitanță.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/cotizatii_helper.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/csrf_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'eroare' => 'Metodă neacceptată']);
    exit;
}
csrf_require_valid();
$membru_id = (int)($_POST['membru_id'] ?? 0);
$tip = $_POST['tip'] ?? '';
$mod_plata = $_POST['mod_plata'] ?? '';
$data_incasare = trim($_POST['data_incasare'] ?? date('Y-m-d'));
$suma = (float)str_replace(',', '.', $_POST['suma'] ?? 0);
$observatii = trim($_POST['observatii'] ?? '') ?: null;

$tipuri = [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE];
$moduri = [INCASARI_MOD_NUMERAR, INCASARI_MOD_CARD_POS, INCASARI_MOD_CARD_ONLINE, INCASARI_MOD_TRANSFER];

if ($membru_id <= 0 || !in_array($tip, $tipuri) || !in_array($mod_plata, $moduri)) {
    echo json_encode(['ok' => false, 'eroare' => 'Date invalide (membru, tip sau mod plată).']);
    exit;
}

$anul = null;
if ($tip === INCASARI_TIP_COTIZATIE) {
    $anul = (int)date('Y');
    cotizatii_ensure_tables($pdo);
    if ($suma <= 0) {
        $stmt = $pdo->prepare("SELECT hgrad FROM membri WHERE id = ?");
        $stmt->execute([$membru_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $grad = $row['hgrad'] ?? 'Fara handicap';
        $suma = incasari_valoare_cotizatie_anuala($pdo, $anul, $grad);
        if ($suma <= 0) $suma = 0;
    }
} else {
    if ($suma <= 0) {
        echo json_encode(['ok' => false, 'eroare' => 'Introduceți suma.']);
        exit;
    }
}

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
incasari_ensure_tables($pdo);
$id = incasari_adauga($pdo, $membru_id, $tip, $anul, $suma, $mod_plata, $data_incasare, $utilizator, $observatii);
if (!$id) {
    echo json_encode(['ok' => false, 'eroare' => 'Eroare la salvare.']);
    exit;
}

require_once __DIR__ . '/../includes/log_helper.php';
log_activitate($pdo, "Încasări: încasare salvată ID {$id} – tip {$tip}, {$suma} RON, membru {$membru_id}");

$inc = incasari_get($pdo, $id);
echo json_encode([
    'ok' => true,
    'id' => $id,
    'seria_chitanta' => $inc['seria_chitanta'] ?? null,
    'nr_chitanta' => $inc['nr_chitanta'] ?? null,
    'tip' => $tip,
    'mod_plata' => $mod_plata,
], JSON_UNESCAPED_UNICODE);
