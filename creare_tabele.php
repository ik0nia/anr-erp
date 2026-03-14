<?php
/**
 * Script pentru crearea tuturor tabelelor în baza de date (hosting sau local).
 * Folosește config.php (aceleași credențiale ca platforma) și rulează toate fișierele schema_*.sql.
 *
 * UTILIZARE: Accesați în browser https://domeniul.ro/cale/creare_tabele.php
 * După ce tabelele au fost create cu succes, ȘTERGEȚI acest fișier din motive de securitate.
 */
require_once __DIR__ . '/config.php';

$title = 'Creare tabele bază de date';
$errors = [];
$success = [];
$run = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executa']);

// Restricționare doar pentru administrator (auth_helper este încărcat din config.php)
if (function_exists('is_admin') && !is_admin()) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Acces interzis</title></head><body>';
    echo '<p>Doar administratorii pot rula acest script.</p><p><a href="index.php">Înapoi la Dashboard</a></p></body></html>';
    exit;
}

$schemaFiles = [
    'schema.sql',
    'schema_utilizatori.sql',
    'schema_contacte.sql',
    'schema_activitati.sql',
    'schema_notificari.sql',
    'schema_taskuri.sql',
    'schema_liste_prezenta.sql',
    'schema_documente.sql',
    'schema_registratura_update.sql',
    'schema_registru_interactiuni_v2.sql',
    'schema_bpa.sql',
    'schema_administrativ.sql',
    'schema_newsletter.sql',
    'schema_librarie_documente.sql',
    'schema_log_activitate.sql',
];

function tableExists(PDO $pdo, $tableName) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName));
        return $stmt && $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function runSchemaFile(PDO $pdo, $basePath, $schemaFile, array &$errors) {
    $filePath = $basePath . '/' . $schemaFile;
    if (!file_exists($filePath)) {
        return 0;
    }
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        $errors[] = "Nu s-a putut citi: {$schemaFile}";
        return 0;
    }
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function ($q) {
            return $q !== '' && !preg_match('/^--/', $q);
        }
    );
    $count = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query === '') continue;
        try {
            $pdo->exec($query);
            $count++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = "{$schemaFile}: " . $e->getMessage();
            }
        }
    }
    return $count;
}

function ensureUtilizatoriTable(PDO $pdo, $basePath, array &$errors) {
    if (tableExists($pdo, 'utilizatori')) {
        return true;
    }
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    } catch (PDOException $e) {}
    runSchemaFile($pdo, $basePath, 'schema_utilizatori.sql', $errors);
    if (tableExists($pdo, 'utilizatori')) {
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (PDOException $e) {}
        return true;
    }
    $createUtilizatori = "CREATE TABLE IF NOT EXISTS utilizatori (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nume_complet VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        functie VARCHAR(255) DEFAULT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        parola_hash VARCHAR(255) NOT NULL,
        rol ENUM('administrator', 'operator') NOT NULL DEFAULT 'operator',
        activ TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_activ (activ)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $createPasswordReset = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        utilizator_id INT NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        expira_la DATETIME NOT NULL,
        folosit TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_expira (expira_la),
        FOREIGN KEY (utilizator_id) REFERENCES utilizatori(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    try {
        $pdo->exec($createUtilizatori);
        $pdo->exec($createPasswordReset);
    } catch (PDOException $e) {
        $errors[] = "Creare utilizatori/password_reset_tokens: " . $e->getMessage();
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (PDOException $e2) {}
        return false;
    }
    try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (PDOException $e) {}
    return tableExists($pdo, 'utilizatori');
}

if ($run) {
    $basePath = __DIR__;
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    } catch (PDOException $e) {}
    $filesRun = 0;
    foreach ($schemaFiles as $schemaFile) {
        $n = runSchemaFile($pdo, $basePath, $schemaFile, $errors);
        if ($n > 0 || file_exists($basePath . '/' . $schemaFile)) {
            $filesRun++;
            $success[] = "Ruleat: {$schemaFile}";
        }
    }
    ensureUtilizatoriTable($pdo, $basePath, $errors);
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (PDOException $e) {}
    $success[] = "Total fișiere procesate: {$filesRun}. Tabelele au fost create sau actualizate.";
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        .alert { padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        ul { margin: 0.5rem 0; padding-left: 1.5rem; }
        button, .btn { padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-size: 1rem; }
        .btn-primary { background: #d97706; color: white; border: none; }
        .btn-primary:hover { background: #b45309; }
        a { color: #d97706; }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <div class="alert alert-warning" role="alert">
        <strong>Securitate:</strong> După ce tabelele au fost create cu succes, ștergeți acest fișier (<code>creare_tabele.php</code>) de pe server.
    </div>
    <?php if ($run): ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <strong>Erori (unele pot fi ignorate, ex. coloană existentă):</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php foreach ($success as $s): ?>
                    <p><?php echo htmlspecialchars($s); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p><a href="membri.php">Mergi la Membri</a> &middot; <a href="index.php">Dashboard</a></p>
    <?php else: ?>
        <p>Acest script execută toate fișierele <code>schema_*.sql</code> din proiect și creează tabelele lipsă în baza de date curentă (<code><?php echo htmlspecialchars(defined('DB_NAME') ? DB_NAME : ''); ?></code>).</p>
        <form method="post" action="">
            <button type="submit" name="executa" value="1" class="btn btn-primary">Execută crearea tabelelor</button>
        </form>
        <p style="margin-top: 1rem;"><a href="index.php">Înapoi la Dashboard</a></p>
    <?php endif; ?>
</body>
</html>
