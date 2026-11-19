<?php
/**
 * Generarea PDF a chitantei de incasare.
 *
 * Logica a fost extrasa din util/incasari-chitanta-pdf.php ca sa poata fi
 * refolosita si la trimiterea chitantei pe email (atasament) sau la partajare.
 */

require_once __DIR__ . '/incasari_helper.php';

/**
 * Formatul chitantei (a4 sau a5), din Setari sau din valoarea ceruta explicit.
 */
function incasari_chitanta_format(PDO $pdo, ?string $cerut = null): string {
    $setat = strtolower(trim((string)(incasari_get_setare($pdo, 'dimensiune_chitanta') ?: 'a5')));
    if (!in_array($setat, ['a4', 'a5'], true)) {
        $setat = 'a5';
    }
    $format = strtolower(trim((string)($cerut ?? $setat)));
    if (!in_array($format, ['a4', 'a5'], true)) {
        $format = $setat;
    }
    return $format;
}

/**
 * Datele afisate pe chitanta (nume, CNP, localitate, judet, serie, numar, text).
 *
 * @return array{nume:string,cnp:string,domloc:string,judet:string,seria:string,nr:string,suma_litere:string,reprezentant:string,text:string,data:string,incasat_de:string}
 */
function incasari_chitanta_date(PDO $pdo, array $inc): array {
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

    $nume = trim(((string)($inc['nume'] ?? '')) . ' ' . ((string)($inc['prenume'] ?? '')));
    $cnp = (string)($inc['cnp'] ?? '');
    $domloc = (string)($inc['domloc'] ?? '');
    $judet = (string)($inc['judet_domiciliu'] ?? '');
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

    $suma_litere = incasari_suma_in_litere($inc['suma']);
    $text = 'Am primit de la ' . $nume . ', CNP: ' . $cnp . ', din loc. ' . $domloc
        . ', Județ: ' . $judet . ', suma de ' . $suma_litere . ' (' . $inc['suma']
        . ' RON), reprezentând ' . $reprezentant . '.';

    return [
        'nume' => $nume,
        'cnp' => $cnp,
        'domloc' => $domloc,
        'judet' => $judet,
        'seria' => (string)($inc['seria_chitanta'] ?? '-'),
        'nr' => (string)($inc['nr_chitanta'] ?? '-'),
        'suma_litere' => $suma_litere,
        'reprezentant' => $reprezentant,
        'text' => $text,
        'data' => date(defined('DATE_FORMAT') ? DATE_FORMAT : 'd/m/Y', strtotime((string)$inc['data_incasare'])),
        'incasat_de' => (string)($inc['created_by'] ?? 'Utilizator'),
    ];
}

/**
 * Numele fisierului PDF pentru o chitanta.
 */
function incasari_chitanta_pdf_nume(array $inc, string $format): string {
    $seria = (string)($inc['seria_chitanta'] ?? '-');
    $nr = (string)($inc['nr_chitanta'] ?? '-');
    return 'chitanta-' . preg_replace('/[^a-z0-9_-]/i', '-', $seria . '-' . $nr) . ($format === 'a5' ? '-a5' : '') . '.pdf';
}

/**
 * Construieste HTML-ul chitantei pentru mPDF.
 */
