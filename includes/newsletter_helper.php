<?php
/**
 * Helper modul Newsletter - trimitere emailuri către categorii de contacte
 */

require_once __DIR__ . '/contacte_helper.php';

define('NEWSLETTER_ATAŞAMENT_MAX_MB', 5);

/**
 * Asigură existența tabelelor pentru newsletter
 */
function newsletter_ensure_tables(PDO $pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subiect VARCHAR(500) NOT NULL,
        continut LONGTEXT NOT NULL,
        nume_expeditor VARCHAR(255) DEFAULT NULL,
        categoria_contacte VARCHAR(100) NOT NULL DEFAULT '',
        nr_recipienti INT NOT NULL DEFAULT 0,
        atasament_nume VARCHAR(255) DEFAULT NULL,
        atasament_path VARCHAR(500) DEFAULT NULL,
        status ENUM('draft', 'trimis', 'programat') NOT NULL DEFAULT 'draft',
        data_trimiterii DATETIME DEFAULT NULL,
        data_programata DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_data_trimiterii (data_trimiterii),
        INDEX idx_categoria (categoria_contacte)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Returnează emailul expeditorului din setări (pentru newsletter)
 */
function newsletter_get_sender_email(PDO $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'newsletter_email'");
        $stmt->execute();
        $r = $stmt->fetch();
        if ($r && !empty(trim($r['valoare'] ?? ''))) {
            return trim($r['valoare']);
        }
        // Fallback la email asociație
        $stmt = $pdo->prepare("SELECT valoare FROM setari WHERE cheie = 'email_asociatie'");
        $stmt->execute();
        $r = $stmt->fetch();
        return $r && !empty(trim($r['valoare'] ?? '')) ? trim($r['valoare']) : '';
    } catch (PDOException $e) {
        return '';
    }
}

/**
 * Returnează lista de categorii (tip_contact) din contacte care au cel puțin un email
 */
function newsletter_get_categorii_contacte(PDO $pdo) {
    ensure_contacte_table($pdo);
    $tipuri = get_contacte_tipuri();
    try {
        $stmt = $pdo->query("SELECT DISTINCT tip_contact FROM contacte WHERE (email IS NOT NULL AND email != '') OR (email_personal IS NOT NULL AND email_personal != '') ORDER BY tip_contact");
        $cats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['tip_contact'];
            $cats[$key] = $tipuri[$key] ?? $key;
        }
        return $cats;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Returnează lista de emailuri pentru o categorie (tip_contact)
 * Prioritate: email, apoi email_personal
 */
function newsletter_get_emails_by_categorie(PDO $pdo, string $categoria) {
    ensure_contacte_table($pdo);
    $stmt = $pdo->prepare("SELECT id, nume, prenume, email, email_personal FROM contacte WHERE tip_contact = ?");
    $stmt->execute([$categoria]);
    $emails = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $email = !empty(trim($row['email'] ?? '')) ? trim($row['email']) : trim($row['email_personal'] ?? '');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = [
                'email' => $email,
                'nume' => trim(($row['nume'] ?? '') . ' ' . ($row['prenume'] ?? '')),
            ];
        }
    }
    return $emails;
}

/**
 * Trimite un newsletter către lista de emailuri (HTML + atașament opțional)
 * @return array ['trimise' => int, 'eroare' => string[]]
 */
function newsletter_trimite_emails(PDO $pdo, string $from_email, string $nume_expeditor, string $subiect, string $continut_html, array $destinatari, ?string $atasament_path = null, ?string $atasament_nume = null) {
    $trimise = 0;
    $eroare = [];
    $from_header = !empty($nume_expeditor)
        ? '=?UTF-8?B?' . base64_encode($nume_expeditor) . "?= <{$from_email}>"
        : $from_email;

    $boundary = md5(uniqid());
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "From: {$from_header}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    if ($atasament_path !== null && $atasament_nume !== null && is_readable($atasament_path)) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $body .= $continut_html;
        $body .= "\r\n--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"" . basename($atasament_nume) . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . basename($atasament_nume) . "\"\r\n\r\n";
        $body .= chunk_split(base64_encode(file_get_contents($atasament_path)));
        $body .= "\r\n--{$boundary}--";
    } else {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body = $continut_html;
    }

    foreach ($destinatari as $d) {
        $to = $d['email'];
        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subiect) . '?=', $body, $headers);
        if ($ok) {
            $trimise++;
        } else {
            $eroare[] = "Eroare la trimitere către {$to}";
        }
    }
    return ['trimise' => $trimise, 'eroare' => $eroare];
}

/**
 * Salvează un draft newsletter
 */
