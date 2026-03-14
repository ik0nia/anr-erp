<?php
/**
 * Helper modul Contacte - tabel, tipuri, import
 */

define('CONTACTE_TIPURI', [
    'Institutie' => 'Instituție',
    'Beneficiar' => 'Beneficiar',
    'Companie' => 'Companie',
    'Contact politic' => 'Contact politic',
    'Voluntar' => 'Voluntar',
    'Donator' => 'Donator',
    'Sponsor' => 'Sponsor',
    'Partener' => 'Parteneri',
    'Presa' => 'Presa',
    'ANR' => 'ANR',
    'Formular 230' => 'Formular 230',
    'alte contacte' => 'Alte contacte',
]);

/**
 * Asigură existența tabelului contacte
 */
function ensure_contacte_table(PDO $pdo) {
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
    } catch (PDOException $e) { /* migrare opțională */ }
}

/**
 * Returnează lista de tipuri de contact
 */
function get_contacte_tipuri() {
    return CONTACTE_TIPURI;
}

/**
 * Extrage data nașterii din CNP (format YYYY-MM-DD sau null dacă invalid).
 */
function contacte_data_nasterii_din_cnp($cnp) {
    $cnp = preg_replace('/\D/', '', (string)$cnp);
    if (strlen($cnp) !== 13 || !ctype_digit($cnp)) return null;
    $s = (int)$cnp[0];
    if ($s < 1 || $s > 9) return null;
    $aa = (int)substr($cnp, 1, 2);
    $ll = (int)substr($cnp, 3, 2);
    $zz = (int)substr($cnp, 5, 2);
    $secol = 1900;
    if ($s === 3 || $s === 4) $secol = 1800;
    elseif ($s === 5 || $s === 6) $secol = 2000;
    $an = $secol + $aa;
    if (!checkdate($ll, $zz, $an)) return null;
    return sprintf('%04d-%02d-%02d', $an, $ll, $zz);
}

/**
 * Creează un contact donator (tip Donator). Din CNP se extrage data nașterii.
 * Returnează id-ul contactului.
 */
function contacte_creare_donator(PDO $pdo, $nume, $prenume = null, $cnp = null, $telefon = null, $email = null) {
    ensure_contacte_table($pdo);
    $nume = trim((string)$nume);
    if ($nume === '') return null;
    $prenume = trim((string)$prenume) ?: null;
    $cnp = trim((string)$cnp) ?: null;
    $telefon = trim((string)$telefon) ?: null;
    $email = trim((string)$email) ?: null;
    $data_nasterii = $cnp ? contacte_data_nasterii_din_cnp($cnp) : null;
    $stmt = $pdo->prepare("INSERT INTO contacte (nume, prenume, cnp, tip_contact, telefon, email, data_nasterii) VALUES (?, ?, ?, 'Donator', ?, ?, ?)");
    $stmt->execute([$nume, $prenume, $cnp, $telefon, $email, $data_nasterii]);
    return (int)$pdo->lastInsertId();
}

/**
 * Formatează telefon pentru link WhatsApp (doar cifre)
 */
function contacte_whatsapp_url($telefon) {
    if (empty($telefon)) return null;
    $nr = preg_replace('/\D/', '', $telefon);
    if (empty($nr)) return null;
    if (substr($nr, 0, 1) === '0') $nr = '4' . $nr; // Ro
    if (strlen($nr) === 10 && $nr[0] === '7') $nr = '4' . $nr;
    return 'https://wa.me/' . $nr;
}

/**
 * Link WhatsApp cu mesaj predefinit (pentru aniversări etc.)
 * @param string $telefon Număr telefon
 * @param string $mesaj Text predefinit (va fi url-encodat)
 * @return string|null URL sau null dacă lipsește telefonul
 */
function contacte_whatsapp_url_cu_mesaj($telefon, $mesaj = '') {
    $url = contacte_whatsapp_url($telefon);
    if (!$url) return null;
    if ($mesaj !== '') {
        $url .= '?text=' . rawurlencode($mesaj);
    }
    return $url;
}

/**
 * Mapare standard coloane Excel -> câmpuri contacte
 */
function contacte_mapare_coloane() {
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

/**
 * Importă contacte din rânduri mapate
 * @param array $headers Headere din Excel (pt. a accesa row[$headers[$idx]])
 * @param array $mapare index => db_field
 */
function importa_contacte(PDO $pdo, array $headers, array $rows, array $mapare) {
    require_once __DIR__ . '/log_helper.php';
    ensure_contacte_table($pdo);
    $tipuri_valide = array_keys(CONTACTE_TIPURI);
    $importati = 0;
    $eroare = [];
    foreach ($rows as $idx => $row) {
        $data = ['nume' => null, 'prenume' => null, 'companie' => null, 'tip_contact' => 'alte contacte',
            'telefon' => null, 'telefon_personal' => null, 'email' => null, 'email_personal' => null,
            'website' => null, 'data_nasterii' => null, 'notite' => null, 'referinta_contact' => null];
        foreach ($mapare as $excel_idx => $db_field) {
            if ($db_field === 'ignora') continue;
            $val = '';
            if (isset($headers[$excel_idx]) && isset($row[$headers[$excel_idx]])) {
                $val = trim((string)$row[$headers[$excel_idx]]);
            } elseif (isset($row[$excel_idx])) {
                $val = trim((string)$row[$excel_idx]);
            }
            if ($db_field === 'data_nasterii' && !empty($val)) {
                $d = date_create_from_format('d.m.Y', $val) ?: date_create_from_format('Y-m-d', $val) ?: date_create_from_format('d/m/Y', $val);
                $data[$db_field] = $d ? $d->format('Y-m-d') : null;
            } elseif ($db_field === 'tip_contact') {
                $data[$db_field] = in_array($val, $tipuri_valide) ? $val : 'alte contacte';
            } else {
                $data[$db_field] = $val ?: null;
            }
        }
        if (empty($data['nume'])) {
            $eroare[] = 'Rând ' . ($idx + 2) . ': Nume obligatoriu';
            continue;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO contacte (nume, prenume, companie, tip_contact, telefon, telefon_personal, email, email_personal, website, data_nasterii, notite, referinta_contact) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['nume'], $data['prenume'], $data['companie'], $data['tip_contact'],
                $data['telefon'], $data['telefon_personal'], $data['email'], $data['email_personal'],
                $data['website'], $data['data_nasterii'], $data['notite'], $data['referinta_contact']
            ]);
            $importati++;
            log_activitate($pdo, 'contacte: Importat contact ' . trim($data['nume'] . ' ' . ($data['prenume'] ?? '')));
        } catch (PDOException $e) {
            $eroare[] = 'Rând ' . ($idx + 2) . ': ' . $e->getMessage();
        }
    }
    return ['importati' => $importati, 'eroare' => $eroare];
}
