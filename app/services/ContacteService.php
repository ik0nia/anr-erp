<?php
/**
 * ContacteService — Business logic pentru modulul Contacte.
 *
 * Toate operatiile CRUD + validare + logging.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/date_helper.php';
require_once APP_ROOT . '/includes/log_helper.php';

/**
 * Tipuri de contact disponibile.
 */
function contacte_tipuri(): array {
    return [
        'Institutie'      => 'Instituție',
        'Beneficiar'      => 'Beneficiar',
        'Companie'        => 'Companie',
        'Contact politic' => 'Contact politic',
        'Voluntar'        => 'Voluntar',
        'Donator'         => 'Donator',
        'Sponsor'         => 'Sponsor',
        'Partener'        => 'Parteneri',
        'Presa'           => 'Presa',
        'ANR'             => 'ANR',
        'Formular 230'    => 'Formular 230',
        'alte contacte'   => 'Alte contacte',
    ];
}

/**
 * Asigura existenta tabelului contacte + coloanele necesare.
 */
function contacte_ensure_table(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS contacte (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        prenume VARCHAR(100) DEFAULT NULL,
        cnp VARCHAR(20) DEFAULT NULL,
        companie VARCHAR(255) DEFAULT NULL,
        tip_contact VARCHAR(50) NOT NULL DEFAULT 'alte contacte',
        telefon VARCHAR(50) DEFAULT NULL,
        telefon_personal VARCHAR(50) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        email_personal VARCHAR(255) DEFAULT NULL,
        website VARCHAR(500) DEFAULT NULL,
        data_nasterii DATE DEFAULT NULL,
        notite TEXT DEFAULT NULL,
        referinta_contact VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tip_contact (tip_contact),
        INDEX idx_nume (nume),
        INDEX idx_companie (companie),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM contacte")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('cnp', $cols)) {
            $pdo->exec("ALTER TABLE contacte ADD COLUMN cnp VARCHAR(20) DEFAULT NULL AFTER prenume");
        }
    } catch (PDOException $e) { /* migrare optionala */ }
}

/**
 * Lista contacte cu filtrare, cautare si paginare.
 *
 * @return array ['contacte'=>[], 'total'=>int, 'total_pages'=>int, 'counts'=>[]]
 */
function contacte_list(PDO $pdo, string $tab, string $cautare, int $page, int $per_page): array {
    $tipuri = contacte_tipuri();
    if ($tab !== 'toate' && !isset($tipuri[$tab])) $tab = 'toate';

    $where = [];
    $params = [];

    if ($cautare !== '') {
        $term = '%' . $cautare . '%';
        $where[] = '(c.nume LIKE ? OR c.prenume LIKE ? OR c.companie LIKE ? OR c.telefon LIKE ? OR c.telefon_personal LIKE ? OR c.email LIKE ? OR c.email_personal LIKE ? OR c.notite LIKE ? OR c.referinta_contact LIKE ?)';
        for ($i = 0; $i < 9; $i++) $params[] = $term;
    }
    if ($tab !== 'toate') {
        $where[] = 'c.tip_contact = ?';
        $params[] = $tab;
    }
    $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    // Total
    $stmt = $pdo->prepare("SELECT COUNT(*) as n FROM contacte c" . $where_sql);
    $stmt->execute($params);
    $total = (int) $stmt->fetch()['n'];

    // Contacte paginate
    $offset = ($page - 1) * $per_page;
    $stmt = $pdo->prepare("SELECT * FROM contacte c" . $where_sql . " ORDER BY c.nume, c.prenume LIMIT " . (int)$per_page . " OFFSET " . (int)$offset);
    $stmt->execute($params);
    $contacte = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Numar per tip (pentru taburi)
    $counts = ['toate' => 0];
    $stmt = $pdo->query('SELECT COUNT(*) as n FROM contacte');
    $counts['toate'] = (int) $stmt->fetch()['n'];
    foreach ($tipuri as $k => $v) {
        $stmt = $pdo->prepare('SELECT COUNT(*) as n FROM contacte WHERE tip_contact = ?');
        $stmt->execute([$k]);
        $counts[$k] = (int) $stmt->fetch()['n'];
    }

    $total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;

    return [
        'contacte'    => $contacte,
        'total'       => $total,
        'total_pages' => $total_pages,
        'counts'      => $counts,
        'tipuri'      => $tipuri,
    ];
}

