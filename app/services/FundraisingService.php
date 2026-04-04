<?php
/**
 * FundraisingService — Formular 230 public + manager ERP.
 *
 * Responsabilități:
 * - schemă DB pentru formulare 230
 * - setări modul (template PDF + mesaj confirmare)
 * - validare date (inclusiv CNP România)
 * - generare PDF din template PDF cu taguri [230...]
 * - stocare în folder privat F230PDF
 * - email admin + email confirmare completator
 * - export CSV
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/document_helper.php';
require_once APP_ROOT . '/includes/mailer_functions.php';

const FUNDRAISING_SETARE_TEMPLATE = 'fundraising_f230_template_pdf';
const FUNDRAISING_SETARE_CONFIRM = 'fundraising_f230_mesaj_confirmare_html';

/**
 * Returnează lista de taguri suportate în template-ul PDF.
 */
function fundraising_f230_taguri(): array
{
    return [
        '230nume' => 'Nume',
        '230in' => 'Inițiala tatălui',
        '230prenume' => 'Prenume',
        '230CNP' => 'CNP',
        '230loc' => 'Localitatea',
        '230jud' => 'Județ',
        '230cp' => 'Cod poștal',
        '230str' => 'Strada',
        '230nr' => 'Număr',
        '230bl' => 'Bloc',
        '230sc' => 'Scară',
        '230et' => 'Etaj',
        '230ap' => 'Apartament',
        '230tel' => 'Telefon',
        '230email' => 'Email',
        '230semnatura' => 'Semnătura (signature pad)',
        '230gdpr' => 'Acord GDPR',
    ];
}

/**
 * Returnează metadata pentru afișare taguri în tab-ul Setări.
 */
function fundraising_f230_taguri_display(): array
{
    $items = [];
    foreach (fundraising_f230_taguri() as $tag => $descriere) {
        $items[] = [
            'tag' => '[' . $tag . ']',
            'descriere' => $descriere,
        ];
    }
    return $items;
}

/**
 * Asigură existența folderului privat F230PDF și protecțiile minime.
 */
function fundraising_f230_ensure_private_storage(): array
{
    $base_dir = APP_ROOT . '/F230PDF';
    $sign_dir = $base_dir . '/signatures';
    if (!is_dir($base_dir)) {
        @mkdir($base_dir, 0750, true);
    }
    if (!is_dir($sign_dir)) {
        @mkdir($sign_dir, 0750, true);
    }

    $htaccess = $base_dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Order deny,allow\nDeny from all\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n");
    }
    $index = $base_dir . '/index.php';
    if (!file_exists($index)) {
        @file_put_contents($index, "<?php http_response_code(403); exit('Acces interzis.');\n");
    }

    return [
        'base_dir' => $base_dir,
        'signature_dir' => $sign_dir,
    ];
}

/**
 * Returnează cale absolută pentru o cale relativă din APP_ROOT.
 */
function fundraising_f230_abs_path(string $relative_path): string
{
    return APP_ROOT . '/' . ltrim($relative_path, '/');
}

/**
 * Transformă cale absolută în cale relativă față de APP_ROOT.
 */
function fundraising_f230_rel_path(string $absolute_path): string
{
    $prefix = rtrim(APP_ROOT, '/') . '/';
    if (strpos($absolute_path, $prefix) === 0) {
        return substr($absolute_path, strlen($prefix));
    }
    return ltrim($absolute_path, '/');
}

/**
 * Citește o setare (key-value) din tabela setari.
 */
