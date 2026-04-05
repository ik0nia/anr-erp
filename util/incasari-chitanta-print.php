<?php
/**
 * Tipărire chitanță încasare - 2 exemplare pe o coală A4.
 * La încărcare se trimite chitanța prin API către FGO.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/incasari_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /membri');
    exit;
}
incasari_ensure_tables($pdo);
$inc = incasari_get($pdo, $id);
if (!$inc) {
    header('Location: /membri');
    exit;
}

// Trimite chitanța către FGO la generare/tipărire
incasari_trimite_fgo($pdo, $inc);

$logo_url = incasari_get_setare($pdo, 'logo_chitanta') ?: (defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : '');
$date_asociatie = incasari_get_setare($pdo, 'date_asociatie') ?: '';
$info_suplimentara_img = trim((string)(incasari_get_setare($pdo, 'informatii_suplimentare_chitanta_image_path') ?? ''));
$info_suplimentara_img_url = '';
if ($info_suplimentara_img !== '') {
    $abs = APP_ROOT . '/' . ltrim($info_suplimentara_img, '/');
    if (is_file($abs)) {
        $info_suplimentara_img_url = '/' . ltrim($info_suplimentara_img, '/');
    }
}
$incasat_de = $inc['created_by'] ?? 'Utilizator';
$dimensiune_chitanta = incasari_get_setare($pdo, 'dimensiune_chitanta') ?: 'a5';
$format_pdf_link = $dimensiune_chitanta === 'a4' ? '' : '&format=a5';
$format_pdf_label = $dimensiune_chitanta === 'a4' ? 'PDF A4' : 'PDF A5';

$reprezentant = !empty($inc['reprezentand']) ? trim((string)$inc['reprezentand']) : '';
if (($inc['tip'] ?? '') === INCASARI_TIP_COTIZATIE && ($reprezentant === '' || preg_match('/^Cotizatie membru\s+0$/', $reprezentant))) {
    $reprezentant = 'Cotizatie membru';
}
if ($reprezentant === '') {
    if (($inc['tip'] ?? '') === INCASARI_TIP_DONATIE) $reprezentant = 'Donație';
    elseif (($inc['tip'] ?? '') === INCASARI_TIP_TAXA_PARTICIPARE) $reprezentant = 'Taxă participare';
    elseif (($inc['tip'] ?? '') === INCASARI_TIP_ALTE) $reprezentant = 'Alte încasări';
    else $reprezentant = 'Cotizatie membru';
}

$suma_litere = incasari_suma_in_litere($inc['suma']);
$nume = trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? ''));
$cnp = $inc['cnp'] ?? '';
$domloc = $inc['domloc'] ?? '';
$judet = $inc['judet_domiciliu'] ?? '';
if (($domloc === '' || $judet === '') && !empty($inc['contact_notite'])) {
    $notite = (string)$inc['contact_notite'];
    if ($domloc === '' && preg_match('/Localitate:\s*([^,]+)/iu', $notite, $mLoc)) {
        $domloc = trim((string)$mLoc[1]);
    }
    if ($judet === '' && preg_match('/Judet:\s*([^,]+)/iu', $notite, $mJudet)) {
        $judet = trim((string)$mJudet[1]);
    }
}
if ($domloc === '' && $judet === '' && !empty($inc['contact_id'])) {
    $domloc = '-';
    $judet = '-';
}
$seria = $inc['seria_chitanta'] ?? '-';
$nr = $inc['nr_chitanta'] ?? '-';

$text_chitanta = "Am primit de la {$nume}, CNP: {$cnp}, din loc. {$domloc}, Județ: {$judet}, suma de {$suma_litere} ({$inc['suma']} RON), reprezentând {$reprezentant}.";
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chitanță <?php echo htmlspecialchars($seria . ' ' . $nr); ?></title>
    <style>
        /* Print pe A4; conținutul util rămâne în 21 x 28 cm. */
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @media print {
            html, body { margin: 0 !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .a4-sheet { box-shadow: none !important; page-break-after: avoid; page-break-inside: avoid; break-inside: avoid; }
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 0; }
        .no-print { text-align: center; padding: 10px 0; }
        .no-print a, .no-print button { margin: 0 4px; padding: 8px 16px; text-decoration: none; background: #b45309; color: #fff; border: 0; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .no-print a:hover, .no-print button:hover { background: #92400a; }
        /* Șablon de tipărire: 21 x 28 cm (două chitanțe a câte 21 x 14 cm). */
        .a4-sheet { width: 210mm; height: 280mm; margin: 0 auto; padding: 0; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .chitanta-half {
            width: 210mm;
            height: 140mm;
            padding: 5mm 8mm;
            overflow: hidden;
        }
        .chitanta-half:first-child { border-bottom: 1px dashed #ccc; }
        .chitanta-half .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2mm; }
        .chitanta-half .date-asociatie { flex: 1; white-space: pre-wrap; font-size: 9px; line-height: 1.15; }
        .chitanta-half .logo { width: 32mm; text-align: right; }
        .chitanta-half .logo img { max-width: 100%; max-height: 18mm; }
        .chitanta-half h1 { text-align: center; font-size: 15px; margin: 2mm 0 0 0; }
        .chitanta-half .seria-nr { text-align: center; font-weight: bold; margin: 0; }
        .chitanta-half .spacer { height: 8mm; }
        .chitanta-half .text-incasare { line-height: 1.4; }
        .chitanta-half .footer { text-align: right; margin-top: 12mm; }
        .chitanta-half .footer .semnatura { margin-top: 2mm; border-bottom: 1px solid #000; width: 25mm; display: inline-block; }
        .chitanta-half { position: relative; }
        .info-suplimentara-card {
            position: absolute;
            left: 8mm;
            bottom: 20mm; /* ~2 cm de linia de tăiere dintre cele 2 chitanțe */
            width: 55mm;  /* 5.5 cm */
            height: 85mm; /* 8.5 cm */
            overflow: hidden;
        }
        .info-suplimentara-card img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print();">Tipărește</button>
        <a href="incasari-chitanta-pdf.php?id=<?php echo $id; ?><?php echo $format_pdf_link; ?>"><?php echo htmlspecialchars($format_pdf_label); ?></a>
        <a href="incasari-chitanta-pdf.php?id=<?php echo $id; ?>&format=a5">PDF A5</a>
        <?php $pdf_a5_url = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . '/util/incasari-chitanta-pdf.php?id=' . $id . $format_pdf_link; ?>
        <a href="https://wa.me/?text=<?php echo urlencode('Chitanta ' . $seria . ' nr. ' . $nr . ' - ' . $inc['suma'] . ' RON - ' . $pdf_a5_url); ?>" target="_blank" rel="noopener noreferrer">Distribuie WhatsApp</a>
        <a href="mailto:?subject=<?php echo urlencode('Chitanta ' . $seria . ' nr. ' . $nr); ?>&body=<?php echo urlencode('Chitanta ' . $seria . ' nr. ' . $nr . ' - ' . $inc['suma'] . ' RON' . "\n" . $pdf_a5_url); ?>">Distribuie Email</a>
    </div>
    <div class="a4-sheet">
        <?php for ($ex = 1; $ex <= 2; $ex++): ?>
        <div class="chitanta-half">
            <?php if ($date_asociatie !== '' || $logo_url): ?>
            <div class="header">
                <div class="date-asociatie"><?php echo nl2br(htmlspecialchars($date_asociatie)); ?></div>
                <?php if ($logo_url): ?>
                <div class="logo"><img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo"></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <h1>CHITANȚA</h1>
            <div class="seria-nr">Seria: <?php echo htmlspecialchars($seria); ?> &nbsp;&nbsp; Nr. <?php echo htmlspecialchars($nr); ?> &nbsp;&nbsp; Data: <?php echo date('d.m.Y', strtotime($inc['data_incasare'])); ?></div>
            <div class="spacer"></div>
            <div class="text-incasare"><?php echo htmlspecialchars($text_chitanta); ?></div>
            <div class="footer">
                Încasat de: <?php echo htmlspecialchars($incasat_de); ?><br>
                Semnătura: <span class="semnatura"></span>
            </div>
            <?php if ($info_suplimentara_img_url !== ''): ?>
            <div class="info-suplimentara-card" aria-hidden="true">
                <img src="<?php echo htmlspecialchars($info_suplimentara_img_url); ?>" alt="">
            </div>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
