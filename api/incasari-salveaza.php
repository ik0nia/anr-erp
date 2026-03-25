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
$reprezentand = trim($_POST['reprezentand'] ?? '') ?: null;

$tipuri = [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE];
$moduri = [INCASARI_MOD_NUMERAR, INCASARI_MOD_CHITANTA_VECHE, INCASARI_MOD_CARD_POS, INCASARI_MOD_CARD_ONLINE, INCASARI_MOD_TRANSFER, INCASARI_MOD_MANDAT_POSTAL];

if ($membru_id <= 0 || !in_array($tip, $tipuri) || !in_array($mod_plata, $moduri)) {
    echo json_encode(['ok' => false, 'eroare' => 'Date invalide (membru, tip sau mod plată).']);
    exit;
}

$anul = null;
if ($tip === INCASARI_TIP_COTIZATIE) {
    $anul = incasari_an_cotizatie_implicit($pdo);
    cotizatii_ensure_tables($pdo);
    if ($reprezentand === null || $reprezentand === '') {
        $reprezentand = 'Cotizatie membru ' . $anul;
    }
    if ($suma <= 0) {
        $stmt = $pdo->prepare("SELECT hgrad, insotitor FROM membri WHERE id = ?");
        $stmt->execute([$membru_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $grad = $row['hgrad'] ?? 'Fara handicap';
        $asistent = cotizatii_map_insotitor_to_asistent($row['insotitor'] ?? '');
        $suma = incasari_valoare_cotizatie_anuala($pdo, $anul, $grad, $asistent);
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
$id = incasari_adauga($pdo, $membru_id, $tip, $anul, $suma, $mod_plata, $data_incasare, $utilizator, $observatii, null, $reprezentand);
if (!$id) {
    echo json_encode(['ok' => false, 'eroare' => 'Eroare la salvare.']);
    exit;
}

require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/registru_interactiuni_v2_helper.php';
log_activitate($pdo, "Încasări: încasare salvată ID {$id} – tip {$tip}, {$suma} RON, membru {$membru_id}", null, $membru_id);

$inc = incasari_get($pdo, $id);
$doc_info = ($inc['seria_chitanta'] ?? '-') . ' nr. ' . ($inc['nr_chitanta'] ?? '-');
if (!empty($inc['seria_chitanta'])) {
    log_activitate($pdo, "Document emis: Chitanță {$doc_info} – {$suma} RON", null, $membru_id);
}

// Înregistrează în Registrul de Interacțiuni v2
$tipuri_afis = incasari_tipuri_afisare();
$tip_afis = $tipuri_afis[$tip] ?? $tip;
$nume_persoana = trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? ''));
$descriere_registru = "{$tip_afis}: {$suma} RON";
if (!empty($inc['seria_chitanta'])) {
    $descriere_registru .= " – Chitanță {$doc_info}";
}
registru_v2_adauga_incasare($pdo, $nume_persoana, $descriere_registru, $utilizator);
echo json_encode([
    'ok' => true,
    'id' => $id,
    'seria_chitanta' => $inc['seria_chitanta'] ?? null,
    'nr_chitanta' => $inc['nr_chitanta'] ?? null,
    'tip' => $tip,
    'mod_plata' => $mod_plata,
], JSON_UNESCAPED_UNICODE);