/**
 * Preia un contact dupa ID.
 */
function contacte_get(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare('SELECT * FROM contacte WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Creeaza un contact nou.
 *
 * @return array ['success'=>bool, 'id'=>int|null, 'error'=>string|null]
 */
function contacte_create(PDO $pdo, array $data, string $utilizator = 'Sistem'): array {
    $nume = trim($data['nume'] ?? '');
    if ($nume === '') {
        return ['success' => false, 'error' => 'Numele este obligatoriu.', 'id' => null];
    }

    $tipuri_valide = array_keys(contacte_tipuri());
    $tip = $data['tip_contact'] ?? 'alte contacte';
    if (!in_array($tip, $tipuri_valide)) $tip = 'alte contacte';

    $prenume = trim($data['prenume'] ?? '') ?: null;
    $companie = trim($data['companie'] ?? '') ?: null;
    $telefon = trim($data['telefon'] ?? '') ?: null;
    $telefon_personal = trim($data['telefon_personal'] ?? '') ?: null;
    $email = trim($data['email'] ?? '') ?: null;
    $email_personal = trim($data['email_personal'] ?? '') ?: null;
    $website = trim($data['website'] ?? '') ?: null;
    $notite = trim($data['notite'] ?? '') ?: null;
    $referinta = trim($data['referinta_contact'] ?? '') ?: null;

    $data_nasterii = trim($data['data_nasterii'] ?? '');
    $data_nasterii = $data_nasterii !== '' ? parse_date_to_ymd($data_nasterii, ['Y-m-d', 'd.m.Y']) : null;

    try {
        $stmt = $pdo->prepare('INSERT INTO contacte (nume, prenume, companie, tip_contact, telefon, telefon_personal, email, email_personal, website, data_nasterii, notite, referinta_contact) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([$nume, $prenume, $companie, $tip, $telefon, $telefon_personal, $email, $email_personal, $website, $data_nasterii, $notite, $referinta]);
        $id = (int)$pdo->lastInsertId();

        log_activitate($pdo, 'contacte: Adăugat contact ' . trim($nume . ' ' . ($prenume ?? '')), $utilizator);

        return ['success' => true, 'id' => $id, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'id' => null, 'error' => 'Eroare la salvare.'];
    }
}

/**
 * Actualizeaza un contact existent. Loggheaza modificarile.
 *
 * @return array ['success'=>bool, 'error'=>string|null]
 */
function contacte_update(PDO $pdo, int $id, array $data, string $utilizator = 'Sistem'): array {
    $nume = trim($data['nume'] ?? '');
    if ($nume === '') {
        return ['success' => false, 'error' => 'Numele este obligatoriu.'];
    }

    $tipuri_valide = array_keys(contacte_tipuri());
    $tip = $data['tip_contact'] ?? 'alte contacte';
    if (!in_array($tip, $tipuri_valide)) $tip = 'alte contacte';

    $prenume = trim($data['prenume'] ?? '') ?: null;
    $companie = trim($data['companie'] ?? '') ?: null;
    $telefon = trim($data['telefon'] ?? '') ?: null;
    $telefon_personal = trim($data['telefon_personal'] ?? '') ?: null;
    $email = trim($data['email'] ?? '') ?: null;
    $email_personal = trim($data['email_personal'] ?? '') ?: null;
    $website = trim($data['website'] ?? '') ?: null;
    $notite = trim($data['notite'] ?? '') ?: null;
    $referinta = trim($data['referinta_contact'] ?? '') ?: null;

    $data_nasterii = trim($data['data_nasterii'] ?? '');
    $data_nasterii = $data_nasterii !== '' ? parse_date_to_ymd($data_nasterii, ['Y-m-d', 'd.m.Y']) : null;

    try {
        // Date vechi pentru log
        $contact_vechi = contacte_get($pdo, $id);
        if (!$contact_vechi) {
            return ['success' => false, 'error' => 'Contactul nu a fost găsit.'];
        }

        $stmt = $pdo->prepare('UPDATE contacte SET nume=?, prenume=?, companie=?, tip_contact=?, telefon=?, telefon_personal=?, email=?, email_personal=?, website=?, data_nasterii=?, notite=?, referinta_contact=? WHERE id=?');
        $stmt->execute([$nume, $prenume, $companie, $tip, $telefon, $telefon_personal, $email, $email_personal, $website, $data_nasterii, $notite, $referinta, $id]);

        // Log modificari
        $nume_complet = trim($nume . ' ' . ($prenume ?? ''));
        $campuri_log = ['telefon' => 'Numar de telefon', 'telefon_personal' => 'Telefon personal', 'email' => 'Email', 'email_personal' => 'Email personal'];
        $modificari = [];
        foreach ($campuri_log as $camp => $label) {
            $vechi = $contact_vechi[$camp] ?? '';
            $nou = ${$camp} ?? '';
            if ($vechi !== $nou) {
                $modificari[] = log_format_modificare($label, $vechi, $nou);
            }
        }

        if (!empty($modificari)) {
            log_activitate($pdo, "contacte: " . implode("; ", $modificari) . " / {$nume_complet}", $utilizator);
        } else {
            log_activitate($pdo, "contacte: Modificat contact {$nume_complet}", $utilizator);
        }

        return ['success' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la actualizare.'];
    }
}

/**
 * Sterge un contact dupa ID.
 *
 * @return array ['success'=>bool, 'nume'=>string|null]
 */
function contacte_delete(PDO $pdo, int $id, string $utilizator = 'Sistem'): array {
    $contact = contacte_get($pdo, $id);
    if (!$contact) {
        return ['success' => false, 'nume' => null];
    }

    $pdo->prepare('DELETE FROM contacte WHERE id = ?')->execute([$id]);
    $nume = trim(($contact['nume'] ?? '') . ' ' . ($contact['prenume'] ?? ''));
    log_activitate($pdo, 'contacte: Șters contact ' . $nume, $utilizator);

    return ['success' => true, 'nume' => $nume];
}

/**
 * Formateaza telefon pentru link WhatsApp.
 */
function contacte_whatsapp(string $telefon): ?string {
    if (empty($telefon)) return null;
    $nr = preg_replace('/\D/', '', $telefon);
    if (empty($nr)) return null;
    if (substr($nr, 0, 1) === '0') $nr = '4' . $nr;
    if (strlen($nr) === 10 && $nr[0] === '7') $nr = '4' . $nr;
    return 'https://wa.me/' . $nr;
}

/**
 * Creeaza un contact donator (tip Donator). Din CNP se extrage data nasterii.
 */
function contacte_creeaza_donator(PDO $pdo, string $nume, ?string $prenume = null, ?string $cnp = null, ?string $telefon = null, ?string $email = null): ?int {
    contacte_ensure_table($pdo);
    $nume = trim($nume);
    if ($nume === '') return null;

    $data_nasterii = null;
    if ($cnp) {
        $cnp_clean = preg_replace('/\D/', '', $cnp);
        if (strlen($cnp_clean) === 13 && ctype_digit($cnp_clean)) {
            $s = (int)$cnp_clean[0];
            $aa = (int)substr($cnp_clean, 1, 2);
            $ll = (int)substr($cnp_clean, 3, 2);
            $zz = (int)substr($cnp_clean, 5, 2);
            $secol = 1900;
            if ($s === 3 || $s === 4) $secol = 1800;
            elseif ($s === 5 || $s === 6) $secol = 2000;
            $an = $secol + $aa;
            if (checkdate($ll, $zz, $an)) {
                $data_nasterii = sprintf('%04d-%02d-%02d', $an, $ll, $zz);
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii) VALUES (?, ?, ?, 'Donator', ?, ?, ?)");
    $stmt->execute([
        $nume,
        $prenume ? trim($prenume) : null,
        $cnp ? trim($cnp) : null,
        $telefon ? trim($telefon) : null,
        $email ? trim($email) : null,
        $data_nasterii,
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * Mapare standard coloane Excel -> campuri contacte (pentru import).
 */
function contacte_mapare_import(): array {
    return [
        'Nume' => 'nume',
        'Prenume' => 'prenume',
        'Companie' => 'companie',
        'Compania' => 'companie',
        'Tip contact' => 'tip_contact',
        'Telefon' => 'telefon',
        'Telefon mobil' => 'telefon',
        'Telefon personal' => 'telefon_personal',
        'Email' => 'email',
        'Email personal' => 'email_personal',
        'Website' => 'website',
        'Data nasterii' => 'data_nasterii',
        'Data nașterii' => 'data_nasterii',
        'Notite' => 'notite',
        'Referinta' => 'referinta_contact',
        'Contact comun' => 'referinta_contact',
    ];
}
