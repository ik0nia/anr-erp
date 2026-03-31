<?php
/**
 * Salvare încasare din Dashboard: donație (donator extern sau membru) sau cotizație.
 * Donator extern: creează contact Donator, înregistrează încasarea cu contact_id.
 * Loghează orice document emis (chitanță).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/contacte_helper.php';
require_once __DIR__ . '/../includes/cotizatii_helper.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'eroare' => 'Metodă neacceptată']);
    exit;
}
csrf_require_valid();

$tip_form = isset($_POST['tip_form']) ? $_POST['tip_form'] : ''; // 'donatie' | 'cotizatie'
$mod_plata = $_POST['mod_plata'] ?? '';
$data_incasare = trim($_POST['data_incasare'] ?? date('Y-m-d'));
$reprezentand = trim($_POST['reprezentand'] ?? '') ?: null;

$moduri = [INCASARI_MOD_NUMERAR, INCASARI_MOD_CHITANTA_VECHE, INCASARI_MOD_CARD_POS, INCASARI_MOD_CARD_ONLINE, INCASARI_MOD_TRANSFER, INCASARI_MOD_MANDAT_POSTAL];
if (!in_array($mod_plata, $moduri)) {
    echo json_encode(['ok' => false, 'eroare' => 'Selectați modul de plată.']);
    exit;
}

$utilizator = $_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator';
incasari_ensure_tables($pdo);
ensure_contacte_table($pdo);

if ($tip_form === 'donatie') {
    // Donație de la persoană care NU e în baza de membri
    $nume = trim($_POST['nume_donator'] ?? '');
    $prenume = trim($_POST['prenume_donator'] ?? '');
    $cnp = trim($_POST['cnp_donator'] ?? '') ?: null;
    $telefon = trim($_POST['telefon_donator'] ?? '') ?: null;
    $email = trim($_POST['email_donator'] ?? '') ?: null;
    $suma = (float)str_replace(',', '.', $_POST['valoare'] ?? 0);

    // Pentru metodele cu identificare directă pe chitanță, numele și prenumele sunt obligatorii.
    $moduri_cu_date_personale_obligatorii = [
        INCASARI_MOD_NUMERAR,
        INCASARI_MOD_CHITANTA_VECHE,
        INCASARI_MOD_MANDAT_POSTAL,
    ];
    if (in_array($mod_plata, $moduri_cu_date_personale_obligatorii, true)) {
        if ($nume === '') {
            echo json_encode(['ok' => false, 'eroare' => 'Numele donatorului este obligatoriu pentru modul de plată selectat.']);
            exit;
        }
        if ($prenume === '') {
            echo json_encode(['ok' => false, 'eroare' => 'Prenumele donatorului este obligatoriu pentru modul de plată selectat.']);
            exit;
        }
    }
    // La Card-POS, Card-Online, Transfer bancar, datele personale nu sunt obligatorii
    if ($nume === '') {
        $nume = 'Donator';
    }

    if ($suma <= 0) {
        echo json_encode(['ok' => false, 'eroare' => 'Introduceți valoarea donației.']);
        exit;
    }

    $reprezentand = $reprezentand ?: 'Donație';

    $contact_id = contacte_creare_donator($pdo, $nume, $prenume ?: null, $cnp, $telefon, $email);
    if (!$contact_id) {
        echo json_encode(['ok' => false, 'eroare' => 'Eroare la crearea contactului donator.']);
        exit;
    }

    $id = incasari_adauga($pdo, null, INCASARI_TIP_DONATIE, null, $suma, $mod_plata, $data_incasare, $utilizator, null, $contact_id, $reprezentand);
    if (!$id) {
        echo json_encode(['ok' => false, 'eroare' => 'Eroare la salvare încasare.']);
        exit;
    }

    log_activitate($pdo, "Contacte: creat donator " . trim($nume . ' ' . $prenume));
    log_activitate($pdo, "Încasări: donație salvată ID {$id} – " . $suma . " RON, donator extern.");
    $inc = incasari_get($pdo, $id);
    $doc_info = ($inc['seria_chitanta'] ?? '-') . ' nr. ' . ($inc['nr_chitanta'] ?? '-');
    log_activitate($pdo, "Document emis: Chitanță Donație {$doc_info}");
} elseif ($tip_form === 'cotizatie') {
    // Cotizație sau donație de la membru (membru_id trimis)
    $membru_id = (int)($_POST['membru_id'] ?? 0);
    $tip_incasare = isset($_POST['tip_incasare_membru']) ? $_POST['tip_incasare_membru'] : 'cotizatie'; // 'cotizatie' | 'donatie'
    $suma = (float)str_replace(',', '.', $_POST['valoare'] ?? 0);

    if ($membru_id <= 0) {
        echo json_encode(['ok' => false, 'eroare' => 'Selectați un membru.']);
        exit;
    }

    $anul = (int)date('Y');
    if ($tip_incasare === 'cotizatie') {
        if ($suma <= 0) {
            $stmt = $pdo->prepare("SELECT hgrad, insotitor FROM membri WHERE id = ?");
            $stmt->execute([$membru_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $grad = $row['hgrad'] ?? 'Fara handicap';
            $asistent = cotizatii_map_insotitor_to_asistent($row['insotitor'] ?? '');
            $suma = incasari_valoare_cotizatie_anuala($pdo, $anul, $grad, $asistent);
        }
        $reprezentand = $reprezentand ?: 'Cotizatie membru';
        $id = incasari_adauga($pdo, $membru_id, INCASARI_TIP_COTIZATIE, $anul, $suma, $mod_plata, $data_incasare, $utilizator, null, null, $reprezentand);
    } else {
        if ($suma <= 0) {
            echo json_encode(['ok' => false, 'eroare' => 'Introduceți valoarea donației.']);
            exit;
        }
        $reprezentand = $reprezentand ?: 'Donație';
        $id = incasari_adauga($pdo, $membru_id, INCASARI_TIP_DONATIE, null, $suma, $mod_plata, $data_incasare, $utilizator, null, null, $reprezentand);
    }

    if (!$id) {
        echo json_encode(['ok' => false, 'eroare' => 'Eroare la salvare încasare.']);
        exit;
    }

    log_activitate($pdo, "Încasări: încasare salvată ID {$id} – tip {$tip_incasare}, {$suma} RON, membru {$membru_id}");
    $inc = incasari_get($pdo, $id);
    $doc_info = ($inc['seria_chitanta'] ?? '-') . ' nr. ' . ($inc['nr_chitanta'] ?? '-');
    log_activitate($pdo, "Document emis: Chitanță " . ($tip_incasare === 'cotizatie' ? 'Cotizație' : 'Donație') . " {$doc_info}");
} else {
    echo json_encode(['ok' => false, 'eroare' => 'Tip formular invalid.']);
    exit;
}

$inc = incasari_get($pdo, $id);

// Înregistrează în Registrul de Interacțiuni v2
require_once __DIR__ . '/../includes/registru_interactiuni_v2_helper.php';
$tipuri_afis = incasari_tipuri_afisare();
$tip_reg = $tip_form === 'cotizatie' ? ($tip_incasare ?? 'cotizatie') : 'donatie';
$tip_afis = $tipuri_afis[$tip_reg] ?? $tip_reg;
$nume_persoana = trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? ''));
$descriere_registru = "{$tip_afis}: {$suma} RON";
$doc_info_reg = ($inc['seria_chitanta'] ?? '-') . ' nr. ' . ($inc['nr_chitanta'] ?? '-');
if (!empty($inc['seria_chitanta'])) {
    $descriere_registru .= " – Chitanță {$doc_info_reg}";
}
registru_v2_adauga_incasare($pdo, $nume_persoana, $descriere_registru, $utilizator);

echo json_encode([
    'ok' => true,
    'id' => $id,
    'seria_chitanta' => $inc['seria_chitanta'] ?? null,
    'nr_chitanta' => $inc['nr_chitanta'] ?? null,
], JSON_UNESCAPED_UNICODE);