function incasari_chitanta_pdf_html(PDO $pdo, array $inc, string $format): string {
    $d = incasari_chitanta_date($pdo, $inc);

    $logo_url = incasari_get_setare($pdo, 'logo_chitanta') ?: (defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : '');
    $date_asociatie = incasari_get_setare($pdo, 'date_asociatie') ?: '';
    $info_supl_img = incasari_get_setare($pdo, 'informatii_suplimentare_chitanta_image_path') ?: '';
    $info_supl_img_abs = $info_supl_img !== '' ? APP_ROOT . '/' . ltrim($info_supl_img, '/') : '';
    $info_supl_img_tag = '';
    if ($info_supl_img !== '' && is_file($info_supl_img_abs)) {
        $info_supl_img_tag = '<img src="' . htmlspecialchars('/' . ltrim($info_supl_img, '/')) . '" style="width:55mm;height:85mm;object-fit:contain;" alt="Informatii suplimentare chitanta">';
    }

    $date_asoc_esc = nl2br(htmlspecialchars($date_asociatie));
    $logo_img = $logo_url ? '<img src="' . htmlspecialchars($logo_url) . '" style="max-width:30mm;max-height:18mm;" alt="">' : '';

    $template = strtolower(trim((string)(incasari_get_setare($pdo, 'template_chitanta') ?: 'standard')));
    if (!in_array($template, ['standard', 'minimal'], true)) {
        $template = 'standard';
    }

    $one = '';
    if ($template === 'standard' && ($date_asoc_esc !== '' || $logo_img !== '')) {
        $one .= '<table width="100%"><tr>';
        $one .= '<td width="70%" style="vertical-align:top; white-space:pre-wrap; font-size:9px; line-height:1.1;">' . $date_asoc_esc . '</td>';
        $one .= '<td width="30%" style="vertical-align:top; text-align:right;">' . $logo_img . '</td>';
        $one .= '</tr></table>';
    }
    $one .= '<div style="text-align:center; font-size:15px; font-weight:bold; margin-top:2mm;">CHITANȚA</div>';
    $one .= '<div style="text-align:center; font-weight:bold; font-size:11px; margin-bottom:0;">Seria: ' . htmlspecialchars($d['seria']) . ' &nbsp;&nbsp; Nr. ' . htmlspecialchars($d['nr']) . ' &nbsp;&nbsp; Data: ' . htmlspecialchars($d['data']) . '</div>';
    $one .= $template === 'minimal' ? '<br>' : '<br><br>';
    $one .= '<div style="font-size:11px; line-height:1.4;">' . htmlspecialchars($d['text']) . '</div>';
    $one .= '<div style="text-align:right; font-size:11px; margin-top:' . ($template === 'minimal' ? '8mm' : '12mm') . ';">';
    $one .= 'Încasat de: ' . htmlspecialchars($d['incasat_de']) . '<br>';
    $one .= 'Semnătura: ___________________';
    $one .= '</div>';
    if ($info_supl_img_tag !== '') {
        // 2 cm deasupra liniei de tăiere, în colțul stânga-jos al fiecărei jumătăți.
        $one .= '<div style="position:absolute; left:8mm; bottom:20mm;">' . $info_supl_img_tag . '</div>';
    }

    if ($format === 'a5') {
        return '<div style="font-family:sans-serif; position:relative; min-height:130mm;">' . $one . '</div>';
    }

    $half_style = 'font-family:sans-serif; position:relative; width:210mm; height:140mm; overflow:hidden; padding:5mm 8mm;';
    $sheet = '<div style="position:relative; width:210mm; height:280mm; margin:0; padding:0; overflow:hidden;">';
    $sheet .= '<div style="' . $half_style . ' border-bottom:1px dashed #999;">' . $one . '</div>';
    $sheet .= '<div style="' . $half_style . '">' . $one . '</div>';
    $sheet .= '</div>';
    return $sheet;
}

/**
 * Construieste obiectul mPDF cu chitanta scrisa in el.
 *
 * @return \Mpdf\Mpdf|null null daca vendor/autoload.php lipseste
 */
function incasari_chitanta_pdf_build(PDO $pdo, array $inc, string $format) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        return null;
    }
    require_once $autoload;

    $html = incasari_chitanta_pdf_html($pdo, $inc, $format);
    if ($format === 'a5') {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 5,
            'margin_bottom' => 5,
        ]);
    } else {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->autoPageBreak = false;
    }
    $mpdf->WriteHTML($html);
    return $mpdf;
}

/**
 * Scrie chitanta in PDF pe disc si returneaza calea fisierului.
 *
 * @return string|null calea fisierului sau null la eroare
 */
function incasari_chitanta_pdf_fisier(PDO $pdo, array $inc, string $format, ?string $cale = null): ?string {
    try {
        $mpdf = incasari_chitanta_pdf_build($pdo, $inc, $format);
        if ($mpdf === null) return null;
        if ($cale === null) {
            // Nume temporar imprevizibil: directorul temporar poate fi partajat pe hosting.
            $cale = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
                . 'anr-chitanta-' . bin2hex(random_bytes(8)) . '.pdf';
        }
        $mpdf->Output($cale, 'F');
        return is_file($cale) ? $cale : null;
    } catch (Throwable $e) {
        error_log('incasari_chitanta_pdf_fisier: ' . $e->getMessage());
        return null;
    }
}
