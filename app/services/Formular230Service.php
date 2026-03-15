<?php
/**
 * Formular230Service — Business logic pentru modulul Formular 230.
 *
 * CRUD + validare + logging pentru persoanele care redirectioneaza 3.5% din impozit.
 * Nu acceseaza $_GET, $_POST, $_SESSION direct.
 * Nu genereaza HTML.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/includes/contacte_helper.php';

/**
 * Asigura existenta tabelelor necesare pentru Formular 230.
 */
function f230_ensure_tables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS formular230_persoane (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume VARCHAR(100) NOT NULL,
        initiala_tatalui VARCHAR(5) DEFAULT NULL,
        prenume VARCHAR(100) NOT NULL,
        cnp VARCHAR(13) NOT NULL,
        strada VARCHAR(255) DEFAULT NULL,
        nr VARCHAR(10) DEFAULT NULL,
        bl VARCHAR(10) DEFAULT NULL,
        sc VARCHAR(10) DEFAULT NULL,
        et VARCHAR(10) DEFAULT NULL,
        ap VARCHAR(10) DEFAULT NULL,
        localitatea VARCHAR(100) DEFAULT NULL,
        judet VARCHAR(100) DEFAULT NULL,
        cod_postal VARCHAR(10) DEFAULT NULL,
        telefon VARCHAR(50) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        ani_formular TEXT DEFAULT NULL,
        status ENUM('Activ','Inactiv') NOT NULL DEFAULT 'Activ',
        canal_tiparit TINYINT(1) NOT NULL DEFAULT 0,
        canal_online TINYINT(1) NOT NULL DEFAULT 0,
        canal_campanie TINYINT(1) NOT NULL DEFAULT 0,
        canal_altele TINYINT(1) NOT NULL DEFAULT 0,
        bifat_an_recent TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_cnp (cnp),
        INDEX idx_nume_prenume (nume, prenume),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS formular230_ani (
        id INT AUTO_INCREMENT PRIMARY KEY,
        an SMALLINT UNSIGNED NOT NULL UNIQUE,
        ordine SMALLINT UNSIGNED NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS formular230_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        utilizator VARCHAR(100) DEFAULT NULL,
        actiune TEXT NOT NULL,
        persoana_id INT DEFAULT NULL,
        INDEX idx_data_ora (data_ora),
        INDEX idx_persoana (persoana_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Scrie un rand in log-ul Formular 230.
 */
function f230_log(PDO $pdo, string $msg, ?int $persoana_id = null, ?string $utilizator = null): void {
    $util = $utilizator ?? 'system';
    $stmt = $pdo->prepare("INSERT INTO formular230_log (utilizator, actiune, persoana_id) VALUES (?, ?, ?)");
    $stmt->execute([$util, $msg, $persoana_id]);
}

/**
 * Returneaza lista de ani disponibili (descrescator).
 */
function f230_get_ani(PDO $pdo): array {
    $stmt = $pdo->query("SELECT an FROM formular230_ani ORDER BY an DESC");
    return $stmt ? array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'an') : [];
}

/**
 * Calculeaza varsta din CNP.
 */
function f230_calc_varsta_din_cnp(string $cnp): ?int {
    $cnp = preg_replace('/\D/', '', $cnp);
    if (strlen($cnp) !== 13) return null;
    $s = (int)$cnp[0];
    $aa = (int)substr($cnp, 1, 2);
    $ll = (int)substr($cnp, 3, 2);
    $zz = (int)substr($cnp, 5, 2);
    $secol = 1900;
    if ($s === 3 || $s === 4) $secol = 1800;
    elseif ($s === 5 || $s === 6) $secol = 2000;
    $an = $secol + $aa;
    if (!checkdate($ll, $zz, $an)) return null;
    $birth = DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $an, $ll, $zz));
    if (!$birth) return null;
    $today = new DateTime();
    return $today->diff($birth)->y;
}

/**
 * Sincronizeaza o persoana Formular 230 cu modulul Contacte.
 */
