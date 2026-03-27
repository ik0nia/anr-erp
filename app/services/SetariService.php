<?php
/**
 * SetariService — Business logic for the Settings module.
 *
 * Centralised settings CRUD, user management, email config,
 * cotizatii/incasari/registratura/newsletter/documente settings.
 * Does NOT access $_GET, $_POST, $_SESSION directly.
 * Does NOT generate HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/excel_import.php';
require_once APP_ROOT . '/includes/file_helper.php';
require_once APP_ROOT . '/includes/document_helper.php';
require_once APP_ROOT . '/includes/registru_interactiuni_v2_helper.php';
require_once APP_ROOT . '/includes/mailer_functions.php';
require_once APP_ROOT . '/includes/cotizatii_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

// ---------------------------------------------------------------------------
// Setari table (key-value store) — ensure + get/set
// ---------------------------------------------------------------------------

/**
 * Ensure the `setari` key-value table exists.
 */
function setari_ensure_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cheie VARCHAR(100) NOT NULL UNIQUE,
        valoare TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Get a setting value by key. Returns null when not found.
 */
function setari_get(PDO $pdo, string $key): ?string
{
    try {
        $stmt = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Set (insert or update) a setting value. Returns old value.
 */
function setari_set(PDO $pdo, string $key, string $value): ?string
{
    setari_ensure_table($pdo);
    $old = setari_get($pdo, $key);
    $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
    $stmt->execute([$key, $value]);
    return $old;
}

/**
 * Bulk-load multiple settings by keys.
 * Returns associative array keyed by setting key.
 */
function setari_get_bulk(PDO $pdo, array $keys): array
{
    if (empty($keys)) return [];
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $result = [];
    try {
        $stmt = $pdo->prepare("SELECT cheie, valoare FROM setari WHERE cheie IN ($placeholders)");
        $stmt->execute($keys);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['cheie']] = (string)($row['valoare'] ?? '');
        }
    } catch (PDOException $e) {}
    return $result;
}

// ---------------------------------------------------------------------------
// User management
// ---------------------------------------------------------------------------

/**
 * List all users (admin only).
 */
