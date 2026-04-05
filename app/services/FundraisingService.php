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
const FUNDRAISING_SETARE_TEMPLATE_MAPPING = 'fundraising_f230_template_mapping_json';
const FUNDRAISING_SETARE_TEMPLATE_UPLOADED_AT = 'fundraising_f230_template_uploaded_at';

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
 * Returnează calea template-ului implicit livrat în repository.
 */
function fundraising_f230_default_template_rel(): string
{
    return 'app/resources/fundraising/D230_ERP2026.pdf';
}

/**
 * Resolve calea efectivă de template:
 * 1) setarea salvată în DB, dacă există și fișierul este prezent;
 * 2) fallback pe template-ul implicit din repository.
 */
function fundraising_f230_resolve_template_rel(PDO $pdo): string
{
    $saved_rel = trim((string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE) ?? ''));
    if ($saved_rel !== '') {
        $saved_abs = fundraising_f230_abs_path($saved_rel);
        if (is_file($saved_abs)) {
            return $saved_rel;
        }
    }

    $default_rel = fundraising_f230_default_template_rel();
    $default_abs = fundraising_f230_abs_path($default_rel);
    if (is_file($default_abs)) {
        return $default_rel;
    }

    return '';
}

/**
 * Hash stabil SHA-256 pentru template-ul PDF activ.
 */
function fundraising_f230_template_sha256(string $template_abs): string
{
    if (!is_file($template_abs)) {
        return '';
    }
    $hash = @hash_file('sha256', $template_abs);
    return is_string($hash) ? strtolower(trim($hash)) : '';
}

/**
 * Returnează calea absolută a template-ului PDF activ.
 */
function fundraising_f230_template_abs(PDO $pdo): string
{
    $template_rel = fundraising_f230_resolve_template_rel($pdo);
    if ($template_rel === '') {
        return '';
    }
    $template_abs = fundraising_f230_abs_path($template_rel);
    return is_file($template_abs) ? $template_abs : '';
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
    $template_rel = fundraising_f230_resolve_template_rel($pdo);
    $template_abs = $template_rel !== '' ? fundraising_f230_abs_path($template_rel) : '';
    $confirm_html = (string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_CONFIRM) ?? '');
    $template_sha256 = $template_abs !== '' ? fundraising_f230_template_sha256($template_abs) : '';
    $template_map = fundraising_f230_get_template_map($pdo);
    $template_page_count = 0;
    if ($template_abs !== '' && is_file($template_abs)) {
        $template_page_count = fundraising_f230_get_template_pdf_page_count($template_abs);
    }
    $template_uploaded_at_raw = trim((string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE_UPLOADED_AT) ?? ''));
    if ($template_uploaded_at_raw === '' && $template_abs !== '' && is_file($template_abs)) {
        $mtime = @filemtime($template_abs);
        if (is_int($mtime) && $mtime > 0) {
            $template_uploaded_at_raw = date('c', $mtime);
        }
    }
    $template_uploaded_at_display = '';
    if ($template_uploaded_at_raw !== '') {
        try {
            $dt = new DateTimeImmutable($template_uploaded_at_raw);
            $template_uploaded_at_display = $dt->format('d.m.Y H:i:s');
        } catch (Throwable $e) {
            $template_uploaded_at_display = '';
        }
    }

    return [
        'template_rel' => $template_rel,
        'template_exists' => $template_abs !== '' && is_file($template_abs),
        'template_sha256' => $template_sha256,
        'template_preview_url' => '/util/f230-template-preview.php',
        'template_page_count' => $template_page_count,
        'template_mapat' => !empty($template_map['mapped']),
        'template_map_missing_tags' => (array)($template_map['missing_tags'] ?? []),
        'template_map_items_by_tag' => (array)($template_map['items_by_tag'] ?? []),
        'template_map_defaults_by_tag' => fundraising_f230_template_map_defaults_by_tag((array)($template_map['items_by_tag'] ?? [])),
        'template_uploaded_at' => $template_uploaded_at_raw,
        'template_uploaded_at_display' => $template_uploaded_at_display,
        'confirm_html' => $confirm_html,
        'public_url' => fundraising_f230_public_url(),
        'storage_folder' => 'F230PDF',
    ];
}

/**
 * Returnează lista tuturor tagurilor obligatorii în maparea template-ului.
 */
function fundraising_f230_required_map_tags(): array
{
    return array_keys(fundraising_f230_taguri());
}

