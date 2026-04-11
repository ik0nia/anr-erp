<?php
/**
 * NotificariService — Business logic pentru modulul Notificari.
 *
 * Toate operatiile CRUD + validare + logging.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/log_helper.php';

if (!defined('NOTIFICARI_IMPORTANTE')) {
    define('NOTIFICARI_IMPORTANTE', ['Normal' => 'Normal', 'Important' => 'Important', 'Informativ' => 'Informativ']);
}
if (!defined('NOTIFICARI_ATAŞAMENT_MAX_MB')) {
    define('NOTIFICARI_ATAŞAMENT_MAX_MB', 15);
}

/**
 * Asigura tabelele notificari si notificari_utilizatori.
 */
function notificari_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificari (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titlu VARCHAR(500) NOT NULL,
        importanta ENUM('Normal', 'Important', 'Informativ') NOT NULL DEFAULT 'Normal',
        continut TEXT NOT NULL,
        link_extern VARCHAR(2000) DEFAULT NULL,
        atasament_nume VARCHAR(255) DEFAULT NULL,
        atasament_path VARCHAR(500) DEFAULT NULL,
        trimite_email TINYINT(1) NOT NULL DEFAULT 0,
        target_scope ENUM('all', 'user') NOT NULL DEFAULT 'all',
        target_user_id INT DEFAULT NULL,
        highlighted_by_comment TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_importanta (importanta),
        INDEX idx_target_user_id (target_user_id),
        INDEX idx_highlighted_by_comment (highlighted_by_comment)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS notificari_utilizatori (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notificare_id INT NOT NULL,
        utilizator_id INT NOT NULL,
        status ENUM('nou', 'citit', 'arhivat') NOT NULL DEFAULT 'nou',
        citit_la DATETIME DEFAULT NULL,
        arhivat_la DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_notif_user (notificare_id, utilizator_id),
        INDEX idx_utilizator_status (utilizator_id, status),
        INDEX idx_notificare (notificare_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notificari_comentarii (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notificare_id INT NOT NULL,
        utilizator_id INT NOT NULL,
        comentariu TEXT NOT NULL,
        notifica_toti TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notif_created (notificare_id, created_at),
        INDEX idx_notif_user (notificare_id, utilizator_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Migrations for existing installations.
    $cols = $pdo->query("SHOW COLUMNS FROM notificari")->fetchAll(PDO::FETCH_ASSOC);
    $existingCols = [];
    foreach ($cols as $c) {
        if (!empty($c['Field'])) {
            $existingCols[(string)$c['Field']] = true;
        }
    }
    if (!isset($existingCols['target_scope'])) {
        $pdo->exec("ALTER TABLE notificari ADD COLUMN target_scope ENUM('all', 'user') NOT NULL DEFAULT 'all' AFTER trimite_email");
    }
    if (!isset($existingCols['target_user_id'])) {
        $pdo->exec("ALTER TABLE notificari ADD COLUMN target_user_id INT DEFAULT NULL AFTER target_scope");
    }
    if (!isset($existingCols['highlighted_by_comment'])) {
        $pdo->exec("ALTER TABLE notificari ADD COLUMN highlighted_by_comment TINYINT(1) NOT NULL DEFAULT 0 AFTER target_user_id");
    }
    $idxRows = $pdo->query("SHOW INDEX FROM notificari")->fetchAll(PDO::FETCH_ASSOC);
    $idxNames = [];
    foreach ($idxRows as $idx) {
        if (!empty($idx['Key_name'])) {
            $idxNames[(string)$idx['Key_name']] = true;
        }
    }
    if (!isset($idxNames['idx_target_user_id'])) {
        $pdo->exec("ALTER TABLE notificari ADD INDEX idx_target_user_id (target_user_id)");
    }
    if (!isset($idxNames['idx_highlighted_by_comment'])) {
        $pdo->exec("ALTER TABLE notificari ADD INDEX idx_highlighted_by_comment (highlighted_by_comment)");
    }
}

function notificari_lista_utilizatori_activi(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, COALESCE(NULLIF(TRIM(nume_complet), ''), NULLIF(TRIM(username), ''), email, CONCAT('Utilizator #', id)) AS eticheta FROM utilizatori WHERE activ = 1 ORDER BY eticheta ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Numar notificari necitite pentru un utilizator.
 */
function notificari_count_necitate(PDO $pdo, int $utilizator_id): int {
    notificari_ensure_tables($pdo);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificari_utilizatori WHERE utilizator_id = ? AND status = 'nou'");
    $stmt->execute([$utilizator_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returneaza toti utilizatorii platformei (id, email, nume_complet) - pentru trimitere email.
 */
function notificari_get_utilizatori_email(PDO $pdo): array {
    try {
        $stmt = $pdo->query("SELECT id, email, nume_complet FROM utilizatori WHERE activ = 1 AND email IS NOT NULL AND email != ''");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Salveaza o notificare si creeaza inregistrari notificari_utilizatori pentru fiecare utilizator (status nou).
 * Daca trimite_email=1, trimite email tuturor utilizatorilor.
 *
 * @return int id notificare sau 0 la eroare
 */
function notificari_adauga(PDO $pdo, array $date, ?array $fisier_atasament = null, ?int $created_by = null): int {
    notificari_ensure_tables($pdo);
    $titlu = trim($date['titlu'] ?? '');
    $importanta = in_array($date['importanta'] ?? '', array_keys(NOTIFICARI_IMPORTANTE)) ? $date['importanta'] : 'Normal';
    $continut = trim($date['continut'] ?? '');
    $link_extern = trim($date['link_extern'] ?? '');
    $trimite_email = !empty($date['trimite_email']) ? 1 : 0;
    $target_scope = (($date['target_scope'] ?? 'all') === 'user') ? 'user' : 'all';
    $target_user_id = (int)($date['target_user_id'] ?? 0);

    if ($titlu === '') {
        return 0;
    }
    if ($target_scope === 'user' && $target_user_id <= 0) {
        return 0;
    }

    $atasament_nume = null;
    $atasament_path = null;
    if (!empty($fisier_atasament['tmp_name']) && is_uploaded_file($fisier_atasament['tmp_name'])) {
        $max_bytes = NOTIFICARI_ATAŞAMENT_MAX_MB * 1024 * 1024;
        if ($fisier_atasament['size'] > $max_bytes) {
            return 0;
        }
        $upload_dir = APP_ROOT . '/uploads/notificari/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = pathinfo($fisier_atasament['name'], PATHINFO_EXTENSION);
        $atasament_nume = basename($fisier_atasament['name']);
        $atasament_path = $upload_dir . 'notif_' . time() . '_' . uniqid() . '.' . $ext;
        if (!move_uploaded_file($fisier_atasament['tmp_name'], $atasament_path)) {
            return 0;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO notificari (titlu, importanta, continut, link_extern, atasament_nume, atasament_path, trimite_email, target_scope, target_user_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titlu, $importanta, $continut, $link_extern ?: null, $atasament_nume, $atasament_path, $trimite_email, $target_scope, ($target_scope === 'user' ? $target_user_id : null), $created_by]);
    $notif_id = (int) $pdo->lastInsertId();
    if ($notif_id <= 0) {
        return 0;
    }

    $ins = $pdo->prepare("INSERT INTO notificari_utilizatori (notificare_id, utilizator_id, status) VALUES (?, ?, 'nou')");
    if ($target_scope === 'user') {
        $ins->execute([$notif_id, $target_user_id]);
    } else {
        $stmt_u = $pdo->query("SELECT id FROM utilizatori WHERE activ = 1");
        while ($row = $stmt_u->fetch(PDO::FETCH_ASSOC)) {
            $ins->execute([$notif_id, (int) $row['id']]);
        }
    }

    if ($trimite_email) {
        if ($target_scope === 'user') {
            notificari_trimite_email_target($pdo, $notif_id, $titlu, $continut, $link_extern, $target_user_id, $atasament_path, $atasament_nume);
        } else {
            notificari_trimite_email_utilizatori($pdo, $notif_id, $titlu, $continut, $link_extern, $atasament_path, $atasament_nume);
        }
    }
    return $notif_id;
}

function notificari_trimite_email_target(PDO $pdo, int $notificare_id, string $titlu, string $continut, string $link_extern, int $target_user_id, ?string $atasament_path = null, ?string $atasament_nume = null): void {
    if ($target_user_id <= 0) {
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT email FROM utilizatori WHERE id = ? AND activ = 1");
        $stmt->execute([$target_user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $to = trim((string)($row['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }
    } catch (PDOException $e) {
        return;
    }

    $base_url = defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '';
    $link_notificare = rtrim($base_url, '/') . '/notificari/view?id=' . $notificare_id;
    $corp_text = $continut . "\n\n";
    if ($link_extern !== '') {
        $corp_text .= "Link extern: " . $link_extern . "\n\n";
    }
    $corp_text .= "Vizualizează notificarea pe platformă: " . $link_notificare;

    $subiect = 'Notificare ANR Bihor: ' . $titlu;
    $boundary = md5(uniqid());
    $headers = "MIME-Version: 1.0\r\n";
    if ($atasament_path !== null && $atasament_nume !== null && is_readable($atasament_path)) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $corp_text;
        $body .= "\r\n--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"" . basename($atasament_nume) . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . basename($atasament_nume) . "\"\r\n\r\n";
        $body .= chunk_split(base64_encode(file_get_contents($atasament_path)));
        $body .= "\r\n--{$boundary}--";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body = $corp_text;
    }
    @mail($to, '=?UTF-8?B?' . base64_encode($subiect) . '?=', $body, $headers);
}

/**
 * Trimite email catre toti utilizatorii: subiect "Notificare ANR Bihor: [titlu]", corp: continut + link, atasament.
 */
function notificari_trimite_email_utilizatori(PDO $pdo, int $notificare_id, string $titlu, string $continut, string $link_extern, ?string $atasament_path = null, ?string $atasament_nume = null): void {
    $utilizatori = notificari_get_utilizatori_email($pdo);
    if (empty($utilizatori)) {
        return;
    }
    $base_url = defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '';
    $link_notificare = rtrim($base_url, '/') . '/notificari/view?id=' . $notificare_id;
    $corp_text = $continut . "\n\n";
    if ($link_extern !== '') {
        $corp_text .= "Link extern: " . $link_extern . "\n\n";
    }
    $corp_text .= "Vizualizează notificarea pe platformă: " . $link_notificare;

    $subiect = 'Notificare ANR Bihor: ' . $titlu;
    $boundary = md5(uniqid());
    $headers = "MIME-Version: 1.0\r\n";
    if ($atasament_path !== null && $atasament_nume !== null && is_readable($atasament_path)) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $corp_text;
        $body .= "\r\n--{$boundary}\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"" . basename($atasament_nume) . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"" . basename($atasament_nume) . "\"\r\n\r\n";
        $body .= chunk_split(base64_encode(file_get_contents($atasament_path)));
        $body .= "\r\n--{$boundary}--";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body = $corp_text;
    }

    foreach ($utilizatori as $u) {
        $to = trim($u['email']);
        if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            @mail($to, '=?UTF-8?B?' . base64_encode($subiect) . '?=', $body, $headers);
        }
    }
}

/**
 * Lista notificari pentru utilizatorul curent (cu excludere arhivate daca nu se cere istoric).
 */
function notificari_lista_pentru_utilizator(PDO $pdo, int $utilizator_id, string $ordine = 'a-z', bool $istoric = false): array {
    notificari_ensure_tables($pdo);
    $status_cond = $istoric ? '' : " AND nu.status != 'arhivat'";
    $ord = ($ordine === 'z-a') ? 'DESC' : 'ASC';
    $sql = "SELECT n.id, n.titlu, n.importanta, n.created_at, nu.status, nu.citit_la, n.highlighted_by_comment
            FROM notificari n
            INNER JOIN notificari_utilizatori nu ON nu.notificare_id = n.id AND nu.utilizator_id = ?
            WHERE 1=1 {$status_cond}
            ORDER BY
                CASE
                    WHEN nu.status = 'nou' AND nu.citit_la IS NULL THEN 1
                    WHEN nu.status = 'nou' AND nu.citit_la IS NOT NULL THEN 2
                    WHEN nu.status = 'citit' THEN 3
                    ELSE 4
                END,
                n.created_at {$ord}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$utilizator_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * O singura notificare dupa id (pentru utilizatorul curent).
 */
function notificari_get_by_id(PDO $pdo, int $id, ?int $utilizator_id = null): ?array {
    notificari_ensure_tables($pdo);
    if (($utilizator_id ?? 0) > 0) {
        $stmt = $pdo->prepare("SELECT n.*, nu.status as user_status, u.username as creator_username, u.nume_complet as creator_nume,
                                      t.nume_complet AS target_nume_complet, t.username AS target_username
                               FROM notificari n
                               INNER JOIN notificari_utilizatori nu ON nu.notificare_id = n.id AND nu.utilizator_id = ?
                               LEFT JOIN utilizatori u ON u.id = n.created_by
                               LEFT JOIN utilizatori t ON t.id = n.target_user_id
                               WHERE n.id = ?");
        $stmt->execute([$utilizator_id, $id]);
    } else {
        $stmt = $pdo->prepare("SELECT n.*, NULL as user_status, u.username as creator_username, u.nume_complet as creator_nume,
                                      t.nume_complet AS target_nume_complet, t.username AS target_username
                               FROM notificari n
                               LEFT JOIN utilizatori u ON u.id = n.created_by
                               LEFT JOIN utilizatori t ON t.id = n.target_user_id
                               WHERE n.id = ?");
        $stmt->execute([$id]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function notificari_comentarii_lista(PDO $pdo, int $notificare_id): array {
    notificari_ensure_tables($pdo);
    if ($notificare_id <= 0) {
        return [];
    }
    try {
        $stmt = $pdo->prepare("SELECT c.id, c.notificare_id, c.utilizator_id, c.comentariu, c.notifica_toti, c.created_at,
                                      COALESCE(NULLIF(TRIM(u.nume_complet), ''), NULLIF(TRIM(u.username), ''), u.email, CONCAT('Utilizator #', c.utilizator_id)) AS utilizator_nume
                               FROM notificari_comentarii c
                               LEFT JOIN utilizatori u ON u.id = c.utilizator_id
                               WHERE c.notificare_id = ?
                               ORDER BY c.created_at ASC, c.id ASC");
        $stmt->execute([$notificare_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function notificari_comentariu_adauga(PDO $pdo, int $notificare_id, int $utilizator_id, string $comentariu, bool $notifica_toti = false): int {
    notificari_ensure_tables($pdo);
    $comentariu = trim($comentariu);
    if ($notificare_id <= 0 || $utilizator_id <= 0 || $comentariu === '' || mb_strlen($comentariu) > 5000) {
        return 0;
    }

    $stmtN = $pdo->prepare("SELECT id, titlu FROM notificari WHERE id = ?");
    $stmtN->execute([$notificare_id]);
    $notif = $stmtN->fetch(PDO::FETCH_ASSOC);
    if (!$notif) {
        return 0;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO notificari_comentarii (notificare_id, utilizator_id, comentariu, notifica_toti) VALUES (?, ?, ?, ?)");
        $stmt->execute([$notificare_id, $utilizator_id, $comentariu, $notifica_toti ? 1 : 0]);
        $comentariu_id = (int)$pdo->lastInsertId();
        if ($comentariu_id <= 0) {
            return 0;
        }

        if ($notifica_toti) {
            $pdo->prepare("UPDATE notificari SET highlighted_by_comment = 1 WHERE id = ?")->execute([$notificare_id]);

            $autor = 'Utilizator';
            try {
                $stmtAutor = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(nume_complet), ''), NULLIF(TRIM(username), ''), email, CONCAT('Utilizator #', id)) AS nume FROM utilizatori WHERE id = ?");
                $stmtAutor->execute([$utilizator_id]);
                $autorRow = $stmtAutor->fetch(PDO::FETCH_ASSOC);
                if (!empty($autorRow['nume'])) {
                    $autor = (string)$autorRow['nume'];
                }
            } catch (PDOException $e) {
                // Keep default fallback label.
            }

            $base_url = defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '';
            $link_notificare = rtrim($base_url, '/') . '/notificari/view?id=' . $notificare_id;
            $continut_broadcast = $autor . " a adaugat un comentariu la notificarea: \"" . (string)$notif['titlu'] . "\".\n\n"
                . "Notificarea comentata este evidentiata. O poti deschide aici: " . $link_notificare;
            notificari_adauga($pdo, [
                'titlu' => 'Comentariu nou la notificarea #' . $notificare_id,
                'importanta' => 'Informativ',
                'continut' => $continut_broadcast,
                'link_extern' => $link_notificare,
                'trimite_email' => 0,
                'target_scope' => 'all',
            ], null, $utilizator_id);
        }

        return $comentariu_id;
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Marcheaza notificarea ca citita pentru utilizator.
 */
function notificari_marcheaza_citita(PDO $pdo, int $notificare_id, int $utilizator_id): bool {
    $stmt = $pdo->prepare("UPDATE notificari_utilizatori SET status = 'citit', citit_la = NOW() WHERE notificare_id = ? AND utilizator_id = ? AND status = 'nou'");
    $stmt->execute([$notificare_id, $utilizator_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Arhiveaza notificarea pentru utilizator.
 */
function notificari_arhiveaza(PDO $pdo, int $notificare_id, int $utilizator_id): bool {
    $stmt = $pdo->prepare("UPDATE notificari_utilizatori SET status = 'arhivat', arhivat_la = NOW() WHERE notificare_id = ? AND utilizator_id = ?");
    $stmt->execute([$notificare_id, $utilizator_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Marcheaza notificarea ca necitita pentru utilizator (status = 'nou').
 */
function notificari_marcheaza_necitita(PDO $pdo, int $notificare_id, int $utilizator_id): bool {
    $stmt = $pdo->prepare("UPDATE notificari_utilizatori SET status = 'nou', citit_la = NULL WHERE notificare_id = ? AND utilizator_id = ?");
    $stmt->execute([$notificare_id, $utilizator_id]);
    return $stmt->rowCount() > 0;
}

/**
 * Creeaza un task din notificare (nume = titlu notificare, detalii = link catre notificare).
 */
function notificari_adauga_la_taskuri(PDO $pdo, int $notificare_id, int $utilizator_id): ?int {
    $notif = notificari_get_by_id($pdo, $notificare_id, $utilizator_id);
    if (!$notif) {
        return null;
    }
    $base_url = defined('PLATFORM_BASE_URL') ? PLATFORM_BASE_URL : '';
    $link = rtrim($base_url, '/') . '/notificari/view?id=' . $notificare_id;
    $nume = $notif['titlu'];
    $detalii = "Notificare platformă.\nLink: " . $link;
    try {
        $stmt = $pdo->prepare("INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$nume, $detalii, $notif['importanta'] === 'Important' ? 'important' : 'normal']);
        return (int) $pdo->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}
