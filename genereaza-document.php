<?php
/**
 * API generare document - primește membru_id + template_id (POST), opțional include_data_generare.
 * Generează DOCX/PDF, înregistrează în registratura + log. Protecție CSRF pentru POST.
 * Răspunde întotdeauna cu JSON (inclusiv la erori) pentru consum din frontend.
 */
ob_start();

require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';
require_once 'includes/document_helper.php';
require_once 'includes/registratura_helper.php';

// Asigură tabel și director pentru templateuri (dacă nu au fost create din Management Documente)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS documente_template (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume_afisare VARCHAR(255) NOT NULL,
        nume_fisier VARCHAR(255) NOT NULL,
        activ TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_activ (activ)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (!is_dir(UPLOAD_TEMPLATE_DIR)) {
        @mkdir(UPLOAD_TEMPLATE_DIR, 0755, true);
    }
} catch (PDOException $e) { /* ignoră */ }

$sendJson = function ($data) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
};

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $sendJson(['success' => false, 'error' => 'Metodă neacceptată. Folosiți POST.']);
    }

    if (defined('CSRF_ENABLED') && CSRF_ENABLED === true) {
        $token = $_POST['_csrf_token'] ?? '';
        if (!function_exists('csrf_validate_token') || !csrf_validate_token($token)) {
            http_response_code(403);
            $sendJson(['success' => false, 'error' => 'Token CSRF invalid sau lipsă. Reîncărcați pagina.']);
        }
    }

    $membru_id = (int)($_POST['membru_id'] ?? 0);
    $template_id = (int)($_POST['template_id'] ?? 0);
    $include_data_generare = isset($_POST['include_data_generare']) && ($_POST['include_data_generare'] === '1' || $_POST['include_data_generare'] === 'true');

    if ($membru_id <= 0 || $template_id <= 0) {
        $sendJson(['success' => false, 'error' => 'Parametri invalizi.']);
    }

    // Încarcă membru
    $stmt = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
    $stmt->execute([$membru_id]);
    $membru = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$membru) {
        $sendJson(['success' => false, 'error' => 'Membrul nu a fost găsit.']);
    }

    // Încarcă template
    $stmt = $pdo->prepare('SELECT * FROM documente_template WHERE id = ? AND activ = 1');
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        $sendJson(['success' => false, 'error' => 'Template-ul nu a fost găsit sau nu este activ.']);
    }

    $template_path = UPLOAD_TEMPLATE_DIR . $template['nume_fisier'];
    if (!file_exists($template_path)) {
        $sendJson(['success' => false, 'error' => 'Fișierul template nu există pe disc.']);
    }

    $result = genereaza_document_docx($template_path, $membru, null, [
        'include_data_generare' => $include_data_generare,
        'nume_template' => $template['nume_afisare'],
    ]);
    if (!$result['success']) {
        $sendJson(['success' => false, 'error' => $result['error'] ?? 'Eroare la generare.']);
    }

    $docx_filename = $result['filename'];
    $docx_path = $result['path'];

    // Director pentru documente generate (DOCX/PDF) descărcabile și arhivate
    $generated_dir = __DIR__ . '/uploads/documente_generate/';
    if (!is_dir($generated_dir)) {
        @mkdir($generated_dir, 0755, true);
    }

    // Construiește numele de bază pentru fișiere în funcție de membru + template
    $membru_nume_simplu = preg_replace('/\s+/', '', trim(($membru['nume'] ?? '') . ($membru['prenume'] ?? '')));
    $template_simplu = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $template['nume_afisare']);
    $timestamp = date('Ymd_His');
    $base_name = $timestamp . '-' . $membru_nume_simplu . '-' . $template_simplu;

    // Copiază DOCX generat în directorul de documente generate cu nume prietenos
    $docx_target_filename = $base_name . '.docx';
    $docx_target_path = $generated_dir . $docx_target_filename;
    @copy($docx_path, $docx_target_path);

    // Conversie și salvare PDF în același director cu nume prietenos
    $pdf_filename = null;
    $pdf_result = converteste_docx_la_pdf($docx_path, $pdo);
    if ($pdf_result['success']) {
        // Dacă tool-ul de conversie a salvat altundeva, copiem rezultatul în directorul standard
        $pdf_source_path = $pdf_result['path'] ?? ($generated_dir . $pdf_result['filename']);
        if (!empty($pdf_source_path) && file_exists($pdf_source_path)) {
            $pdf_target_filename = $base_name . '.pdf';
            $pdf_target_path = $generated_dir . $pdf_target_filename;
            @copy($pdf_source_path, $pdf_target_path);
            $pdf_filename = $pdf_target_filename;
        }
    }

    $nr_inregistrare = null;
    try {
        ensure_registratura_table($pdo);
        $nr_intern = registratura_urmatorul_nr($pdo);
        $nr_inregistrare = (string) $nr_intern;
        $utilizator = $_SESSION['utilizator'] ?? 'Sistem';
        $detalii = 'Generare document: ' . $template['nume_afisare'] . ' - ' . trim($membru['nume'] . ' ' . $membru['prenume']);
        $stmt = $pdo->prepare('INSERT INTO registratura (nr_intern, nr_inregistrare, utilizator, tip_act, detalii, continut_document, membru_id, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $nr_intern,
            $nr_inregistrare,
            $utilizator,
            'Generare document',
            $detalii,
            $detalii,
            $membru_id,
            $docx_target_filename
        ]);
    } catch (PDOException $e) {
        // Nu blocăm dacă registratura eșuează
    }

    $membru_nume = trim($membru['nume'] . ' ' . $membru['prenume']);
    log_activitate($pdo, "documente: Document generat - {$template['nume_afisare']} / {$membru_nume}", null, $membru_id);

    $download_token = base64_encode($docx_filename);
    $pdf_token = $pdf_filename ? base64_encode($pdf_filename) : null;

    $sendJson([
        'success' => true,
        'docx_filename' => $docx_filename,
        'docx_token' => $download_token,
        'pdf_filename' => $pdf_filename,
        'pdf_token' => $pdf_token,
        'nr_inregistrare' => $nr_inregistrare,
    ]);
} catch (PDOException $e) {
    $sendJson(['success' => false, 'error' => 'Eroare bază de date. Încercați din nou.']);
} catch (Throwable $e) {
    $sendJson(['success' => false, 'error' => 'Eroare server: ' . $e->getMessage()]);
}
