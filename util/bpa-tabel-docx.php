<?php
/**
 * Export tabel distributie BPA in DOCX (PhpWord). Foloseste antetul asociatiei din Setari daca este setat.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/bpa_helper.php';
require_once __DIR__ . '/../includes/liste_helper.php';
require_once __DIR__ . '/../includes/document_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ajutoare-bpa.php'); exit; }
$tabel = bpa_get_tabel($pdo, $id);
if (!$tabel) { header('Location: ajutoare-bpa.php'); exit; }

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) { header('Location: ajutoare-bpa.php'); exit; }
require_once $autoload;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Font;

$antet_path = get_antet_asociatie_docx_path($pdo);
if ($antet_path && file_exists($antet_path)) {
    $phpWord = IOFactory::load($antet_path);
    $section = $phpWord->addSection();
} else {
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
}

$section->addTitle('Tabel Distributie', 0);
$section->addText('Data: ' . date(DATE_FORMAT, strtotime($tabel['data_tabel'])) . '   Nr. ' . $tabel['nr_tabel'], ['size' => 10]);
$section->addTextBreak(1);

$locuri = [];
if ($tabel['predare_sediul']) $locuri[] = 'Predare la sediu';
if ($tabel['predare_centru']) $locuri[] = 'Predare la centru';
if ($tabel['livrare_domiciliu']) $locuri[] = 'Livrare la domiciliu';
if (!empty($locuri)) {
    $section->addText(implode(' • ', $locuri), ['size' => 9]);
    $section->addTextBreak(1);
}

$headers = ['Nr. crt.', 'Nume și prenume', 'Localitate domiciliu', 'Seria și nr. C.I.', 'Vârstă', 'Greutate (Kg)', 'Semnătură'];
$table = $section->addTable(['borderSize' => 6, 'cellMargin' => 80]);
$table->addRow();
foreach ($headers as $h) {
    $table->addCell(1500)->addText($h, ['bold' => true, 'size' => 9]);
}
foreach ($tabel['randuri'] as $i => $r) {
    $table->addRow();
    $table->addCell(500)->addText((string)($i + 1));
    $nume = !empty($r['membru_id']) ? trim(($r['nume'] ?? '') . ' ' . ($r['prenume'] ?? '')) : trim(($r['nume_manual'] ?? '') . ' ' . ($r['prenume_manual'] ?? ''));
    $table->addCell(2000)->addText($nume);
    $table->addCell(1500)->addText($r['localitate'] ?? $r['domloc'] ?? '');
    $table->addCell(1200)->addText(($r['ciseria'] ?? '') . ' ' . ($r['cinumar'] ?? '') ?: ($r['seria_nr_ci'] ?? ''));
    $dn = $r['datanastere'] ?? $r['data_nastere'] ?? null;
    $table->addCell(500)->addText($dn ? (string)calculeaza_varsta($dn) : '');
    $table->addCell(800)->addText(number_format($r['greutate_pachet'] ?? 0, 2, ',', '.'));
    $table->addCell(1000)->addText('');
}
$section->addTextBreak(1);
$section->addText('Total cantitate distribuită: ' . number_format($tabel['cantitate_totala'], 2, ',', '.') . ' kg', ['bold' => true]);
$section->addTextBreak(2);
$section->addText('Mihai Merca, Președinte', ['size' => 10]);
$section->addText('Cristina Cociuba, Responsabil distributie', ['size' => 10]);

$filename = 'Tabel-Distributie-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $tabel['nr_tabel']) . '.docx';
$dir = __DIR__ . '/../uploads/documente_generate';
if (!is_dir($dir)) mkdir($dir, 0755, true);
$path = $dir . '/' . $filename;
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($path);

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
