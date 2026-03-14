<?php
/**
 * Clasa principală pentru instalare CRM ANR Bihor
 */
class CRMInstaller {
    private $basePath;
    private $schemaFiles;
    
    public function __construct() {
        $this->basePath = dirname(__DIR__);
        $this->schemaFiles = [
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
    }
    
    public function processStep($step) {
        $errors = [];
        $success = false;
        $redirect = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            switch ($step) {
                case 1:
                    $result = $this->step1_CheckRequirements();
                    break;
                case 2:
                    $result = $this->step2_DatabaseConfig();
                    break;
                case 3:
                    $result = $this->step3_CreateTables();
                    break;
                case 4:
                    $result = $this->step4_CreateAdmin();
                    break;
                case 5:
                    $result = $this->step5_PlatformConfig();
                    break;
                case 6:
                    $result = $this->step6_InstallPackages();
                    break;
                case 7:
                    $result = $this->step7_BuildCSS();
                    break;
                case 8:
                    $result = $this->step8_Finalize();
                    break;
                default:
                    $result = ['errors' => ['Pas invalid']];
            }
            
            $errors = $result['errors'] ?? [];
            $success = $result['success'] ?? false;
            
            if (empty($errors) && $success) {
                $_SESSION['install_step'] = $step + 1;
                if ($step < 8) {
                    // Returnează doar query string; setup.php/install adaugă scriptul
                    $redirect = '?step=' . ($step + 1);
                }
            }
        }
        
        return [
            'errors' => $errors,
            'success' => $success,
            'redirect' => $redirect
        ];
    }
    