function fundraising_setare_get(PDO $pdo, string $cheie): ?string
{
    try {
        $stmt = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ? LIMIT 1');
        $stmt->execute([$cheie]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Salvează (insert/update) o setare key-value.
 */
function fundraising_setare_set(PDO $pdo, string $cheie, string $valoare): void
{
    $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
    $stmt->execute([$cheie, $valoare]);
}

/**
 * Inițializează schema și setările default pentru modul.
 */
function fundraising_f230_ensure_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cheie VARCHAR(100) NOT NULL UNIQUE,
        valoare TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS fundraising_f230_formulare (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        initiala_tatalui VARCHAR(3) DEFAULT NULL,
        prenume VARCHAR(100) NOT NULL,
        cnp VARCHAR(13) NOT NULL,
        localitate VARCHAR(120) NOT NULL,
        judet VARCHAR(120) NOT NULL,
        cod_postal VARCHAR(6) DEFAULT NULL,
        strada VARCHAR(160) NOT NULL,
        numar VARCHAR(20) NOT NULL,
        bloc VARCHAR(10) DEFAULT NULL,
        scara VARCHAR(10) DEFAULT NULL,
        etaj VARCHAR(10) DEFAULT NULL,
        apartament VARCHAR(10) DEFAULT NULL,
        telefon VARCHAR(50) NOT NULL,
        email VARCHAR(255) NOT NULL,
        gdpr_acord TINYINT(1) NOT NULL DEFAULT 0,
        semnatura_path VARCHAR(255) NOT NULL,
        pdf_path VARCHAR(255) NOT NULL,
        pdf_filename VARCHAR(255) NOT NULL,
        sursa ENUM('online','manual') NOT NULL DEFAULT 'online',
        ip_adresa VARCHAR(64) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_f230_cnp (cnp),
        INDEX idx_f230_created (created_at),
        INDEX idx_f230_sursa (sursa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (fundraising_setare_get($pdo, FUNDRAISING_SETARE_CONFIRM) === null) {
        $default_msg = '<p>Vă mulțumim pentru completarea Formularului 230 pentru Asociația Nevăzătorilor Bihor.</p>'
            . '<p>Datele au fost înregistrate cu succes.</p>'
            . '<p><strong>[230nume] [230prenume]</strong>, aprecierile noastre pentru sprijin!</p>';
        fundraising_setare_set($pdo, FUNDRAISING_SETARE_CONFIRM, $default_msg);
    }

    fundraising_f230_ensure_private_storage();
}

/**
 * URL public către formularul online.
 */
function fundraising_f230_public_url(): string
{
    if (defined('PLATFORM_BASE_URL') && trim((string)PLATFORM_BASE_URL) !== '') {
        return rtrim((string)PLATFORM_BASE_URL, '/') . '/fundraising/formular-230';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/fundraising/formular-230';
}

/**
 * Returnează setările curente ale modulului.
 */
function fundraising_f230_get_settings(PDO $pdo): array
{
    $template_rel = trim((string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE) ?? ''));
    $template_abs = $template_rel !== '' ? fundraising_f230_abs_path($template_rel) : '';
    $confirm_html = (string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_CONFIRM) ?? '');

    return [
        'template_rel' => $template_rel,
        'template_exists' => $template_abs !== '' && is_file($template_abs),
        'confirm_html' => $confirm_html,
        'public_url' => fundraising_f230_public_url(),
        'storage_folder' => 'F230PDF',
    ];
}

/**
 * Curățare HTML minimă pentru mesajul configurabil din TinyMCE.
 */
function fundraising_f230_sanitize_confirm_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    $allowed = '<p><br><strong><em><u><ul><ol><li><a><span><h1><h2><h3><h4><h5><h6><blockquote>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $html = preg_replace('#\son\w+\s*=\s*([\'"]).*?\1#is', '', $html);
    return trim((string)$html);
}

/**
 * Salvează setările modulului: template PDF + mesaj confirmare.
 */
function fundraising_f230_save_settings(PDO $pdo, array $post, array $files): array
{
    try {
        $confirm_html = fundraising_f230_sanitize_confirm_html((string)($post['mesaj_confirmare_html'] ?? ''));
        if ($confirm_html === '') {
            return ['success' => false, 'error' => 'Mesajul de confirmare nu poate fi gol.'];
        }
        fundraising_setare_set($pdo, FUNDRAISING_SETARE_CONFIRM, $confirm_html);

        $fisier = $files['template_pdf_230'] ?? null;
        if (is_array($fisier) && (int)($fisier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ((int)($fisier['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => 'Eroare la încărcarea fișierului template PDF.'];
            }

            $ext = strtolower((string)pathinfo((string)($fisier['name'] ?? ''), PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                return ['success' => false, 'error' => 'Template-ul trebuie să fie fișier PDF.'];
            }
            if ((int)($fisier['size'] ?? 0) > 20 * 1024 * 1024) {
                return ['success' => false, 'error' => 'Template-ul PDF depășește 20 MB.'];
            }

            $upload_dir = APP_ROOT . '/uploads/fundraising/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            $filename = 'template-230-' . date('Ymd-His') . '-' . substr(md5((string)uniqid('', true)), 0, 10) . '.pdf';
            $dest = $upload_dir . $filename;
            if (!@move_uploaded_file((string)$fisier['tmp_name'], $dest)) {
                return ['success' => false, 'error' => 'Nu s-a putut salva template-ul PDF pe server.'];
            }

            $old_rel = trim((string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE) ?? ''));
            $new_rel = 'uploads/fundraising/' . $filename;
            fundraising_setare_set($pdo, FUNDRAISING_SETARE_TEMPLATE, $new_rel);

            if ($old_rel !== '') {
                $old_abs = fundraising_f230_abs_path($old_rel);
                if (is_file($old_abs) && $old_abs !== $dest) {
                    @unlink($old_abs);
                }
            }
        }

        return ['success' => true, 'error' => null];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Eroare la salvarea setărilor: ' . $e->getMessage()];
    }
}

/**
 * Normalizează text simplu (max len + spații multiple).
 */
function fundraising_f230_text(?string $val, int $max = 255): string
{
    $txt = trim((string)$val);
    $txt = preg_replace('/\s+/u', ' ', $txt);
    if ($txt === null) {
        $txt = '';
    }
    return mb_substr($txt, 0, $max);
}

/**
 * Normalizează text pentru utilizare sigură în numele fișierelor.
 */
function fundraising_f230_filename_part(?string $val, int $max = 80): string
{
    $txt = fundraising_f230_text($val, $max);
    if ($txt === '') {
        return 'NECOMPLETAT';
    }
    $txt = strtr($txt, [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        'Ă' => 'A', 'Â' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ş' => 'S', 'Ț' => 'T', 'Ţ' => 'T',
    ]);
    $txt = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $txt);
    $txt = trim((string)$txt, '_');
    if ($txt === '') {
        return 'NECOMPLETAT';
    }
    return mb_substr($txt, 0, $max);
}

/**
 * Extrage datele formularului din POST (online sau manual).
 */
function fundraising_f230_extract_data(array $post): array
{
    return [
        'nume' => fundraising_f230_text($post['nume'] ?? '', 100),
        'initiala_tatalui' => fundraising_f230_text($post['initiala_tatalui'] ?? '', 3),
        'prenume' => fundraising_f230_text($post['prenume'] ?? '', 100),
        'cnp' => preg_replace('/\D+/', '', (string)($post['cnp'] ?? '')),
        'localitate' => fundraising_f230_text($post['localitate'] ?? '', 120),
        'judet' => fundraising_f230_text($post['judet'] ?? '', 120),
        'cod_postal' => preg_replace('/\D+/', '', (string)($post['cod_postal'] ?? '')),
        'strada' => fundraising_f230_text($post['strada'] ?? '', 160),
        'numar' => fundraising_f230_text($post['numar'] ?? '', 20),
        'bloc' => fundraising_f230_text($post['bloc'] ?? '', 10),
        'scara' => fundraising_f230_text($post['scara'] ?? '', 10),
        'etaj' => fundraising_f230_text($post['etaj'] ?? '', 10),
        'apartament' => fundraising_f230_text($post['apartament'] ?? '', 10),
        'telefon' => fundraising_f230_text($post['telefon'] ?? '', 50),
        'email' => fundraising_f230_text($post['email'] ?? '', 255),
        'gdpr_acord' => !empty($post['gdpr_acord']) ? 1 : 0,
        'signature_data' => trim((string)($post['signature_data'] ?? '')),
    ];
}

/**
 * Validare CNP România (structură + checksum).
 */
function fundraising_f230_valid_cnp(string $cnp): bool
{
    if (!preg_match('/^\d{13}$/', $cnp)) {
        return false;
    }
    $s = (int)$cnp[0];
    $aa = (int)substr($cnp, 1, 2);
    $ll = (int)substr($cnp, 3, 2);
    $zz = (int)substr($cnp, 5, 2);

    $secol = 1900;
    if ($s === 1 || $s === 2) {
        $secol = 1900;
    } elseif ($s === 3 || $s === 4) {
        $secol = 1800;
    } elseif ($s === 5 || $s === 6 || $s === 7 || $s === 8) {
        $secol = 2000;
    } elseif ($s === 9) {
        $secol = 1900;
    } else {
        return false;
    }

    $an = $secol + $aa;
    if (!checkdate($ll, $zz, $an)) {
        return false;
    }

    $const = '279146358279';
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += ((int)$cnp[$i]) * ((int)$const[$i]);
    }
    $control = $sum % 11;
    if ($control === 10) {
        $control = 1;
    }
    return $control === (int)$cnp[12];
}

/**
 * Validează datele formularului.
 */
function fundraising_f230_validate(array $data): ?string
{
    $required = [
        'nume' => 'Nume',
        'prenume' => 'Prenume',
        'cnp' => 'CNP',
        'localitate' => 'Localitatea',
        'judet' => 'Județ',
        'strada' => 'Strada',
        'numar' => 'Număr',
        'telefon' => 'Telefon',
        'email' => 'Email',
    ];
    foreach ($required as $key => $label) {
        if (trim((string)($data[$key] ?? '')) === '') {
            return 'Câmpul obligatoriu lipsește: ' . $label . '.';
        }
    }

    if (!fundraising_f230_valid_cnp((string)$data['cnp'])) {
        return 'CNP invalid. Verificați cele 13 cifre și algoritmul de control.';
    }
    if (!filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Adresa de email nu este validă.';
    }
    if ($data['cod_postal'] !== '' && !preg_match('/^\d{6}$/', (string)$data['cod_postal'])) {
        return 'Codul poștal trebuie să conțină exact 6 cifre.';
    }
    if ((int)$data['gdpr_acord'] !== 1) {
        return 'Acordul GDPR este obligatoriu.';
    }
    if (trim((string)$data['signature_data']) === '') {
        return 'Semnătura este obligatorie.';
    }

    return null;
}

/**
 * Salvează semnătura (data URL PNG) în F230PDF/signatures.
 */
function fundraising_f230_store_signature(string $signature_data): array
{
    if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $signature_data, $m)) {
        return ['success' => false, 'error' => 'Format semnătură invalid.'];
    }

    $binary = base64_decode($m[1], true);
    if ($binary === false || strlen($binary) < 64) {
        return ['success' => false, 'error' => 'Semnătura nu poate fi procesată.'];
    }

    $storage = fundraising_f230_ensure_private_storage();
    $filename = 'semnatura-' . date('Ymd-His') . '-' . substr(md5((string)uniqid('', true)), 0, 10) . '.png';
    $abs = $storage['signature_dir'] . '/' . $filename;
    if (@file_put_contents($abs, $binary) === false) {
        return ['success' => false, 'error' => 'Semnătura nu a putut fi salvată pe server.'];
    }

    return [
        'success' => true,
        'abs_path' => $abs,
        'rel_path' => fundraising_f230_rel_path($abs),
    ];
}

/**
 * Build map tag => valoare pe baza datelor formularului.
 */
function fundraising_f230_build_tag_values(array $data): array
{
    return [
        '230nume' => (string)$data['nume'],
        '230in' => (string)$data['initiala_tatalui'],
        '230prenume' => (string)$data['prenume'],
        '230CNP' => (string)$data['cnp'],
        '230loc' => (string)$data['localitate'],
        '230jud' => (string)$data['judet'],
        '230cp' => (string)$data['cod_postal'],
        '230str' => (string)$data['strada'],
        '230nr' => (string)$data['numar'],
        '230bl' => (string)$data['bloc'],
        '230sc' => (string)$data['scara'],
        '230et' => (string)$data['etaj'],
        '230ap' => (string)$data['apartament'],
        '230tel' => (string)$data['telefon'],
        '230email' => (string)$data['email'],
        '230semnatura' => '',
        '230gdpr' => ((int)$data['gdpr_acord'] === 1) ? 'DA' : 'NU',
    ];
}

/**
 * Generează PDF completat pe baza template-ului configurat.
 */
function fundraising_f230_generate_pdf(PDO $pdo, array $data, string $signature_abs_path, int $record_id): array
{
    $template_rel = trim((string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE) ?? ''));
    if ($template_rel === '') {
        return ['success' => false, 'error' => 'Nu există template PDF configurat în Setări Fundraising.'];
    }
    $template_abs = fundraising_f230_abs_path($template_rel);
    if (!is_file($template_abs)) {
        return ['success' => false, 'error' => 'Template-ul PDF configurat nu există pe server.'];
    }

    $autoload = APP_ROOT . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return ['success' => false, 'error' => 'Lipsesc bibliotecile Composer (vendor/autoload.php).'];
    }
    require_once $autoload;
    if (!class_exists('setasign\\Fpdi\\Fpdi')) {
        return ['success' => false, 'error' => 'FPDI nu este disponibil pentru procesarea template-ului PDF.'];
    }

    $tag_values = fundraising_f230_build_tag_values($data);
    $streams = documente_pdf_extract_page_streams($template_abs);
    if (empty($streams)) {
        return ['success' => false, 'error' => 'Template-ul PDF nu poate fi citit (stream-uri indisponibile).'];
    }

    $placements_by_page = [];
    $found_signature_tag = false;
    foreach ($streams as $idx => $stream) {
        $placements = documente_pdf_detect_tag_positions($stream, $tag_values, $idx + 1);
        if (!empty($placements)) {
            $placements_by_page[$idx + 1] = $placements;
            foreach ($placements as $pl) {
                if (($pl['tag'] ?? '') === '230semnatura') {
                    $found_signature_tag = true;
                }
            }
        }
    }

    if (empty($placements_by_page)) {
        return ['success' => false, 'error' => 'Template-ul PDF nu conține taguri detectabile [230...].'];
    }
    if (!$found_signature_tag) {
        return ['success' => false, 'error' => 'Template-ul PDF nu conține tagul obligatoriu [230semnatura].'];
    }

    $storage = fundraising_f230_ensure_private_storage();
    $nume_part = fundraising_f230_filename_part((string)$data['nume'], 70);
    $prenume_part = fundraising_f230_filename_part((string)$data['prenume'], 70);
    $loc_part = fundraising_f230_filename_part((string)$data['localitate'], 70);
    $pdf_filename = 'D230_' . $nume_part . '_' . $prenume_part . '_' . $loc_part . '.pdf';
    $dupe_index = 1;
    while (is_file($storage['base_dir'] . '/' . $pdf_filename)) {
        $dupe_index++;
        $pdf_filename = 'D230_' . $nume_part . '_' . $prenume_part . '_' . $loc_part . '_' . $dupe_index . '.pdf';
    }
    $pdf_abs = $storage['base_dir'] . '/' . $pdf_filename;

    try {
        $pdf = new \setasign\Fpdi\Fpdi();
        $page_count = $pdf->setSourceFile($template_abs);
        for ($page_no = 1; $page_no <= $page_count; $page_no++) {
            $tpl = $pdf->importPage($page_no);
            $size = $pdf->getTemplateSize($tpl);
            if ($size && isset($size['width'], $size['height'], $size['orientation'])) {
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            } else {
                $pdf->AddPage();
            }
            $pdf->useTemplate($tpl);

            $page_height_mm = (float)($size['height'] ?? 297.0);
            $placements = $placements_by_page[$page_no] ?? [];
            foreach ($placements as $pl) {
                $tag = (string)($pl['tag'] ?? '');
                $font_pt = max(7.0, min(14.0, (float)($pl['font_pt'] ?? 10.0)));
                $x_mm = documente_pdf_pt_to_mm((float)($pl['x_pt'] ?? 0.0));
                $y_mm = $page_height_mm - documente_pdf_pt_to_mm((float)($pl['y_pt'] ?? 0.0));
                $tag_width_mm = max(10.0, documente_pdf_pt_to_mm((float)($pl['tag_width_pt'] ?? 24.0)));
                $font_mm = documente_pdf_pt_to_mm($font_pt);

                // Ștergem vizual tagul [230...] înainte de suprascriere.
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect(
                    max(0.0, $x_mm - 0.5),
                    max(0.0, $y_mm - ($font_mm * 0.95)),
                    max(8.0, $tag_width_mm + 1.5),
                    max(2.0, $font_mm * 1.4),
                    'F'
                );

                if ($tag === '230semnatura') {
                    $sig_h = max(7.5, $font_mm * 2.5);
                    $sig_w = max(24.0, $tag_width_mm + 6.0);
                    $sig_y = max(0.0, $y_mm - $sig_h + 1.2);
                    $pdf->Image($signature_abs_path, $x_mm, $sig_y, $sig_w, $sig_h, 'PNG');
                    continue;
                }

                $value = trim((string)($pl['value'] ?? ''));
                if ($value === '') {
                    continue;
                }
                $value = preg_replace('/\s+/u', ' ', $value);
                $enc = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);
                if ($enc === false) {
                    $enc = $value;
                }
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('Helvetica', '', $font_pt);
                $pdf->Text($x_mm, $y_mm, (string)$enc);
            }
        }

        $pdf->Output('F', $pdf_abs);
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Eroare la generarea PDF: ' . $e->getMessage()];
    }

    if (!is_file($pdf_abs)) {
        return ['success' => false, 'error' => 'PDF-ul completat nu a fost salvat pe server.'];
    }

    return [
        'success' => true,
        'pdf_abs_path' => $pdf_abs,
        'pdf_rel_path' => fundraising_f230_rel_path($pdf_abs),
        'pdf_filename' => $pdf_filename,
    ];
}

