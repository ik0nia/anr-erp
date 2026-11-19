<?php
/**
 * Descarcă chitanța încasare în format PDF - 2 exemplare pe o coală A4 (sau una pe A5).
 * La generare se trimite chitanța prin API către FGO, cu excepția descărcărilor
 * făcute pentru partajare (partaj=1), ca să nu se raporteze de două ori.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/incasari_helper.php';
require_once __DIR__ . '/../includes/incasari_chitanta_pdf.php';

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

$pentru_partajare = !empty($_GET['partaj']);
if (!$pentru_partajare) {
    // Trimite chitanța către FGO la generare
    incasari_trimite_fgo($pdo, $inc);
}

$format = incasari_chitanta_format($pdo, isset($_GET['format']) ? (string)$_GET['format'] : null);

$mpdf = incasari_chitanta_pdf_build($pdo, $inc, $format);
if ($mpdf === null) {
    header('Location: incasari-chitanta-print.php?id=' . $id);
    exit;
}

try {
    $mpdf->Output(incasari_chitanta_pdf_nume($inc, $format), 'D');
    exit;
} catch (Throwable $e) {
    header('Location: incasari-chitanta-print.php?id=' . $id . '&eroare=pdf');
    exit;
}
