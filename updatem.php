<?php
/**
 * Update DB – Adăugare coloane suplimentare în tabelul `membri`
 *
 * Coloane cerute:
 *  - insotitor           (dacă are însoțitor / statut însoțitor)
 *  - nume_apartinator    (nume apartinator)
 *  - prenume_apartinator (prenume apartinator)
 *  - grad_handicap       (text grad handicap – complementar lui hgrad ENUM)
 *  - surse_venit         (surse de venit)
 *  - tara_nastere        (țara nașterii – complementar lui locnastere/judnastere)
 *  - diagnostic          (diagnostic detaliat)
 *
 * Utilizare:
 *  1. Urcă acest fișier în rădăcina proiectului pe hosting.
 *  2. Autentifică-te ca administrator.
 *  3. Accesează în browser: /update_membri_adauga_coloane_noi.php
 *  4. Apasă „Rulează actualizarea”.
 *  5. După succes, ȘTERGE acest fișier de pe server (securitate).
 */

require_once __DIR__ . '/config.php';

// Doar administratorii ar trebui să poată rula scriptul
if (function_exists('require_admin')) {
    require_admin();
}

header('Content-Type: text/html; charset=utf-8');

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function db_current(PDO $pdo): string {
    try {
        $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        return (string)($db ?: '');
    } catch (Throwable $e) {
        return '';
    }
}

function table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        return $stmt && $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $sql = "SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$db = db_current($pdo);
$run = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_update']));
$errors = [];
$success = [];
$warnings = [];

if ($run) {
    try {
        if (!table_exists($pdo, 'membri')) {
            $errors[] = "Tabelul `membri` nu există în baza de date „" . ($db ?: (defined('DB_NAME') ? DB_NAME : '')) . "”. Rulează mai întâi `schema.sql`.";
        } else {
            // 1) insotitor – flag/descriere însoțitor
            if (!column_exists($pdo, 'membri', 'insotitor')) {
                // folosim TINYINT(1) pentru flag simplu (0/1)
                $pdo->exec("ALTER TABLE membri ADD COLUMN insotitor TINYINT(1) NOT NULL DEFAULT 0 AFTER hgrad");
                $success[] = "Coloana `insotitor` a fost adăugată (TINYINT(1), implicit 0).";
            }

            // 2) nume_apartinator / prenume_apartinator – lângă telefonapartinator
            if (!column_exists($pdo, 'membri', 'nume_apartinator')) {
                $pdo->exec("ALTER TABLE membri ADD COLUMN nume_apartinator VARCHAR(100) DEFAULT NULL AFTER telefonapartinator");
                $success[] = "Coloana `nume_apartinator` a fost adăugată (VARCHAR(100)).";
            }
            if (!column_exists($pdo, 'membri', 'prenume_apartinator')) {
                $pdo->exec("ALTER TABLE membri ADD COLUMN prenume_apartinator VARCHAR(100) DEFAULT NULL AFTER nume_apartinator");
                $success[] = "Coloana `prenume_apartinator` a fost adăugată (VARCHAR(100)).";
            }

            // 3) grad_handicap – text liber, complementar lui hgrad ENUM
            if (!column_exists($pdo, 'membri', 'grad_handicap')) {
                $pdo->exec("ALTER TABLE membri ADD COLUMN grad_handicap VARCHAR(100) DEFAULT NULL AFTER hgrad");
                $success[] = "Coloana `grad_handicap` a fost adăugată (VARCHAR(100)).";
            } else {
                $warnings[] = "Coloana `grad_handicap` există deja – nu a fost modificată.";
            }

            // 4) surse_venit – sursele de venit (liste / text)
            if (!column_exists($pdo, 'membri', 'surse_venit')) {
                $pdo->exec("ALTER TABLE membri ADD COLUMN surse_venit VARCHAR(255) DEFAULT NULL AFTER grad_handicap");
                $success[] = "Coloana `surse_venit` a fost adăugată (VARCHAR(255)).";
            }

            // 5) tara_nastere – țara nașterii (complementar locnastere/judnastere)
            if (!column_exists($pdo, 'membri', 'tara_nastere')) {
                $pdo->exec("ALTER TABLE membri ADD COLUMN tara_nastere VARCHAR(100) DEFAULT NULL AFTER judnastere");
                $success[] = "Coloana `tara_nastere` a fost adăugată (VARCHAR(100)).";
            }

            // 6) diagnostic – text mai lung
            if (!column_exists($pdo, 'membri', 'diagnostic')) {
                $pdo->exec("ALTER TABLE membri ADD COLUMN diagnostic TEXT DEFAULT NULL AFTER notamembru");
                $success[] = "Coloana `diagnostic` a fost adăugată (TEXT).";
            }
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update DB: Adăugare coloane noi în membri</title>
    <link href="css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-slate-50 dark:bg-gray-900 text-slate-900 dark:text-gray-100">
<main id="main-content" class="max-w-3xl mx-auto p-6">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold">Update DB: coloane suplimentare în tabelul <code class="px-1 rounded bg-slate-200 dark:bg-gray-800">membri</code></h1>
        <p class="mt-2 text-sm text-slate-700 dark:text-gray-300">
            Baza de date curentă (din conexiunea aplicației): <strong><?php echo h($db ?: (defined('DB_NAME') ? DB_NAME : '')); ?></strong>
        </p>
    </header>

    <section class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded" role="alert" aria-live="polite">
        <p class="font-medium">Securitate:</p>
        <p class="text-sm mt-1">
            După ce rulezi cu succes acest update, <strong>șterge fișierul</strong>
            <code class="px-1 rounded bg-amber-100 dark:bg-amber-800">update_membri_adauga_coloane_noi.php</code> de pe server.
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
                Acest update va adăuga, dacă lipsesc, următoarele coloane în tabelul <code class="px-1 rounded bg-slate-100 dark:bg-gray-700">membri</code>:
            </p>
            <ul class="mt-2 list-disc pl-5 text-sm text-slate-700 dark:text-gray-300">
                <li><code>insotitor</code> (TINYINT(1), implicit 0) – marcare însoțitor</li>
                <li><code>nume_apartinator</code> (VARCHAR(100)) – nume apartinator</li>
                <li><code>prenume_apartinator</code> (VARCHAR(100)) – prenume apartinator</li>
                <li><code>grad_handicap</code> (VARCHAR(100)) – text grad handicap</li>
                <li><code>surse_venit</code> (VARCHAR(255)) – surse de venit</li>
                <li><code>tara_nastere</code> (VARCHAR(100)) – țara nașterii</li>
                <li><code>diagnostic</code> (TEXT) – diagnostic detaliat</li>
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

