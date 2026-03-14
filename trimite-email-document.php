<?php
/**
 * Trimite email cu document generat
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodă invalidă.']);
    exit;
}

$to = trim($_POST['email'] ?? '');
$subject = trim($_POST['subiect'] ?? '');
$message = trim($_POST['mesaj'] ?? '');
$docx_token = trim($_POST['docx_token'] ?? '');
$attach_ci = !empty($_POST['attach_ci']);
$attach_ch = !empty($_POST['attach_ch']);
$membru_id = (int)($_POST['membru_id'] ?? 0);

if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Adresă email invalidă.']);
    exit;
}

// Validare token(e) și colectare fișiere de atașat
$attach_docx = !empty($_POST['attach_docx']);
$attach_pdf = !empty($_POST['attach_pdf']);
$pdf_token = trim($_POST['pdf_token'] ?? '');

if (!$attach_docx && !$attach_pdf) {
    echo json_encode(['success' => false, 'error' => 'Selectați cel puțin „Atașează DOCX” sau „Atașează PDF”.']);
    exit;
}

$doc_dir = __DIR__ . '/uploads/documente_generate/';
$base_dir_real = realpath($doc_dir);
if (!$base_dir_real) {
    echo json_encode(['success' => false, 'error' => 'Director documente indisponibil.']);
    exit;
}

$valid_token = function ($token) {
    $filename = base64_decode($token, true);
    if ($filename === false || $filename === '') return [false, null];
    if (preg_match('/[\/\\\\:*?"<>|\x00-\x1f]|\.\./', $filename)) return [false, null];
    return [true, $filename];
};

$attach_files = [];

if ($attach_docx && $docx_token !== '') {
    list($ok, $filename) = $valid_token($docx_token);
    if (!$ok) {
        echo json_encode(['success' => false, 'error' => 'Token document DOCX invalid.']);
        exit;
    }
    $path = realpath($doc_dir . $filename);
    if (!$path || strpos($path, $base_dir_real) !== 0 || !file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'Documentul DOCX nu a fost găsit.']);
        exit;
    }
    $attach_files[] = ['path' => $path, 'name' => basename($path)];
}

if ($attach_pdf && $pdf_token !== '') {
    list($ok, $filename) = $valid_token($pdf_token);
    if (!$ok) {
        echo json_encode(['success' => false, 'error' => 'Token document PDF invalid.']);
        exit;
    }
    $path = realpath($doc_dir . $filename);
    if (!$path || strpos($path, $base_dir_real) !== 0 || !file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'Documentul PDF nu a fost găsit.']);
        exit;
    }
    $attach_files[] = ['path' => $path, 'name' => basename($path)];
}

if (empty($attach_files)) {
    echo json_encode(['success' => false, 'error' => 'Selectați cel puțin un document de atașat (DOCX sau PDF).']);
    exit;
}

// Email asociatie pentru Cc
$email_cc = '';
try {
    $stmt = $pdo->query("SELECT valoare FROM setari WHERE cheie = 'email_asociatie'");
    $row = $stmt->fetch();
    if ($row && !empty(trim($row['valoare']))) {
        $email_cc = trim($row['valoare']);
    }
} catch (PDOException $e) {}

// Atașamente CI și CH
if ($membru_id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT doc_ci, doc_ch FROM membri WHERE id = ?');
        $stmt->execute([$membru_id]);
        $m = $stmt->fetch(PDO::FETCH_ASSOC);
        $upload_dir = __DIR__ . '/uploads/';
        if ($attach_ci && !empty($m['doc_ci'])) {
            $ci_path = $upload_dir . 'ci/' . $m['doc_ci'];
            if (file_exists($ci_path)) {
                $attach_files[] = ['path' => $ci_path, 'name' => 'act_identitate_' . $m['doc_ci']];
            }
        }
        if ($attach_ch && !empty($m['doc_ch'])) {
            $ch_path = $upload_dir . 'ch/' . $m['doc_ch'];
            if (file_exists($ch_path)) {
                $attach_files[] = ['path' => $ch_path, 'name' => 'certificat_handicap_' . $m['doc_ch']];
            }
        }
    } catch (PDOException $e) {}
}

// Reconstruim email cu toate atașamentele
$boundary = md5(uniqid());
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
$headers .= "From: ERP ANR BIHOR <noreply@anrbihor.ro>\r\n";
$body = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$body .= $message ?: '(Fără mesaj)';
foreach ($attach_files as $att) {
    $body .= "\r\n--{$boundary}\r\n";
    $body .= "Content-Type: application/octet-stream; name=\"" . $att['name'] . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"" . $att['name'] . "\"\r\n\r\n";
    $body .= chunk_split(base64_encode(file_get_contents($att['path'])));
}
$body .= "\r\n--{$boundary}--";

$to_header = $to;
if (!empty($email_cc)) {
    $headers .= "Cc: " . $email_cc . "\r\n";
}

$sent = @mail($to, $subject ?: 'Document ERP ANR BIHOR', $body, $headers);

if ($sent) {
    // Încarcă numele membrului pentru logging
    $membru_nume = '';
    if ($membru_id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT nume, prenume FROM membri WHERE id = ?');
            $stmt->execute([$membru_id]);
            $m = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($m) {
                $membru_nume = trim($m['nume'] . ' ' . $m['prenume']);
            }
        } catch (PDOException $e) {}
    }
    $context = $membru_nume ? "{$membru_nume}" : "Email: {$to}";
    log_activitate($pdo, "documente: Email trimis cu document catre {$to} / {$context}", null, $membru_id ?: null);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Trimiterea emailului a eșuat. Verificați configurarea serverului.']);
}
