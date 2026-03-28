<?php
/**
 * Trimite email cu documentul generat (prioritar PDF)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/log_helper.php';
require_once __DIR__ . '/../includes/document_helper.php';
require_once __DIR__ . '/../includes/mailer_functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodă invalidă.']);
    exit;
}
if (function_exists('csrf_require_valid')) {
    csrf_require_valid();
}

$to = trim($_POST['email'] ?? '');
$subject = trim($_POST['subiect'] ?? '');
$message = trim($_POST['mesaj'] ?? '');
$pdf_token = trim($_POST['pdf_token'] ?? '');
$membru_id = (int)($_POST['membru_id'] ?? 0);
$document_generat_id = (int)($_POST['document_generat_id'] ?? 0);

if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Adresă email invalidă.']);
    exit;
}
if ($pdf_token === '') {
    echo json_encode(['success' => false, 'error' => 'Token document PDF lipsă.']);
    exit;
}

$doc_dir = __DIR__ . '/../uploads/documente_generate/';
$base_dir_real = realpath($doc_dir);
if (!$base_dir_real) {
    echo json_encode(['success' => false, 'error' => 'Director documente indisponibil.']);
    exit;
}

$filename = base64_decode($pdf_token, true);
if ($filename === false || $filename === '' || preg_match('/[\/\\\\:*?"<>|\x00-\x1f]|\.\./', $filename)) {
    echo json_encode(['success' => false, 'error' => 'Token document PDF invalid.']);
    exit;
}

$path = realpath($doc_dir . $filename);
if (!$path || strpos($path, $base_dir_real) !== 0 || !is_file($path)) {
    echo json_encode(['success' => false, 'error' => 'Documentul PDF nu a fost găsit.']);
    exit;
}

$subject = $subject !== '' ? $subject : 'Documentul a fost generat';
$message = $message !== '' ? $message : 'Buna ziua, va trimitem atasat documentul completat.';
$html = '<p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

$sent = sendEmailWithAttachment($pdo, $to, $subject, $html, $path, basename($path));
if (!$sent) {
    echo json_encode(['success' => false, 'error' => 'Trimiterea emailului a eșuat. Verificați configurarea SMTP/email.']);
    exit;
}

$membru_nume = '';
if ($membru_id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT nume, prenume FROM membri WHERE id = ?');
        $stmt->execute([$membru_id]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($m) {
            $membru_nume = trim($m['nume'] . ' ' . $m['prenume']);
        }
    } catch (PDOException $e) {
        // no-op
    }
}
$context = $membru_nume !== '' ? $membru_nume : ("Email: " . $to);
log_activitate($pdo, "documente: Email trimis cu document PDF catre {$to} / {$context}", null, $membru_id ?: null);
if ($document_generat_id > 0) {
    documente_marcheaza_actiune($pdo, $document_generat_id, 'email');
}

echo json_encode(['success' => true]);
