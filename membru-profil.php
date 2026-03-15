<?php
/**
 * Pagină Profil Membru - CRM ANR Bihor
 * Afișează și permite editarea tuturor datelor unui membru
 */
// Activează output buffering pentru a preveni probleme cu redirect-uri
ob_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/liste_helper.php';

// Scrie mesaje de debug într-un fișier din proiect (nu depinde de php.ini / Apache)
function debug_profil_log($mesaj) {
    $line = date('Y-m-d H:i:s') . ' ' . $mesaj . "\n";
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $f = $dir . DIRECTORY_SEPARATOR . 'debug-profil.txt';
    if (!@file_put_contents($f, $line, FILE_APPEND | LOCK_EX)) {
        // Fallback: scrie în rădăcina proiectului
        @file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug-profil-last.txt', $line, FILE_APPEND | LOCK_EX);
    }
}
require_once 'includes/log_helper.php';
require_once 'includes/cnp_validator.php';
require_once 'includes/file_helper.php';
require_once 'includes/membri_alerts.php';
require_once 'includes/cotizatii_helper.php';
require_once 'includes/incasari_helper.php';
require_once 'membri_processing.php';

$eroare = '';
$succes = '';
$membru = null;
$membru_id = (int)($_GET['id'] ?? 0);

if ($membru_id <= 0) {
    header('Location: membri.php');
    exit;
}

