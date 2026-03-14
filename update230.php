<?php
/**
 * Update setup pentru modulul Formular 230.
 *
 * - Creează tabelele necesare:
 *   - formular230_persoane
 *   - formular230_ani
 *   - formular230_log
 * - Adaugă coloanele de notificare expirare CI/CH în tabelul membri, dacă lipsesc:
 *   - expira_ci_notificat TINYINT(1)
 *   - expira_ch_notificat TINYINT(1)
 *
 * Utilizare:
 *   1. Încarcă acest fișier pe hosting, în același director cu config.php.
 *   2. Autentifică-te în CRM ca administrator.
 *   3. Accesează în browser: /update_setup_formular230.php
 *   4. După executare cu succes, ȘTERGE acest fișier de pe server.
 */

require_once __DIR__ . '/config.php';

if (function_exists('require_admin')) {
    require_admin();
}

header('Content-Type: text/html; charset=utf-8');

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = [];

try {
    // 1) Tabela principală: formular230_persoane
    $sqlPersoane = "
        CREATE TABLE IF NOT EXISTS formular230_persoane (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nume VARCHAR(100) NOT NULL,
            initiala_tatalui VARCHAR(5) DEFAULT NULL,
            prenume VARCHAR(100) NOT NULL,
            cnp VARCHAR(13) NOT NULL,
            strada VARCHAR(255) DEFAULT NULL,
            nr VARCHAR(10) DEFAULT NULL,
            bl VARCHAR(10) DEFAULT NULL,
            sc VARCHAR(10) DEFAULT NULL,
            et VARCHAR(10) DEFAULT NULL,
            ap VARCHAR(10) DEFAULT NULL,
            localitatea VARCHAR(100) DEFAULT NULL,
            judet VARCHAR(100) DEFAULT NULL,
            cod_postal VARCHAR(10) DEFAULT NULL,
            telefon VARCHAR(50) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            ani_formular TEXT DEFAULT NULL,
            status ENUM('Activ','Inactiv') NOT NULL DEFAULT 'Activ',
            canal_tiparit TINYINT(1) NOT NULL DEFAULT 0,
            canal_online TINYINT(1) NOT NULL DEFAULT 0,
            canal_campanie TINYINT(1) NOT NULL DEFAULT 0,
            canal_altele TINYINT(1) NOT NULL DEFAULT 0,
            bifat_an_recent TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cnp (cnp),
            INDEX idx_nume_prenume (nume, prenume),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sqlPersoane);
    $success[] = 'Tabela formular230_persoane a fost creată/verificată.';

    // 2) Tabela formular230_ani
    $sqlAni = "
        CREATE TABLE IF NOT EXISTS formular230_ani (
            id INT AUTO_INCREMENT PRIMARY KEY,
            an SMALLINT UNSIGNED NOT NULL UNIQUE,
            ordine SMALLINT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sqlAni);
    $success[] = 'Tabela formular230_ani a fost creată/verificată.';

    // Populare ani de bază (2022–2025) dacă nu există niciun rând
    $stmt = $pdo->query("SELECT COUNT(*) FROM formular230_ani");
    $countAni = (int)($stmt ? $stmt->fetchColumn() : 0);
    if ($countAni === 0) {
        $aniInitiali = [2022, 2023, 2024, 2025];
        $ordine = count($aniInitiali);
        $ins = $pdo->prepare("INSERT INTO formular230_ani (an, ordine) VALUES (?, ?)");
        foreach ($aniInitiali as $an) {
            $ins->execute([$an, $ordine--]);
        }
        $success[] = 'Anii 2022–2025 au fost adăugați în formular230_ani.';
    }

    // 3) Tabela formular230_log
    $sqlLog = "
        CREATE TABLE IF NOT EXISTS formular230_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            utilizator VARCHAR(100) DEFAULT NULL,
            actiune TEXT NOT NULL,
            persoana_id INT DEFAULT NULL,
            INDEX idx_data_ora (data_ora),
            INDEX idx_persoana (persoana_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $pdo->exec($sqlLog);
    $success[] = 'Tabela formular230_log a fost creată/verificată.';

    // 4) Adaugă coloanele de notificare în membri
    $colsMembri = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('expira_ci_notificat', $colsMembri, true)) {
        $pdo->exec("ALTER TABLE membri ADD COLUMN expira_ci_notificat TINYINT(1) NOT NULL DEFAULT 0 AFTER ceexp");
        $success[] = 'Coloana membri.expira_ci_notificat a fost adăugată.';
    }
    if (!in_array('expira_ch_notificat', $colsMembri, true)) {
        $pdo->exec("ALTER TABLE membri ADD COLUMN expira_ch_notificat TINYINT(1) NOT NULL DEFAULT 0 AFTER expira_ci_notificat");
        $success[] = 'Coloana membri.expira_ch_notificat a fost adăugată.';
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <title>Update setup Formular 230</title>
    <link href="css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-slate-50 dark:bg-gray-900 text-slate-900 dark:text-gray-100">
<main class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Update setup – Formular 230</h1>
    <?php if ($errors): ?>
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded">
            <p class="font-medium text-red-800 dark:text-red-200">Au apărut erori:</p>
            <ul class="mt-2 list-disc pl-5 text-sm">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo h($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 rounded">
            <p class="font-medium text-emerald-800 dark:text-emerald-200">Setup rulat cu succes.</p>
            <ul class="mt-2 list-disc pl-5 text-sm">
                <?php foreach ($success as $s): ?>
                    <li><?php echo h($s); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <p class="text-sm text-slate-700 dark:text-gray-300 mt-4">
        După ce verifici că modulul <strong>Formular 230</strong> funcționează, este recomandat să ștergi acest fișier
        (<code>update_setup_formular230.php</code>) de pe server din motive de securitate.
    </p>

    <p class="mt-4">
        <a href="formular-230.php" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium">
            Mergi la modulul Formular 230
        </a>
        <a href="index.php" class="ml-2 inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-800 font-medium">
            Înapoi la Dashboard
        </a>
    </p>
</main>
</body>
</html>

