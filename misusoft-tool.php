<?php
/**
 * Maintenance Tool - CRM ANR Bihor
 * Script utilitar independent pentru backup/restore complet.
 * Funcționează izolat pentru recuperare în caz de crash total.
 *
 * Cerințe: PHP cu extensiile zip și mysqli. Acces protejat prin parolă.
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ============== CONFIGURARE AUTENTIFICARE ==============
$auth_password = 'CrmMaintenance2025!'; // Schimbați la primul deploy!

// ============== VERIFICĂRI INIȚIALE ==============
if (!extension_loaded('zip')) {
    die('Eroare: Extensia PHP <strong>zip</strong> este necesară. Instalați-o în php.ini.');
}
if (!extension_loaded('mysqli')) {
    die('Eroare: Extensia PHP <strong>mysqli</strong> este necesară. Instalați-o în php.ini.');
}

// ============== AUTO-CONFIGURARE DIN config.php ==============
$db_config = [
    'host' => null,
    'name' => null,
    'user' => null,
    'pass' => null,
];

$config_path = __DIR__ . '/config.php';
if (file_exists($config_path)) {
    @include_once $config_path; // încarcă define-uri
    $db_config['host'] = defined('DB_HOST') ? DB_HOST : null;
    $db_config['name'] = defined('DB_NAME') ? DB_NAME : null;
    $db_config['user'] = defined('DB_USER') ? DB_USER : null;
    $db_config['pass'] = defined('DB_PASS') ? DB_PASS : '';
    // Fallback regex dacă constantele au alt nume sau format
    if (!$db_config['host'] || !$db_config['name'] || !$db_config['user']) {
        $config_content = file_get_contents($config_path);
        if (!$db_config['host'] && preg_match("/define\s*\(\s*['\"]?(?:DB_HOST|db_host)['\"]?\s*,\s*['\"]([^'\"]*)['\"]/i", $config_content, $m)) $db_config['host'] = $m[1];
        if (!$db_config['name'] && preg_match("/define\s*\(\s*['\"]?(?:DB_NAME|db_name)['\"]?\s*,\s*['\"]([^'\"]*)['\"]/i", $config_content, $m)) $db_config['name'] = $m[1];
        if (!$db_config['user'] && preg_match("/define\s*\(\s*['\"]?(?:DB_USER|db_user)['\"]?\s*,\s*['\"]([^'\"]*)['\"]/i", $config_content, $m)) $db_config['user'] = $m[1];
        if ($db_config['pass'] === '' && preg_match("/define\s*\(\s*['\"]?(?:DB_PASS|db_pass)['\"]?\s*,\s*['\"]([^'\"]*)['\"]/i", $config_content, $m)) $db_config['pass'] = $m[1];
    }
}

$BACKUP_DIR = __DIR__ . '/backups';
$MAINTENANCE_SCRIPT = basename(__FILE__);

// ============== AUTENTIFICARE ==============
session_start();
$authenticated = !empty($_SESSION['maintenance_auth']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (isset($_POST['password']) && hash_equals($auth_password, (string)($_POST['password'] ?? ''))) {
        $_SESSION['maintenance_auth'] = true;
        $authenticated = true;
    } else {
        $login_error = 'Parolă incorectă.';
    }
}
if (isset($_GET['logout'])) {
    unset($_SESSION['maintenance_auth']);
    session_destroy();
    header('Location: ' . basename(__FILE__));
    exit;
}

// ============== CLASĂ CRMManager ==============
class CRMManager
{
    private array $db_config;
    private string $root_dir;
    private string $backup_dir;
    private string $maintenance_script;
    private array $logs = [];

    public function __construct(array $db_config, string $root_dir, string $backup_dir, string $maintenance_script)
    {
        $this->db_config = $db_config;
        $this->root_dir = rtrim($root_dir, '/\\');
        $this->backup_dir = rtrim($backup_dir, '/\\');
        $this->maintenance_script = $maintenance_script;
    }

    private function log(string $msg): void
    {
        $this->logs[] = ['ts' => date('H:i:s'), 'msg' => $msg];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function validateConfig(): ?string
    {
        if (empty($this->db_config['host']) || empty($this->db_config['name']) || empty($this->db_config['user'])) {
            return 'Configurarea bazei de date nu a putut fi extrasă din config.php. Verificați constantele DB_HOST, DB_NAME, DB_USER, DB_PASS.';
        }
        return null;
    }

    public function runBackup(): array
    {
        set_time_limit(0);
        $this->logs = [];

        if (!is_writable($this->root_dir)) {
            return ['ok' => false, 'error' => 'Nu există permisiuni de scriere în directorul proiectului.'];
        }
        if (!is_dir($this->backup_dir)) {
            if (!@mkdir($this->backup_dir, 0755, true)) {
                return ['ok' => false, 'error' => 'Nu s-a putut crea directorul backups/.'];
            }
        }
        if (!is_writable($this->backup_dir)) {
            return ['ok' => false, 'error' => 'Nu există permisiuni de scriere în directorul backups/.'];
        }

        $zip_name = 'BackupCRM_' . date('Y-m-d_H-i') . '.zip';
        $zip_path = $this->backup_dir . DIRECTORY_SEPARATOR . $zip_name;
        $dump_path = $this->backup_dir . DIRECTORY_SEPARATOR . 'dump_' . uniqid() . '.sql';

        try {
            // 1. Generare dump SQL
            $this->log('Începe generarea dump SQL...');
            $sql_content = $this->generateSqlDump();
            if ($sql_content === null) {
                return ['ok' => false, 'error' => 'Nu s-a putut genera dump-ul SQL.', 'logs' => $this->logs];
            }
            if (file_put_contents($dump_path, $sql_content) === false) {
                return ['ok' => false, 'error' => 'Nu s-a putut scrie fișierul SQL temporar.', 'logs' => $this->logs];
            }
            $this->log('Dump SQL generat: ' . number_format(strlen($sql_content) / 1024, 1) . ' KB');

            // 2. Creare arhivă ZIP
            $this->log('Creare arhivă ZIP...');
            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                @unlink($dump_path);
                return ['ok' => false, 'error' => 'Nu s-a putut crea arhiva ZIP.', 'logs' => $this->logs];
            }

            $zip->addFile($dump_path, 'dump.sql');
            $this->log('Adăugat dump.sql în arhivă');

            // 3. Adăugare fișiere recursive (exclude backup zips și directorul backups)
            $added = $this->addFilesToZip($zip, $this->root_dir, '');
            $this->log('Adăugate ' . $added . ' fișiere în arhivă');

            $zip->close();
            @unlink($dump_path);
            $size = file_exists($zip_path) ? filesize($zip_path) : 0;
            $this->log('Backup finalizat: ' . $zip_name . ' (' . number_format($size / 1024 / 1024, 2) . ' MB)');

            return ['ok' => true, 'file' => $zip_name, 'logs' => $this->logs];
        } catch (Throwable $e) {
            @unlink($dump_path ?? '');
            if (isset($zip) && $zip instanceof ZipArchive) {
                $zip->close();
            }
            @unlink($zip_path ?? '');
            $this->log('EROARE: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage(), 'logs' => $this->logs];
        }
    }

    private function generateSqlDump(): ?string
    {
        $h = $this->db_config['host'] ?? 'localhost';
        $n = $this->db_config['name'] ?? '';
        $u = $this->db_config['user'] ?? '';
        $p = $this->db_config['pass'] ?? '';

        // Încercare mysqldump
        $cmd = sprintf(
            'mysqldump --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s 2>/dev/null',
            escapeshellarg($h),
            escapeshellarg($u),
            escapeshellarg($p),
            escapeshellarg($n)
        );
        $out = [];
        @exec($cmd, $out);
        $result = implode("\n", $out);
        if (!empty($result) && (strpos($result, 'CREATE TABLE') !== false || strpos($result, 'INSERT') !== false)) {
            return $result;
        }

        // Fallback: export PHP via mysqli
        $mysqli = @new mysqli($h, $u, $p, $n);
        if ($mysqli->connect_error) {
            $this->log('mysqli connect: ' . $mysqli->connect_error);
            return null;
        }
        $mysqli->set_charset('utf8mb4');
        $dump = "-- MySQL Dump (PHP fallback)\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        $res = $mysqli->query("SHOW TABLES");
        if (!$res) {
            $mysqli->close();
            return null;
        }
        $tables = [];
        while ($row = $res->fetch_array()) {
            $tables[] = $row[0];
        }
        foreach ($tables as $table) {
            $dump .= "\nDROP TABLE IF EXISTS `" . $mysqli->real_escape_string($table) . "`;\n";
            $cr = $mysqli->query("SHOW CREATE TABLE `" . $mysqli->real_escape_string($table) . "`");
            if ($cr && $r = $cr->fetch_array()) {
                $dump .= $r[1] . ";\n";
            }
            $sel = $mysqli->query("SELECT * FROM `" . $mysqli->real_escape_string($table) . "`");
            if ($sel && $sel->num_rows > 0) {
                $cols = [];
                while ($c = $sel->fetch_field()) {
                    $cols[] = "`" . $c->name . "`";
                }
                $dump .= "INSERT INTO `" . $mysqli->real_escape_string($table) . "` (" . implode(',', $cols) . ") VALUES\n";
                $rows = [];
                $sel->data_seek(0);
                while ($row = $sel->fetch_assoc()) {
                    $vals = [];
                    foreach ($row as $v) {
                        $vals[] = $v === null ? 'NULL' : "'" . $mysqli->real_escape_string((string)$v) . "'";
                    }
                    $rows[] = '(' . implode(',', $vals) . ')';
                }
                $dump .= implode(",\n", $rows) . ";\n";
            }
        }
        $mysqli->close();
        return $dump;
    }

    private function addFilesToZip(ZipArchive $zip, string $dir, string $relative): int
    {
        $count = 0;
        $real_backup = realpath($this->backup_dir);
        $items = @scandir($dir);
        if (!$items) return 0;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $item;
            $rel = $relative ? $relative . '/' . $item : $item;
            if (is_dir($full)) {
                if ($item === 'backups') {
                    continue; // exclude backups dir (evită recursivitate infinită)
                }
                $real_full = realpath($full);
                if ($real_backup && $real_full && strpos($real_full, $real_backup) === 0) {
                    continue; // exclude anything inside backups
                }
                $count += $this->addFilesToZip($zip, $full, $rel);
            } else {
                if (preg_match('/^BackupCRM_.*\.zip$/i', $item)) continue;
                if ($item === $this->maintenance_script && $relative === '') continue; // opțional: exclude maintenance script din backup
                $zip->addFile($full, $rel);
                $count++;
            }
        }
        return $count;
    }

    public function runRestore(string $zip_path): array
    {
        set_time_limit(0);
        $this->logs = [];

        if (!file_exists($zip_path) || !is_readable($zip_path)) {
            return ['ok' => false, 'error' => 'Fișierul de backup nu există sau nu este accesibil.', 'logs' => []];
        }
        $zip = new ZipArchive();
        if ($zip->open($zip_path) !== true) {
            return ['ok' => false, 'error' => 'Arhiva ZIP invalidă sau coruptă.', 'logs' => []];
        }

        try {
            // 1. Găsire fișier SQL în arhivă
            $sql_local = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('/\.sql$/i', $name) && strpos($name, '__MACOSX') === false) {
                    $sql_local = $name;
                    break;
                }
            }
            if (!$sql_local) {
                $zip->close();
                return ['ok' => false, 'error' => 'Nu s-a găsit fișier SQL în arhivă.', 'logs' => []];
            }

            $this->log('Fișier SQL găsit: ' . $sql_local);

            // 2. Curățare director (excluzând maintenance script și backups)
            $this->log('Curățare director proiect (păstrare maintenance_tool.php)...');
            $cleaned = $this->cleanDirectory($this->root_dir);
            $this->log('Șterse/curățate ' . $cleaned . ' elemente');

            // 3. Extragere arhivă (zip încă deschis)
            $this->log('Extragere arhivă...');
            $zip->extractTo($this->root_dir);
            $zip->close();
            $this->log('Arhivă extrasă');

            // 4. Import SQL
            $sql_full = $this->root_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sql_local);
            if (!file_exists($sql_full)) {
                $sql_full = $this->root_dir . DIRECTORY_SEPARATOR . basename($sql_local);
            }
            if (!file_exists($sql_full)) {
                return ['ok' => false, 'error' => 'Fișierul SQL extras nu a fost găsit.', 'logs' => $this->logs];
            }
            $this->log('Import SQL în curs...');
            $imported = $this->importSqlFile($sql_full);
            @unlink($sql_full);
            $this->log('Import finalizat: ' . $imported . ' comenzi executate');

            return ['ok' => true, 'logs' => $this->logs];
        } catch (Throwable $e) {
            $zip->close();
            $this->log('EROARE: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage(), 'logs' => $this->logs];
        }
    }

    private function cleanDirectory(string $dir): int
    {
        $count = 0;
        $real_backup = realpath($this->backup_dir);
        $real_root = realpath($this->root_dir);
        $items = @scandir($dir);
        if (!$items) return 0;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $full = $dir . DIRECTORY_SEPARATOR . $item;
            $real_full = realpath($full);
            if ($item === $this->maintenance_script && $dir === $this->root_dir) continue;
            if ($real_backup && $real_full && strpos($real_full, $real_backup) === 0) continue; // nu șterge backups/
            if (is_dir($full)) {
                $count += $this->cleanDirectory($full);
                @rmdir($full);
                $count++;
            } else {
                @unlink($full);
                $count++;
            }
        }
        return $count;
    }

    private function importSqlFile(string $path): int
    {
        $sql = file_get_contents($path);
        if ($sql === false) return 0;

        $h = $this->db_config['host'] ?? 'localhost';
        $u = $this->db_config['user'] ?? '';
        $p = $this->db_config['pass'] ?? '';
        $n = $this->db_config['name'] ?? '';

        // Încercare mysql CLI (mai rapid pentru fișiere mari)
        $isWin = (defined('PHP_OS_FAMILY') && PHP_OS_FAMILY === 'Windows') || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $stderr = $isWin ? '2>nul' : '2>/dev/null';
        $path_esc = escapeshellarg($path);
        $cmd = sprintf(
            'mysql --host=%s --user=%s --password=%s %s < %s %s',
            escapeshellarg($h),
            escapeshellarg($u),
            escapeshellarg($p),
            escapeshellarg($n),
            $path_esc,
            $stderr
        );
        exec($cmd, $_, $exitCode);
        if ($exitCode === 0) {
            return 1; // Import reușit prin mysql CLI
        }

        // Fallback PHP via mysqli
        $mysqli = new mysqli($h, $u, $p, $n);
        if ($mysqli->connect_error) {
            throw new RuntimeException('Conectare DB eșuată: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
        $count = 0;
        $stmts = preg_split('/;\s*[\r\n]+/', $sql);
        foreach ($stmts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || preg_match('/^--/', $stmt)) continue;
            $fullStmt = substr($stmt, -1) === ';' ? $stmt : $stmt . ';';
            if ($mysqli->query($fullStmt)) $count++;
        }
        $mysqli->close();
        return $count;
    }

    public function listBackups(): array
    {
        $list = [];
        if (!is_dir($this->backup_dir)) return $list;
        foreach (glob($this->backup_dir . '/BackupCRM_*.zip') as $f) {
            $list[] = [
                'name' => basename($f),
                'path' => $f,
                'size' => filesize($f),
                'time' => filemtime($f),
            ];
        }
        usort($list, fn($a, $b) => $b['time'] <=> $a['time']);
        return $list;
    }
}

// ============== PROCESARE CERERI (doar dacă autentificat) ==============
$result = null;
if ($authenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $manager = new CRMManager($db_config, __DIR__, $BACKUP_DIR, $MAINTENANCE_SCRIPT);
    if (isset($_POST['action_backup'])) {
        $cfg_err = $manager->validateConfig();
        if ($cfg_err) {
            $result = ['ok' => false, 'error' => $cfg_err, 'logs' => []];
        } else {
            $result = $manager->runBackup();
        }
    } elseif (isset($_POST['action_restore'])) {
        $cfg_err = $manager->validateConfig();
        if ($cfg_err) {
            $result = ['ok' => false, 'error' => $cfg_err, 'logs' => []];
        } elseif (!empty($_POST['restore_file'])) {
            $f = basename($_POST['restore_file']);
            $path = $BACKUP_DIR . DIRECTORY_SEPARATOR . $f;
            if (preg_match('/^BackupCRM_.*\.zip$/', $f) && file_exists($path)) {
                $result = $manager->runRestore($path);
            } else {
                $result = ['ok' => false, 'error' => 'Fișier invalid.', 'logs' => []];
            }
        } elseif (!empty($_FILES['restore_upload']['tmp_name']) && is_uploaded_file($_FILES['restore_upload']['tmp_name'])) {
            $tmp = $_FILES['restore_upload']['tmp_name'];
            $result = $manager->runRestore($tmp);
        } else {
            $result = ['ok' => false, 'error' => 'Selectați o arhivă sau încărcați un fișier.', 'logs' => []];
        }
    }
}

// ============== LISTARE BACKUP-URI (pentru UI Restore) ==============
$backups = [];
if ($authenticated) {
    $m = new CRMManager($db_config, __DIR__, $BACKUP_DIR, $MAINTENANCE_SCRIPT);
    $backups = $m->listBackups();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Tool – CRM ANR Bihor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 dark:bg-gray-900 min-h-screen text-slate-900 dark:text-gray-100">
<div class="max-w-2xl mx-auto p-6">
    <header class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Maintenance Tool</h1>
        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">Backup & Restore – CRM ANR Bihor</p>
    </header>

    <?php if (!$authenticated): ?>
    <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-4">Autentificare</h2>
        <?php if (!empty($login_error)): ?>
        <p class="text-red-600 dark:text-red-400 text-sm mb-4" role="alert"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <form method="post" class="space-y-4">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Parolă</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                       class="w-full px-3 py-2 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <button type="submit" name="login" value="1" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Acces</button>
        </form>
    </section>
    <?php else: ?>
    <nav class="mb-6 flex gap-2">
        <a href="?logout=1" class="text-sm text-slate-600 dark:text-gray-400 hover:underline">Deconectare</a>
    </nav>

    <?php if ($result): ?>
    <section class="mb-6 p-4 rounded-lg <?php echo $result['ok'] ? 'bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700' : 'bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700'; ?>" role="status">
        <p class="font-medium <?php echo $result['ok'] ? 'text-emerald-800 dark:text-emerald-200' : 'text-red-800 dark:text-red-200'; ?>">
            <?php echo $result['ok'] ? 'Operațiune finalizată cu succes.' . (isset($result['file']) ? ' Fișier: ' . htmlspecialchars($result['file']) : '') : 'Eroare: ' . htmlspecialchars($result['error'] ?? 'necunoscută'); ?>
        </p>
        <?php if (!empty($result['logs'])): ?>
        <pre class="mt-3 text-xs overflow-x-auto max-h-48 overflow-y-auto bg-white/50 dark:bg-black/20 p-3 rounded font-mono"><?php foreach ($result['logs'] as $l) { echo htmlspecialchars($l['ts'] . ' | ' . $l['msg']) . "\n"; } ?></pre>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <div class="grid gap-6 md:grid-cols-2">
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-2">Backup</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Generează un backup complet (SQL + fișiere) în <code class="text-amber-600 dark:text-amber-400">backups/</code></p>
            <form method="post" onsubmit="return confirm('Executați backup-ul? Operațiunea poate dura câteva minute.');">
                <input type="hidden" name="action_backup" value="1">
                <button type="submit" class="w-full px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Execută Backup</button>
            </form>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold mb-2">Restore</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Restaurați din arhivă existentă sau încărcați o nouă arhivă. <strong>Datele curente vor fi suprascrise!</strong></p>
            <form method="post" enctype="multipart/form-data" onsubmit="return confirm('ATENȚIE: Restore-ul va șterge fișierele curente și va reimporta baza de date. Continuați?');">
                <input type="hidden" name="action_restore" value="1">
                <?php if (!empty($backups)): ?>
                <div class="mb-4">
                    <label for="restore_file" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Arhivă existentă</label>
                    <select id="restore_file" name="restore_file" class="w-full px-3 py-2 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                        <option value="">-- Selectați --</option>
                        <?php foreach ($backups as $b): ?>
                        <option value="<?php echo htmlspecialchars($b['name']); ?>"><?php echo htmlspecialchars($b['name']); ?> (<?php echo number_format($b['size']/1024/1024, 2); ?> MB, <?php echo date('d.m.Y H:i', $b['time']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-xs text-slate-500 dark:text-gray-500 mb-2">sau încărcați o arhivă nouă:</p>
                <?php endif; ?>
                <div class="mb-4">
                    <input type="file" name="restore_upload" accept=".zip" class="w-full text-sm text-slate-600 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-amber-100 file:text-amber-800 dark:file:bg-amber-900/50 dark:file:text-amber-200">
                </div>
                <button type="submit" class="w-full px-4 py-3 bg-slate-600 hover:bg-slate-700 dark:bg-gray-600 dark:hover:bg-gray-500 text-white font-medium rounded-lg">Execută Restore</button>
            </form>
        </section>
    </div>

    <section class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
        <h3 class="text-sm font-semibold mb-2">Status</h3>
        <ul class="text-sm text-slate-600 dark:text-gray-400 space-y-1">
            <li>PHP: <?php echo PHP_VERSION; ?></li>
            <li>Extensii: zip ✓, mysqli ✓</li>
            <li>DB config: <?php echo (!empty($db_config['host']) && !empty($db_config['name'])) ? 'OK (' . htmlspecialchars($db_config['host'] . '/' . $db_config['name']) . ')' : 'Lipsă sau incomplet'; ?></li>
            <li>Backup dir: <?php echo is_dir($BACKUP_DIR) && is_writable($BACKUP_DIR) ? 'OK' : (is_dir($BACKUP_DIR) ? 'Nu e scriibil' : 'Nu există'); ?></li>
        </ul>
    </section>
    <?php endif; ?>
</div>
</body>
</html>
