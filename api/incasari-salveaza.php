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
// Mandat postal: bifa spune daca banii au fost incasati in numerar (intra in casa)
// sau au venit prin banca (se inregistreaza plata, fara chitanta ERP).
$mandat_numerar = !empty($_POST['mandat_numerar']) ? 1 : 0;

$tipuri = [INCASARI_TIP_COTIZATIE, INCASARI_TIP_DONATIE, INCASARI_TIP_TAXA_PARTICIPARE, INCASARI_TIP_ALTE];
$moduri = [INCASARI_MOD_NUMERAR, INCASARI_MOD_CHITANTA_VECHE, INCASARI_MOD_CARD_POS, INCASARI_MOD_CARD_ONLINE, INCASARI_MOD_TRANSFER, INCASARI_MOD_MANDAT_POSTAL];

if ($membru_id <= 0 || !in_array($tip, $tipuri) || !in_array($mod_plata, $moduri)) {
    echo json_encode(['ok' => false, 'eroare' => 'Date invalide (membru, tip sau mod plată).']);
    exit;
}

// Anul cotizatiei vine din dropdown-ul „Cotizatie pentru anul”. Gol inseamna „Alta plata”.
$an_cotizatie_post = trim((string)($_POST['an_cotizatie'] ?? ''));

$anul = null;
if ($tip === INCASARI_TIP_COTIZATIE) {
    cotizatii_ensure_tables($pdo);

    if ($an_cotizatie_post === '') {
        echo json_encode(['ok' => false, 'eroare' => 'Selectați anul cotizației sau schimbați tipul încasării.']);
        exit;
    }

    $anul = (int)$an_cotizatie_post;
    $situatie = cotizatii_situatie_membru($pdo, $membru_id);
    $status_dosar = (string)($situatie['status_dosar'] ?? 'Activ');
    if ($status_dosar !== 'Activ') {
        echo json_encode(['ok' => false, 'eroare' => 'Membrul are status dosar ' . $status_dosar . ' și nu datorează cotizație.']);
        exit;
    }

    $rand_an = $situatie['ani'][$anul] ?? null;
    if ($rand_an === null || $rand_an['status'] === 'nedefinit') {
        echo json_encode(['ok' => false, 'eroare' => 'Pentru anul ' . $anul . ' nu există o cotizație definită în Setări pentru acest membru.']);
        exit;
    }
    if ($rand_an['status'] === 'scutit') {
        echo json_encode(['ok' => false, 'eroare' => 'Membrul este scutit de plata cotizației pentru anul ' . $anul . '.']);
        exit;
    }
    if ($rand_an['status'] === 'achitat') {
        echo json_encode(['ok' => false, 'eroare' => 'Cotizația pentru anul ' . $anul . ' este deja achitată.']);
        exit;
    }

    // Anul apare pe chitanta: „Cotizatie membru 2027”.
    $reprezentand = incasari_reprezentand_cotizatie($anul, $mod_plata);

    if ($suma <= 0) {
        $suma = (float)$rand_an['rest'];
    }
    if ($suma <= 0) {
        echo json_encode(['ok' => false, 'eroare' => 'Introduceți valoarea cotizației.']);
        exit;
    }
} else {
    if ($an_cotizatie_post !== '') {
        echo json_encode(['ok' => false, 'eroare' => 'Ați selectat un an de cotizație, dar tipul încasării nu este cotizație.']);
        exit;
    }
    if ($suma <= 0) {
        echo json_encode(['ok' => false, 'eroare' => 'Introduceți suma.']);
        exit;
    }
}

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
incasari_ensure_tables($pdo);
$id = incasari_adauga($pdo, $membru_id, $tip, $anul, $suma, $mod_plata, $data_incasare, $utilizator, $observatii, null, $reprezentand, $mandat_numerar);
if (!$id) {
    echo json_encode(['ok' => false, 'eroare' => 'Eroare la salvare.']);
    exit;
}

require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/registru_interactiuni_v2_helper.php';
$detaliu_an = $anul ? " pentru anul {$anul}" : '';
log_activitate($pdo, "Încasări: încasare salvată ID {$id} – tip {$tip}{$detaliu_an}, {$suma} RON, membru {$membru_id}", null, $membru_id);

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
    'an_cotizatie' => $anul,
    'mod_plata' => $mod_plata,
], JSON_UNESCAPED_UNICODE);