/**
 * Item implicit de mapare pentru un tag.
 */
function fundraising_f230_default_map_item(string $tag): array
{
    $is_signature = $tag === '230semnatura';
    return [
        'tag' => $tag,
        'page' => 1,
        'x_pct' => 5.0,
        'y_pct' => 5.0,
        'w_pct' => $is_signature ? 22.0 : 18.0,
        'h_pct' => $is_signature ? 8.0 : 2.8,
        'font_pt' => $is_signature ? 10.0 : 10.0,
    ];
}

/**
 * Validează payload-ul mapării template-ului PDF.
 */
function fundraising_f230_validate_template_map(array $payload, string $expected_template_sha256, int $max_pages = 30): array
{
    $incoming_sha = strtolower(trim((string)($payload['template_sha256'] ?? '')));
    if ($incoming_sha === '' || $incoming_sha !== strtolower(trim($expected_template_sha256))) {
        return ['success' => false, 'error' => 'Maparea nu corespunde template-ului PDF activ. Reîncarcă pagina și remapează.'];
    }

    $items = $payload['items'] ?? null;
    if (!is_array($items) || empty($items)) {
        return ['success' => false, 'error' => 'Maparea este goală. Marchează poziția pentru toate câmpurile.'];
    }

    $allowed_tags = fundraising_f230_required_map_tags();
    $allowed_set = array_fill_keys($allowed_tags, true);
    $normalized = [];

    foreach ($items as $raw_item) {
        if (!is_array($raw_item)) {
            continue;
        }
        $tag = trim((string)($raw_item['tag'] ?? ''));
        if ($tag === '' || !isset($allowed_set[$tag])) {
            continue;
        }
        $page = (int)($raw_item['page'] ?? 1);
        $x_pct = (float)($raw_item['x_pct'] ?? 0.0);
        $y_pct = (float)($raw_item['y_pct'] ?? 0.0);
        $w_pct = (float)($raw_item['w_pct'] ?? 0.0);
        $h_pct = (float)($raw_item['h_pct'] ?? 0.0);
        $font_pt = (float)($raw_item['font_pt'] ?? 10.0);

        $max_allowed_pages = max(1, $max_pages);
        if ($page < 1 || $page > $max_allowed_pages) {
            return ['success' => false, 'error' => 'Pagina mapată este invalidă pentru tagul [' . $tag . '].'];
        }
        if ($x_pct < 0.0 || $x_pct > 100.0 || $y_pct < 0.0 || $y_pct > 100.0) {
            return ['success' => false, 'error' => 'Coordonatele pentru [' . $tag . '] trebuie să fie între 0 și 100%.'];
        }
        if ($w_pct <= 0.0 || $w_pct > 100.0 || $h_pct <= 0.0 || $h_pct > 100.0) {
            return ['success' => false, 'error' => 'Dimensiunile zonei pentru [' . $tag . '] trebuie să fie valide.'];
        }
        if ($font_pt < 6.0 || $font_pt > 24.0) {
            return ['success' => false, 'error' => 'Dimensiunea fontului pentru [' . $tag . '] este invalidă.'];
        }

        $normalized[$tag] = [
            'tag' => $tag,
            'page' => $page,
            'x_pct' => round($x_pct, 4),
            'y_pct' => round($y_pct, 4),
            'w_pct' => round($w_pct, 4),
            'h_pct' => round($h_pct, 4),
            'font_pt' => round($font_pt, 2),
        ];
    }

    $missing = [];
    foreach ($allowed_tags as $tag_name) {
        if (!isset($normalized[$tag_name])) {
            $missing[] = $tag_name;
        }
    }
    if (!empty($missing)) {
        return ['success' => false, 'error' => 'Maparea nu este completă. Lipsesc: ' . implode(', ', array_map(static function ($t) {
            return '[' . $t . ']';
        }, $missing)) . '.', 'missing_tags' => $missing];
    }

    return ['success' => true, 'items_by_tag' => $normalized, 'missing_tags' => []];
}

/**
 * Returnează maparea salvată pentru template-ul activ.
 */