function setari_users_list(PDO $pdo): array
{
    auth_ensure_tables($pdo);
    try {
        $stmt = $pdo->query('SELECT id, nume_complet, email, functie, username, rol, activ, primeste_notificari_email, created_at FROM utilizatori ORDER BY nume_complet');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Create a new user. Returns ['success' => bool, 'error' => string|null].
 */
function setari_user_create(PDO $pdo, array $data): array
{
    auth_ensure_tables($pdo);
    $nume_complet = trim($data['nume_complet'] ?? '');
    $email = trim($data['email'] ?? '');
    $functie = trim($data['functie'] ?? '');
    $username = trim($data['username'] ?? '');
    $parola = (string)($data['parola'] ?? '');
    $rol = in_array($data['rol'] ?? '', ['administrator', 'operator']) ? $data['rol'] : 'operator';

    if ($nume_complet === '' || $email === '' || $username === '' || $parola === '') {
        return ['success' => false, 'error' => 'Numele complet, emailul, numele de utilizator și parola sunt obligatorii.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Adresa de email nu este validă.'];
    }
    if (strlen($parola) < 6) {
        return ['success' => false, 'error' => 'Parola trebuie să aibă minim 6 caractere.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM utilizatori WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Există deja un utilizator cu acest nume de utilizator.'];
        }

        $hash = password_hash($parola, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO utilizatori (nume_complet, email, functie, username, parola_hash, rol, activ) VALUES (?, ?, ?, ?, ?, ?, 1)')
            ->execute([$nume_complet, $email, $functie ?: null, $username, $hash, $rol]);

        auth_trimite_email_confirmare_utilizator($nume_complet, $email, $username, $functie, $rol);
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare utilizator: ' . $e->getMessage()];
    }
}

/**
 * Update an existing user.
 */
function setari_user_update(PDO $pdo, int $id, array $data, ?int $current_user_id = null): array
{
    auth_ensure_tables($pdo);
    if ($id <= 0) {
        return ['success' => false, 'error' => 'Utilizator invalid.'];
    }

    $nume_complet = trim((string)($data['nume_complet'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $functie = trim((string)($data['functie'] ?? ''));
    $username = trim((string)($data['username'] ?? ''));
    $rol = in_array(($data['rol'] ?? ''), ['administrator', 'operator'], true) ? (string)$data['rol'] : 'operator';
    $activ = !empty($data['activ']) ? 1 : 0;
    $primeste_notificari = !empty($data['primeste_notificari_email']) ? 1 : 0;
    $parola_noua = (string)($data['parola_noua'] ?? '');

    if ($nume_complet === '' || $email === '' || $username === '') {
        return ['success' => false, 'error' => 'Numele complet, emailul și numele de utilizator sunt obligatorii.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Adresa de email nu este validă.'];
    }
    if ($parola_noua !== '' && strlen($parola_noua) < 6) {
        return ['success' => false, 'error' => 'Parola nouă trebuie să aibă minim 6 caractere.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM utilizatori WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $curent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$curent) {
            return ['success' => false, 'error' => 'Utilizatorul nu a fost găsit.'];
        }

        $stmtU = $pdo->prepare('SELECT id FROM utilizatori WHERE username = ? AND id <> ? LIMIT 1');
        $stmtU->execute([$username, $id]);
        if ($stmtU->fetch()) {
            return ['success' => false, 'error' => 'Există deja un utilizator cu acest nume de utilizator.'];
        }

        $stmtE = $pdo->prepare('SELECT id FROM utilizatori WHERE email = ? AND id <> ? LIMIT 1');
        $stmtE->execute([$email, $id]);
        if ($stmtE->fetch()) {
            return ['success' => false, 'error' => 'Există deja un utilizator cu această adresă de email.'];
        }

        if ($current_user_id !== null && $current_user_id === $id && $activ === 0) {
            return ['success' => false, 'error' => 'Nu vă puteți dezactiva propriul cont.'];
        }

        // Protecție: nu permitem eliminarea ultimului administrator activ.
        $este_admin_activ_curent = (($curent['rol'] ?? '') === 'administrator' && (int)($curent['activ'] ?? 0) === 1);
        $va_ramane_admin_activ = ($rol === 'administrator' && $activ === 1);
        if ($este_admin_activ_curent && !$va_ramane_admin_activ) {
            $stmtAdmins = $pdo->query("SELECT COUNT(*) FROM utilizatori WHERE rol = 'administrator' AND activ = 1");
            $nr_admini_activi = (int)$stmtAdmins->fetchColumn();
            if ($nr_admini_activi <= 1) {
                return ['success' => false, 'error' => 'Nu puteți modifica ultimul administrator activ.'];
            }
        }

        if ($parola_noua !== '') {
            $hash = password_hash($parola_noua, PASSWORD_DEFAULT);
            $sql = 'UPDATE utilizatori SET nume_complet = ?, email = ?, functie = ?, username = ?, rol = ?, activ = ?, primeste_notificari_email = ?, parola_hash = ? WHERE id = ?';
            $pdo->prepare($sql)->execute([
                $nume_complet,
                $email,
                $functie !== '' ? $functie : null,
                $username,
                $rol,
                $activ,
                $primeste_notificari,
                $hash,
                $id
            ]);
        } else {
            $sql = 'UPDATE utilizatori SET nume_complet = ?, email = ?, functie = ?, username = ?, rol = ?, activ = ?, primeste_notificari_email = ? WHERE id = ?';
            $pdo->prepare($sql)->execute([
                $nume_complet,
                $email,
                $functie !== '' ? $functie : null,
                $username,
                $rol,
                $activ,
                $primeste_notificari,
                $id
            ]);
        }

        log_activitate($pdo, 'Setări: utilizator actualizat ID ' . $id . ' (' . $username . ')');
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizare utilizator: ' . $e->getMessage()];
    }
}

/**
 * Delete a user.
 */
function setari_user_delete(PDO $pdo, int $id, ?int $current_user_id = null): array
{
    auth_ensure_tables($pdo);
    if ($id <= 0) {
        return ['success' => false, 'error' => 'Utilizator invalid.'];
    }

    try {
        if ($current_user_id !== null && $current_user_id === $id) {
            return ['success' => false, 'error' => 'Nu vă puteți șterge propriul cont.'];
        }

        $stmt = $pdo->prepare('SELECT id, username, rol, activ FROM utilizatori WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $utilizator = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$utilizator) {
            return ['success' => false, 'error' => 'Utilizatorul nu a fost găsit.'];
        }

        if (($utilizator['rol'] ?? '') === 'administrator' && (int)($utilizator['activ'] ?? 0) === 1) {
            $stmtAdmins = $pdo->query("SELECT COUNT(*) FROM utilizatori WHERE rol = 'administrator' AND activ = 1");
            $nr_admini_activi = (int)$stmtAdmins->fetchColumn();
            if ($nr_admini_activi <= 1) {
                return ['success' => false, 'error' => 'Nu puteți șterge ultimul administrator activ.'];
            }
        }

        $pdo->prepare('DELETE FROM utilizatori WHERE id = ?')->execute([$id]);
        log_activitate($pdo, 'Setări: utilizator șters ID ' . $id . ' (' . ($utilizator['username'] ?? '-') . ')');
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la ștergere utilizator: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Logo
// ---------------------------------------------------------------------------

/**
 * Update the platform logo URL.
 */
function setari_update_logo(PDO $pdo, string $logo_url): array
{
    if (empty($logo_url)) {
        return ['success' => false, 'error' => 'URL-ul logo-ului este obligatoriu.'];
    }
    if (!filter_var($logo_url, FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'URL-ul introdus nu este valid.'];
    }
    try {
        $old = setari_set($pdo, 'logo_url', $logo_url);
        log_activitate($pdo, log_format_modificare('Logo URL', $old ?: '(gol)', $logo_url, 'setari'));
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Platform name
// ---------------------------------------------------------------------------

/**
 * Update the platform name.
 */
function setari_update_platform_name(PDO $pdo, string $name): array
{
    if (empty($name)) {
        return ['success' => false, 'error' => 'Numele platformei este obligatoriu.'];
    }
    try {
        $old = setari_set($pdo, 'platform_name', $name);
        if ($old === null) $old = PLATFORM_NAME;
        log_activitate($pdo, log_format_modificare('Nume platforma', $old, $name, 'setari'));
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Antet asociatie (DOCX upload)
// ---------------------------------------------------------------------------

/**
 * Upload and save the association header DOCX file.
 * $file is the $_FILES['antet_docx'] entry.
 */
function setari_upload_antet(PDO $pdo, array $file): array
{
    if (!isset($file) || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        $err = $file['error'] ?? 0;
        return ['success' => false, 'error' => $err === UPLOAD_ERR_NO_FILE ? 'Selectați un fișier DOCX.' : 'Eroare la încărcarea fișierului.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'docx') {
        return ['success' => false, 'error' => 'Doar fișiere DOCX sunt acceptate.'];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Fișierul depășește 10 MB.'];
    }

    $upload_dir = APP_ROOT . '/uploads/antet/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = 'antet_' . time() . '_' . preg_replace('/[^a-z0-9_-]/i', '', substr(uniqid(), -8)) . '.docx';
    $full_path = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $full_path)) {
        return ['success' => false, 'error' => 'Eroare la salvarea fișierului pe server.'];
    }

    try {
        $rel_path = 'uploads/antet/' . $filename;
        setari_set($pdo, 'antet_asociatie_docx', $rel_path);
        log_activitate($pdo, 'Setări: antet asociație DOCX încărcat – ' . $filename);
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        @unlink($full_path);
        return ['success' => false, 'error' => 'Eroare la salvare setare: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Email settings
// ---------------------------------------------------------------------------

/**
 * Get current email (SMTP) settings.
 */
function setari_email_config(PDO $pdo): array
{
    mailer_ensure_table($pdo);
    return mailer_get_settings($pdo);
}

/**
 * Save SMTP / sender settings.
 */
function setari_email_save(PDO $pdo, array $data): array
{
    mailer_ensure_table($pdo);
    $smtp_host = trim($data['smtp_host'] ?? '');
    $smtp_port = (int)($data['smtp_port'] ?? 587);
    if ($smtp_port < 1 || $smtp_port > 65535) $smtp_port = 587;
    $smtp_user = trim($data['smtp_user'] ?? '');
    $smtp_pass = (string)($data['smtp_pass'] ?? '');
    $smtp_encryption = in_array($data['smtp_encryption'] ?? '', ['tls', 'ssl', '']) ? trim($data['smtp_encryption']) : 'tls';
    $from_name = trim($data['from_name'] ?? '');
    $from_email = trim($data['from_email'] ?? '');
    $email_signature = trim($data['email_signature'] ?? '');

    try {
        $pass_val = $smtp_pass !== '' ? $smtp_pass : (mailer_get_settings($pdo)['smtp_pass'] ?? '');
        $stmt = $pdo->prepare("UPDATE settings_email SET smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, smtp_encryption=?, from_name=?, from_email=?, email_signature=? WHERE id=1");
        $stmt->execute([$smtp_host ?: null, $smtp_port, $smtp_user ?: null, $pass_val, $smtp_encryption ?: null, $from_name ?: null, $from_email ?: null, $email_signature ?: null]);
        log_activitate($pdo, 'Setări Email (EMAILCRM) actualizate.');
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare setări email: ' . $e->getMessage()];
    }
}

/**
 * Send a test email. Returns ['success' => bool, 'error' => string|null].
 */
function setari_email_test(PDO $pdo, string $destinatar, ?int $user_id): array
{
    if ($destinatar === '' && $user_id) {
        $stmt = $pdo->prepare('SELECT email FROM utilizatori WHERE id = ? AND activ = 1');
        $stmt->execute([$user_id]);
        $destinatar = trim((string)$stmt->fetchColumn());
    }
    if ($destinatar === '' || !filter_var($destinatar, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Introduceți o adresă de email validă sau asigurați-vă că utilizatorul logat are email setat.'];
    }
    $result = sendAutomatedEmailDetailed($pdo, $destinatar, 'Test Email CRM – Setări email', 'Acesta este un email de test trimis din modulul Setări → Email. Setările SMTP/expeditor sunt funcționale.');
    if ($result['success']) {
        return ['success' => true, 'error' => null, 'destinatar' => $destinatar];
    }
    return ['success' => false, 'error' => $result['error'] ?: 'Trimiterea emailului de test a eșuat. Verificați setările SMTP și adresa expeditor.'];
}

// ---------------------------------------------------------------------------
// Registratura settings
// ---------------------------------------------------------------------------

/**
 * Save the registratura starting number.
 */
function setari_registratura_save(PDO $pdo, int $nr_pornire): array
{
    if ($nr_pornire < 1) $nr_pornire = 1;
    try {
        $old = setari_set($pdo, 'registratura_nr_pornire', (string)$nr_pornire);
        log_activitate($pdo, log_format_modificare('Registratura nr pornire', $old ?: '(gol)', (string)$nr_pornire, 'setari'));
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Newsletter settings
// ---------------------------------------------------------------------------

/**
 * Save newsletter sender email.
 */
function setari_newsletter_save(PDO $pdo, string $email): array
{
    try {
        $old = setari_set($pdo, 'newsletter_email', $email);
        if ($old !== $email) {
            log_activitate($pdo, log_format_modificare('Email newsletter (expeditor)', $old ?: '(gol)', $email ?: '(gol)', 'setari'));
        }
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Documente settings
// ---------------------------------------------------------------------------

/**
 * Save document generation settings (email + LibreOffice path).
 */
function setari_documente_save(PDO $pdo, string $email_asoc, string $libreoffice): array
{
    try {
        $old_email = setari_set($pdo, 'email_asociatie', $email_asoc);
        $old_lo = setari_set($pdo, 'cale_libreoffice', $libreoffice);

        if ($old_email !== $email_asoc) {
            log_activitate($pdo, log_format_modificare('Email asociatie', $old_email ?: '(gol)', $email_asoc ?: '(gol)', 'setari'));
        }
        if ($old_lo !== $libreoffice) {
            log_activitate($pdo, log_format_modificare('Cale LibreOffice', $old_lo ?: '(gol)', $libreoffice ?: '(gol)', 'setari'));
        }
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare: ' . $e->getMessage()];
    }
}

/**
 * Save custom HTML header for printable documents.
 */
function setari_save_documente_antet_html(PDO $pdo, string $antet_html): array
{
    try {
        $old = setari_get($pdo, 'documente_antet_html') ?? '';
        $new = documente_antet_sanitize_html($antet_html);
        setari_set($pdo, 'documente_antet_html', $new);
        if ($old !== $new) {
            log_activitate($pdo, 'Setări: antet documente actualizat.');
        }
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare antet documente: ' . $e->getMessage()];
    }
}

/**
 * Upload imagine pentru antetul documentelor (alternativă la antetul HTML).
 * $file este intrarea din $_FILES['documente_antet_image'].
 */
function setari_upload_documente_antet_image(PDO $pdo, array $file): array
{
    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'error' => null];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'path' => null, 'error' => 'Eroare la încărcarea imaginii antet.'];
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        return ['success' => false, 'path' => null, 'error' => 'Imaginea antet depășește 8 MB.'];
    }

    $allowedByMime = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExt = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

    $mime = '';
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mime = (string)finfo_file($f, (string)($file['tmp_name'] ?? ''));
            finfo_close($f);
        }
    }

    $finalExt = '';
    if ($mime !== '' && isset($allowedByMime[$mime])) {
        $finalExt = $allowedByMime[$mime];
    } elseif (in_array($ext, $allowedExt, true)) {
        $finalExt = $ext === 'jpeg' ? 'jpg' : $ext;
    }
    if ($finalExt === '') {
        return ['success' => false, 'path' => null, 'error' => 'Format imagine neacceptat. Sunt permise PNG/JPG/WEBP/GIF.'];
    }

    $upload_dir = APP_ROOT . '/uploads/antet-documente/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        return ['success' => false, 'path' => null, 'error' => 'Nu s-a putut crea directorul pentru imagini antet.'];
    }

    $filename = 'antet_documente_' . date('Ymd_His') . '_' . substr(md5((string)uniqid('', true)), 0, 10) . '.' . $finalExt;
    $full_path = $upload_dir . $filename;
    if (!move_uploaded_file((string)$file['tmp_name'], $full_path)) {
        return ['success' => false, 'path' => null, 'error' => 'Nu s-a putut salva imaginea antet pe server.'];
    }

    try {
        $rel_path = 'uploads/antet-documente/' . $filename;
        $old_path = trim((string)(setari_get($pdo, 'documente_antet_image_path') ?? ''));
        setari_set($pdo, 'documente_antet_image_path', $rel_path);
        if ($old_path !== '' && $old_path !== $rel_path) {
            $old_abs = APP_ROOT . '/' . ltrim($old_path, '/');
            if (is_file($old_abs)) {
                @unlink($old_abs);
            }
        }
        log_activitate($pdo, 'Setări: imagine antet documente încărcată – ' . $filename);
        return ['success' => true, 'path' => $rel_path, 'error' => null];
    } catch (PDOException $e) {
        @unlink($full_path);
        return ['success' => false, 'path' => null, 'error' => 'Eroare la salvare setare imagine antet: ' . $e->getMessage()];
    }
}

/**
 * Salvează configurația antetului documente (sursă HTML sau imagine).
 */
function setari_save_documente_antet_config(PDO $pdo, string $source, string $antet_html, string $image_alt = ''): array
{
    $source = $source === 'image' ? 'image' : 'html';
    $image_alt = trim($image_alt);
    if ($image_alt === '') {
        $image_alt = 'Antet documente platformă';
    }

    try {
        $new_html = documente_antet_sanitize_html($antet_html);
        $old_html = setari_get($pdo, 'documente_antet_html') ?? '';
        $old_source = setari_get($pdo, 'documente_antet_source') ?? 'html';
        $old_alt = setari_get($pdo, 'documente_antet_image_alt') ?? '';

        if ($source === 'image') {
            $img_path = trim((string)(setari_get($pdo, 'documente_antet_image_path') ?? ''));
            $img_abs = $img_path !== '' ? APP_ROOT . '/' . ltrim($img_path, '/') : '';
            if ($img_path === '' || !is_file($img_abs)) {
                return ['success' => false, 'error' => 'Selectați și încărcați o imagine validă pentru antet.'];
            }
        }

        setari_set($pdo, 'documente_antet_html', $new_html);
        setari_set($pdo, 'documente_antet_source', $source);
        setari_set($pdo, 'documente_antet_image_alt', $image_alt);

        if ($old_html !== $new_html || $old_source !== $source || $old_alt !== $image_alt) {
            log_activitate($pdo, 'Setări: configurație antet documente actualizată (' . $source . ').');
        }
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare configurare antet documente: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Cotizatii settings (delegates to cotizatii_helper)
// ---------------------------------------------------------------------------

/**
 * Save a cotizatie anuala entry. Returns ['success' => bool, 'error' => string|null].
 */
function setari_cotizatie_anuala_save(PDO $pdo, int $id, int $anul, string $grad, string $asistent, float $valoare): array
{
    if ($anul < 1900 || $anul > 2100 || $grad === '' || $valoare < 0) {
        return ['success' => false, 'error' => 'Date invalide pentru cotizație.'];
    }
    cotizatii_ensure_tables($pdo);
    cotizatii_salveaza_anuala($pdo, $id, $anul, $grad, $asistent, $valoare);
    log_activitate($pdo, 'Setări: cotizație anuală salvată – ' . $anul . ' / ' . $grad);
    return ['success' => true, 'error' => null];
}

/**
 * Delete a cotizatie anuala entry.
 */
function setari_cotizatie_anuala_delete(PDO $pdo, int $id): array
{
    cotizatii_ensure_tables($pdo);
    if ($id > 0 && cotizatii_sterge_anuala($pdo, $id)) {
        log_activitate($pdo, 'Setări: cotizație anuală ștearsă ID ' . $id);
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Cotizația nu a putut fi ștearsă.'];
}

/**
 * Add a cotizatie exemption.
 */
function setari_scutire_add(PDO $pdo, int $membru_id, ?string $data_pana, bool $permanenta, string $motiv): array
{
    if ($membru_id <= 0) {
        return ['success' => false, 'error' => 'Membru invalid.'];
    }
    cotizatii_ensure_tables($pdo);
    cotizatii_adauga_scutire($pdo, $membru_id, $data_pana, $permanenta, $motiv);
    log_activitate($pdo, 'Setări: scutire cotizație adăugată pentru membru ID ' . $membru_id);
    return ['success' => true, 'error' => null];
}

/**
 * Update a cotizatie exemption.
 */
function setari_scutire_update(PDO $pdo, int $id, ?string $data_pana, bool $permanenta, string $motiv): array
{
    if ($id <= 0) {
        return ['success' => false, 'error' => 'Scutire invalidă.'];
    }
    cotizatii_ensure_tables($pdo);
    cotizatii_actualizeaza_scutire($pdo, $id, $data_pana, $permanenta, $motiv);
    log_activitate($pdo, 'Setări: scutire cotizație actualizată ID ' . $id);
    return ['success' => true, 'error' => null];
}

/**
 * Delete a cotizatie exemption.
 */
function setari_scutire_delete(PDO $pdo, int $id): array
{
    cotizatii_ensure_tables($pdo);
    if ($id > 0 && cotizatii_sterge_scutire($pdo, $id)) {
        log_activitate($pdo, 'Setări: scutire cotizație ștearsă ID ' . $id);
        return ['success' => true, 'error' => null];
    }
    return ['success' => false, 'error' => 'Scutirea nu a putut fi ștearsă.'];
}

// ---------------------------------------------------------------------------
// Incasari settings (delegates to incasari_helper)
// ---------------------------------------------------------------------------

/**
 * Save receipt series for incasari.
 */
function setari_incasari_serii_save(PDO $pdo, array $data): array
{
    incasari_ensure_tables($pdo);
    $nr_start_donatii = max(1, (int)($data['nr_start_donatii'] ?? 1));
    $nr_start_incasari = max(1, (int)($data['nr_start_incasari'] ?? 1));

    $nr_curent_donatii = max($nr_start_donatii, (int)($data['nr_curent_donatii'] ?? $nr_start_donatii));
    $nr_curent_incasari = max($nr_start_incasari, (int)($data['nr_curent_incasari'] ?? $nr_start_incasari));

    $nr_final_donatii = max($nr_start_donatii, (int)($data['nr_final_donatii'] ?? $nr_curent_donatii));
    $nr_final_incasari = max($nr_start_incasari, (int)($data['nr_final_incasari'] ?? $nr_curent_incasari));

    incasari_salveaza_serie(
        $pdo,
        'donatii',
        trim($data['serie_donatii'] ?? 'D'),
        $nr_start_donatii,
        $nr_curent_donatii,
        $nr_final_donatii
    );
    incasari_salveaza_serie(
        $pdo,
        'incasari',
        trim($data['serie_incasari'] ?? 'INC'),
        $nr_start_incasari,
        $nr_curent_incasari,
        $nr_final_incasari
    );
    log_activitate($pdo, 'Setări: serii chitanțe Încasări actualizate.');
    return ['success' => true, 'error' => null];
}

/**
 * Save receipt design settings.
 */
function setari_incasari_design_save(PDO $pdo, array $data): array
{
    $email_notificari_stergere = trim((string)($data['email_notificari_stergere_chitanta'] ?? ''));
    if ($email_notificari_stergere !== '' && !filter_var($email_notificari_stergere, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Adresa email pentru notificări ștergere chitanță nu este validă.'];
    }

    $upload_info_img = $data['info_suplimentare_chitanta_imagine'] ?? null;
    if (is_array($upload_info_img) && (int)($upload_info_img['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload_result = incasari_upload_info_suplimentara_image($pdo, $upload_info_img);
        if (empty($upload_result['success'])) {
            return ['success' => false, 'error' => $upload_result['error'] ?? 'Eroare la încărcarea imaginii pentru informații suplimentare.'];
        }
    }

    incasari_set_setare($pdo, 'logo_chitanta', trim((string)($data['logo_chitanta'] ?? '')));
    incasari_set_setare($pdo, 'date_asociatie', trim((string)($data['date_asociatie'] ?? '')));
    incasari_set_setare(
        $pdo,
        'dimensiune_chitanta',
        in_array((string)($data['dimensiune_chitanta'] ?? 'a5'), ['a5', 'a4'], true) ? (string)$data['dimensiune_chitanta'] : 'a5'
    );
    incasari_set_setare($pdo, 'template_chitanta', trim((string)($data['template_chitanta'] ?? 'standard')));
    incasari_set_setare($pdo, 'email_notificari_stergere_chitanta', $email_notificari_stergere);
    log_activitate($pdo, 'Setări: design chitanțe Încasări actualizat.');
    return ['success' => true, 'error' => null];
}

/**
 * Save FGO.ro API settings.
 */
function setari_incasari_fgo_save(PDO $pdo, array $data): array
{
    incasari_set_setare($pdo, 'fgo_api_key', trim($data['fgo_api_key'] ?? ''));
    incasari_set_setare($pdo, 'fgo_merchant_name', trim($data['fgo_merchant_name'] ?? ''));
    incasari_set_setare($pdo, 'fgo_merchant_tax_id', trim($data['fgo_merchant_tax_id'] ?? ''));
    incasari_set_setare($pdo, 'fgo_api_url', trim($data['fgo_api_url'] ?? ''));
    incasari_set_setare($pdo, 'fgo_mediu', in_array($data['fgo_mediu'] ?? '', ['test', 'productie']) ? $data['fgo_mediu'] : 'test');
    log_activitate($pdo, 'Setări: parametri FGO.ro API actualizați.');
    return ['success' => true, 'error' => null];
}

/**
 * Load incasari tab data (series, design, donatii list).
 */
function setari_incasari_load(PDO $pdo): array
{
    incasari_ensure_tables($pdo);
    $serie_donatii = incasari_get_serie($pdo, 'donatii') ?: [];
    $serie_incasari = incasari_get_serie($pdo, 'incasari') ?: [];

    $nr_final_donatii = incasari_get_serie_nr_final($pdo, 'donatii');
    if ($nr_final_donatii <= 0) {
        $nr_final_donatii = max((int)($serie_donatii['nr_curent'] ?? 1), (int)($serie_donatii['nr_start'] ?? 1));
    }

    $nr_final_incasari = incasari_get_serie_nr_final($pdo, 'incasari');
    if ($nr_final_incasari <= 0) {
        $nr_final_incasari = max((int)($serie_incasari['nr_curent'] ?? 1), (int)($serie_incasari['nr_start'] ?? 1));
    }

    $info_suplimentare_path = trim((string)(incasari_get_setare($pdo, 'informatii_suplimentare_chitanta_image_path') ?? ''));
    $info_suplimentare_url = $info_suplimentare_path !== '' ? '/' . ltrim($info_suplimentare_path, '/') : '';

    return [
        'serie_donatii' => array_merge($serie_donatii, ['nr_final' => $nr_final_donatii]),
        'serie_incasari' => array_merge($serie_incasari, ['nr_final' => $nr_final_incasari]),
        'donatii' => incasari_lista_donatii($pdo, 500),
        'design' => [
            'logo_chitanta' => incasari_get_setare($pdo, 'logo_chitanta') ?: (defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''),
            'date_asociatie' => incasari_get_setare($pdo, 'date_asociatie') ?: '',
            'dimensiune_chitanta' => incasari_get_setare($pdo, 'dimensiune_chitanta') ?: 'a5',
            'template_chitanta' => incasari_get_setare($pdo, 'template_chitanta') ?: 'standard',
            'email_notificari_stergere_chitanta' => incasari_get_setare($pdo, 'email_notificari_stergere_chitanta') ?: '',
            'fgo_api_key' => incasari_get_setare($pdo, 'fgo_api_key') ?: '',
            'fgo_merchant_name' => incasari_get_setare($pdo, 'fgo_merchant_name') ?: '',
            'fgo_merchant_tax_id' => incasari_get_setare($pdo, 'fgo_merchant_tax_id') ?: '',
            'fgo_api_url' => incasari_get_setare($pdo, 'fgo_api_url') ?: 'https://api.fgo.ro',
            'fgo_mediu' => incasari_get_setare($pdo, 'fgo_mediu') ?: 'test',
            'info_suplimentare_chitanta_image_path' => $info_suplimentare_path,
            'info_suplimentare_chitanta_image_url' => $info_suplimentare_url,
        ],
    ];
}

// ---------------------------------------------------------------------------
// Dashboard settings (registru interactiuni v2 subiecte)
// ---------------------------------------------------------------------------

/**
 * Add a new registru interactiuni v2 subject.
 */
function setari_subiect_v2_add(PDO $pdo, string $nume): array
{
    if (empty($nume)) {
        return ['success' => false, 'error' => 'Numele subiectului este obligatoriu.'];
    }
    ensure_registru_v2_tables($pdo);
    try {
        $r = $pdo->query('SELECT COALESCE(MAX(ordine), 0) + 1 as next_ord FROM registru_interactiuni_v2_subiecte')->fetch();
        $ord = (int)($r['next_ord'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO registru_interactiuni_v2_subiecte (nume, ordine, activ) VALUES (?, ?, 1)');
        $stmt->execute([$nume, $ord]);
        log_activitate($pdo, "registru_interactiuni_v2: Subiect adaugat: {$nume} / Modul: Setari");
        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la adăugare subiect v2: ' . $e->getMessage()];
    }
}

/**
 * Toggle active status of a registru interactiuni v2 subject.
 */
function setari_subiect_v2_toggle(PDO $pdo, int $id): array
{
    if ($id <= 0) {
        return ['success' => false, 'error' => 'ID invalid.'];
    }
    ensure_registru_v2_tables($pdo);
    try {
        $stmt = $pdo->prepare('SELECT nume, activ FROM registru_interactiuni_v2_subiecte WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $nou_activ = $row['activ'] ? 0 : 1;
            $pdo->prepare('UPDATE registru_interactiuni_v2_subiecte SET activ = ? WHERE id = ?')->execute([$nou_activ, $id]);
            $act = $nou_activ ? 'activat' : 'dezactivat';
            log_activitate($pdo, "registru_interactiuni_v2: Subiect {$row['nume']} {$act} / Modul: Setari");
            return ['success' => true, 'error' => null];
        }
        return ['success' => false, 'error' => 'Subiectul nu a fost găsit.'];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizare v2: ' . $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Import Excel (legacy — kept for backward compat)
// ---------------------------------------------------------------------------

/**
 * Process Excel file import. Returns import results or error.
 */
function setari_import_excel(PDO $pdo, array $file, array $post): array
{
    if (!isset($file) || ($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Nu s-a selectat niciun fișier sau a apărut o eroare la încărcare.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
        return ['success' => false, 'error' => 'Tipul fișierului nu este suportat. Folosiți CSV sau Excel (.xlsx, .xls).'];
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Fișierul depășește 10 MB.'];
    }

    $upload_dir = APP_ROOT . '/uploads/import/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = 'import_' . time() . '_' . uniqid() . '.' . $extension;
    $file_path = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => false, 'error' => 'Eroare la încărcarea fișierului.'];
    }

    $excel_data = citeste_fisier_excel($file_path);
    if (empty($excel_data['headers'])) {
        unlink($file_path);
        return ['success' => false, 'error' => 'Nu s-au putut citi header-urile din fișier.'];
    }

    $mapare_coloane = mapeaza_coloane($excel_data['headers']);

    // If manual mapping was submitted
    if (isset($post['mapare_coloane']) && is_array($post['mapare_coloane'])) {
        $mapare_coloane = [];
        foreach ($post['mapare_coloane'] as $index => $db_field) {
            if (!empty($db_field) && $db_field !== 'ignora') {
                $mapare_coloane[$index] = $db_field;
            }
        }
    }

    // Execute import if requested
    if (isset($post['executa_import']) && !empty($mapare_coloane)) {
        $skip_duplicates = isset($post['skip_duplicates']) ? 1 : 0;
        $import_result = importa_membri($pdo, $excel_data['rows'], $mapare_coloane, $skip_duplicates);
        unlink($file_path);

        $succes = '';
        $eroare = '';
        if ($import_result['importati'] > 0) {
            $succes = "Import reușit: {$import_result['importati']} membri importați";
            if ($import_result['skipati'] > 0) {
                $succes .= ", {$import_result['skipati']} membri săriți (duplicate)";
            }
        }
        if (!empty($import_result['eroare'])) {
            $eroare = "Erori la import: " . implode("; ", array_slice($import_result['eroare'], 0, 10));
            if (count($import_result['eroare']) > 10) {
                $eroare .= " ... și " . (count($import_result['eroare']) - 10) . " altele";
            }
        }

        return [
            'success' => true,
            'succes_msg' => $succes,
            'error' => $eroare ?: null,
            'excel_data' => null,
            'mapare_coloane' => null,
            'import_result' => $import_result,
        ];
    }

    return [
        'success' => true,
        'error' => null,
        'excel_data' => $excel_data,
        'mapare_coloane' => $mapare_coloane,
        'file_path' => $file_path,
    ];
}

// ---------------------------------------------------------------------------
// Load general settings (for view)
// ---------------------------------------------------------------------------

/**
 * Load all general settings needed for the view.
 */
function setari_load_general(PDO $pdo): array
{
    setari_ensure_table($pdo);
    $bulk = setari_get_bulk($pdo, [
        'logo_url', 'platform_name', 'email_asociatie', 'cale_libreoffice',
        'registratura_nr_pornire', 'newsletter_email', 'antet_asociatie_docx',
    ]);

    return [
        'logo_url_actual' => !empty($bulk['logo_url']) ? $bulk['logo_url'] : PLATFORM_LOGO_URL,
        'nume_platforma_actual' => !empty($bulk['platform_name']) ? $bulk['platform_name'] : PLATFORM_NAME,
        'email_asociatie' => $bulk['email_asociatie'] ?? '',
        'cale_libreoffice' => $bulk['cale_libreoffice'] ?? '',
        'registratura_nr_pornire' => (int)($bulk['registratura_nr_pornire'] ?? 1) ?: 1,
        'newsletter_email' => $bulk['newsletter_email'] ?? '',
        'antet_asociatie_docx' => $bulk['antet_asociatie_docx'] ?? '',
    ];
}
