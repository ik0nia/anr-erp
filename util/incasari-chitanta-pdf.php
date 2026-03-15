<?php
/**
 * Descarcă chitanța încasare în format PDF - 2 exemplare pe o coală A4.
 * La generare se trimite chitanța prin API către FGO.
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

// Trimite chitanța către FGO la generare
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
if ($domloc === '' && $judet === '' && !empty($inc['contact_id'])) {
    $domloc = '-';
    $judet = '-';
}
$seria = $inc['seria_chitanta'] ?? '-';
$nr = $inc['nr_chitanta'] ?? '-';

$text_chitanta = "Am primit de la " . htmlspecialchars($nume) . ", CNP: " . htmlspecialchars($cnp) . ", din loc. " . htmlspecialchars($domloc) . ", Județ: " . htmlspecialchars($judet) . ", suma de " . htmlspecialchars($suma_litere) . " (" . htmlspecialchars($inc['suma']) . " RON), reprezentând " . htmlspecialchars($reprezentant) . ".";
$date_asoc_esc = nl2br(htmlspecialchars($date_asociatie));
$logo_img = $logo_url ? '<img src="' . htmlspecialchars($logo_url) . '" style="max-width:32mm;max-height:22mm;" alt="">' : '';

$block = '
<div style="height:148mm; padding:8mm; border-bottom:1px dashed #ccc; position:relative;">
    <div style="display:table; width:100%; margin-bottom:4mm;">
        <div style="display:table-cell; width:70%; white-space:pre-wrap; font-size:10px;">' . $date_asoc_esc . '</div>
        <div style="display:table-cell; width:30%; text-align:right;">' . $logo_img . '</div>
    </div>
    <h1 style="text-align:center; font-size:13px; margin:2mm 0;">CHITANȚA</h1>
    <p style="text-align:center; margin-bottom:3mm; font-weight:bold;">Seria: ' . htmlspecialchars($seria) . ' &nbsp; Nr. ' . htmlspecialchars($nr) . '</p>
    <p style="margin:3mm 0; line-height:1.35;">' . $text_chitanta . '</p>
    <div style="position:absolute; right:8mm; bottom:18mm; text-align:right;">
        Încasat de: ' . htmlspecialchars($incasat_de) . '<br>
        Semnătura: <span style="border-bottom:1px solid #000; width:25mm; display:inline-block;"></span>
    </div>
</div>';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    header('Location: incasari-chitanta-print.php?id=' . $id);
    exit;
}
require_once $autoload;

try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
    ]);
    $mpdf->WriteHTML($block);
    $mpdf->WriteHTML($block);
    $filename = 'chitanta-' . preg_replace('/[^a-z0-9_-]/i', '-', $seria . '-' . $nr) . '.pdf';
    $mpdf->Output($filename, 'D');
    exit;
} catch (Exception $e) {
    header('Location: incasari-chitanta-print.php?id=' . $id . '&eroare=pdf');
    exit;
}
