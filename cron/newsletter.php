<?php
/**
 * Cron: trimitere newsletteruri programate
 * Rulează la fiecare minut (sau la interval dorit) și trimite newsletterurile
 * cu status = 'programat' și data_programata <= NOW().
 *
 * Exemplu crontab (Linux): * * * * * php /path/to/htdocs/crm-anr-bihor/cron/newsletter.php
 * Windows Task Scheduler: php.exe "C:\xampp\htdocs\crm-anr-bihor\cron\newsletter.php"
 *
 * Opțional din browser (pentru hosting fără cron): ?key=CHEIE_SECRETA
 * Setați în config sau .env o cheie și apelați URL-ul cu key=...
 */
$run_from_cli = (php_sapi_name() === 'cli');

if (!$run_from_cli) {
    // Apel din browser: permite doar cu cheie secretă (CRON_NEWSLETTER_KEY în config)
    $key = $_GET['key'] ?? '';
    if (CRON_NEWSLETTER_KEY === '' || $key !== CRON_NEWSLETTER_KEY) {
        header('HTTP/1.1 403 Forbidden');
        exit('Forbidden');
    }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/newsletter_helper.php';

$rez = newsletter_proceseaza_programate($pdo);

if ($run_from_cli) {
    if ($rez['procesate'] > 0) {
        echo date('Y-m-d H:i:s') . " Newsletter: {$rez['procesate']} programate trimise, {$rez['trimise_total']} emailuri.\n";
        foreach ($rez['erori'] as $e) {
            echo "  Eroare: {$e}\n";
        }
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'procesate' => $rez['procesate'],
        'trimise_total' => $rez['trimise_total'],
        'erori' => $rez['erori'],
    ], JSON_UNESCAPED_UNICODE);
}