function fundraising_f230_get_template_map(PDO $pdo): array
{
    $template_rel = fundraising_f230_resolve_template_rel($pdo);
    if ($template_rel === '') {
        return ['mapped' => false, 'items_by_tag' => [], 'missing_tags' => fundraising_f230_required_map_tags()];
    }
    $template_abs = fundraising_f230_abs_path($template_rel);
    if (!is_file($template_abs)) {
        return ['mapped' => false, 'items_by_tag' => [], 'missing_tags' => fundraising_f230_required_map_tags()];
    }
    $template_sha256 = fundraising_f230_template_sha256($template_abs);
    if ($template_sha256 === '') {
        return ['mapped' => false, 'items_by_tag' => [], 'missing_tags' => fundraising_f230_required_map_tags()];
    }

    $raw = (string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE_MAPPING) ?? '');
    if (trim($raw) === '') {
        return ['mapped' => false, 'items_by_tag' => [], 'missing_tags' => fundraising_f230_required_map_tags()];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['mapped' => false, 'items_by_tag' => [], 'missing_tags' => fundraising_f230_required_map_tags()];
    }

    $page_count = fundraising_f230_template_map_max_pages($template_abs);
    $validated = fundraising_f230_validate_template_map($decoded, $template_sha256, $page_count);
    if (empty($validated['success'])) {
        return [
            'mapped' => false,
            'items_by_tag' => [],
            'missing_tags' => (array)($validated['missing_tags'] ?? fundraising_f230_required_map_tags()),
            'error' => (string)($validated['error'] ?? ''),
        ];
    }

    return [
        'mapped' => true,
        'items_by_tag' => (array)$validated['items_by_tag'],
        'missing_tags' => [],
        'template_sha256' => $template_sha256,
    ];
}

/**
 * Returnează maparea implicită pentru fiecare tag (prefill UI mapper).
 */
function fundraising_f230_template_map_defaults_by_tag(array $items_by_tag): array
{
    $defaults = [];
    foreach (fundraising_f230_required_map_tags() as $tag) {
        $base = fundraising_f230_default_map_item($tag);
        if (isset($items_by_tag[$tag]) && is_array($items_by_tag[$tag])) {
            $defaults[$tag] = array_merge($base, $items_by_tag[$tag]);
        } else {
            $defaults[$tag] = $base;
        }
    }
    return $defaults;
}

/**
 * Salvează maparea manuală a câmpurilor pentru template-ul PDF activ.
 */
function fundraising_f230_save_template_map(PDO $pdo, array $post): array
{
    $template_rel = fundraising_f230_resolve_template_rel($pdo);
    if ($template_rel === '') {
        return ['success' => false, 'error' => 'Nu există template PDF activ pentru mapare.'];
    }
    $template_abs = fundraising_f230_abs_path($template_rel);
    if (!is_file($template_abs)) {
        return ['success' => false, 'error' => 'Template-ul PDF activ nu există pe server.'];
    }
    $template_sha256 = fundraising_f230_template_sha256($template_abs);
    if ($template_sha256 === '') {
        return ['success' => false, 'error' => 'Nu s-a putut calcula semnătura template-ului PDF.'];
    }

    $json = trim((string)($post['template_map_json'] ?? ''));
    if ($json === '') {
        return ['success' => false, 'error' => 'Payload mapare lipsă.'];
    }
    $payload = json_decode($json, true);
    if (!is_array($payload)) {
        return ['success' => false, 'error' => 'Payload mapare invalid (JSON).'];
    }

    $page_count = fundraising_f230_template_map_max_pages($template_abs);
    $validated = fundraising_f230_validate_template_map($payload, $template_sha256, $page_count);
    if (empty($validated['success'])) {
        return ['success' => false, 'error' => (string)($validated['error'] ?? 'Mapare invalidă.')];
    }

    $save_payload = [
        'template_sha256' => $template_sha256,
        'template_rel' => $template_rel,
        'mapped_at' => date('c'),
        'items' => array_values((array)$validated['items_by_tag']),
    ];
    fundraising_setare_set(
        $pdo,
        FUNDRAISING_SETARE_TEMPLATE_MAPPING,
        (string)json_encode($save_payload, JSON_UNESCAPED_UNICODE)
    );

    return ['success' => true];
}

/**
 * Verifică dacă fișierul încărcat este un PDF valid.
 */
function fundraising_f230_validate_uploaded_template_pdf(string $abs_path): ?string
{
    $check = documente_validate_template_integrity($abs_path, 'pdf');
    if (empty($check['ok'])) {
        return (string)($check['error'] ?? 'Template PDF invalid.');
    }
    return null;
}

/**
 * Mesaj prietenos pentru codurile de eroare upload PHP.
 */