// Procesare marcare / debifare avertisment „Membru informat” (ÎNAINTE de header.php pentru redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcheaza_alert_informat'])) {
    csrf_require_valid();
    $alert_tip = trim($_POST['alert_tip'] ?? '');
    $membru_id_alert = (int)($_POST['membru_id'] ?? 0);
    $debifa = empty($_POST['marcat_informat']); // checkbox debifat = nu e în POST

    if ($membru_id_alert > 0 && in_array($alert_tip, ['ci', 'ch', 'cotizatie'])) {
        try {
            if ($debifa) {
                // Debifare: șterge marcarea „Membru informat”
                $stmt = $pdo->prepare('DELETE FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
                $stmt->execute([$membru_id_alert, $alert_tip]);
                if ($stmt->rowCount() > 0) {
                    log_activitate($pdo, "membri: Avertisment {$alert_tip} debifat (membru nu mai e marcat ca informat) pentru membru ID {$membru_id_alert}");
                }
            } else {
                // Bifare: marcare „Membru informat”
                $pdo->exec("CREATE TABLE IF NOT EXISTS membri_alerts_dismissed (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    membru_id INT NOT NULL,
                    alert_tip VARCHAR(10) NOT NULL,
                    data_informat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_membru_alert (membru_id, alert_tip),
                    FOREIGN KEY (membru_id) REFERENCES membri(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $stmt_check = $pdo->prepare('SELECT id FROM membri_alerts_dismissed WHERE membru_id = ? AND alert_tip = ?');
                $stmt_check->execute([$membru_id_alert, $alert_tip]);

                if (!$stmt_check->fetch()) {
                    $stmt = $pdo->prepare('INSERT INTO membri_alerts_dismissed (membru_id, alert_tip) VALUES (?, ?)');
                    $stmt->execute([$membru_id_alert, $alert_tip]);
                    log_activitate($pdo, "membri: Avertisment {$alert_tip} marcat ca informat pentru membru ID {$membru_id_alert}");
                }
            }

            header('Location: membru-profil.php?id=' . $membru_id_alert);
            exit;
        } catch (PDOException $e) {
            $eroare = $debifa ? 'Eroare la debifarea avertismentului.' : 'Eroare la marcarea avertismentului.';
            $eroare .= ' ' . $e->getMessage();
        }
    }
}

// Procesare actualizare membru (ÎNAINTE de header.php pentru redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_membru'])) {
    debug_profil_log('--- POST Salvare primit (înainte de CSRF) ---');
    csrf_require_valid();
    $_POST['membru_id'] = $membru_id;
    
    // Log pentru debugging (și în fișier din proiect: logs/debug-profil.txt sau debug-profil-last.txt)
    $msg_post = 'POST primit - membru_id=' . $membru_id . ', gdpr=' . ($_POST['gdpr'] ?? 'nu setat') . ', nume=' . ($_POST['nume'] ?? '') . ', prenume=' . ($_POST['prenume'] ?? '') . ', cnp=' . substr($_POST['cnp'] ?? '', 0, 3) . '...';
    error_log('DEBUG membru-profil: ' . $msg_post);
    debug_profil_log('POST: ' . $msg_post);
    
    try {
        $result = proceseaza_formular_membru($pdo, $_POST, $_FILES);
        
        if ($result['success']) {
            error_log('DEBUG membru-profil: Salvare reușită pentru membru_id=' . $membru_id);
            debug_profil_log('Salvare REUȘITĂ pentru membru_id=' . $membru_id);
            while (ob_get_level()) {
                ob_end_clean();
            }
            $redirect_url = 'membru-profil.php?id=' . $membru_id . '&succes=1';
            if (!headers_sent()) {
                header('Location: ' . $redirect_url);
                exit;
            }
            // Fallback dacă header()-ul a eșuat (output trimis înainte): log diagnostic + pagină minimă
            debug_profil_log('SUCCESS dar redirect eșuat – headers_sent');
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><p>Redirecționare…</p><script>location.replace(' . json_encode($redirect_url) . ');</script></body></html>';
            exit;
        } else {
            $eroare = $result['error'] ?? 'Eroare necunoscută la salvare';
            error_log('DEBUG membru-profil: Eroare la salvare - ' . $eroare);
            debug_profil_log('EROARE la salvare: ' . $eroare);
            if (ob_get_level() > 0) {
                ob_clean();
            }
        }
    } catch (Exception $e) {
        $eroare = 'Eroare neașteptată: ' . $e->getMessage();
        error_log('DEBUG membru-profil: Excepție - ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        debug_profil_log('EXCEPȚIE: ' . $e->getMessage());
        if (ob_get_level() > 0) {
            ob_clean();
        }
    }
}

// Dacă am fost pe POST (salvare) dar nu avem nici eroare nici redirect, forțăm un mesaj vizibil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_membru']) && $eroare === '' && !isset($_GET['succes'])) {
    $eroare = 'Salvarea nu a reușit. Verifică datele (nume, prenume, CNP, email) sau contactează administratorul. Verifică fișierul logs/debug-profil.txt din proiect.';
    error_log('DEBUG membru-profil: POST fără eroare setată – afișăm mesaj generic');
    debug_profil_log('POST fără eroare setată – afișăm mesaj generic');
}

// Încărcare date membru ÎNAINTE de orice output (pentru redirect corect când membru nu există)
$scutire_cotizatie = null;
$cotizatie_achitata_an_curent = false;
$valoare_cotizatie_an = 0;
try {
    $stmt = $pdo->prepare('SELECT * FROM membri WHERE id = ?');
    $stmt->execute([$membru_id]);
    $membru = $stmt->fetch();
    if (!$membru) {
        while (ob_get_level()) { ob_end_clean(); }
        header('Location: membri.php');
        exit;
    }
    try {
        $scutire_cotizatie = cotizatii_membru_este_scutit($pdo, $membru_id);
    } catch (Exception $e) {}
    try {
        $cotizatie_achitata_an_curent = !empty($scutire_cotizatie) || incasari_cotizatie_achitata_an($pdo, $membru_id, (int)date('Y'));
        $valoare_cotizatie_an = incasari_valoare_cotizatie_anuala($pdo, (int)date('Y'), $membru['hgrad'] ?? 'Fara handicap');
    } catch (Exception $e) {}
} catch (PDOException $e) {
    $eroare = 'Eroare la încărcarea datelor: ' . $e->getMessage();
    $membru = null;
}

// Include header și sidebar DUPĂ încărcarea membrului (redirect-ul la „membru negăsit” funcționează acum)
include 'header.php';
include 'sidebar.php';

// Afișare mesaj succes
if (isset($_GET['succes']) && $_GET['succes'] == '1') {
    $succes = 'Datele membrului au fost actualizate cu succes.';
}

// calculeaza_varsta() este furnizată de includes/liste_helper.php

$varsta = $membru ? calculeaza_varsta($membru['datanastere'] ?? null) : null;

// Istoric modificări pentru membru (log activitate)
$istoric_modificari = [];
if ($membru) {
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM log_activitate")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('membru_id', $cols, true)) {
            $stmt_log = $pdo->prepare("
                SELECT data_ora, utilizator, actiune 
                FROM log_activitate 
                WHERE membru_id = ? 
                ORDER BY data_ora DESC 
                LIMIT 50
            ");
            $stmt_log->execute([$membru_id]);
            $istoric_modificari = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Fallback pentru instalații vechi: filtrare după numele membrului în descriere
            $nume_complet = trim(($membru['nume'] ?? '') . ' ' . ($membru['prenume'] ?? ''));
            if ($nume_complet !== '') {
                $stmt_log = $pdo->prepare("
                    SELECT data_ora, utilizator, actiune 
                    FROM log_activitate 
                    WHERE actiune LIKE ? 
                    ORDER BY data_ora DESC 
                    LIMIT 50
                ");
                $stmt_log->execute(['%' . $nume_complet . '%']);
                $istoric_modificari = $stmt_log->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        $istoric_modificari = [];
    }
}
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <?php if (!$membru): ?>
    <div class="p-6">
        <div class="max-w-xl p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <p class="font-semibold">Membru negăsit sau eroare la încărcarea datelor.</p>
            <?php if (!empty($eroare)): ?>
            <p class="mt-2 text-sm"><?php echo htmlspecialchars($eroare); ?></p>
            <?php endif; ?>
        </div>
        <p class="mt-4">
            <a href="membri.php" class="inline-flex items-center gap-2 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <i data-lucide="arrow-left" class="w-4 h-4" aria-hidden="true"></i>
                Înapoi la lista de membri
            </a>
        </p>
    </div>
    <?php else: ?>
    <?php if (!empty($eroare)): ?>
    <div id="banner-eroare-salvare" class="fixed top-0 left-0 right-0 z-[100] px-4 py-3 bg-red-600 text-white shadow-lg flex items-center justify-between gap-4" role="alert" aria-live="assertive">
        <div class="flex-1 min-w-0">
            <p class="font-semibold">Salvare eșuată:</p>
            <p class="text-sm opacity-95 truncate" title="<?php echo htmlspecialchars($eroare); ?>"><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <button type="button" onclick="document.getElementById('banner-eroare-salvare').remove()" class="flex-shrink-0 px-3 py-1 bg-red-700 hover:bg-red-800 rounded focus:outline-none focus:ring-2 focus:ring-white" aria-label="Închide mesajul">×</button>
    </div>
    <?php endif; ?>
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                Profil Membru: <?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-gray-400" aria-label="Număr și data dosar">
                <?php if (!empty($membru['dosarnr']) || !empty($membru['dosardata'])): ?>
                <span class="font-medium text-slate-800 dark:text-gray-200">Nr. dosar: <?php echo htmlspecialchars($membru['dosarnr'] ?? '-'); ?></span>
                <?php if (!empty($membru['dosardata'])): ?>
                <span class="mx-2 text-slate-400 dark:text-gray-500" aria-hidden="true">|</span>
                <span class="font-medium text-slate-800 dark:text-gray-200">Data dosar: <?php echo date(DATE_FORMAT, strtotime($membru['dosardata'])); ?></span>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-slate-500 dark:text-gray-500">Nr. dosar: — · Data dosar: —</span>
                <?php endif; ?>
            </p>
            <div class="flex flex-wrap items-center gap-4 mt-2">
                <div class="flex items-center gap-2">
                    <label for="status_dosar_header" class="text-sm text-slate-600 dark:text-gray-400">Status:</label>
                    <select id="status_dosar_header" 
                            onchange="document.getElementById('status_dosar').value = this.value; document.getElementById('form-membru-profil').requestSubmit();"
                            class="px-2 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <option value="Activ" <?php echo ($membru['status_dosar'] ?? 'Activ') === 'Activ' ? 'selected' : ''; ?>>Activ</option>
                        <option value="Expirat" <?php echo ($membru['status_dosar'] ?? '') === 'Expirat' ? 'selected' : ''; ?>>Expirat</option>
                        <option value="Suspendat" <?php echo ($membru['status_dosar'] ?? '') === 'Suspendat' ? 'selected' : ''; ?>>Suspendat</option>
                        <option value="Retras" <?php echo ($membru['status_dosar'] ?? '') === 'Retras' ? 'selected' : ''; ?>>Retras</option>
                        <option value="Decedat" <?php echo ($membru['status_dosar'] ?? '') === 'Decedat' ? 'selected' : ''; ?>>Decedat</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if (!empty($membru['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($membru['email']); ?>" 
               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Trimite email">
                <i data-lucide="mail" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Email
            </a>
            <?php endif; ?>
            <?php if (!empty($membru['telefonnev'])): ?>
            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $membru['telefonnev']); ?>" 
               target="_blank"
               class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Mesaj WhatsApp">
                <i data-lucide="message-circle" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                WhatsApp
            </a>
            <?php endif; ?>
            <button type="button"
                    data-action="generare-document"
                    data-membru-id="<?php echo $membru['id']; ?>"
                    class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    aria-label="Generează document pentru <?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>">
                <i data-lucide="file-text" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Generează Document
            </button>
            <?php if (!empty($scutire_cotizatie)): ?>
            <a href="setari.php?tab=cotizatii#scutire-<?php echo (int)$scutire_cotizatie['id']; ?>"
               class="inline-flex items-center px-3 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Scutit de cotizație – vezi detalii scutire">
                <i data-lucide="shield-check" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Scutit de cotizatie
            </a>
            <?php else: ?>
            <button type="button"
                    class="btn-deschide-incasari inline-flex items-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    data-membru-id="<?php echo (int)$membru['id']; ?>"
                    data-valoare-cot="<?php echo number_format($valoare_cotizatie_an, 2, '.', ''); ?>"
                    data-cot-achitata="<?php echo $cotizatie_achitata_an_curent ? '1' : '0'; ?>"
                    aria-label="Încasează">
                <i data-lucide="dollar-sign" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Încasează
            </button>
            <?php endif; ?>
            <button type="button"
                    id="btn-editeaza-datele"
                    class="inline-flex items-center px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    aria-pressed="false"
                    aria-label="Comută în modul de editare a datelor membrului">
                <i data-lucide="edit-3" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Editează datele
            </button>
            <a href="membri.php" 
               class="inline-flex items-center px-3 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <i data-lucide="arrow-left" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Înapoi
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div id="mesaj-eroare-salvare" class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
            <p class="font-semibold">Salvare eșuată:</p>
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <?php
        // Afișare mesaje de avertizare pentru membru: un singur rând (stil implicit) pentru toate avertismentele
        $alerts = genereaza_alerts_membru_pentru_profil($membru, $pdo);
        if (!empty($alerts)):
        ?>
        <div class="mb-3 py-2 px-3 rounded-r border-l-4 flex flex-wrap items-center gap-3 bg-slate-100 dark:bg-slate-700/50" role="alert" aria-live="polite">
            <?php
            foreach ($alerts as $alert):
                $alert_key = $alert['alert_key'] ?? '';
                $is_error = $alert['tip'] === 'error';
                $is_ci = $alert_key === 'ci';
                $is_ch = $alert_key === 'ch';
                $is_cotizatie = $alert_key === 'cotizatie';
                if ($is_error) {
                    $bg = 'bg-red-600';
                    $borderCls = 'border-red-800';
                    $textCls = 'text-white';
                } elseif ($is_ci) {
                    $bg = 'bg-orange-500';
                    $borderCls = 'border-orange-700';
                    $textCls = 'text-black';
                } elseif ($is_ch) {
                    $bg = 'bg-yellow-400';
                    $borderCls = 'border-yellow-600';
                    $textCls = 'text-black';
                } elseif ($is_cotizatie) {
                    $bg = 'bg-amber-500';
                    $borderCls = 'border-amber-700';
                    $textCls = 'text-black';
                } else {
                    $bg = 'bg-slate-500';
                    $borderCls = 'border-slate-700';
                    $textCls = 'text-white';
                }
            ?>
            <div class="flex items-center gap-2 <?php echo $bg; ?> <?php echo $textCls; ?> py-1.5 px-2.5 rounded border-l-4 <?php echo $borderCls; ?>">
                <i data-lucide="<?php echo $is_error ? 'alert-circle' : 'alert-triangle'; ?>" class="w-4 h-4 flex-shrink-0" aria-hidden="true"></i>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($alert['mesaj']); ?></span>
                <?php if ($alert_key && in_array($alert_key, ['ci', 'ch', 'cotizatie'])): ?>
                <form method="post" action="membru-profil.php?id=<?php echo $membru_id; ?>" class="inline ml-1">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="marcheaza_alert_informat" value="1">
                    <input type="hidden" name="membru_id" value="<?php echo $membru_id; ?>">
                    <input type="hidden" name="alert_tip" value="<?php echo htmlspecialchars($alert_key); ?>">
                    <label class="flex items-center gap-1 cursor-pointer" title="Bifează dacă membrul a fost informat; debifează pentru a reseta (se salvează la schimbare).">
                        <input type="checkbox" name="marcat_informat" value="1" onchange="(this.form.requestSubmit && this.form.requestSubmit()) || this.form.submit()" <?php echo !empty($alert['dismissed']) ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded border-2 border-current focus:ring-2 focus:ring-black"
                               aria-label="Membru informat; poți debifa pentru a reseta marcarea">
                        <span class="text-xs font-medium">Membru informat</span>
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <?php require_once 'membru-profil-form.php'; ?>
            <?php render_formular_profil_membru($membru, $eroare, $istoric_modificari); ?>
        </div>

        <?php
        $lista_incasari = incasari_lista_membru($pdo, $membru_id);
        $tipuri_afisare = incasari_tipuri_afisare();
        $moduri_plata_afisare = incasari_moduri_plata_afisare();
        ?>
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" id="sectiune-istoric-incasari">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2" id="titlu-istoric-incasari">
                <i data-lucide="history" class="w-5 h-5" aria-hidden="true"></i>
                Istoric încasări
            </h2>
            <?php if (empty($lista_incasari)): ?>
            <p class="text-slate-600 dark:text-gray-400 py-4">Nu există încasări alocate acestui membru.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-labelledby="titlu-istoric-incasari" aria-describedby="desc-istoric-incasari">
                    <caption id="desc-istoric-incasari" class="sr-only">Lista tuturor încasărilor alocate membrului</caption>
                    <thead class="bg-slate-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Tip</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Sumă (RON)</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Mod plată</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Serie / Nr. chitanță</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($lista_incasari as $inc): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo $inc['data_incasare'] ? date(DATE_FORMAT, strtotime($inc['data_incasare'])) : '-'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($tipuri_afisare[$inc['tip']] ?? $inc['tip']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo number_format((float)$inc['suma'], 2, ',', ' '); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($moduri_plata_afisare[$inc['mod_plata']] ?? $inc['mod_plata']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars(($inc['seria_chitanta'] ?? '') . ' ' . ($inc['nr_chitanta'] ?? '')); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <a href="util/incasari-chitanta-print.php?id=<?php echo (int)$inc['id']; ?>"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1.5 rounded border border-amber-500 dark:border-amber-400 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/30 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                       aria-label="Vizualizează chitanța <?php echo htmlspecialchars(($inc['seria_chitanta'] ?? '') . ' ' . ($inc['nr_chitanta'] ?? '')); ?>">
                                        <i data-lucide="eye" class="w-4 h-4" aria-hidden="true"></i>
                                        Vizualizează
                                    </a>
                                    <a href="util/incasari-chitanta-pdf.php?id=<?php echo (int)$inc['id']; ?>"
                                       class="inline-flex items-center gap-1 px-2 py-1.5 rounded border border-slate-400 dark:border-gray-500 text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                       aria-label="Descarcă PDF chitanța <?php echo htmlspecialchars(($inc['seria_chitanta'] ?? '') . ' ' . ($inc['nr_chitanta'] ?? '')); ?>">
                                        <i data-lucide="file-down" class="w-4 h-4" aria-hidden="true"></i>
                                        Descarcă PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/documente_modal.php'; ?>
<?php require_once 'includes/incasari_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    var eroareEl = document.getElementById('mesaj-eroare-salvare');
    if (eroareEl) {
        eroareEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var text = (document.querySelector('#banner-eroare-salvare p.text-sm') || eroareEl).textContent.trim();
        if (text) { alert('Salvare eșuată:\n\n' + text); }
    }

    // Modul vizualizare / editare profil membru
    var formProfil = document.getElementById('form-membru-profil');
    var btnEdit = document.getElementById('btn-editeaza-datele');
    var btnSave = document.getElementById('btn-salveaza-datele');

    if (formProfil && btnEdit && btnSave) {
        var inEditMode = false;

        function setEditMode(edit) {
            inEditMode = edit;

            var fields = formProfil.querySelectorAll('input, select, textarea');
            fields.forEach(function(el) {
                if (el.type === 'hidden') return;
                if (edit) {
                    el.removeAttribute('disabled');
                    el.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    el.setAttribute('disabled', 'disabled');
                    el.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });

            btnSave.classList.toggle('hidden', !edit);
            btnEdit.setAttribute('aria-pressed', edit ? 'true' : 'false');
            var labelSpan = btnEdit.querySelector('span');
            if (!labelSpan) {
                // fallback: schimbăm textul întregului buton
                btnEdit.textContent = edit ? 'Salvează datele' : 'Editează datele';
            } else {
                labelSpan.textContent = edit ? 'Salvează datele' : 'Editează datele';
            }
        }

        // Inițial: modul doar vizualizare
        setEditMode(false);

        btnEdit.addEventListener('click', function() {
            // La primul click, trecem în modul editare; al doilea click lasă utilizatorul în modul editare
            // pentru a salva din butonul din partea de jos.
            if (!inEditMode) {
                setEditMode(true);
                // Focus pe primul câmp editabil
                var firstInput = formProfil.querySelector('input:not([type="hidden"]), select, textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            } else {
                // Dacă este deja în modul editare, derulăm la butonul "Salvează Modificări"
                btnSave.scrollIntoView({ behavior: 'smooth', block: 'center' });
                btnSave.focus();
            }
        });
    }
});
</script>
</body>
</html>
