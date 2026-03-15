<?php
/**
 * Service: Aniversari — Logica de business pentru aniversari membri si contacte
 */

/**
 * Calculeaza varsta pe baza datei de nastere
 */
function aniversari_calculeaza_varsta($data_nastere) {
    if (empty($data_nastere)) return '-';
    $birth = new DateTime($data_nastere);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

/**
 * Obtine membrii cu ziua de nastere azi (exclus Decedat)
 */
function aniversari_membri_azi(PDO $pdo): array {
    try {
        $stmt = $pdo->query("
            SELECT id, nume, prenume, datanastere, domloc, telefonnev, telefonapartinator, email,
                   gdpr, cidataelib, ceexp
            FROM membri
            WHERE datanastere IS NOT NULL
              AND MONTH(datanastere) = MONTH(CURDATE())
              AND DAY(datanastere) = DAY(CURDATE())
              AND (status_dosar IS NULL OR status_dosar != 'Decedat')
            ORDER BY nume, prenume
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Aniversari membri: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtine contactele cu ziua de nastere azi (exclus Beneficiar)
 */
function aniversari_contacte_azi(PDO $pdo): array {
    try {
        $stmt = $pdo->prepare("
            SELECT id, nume, prenume, data_nasterii, companie, telefon, telefon_personal, email, email_personal, tip_contact
            FROM contacte
            WHERE data_nasterii IS NOT NULL
              AND MONTH(data_nasterii) = MONTH(CURDATE())
              AND DAY(data_nasterii) = DAY(CURDATE())
              AND (tip_contact IS NULL OR tip_contact != 'Beneficiar')
            ORDER BY nume, prenume
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Aniversari contacte: ' . $e->getMessage());
        return [];
    }
}

/**
 * Obtine numarul de aniversari per zi pentru luna curenta (membri + contacte)
 */
function aniversari_per_zi_luna(PDO $pdo): array {
    $result = [];

    try {
        $stmt = $pdo->query("
            SELECT DAY(datanastere) as zi, COUNT(*) as n FROM membri
            WHERE datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE())
              AND (status_dosar IS NULL OR status_dosar != 'Decedat')
            GROUP BY zi
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[(int)$row['zi']] = (int)($result[(int)$row['zi']] ?? 0) + (int)$row['n'];
        }
    } catch (PDOException $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT DAY(data_nasterii) as zi, COUNT(*) as n FROM contacte
            WHERE data_nasterii IS NOT NULL AND MONTH(data_nasterii) = MONTH(CURDATE())
              AND (tip_contact IS NULL OR tip_contact != 'Beneficiar')
            GROUP BY zi
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[(int)$row['zi']] = (int)($result[(int)$row['zi']] ?? 0) + (int)$row['n'];
        }
    } catch (PDOException $e) {}

    return $result;
}

/**
 * Calculeaza datele de calendar pentru luna curenta
 */
function aniversari_calendar_data(): array {
    $luna_curenta = (int)date('n');
    $anul_curent = (int)date('Y');
    return [
        'luna_curenta' => $luna_curenta,
        'anul_curent' => $anul_curent,
        'zi_azi' => (int)date('j'),
        'zile_in_luna' => (int)date('t'),
        'prima_zi_luna' => (int)date('w', mktime(0, 0, 0, $luna_curenta, 1, $anul_curent)),
        'luni_ro' => ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
        'zile_sapt' => ['Dum', 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sam'],
    ];
}