function newsletter_salveaza_draft(PDO $pdo, array $date) {
    newsletter_ensure_tables($pdo);
    $subiect = trim($date['subiect'] ?? '');
    $continut = $date['continut'] ?? '';
    $nume_expeditor = trim($date['nume_expeditor'] ?? '');
    $categoria = $date['categoria_contacte'] ?? '';
    $atasament_nume = $date['atasament_nume'] ?? null;
    $atasament_path = $date['atasament_path'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO newsletter (subiect, continut, nume_expeditor, categoria_contacte, nr_recipienti, atasament_nume, atasament_path, status) VALUES (?, ?, ?, ?, 0, ?, ?, 'draft')");
    $stmt->execute([$subiect, $continut, $nume_expeditor ?: null, $categoria, $atasament_nume, $atasament_path]);
    return (int) $pdo->lastInsertId();
}

/**
 * Actualizează un newsletter existent (draft)
 */
function newsletter_actualizeaza_draft(PDO $pdo, int $id, array $date) {
    newsletter_ensure_tables($pdo);
    $subiect = trim($date['subiect'] ?? '');
    $continut = $date['continut'] ?? '';
    $nume_expeditor = trim($date['nume_expeditor'] ?? '');
    $categoria = $date['categoria_contacte'] ?? '';
    $atasament_nume = $date['atasament_nume'] ?? null;
    $atasament_path = $date['atasament_path'] ?? null;

    $stmt = $pdo->prepare("UPDATE newsletter SET subiect = ?, continut = ?, nume_expeditor = ?, categoria_contacte = ?, atasament_nume = ?, atasament_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'draft'");
    $stmt->execute([$subiect, $continut, $nume_expeditor ?: null, $categoria, $atasament_nume, $atasament_path, $id]);
    return $stmt->rowCount() > 0;
}

/**
 * Marchează newsletter ca trimis și salvează nr_recipienti + data_trimiterii
 */
function newsletter_marca_trimis(PDO $pdo, int $id, int $nr_recipienti) {
    $stmt = $pdo->prepare("UPDATE newsletter SET status = 'trimis', nr_recipienti = ?, data_trimiterii = NOW(), data_programata = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$nr_recipienti, $id]);
    return $stmt->rowCount() > 0;
}

/**
 * Salvează newsletter și îl trimite acum. Returnează ['id' => int, 'trimise' => int, 'eroare' => string[]]
 */
function newsletter_trimite_acum(PDO $pdo, array $date, ?array $fisier_atasament = null) {
    newsletter_ensure_tables($pdo);
    require_once __DIR__ . '/contacte_helper.php';
    ensure_contacte_table($pdo);

    $subiect = trim($date['subiect'] ?? '');
    $continut = $date['continut'] ?? '';
    $nume_expeditor = trim($date['nume_expeditor'] ?? '');
    $categoria = trim($date['categoria_contacte'] ?? '');

    if ($subiect === '' || $continut === '') {
        return ['id' => 0, 'trimise' => 0, 'eroare' => ['Subiectul și conținutul sunt obligatorii.']];
    }
    if ($categoria === '') {
        return ['id' => 0, 'trimise' => 0, 'eroare' => ['Selectați lista de contacte (categoria).']];
    }

    $from_email = newsletter_get_sender_email($pdo);
    if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        return ['id' => 0, 'trimise' => 0, 'eroare' => ['Configurați emailul expeditorului în Setări → Newsletter.']];
    }

    $destinatari = newsletter_get_emails_by_categorie($pdo, $categoria);
    if (empty($destinatari)) {
        return ['id' => 0, 'trimise' => 0, 'eroare' => ['Nu există contacte cu email valid în categoria selectată.']];
    }

    $atasament_path = null;
    $atasament_nume = null;
    if (!empty($fisier_atasament['tmp_name']) && is_uploaded_file($fisier_atasament['tmp_name'])) {
        $max_bytes = NEWSLETTER_ATAŞAMENT_MAX_MB * 1024 * 1024;
        if ($fisier_atasament['size'] > $max_bytes) {
            return ['id' => 0, 'trimise' => 0, 'eroare' => ['Atașamentul depășește ' . NEWSLETTER_ATAŞAMENT_MAX_MB . ' MB.']];
        }
        $upload_dir = __DIR__ . '/../uploads/newsletter/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = pathinfo($fisier_atasament['name'], PATHINFO_EXTENSION);
        $atasament_nume = basename($fisier_atasament['name']);
        $atasament_path = $upload_dir . 'nl_' . time() . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($fisier_atasament['tmp_name'], $atasament_path)) {
            return ['id' => 0, 'trimise' => 0, 'eroare' => ['Eroare la încărcarea atașamentului.']];
        }
    }

    // Salvare în DB (status trimis după trimitere)
    $stmt = $pdo->prepare("INSERT INTO newsletter (subiect, continut, nume_expeditor, categoria_contacte, nr_recipienti, atasament_nume, atasament_path, status, data_trimiterii) VALUES (?, ?, ?, ?, 0, ?, ?, 'draft', NULL)");
    $stmt->execute([$subiect, $continut, $nume_expeditor ?: null, $categoria, $atasament_nume, $atasament_path]);
    $id = (int) $pdo->lastInsertId();

    $rez = newsletter_trimite_emails($pdo, $from_email, $nume_expeditor, $subiect, $continut, $destinatari, $atasament_path, $atasament_nume);
    newsletter_marca_trimis($pdo, $id, $rez['trimise']);

    return [
        'id' => $id,
        'trimise' => $rez['trimise'],
        'eroare' => $rez['eroare'],
    ];
}

