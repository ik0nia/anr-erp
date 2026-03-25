<?php
/**
 * Export listă prezență în PDF (A4) pornind de la DOCX cu antet asociație.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/document_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /activitati'); exit; }

$temp_docx = null;
try {
    $stmt = $pdo->prepare('SELECT tip_titlu, data_lista FROM liste_prezenta WHERE id = ?');
    $stmt->execute([$id]);
    $lista = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lista) {
        header('Location: /activitati');
        exit;
    }

    // Genereaza temporar DOCX-ul care deja include antetul asociației.
    ob_start();
    $_GET['id'] = $id;
    include __DIR__ . '/lista-prezenta-docx.php';
    $docx_bytes = ob_get_clean();
    if ($docx_bytes === false || $docx_bytes === '') {
        header('Location: /activitati');
        exit;
    }
    $tmp_dir = __DIR__ . '/../uploads/documente_generate';
    if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0755, true);
    $temp_docx = $tmp_dir . '/lista_' . $id . '_tmp_' . uniqid() . '.docx';
    file_put_contents($temp_docx, $docx_bytes);

    $pdf_result = converteste_docx_la_pdf($temp_docx, $pdo);
    if (!$pdf_result['success'] || empty($pdf_result['path']) || !file_exists($pdf_result['path'])) {
        @unlink($temp_docx);
        header('Location: lista-prezenta-print.php?id=' . $id);
        exit;
    }

    $pdf_path = $pdf_result['path'];
    $filename = 'Lista-prezenta-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $lista['tip_titlu']) . '-' . date('Y-m-d', strtotime($lista['data_lista'])) . '.pdf';

    log_activitate($pdo, "liste_prezenta: Lista de prezenta exportata PDF - {$lista['tip_titlu']} (ID: {$id})");

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdf_path));
    readfile($pdf_path);

    @unlink($temp_docx);
    @unlink($pdf_path);
    exit;
} catch (Throwable $e) {
    if ($temp_docx && file_exists($temp_docx)) @unlink($temp_docx);
    header('Location: lista-prezenta-print.php?id=' . $id);
    exit;
}
