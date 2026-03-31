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
        'Beneficiar'      => 'Beneficiari',
        'Companie'        => 'Companie',
        'Contact politic' => 'Contact politic',
        'Voluntar'        => 'Voluntari',
        'Donator'         => 'Donatori',
        'Sponsor'         => 'Sponsori',
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
    // No-op: schema is managed by install/schema/migration.php
    return;
}

/**
 * Verifica existenta unei coloane in tabela contacte (cache per-request).
 */
function contacte_table_has_column(PDO $pdo, string $column): bool {
    static $cache = [];
    if (array_key_exists($column, $cache)) {
        return $cache[$column];
    }
    try {
        $stmt = $pdo->prepare('SHOW COLUMNS FROM contacte LIKE ?');
        $stmt->execute([$column]);
        $cache[$column] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        return $cache[$column];
    } catch (PDOException $e) {
        $cache[$column] = false;
        return false;
    }
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
function contacte_creeaza_donator(PDO $pdo, string $nume, ?string $prenume = null, ?string $cnp = null, ?string $telefon = null, ?string $email = null, ?string $domloc = null, ?string $judet_domiciliu = null): ?int {
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

    $has_domloc = contacte_table_has_column($pdo, 'domloc');
    $has_judet = contacte_table_has_column($pdo, 'judet_domiciliu');
    $has_notite = contacte_table_has_column($pdo, 'notite');

    $coloane = ['nume', 'prenume', 'cnp', 'tip_contact', 'telefon', 'email', 'data_nasterii'];
    $params = [
        $nume,
        $prenume ? trim($prenume) : null,
        $cnp ? trim($cnp) : null,
        'Donator',
        $telefon ? trim($telefon) : null,
        $email ? trim($email) : null,
        $data_nasterii,
    ];

    if ($has_domloc) {
        $coloane[] = 'domloc';
        $params[] = $domloc ? trim($domloc) : null;
    }
    if ($has_judet) {
        $coloane[] = 'judet_domiciliu';
        $params[] = $judet_domiciliu ? trim($judet_domiciliu) : null;
    }
    if ((!$has_domloc || !$has_judet) && $has_notite) {
        $fallback_note = [];
        if (!empty($domloc)) $fallback_note[] = 'Localitate: ' . trim($domloc);
        if (!empty($judet_domiciliu)) $fallback_note[] = 'Judet: ' . trim($judet_domiciliu);
        if (!empty($fallback_note)) {
            $coloane[] = 'notite';
            $params[] = implode(', ', $fallback_note);
        }
    }

    $placeholders = implode(', ', array_fill(0, count($coloane), '?'));
    $sql = 'INSERT INTO contacte (' . implode(', ', $coloane) . ') VALUES (' . $placeholders . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$pdo->lastInsertId();
}

/**
 * Sincronizeaza membrii in modulul contacte.
 * Pentru fiecare membru: daca exista deja un contact cu acelasi CNP, il actualizeaza;
 * daca nu exista, il creeaza ca tip intern "Beneficiar" (afisat ca "Beneficiari").
 *
 * @return array ['success'=>bool, 'created'=>int, 'updated'=>int, 'error'=>string|null]
 */
function contacte_sync_membri(PDO $pdo, string $utilizator = 'Sistem'): array {
    try {
        $stmt = $pdo->query("SELECT id, nume, prenume, cnp, telefonnev, email, datanastere FROM membri WHERE status_dosar = 'Activ' ORDER BY nume, prenume");
        $membri = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['success' => false, 'created' => 0, 'updated' => 0, 'error' => 'Eroare la citirea membrilor: ' . $e->getMessage()];
    }

    $created = 0;
    $updated = 0;

    foreach ($membri as $membru) {
        $cnp = trim($membru['cnp'] ?? '');
        $nume = trim($membru['nume'] ?? '');
        $prenume = trim($membru['prenume'] ?? '');
        if ($nume === '') continue;

        $contact_existent = null;
        if ($cnp !== '') {
            $stmt = $pdo->prepare('SELECT id FROM contacte WHERE cnp = ? LIMIT 1');
            $stmt->execute([$cnp]);
            $contact_existent = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $telefon = trim($membru['telefonnev'] ?? '') ?: null;
        $email = trim($membru['email'] ?? '') ?: null;
        $data_nasterii = $membru['datanastere'] ?? null;

        if ($contact_existent) {
            $stmt = $pdo->prepare('UPDATE contacte SET nume = ?, prenume = ?, telefon = COALESCE(?, telefon), email = COALESCE(?, email), data_nasterii = COALESCE(?, data_nasterii) WHERE id = ?');
            $stmt->execute([$nume, $prenume, $telefon, $email, $data_nasterii, $contact_existent['id']]);
            $updated++;
        } else {
            $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii, referinta_contact) VALUES (?, ?, ?, 'Beneficiar', ?, ?, ?, 'Sincronizat din membri')");
            $stmt->execute([$nume, $prenume ?: null, $cnp ?: null, $telefon, $email, $data_nasterii]);
            $created++;
        }
    }

    log_activitate($pdo, "contacte: Sincronizare membri -> contacte (creati: {$created}, actualizati: {$updated})", $utilizator);

    return ['success' => true, 'created' => $created, 'updated' => $updated, 'error' => null];
}

/**
 * Sincronizeaza un singur membru in contacte (la adaugare membru nou).
 *
 * @return int|null ID-ul contactului creat/actualizat
 */
function contacte_sync_membru(PDO $pdo, array $membru, string $utilizator = 'Sistem'): ?int {
    $cnp = trim($membru['cnp'] ?? '');
    $nume = trim($membru['nume'] ?? '');
    $prenume = trim($membru['prenume'] ?? '');
    if ($nume === '') return null;

    $telefon = trim($membru['telefonnev'] ?? '') ?: null;
    $email = trim($membru['email'] ?? '') ?: null;
    $data_nasterii = $membru['datanastere'] ?? null;

    try {
        $contact_existent = null;
        if ($cnp !== '') {
            $stmt = $pdo->prepare('SELECT id FROM contacte WHERE cnp = ? LIMIT 1');
            $stmt->execute([$cnp]);
            $contact_existent = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($contact_existent) {
            $stmt = $pdo->prepare('UPDATE contacte SET nume = ?, prenume = ?, telefon = COALESCE(?, telefon), email = COALESCE(?, email), data_nasterii = COALESCE(?, data_nasterii) WHERE id = ?');
            $stmt->execute([$nume, $prenume, $telefon, $email, $data_nasterii, $contact_existent['id']]);
            return (int)$contact_existent['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii, referinta_contact) VALUES (?, ?, ?, 'Beneficiar', ?, ?, ?, 'Sincronizat din membri')");
            $stmt->execute([$nume, $prenume ?: null, $cnp ?: null, $telefon, $email, $data_nasterii]);
            return (int)$pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        error_log('contacte_sync_membru eroare: ' . $e->getMessage());
        return null;
    }
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
