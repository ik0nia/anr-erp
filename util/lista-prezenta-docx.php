<?php
/**
 * Export listă prezență în DOCX (cu antet asociație din Setări dacă este setat).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/liste_helper.php';
require_once __DIR__ . '/../includes/contacte_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/document_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /activitati'); exit; }

try {
    $stmt = $pdo->prepare('SELECT * FROM liste_prezenta WHERE id = ?');
    $stmt->execute([$id]);
    $lista = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lista) { header('Location: /activitati'); exit; }

    ensure_contacte_table($pdo);
    $stmt = $pdo->prepare('
        SELECT lm.ordine, lm.nume_manual, lm.membru_id, lm.contact_id,
               m.nume, m.prenume, m.datanastere, m.ciseria, m.cinumar, m.domloc,
               c.nume AS contact_nume, c.prenume AS contact_prenume
        FROM liste_prezenta_membri lm
        LEFT JOIN membri m ON lm.membru_id = m.id
        LEFT JOIN contacte c ON c.id = lm.contact_id
        WHERE lm.lista_id = ?
        ORDER BY lm.ordine
    ');
    $stmt->execute([$id]);
    $participanti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: /activitati');
    exit;
}

$coloane = json_decode($lista['coloane_selectate'] ?? '[]', true) ?: ['nr_crt','nume_prenume','semnatura'];

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) { header('Location: /activitati'); exit; }
require_once $autoload;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Paper;

$antet_path = get_antet_asociatie_docx_path($pdo);
if ($antet_path && file_exists($antet_path)) {
    $phpWord = IOFactory::load($antet_path);
    $section = $phpWord->addSection(['paperSize' => Paper::SIZE_A4]);
} else {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection(['paperSize' => Paper::SIZE_A4]);
}

$section->addTitle($lista['tip_titlu'], 0);
if (!empty($lista['detalii_activitate'])) {
    $section->addText($lista['detalii_activitate'], ['size' => 10]);
}
$section->addText('Data: ' . date(DATE_FORMAT, strtotime($lista['data_lista'])), ['bold' => true, 'size' => 10]);
$section->addTextBreak(1);
if (!empty($lista['detalii_suplimentare_sus'])) {
    $section->addText($lista['detalii_suplimentare_sus'], ['size' => 9]);
    $section->addTextBreak(1);
}

$headers = [];
foreach ($coloane as $col) {
    $headers[] = LISTE_COLOANE[$col] ?? $col;
}
$table = $section->addTable(['borderSize' => 6, 'cellMargin' => 80]);
$table->addRow();
foreach ($headers as $h) {
    $table->addCell(1500)->addText($h, ['bold' => true, 'size' => 9]);
}
foreach ($participanti as $i => $p) {
    $table->addRow();
    foreach ($coloane as $col) {
        $val = '';
        if ($col === 'nr_crt') $val = (string)($i + 1);
        elseif ($col === 'nume_prenume') {
            if (!empty($p['nume_manual'])) {
                $val = $p['nume_manual'];
            } else {
                $membruNume = trim(($p['nume'] ?? '') . ' ' . ($p['prenume'] ?? ''));
                $contactNume = trim(($p['contact_nume'] ?? '') . ' ' . ($p['contact_prenume'] ?? ''));
                $val = $membruNume !== '' ? $membruNume : $contactNume;
            }
        }
        elseif ($col === 'datanastere') $val = $p['datanastere'] ? date(DATE_FORMAT, strtotime($p['datanastere'])) : '';
        elseif ($col === 'varsta') $val = (string)(calculeaza_varsta($p['datanastere']) ?? '');
        elseif ($col === 'ci') $val = trim(($p['ciseria'] ?? '') . ' ' . ($p['cinumar'] ?? ''));
        elseif ($col === 'domloc') $val = $p['domloc'] ?? '';
        $table->addCell(1500)->addText($val, ['size' => 9]);
    }
}
$section->addTextBreak(1);
if (!empty($lista['detalii_suplimentare_jos'])) {
    $section->addText($lista['detalii_suplimentare_jos'], ['size' => 9]);
    $section->addTextBreak(1);
}

$semn = [
    [$lista['semnatura_stanga_nume'] ?? '', $lista['semnatura_stanga_functie'] ?? ''],
    [$lista['semnatura_centru_nume'] ?? '', $lista['semnatura_centru_functie'] ?? ''],
    [$lista['semnatura_dreapta_nume'] ?? '', $lista['semnatura_dreapta_functie'] ?? '']
];
foreach ($semn as $s) {
    if (trim($s[0]) === '' && trim($s[1]) === '') continue;
    $section->addText($s[0], ['bold' => true, 'size' => 10]);
    if ($s[1] !== '') $section->addText($s[1], ['size' => 9]);
    $section->addTextBreak(1);
}

log_activitate($pdo, "liste_prezenta: Lista de prezenta exportata DOCX - {$lista['tip_titlu']} (ID: {$id})");

$filename = 'Lista-prezenta-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $lista['tip_titlu']) . '-' . date('Y-m-d', strtotime($lista['data_lista'])) . '.docx';
$dir = __DIR__ . '/../uploads/documente_generate';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$path = $dir . '/lista_' . $id . '_' . time() . '.docx';
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($path);

if (defined('LISTA_PREZENTA_DOCX_EMBED') && LISTA_PREZENTA_DOCX_EMBED === true) {
    $GLOBALS['lista_prezenta_docx_result'] = [
        'success' => true,
        'path' => $path,
        'filename' => $filename,
        'lista' => $lista,
    ];
    return;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
@unlink($path);
exit;