    // Pasul 1: Verificare cerințe
    private function step1_CheckRequirements() {
        $errors = [];
        $checks = [];
        
        // Verifică versiunea PHP
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '7.4.0', '<')) {
            $errors[] = "PHP 7.4 sau mai nou este necesar. Versiunea curentă: {$phpVersion}";
            $checks['php'] = false;
        } else {
            $checks['php'] = true;
        }
        
        // Verifică extensiile necesare
        $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'gd', 'zip', 'curl'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = "Extensia PHP '{$ext}' nu este instalată.";
                $checks['ext_' . $ext] = false;
            } else {
                $checks['ext_' . $ext] = true;
            }
        }
        
        // Verifică permisiuni folder uploads
        $uploadsPath = $this->basePath . '/uploads';
        if (!is_dir($uploadsPath)) {
            if (!mkdir($uploadsPath, 0755, true)) {
                $errors[] = "Nu se poate crea folderul uploads/. Verifică permisiunile.";
                $checks['uploads'] = false;
            } else {
                $checks['uploads'] = true;
            }
        } else {
            if (!is_writable($uploadsPath)) {
                $errors[] = "Folderul uploads/ nu are permisiuni de scriere.";
                $checks['uploads'] = false;
            } else {
                $checks['uploads'] = true;
            }
        }
        
        $_SESSION['install_data'][1] = [
            'checks' => $checks,
            'php_version' => $phpVersion
        ];
        
        if (empty($errors)) {
            return ['success' => 'Toate cerințele sunt îndeplinite.'];
        }
        
        return ['errors' => $errors];
    }
    
    // Pasul 2: Configurare baza de date
    private function step2_DatabaseConfig() {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';
        
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Numele bazei de date este obligatoriu.';
        }
        if (empty($user)) {
            $errors[] = 'Utilizatorul bazei de date este obligatoriu.';
        }
        
        if (empty($errors)) {
            // Strategie: Încearcă mai întâi să se conecteze direct la baza de date
            // Dacă reușește, baza există. Dacă nu, verifică dacă poate fi creată.
            try {
                // Încearcă conexiunea directă la baza de date
                $pdo = new PDO(
                    "mysql:host={$host};dbname={$name};charset=utf8mb4",
                    $user,
                    $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Dacă ajunge aici, conexiunea la baza de date a reușit - baza există!
                $dbCreated = false;
                $dbExists = true;
                
            } catch (PDOException $e) {
                // Dacă conexiunea directă eșuează, verifică dacă e din cauza că baza nu există
                if (strpos($e->getMessage(), "Unknown database") !== false || 
                    strpos($e->getMessage(), "1049") !== false) {
                    // Baza de date nu există - încearcă să o creeze
                    try {
                        // Conectează fără baza de date pentru a putea crea una nouă
                        $pdo = new PDO(
                            "mysql:host={$host};charset=utf8mb4",
                            $user,
                            $pass,
                            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                        );
                        
                        // Încearcă să creeze baza de date
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Reconectează la baza de date creată
                        $pdo = new PDO(
                            "mysql:host={$host};dbname={$name};charset=utf8mb4",
                            $user,
                            $pass,
                            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                        );
                        
                        $dbCreated = true;
                        $dbExists = true;
                        
                    } catch (PDOException $createError) {
                        // Nu poate crea baza de date
                        if (strpos($createError->getMessage(), 'Access denied') !== false || 
                            strpos($createError->getMessage(), '1044') !== false ||
                            strpos($createError->getMessage(), 'CREATE') !== false) {
                            $errors[] = 'Baza de date "' . htmlspecialchars($name) . '" nu există și nu ai permisiuni pentru a o crea automat.';
                            $errors[] = 'Te rugăm să creezi manual baza de date în cPanel/phpMyAdmin, apoi continuă instalarea.';
                            $errors[] = 'După crearea bazei de date, apasă din nou "Continuă".';
                            return ['errors' => $errors];
                        }
                        // Altă eroare
                        $errors[] = 'Eroare la crearea bazei de date: ' . $createError->getMessage();
                        return ['errors' => $errors];
                    }
                } else {
                    // Altă eroare de conexiune (parolă greșită, utilizator greșit, etc.)
                    $errors[] = 'Eroare conexiune baza de date: ' . $e->getMessage();
                    $errors[] = 'Verifică că:';
                    $errors[] = '- Numele bazei de date este corect: "' . htmlspecialchars($name) . '"';
                    $errors[] = '- Utilizatorul și parola sunt corecte';
                    $errors[] = '- Utilizatorul are permisiuni pe baza de date';
                    $errors[] = '- Baza de date există în cPanel/phpMyAdmin';
                    return ['errors' => $errors];
                }
            }
            
            // Dacă ajunge aici, conexiunea a reușit
            // NU salvăm PDO în sesiune (nu e serializabil la redirect)
            if (isset($pdo) && $pdo) {
                $_SESSION['install_data'][2] = [
                    'db_host' => $host,
                    'db_name' => $name,
                    'db_user' => $user,
                    'db_pass' => $pass,
                    'db_created' => $dbCreated ?? false
                ];
                
                $successMsg = 'Conexiunea la baza de date a fost stabilită cu succes.';
                if ($dbCreated ?? false) {
                    $successMsg .= ' Baza de date a fost creată automat.';
                } else {
                    $successMsg .= ' Baza de date există și este accesibilă.';
                }
                
                return ['success' => $successMsg];
            } else {
                $errors[] = 'Eroare neașteptată: Conexiunea nu a putut fi stabilită.';
                return ['errors' => $errors];
            }
        }
        
        return ['errors' => $errors];
    }
    
    /** Verifică dacă un tabel există în baza de date */
    private function tableExists(PDO $pdo, $tableName) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName));
            return $stmt && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Asigură existența tabelelor utilizatori și password_reset_tokens.
     * 1) Încearcă fișierul schema_utilizatori.sql din basePath
     * 2) Dacă tabela lipsește încă, execută CREATE TABLE inline (independent de fișier)
     * Folosit la pasul 3 și 4 ca să nu depindem de calea/locul fișierului pe hosting.
     */
    private function ensureUtilizatoriTable(PDO $pdo) {
        if ($this->tableExists($pdo, 'utilizatori')) {
            return true;
        }
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        } catch (PDOException $e) {}
        
        $this->runSchemaFile($pdo, 'schema_utilizatori.sql');
        
        if ($this->tableExists($pdo, 'utilizatori')) {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (PDOException $e) {}
            return true;
        }
        
        // Fallback: creează tabelele direct din PHP (nu depinde de fișier pe disc)
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
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (PDOException $e2) {}
            return false;
        }
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (PDOException $e) {}
        return $this->tableExists($pdo, 'utilizatori');
    }
    
    /** Rulează un singur fișier schema (pentru recuperare); returnează numărul de erori */
    private function runSchemaFile(PDO $pdo, $schemaFile) {
        $filePath = $this->basePath . '/' . $schemaFile;
        if (!file_exists($filePath)) {
            return 1;
        }
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            return 1;
        }
        $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
        $sql = preg_replace('/USE.*?;/i', '', $sql);
        $queries = array_filter(
            array_map('trim', explode(';', $sql)),
            function($q) {
                return !empty($q) && !preg_match('/^--/', $q);
            }
        );
        $errCount = 0;
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query === '') continue;
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errCount++;
                }
            }
        }
        return $errCount;
    }
    
    /** Creează conexiune PDO din credențialele salvate la pasul 2 */
    private function getPdoFromSession() {
        if (!isset($_SESSION['install_data'][2]['db_host'])) {
            return null;
        }
        $d = $_SESSION['install_data'][2];
        try {
            return new PDO(
                'mysql:host=' . $d['db_host'] . ';dbname=' . $d['db_name'] . ';charset=utf8mb4',
                $d['db_user'],
                $d['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // Pasul 3: Creare tabele
    private function step3_CreateTables() {
        $pdo = $this->getPdoFromSession();
        if (!$pdo) {
            return ['errors' => ['Configurarea bazei de date nu a fost completată. Refă pasul 2.']];
        }
        $errors = [];
        $created = 0;
        
        // Dezactivează verificarea FK temporar ca tabelele să se creeze indiferent de ordine
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        } catch (PDOException $e) {
            // Ignoră dacă serverul nu suportă
        }
        
        foreach ($this->schemaFiles as $schemaFile) {
            $filePath = $this->basePath . '/' . $schemaFile;
            if (!file_exists($filePath)) {
                continue; // Skip fișiere care nu există
            }
            
            $sql = file_get_contents($filePath);
            if ($sql === false) {
                $errors[] = "Nu s-a putut citi fișierul: {$schemaFile}";
                continue;
            }
            
            // Elimină CREATE DATABASE și USE dacă există
            $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
            $sql = preg_replace('/USE.*?;/i', '', $sql);
            
            // Împarte în query-uri (evită split în interiorul stringurilor cu ;)
            $queries = array_filter(
                array_map('trim', explode(';', $sql)),
                function($q) {
                    return !empty($q) && !preg_match('/^--/', $q);
                }
            );
            
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query === '') {
                    continue;
                }
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        $errors[] = "Eroare la {$schemaFile}: " . $e->getMessage();
                    }
                    // Continuă cu următorul statement (un eșec nu oprește tot fișierul)
                }
            }
            $created++;
        }
        
        // Obligatoriu pentru pasul 4: tabela utilizatori (fișier schema sau fallback inline)
        if (!$this->ensureUtilizatoriTable($pdo)) {
            $errors[] = 'Tabela utilizatori nu a putut fi creată. Verifică permisiunile MySQL (CREATE, ALTER) pentru baza de date.';
        }
        
        // Reactivează verificarea FK
        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (PDOException $e) {
            // Ignoră
        }
        
        $_SESSION['install_data'][3] = [
            'tables_created' => $created,
            'schema_files' => count($this->schemaFiles)
        ];
        
        if (empty($errors)) {
            return ['success' => "Au fost create tabelele din {$created} fișiere schema."];
        }
        
        return ['errors' => $errors];
    }
    
    // Pasul 4: Creare utilizator admin
    private function step4_CreateAdmin() {
        $pdo = $this->getPdoFromSession();
        if (!$pdo) {
            return ['errors' => ['Configurarea bazei de date nu a fost completată. Refă pasul 2.']];
        }
        $errors = [];
        
        // Asigură existența tabelei utilizatori (fișier schema sau fallback inline)
        if (!$this->ensureUtilizatoriTable($pdo)) {
            return ['errors' => ['Tabela utilizatori lipsește. Refă pasul 3 (Creare tabele) sau verifică permisiunile MySQL.']];
        }
        
        $username = trim($_POST['admin_username'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $email = trim($_POST['admin_email'] ?? '');
        $nume_complet = trim($_POST['admin_nume'] ?? 'Administrator');
        
        if (empty($username)) {
            $errors[] = 'Numele de utilizator este obligatoriu.';
        }
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Parola trebuie să aibă minim 6 caractere.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email valid este obligatoriu.';
        }
        
        if (empty($errors)) {
            try {
                // Verifică dacă există deja
                $stmt = $pdo->prepare("SELECT id FROM utilizatori WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    $errors[] = 'Există deja un utilizator cu acest nume.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO utilizatori (nume_complet, email, username, parola_hash, rol, activ) VALUES (?, ?, ?, ?, 'administrator', 1)");
                    $stmt->execute([$nume_complet, $email, $username, $hash]);
                    
                    $_SESSION['install_data'][4] = [
                        'admin_username' => $username,
                        'admin_email' => $email
                    ];
                    
                    return ['success' => 'Utilizatorul administrator a fost creat cu succes.'];
                }
            } catch (PDOException $e) {
                $errors[] = 'Eroare la crearea utilizatorului: ' . $e->getMessage();
            }
        }
        
        return ['errors' => $errors];
    }
    
    // Pasul 5: Configurare platformă
    private function step5_PlatformConfig() {
        if (!isset($_SESSION['install_data'][2])) {
            return ['errors' => ['Configurarea bazei de date nu a fost completată.']];
        }
        
        $base_url = trim($_POST['platform_url'] ?? '');
        $platform_name = trim($_POST['platform_name'] ?? 'CRM ANR Bihor');
        
        $errors = [];
        
        if (empty($base_url)) {
            // Încearcă să detecteze automat
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $path = str_replace('/install', '', $path);
            $base_url = $protocol . '://' . $host . $path;
        }
        
        if (!filter_var($base_url, FILTER_VALIDATE_URL)) {
            $errors[] = 'URL-ul platformei nu este valid.';
        }
        
        if (empty($errors)) {
            $_SESSION['install_data'][5] = [
                'platform_url' => rtrim($base_url, '/'),
                'platform_name' => $platform_name
            ];
            
            return ['success' => 'Configurarea platformei a fost salvată.'];
        }
        
        return ['errors' => $errors];
    }
    
    // Pasul 6: Instalare pachete Composer
    private function step6_InstallPackages() {
        $errors = [];
        
        $composerPath = $this->basePath . '/composer.json';
        if (!file_exists($composerPath)) {
            return ['success' => 'Nu există fișier composer.json. Se continuă fără instalare pachete.'];
        }
        
        // Verifică dacă Composer este disponibil
        $composerCmd = $this->findComposer();
        if (!$composerCmd) {
            return ['success' => 'Composer nu este disponibil. Pachetele pot fi instalate manual mai târziu cu: composer install'];
        }
        
        // Încearcă instalarea
        $output = [];
        $returnCode = 0;
        chdir($this->basePath);
        exec("{$composerCmd} install --no-dev --optimize-autoloader 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            $errors[] = 'Eroare la instalarea pachetelor Composer: ' . implode("\n", $output);
        } else {
            $_SESSION['install_data'][6] = [
                'composer_installed' => true
            ];
            return ['success' => 'Pachetele Composer au fost instalate cu succes.'];
        }
        
        return ['errors' => $errors];
    }
    
    // Pasul 7: Build CSS
    private function step7_BuildCSS() {
        $errors = [];
        
        $packagePath = $this->basePath . '/package.json';
        if (!file_exists($packagePath)) {
            return ['success' => 'Nu există fișier package.json. CSS-ul poate fi generat manual mai târziu.'];
        }
        
        // Verifică dacă npm/node este disponibil
        $npmCmd = $this->findNpm();
        if (!$npmCmd) {
            return ['success' => 'npm nu este disponibil. CSS-ul poate fi generat manual mai târziu cu: npm run build:css'];
        }
        
        // Instalează dependențele și build CSS
        chdir($this->basePath);
        
        // npm install
        $output = [];
        $returnCode = 0;
        exec("{$npmCmd} install 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            // npm run build:css
            exec("{$npmCmd} run build:css 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                $_SESSION['install_data'][7] = [
                    'css_built' => true
                ];
                return ['success' => 'CSS-ul a fost generat cu succes.'];
            } else {
                $errors[] = 'Eroare la generarea CSS: ' . implode("\n", array_slice($output, -5));
            }
        } else {
            $errors[] = 'Eroare la instalarea dependențelor npm: ' . implode("\n", array_slice($output, -5));
        }
        
        if (empty($errors)) {
            return ['success' => 'CSS-ul a fost generat cu succes.'];
        }
        
        return ['errors' => $errors];
    }
    
    // Pasul 8: Finalizare
    private function step8_Finalize() {
        // Creează fișierul config.php
        $configData = $this->generateConfigFile();
        $configPath = $this->basePath . '/config.php';
        
        if (file_put_contents($configPath, $configData) === false) {
            return ['errors' => ['Nu s-a putut crea fișierul config.php. Verifică permisiunile.']];
        }
        
        // Creează fișier .installed pentru a preveni reinstalarea
        @file_put_contents($this->basePath . '/.installed', date('Y-m-d H:i:s'));
        
        // Salvează setări în baza de date
        $pdo = $this->getPdoFromSession();
        if ($pdo && isset($_SESSION['install_data'][5])) {
            try {
                $platformName = $_SESSION['install_data'][5]['platform_name'] ?? 'CRM ANR Bihor';
                
                // Creează tabelul setari dacă nu există
                $pdo->exec("CREATE TABLE IF NOT EXISTS setari (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cheie VARCHAR(100) NOT NULL UNIQUE,
                    valoare TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
                $stmt = $pdo->prepare("INSERT INTO setari (cheie, valoare) VALUES (?, ?) ON DUPLICATE KEY UPDATE valoare = ?");
                $stmt->execute(['platform_name', $platformName, $platformName]);
            } catch (PDOException $e) {
                // Nu e critic, continuă
            }
        }
        
        // Șterge datele din sesiune
        unset($_SESSION['install_step']);
        unset($_SESSION['install_data']);
        
        return ['success' => 'Instalarea a fost finalizată cu succes!'];
    }
    
    private function generateConfigFile() {
        $dbData = $_SESSION['install_data'][2] ?? [];
        $platformData = $_SESSION['install_data'][5] ?? [];
        
        $dbHost = addslashes($dbData['db_host'] ?? 'localhost');
        $dbName = addslashes($dbData['db_name'] ?? '');
        $dbUser = addslashes($dbData['db_user'] ?? '');
        $dbPass = addslashes($dbData['db_pass'] ?? '');
        $platformUrl = addslashes($platformData['platform_url'] ?? '');
        $platformName = addslashes($platformData['platform_name'] ?? 'CRM ANR Bihor');
        
        return <<<PHP
<?php
/**
 * Configurare CRM ANR - Asociația Nevăzătorilor Bihor
 * Generat automat de installer
 */

if (!defined('PLATFORM_NAME')) {
    define('PLATFORM_NAME', '{$platformName}');
}
if (!defined('PLATFORM_LOGO_URL')) {
    define('PLATFORM_LOGO_URL', 'https://anrbihor.ro/wp-content/uploads/2023/08/logo-anr-site-test1.png');
}
if (!defined('PLATFORM_BASE_URL')) {
    define('PLATFORM_BASE_URL', '{$platformUrl}');
}

define('DATE_FORMAT', 'd.m.Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd.m.Y H:i');

if (!defined('CRON_NEWSLETTER_KEY')) {
    define('CRON_NEWSLETTER_KEY', '');
}

if (!defined('CSRF_ENABLED')) {
    define('CSRF_ENABLED', true);
}

define('DB_HOST', '{$dbHost}');
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');

try {
    \$pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    try {
        \$stmt = \$pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
        \$stmt->execute(['platform_name']);
        \$platform_name = \$stmt->fetchColumn();
        if (\$platform_name) {
            if (!function_exists('get_platform_name')) {
                function get_platform_name() {
                    global \$pdo;
                    try {
                        \$stmt = \$pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
                        \$stmt->execute(['platform_name']);
                        \$name = \$stmt->fetchColumn();
                        return \$name ?: PLATFORM_NAME;
                    } catch (Exception \$e) {
                        return PLATFORM_NAME;
                    }
                }
            }
        }
    } catch (Exception \$e) {}
} catch (PDOException \$e) {
    die('Eroare conexiune bază de date: ' . \$e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    \$script = basename(\$_SERVER['SCRIPT_NAME'] ?? '');
    if (\$script === 'login.php' && \$_SERVER['REQUEST_METHOD'] === 'POST' && !empty(\$_POST['remember_me'])) {
        session_set_cookie_params([
            'lifetime' => 30 * 24 * 3600,
            'path' => '/',
            'secure' => !empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

require_once __DIR__ . '/includes/auth_helper.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/platform_helper.php';
\$script = basename(\$_SERVER['SCRIPT_NAME'] ?? '');
if (!in_array(\$script, auth_pagini_publice(), true)) {
    require_login();
}
PHP;
    }
    
    private function findComposer() {
        $commands = ['composer', 'composer.phar', 'php composer.phar'];
        foreach ($commands as $cmd) {
            $output = [];
            $returnCode = 0;
            exec("{$cmd} --version 2>&1", $output, $returnCode);
            if ($returnCode === 0) {
                return $cmd;
            }
        }
        return null;
    }
    
    private function findNpm() {
        $commands = ['npm', 'node npm'];
        foreach ($commands as $cmd) {
            $output = [];
            $returnCode = 0;
            exec("{$cmd} --version 2>&1", $output, $returnCode);
            if ($returnCode === 0) {
                return $cmd;
            }
        }
        return null;
    }
}
