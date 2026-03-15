<?php
/**
 * Helper modul Newsletter - trimitere emailuri către categorii de contacte
 */

require_once __DIR__ . '/contacte_helper.php';
require_once __DIR__ . '/mailer_functions.php';

define('NEWSLETTER_ATASAMENT_MAX_MB', 5);

/**
 * Asigura existenta coloanei newsletter_opt_in in tabela membri
 */
function newsletter_ensure_opt_in_column(PDO $pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM membri LIKE 'newsletter_opt_in'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE membri ADD COLUMN newsletter_opt_in TINYINT(1) DEFAULT 0");
        }
    } catch (PDOException $e) {}
}

/**
 * Asigură existența tabelelor pentru newsletter
 */
function newsletter_ensure_tables(PDO $pdo) {
    // No-op: schema is managed by install/schema/migration.php
    return;
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

    foreach ($destinatari as $d) {
        $to = $d['email'];
        $ok = sendEmailWithAttachment($pdo, $to, $subiect, $continut_html, $atasament_path, $atasament_nume);
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
        $max_bytes = NEWSLETTER_ATASAMENT_MAX_MB * 1024 * 1024;
        if ($fisier_atasament['size'] > $max_bytes) {
            return ['id' => 0, 'trimise' => 0, 'eroare' => ['Atașamentul depășește ' . NEWSLETTER_ATASAMENT_MAX_MB . ' MB.']];
        }
        $upload_dir = __DIR__ . '/../uploads/newsletter/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = strtolower(pathinfo($fisier_atasament['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'csv', 'txt', 'zip'];
        if (!in_array($ext, $allowed_ext)) {
            return ['id' => 0, 'trimise' => 0, 'eroare' => ['Tip de fisier nepermis.']];
        }
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
        $max_bytes = NEWSLETTER_ATASAMENT_MAX_MB * 1024 * 1024;
        if ($fisier_atasament['size'] > $max_bytes) {
            return ['id' => 0, 'eroare' => 'Atașamentul depășește ' . NEWSLETTER_ATASAMENT_MAX_MB . ' MB.'];
        }
        $upload_dir = __DIR__ . '/../uploads/newsletter/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = strtolower(pathinfo($fisier_atasament['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'csv', 'txt', 'zip'];
        if (!in_array($ext, $allowed_ext)) {
            return ['id' => 0, 'eroare' => 'Tip de fisier nepermis.'];
        }
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
    $limit = (int)$limit;
    $stmt = $pdo->prepare("SELECT id, subiect, nr_recipienti, categoria_contacte, data_trimiterii, status FROM newsletter WHERE status = 'trimis' ORDER BY data_trimiterii DESC LIMIT " . $limit);
    $stmt->execute([]);
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

/**
 * Toti membrii activi cu email valid
 */
function newsletter_get_membri_cu_email(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT id, nume, prenume, email FROM membri WHERE status_dosar = 'Activ' AND email IS NOT NULL AND email != '' ORDER BY nume, prenume");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Membri cu newsletter_opt_in=1 si email valid
 */
function newsletter_get_membri_opted_in(PDO $pdo) {
    newsletter_ensure_opt_in_column($pdo);
    try {
        $stmt = $pdo->query("SELECT id, nume, prenume, email FROM membri WHERE status_dosar = 'Activ' AND email IS NOT NULL AND email != '' AND newsletter_opt_in = 1 ORDER BY nume, prenume");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Liste predefinite de destinatari pentru newsletter
 * Returneaza array cu: key => ['name' => string, 'count' => int]
 */
function newsletter_get_liste_predefinite(PDO $pdo) {
    $liste = [];

    // 1. Toti membrii cu email (opted in)
    $opted_in = newsletter_get_membri_opted_in($pdo);
    $liste['membri_opted_in'] = [
        'name' => 'Membrii abonati la newsletter',
        'count' => count($opted_in),
    ];

    // 2. Toti membrii cu email (indiferent de opt-in)
    $toti = newsletter_get_membri_cu_email($pdo);
    $liste['membri_toti'] = [
        'name' => 'Toti membrii cu email',
        'count' => count($toti),
    ];

    // 3. Categorii contacte
    $categorii = newsletter_get_categorii_contacte($pdo);
    foreach ($categorii as $key => $label) {
        $emails = newsletter_get_emails_by_categorie($pdo, $key);
        $liste['contacte_' . $key] = [
            'name' => 'Contacte: ' . $label,
            'count' => count($emails),
        ];
    }

    return $liste;
}

/**
 * Lista tuturor newsletterelor (drafturi + trimise + programate) ordonate descrescator
 */
function newsletter_lista_toate(PDO $pdo, int $limit = 50) {
    newsletter_ensure_tables($pdo);
    $limit = (int)$limit;
    try {
        $stmt = $pdo->query("SELECT id, subiect, nr_recipienti, categoria_contacte, data_trimiterii, data_programata, status, created_at FROM newsletter ORDER BY COALESCE(data_trimiterii, data_programata, created_at) DESC LIMIT " . $limit);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Un singur newsletter dupa id (alias pentru newsletter_get_by_id)
 */
function newsletter_get(PDO $pdo, int $id) {
    return newsletter_get_by_id($pdo, $id);
}

/**
 * Programeaza trimiterea unui newsletter existent (draft)
 */
function newsletter_programeaza_trimitere(PDO $pdo, int $id, string $data_programata) {
    try {
        $stmt = $pdo->prepare("UPDATE newsletter SET status = 'programat', data_programata = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'draft'");
        $stmt->execute([$data_programata, $id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Obtine emailurile destinatarilor pe baza cheii listei predefinite
 * @return array [['email' => ..., 'nume' => ...], ...]
 */
function newsletter_get_destinatari_by_lista(PDO $pdo, string $lista_key) {
    if ($lista_key === 'membri_opted_in') {
        $membri = newsletter_get_membri_opted_in($pdo);
        return array_map(function($m) {
            return ['email' => $m['email'], 'nume' => trim($m['nume'] . ' ' . $m['prenume'])];
        }, $membri);
    }
    if ($lista_key === 'membri_toti') {
        $membri = newsletter_get_membri_cu_email($pdo);
        return array_map(function($m) {
            return ['email' => $m['email'], 'nume' => trim($m['nume'] . ' ' . $m['prenume'])];
        }, $membri);
    }
    // Contacte category: strip "contacte_" prefix
    if (strpos($lista_key, 'contacte_') === 0) {
        $categoria = substr($lista_key, strlen('contacte_'));
        return newsletter_get_emails_by_categorie($pdo, $categoria);
    }
    return [];
}