function fundraising_f230_upload_error_message(int $upload_error): string
{
    switch ($upload_error) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Fișierul PDF depășește limita permisă de server.';
        case UPLOAD_ERR_PARTIAL:
            return 'Fișierul PDF a fost încărcat parțial. Reîncearcă upload-ul.';
        case UPLOAD_ERR_NO_FILE:
            return 'Selectează un fișier template PDF înainte de salvare.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Lipsește directorul temporar de upload pe server.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Serverul nu poate scrie fișierul încărcat pe disc.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload-ul fișierului a fost blocat de o extensie PHP a serverului.';
        case UPLOAD_ERR_OK:
        default:
            return 'Eroare la încărcarea fișierului template PDF.';
    }
}

/**
 * Procesează upload-ul unui template PDF și îl setează ca activ.
 * Resetează maparea existentă deoarece coordonatele vechi nu mai sunt valide.
 */
function fundraising_f230_upload_template_file(PDO $pdo, array $files): array
{
    $fisier = $files['template_pdf_230'] ?? null;
    if (!is_array($fisier)) {
        return ['success' => false, 'error' => 'Selectează un fișier template PDF înainte de salvare.'];
    }
    $upload_error = (int)($fisier['error'] ?? UPLOAD_ERR_OK);
    if ($upload_error !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => fundraising_f230_upload_error_message($upload_error)];
    }

    $ext = strtolower((string)pathinfo((string)($fisier['name'] ?? ''), PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['success' => false, 'error' => 'Template-ul trebuie să fie fișier PDF.'];
    }
    if ((int)($fisier['size'] ?? 0) > 20 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Template-ul PDF depășește 20 MB.'];
    }

    $upload_dir = APP_ROOT . '/uploads/fundraising/';
    if (!is_dir($upload_dir) && !@mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        return ['success' => false, 'error' => 'Directorul uploads/fundraising nu poate fi creat pe server.'];
    }
    if (!is_writable($upload_dir)) {
        @chmod($upload_dir, 0775);
        clearstatcache(true, $upload_dir);
    }
    if (!is_writable($upload_dir)) {
        return ['success' => false, 'error' => 'Directorul uploads/fundraising nu este inscriptibil. Verifică permisiunile serverului.'];
    }

    $tmp_name = (string)($fisier['tmp_name'] ?? '');
    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        return ['success' => false, 'error' => 'Fișierul temporar de upload nu este valid sau a expirat. Reîncearcă.'];
    }

    $filename = 'template-230-' . date('Ymd-His') . '-' . substr(md5((string)uniqid('', true)), 0, 10) . '.pdf';
    $dest = $upload_dir . $filename;
    if (!@move_uploaded_file($tmp_name, $dest)) {
        return ['success' => false, 'error' => 'Nu s-a putut salva template-ul PDF pe server.'];
    }

    $template_err = fundraising_f230_validate_uploaded_template_pdf($dest);
    if ($template_err !== null) {
        @unlink($dest);
        return ['success' => false, 'error' => $template_err];
    }

    $old_rel = trim((string)(fundraising_setare_get($pdo, FUNDRAISING_SETARE_TEMPLATE) ?? ''));
    $new_rel = 'uploads/fundraising/' . $filename;
    fundraising_setare_set($pdo, FUNDRAISING_SETARE_TEMPLATE, $new_rel);
    fundraising_setare_set($pdo, FUNDRAISING_SETARE_TEMPLATE_MAPPING, '');
    fundraising_setare_set($pdo, FUNDRAISING_SETARE_TEMPLATE_UPLOADED_AT, date('c'));

    if ($old_rel !== '') {
        $old_abs = fundraising_f230_abs_path($old_rel);
        if (is_file($old_abs) && $old_abs !== $dest) {
            @unlink($old_abs);
        }
    }

    return [
        'success' => true,
        'template_pages' => fundraising_f230_get_template_pdf_page_count($dest),
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

        $upload_res = ['success' => true, 'template_pages' => 0];
        $fisier = $files['template_pdf_230'] ?? null;
        if (is_array($fisier) && (int)($fisier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload_res = fundraising_f230_upload_template_file($pdo, $files);
            if (empty($upload_res['success'])) {
                return ['success' => false, 'error' => (string)($upload_res['error'] ?? 'Template-ul PDF nu a putut fi salvat.')];
            }
        }

        return [
            'success' => true,
            'error' => null,
            'template_uploaded' => !empty($upload_res['template_pages']),
            'template_pages' => (int)($upload_res['template_pages'] ?? 0),
        ];
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
 * Returnează numărul de pagini detectabile pentru template-ul PDF.
 */
function fundraising_f230_get_template_pdf_page_count(string $template_abs): int
{
    if (!is_file($template_abs)) {
        return 0;
    }

    // 1) Încearcă metoda FPDI (fiabilă pentru majoritatea PDF-urilor).
    try {
        $autoload = APP_ROOT . '/vendor/autoload.php';
        if (is_file($autoload) && !class_exists('setasign\\Fpdi\\Fpdi', false)) {
            require_once $autoload;
        }
        if (class_exists('setasign\\Fpdi\\Fpdi')) {
            $pdf = new \setasign\Fpdi\Fpdi();
            $count = (int)$pdf->setSourceFile($template_abs);
            if ($count > 0) {
                return $count;
            }
        }
    } catch (Throwable $e) {
        // Fallback la metodă tolerantă mai jos.
    }

    // 2) Fallback tolerant: numără markerii /Type /Page din binarul PDF.
    try {
        $bin = @file_get_contents($template_abs);
        if ($bin === false || $bin === '') {
            return 0;
        }
        $matches = @preg_match_all('/\/Type\s*\/Page\b/', (string)$bin, $m);
        if (is_int($matches) && $matches > 0) {
            return $matches;
        }
    } catch (Throwable $e) {
        return 0;
    }

    return 0;
}

/**
 * Returnează numărul maxim de pagini permis pentru validarea mapării.
 * Dacă serverul nu poate determina robust numărul de pagini, folosim o limită
 * sigură pentru a evita blocarea mapării (UI-ul pdf.js oferă numărul real).
 */
function fundraising_f230_template_map_max_pages(string $template_abs): int
{
    $count = fundraising_f230_get_template_pdf_page_count($template_abs);
    if ($count > 0) {
        return $count;
    }
    return 30;
}

/**
 * Detectează eroarea FPDI de compresie neacceptată.
 */
function fundraising_f230_is_fpdi_unsupported_compression_error(string $message): bool
{
    $msg = strtolower(trim($message));
    if ($msg === '') {
        return false;
    }
    return strpos($msg, 'compression technique') !== false
        || strpos($msg, 'free parser shipped with fpdi') !== false
        || strpos($msg, 'fpdi-pdf-parser') !== false;
}

/**
 * Returnează true dacă un binary shell este disponibil.
 */
function fundraising_f230_command_available(string $binary): bool
{
    $bin = trim($binary);
    if ($bin === '') {
        return false;
    }
    if (strpos($bin, '/') !== false || strpos($bin, '\\') !== false) {
        return is_file($bin) && is_executable($bin);
    }

    if (!function_exists('documente_exec_available') || !documente_exec_available()) {
        return false;
    }
    $check = 'command -v ' . escapeshellarg($bin) . ' >/dev/null 2>&1';
    @exec($check, $out, $code);
    return (int)$code === 0;
}

/**
 * Returnează path FPDI-compatible pentru template-ul dat (din cache sau generat).
 */
function fundraising_f230_get_or_create_fpdi_compatible_template(string $template_abs): array
{
    if (!is_file($template_abs)) {
        return ['success' => false, 'error' => 'Template-ul sursă nu există pentru conversie FPDI.'];
    }
    if (!function_exists('documente_exec_available') || !documente_exec_available()) {
        return ['success' => false, 'error' => 'Conversia automată necesită exec() activ pe server.'];
    }

    $hash = fundraising_f230_template_sha256($template_abs);
    if ($hash === '') {
        return ['success' => false, 'error' => 'Nu s-a putut calcula hash-ul template-ului PDF.'];
    }

    $dir_abs = APP_ROOT . '/uploads/fundraising/fpdi-compatible/';
    if (!is_dir($dir_abs) && !@mkdir($dir_abs, 0755, true) && !is_dir($dir_abs)) {
        return ['success' => false, 'error' => 'Nu s-a putut crea directorul de cache FPDI-compatible.'];
    }
    $dest_abs = $dir_abs . 'template-fpdi-' . $hash . '.pdf';
    if (is_file($dest_abs) && filesize($dest_abs) > 0) {
        return ['success' => true, 'path' => $dest_abs, 'cached' => true];
    }

    $tmp_base = $dir_abs . 'tmp-fpdi-' . $hash . '-' . substr(md5((string)uniqid('', true)), 0, 8);
    $candidates = [
        [
            'bin' => 'qpdf',
            'build' => static function (string $bin, string $src, string $dest) {
                $cmd = escapeshellarg($bin)
                    . ' --object-streams=disable --stream-data=uncompress '
                    . escapeshellarg($src) . ' ' . escapeshellarg($dest) . ' 2>&1';
                return ['cmd' => $cmd, 'output' => $dest];
            },
        ],
        [
            'bin' => 'gs',
            'build' => static function (string $bin, string $src, string $dest) {
                $cmd = escapeshellarg($bin)
                    . ' -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH '
                    . '-sOutputFile=' . escapeshellarg($dest) . ' '
                    . escapeshellarg($src) . ' 2>&1';
                return ['cmd' => $cmd, 'output' => $dest];
            },
        ],
        [
            'bin' => 'pdftocairo',
            'build' => static function (string $bin, string $src, string $dest) use ($tmp_base) {
                $out_base = $tmp_base . '-pdftocairo';
                $cmd = escapeshellarg($bin)
                    . ' -pdf ' . escapeshellarg($src) . ' ' . escapeshellarg($out_base) . ' 2>&1';
                return ['cmd' => $cmd, 'output' => $out_base . '.pdf'];
            },
        ],
    ];

    $last_error = 'Niciun utilitar de conversie PDF nu este disponibil (qpdf/gs/pdftocairo).';
    foreach ($candidates as $cand) {
        $bin = (string)$cand['bin'];
        if (!fundraising_f230_command_available($bin)) {
            continue;
        }
        $meta = $cand['build']($bin, $template_abs, $dest_abs);
        $cmd = (string)($meta['cmd'] ?? '');
        $out_file = (string)($meta['output'] ?? '');
        if ($cmd === '' || $out_file === '') {
            continue;
        }
        $output = [];
        $code = 1;
        @exec($cmd, $output, $code);
        if ((int)$code !== 0 || !is_file($out_file) || filesize($out_file) <= 0) {
            $last_error = 'Conversia cu ' . $bin . ' a eșuat.';
            continue;
        }

        if ($out_file !== $dest_abs) {
            if (!@rename($out_file, $dest_abs)) {
                $copy_ok = @copy($out_file, $dest_abs);
                @unlink($out_file);
                if (!$copy_ok) {
                    $last_error = 'Conversia a reușit, dar fișierul rezultat nu a putut fi mutat în cache.';
                    continue;
                }
            }
        }
        if (!is_file($dest_abs) || filesize($dest_abs) <= 0) {
            $last_error = 'Fișierul PDF convertit nu este valid.';
            continue;
        }
        return ['success' => true, 'path' => $dest_abs, 'cached' => false];
    }

    return ['success' => false, 'error' => $last_error];
}

/**
 * Renderizează overlay-ul mapat într-un PDF rezultat.
 */
function fundraising_f230_render_overlay_pdf(
    PDO $pdo,
    string $template_abs,
    string $pdf_abs,
    string $signature_abs_path,
    array $tag_values
): array {
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

            $page_width_mm = (float)($size['width'] ?? 210.0);
            $page_height_mm = (float)($size['height'] ?? 297.0);
            $placements = fundraising_f230_get_overlay_placements($pdo, $page_width_mm, $page_height_mm, $page_no);
            foreach ($placements as $pl) {
                $tag = (string)($pl['tag'] ?? '');
                $font_pt = max(7.0, min(14.0, (float)($pl['font_pt'] ?? 10.0)));
                $x_mm = (float)($pl['x_mm'] ?? 0.0);
                $y_mm_top = (float)($pl['y_mm'] ?? 0.0);
                $w_mm = max(3.0, (float)($pl['w_mm'] ?? 3.0));
                $h_mm = max(1.2, (float)($pl['h_mm'] ?? 1.2));
                $y_mm_bottom = $page_height_mm - $y_mm_top;
                $font_mm = documente_pdf_pt_to_mm($font_pt);

                // PDF Overlay: scriem valorile în zona mapată.
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect($x_mm, max(0.0, $y_mm_bottom - $h_mm), $w_mm, $h_mm, 'F');

                if ($tag === '230semnatura') {
                    $sig_h = max(4.0, $h_mm);
                    $sig_w = max(6.0, $w_mm);
                    $sig_y = max(0.0, $page_height_mm - $y_mm_top - $sig_h);
                    $pdf->Image($signature_abs_path, $x_mm, $sig_y, $sig_w, $sig_h, 'PNG');
                    continue;
                }

                $value = trim((string)($tag_values[$tag] ?? ''));
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
                $text_y = max(1.0, $y_mm_bottom - max(0.1, ($h_mm - $font_mm) * 0.35));
                $pdf->Text($x_mm + 0.4, $text_y, (string)$enc);
            }
        }

        $pdf->Output('F', $pdf_abs);
        return ['success' => true];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => (string)$e->getMessage()];
    }
}