/**
 * Returnează emailul platformei (destinatar admin pentru notificări).
 */
function fundraising_f230_platform_email(PDO $pdo): string
{
    $email = trim((string)(fundraising_setare_get($pdo, 'email_asociatie') ?? ''));
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    $settings = mailer_get_settings($pdo);
    $from = trim((string)($settings['from_email'] ?? ''));
    if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return $from;
    }
    return '';
}

/**
 * Înlocuiește tagurile [230...] într-un șablon text/HTML.
 */
function fundraising_f230_replace_tags(string $content, array $data): string
{
    $tag_values = fundraising_f230_build_tag_values($data);
    $repl = [];
    foreach ($tag_values as $tag => $val) {
        $repl['[' . $tag . ']'] = (string)$val;
    }
    return strtr($content, $repl);
}

/**
 * Trimite notificarea obligatorie către emailul platformei.
 */
function fundraising_f230_send_admin_email(PDO $pdo, array $data, string $pdf_abs_path): bool
{
    $to = fundraising_f230_platform_email($pdo);
    if ($to === '') {
        return false;
    }

    $subject = 'Formular 230 – ' . $data['nume'] . ' ' . $data['prenume'];
    $body = 'Formular 230 nou! ' . $data['nume'] . ' ' . $data['prenume']
        . ' din ' . $data['localitate'] . '/' . $data['judet']
        . ' a completat formularul 230 online. Telefon ' . $data['telefon']
        . ', email ' . $data['email'] . '. Formularul a fost incarcat in ERP.';
    $html = '<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . '</p>';

    return sendEmailWithAttachment($pdo, $to, $subject, $html, $pdf_abs_path, basename($pdf_abs_path));
}

