<?php
/**
 * Notificare zilnică Aniversări – generează o notificare cu aniversările zilei (membri + contacte).
 * Rulează o dată pe zi (ex: cron 0 8 * * * php .../cron/aniversari-notificare.php sau acces URL dimineața).
 */
require_once __DIR__ . '/../config.php';

$run_from_cli = (php_sapi_name() === 'cli');

if (!$run_from_cli) {
    // Apel din browser: permite doar cu cheie secretă (CRON_NEWSLETTER_KEY în config)
    $key = $_GET['key'] ?? '';
    if (!defined('CRON_NEWSLETTER_KEY') || CRON_NEWSLETTER_KEY === '' || $key !== CRON_NEWSLETTER_KEY) {
        header('HTTP/1.1 403 Forbidden');
        exit('Forbidden');
    }
}

require_once __DIR__ . '/../includes/notificari_helper.php';
require_once __DIR__ . '/../includes/contacte_helper.php';
require_once __DIR__ . '/../includes/log_helper.php';

$azi = date('Y-m-d');

// Verifică dacă notificarea pentru azi a fost deja trimisă
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS setari (id INT AUTO_INCREMENT PRIMARY KEY, cheie VARCHAR(100) NOT NULL UNIQUE, valoare TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $stmt = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
    $stmt->execute(['aniversari_notificare_ultima_zi']);
    $ultima = $stmt->fetchColumn();
    if ($ultima === $azi) {
        if (php_sapi_name() === 'cli') {
            echo "Notificare aniversări pentru {$azi} a fost deja trimisă.\n";
        }
        exit(0);
    }
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        echo "Eroare verificare setări: " . $e->getMessage() . "\n";
    }
    exit(1);
}

// Încarcă aniversări membri
$membri = [];
try {
    $stmt = $pdo->query("
        SELECT nume, prenume, datanastere
        FROM membri
        WHERE datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE()) AND DAY(datanastere) = DAY(CURDATE())
        ORDER BY nume, prenume
    ");
    $membri = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Încarcă aniversări contacte (fără Beneficiari)
$contacte = [];
try {
    ensure_contacte_table($pdo);
    $stmt = $pdo->prepare("
        SELECT nume, prenume, tip_contact
        FROM contacte
        WHERE data_nasterii IS NOT NULL AND MONTH(data_nasterii) = MONTH(CURDATE()) AND DAY(data_nasterii) = DAY(CURDATE())
          AND (
                tip_contact IS NULL
                OR (tip_contact != 'Beneficiar' AND tip_contact != 'Beneficiari')
              )
        ORDER BY nume, prenume
    ");
    $stmt->execute();
    $contacte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$total = count($membri) + count($contacte);
$titlu = 'Aniversări ' . date('d.m.Y');
$continut = "Azi (" . date('d.m.Y') . ") își serbează ziua de naștere " . $total . " persoane.\n\n";

if (!empty($membri)) {
    $continut .= "Membri:\n";
    foreach ($membri as $m) {
        $continut .= "• " . trim($m['nume'] . ' ' . $m['prenume']) . "\n";
    }
    $continut .= "\n";
}
if (!empty($contacte)) {
    $continut .= "Contacte:\n";
    foreach ($contacte as $c) {
        $tip = isset($c['tip_contact']) && $c['tip_contact'] !== '' ? (CONTACTE_TIPURI[$c['tip_contact']] ?? $c['tip_contact']) : '';
        $continut .= "• " . trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? '')) . ($tip ? " ({$tip})" : "") . "\n";
    }
}
$continut .= "\nVizualizați lista completă în modulul Aniversări (Dashboard → Aniversări zilei).";

notificari_ensure_tables($pdo);
$notif_id = notificari_adauga($pdo, [
    'titlu' => $titlu,
    'importanta' => 'Informativ',
    'continut' => $continut,
    'trimite_email' => 0,
], null, null);

if ($notif_id > 0) {
    $stmt = $pdo->prepare('INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = VALUES(valoare)');
    $stmt->execute(['aniversari_notificare_ultima_zi', $azi]);
    log_activitate($pdo, "Notificare zilnică aniversări: {$titlu} (ID: {$notif_id}), {$total} persoane.");
    if (php_sapi_name() === 'cli') {
        echo "Notificare aniversări creată: ID {$notif_id}, {$total} persoane.\n";
    }
} else {
    if (php_sapi_name() === 'cli') {
        echo "Eroare la crearea notificării.\n";
    }
    exit(1);
}
