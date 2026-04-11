<?php
/**
 * API: Șterge document generat din profil membru (DB + fișier).
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/document_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'eroare' => 'Metodă neacceptată'], JSON_UNESCAPED_UNICODE);
    exit;
}

csrf_require_valid();

$membru_id = (int)($_POST['membru_id'] ?? 0);
$document_id = (int)($_POST['document_id'] ?? ($_POST['id'] ?? 0));
$fisier_hint = trim((string)($_POST['fisier_pdf'] ?? ''));
$docx_hint = trim((string)($_POST['fisier_docx'] ?? ''));

if ($membru_id <= 0) {
    echo json_encode(['ok' => false, 'eroare' => 'Membru invalid.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Șterge fișierul din directoarele permise pentru documente generate.
 */
$delete_generated_file = static function (string $filename): bool {
    $filename = basename(trim($filename));
    if ($filename === '' || preg_match('/[\\\\\/:*?"<>|\x00-\x1F]/', $filename)) {
        return false;
    }
    $removed = false;
    $dirs = [
        APP_ROOT . '/uploads/documente_generate/',
        APP_ROOT . '/documentegenerate/',
    ];
    foreach ($dirs as $dir) {
        $path = rtrim($dir, '/\\') . '/' . $filename;
        if (is_file($path) && @unlink($path)) {
            $removed = true;
        }
    }
    return $removed;
};

documente_ensure_generated_table($pdo);

$doc_row = null;
if ($document_id > 0) {
    $stmt = $pdo->prepare('SELECT id, membru_id, fisier_pdf, fisier_docx FROM documente_generate WHERE id = ? AND membru_id = ? LIMIT 1');
    $stmt->execute([$document_id, $membru_id]);
    $doc_row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$deleted_db = false;
$deleted_files = false;

try {
    if ($doc_row) {
        $stmt_del = $pdo->prepare('DELETE FROM documente_generate WHERE id = ? AND membru_id = ?');
        $stmt_del->execute([(int)$doc_row['id'], $membru_id]);
        $deleted_db = $stmt_del->rowCount() > 0;

        $pdf = trim((string)($doc_row['fisier_pdf'] ?? ''));
        $docx = trim((string)($doc_row['fisier_docx'] ?? ''));
        if ($pdf !== '') {
            $deleted_files = $delete_generated_file($pdf) || $deleted_files;
        }
        if ($docx !== '') {
            $deleted_files = $delete_generated_file($docx) || $deleted_files;
        }
    }

    if ($fisier_hint !== '') {
        $deleted_files = $delete_generated_file($fisier_hint) || $deleted_files;
        if ($doc_row === null) {
            $stmt_del_by_file = $pdo->prepare('DELETE FROM documente_generate WHERE membru_id = ? AND fisier_pdf = ?');
            $stmt_del_by_file->execute([$membru_id, basename($fisier_hint)]);
            $deleted_db = ($stmt_del_by_file->rowCount() > 0) || $deleted_db;
        }
    }
    if ($docx_hint !== '') {
        $deleted_files = $delete_generated_file($docx_hint) || $deleted_files;
        if ($doc_row === null) {
            $stmt_del_by_docx = $pdo->prepare('DELETE FROM documente_generate WHERE membru_id = ? AND fisier_docx = ?');
            $stmt_del_by_docx->execute([$membru_id, basename($docx_hint)]);
            $deleted_db = ($stmt_del_by_docx->rowCount() > 0) || $deleted_db;
        }
    }
} catch (Throwable $e) {
    error_log('membri-documente-sterge: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'eroare' => 'Eroare la ștergerea documentului.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$deleted_db && !$deleted_files) {
    echo json_encode(['ok' => false, 'eroare' => 'Documentul nu a fost găsit sau a fost deja șters.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt_membru = $pdo->prepare('SELECT nume, prenume FROM membri WHERE id = ? LIMIT 1');
$stmt_membru->execute([$membru_id]);
$m = $stmt_membru->fetch(PDO::FETCH_ASSOC) ?: ['nume' => '', 'prenume' => ''];
$nume_membru = trim((string)$m['nume'] . ' ' . (string)$m['prenume']);
if ($nume_membru === '') {
    $nume_membru = 'Membru ID ' . $membru_id;
}
$utilizator = (string)($_SESSION['utilizator'] ?? $_SESSION['nume_complet'] ?? 'Utilizator');
log_activitate($pdo, 'Document generat șters din profil membru: ' . $nume_membru . ' / Utilizator: ' . $utilizator, null, $membru_id);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);