/**
 * Trimite emailul de confirmare către persoana care a completat formularul.
 */
function fundraising_f230_send_confirmation_email(PDO $pdo, array $data): bool
{
    $to = trim((string)($data['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $template = (string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_CONFIRM) ?? '');
    if (trim($template) === '') {
        return false;
    }
    $subject = 'Confirmare Formular 230 – ' . $data['nume'] . ' ' . $data['prenume'];
    $html = fundraising_f230_replace_tags($template, $data);

    return sendEmailWithAttachment($pdo, $to, $subject, $html, null, null);
}

/**
 * Procesează complet un formular (online/manual): validare, salvare, PDF, email.
 */
function fundraising_f230_process_submission(PDO $pdo, array $post, array $opts = []): array
{
    fundraising_f230_ensure_schema($pdo);
    $data = fundraising_f230_extract_data($post);
    $sursa = ($opts['sursa'] ?? 'online') === 'manual' ? 'manual' : 'online';
    $trimite_confirmare = array_key_exists('trimite_confirmare', $opts)
        ? (bool)$opts['trimite_confirmare']
        : ($sursa === 'online');
    $ip = fundraising_f230_text((string)($opts['ip'] ?? ''), 64);
    $ua = fundraising_f230_text((string)($opts['user_agent'] ?? ''), 255);

    $valid_err = fundraising_f230_validate($data);
    if ($valid_err !== null) {
        return ['success' => false, 'error' => $valid_err];
    }

    $sig = fundraising_f230_store_signature((string)$data['signature_data']);
    if (empty($sig['success'])) {
        return ['success' => false, 'error' => $sig['error'] ?? 'Semnătura nu a putut fi salvată.'];
    }

    $record_id = 0;
    $pdf_abs = '';
    $warnings = [];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO fundraising_f230_formulare
            (nume, initiala_tatalui, prenume, cnp, localitate, judet, cod_postal, strada, numar, bloc, scara, etaj, apartament, telefon, email, gdpr_acord, semnatura_path, pdf_path, pdf_filename, sursa, ip_adresa, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', ?, ?, ?)");
        $stmt->execute([
            $data['nume'],
            $data['initiala_tatalui'] !== '' ? $data['initiala_tatalui'] : null,
            $data['prenume'],
            $data['cnp'],
            $data['localitate'],
            $data['judet'],
            $data['cod_postal'] !== '' ? $data['cod_postal'] : null,
            $data['strada'],
            $data['numar'],
            $data['bloc'] !== '' ? $data['bloc'] : null,
            $data['scara'] !== '' ? $data['scara'] : null,
            $data['etaj'] !== '' ? $data['etaj'] : null,
            $data['apartament'] !== '' ? $data['apartament'] : null,
            $data['telefon'],
            $data['email'],
            (int)$data['gdpr_acord'],
            (string)$sig['rel_path'],
            $sursa,
            $ip !== '' ? $ip : null,
            $ua !== '' ? $ua : null,
        ]);
        $record_id = (int)$pdo->lastInsertId();

        $pdf = fundraising_f230_generate_pdf($pdo, $data, (string)$sig['abs_path'], $record_id);
        if (empty($pdf['success'])) {
            throw new RuntimeException((string)($pdf['error'] ?? 'Generarea PDF a eșuat.'));
        }
        $pdf_abs = (string)$pdf['pdf_abs_path'];
        $pdf_rel = (string)$pdf['pdf_rel_path'];
        $pdf_name = (string)$pdf['pdf_filename'];

        $upd = $pdo->prepare("UPDATE fundraising_f230_formulare SET pdf_path = ?, pdf_filename = ? WHERE id = ?");
        $upd->execute([$pdf_rel, $pdf_name, $record_id]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (!empty($sig['abs_path']) && is_file((string)$sig['abs_path'])) {
            @unlink((string)$sig['abs_path']);
        }
        if ($pdf_abs !== '' && is_file($pdf_abs)) {
            @unlink($pdf_abs);
        }
        return ['success' => false, 'error' => 'Formularul nu a putut fi salvat: ' . $e->getMessage()];
    }

    if (!fundraising_f230_send_admin_email($pdo, $data, $pdf_abs)) {
        $warnings[] = 'Emailul către administrator nu a putut fi trimis.';
    }
    if ($trimite_confirmare && !fundraising_f230_send_confirmation_email($pdo, $data)) {
        $warnings[] = 'Emailul de confirmare către completator nu a putut fi trimis.';
    }

    return [
        'success' => true,
        'id' => $record_id,
        'warning' => !empty($warnings) ? implode(' ', $warnings) : null,
    ];
}

/**
 * Lista formularelor completate.
 */
function fundraising_f230_list_formulare(PDO $pdo, int $limit = 1000): array
{
    $limit = max(1, min(5000, $limit));
    $stmt = $pdo->prepare("SELECT *
        FROM fundraising_f230_formulare
        ORDER BY created_at DESC, id DESC
        LIMIT {$limit}");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Returnează un formular după ID.
 */
function fundraising_f230_get_formular(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM fundraising_f230_formulare WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Returnează calea absolută a PDF-ului pentru un formular.
 */
function fundraising_f230_get_pdf_abs_path(array $formular): string
{
    $rel = trim((string)($formular['pdf_path'] ?? ''));
    if ($rel === '') {
        return '';
    }
    return fundraising_f230_abs_path($rel);
}

/**
 * Export CSV pentru tabelul Formular 230 (Fundraising).
 */
function fundraising_f230_export_csv(PDO $pdo): void
{
    $rows = fundraising_f230_list_formulare($pdo, 50000);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="fundraising-formular-230-' . date('Y-m-d-His') . '.csv"');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, [
        'ID',
        'Nume',
        'Initiala tatalui',
        'Prenume',
        'CNP',
        'Localitate',
        'Judet',
        'Cod postal',
        'Strada',
        'Numar',
        'Bloc',
        'Scara',
        'Etaj',
        'Apartament',
        'Telefon',
        'Email',
        'GDPR',
        'Sursa',
        'Data creare',
        'Document PDF',
    ], ',');

    foreach ($rows as $r) {
        fputcsv($out, [
            (int)$r['id'],
            (string)$r['nume'],
            (string)($r['initiala_tatalui'] ?? ''),
            (string)$r['prenume'],
            (string)$r['cnp'],
            (string)$r['localitate'],
            (string)$r['judet'],
            (string)($r['cod_postal'] ?? ''),
            (string)$r['strada'],
            (string)$r['numar'],
            (string)($r['bloc'] ?? ''),
            (string)($r['scara'] ?? ''),
            (string)($r['etaj'] ?? ''),
            (string)($r['apartament'] ?? ''),
            (string)$r['telefon'],
            (string)$r['email'],
            ((int)$r['gdpr_acord'] === 1) ? 'DA' : 'NU',
            (string)$r['sursa'],
            (string)$r['created_at'],
            '/util/f230-descarca.php?id=' . (int)$r['id'],
        ], ',');
    }

    fclose($out);
    exit;
}