function f230_sync_contact(PDO $pdo, array $p): void {
    $nume = trim($p['nume'] ?? '');
    $prenume = trim($p['prenume'] ?? '');
    $cnp = trim($p['cnp'] ?? '');
    $telefon = trim($p['telefon'] ?? '');
    $email = trim($p['email'] ?? '');
    $localitatea = trim($p['localitatea'] ?? '');
    $judet = trim($p['judet'] ?? '');

    if ($nume === '' || $prenume === '') return;

    ensure_contacte_table($pdo);
    $data_n = contacte_data_nasterii_din_cnp($cnp);

    $stmt = $pdo->prepare("SELECT id FROM contacte WHERE cnp = ? AND tip_contact = 'Formular 230' LIMIT 1");
    $stmt->execute([$cnp]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE contacte
            SET nume = ?, prenume = ?, telefon = ?, email = ?, data_nasterii = ?, notite = ?, tip_contact = 'Formular 230'
            WHERE id = ?");
        $notite = trim($localitatea . ', ' . $judet);
        $stmt->execute([$nume, $prenume, $telefon ?: null, $email ?: null, $data_n, $notite ?: null, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii, notite)
            VALUES (?, ?, ?, 'Formular 230', ?, ?, ?, ?)");
        $notite = trim($localitatea . ', ' . $judet);
        $stmt->execute([$nume, $prenume, $cnp, $telefon ?: null, $email ?: null, $data_n, $notite ?: null]);
    }
}

/**
 * Valideaza datele de intrare pentru o persoana.
 * Returneaza string cu mesaj de eroare sau null daca e valid.
 */
function f230_validate(string $nume, string $prenume, string $cnp): ?string {
    if ($nume === '' || $prenume === '' || $cnp === '') {
        return 'Numele, prenumele si CNP-ul sunt obligatorii.';
    }
    if (strlen($cnp) !== 13) {
        return 'CNP-ul trebuie sa aiba 13 cifre.';
    }
    return null;
}

/**
 * Construieste array-ul de campuri pentru insert/update.
 */
function f230_build_fields(array $post): array {
    $ani_selectati = isset($post['ani_formular']) && is_array($post['ani_formular']) ? array_map('intval', $post['ani_formular']) : [];
    sort($ani_selectati);

    return [
        'nume' => trim($post['nume'] ?? ''),
        'initiala_tatalui' => trim($post['initiala_tatalui'] ?? '') ?: null,
        'prenume' => trim($post['prenume'] ?? ''),
        'cnp' => preg_replace('/\D/', '', $post['cnp'] ?? ''),
        'strada' => trim($post['strada'] ?? '') ?: null,
        'nr' => trim($post['nr'] ?? '') ?: null,
        'bl' => trim($post['bl'] ?? '') ?: null,
        'sc' => trim($post['sc'] ?? '') ?: null,
        'et' => trim($post['et'] ?? '') ?: null,
        'ap' => trim($post['ap'] ?? '') ?: null,
        'localitatea' => trim($post['localitatea'] ?? '') ?: null,
        'judet' => trim($post['judet'] ?? '') ?: null,
        'cod_postal' => trim($post['cod_postal'] ?? '') ?: null,
        'telefon' => trim($post['telefon'] ?? '') ?: null,
        'email' => trim($post['email'] ?? '') ?: null,
        'ani_formular' => $ani_selectati ? implode(',', $ani_selectati) : null,
        'status' => in_array($post['status'] ?? '', ['Activ', 'Inactiv'], true) ? $post['status'] : 'Activ',
        'canal_tiparit' => !empty($post['canal_tiparit']) ? 1 : 0,
        'canal_online' => !empty($post['canal_online']) ? 1 : 0,
        'canal_campanie' => !empty($post['canal_campanie']) ? 1 : 0,
        'canal_altele' => !empty($post['canal_altele']) ? 1 : 0,
        'bifat_an_recent' => !empty($post['bifat_an_recent']) ? 1 : 0,
    ];
}

/**
 * Creeaza o noua persoana Formular 230.
 * Returneaza ID-ul inserat.
 */
function f230_create(PDO $pdo, array $fields, string $utilizator): int {
    $cols = implode(', ', array_keys($fields));
    $place = ':' . implode(', :', array_keys($fields));
    $sql = "INSERT INTO formular230_persoane ($cols) VALUES ($place)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($fields);
    $id = (int)$pdo->lastInsertId();
    f230_log($pdo, "Adaugare persoana Formular 230: {$fields['nume']} {$fields['prenume']}", $id, $utilizator);
    return $id;
}

/**
 * Actualizeaza o persoana existenta.
 */
function f230_update(PDO $pdo, int $id, array $fields, string $utilizator): void {
    $setSql = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
    $sql = "UPDATE formular230_persoane SET $setSql WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $fields['id'] = $id;
    $stmt->execute($fields);
    f230_log($pdo, "Actualizare persoana Formular 230: {$fields['nume']} {$fields['prenume']}", $id, $utilizator);
}

/**
 * Arhiveaza o persoana (status = Inactiv).
 */
function f230_archive(PDO $pdo, int $id, string $utilizator): void {
    $stmt = $pdo->prepare("UPDATE formular230_persoane SET status = 'Inactiv' WHERE id = ?");
    $stmt->execute([$id]);
    f230_log($pdo, "Persoana Formular 230 arhivata", $id, $utilizator);
}

/**
 * Returneaza lista persoanelor + paginare.
 */
function f230_list(PDO $pdo, int $page, int $per_page, bool $hide_bifat, ?int $an_curent_form): array {
    $where = "WHERE status = 'Activ'";
    $params = [];
    if ($hide_bifat && $an_curent_form !== null) {
        $where .= " AND bifat_an_recent = 0";
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM formular230_persoane $where");
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetchColumn();
    $total_pages = max(1, (int)ceil($total / $per_page));
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $per_page;

    $limit = (int)$per_page;
    $offset_int = (int)$offset;
    $stmtList = $pdo->prepare("
        SELECT * FROM formular230_persoane
        $where
        ORDER BY nume, prenume
        LIMIT $limit OFFSET $offset_int
    ");
    $stmtList->execute($params);
    $persoane = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    return [
        'persoane' => $persoane,
        'page' => $page,
        'total_pages' => $total_pages,
        'total' => $total,
    ];
}