/**
 * Salvează newsletter pentru trimitere programată
 */
function newsletter_programeaza(PDO $pdo, array $date, string $data_programata_mysql, ?array $fisier_atasament = null) {
    newsletter_ensure_tables($pdo);

    $subiect = trim($date['subiect'] ?? '');
    $continut = $date['continut'] ?? '';
    $nume_expeditor = trim($date['nume_expeditor'] ?? '');
    $categoria = trim($date['categoria_contacte'] ?? '');

    if ($subiect === '' || $continut === '' || $categoria === '') {
        return ['id' => 0, 'eroare' => 'Completați subiectul, conținutul și categoria.'];
    }

    $atasament_path = null;
    $atasament_nume = null;
    if (!empty($fisier_atasament['tmp_name']) && is_uploaded_file($fisier_atasament['tmp_name'])) {
        $max_bytes = NEWSLETTER_ATAŞAMENT_MAX_MB * 1024 * 1024;
        if ($fisier_atasament['size'] > $max_bytes) {
            return ['id' => 0, 'eroare' => 'Atașamentul depășește ' . NEWSLETTER_ATAŞAMENT_MAX_MB . ' MB.'];
        }
        $upload_dir = __DIR__ . '/../uploads/newsletter/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = pathinfo($fisier_atasament['name'], PATHINFO_EXTENSION);
        $atasament_nume = basename($fisier_atasament['name']);
        $atasament_path = $upload_dir . 'nl_' . time() . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($fisier_atasament['tmp_name'], $atasament_path)) {
            return ['id' => 0, 'eroare' => 'Eroare la încărcarea atașamentului.'];
        }
    }

    $stmt = $pdo->prepare("INSERT INTO newsletter (subiect, continut, nume_expeditor, categoria_contacte, nr_recipienti, atasament_nume, atasament_path, status, data_programata) VALUES (?, ?, ?, ?, 0, ?, ?, 'programat', ?)");
    $stmt->execute([$subiect, $continut, $nume_expeditor ?: null, $categoria, $atasament_nume, $atasament_path, $data_programata_mysql]);
    $id = (int) $pdo->lastInsertId();
    return ['id' => $id, 'eroare' => null];
}

/**
 * Lista newsletterelor trimise (pentru afișare sub formular)
 */
function newsletter_lista_trimise(PDO $pdo, int $limit = 20) {
    newsletter_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT id, subiect, nr_recipienti, categoria_contacte, data_trimiterii, status FROM newsletter WHERE status = 'trimis' ORDER BY data_trimiterii DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Un singur newsletter după id
 */
function newsletter_get_by_id(PDO $pdo, int $id) {
    newsletter_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT * FROM newsletter WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Lista pentru rapoarte: toate newsletterele trimise cu nr contacte și categoria
 */
function newsletter_lista_rapoarte(PDO $pdo) {
    newsletter_ensure_tables($pdo);
    $stmt = $pdo->query("SELECT id, subiect, nr_recipienti, categoria_contacte, data_trimiterii FROM newsletter WHERE status = 'trimis' ORDER BY data_trimiterii DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Procesează newsletterurile programate ale căror data_programata <= NOW().
 * Este apelat de cron_newsletter.php (ex.: la fiecare minut).
 * @return array ['procesate' => int, 'trimise_total' => int, 'erori' => string[]]
 */
function newsletter_proceseaza_programate(PDO $pdo) {
    newsletter_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT id, subiect, continut, nume_expeditor, categoria_contacte, atasament_path, atasament_nume FROM newsletter WHERE status = 'programat' AND data_programata IS NOT NULL AND data_programata <= NOW() ORDER BY data_programata ASC");
    $stmt->execute();
    $programate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $procesate = 0;
    $trimise_total = 0;
    $erori = [];

    $from_email = newsletter_get_sender_email($pdo);
    if (empty($from_email) || !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        return ['procesate' => 0, 'trimise_total' => 0, 'erori' => ['Email expeditor neconfigurat în Setări → Newsletter.']];
    }

    foreach ($programate as $nl) {
        $id = (int) $nl['id'];
        $destinatari = newsletter_get_emails_by_categorie($pdo, $nl['categoria_contacte']);
        $atasament_path = null;
        $atasament_nume = null;
        if (!empty($nl['atasament_path']) && is_readable($nl['atasament_path'])) {
            $atasament_path = $nl['atasament_path'];
            $atasament_nume = $nl['atasament_nume'] ?? basename($nl['atasament_path']);
        }
        $rez = newsletter_trimite_emails(
            $pdo,
            $from_email,
            $nl['nume_expeditor'] ?? '',
            $nl['subiect'],
            $nl['continut'],
            $destinatari,
            $atasament_path,
            $atasament_nume
        );
        $nr_trimise = $rez['trimise'];
        foreach ($rez['eroare'] as $e) {
            $erori[] = "Newsletter #{$id}: {$e}";
        }
        newsletter_marca_trimis($pdo, $id, $nr_trimise);
        $procesate++;
        $trimise_total += $nr_trimise;
    }

    return ['procesate' => $procesate, 'trimise_total' => $trimise_total, 'erori' => $erori];
}
