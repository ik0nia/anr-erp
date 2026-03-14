<?php
/**
 * Helper pentru log-uri de activitate
 * Format actiune: nume_camp, valoare_initiala > valoare_dupa / nume_membru
 */

if (!defined('DATE_FORMAT')) {
    define('DATE_FORMAT', 'd.m.Y');
    define('TIME_FORMAT', 'H:i');
    define('DATETIME_FORMAT', 'd.m.Y H:i');
}

/**
 * Înregistrează o acțiune în log-ul de activitate
 *
 * @param PDO $pdo Conexiunea la baza de date
 * @param string $actiune Descrierea acțiunii (ex: "Numar de telefon: 0740292095 > 0740001100 / Ion Popescu")
 * @param string|null $utilizator Numele utilizatorului (default: din sesiune sau "Sistem")
 * @param int|null $membru_id ID-ul membrului (opțional)
 */
function log_activitate($pdo, $actiune, $utilizator = null, $membru_id = null) {
    if ($utilizator === null) {
        $utilizator = $_SESSION['utilizator'] ?? 'Sistem';
    }

    try {
        // Verifică dacă tabelul are coloana membru_id
        $stmt = $pdo->prepare('INSERT INTO log_activitate (utilizator, actiune, membru_id) VALUES (?, ?, ?)');
        $stmt->execute([$utilizator, $actiune, $membru_id]);
    } catch (PDOException $e) {
        // Dacă coloana membru_id nu există, încercăm fără ea
        try {
            $stmt = $pdo->prepare('INSERT INTO log_activitate (utilizator, actiune) VALUES (?, ?)');
            $stmt->execute([$utilizator, $actiune]);
        } catch (PDOException $e2) {
            // Nu blocăm aplicația dacă log-ul eșuează (ex: tabel inexistent)
            error_log('Log activitate eroare: ' . $e2->getMessage());
        }
    }
}

/**
 * Formatează un mesaj de log pentru modificări (vechi > nou)
 *
 * @param string $camp Numele câmpului modificat
 * @param mixed $valoare_veche Valoarea veche (va fi convertită la string)
 * @param mixed $valoare_noua Valoarea nouă (va fi convertită la string)
 * @param string|null $context Context suplimentar (ex: nume membru, nume beneficiar, modul)
 * @return string Mesaj formatat pentru log
 */
function log_format_modificare($camp, $valoare_veche, $valoare_noua, $context = null) {
    $veche = $valoare_veche === null || $valoare_veche === '' ? '(gol)' : (string)$valoare_veche;
    $noua = $valoare_noua === null || $valoare_noua === '' ? '(gol)' : (string)$valoare_noua;
    $msg = "{$camp}: {$veche} > {$noua}";
    if ($context) {
        $msg .= " / {$context}";
    }
    return $msg;
}

/**
 * Formatează un mesaj de log pentru creare
 *
 * @param string $tip Tipul înregistrării (ex: "membru", "contact", "activitate")
 * @param string $nume Numele sau identificatorul înregistrării
 * @param string|null $context Context suplimentar
 * @return string Mesaj formatat pentru log
 */
function log_format_creare($tip, $nume, $context = null) {
    $msg = "{$tip}: Creat {$nume}";
    if ($context) {
        $msg .= " / {$context}";
    }
    return $msg;
}

/**
 * Formatează un mesaj de log pentru ștergere
 *
 * @param string $tip Tipul înregistrării
 * @param string $nume Numele sau identificatorul înregistrării
 * @param string|null $context Context suplimentar
 * @return string Mesaj formatat pentru log
 */
function log_format_stergere($tip, $nume, $context = null) {
    $msg = "{$tip}: Șters {$nume}";
    if ($context) {
        $msg .= " / {$context}";
    }
    return $msg;
}