/**
 * Transformă maparea salvată în poziții absolute (mm) pentru overlay.
 */
function fundraising_f230_get_overlay_placements(PDO $pdo, float $page_width_mm, float $page_height_mm, int $page_no): array
{
    $map = fundraising_f230_get_template_map($pdo);
    if (empty($map['mapped'])) {
        return [];
    }

    $items = (array)($map['items_by_tag'] ?? []);
    $placements = [];
    foreach ($items as $tag => $item) {
        if (!is_array($item)) {
            continue;
        }
        $item_page = (int)($item['page'] ?? 0);
        if ($item_page !== $page_no) {
            continue;
        }

        $x_pct = (float)($item['x_pct'] ?? 0.0);
        $y_pct = (float)($item['y_pct'] ?? 0.0);
        $w_pct = (float)($item['w_pct'] ?? 0.0);
        $h_pct = (float)($item['h_pct'] ?? 0.0);
        $font_pt = (float)($item['font_pt'] ?? 10.0);

        $placements[] = [
            'tag' => (string)$tag,
            'x_mm' => ($x_pct / 100.0) * $page_width_mm,
            'y_mm' => ($y_pct / 100.0) * $page_height_mm,
            'w_mm' => ($w_pct / 100.0) * $page_width_mm,
            'h_mm' => ($h_pct / 100.0) * $page_height_mm,
            'font_pt' => $font_pt,
        ];
    }

    return $placements;
}

