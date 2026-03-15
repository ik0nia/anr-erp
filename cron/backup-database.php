<?php
/**
 * Script backup bază de date - CRM ANR Bihor
 * Rulați manual sau configurați ca cron job pentru backup automat
 *
 * Utilizare manuală: php cron/backup-database.php
 * Sau accesați în browser: http://localhost/crm-anr-bihor/cron/backup-database.php
 *
 * PENTRU SECURITATE: Protejați acest fișier cu .htaccess sau mutați-l în afara document root!
 */

require_once __DIR__ . '/../config.php';

// Configurare backup
$backup_dir = __DIR__ . '/../backups/';
$max_backups = 30; // Păstrează ultimele 30 backup-uri
$filename_prefix = 'crm_backup_';

// Creează directorul backup dacă nu există
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0750, true);
}

// Generare nume fișier
$timestamp = date('Y-m-d_His');
$filename = $filename_prefix . $timestamp . '.sql';
$filepath = $backup_dir . $filename;

try {
    // Comandă mysqldump
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s %s > %s',
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_NAME),
        escapeshellarg($filepath)
    );

    // Executare backup
    exec($command, $output, $return_var);

    if ($return_var !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
        throw new Exception('Eroare la crearea backup-ului. Verificați configurarea mysqldump.');
    }

    // Comprimare backup (opțional - necesită gzip)
    if (function_exists('gzencode')) {
        $compressed = gzencode(file_get_contents($filepath), 9);
        $compressed_file = $filepath . '.gz';
        file_put_contents($compressed_file, $compressed);
        unlink($filepath); // Șterge fișierul necomprimat
        $filepath = $compressed_file;
        $filename .= '.gz';
    }

    // Ștergere backup-uri vechi
    $backups = glob($backup_dir . $filename_prefix . '*.sql*');
    if (count($backups) > $max_backups) {
        usort($backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        $to_delete = array_slice($backups, 0, count($backups) - $max_backups);
        foreach ($to_delete as $old_backup) {
            @unlink($old_backup);
        }
    }

    $file_size = filesize($filepath);
    $file_size_mb = round($file_size / 1024 / 1024, 2);

    // Output
    if (php_sapi_name() === 'cli') {
        echo "Backup creat cu succes!\n";
        echo "Fișier: {$filename}\n";
        echo "Dimensiune: {$file_size_mb} MB\n";
        echo "Locație: {$filepath}\n";
    } else {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="ro">
        <head><meta charset="utf-8">

            <title>Backup baza de date</title>
            <link href="../css/tailwind.css" rel="stylesheet">
        </head>
        <body class="bg-gray-100 p-8">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
                <h1 class="text-xl font-bold mb-4">Backup creat cu succes</h1>
                <p class="mb-2"><strong>Fișier:</strong> <?php echo htmlspecialchars($filename); ?></p>
                <p class="mb-2"><strong>Dimensiune:</strong> <?php echo $file_size_mb; ?> MB</p>
                <p class="mb-4"><strong>Locație:</strong> <code class="text-sm"><?php echo htmlspecialchars($filepath); ?></code></p>
                <a href="../setari.php" class="inline-block px-4 py-2 bg-amber-600 text-white rounded-lg">Înapoi la Setări</a>
            </div>
        </body>
        </html>
        <?php
    }

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "EROARE: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo "EROARE: " . htmlspecialchars($e->getMessage());
    }
    exit(1);
}
