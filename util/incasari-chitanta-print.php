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
$incasat_de = $inc['created_by'] ?? 'Utilizator';

$reprezentant = !empty($inc['reprezentand']) ? $inc['reprezentand'] : '';
if ($reprezentant === '') {
    $reprezentant = 'Cotizație anul ' . ($inc['anul'] ?? date('Y'));
    if (($inc['tip'] ?? '') === INCASARI_TIP_DONATIE) $reprezentant = 'Donație';
    elseif (($inc['tip'] ?? '') === INCASARI_TIP_TAXA_PARTICIPARE) $reprezentant = 'Taxă participare';
    elseif (($inc['tip'] ?? '') === INCASARI_TIP_ALTE) $reprezentant = 'Alte încasări';
}

$suma_litere = incasari_suma_in_litere($inc['suma']);
$nume = trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? ''));
$cnp = $inc['cnp'] ?? '';
$domloc = $inc['domloc'] ?? '';
$judet = $inc['judet_domiciliu'] ?? '';
if ($domloc === '' && $judet === '' && $inc['contact_id']) {
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
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .a4-sheet { box-shadow: none !important; }
        }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 8px; }
        .a4-sheet { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 0; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .chitanta-half { width: 210mm; height: 148.5mm; padding: 8mm; box-sizing: border-box; border-bottom: 1px dashed #ccc; position: relative; }
        .chitanta-half:last-child { border-bottom: 0; }
        .chitanta-half .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4mm; }
        .chitanta-half .date-asociatie { flex: 1; white-space: pre-wrap; font-size: 10px; }
        .chitanta-half .logo { width: 32mm; text-align: right; }
        .chitanta-half .logo img { max-width: 100%; max-height: 22mm; }
        .chitanta-half h1 { text-align: center; font-size: 13px; margin: 2mm 0; }
        .chitanta-half .seria-nr { text-align: center; margin-bottom: 3mm; font-weight: bold; }
        .chitanta-half .text-incasare { margin: 3mm 0; line-height: 1.35; }
        .chitanta-half .footer { position: absolute; right: 8mm; bottom: 18mm; text-align: right; }
        .chitanta-half .footer .semnatura { margin-top: 2mm; border-bottom: 1px solid #000; width: 25mm; display: inline-block; }
        .no-print { text-align: center; margin: 10px 0; }
        .no-print a, .no-print button { margin: 0 4px; padding: 8px 16px; text-decoration: none; background: #b45309; color: #fff; border: 0; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .no-print a:hover, .no-print button:hover { background: #92400a; }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="incasari-chitanta-pdf.php?id=<?php echo $id; ?>">Descarcă PDF</a>
        <button type="button" onclick="window.print();">Tipărește</button>
    </div>
    <div class="a4-sheet">
        <?php for ($ex = 1; $ex <= 2; $ex++): ?>
        <div class="chitanta-half">
            <div class="header">
                <div class="date-asociatie"><?php echo nl2br(htmlspecialchars($date_asociatie)); ?></div>
                <?php if ($logo_url): ?>
                <div class="logo"><img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo"></div>
                <?php endif; ?>
            </div>
            <h1>CHITANȚA</h1>
            <div class="seria-nr">Seria: <?php echo htmlspecialchars($seria); ?> &nbsp; Nr. <?php echo htmlspecialchars($nr); ?></div>
            <div class="text-incasare"><?php echo htmlspecialchars($text_chitanta); ?></div>
            <div class="footer">
                Încasat de: <?php echo htmlspecialchars($incasat_de); ?><br>
                Semnătura: <span class="semnatura"></span>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <script>
        window.onload = function() { window.print(); };
    </script>
</body>
</html>