/**
 * Generează PDF completat pe baza template-ului configurat.
 */
function fundraising_f230_generate_pdf(PDO $pdo, array $data, string $signature_abs_path, int $record_id): array
{
    $template_rel = fundraising_f230_resolve_template_rel($pdo);
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
    $map = fundraising_f230_get_template_map($pdo);
    if (empty($map['mapped'])) {
        return ['success' => false, 'error' => 'Template-ul PDF nu este mapat complet. Configurează maparea în tabul Setări.'];
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

    $render = fundraising_f230_render_overlay_pdf($pdo, $template_abs, $pdf_abs, $signature_abs_path, $tag_values);
    if (empty($render['success'])) {
        $message = (string)($render['error'] ?? 'Eroare necunoscută la randarea PDF.');
        if (!fundraising_f230_is_fpdi_unsupported_compression_error($message)) {
            return ['success' => false, 'error' => 'Eroare la generarea PDF: ' . $message];
        }

        $converted = fundraising_f230_get_or_create_fpdi_compatible_template($template_abs);
        if (!empty($converted['success'])) {
            $retry_abs = (string)($converted['path'] ?? '');
            $retry_render = fundraising_f230_render_overlay_pdf($pdo, $retry_abs, $pdf_abs, $signature_abs_path, $tag_values);
            if (empty($retry_render['success'])) {
                return ['success' => false, 'error' => 'Eroare la generarea PDF: ' . (string)($retry_render['error'] ?? 'Randare eșuată după conversie.')];
            }
        } else {
            return ['success' => false, 'error' => 'Template PDF incompatibil FPDI și nu a putut fi convertit automat pe server.'];
        }
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
 * Returnează lista adreselor de administrare la care trimitem notificarea.
 * Prioritate:
 * 1) email_asociatie
 * 2) from_email (dacă diferă)
 * 3) utilizatori activi care au primeste_notificari_email=1
 */
