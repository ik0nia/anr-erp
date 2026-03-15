<?php
/**
 * Helper modul Contacte — WRAPPER COMPATIBILITATE
 *
 * Acest fisier pastreaza compatibilitatea cu modulele care inca
 * folosesc require_once 'includes/contacte_helper.php'.
 *
 * Logica reala este in: app/services/ContacteService.php
 *
 * Functiile vechi delegheaza catre service-ul nou.
 */
require_once __DIR__ . '/date_helper.php';
require_once __DIR__ . '/../app/services/ContacteService.php';

// Constanta pastrata pentru compatibilitate (contacte-import.php o foloseste)
if (!defined('CONTACTE_TIPURI')) {
    define('CONTACTE_TIPURI', contacte_tipuri());
}

/**
 * @deprecated Foloseste contacte_ensure_table() din ContacteService
 */
function ensure_contacte_table(PDO $pdo) {
    contacte_ensure_table($pdo);
}

/**
 * @deprecated Foloseste contacte_tipuri() din ContacteService
 */
function get_contacte_tipuri() {
    return contacte_tipuri();
}

/**
 * @deprecated Foloseste contacte_whatsapp() din ContacteService
 */
function contacte_whatsapp_url($telefon) {
    return contacte_whatsapp((string)$telefon);
}

/**
 * @deprecated Foloseste contacte_whatsapp() din ContacteService
 */
function contacte_whatsapp_url_cu_mesaj($telefon, $mesaj = '') {
    $url = contacte_whatsapp((string)$telefon);
    if (!$url) return null;
    if ($mesaj !== '') {
        $url .= '?text=' . rawurlencode($mesaj);
    }
    return $url;
}

/**
 * Extrage data nasterii din CNP. Pastrat pentru compatibilitate.
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
 * @deprecated Foloseste contacte_creeaza_donator() din ContacteService
 */
function contacte_creare_donator(PDO $pdo, $nume, $prenume = null, $cnp = null, $telefon = null, $email = null) {
    return contacte_creeaza_donator($pdo, (string)$nume, $prenume, $cnp, $telefon, $email);
}

/**
 * Mapare standard coloane Excel -> campuri contacte (compatibilitate)
 */
function contacte_mapare_coloane() {
    return contacte_mapare_import();
}

/**
 * Importa contacte din randuri mapate (pastrat aici — folosit de contacte-import.php)
 */
function importa_contacte(PDO $pdo, array $headers, array $rows, array $mapare) {
    require_once __DIR__ . '/log_helper.php';
    contacte_ensure_table($pdo);
    $tipuri_valide = array_keys(contacte_tipuri());
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
                $data[$db_field] = parse_date_to_ymd($val, ['d.m.Y', 'Y-m-d', 'd/m/Y']);
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
