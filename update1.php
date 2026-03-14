<?php
/**
 * Update: repară lipsa tabelului `membri` + coloane necesare.
 *
 * Scop: rezolvă eroarea din `membri.php` când tabelul `membri` nu există
 * sau lipsește coloane precum `status_dosar`, `cidataexp`, `judet_domiciliu`.
 *
 * Utilizare:
 * - Accesează în browser: /update_fix_membri_table.php
 * - Apasă „Rulează actualizarea”
 * - DUPĂ succes: șterge fișierul de pe server (securitate)
 */
require_once __DIR__ . '/config.php';

// Doar administratorii pot rula update-ul
if (function_exists('require_admin')) {
    require_admin();
}

header('Content-Type: text/html; charset=utf-8');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function db_current(PDO $pdo): string {
    try {
        $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        return (string)($db ?: '');
    } catch (Exception $e) {
        return '';
    }
}

function table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return $stmt && $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function column_info(PDO $pdo, string $table, string $column): ?array {
    $sql = "SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, CHARACTER_MAXIMUM_LENGTH
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table, $column]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function index_exists(PDO $pdo, string $table, string $indexName): bool {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1");
        $stmt->execute([$table, $indexName]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function any_index_on_column(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function max_len(PDO $pdo, string $table, string $column): ?int {
    try {
        $stmt = $pdo->query("SELECT MAX(CHAR_LENGTH(`{$column}`)) FROM `{$table}`");
        $v = $stmt ? $stmt->fetchColumn() : null;
        if ($v === null) return null;
        return (int)$v;
    } catch (PDOException $e) {
        return null;
    }
}

$db = db_current($pdo);
$run = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_update']));
$errors = [];
$success = [];
$warnings = [];

if ($run) {
    try {
        // 1) Creează tabelul dacă lipsește (rezolvă direct eroarea 42S02)
        if (!table_exists($pdo, 'membri')) {
            $createSql = "CREATE TABLE IF NOT EXISTS membri (
                id INT AUTO_INCREMENT PRIMARY KEY,
                dosarnr VARCHAR(50) DEFAULT NULL,
                dosardata DATE DEFAULT NULL,
                status_dosar ENUM('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat') DEFAULT 'Activ',
                nume VARCHAR(100) NOT NULL,
                prenume VARCHAR(100) NOT NULL,
                telefonnev VARCHAR(20) DEFAULT NULL,
                telefonapartinator VARCHAR(20) DEFAULT NULL,
                email VARCHAR(255) DEFAULT NULL,
                datanastere DATE DEFAULT NULL,
                locnastere VARCHAR(100) DEFAULT NULL,
                judnastere VARCHAR(50) DEFAULT NULL,
                ciseria VARCHAR(2) DEFAULT NULL,
                cinumar VARCHAR(7) DEFAULT NULL,
                cielib VARCHAR(100) DEFAULT NULL,
                cidataelib DATE DEFAULT NULL,
                cidataexp DATE DEFAULT NULL,
                gdpr TINYINT(1) DEFAULT 0,
                codpost VARCHAR(10) DEFAULT NULL,
                tipmediuur ENUM('Urban', 'Rural') DEFAULT NULL,
                domloc VARCHAR(100) DEFAULT NULL,
                judet_domiciliu VARCHAR(50) DEFAULT NULL,
                domstr VARCHAR(100) DEFAULT NULL,
                domnr VARCHAR(20) DEFAULT NULL,
                dombl VARCHAR(20) DEFAULT NULL,
                domsc VARCHAR(10) DEFAULT NULL,
                domet VARCHAR(10) DEFAULT NULL,
                domap VARCHAR(10) DEFAULT NULL,
                sex ENUM('Masculin', 'Feminin') DEFAULT NULL,
                hgrad ENUM('Grav cu insotitor', 'Grav', 'Accentuat', 'Mediu', 'Usor', 'Alt handicap', 'Asociat', 'Fara handicap') DEFAULT NULL,
                hmotiv VARCHAR(255) DEFAULT NULL,
                hdur ENUM('Permanent', 'Revizuibil') DEFAULT NULL,
                cnp VARCHAR(13) NOT NULL,
                cenr VARCHAR(50) DEFAULT NULL,
                cedata DATE DEFAULT NULL,
                ceexp DATE DEFAULT NULL,
                primaria VARCHAR(255) DEFAULT NULL,
                doc_ci VARCHAR(255) DEFAULT NULL,
                doc_ch VARCHAR(255) DEFAULT NULL,
                notamembru TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_cnp (cnp),
                INDEX idx_dosarnr (dosarnr),
                INDEX idx_nume_prenume (nume, prenume),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($createSql);
            $success[] = "Tabelul `membri` a fost creat.";
        } else {
            $success[] = "Tabelul `membri` există deja.";
        }

        // 2) Asigură coloanele necesare pentru modulul Membri (compatibil și cu tabele vechi)
        if (!column_info($pdo, 'membri', 'status_dosar')) {
            $pdo->exec("ALTER TABLE membri ADD COLUMN status_dosar ENUM('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat') DEFAULT 'Activ' AFTER dosardata");
            $success[] = "Coloana `status_dosar` a fost adăugată.";
        }
        if (!column_info($pdo, 'membri', 'cidataexp')) {
            $pdo->exec("ALTER TABLE membri ADD COLUMN cidataexp DATE DEFAULT NULL AFTER cidataelib");
            $success[] = "Coloana `cidataexp` a fost adăugată.";
        }
        if (!column_info($pdo, 'membri', 'judet_domiciliu')) {
            $pdo->exec("ALTER TABLE membri ADD COLUMN judet_domiciliu VARCHAR(50) DEFAULT NULL AFTER domloc");
            $success[] = "Coloana `judet_domiciliu` a fost adăugată.";
        }

        // 3) `hmotiv`: dacă e TEXT, trecere la VARCHAR(255) doar dacă e sigur (fără trunchiere)
        $hm = column_info($pdo, 'membri', 'hmotiv');
        if ($hm && strtolower((string)$hm['DATA_TYPE']) === 'text') {
            $ml = max_len($pdo, 'membri', 'hmotiv');
            if ($ml !== null && $ml > 255) {
                $warnings[] = "Coloana `hmotiv` este TEXT și are valori până la {$ml} caractere. Nu am convertit la VARCHAR(255) ca să evit trunchierea. (Recomandare: măriți limita sau curățați datele.)";
            } else {
                $pdo->exec("ALTER TABLE membri MODIFY COLUMN hmotiv VARCHAR(255) DEFAULT NULL");
                $success[] = "Coloana `hmotiv` a fost convertită la VARCHAR(255).";
            }
        }

        // 4) Indexuri utile (fără a forța UNIQUE care poate eșua dacă există duplicate)
        if (!index_exists($pdo, 'membri', 'idx_dosarnr')) {
            $pdo->exec("ALTER TABLE membri ADD INDEX idx_dosarnr (dosarnr)");
            $success[] = "Index `idx_dosarnr` a fost adăugat.";
        }
        if (!any_index_on_column($pdo, 'membri', 'cnp')) {
            $pdo->exec("ALTER TABLE membri ADD INDEX idx_cnp (cnp)");
            $success[] = "Index `idx_cnp` a fost adăugat.";
        }
        if (!index_exists($pdo, 'membri', 'idx_nume_prenume')) {
            $pdo->exec("ALTER TABLE membri ADD INDEX idx_nume_prenume (nume, prenume)");
            $success[] = "Index `idx_nume_prenume` a fost adăugat.";
        }
        if (!index_exists($pdo, 'membri', 'idx_email')) {
            $pdo->exec("ALTER TABLE membri ADD INDEX idx_email (email)");
            $success[] = "Index `idx_email` a fost adăugat.";
        }

        // 5) Normalizează status_dosar la valori acceptate (nu forțăm MODIFY ENUM aici; doar date)
        if (column_info($pdo, 'membri', 'status_dosar')) {
            $pdo->exec("UPDATE membri SET status_dosar = 'Activ' WHERE status_dosar IS NULL OR status_dosar NOT IN ('Activ', 'Expirat', 'Suspendat', 'Retras', 'Decedat')");
            $success[] = "Status-urile lipsă/vechi au fost normalizate la `Activ` (unde a fost cazul).";
        }
    } catch (PDOException $e) {
        $errors[] = $e->getMessage();
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update DB: Fix tabel membri</title>
    <link href="css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-slate-50 dark:bg-gray-900 text-slate-900 dark:text-gray-100">
<main id="main-content" class="max-w-3xl mx-auto p-6">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold">Update DB: repară tabelul <code class="px-1 rounded bg-slate-200 dark:bg-gray-800">membri</code></h1>
        <p class="mt-2 text-sm text-slate-700 dark:text-gray-300">
            Baza de date curentă (din conexiunea aplicației): <strong><?php echo h($db ?: (defined('DB_NAME') ? DB_NAME : '')); ?></strong>
        </p>
    </header>

    <section class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded" role="alert" aria-live="polite">
        <p class="font-medium">Securitate:</p>
        <p class="text-sm mt-1">
            După ce rulezi cu succes acest update, <strong>șterge fișierul</strong> <code class="px-1 rounded bg-amber-100 dark:bg-amber-800">update_fix_membri_table.php</code> de pe server.
        </p>
    </section>

    <?php if ($run): ?>
        <?php if (!empty($errors)): ?>
            <section class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded" role="alert" aria-live="assertive">
                <p class="font-semibold text-red-800 dark:text-red-200">Au apărut erori:</p>
                <ul class="mt-2 list-disc pl-5 text-sm text-red-800 dark:text-red-200">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo h($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (!empty($warnings)): ?>
            <section class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded" role="status" aria-live="polite">
                <p class="font-semibold text-amber-900 dark:text-amber-200">Atenționări:</p>
                <ul class="mt-2 list-disc pl-5 text-sm text-amber-900 dark:text-amber-200">
                    <?php foreach ($warnings as $w): ?>
                        <li><?php echo h($w); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <section class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded" role="status" aria-live="polite">
                <p class="font-semibold text-emerald-800 dark:text-emerald-200">Rezultat:</p>
                <ul class="mt-2 list-disc pl-5 text-sm text-emerald-800 dark:text-emerald-200">
                    <?php foreach ($success as $s): ?>
                        <li><?php echo h($s); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <p class="mt-4">
            <a class="inline-flex items-center gap-2 px-3 py-2 rounded border border-slate-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
               href="membri.php">
                Mergi la „Membri”
            </a>
            <a class="ml-2 inline-flex items-center gap-2 px-3 py-2 rounded border border-slate-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
               href="index.php">
                Dashboard
            </a>
        </p>
    <?php else: ?>
        <section class="mb-6 p-4 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded">
            <p class="text-sm text-slate-700 dark:text-gray-300">
                Acest update va:
            </p>
            <ul class="mt-2 list-disc pl-5 text-sm text-slate-700 dark:text-gray-300">
                <li>Creeze tabelul <code class="px-1 rounded bg-slate-100 dark:bg-gray-700">membri</code> dacă lipsește</li>
                <li>Adauge coloanele lipsă (<code class="px-1 rounded bg-slate-100 dark:bg-gray-700">status_dosar</code>, <code class="px-1 rounded bg-slate-100 dark:bg-gray-700">cidataexp</code>, <code class="px-1 rounded bg-slate-100 dark:bg-gray-700">judet_domiciliu</code>)</li>
                <li>Asigure indexuri utile pentru căutare/filtrare</li>
            </ul>
        </section>

        <form method="post" action="" class="flex items-center gap-3">
            <button type="submit"
                    name="run_update"
                    value="1"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                    aria-label="Rulează actualizarea bazei de date pentru tabelul membri">
                Rulează actualizarea
            </button>
            <a href="index.php" class="text-sm text-slate-700 dark:text-gray-300 underline hover:text-amber-700 dark:hover:text-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded">
                Renunță
            </a>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