function fundraising_f230_platform_email_targets(PDO $pdo): array
{
    $targets = [];
    $add = static function (string $email) use (&$targets): void {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $key = strtolower($email);
        if (!isset($targets[$key])) {
            $targets[$key] = $email;
        }
    };

    $add((string)(fundraising_setare_get($pdo, 'email_asociatie') ?? ''));

    $settings = mailer_get_settings($pdo);
    $add((string)($settings['from_email'] ?? ''));

    try {
        $stmt = $pdo->query("SELECT email FROM utilizatori WHERE activ = 1 AND primeste_notificari_email = 1");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            $add((string)($row['email'] ?? ''));
        }
    } catch (Throwable $e) {
        // Nu blocăm fluxul dacă tabela utilizatori nu este disponibilă.
    }

    return array_values($targets);
}

/**
 * Returnează un mesaj explicit de eroare dacă lipsește configurarea minimă email.
 */
function fundraising_f230_validate_email_config(PDO $pdo): ?string
{
    $targets = fundraising_f230_platform_email_targets($pdo);
    if (empty($targets)) {
        return 'Nu este configurată nicio adresă validă pentru administrator (Setări > Generare documente: email_asociatie sau Setări > Email: from_email / utilizatori cu notificări email).';
    }
    $settings = mailer_get_settings($pdo);
    $from_email = trim((string)($settings['from_email'] ?? ''));
    if ($from_email === '' || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        return 'Nu este configurată o adresă validă de expeditor (Setări > Email: from_email).';
    }
    return null;
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
 * Returnează textul mesajului de submit în funcție de sursă.
 */
function fundraising_f230_submission_action_text(string $sursa): string
{
    return $sursa === 'manual'
        ? 'a fost introdus manual în ERP.'
        : 'a completat formularul 230 online.';
}

/**
 * Trimite notificarea obligatorie către emailul platformei.
 */
function fundraising_f230_send_admin_email(PDO $pdo, array $data, string $pdf_abs_path): bool
{
    $targets = fundraising_f230_platform_email_targets($pdo);
    if (empty($targets)) {
        return false;
    }

    $subject = 'Formular 230 – ' . $data['nume'] . ' ' . $data['prenume'];
    $sursa = (string)($data['sursa'] ?? 'online');
    $body = 'Formular 230 nou! ' . $data['nume'] . ' ' . $data['prenume']
        . ' din ' . $data['localitate'] . '/' . $data['judet']
        . ' ' . fundraising_f230_submission_action_text($sursa) . ' Telefon ' . $data['telefon']
        . ', email ' . $data['email'] . '. Formularul a fost incarcat in ERP.';
    $html = '<p>' . nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8')) . '</p>';

    // Păstrăm trimiterea documentului către administrator conform cerinței.
    $ok_any = false;
    foreach ($targets as $to) {
        if (sendEmailWithAttachment($pdo, (string)$to, $subject, $html, $pdf_abs_path, basename($pdf_abs_path))) {
            $ok_any = true;
        }
    }
    return $ok_any;
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
    $text = trim((string)html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($text === '') {
        $text = 'Confirmare Formular 230';
    }
    return sendAutomatedEmail($pdo, $to, $subject, $text);
}

/**
 * Trimite emailurile de notificare pentru un formular salvat.
 * Se poate apela sincron sau post-răspuns (fastcgi_finish_request).
 */
function fundraising_f230_dispatch_submission_emails(PDO $pdo, int $formular_id, bool $trimite_confirmare = true): array
{
    $warnings = [];
    $config_error = fundraising_f230_validate_email_config($pdo);
    if ($config_error !== null) {
        return ['warning' => $config_error];
    }
    $formular = fundraising_f230_get_formular($pdo, $formular_id);
    if (!$formular) {
        return ['warning' => 'Formularul nu a fost găsit pentru notificări email.'];
    }

    $pdf_abs = fundraising_f230_get_pdf_abs_path($formular);
    if (!fundraising_f230_send_admin_email($pdo, $formular, $pdf_abs)) {
        $warnings[] = 'Emailul către administrator nu a putut fi trimis.';
    }
    if ($trimite_confirmare && !fundraising_f230_send_confirmation_email($pdo, $formular)) {
        $warnings[] = 'Emailul de confirmare către completator nu a putut fi trimis.';
    }

    return ['warning' => !empty($warnings) ? implode(' ', $warnings) : null];
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
    $trimite_emailuri = array_key_exists('trimite_emailuri', $opts)
        ? (bool)$opts['trimite_emailuri']
        : true;
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

    if ($trimite_emailuri) {
        $dispatch = fundraising_f230_dispatch_submission_emails($pdo, $record_id, $trimite_confirmare);
        if (!empty($dispatch['warning'])) {
            $warnings[] = (string)$dispatch['warning'];
        }
    }

    return [
        'success' => true,
        'id' => $record_id,
        'trimite_confirmare' => $trimite_confirmare,
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
