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
$info_supl_img = incasari_get_setare($pdo, 'informatii_suplimentare_chitanta_image_path') ?: '';
$info_supl_img_abs = $info_supl_img !== '' ? APP_ROOT . '/' . ltrim($info_supl_img, '/') : '';
$info_supl_img_tag = '';
if ($info_supl_img !== '' && is_file($info_supl_img_abs)) {
    $info_supl_img_tag = '<img src="' . htmlspecialchars('/' . ltrim($info_supl_img, '/')) . '" style="width:55mm;height:85mm;object-fit:contain;" alt="Informatii suplimentare chitanta">';
}

$reprezentant = !empty($inc['reprezentand']) ? trim((string)$inc['reprezentand']) : '';
if (($inc['tip'] ?? '') === INCASARI_TIP_COTIZATIE && ($reprezentant === '' || preg_match('/^Cotizatie membru\s+0$/', $reprezentant))) {
    $reprezentant = 'Cotizatie membru';
}
if ($reprezentant === '') {
    $reprezentant = 'Cotizatie membru';
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
    $notite_contact = trim((string)($inc['contact_notite'] ?? ''));
    if ($notite_contact !== '') {
        if (preg_match('/Localitate:\s*([^,;]+)/i', $notite_contact, $m)) {
            $domloc = trim((string)$m[1]);
        }
        if (preg_match('/Judet:\s*([^,;]+)/i', $notite_contact, $m)) {
            $judet = trim((string)$m[1]);
        }
    }
    if ($domloc === '') $domloc = '-';
    if ($judet === '') $judet = '-';
}
$seria = $inc['seria_chitanta'] ?? '-';
$nr = $inc['nr_chitanta'] ?? '-';

$text_chitanta = "Am primit de la " . htmlspecialchars($nume) . ", CNP: " . htmlspecialchars($cnp) . ", din loc. " . htmlspecialchars($domloc) . ", Județ: " . htmlspecialchars($judet) . ", suma de " . htmlspecialchars($suma_litere) . " (" . htmlspecialchars($inc['suma']) . " RON), reprezentând " . htmlspecialchars($reprezentant) . ".";
$date_asoc_esc = nl2br(htmlspecialchars($date_asociatie));
$logo_img = $logo_url ? '<img src="' . htmlspecialchars($logo_url) . '" style="max-width:30mm;max-height:18mm;" alt="">' : '';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    header('Location: incasari-chitanta-print.php?id=' . $id);
    exit;
}
require_once $autoload;

$dimensiune_setata = strtolower(trim((string)(incasari_get_setare($pdo, 'dimensiune_chitanta') ?: 'a5')));
if (!in_array($dimensiune_setata, ['a4', 'a5'], true)) {
    $dimensiune_setata = 'a5';
}
$format = strtolower(trim((string)($_GET['format'] ?? $dimensiune_setata)));
if (!in_array($format, ['a4', 'a5'], true)) {
    $format = $dimensiune_setata;
}
$template = strtolower(trim((string)(incasari_get_setare($pdo, 'template_chitanta') ?: 'standard')));
if (!in_array($template, ['standard', 'minimal'], true)) {
    $template = 'standard';
}

// Construim conținutul unei chitanțe
$one = '';
if ($template === 'standard' && ($date_asoc_esc !== '' || $logo_img !== '')) {
    $one .= '<table width="100%"><tr>';
    $one .= '<td width="70%" style="vertical-align:top; white-space:pre-wrap; font-size:9px; line-height:1.1;">' . $date_asoc_esc . '</td>';
    $one .= '<td width="30%" style="vertical-align:top; text-align:right;">' . $logo_img . '</td>';
    $one .= '</tr></table>';
}
$one .= '<div style="text-align:center; font-size:15px; font-weight:bold; margin-top:2mm;">CHITANȚA</div>';
$data_chitanta = date('d.m.Y', strtotime($inc['data_incasare']));
$one .= '<div style="text-align:center; font-weight:bold; font-size:11px; margin-bottom:0;">Seria: ' . htmlspecialchars($seria) . ' &nbsp;&nbsp; Nr. ' . htmlspecialchars($nr) . ' &nbsp;&nbsp; Data: ' . htmlspecialchars($data_chitanta) . '</div>';
$one .= $template === 'minimal' ? '<br>' : '<br><br>';
$one .= '<div style="font-size:11px; line-height:1.4;">' . $text_chitanta . '</div>';
$one .= '<div style="text-align:right; font-size:11px; margin-top:' . ($template === 'minimal' ? '8mm' : '12mm') . ';">';
$one .= 'Încasat de: ' . htmlspecialchars($incasat_de) . '<br>';
$one .= 'Semnătura: ___________________';
$one .= '</div>';
if ($info_supl_img_tag !== '') {
    // 2 cm deasupra liniei de tăiere, în colțul stânga-jos al fiecărei jumătăți.
    $one .= '<div style="position:absolute; left:8mm; bottom:20mm;">' . $info_supl_img_tag . '</div>';
}

try {
    if ($format === 'a5') {
        // O singură chitanță pe pagină A5
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 5,
            'margin_bottom' => 5,
        ]);
        $mpdf->WriteHTML('<div style="font-family:sans-serif; position:relative; min-height:130mm;">' . $one . '</div>');
    } else {
        // 2 chitanțe pe o pagină A4 - fiecare 210x140mm (în interiorul paginii 210x280mm)
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->autoPageBreak = false;

        $half_style = 'font-family:sans-serif; position:relative; width:210mm; height:140mm; overflow:hidden; padding:5mm 8mm;';
        $sheet = '<div style="position:relative; width:210mm; height:280mm; margin:0; padding:0; overflow:hidden;">';
        $sheet .= '<div style="' . $half_style . ' border-bottom:1px dashed #999;">' . $one . '</div>';
        $sheet .= '<div style="' . $half_style . '">' . $one . '</div>';
        $sheet .= '</div>';
        $mpdf->WriteHTML($sheet);
    }

    $filename = 'chitanta-' . preg_replace('/[^a-z0-9_-]/i', '-', $seria . '-' . $nr) . ($format === 'a5' ? '-a5' : '') . '.pdf';
    $mpdf->Output($filename, 'D');
    exit;
} catch (Exception $e) {
    header('Location: incasari-chitanta-print.php?id=' . $id . '&eroare=pdf');
    exit;
}
