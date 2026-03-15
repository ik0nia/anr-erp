<?php
// Output buffering pentru redirect-uri după POST (finalizare task, interacțiuni etc.)
ob_start();
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/registru_interactiuni_v2_helper.php';
require_once APP_ROOT . '/includes/librarie_documente_helper.php';
require_once APP_ROOT . '/includes/contacte_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';

$eroare = '';
$succes = '';
$eroare_bd = '';
$taskuri_active = [];
$taskuri_istoric = [];
$membri_cu_avertizari = 0;

// Procesare finalizare task ÎNAINTE de output - pentru redirecționare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizeaza_task'])) {
    csrf_require_valid();
    $id = (int) ($_POST['task_id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND finalizat = 0');
            $stmt->execute([$id]);
            $task = $stmt->fetch();
            if ($task) {
                // IMPORTANT: Finalizarea unui task NU afectează interacțiunile din registru_interactiuni_v2
                // Taskurile sunt independente - doar se marchează ca finalizate în tabelul taskuri
                // Interacțiunile rămân permanent înregistrate în registru_interactiuni_v2
                $stmt = $pdo->prepare('UPDATE taskuri SET finalizat = 1, data_finalizare = NOW() WHERE id = ?');
                $stmt->execute([$id]);
                log_activitate($pdo, "Sarcină finalizată: {$task['nume']}");
                $redirect_url = '/dashboard?succes=1';
                if (function_exists('ob_get_level')) {
                    while (ob_get_level()) { ob_end_clean(); }
                }
                if (!headers_sent()) {
                    header('Location: ' . $redirect_url);
                    exit;
                }
                echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><p>Redirecționare…</p><script>location.replace(' . json_encode($redirect_url) . ');</script></body></html>';
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la finalizare.';
        }
    }
}

// Procesare înregistrare interacțiune v2 ÎNAINTE de output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_interactiune_v2'])) {
    csrf_require_valid();
    ensure_registru_v2_tables($pdo);
    $tip = in_array($_POST['tip_interactiune_v2'] ?? '', ['apel', 'vizita']) ? $_POST['tip_interactiune_v2'] : 'apel';
    $persoana = trim($_POST['persoana_v2'] ?? '');
    $telefon = trim($_POST['telefon_v2'] ?? '');
    $subiect_id = (int)($_POST['subiect_id_v2'] ?? 0);
    $subiect_alt = trim($_POST['subiect_alt_v2'] ?? '');
    $informatii_suplimentare = trim($_POST['informatii_suplimentare_v2'] ?? '');
    $task_activ = isset($_POST['task_activ_v2']) ? 1 : 0;
    $user = $_SESSION['utilizator'] ?? 'Utilizator';
    $user_id = $_SESSION['user_id'] ?? null;

    if (empty($persoana)) {
        $persoana = 'Fara nume';
    }
    
    try {
        $subiect_id_val = $subiect_id > 0 ? $subiect_id : null;
        $subiect_alt_val = !empty($subiect_alt) ? $subiect_alt : null;
        
        $stmt = $pdo->prepare('INSERT INTO registru_interactiuni_v2 (tip, persoana, telefon, subiect_id, subiect_alt, informatii_suplimentare, task_activ, utilizator, utilizator_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$tip, $persoana, $telefon ?: null, $subiect_id_val, $subiect_alt_val, $informatii_suplimentare ?: null, $task_activ, $user, $user_id]);
        $interact_id = $pdo->lastInsertId();
        $task_id_val = null;
        
        if ($task_activ) {
            // Construiește numele taskului: [tip interactiune] numele persoanei (telefon) - subiect - informatii suplimentare
            $tip_label = $tip === 'apel' ? '[Apel]' : '[Vizita]';
            
            // Telefon sau "fara telefon"
            $telefon_display = $telefon ? "({$telefon})" : "(fara telefon)";
            
            // Subiect
            $subiect_display = '';
            if ($subiect_alt_val) {
                $subiect_display = $subiect_alt_val;
            } elseif ($subiect_id_val) {
                $stmt_sn = $pdo->prepare('SELECT nume FROM registru_interactiuni_v2_subiecte WHERE id = ?');
                $stmt_sn->execute([$subiect_id_val]);
                $nume_subiect = $stmt_sn->fetchColumn();
                if ($nume_subiect) {
                    $subiect_display = $nume_subiect;
                }
            }
            
            // Construiește numele taskului
            $nume_task_parts = [];
            $nume_task_parts[] = $tip_label . ' ' . $persoana . ' ' . $telefon_display;
            if ($subiect_display) {
                $nume_task_parts[] = $subiect_display;
            }
            if ($informatii_suplimentare) {
                $nume_task_parts[] = $informatii_suplimentare;
            }
            $nume_task = implode(' - ', $nume_task_parts);
            
            $detalii_task = $informatii_suplimentare ?: '';
            
            // Verifică dacă tabelul taskuri are coloana utilizator_id
            $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
            $has_user_id = in_array('utilizator_id', $cols);
            
            if ($has_user_id) {
                $stmt_t = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta, utilizator_id) VALUES (?, NOW(), ?, ?, ?)');
                $stmt_t->execute([$nume_task, $detalii_task ?: null, 'normal', $user_id]);
            } else {
                $stmt_t = $pdo->prepare('INSERT INTO taskuri (nume, data_ora, detalii, nivel_urgenta) VALUES (?, NOW(), ?, ?)');
                $stmt_t->execute([$nume_task, $detalii_task ?: null, 'normal']);
            }
            $task_id_val = $pdo->lastInsertId();
            // Salvează task_id în interacțiune pentru referință (opțional, pentru tracking)
            // IMPORTANT: Taskurile sunt independente de interacțiuni - finalizarea unui task NU afectează interacțiunea
            $pdo->prepare('UPDATE registru_interactiuni_v2 SET task_id = ? WHERE id = ?')->execute([$task_id_val, $interact_id]);
            log_activitate($pdo, "Task creat din interacțiune v2: {$nume_task}");
        }
        log_activitate($pdo, "registru_interactiuni_v2: " . ($tip === 'apel' ? 'Apel telefonic' : 'Vizită sediu') . " înregistrat: {$persoana}");
        $redirect_url = '/dashboard?succes_interact_v2=1';
        while (ob_get_level()) { ob_end_clean(); }
        if (!headers_sent()) {
            header('Location: ' . $redirect_url);
            exit;
        }
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><p>Redirecționare…</p><script>location.replace(' . json_encode($redirect_url) . ');</script></body></html>';
        exit;
    } catch (PDOException $e) {
        $eroare = 'Eroare la salvare: ' . $e->getMessage();
    }
}

include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';

if (isset($_GET['succes'])) {
    $succes = $_GET['succes'] == '4' ? 'Taskul a fost actualizat cu succes.' : 'Taskul a fost marcat ca finalizat.';
}
if (isset($_GET['succes_task'])) {
    $succes = 'Taskul a fost adăugat cu succes.';
}
if (isset($_GET['succes_interact_v2'])) {
    $succes = 'Interacțiunea a fost înregistrată cu succes.';
}

try {
    // Verifică dacă există coloana utilizator_id
    $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
    $has_user_id = in_array('utilizator_id', $cols);
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($has_user_id && $user_id) {
        // Filtrează taskurile pe utilizator
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 AND (utilizator_id IS NULL OR utilizator_id = ?) ORDER BY data_ora ASC');
        $stmt->execute([$user_id]);
        $taskuri_active = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT COUNT(*) as n FROM taskuri WHERE finalizat = 1 AND (utilizator_id IS NULL OR utilizator_id = ?)');
        $stmt->execute([$user_id]);
        $taskuri_istoric_count = $stmt->fetch()['n'];
    } else {
        // Comportament vechi dacă nu există coloana utilizator_id
        $stmt = $pdo->query('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 ORDER BY data_ora ASC');
        $taskuri_active = $stmt->fetchAll();
        $stmt = $pdo->query('SELECT COUNT(*) as n FROM taskuri WHERE finalizat = 1');
        $taskuri_istoric_count = $stmt->fetch()['n'];
    }
    // Număr membri cu CI sau certificat care expiră în <= 60 zile (excluzând cei notificați)
    $membri_cu_avertizari = 0;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('cidataexp', $cols) && in_array('ceexp', $cols)) {
            $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE
                (status_dosar IS NULL OR status_dosar = 'Activ' OR status_dosar NOT IN ('Suspendat', 'Expirat', 'Retras', 'Decedat'))
                AND (
                    (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND cidataexp > CURDATE() AND (expira_ci_notificat IS NULL OR expira_ci_notificat = 0))
                    OR (ceexp IS NOT NULL AND ceexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND ceexp > CURDATE() AND (expira_ch_notificat IS NULL OR expira_ch_notificat = 0))
                )");
            $membri_cu_avertizari = (int) $stmt->fetch()['n'];
        }
    } catch (PDOException $e) {}
} catch (PDOException $e) {
    $eroare_bd = 'Tabelul taskuri nu există. Rulați schema.sql sau schema_taskuri.sql în baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '.';
    $taskuri_istoric_count = 0;
    $membri_cu_avertizari = 0;
}
ensure_registru_v2_tables($pdo);
librarie_documente_ensure_tables($pdo);
$librarie_cautare = trim($_GET['cautare_librarie'] ?? '');
$librarie_lista = librarie_documente_lista($pdo, $librarie_cautare);
$subiecte_interactiuni_v2 = get_subiecte_interactiuni_v2($pdo);

// Statistici pentru Registru Interacțiuni v2
$interactiuni_v2_azi = registru_v2_interactiuni_azi($pdo);

?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Dashboard CRM</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare_bd); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coloana stânga - Butoane rapide, Caută membru și Librărie -->
            <aside class="lg:order-1 lg:col-span-1 flex flex-col gap-4" aria-label="Zonă informații">
                <!-- Bloc butoane acțiuni rapide -->
                <nav class="grid grid-cols-5 gap-2 w-full" aria-label="Acțiuni rapide">
                    <a href="activitati.php?adauga=1&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Crează activitate nouă">
                        <i data-lucide="calendar-plus" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Crează activitate</span>
                    </a>
                    <a href="lista-prezenta-create.php" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Crează listă nouă">
                        <i data-lucide="list" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Crează listă</span>
                    </a>
                    <a href="membri.php?avertizari=1" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Actualizare date membri<?php echo $membri_cu_avertizari > 0 ? '. ' . $membri_cu_avertizari . ' membri cu avertizări' : ''; ?>">
                        <?php if ($membri_cu_avertizari > 0): ?>
                        <span class="absolute top-2 right-2 flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-bold text-white bg-amber-600 rounded-full z-10" aria-hidden="true"><?php echo $membri_cu_avertizari; ?></span>
                        <?php endif; ?>
                        <i data-lucide="users" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Actualizare membri</span>
                    </a>
                    <a href="todo-adauga.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Adaugă task nou">
                        <i data-lucide="plus-circle" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Adaugă task</span>
                    </a>
                    <a href="registratura-adauga.php?redirect=dashboard" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Înregistrare document în registratură">
                        <i data-lucide="book-open" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Registratura</span>
                    </a>
                    <a href="ajutoare-bpa.php" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Distribuție BPA – modul Ajutoare BPA">
                        <i data-lucide="package" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Distribuție BPA</span>
                    </a>
                    <button type="button" class="btn-deschide-incasari-dashboard aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                            aria-label="Încasează Donație – deschide fereastra pentru încasare donații sau cotizații">
                        <i data-lucide="banknote" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Încasează Donație</span>
                    </button>
                    <a href="voluntariat.php?tab=activitati&from=dashboard" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Activitate voluntari – adaugă activitate în registrul de activități voluntari">
                        <i data-lucide="hand-heart" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Activitate voluntari</span>
                    </a>
                </nav>
                <!-- Bloc Caută membru -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Caută Membru</h2>
                    <form method="get" action="/dashboard" id="form-cautare-membru" class="mb-4">
                        <div class="relative">
                            <input type="search" 
                                   name="cautare_membru" 
                                   id="cautare_membru"
                                   value="<?php echo htmlspecialchars($_GET['cautare_membru'] ?? ''); ?>" 
                                   placeholder="Nume, prenume sau CNP..." 
                                   class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   aria-label="Caută membru">
                            <button type="submit" 
                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400"
                                    aria-label="Caută">
                                <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                    <div id="rezultate-cautare" class="space-y-2 max-h-64 overflow-y-auto">
                        <?php
                        $cautare_membru = trim($_GET['cautare_membru'] ?? '');
                        if (!empty($cautare_membru)):
                            try {
                                $search_term = '%' . $cautare_membru . '%';
                                $stmt = $pdo->prepare('SELECT id, nume, prenume, cnp, dosarnr FROM membri 
                                                       WHERE nume LIKE ? OR prenume LIKE ? OR cnp LIKE ? OR dosarnr LIKE ? 
                                                       ORDER BY nume, prenume LIMIT 10');
                                $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
                                $rezultate = $stmt->fetchAll();
                                
                                if (empty($rezultate)):
                        ?>
                        <p class="text-sm text-slate-600 dark:text-gray-400">Nu s-au găsit membri.</p>
                        <?php
                                else:
                                    foreach ($rezultate as $m):
                        ?>
                        <a href="membru-profil.php?id=<?php echo $m['id']; ?>" 
                           class="block p-2 rounded hover:bg-slate-100 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500 transition">
                            <div class="font-medium text-slate-900 dark:text-white text-sm">
                                <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>
                            </div>
                            <?php if ($m['dosarnr']): ?>
                            <div class="text-xs text-slate-600 dark:text-gray-400">Dosar: <?php echo htmlspecialchars($m['dosarnr']); ?></div>
                            <?php endif; ?>
                        </a>
                        <?php
                                    endforeach;
                                endif;
                            } catch (PDOException $e) {
                                echo '<p class="text-sm text-red-600 dark:text-red-400">Eroare la căutare.</p>';
                            }
                        endif;
                        ?>
                    </div>
                </div>
                <!-- Bloc Librărie documente -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">
                        <a href="librarie-documente.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">Librărie documente</a>
                    </h2>
                    <form method="get" action="/dashboard" id="form-cautare-librarie" class="mb-4">
                        <input type="hidden" name="cautare_membru" value="<?php echo htmlspecialchars($_GET['cautare_membru'] ?? ''); ?>">
                        <div class="relative">
                            <input type="search" name="cautare_librarie" id="cautare_librarie"
                                   value="<?php echo htmlspecialchars($librarie_cautare); ?>"
                                   placeholder="Caută document în librărie..."
                                   class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   aria-label="Caută document în librărie">
                            <button type="submit" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-gray-400 hover:text-amber-600" aria-label="Caută">
                                <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php if (empty($librarie_lista)): ?>
                        <p class="text-sm text-slate-600 dark:text-gray-400"><?php echo $librarie_cautare !== '' ? 'Nu s-au găsit documente.' : 'Niciun document încărcat. <a href="librarie-documente.php" class="text-amber-600 dark:text-amber-400 hover:underline">Librărie documente</a>'; ?></p>
                        <?php else: ?>
                        <?php foreach ($librarie_lista as $ld): ?>
                        <div class="flex items-center justify-between gap-2 p-2 rounded hover:bg-slate-50 dark:hover:bg-gray-700 border-b border-slate-100 dark:border-gray-600 last:border-0">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-slate-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($ld['institutie']); ?></p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($ld['nume_document']); ?></p>
                            </div>
                            <span class="flex items-center gap-2 flex-shrink-0">
                                <a href="util/descarca-librarie-document.php?id=<?php echo (int)$ld['id']; ?>&amp;print=1" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-slate-500 transition"
                                   aria-label="Print <?php echo htmlspecialchars($ld['nume_document']); ?>">
                                    <i data-lucide="printer" class="w-4 h-4" aria-hidden="true"></i>
                                    <span>Print</span>
                                </a>
                                <a href="util/descarca-librarie-document.php?id=<?php echo (int)$ld['id']; ?>"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-blue-500 transition"
                                   aria-label="Descarcă <?php echo htmlspecialchars($ld['nume_document']); ?>">
                                    <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                                    <span>Descarcă</span>
                                </a>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <!-- Coloana mijloc - Taskuri -->
            <section class="lg:order-2 lg:col-span-1 flex flex-col gap-4 min-w-0 overflow-hidden" aria-labelledby="titlu-todo">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <header class="flex flex-wrap justify-between items-center gap-4 p-6 pb-4">
                        <h2 id="titlu-todo" class="text-lg font-semibold text-slate-900 dark:text-white">
                            <a href="todo.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">Taskuri</a>
                        </h2>
                        <a href="todo-adauga.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>"
                           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white dark:text-white bg-orange-500 dark:bg-orange-600 border border-orange-600 dark:border-orange-700 rounded-lg hover:bg-orange-600 dark:hover:bg-orange-700 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition"
                           aria-label="Adaugă task nou">
                            <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                            <span>Task Nou</span>
                        </a>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Taskuri active" id="todo-table-dashboard">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-2 text-left"><span class="sr-only">Finalizat</span></th>
                                    <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">
                                        <button type="button" class="sort-header-btn text-left hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" data-column="1" data-order="asc" aria-label="Sortează după nume">
                                            Nume
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">
                                        <button type="button" class="sort-header-btn text-left hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" data-column="2" data-order="asc" aria-label="Sortează după dată și oră">
                                            Data și oră
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">
                                        <button type="button" class="sort-header-btn text-right hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" data-column="3" data-order="asc" aria-label="Sortează după urgență">
                                            Urgență
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($taskuri_active)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu există taskuri active. <a href="todo-adauga.php" class="text-amber-600 dark:text-amber-400 hover:underline">Adaugă un task</a></td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($taskuri_active as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <form method="post" action="/dashboard" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="finalizeaza_task" value="1">
                                            <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="marca_finalizat" onchange="(this.form.requestSubmit && this.form.requestSubmit()) || this.form.submit()"
                                                       class="w-5 h-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                                       aria-label="Marchează taskul <?php echo htmlspecialchars($t['nume']); ?> ca finalizat">
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button"
                                                onclick='deschideEditare(<?php echo json_encode((int)$t['id']); ?>, <?php echo json_encode($t['nume']); ?>, <?php echo json_encode(date('Y-m-d', strtotime($t['data_ora']))); ?>, <?php echo json_encode(date('H:i', strtotime($t['data_ora']))); ?>, <?php echo json_encode($t['detalii'] ?? ''); ?>, <?php echo json_encode($t['nivel_urgenta']); ?>)'
                                                class="text-left font-medium text-amber-600 dark:text-amber-400 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 rounded"
                                                aria-label="Vezi detaliile taskului <?php echo htmlspecialchars($t['nume']); ?>">
                                            <?php echo htmlspecialchars($t['nume']); ?>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo date(DATETIME_FORMAT, strtotime($t['data_ora'])); ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <?php
                                        $badge = ['normal' => 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200', 'important' => 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100', 'reprogramat' => 'bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100'][$t['nivel_urgenta']] ?? 'bg-slate-200 dark:bg-slate-600';
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($t['nivel_urgenta'])); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Coloana dreapta - Registru Interacțiuni și Interacțiuni zilnice -->
            <aside class="lg:order-3 lg:col-span-1 flex flex-col gap-4" aria-label="Registru Interacțiuni">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        <a href="registru-interactiuni-v2.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">Registru Interacțiuni</a>
                    </h2>
                    <form method="post" action="/dashboard" id="form-interactiuni-v2">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="adauga_interactiune_v2" value="1">
                        <input type="hidden" name="tip_interactiune_v2" id="tip-interact-v2-input" value="apel">
                        <div class="flex gap-2 mb-4">
                            <button type="button" id="btn-apel-v2" onclick="setTipInteractiuneV2('apel')" aria-pressed="true" aria-label="Apel telefonic"
                                    class="flex-1 px-4 py-3 rounded-lg font-medium border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200">
                                <i data-lucide="phone" class="w-5 h-5 inline-block mr-1" aria-hidden="true"></i> Apel Telefonic
                            </button>
                            <button type="button" id="btn-vizita-v2" onclick="setTipInteractiuneV2('vizita')" aria-pressed="false" aria-label="Vizită sediu"
                                    class="flex-1 px-4 py-3 rounded-lg font-medium border-2 border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:border-amber-500">
                                <i data-lucide="building" class="w-5 h-5 inline-block mr-1" aria-hidden="true"></i> Vizita Sediu
                            </button>
                        </div>
                        <div class="mb-3">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label for="interact-persoana-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume <span class="text-red-600" aria-hidden="true">*</span></label>
                                    <input type="text" id="interact-persoana-v2" name="persoana_v2"
                                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                           placeholder="Nume persoană (sau 'Fara nume' dacă nu se completează)"
                                           aria-required="true">
                                </div>
                                <div class="flex-1">
                                    <label for="interact-telefon-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr. telefon <span class="text-xs text-slate-500 dark:text-gray-400">(opțional)</span></label>
                                    <input type="tel" id="interact-telefon-v2" name="telefon_v2"
                                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                           placeholder="07xx xxx xxx (opțional)"
                                           aria-describedby="interact-telefon-v2-desc">
                                    <span id="interact-telefon-v2-desc" class="sr-only">Câmp opțional; nu este obligatoriu.</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1 invisible">Task</label>
                                    <button type="button" id="btn-task-activ-v2" onclick="toggleTaskActivV2()" 
                                            class="px-3 py-2 border-2 border-slate-600 dark:border-gray-500 bg-slate-600 dark:bg-gray-500 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center hover:bg-slate-700 dark:hover:bg-gray-600"
                                            aria-pressed="false" aria-label="Creează task în Taskuri">
                                        <input type="checkbox" name="task_activ_v2" value="1" id="task-activ-v2" class="sr-only" aria-hidden="true">
                                        <i data-lucide="square" id="icon-task-v2" class="w-5 h-5 text-white" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="interact-subiect-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Subiectul discutat</label>
                            <select id="interact-subiect-v2" name="subiect_id_v2" aria-label="Selectează subiectul interacțiunii"
                                    class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                <option value="">-- Selectați --</option>
                                <?php foreach ($subiecte_interactiuni_v2 as $s): ?>
                                <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['nume']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="interact-subiect-alt-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Alt subiect</label>
                            <input type="text" name="subiect_alt_v2" id="interact-subiect-alt-v2"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   placeholder="Specificați alt subiect dacă nu e în listă">
                        </div>
                        <div class="mb-3">
                            <label for="interact-informatii-suplimentare-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Informații suplimentare</label>
                            <textarea id="interact-informatii-suplimentare-v2" name="informatii_suplimentare_v2" rows="3"
                                      class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                      placeholder="Detalii despre interacțiune..."></textarea>
                        </div>
                        <div class="mb-4">
                            <button type="button" id="btn-task-activ-v2" onclick="toggleTaskActivV2()" 
                                    class="w-full px-4 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center gap-2 hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                                    aria-pressed="false" aria-label="Creează task în Taskuri">
                                <input type="checkbox" name="task_activ_v2" value="1" id="task-activ-v2" class="sr-only" aria-hidden="true">
                                <i data-lucide="check-square" id="icon-task-v2" class="w-5 h-5 text-slate-400 dark:text-gray-500" aria-hidden="true"></i>
                                <span id="text-task-v2" class="text-slate-700 dark:text-gray-300">Creează task în Taskuri</span>
                            </button>
                            <p class="text-xs text-slate-600 dark:text-gray-400 mt-1 text-center">Apasă butonul pentru a crea automat un task în modulul Taskuri.</p>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează interacțiunea">Salvează</button>
                    </form>
                </div>
                <!-- Bloc afișare interacțiuni zilnice -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Interacțiuni zilnice</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="counter-apel-v2-azi" aria-live="polite" aria-label="Număr apeluri telefonice azi"><?php echo $interactiuni_v2_azi['apel']; ?></p>
                            <p class="text-xs text-slate-600 dark:text-gray-400">Apeluri telefonice azi</p>
                        </div>
                        <div class="text-center p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="counter-vizita-v2-azi" aria-live="polite" aria-label="Număr vizite la sediu azi"><?php echo $interactiuni_v2_azi['vizita']; ?></p>
                            <p class="text-xs text-slate-600 dark:text-gray-400">Vizite la sediu azi</p>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</main>

<!-- Modal editare task -->
<dialog id="detalii-task" role="dialog" aria-modal="true" aria-labelledby="titlu-detalii" class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-detalii" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Editează task</h2>
        <form method="post" action="todo.php" id="form-edit-task">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="actualizeaza_task" value="1">
            <input type="hidden" name="task_id" id="edit-task-id" value="">
            <input type="hidden" name="redirect_after" id="edit-redirect" value="index.php">
            <div class="space-y-4">
                <div>
                    <label for="edit-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="edit-nume" name="nume" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" id="edit-data" name="data" required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="edit-ora" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora</label>
                        <input type="time" id="edit-ora" name="ora"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-describedby="edit-ora-desc">
                        <span id="edit-ora-desc" class="sr-only">Format 24 de ore</span>
                    </div>
                </div>
                <div>
                    <label for="edit-detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="edit-detalii" name="detalii" rows="3"
                              class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></textarea>
                </div>
                <div>
                    <label for="edit-urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                    <select id="edit-urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Selectează nivelul de urgență">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="reprogramat">Reprogramat</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-renunta-task" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Renunță (Esc)">Renunță</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează (Enter)">Salvează</button>
            </div>
        </form>
    </div>
</dialog>

<script>
lucide.createIcons();
function setTipInteractiuneV2(tip) {
    document.getElementById('tip-interact-v2-input').value = tip;
    var btnApel = document.getElementById('btn-apel-v2');
    var btnVizita = document.getElementById('btn-vizita-v2');
    if (tip === 'apel') {
        btnApel.setAttribute('aria-pressed', 'true');
        btnApel.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200';
        btnVizita.setAttribute('aria-pressed', 'false');
        btnVizita.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:border-amber-500';
    } else {
        btnVizita.setAttribute('aria-pressed', 'true');
        btnVizita.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200';
        btnApel.setAttribute('aria-pressed', 'false');
        btnApel.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:border-amber-500';
    }
}
// Toggle buton task activ v2
function toggleTaskActivV2() {
    var checkbox = document.getElementById('task-activ-v2');
    var btn = document.getElementById('btn-task-activ-v2');
    var icon = document.getElementById('icon-task-v2');
    var isChecked = checkbox.checked;
    
    checkbox.checked = !isChecked;
    var newChecked = checkbox.checked;
    
    if (newChecked) {
        btn.setAttribute('aria-pressed', 'true');
        btn.className = 'px-3 py-2 border-2 border-green-600 dark:border-green-500 bg-green-600 dark:bg-green-500 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center hover:bg-green-700 dark:hover:bg-green-600';
        if (typeof lucide !== 'undefined') {
            icon.setAttribute('data-lucide', 'check-square');
            lucide.createIcons();
        }
        icon.className = 'w-5 h-5 text-white';
    } else {
        btn.setAttribute('aria-pressed', 'false');
        btn.className = 'px-3 py-2 border-2 border-slate-600 dark:border-gray-500 bg-slate-600 dark:bg-gray-500 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center hover:bg-slate-700 dark:hover:bg-gray-600';
        if (typeof lucide !== 'undefined') {
            icon.setAttribute('data-lucide', 'square');
            lucide.createIcons();
        }
        icon.className = 'w-5 h-5 text-white';
    }
}

// Actualizare automată contoare Registru Interacțiuni v2
(function() {
    function actualizeazaContoareV2() {
        fetch('/api/registru-v2-stats')
            .then(response => response.json())
            .then(data => {
                if (data.apel !== undefined) {
                    var el = document.getElementById('counter-apel-v2-azi');
                    if (el) el.textContent = data.apel;
                }
                if (data.vizita !== undefined) {
                    var el = document.getElementById('counter-vizita-v2-azi');
                    if (el) el.textContent = data.vizita;
                }
            })
            .catch(error => {
                console.error('Eroare actualizare statistici v2:', error);
            });
    }
    // Actualizează la fiecare 30 secunde
    setInterval(actualizeazaContoareV2, 30000);
})();
// Câmpul "Alt subiect" este afișat permanent; nu mai e nevoie de toggle.
function deschideEditare(id, nume, data, ora, detalii, urgenta) {
    document.getElementById('edit-task-id').value = id;
    document.getElementById('edit-nume').value = nume || '';
    document.getElementById('edit-data').value = data || '';
    document.getElementById('edit-ora').value = ora || '09:00';
    document.getElementById('edit-detalii').value = detalii || '';
    document.getElementById('edit-urgenta').value = urgenta || 'normal';
    document.getElementById('edit-redirect').value = 'index.php';
    document.getElementById('detalii-task').showModal();
    document.getElementById('edit-nume').focus();
}
var dlgTask = document.getElementById('detalii-task');
if (dlgTask) {
    dlgTask.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { this.close(); }
    });
    document.getElementById('btn-renunta-task')?.addEventListener('click', function() { dlgTask.close(); });
}

// Sortare tabel Taskuri
(function() {
    const table = document.getElementById('todo-table-dashboard');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    document.querySelectorAll('.sort-header-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const column = parseInt(this.getAttribute('data-column'));
            let order = this.getAttribute('data-order'); // 'asc' sau 'desc'
            
            // Toggle order: dacă e 'asc', devine 'desc', altfel devine 'asc'
            order = order === 'asc' ? 'desc' : 'asc';
            this.setAttribute('data-order', order);
            
            // Resetează ordinea pentru celelalte coloane
            document.querySelectorAll('.sort-header-btn').forEach(function(otherBtn) {
                if (otherBtn !== btn) {
                    otherBtn.setAttribute('data-order', 'asc');
                }
            });
            
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort(function(a, b) {
                let aVal, bVal;
                
                if (column === 1) {
                    // Coloana Nume
                    aVal = a.cells[1].textContent.trim().toLowerCase();
                    bVal = b.cells[1].textContent.trim().toLowerCase();
                } else if (column === 2) {
                    // Coloana Data și oră
                    aVal = a.cells[2].textContent.trim();
                    bVal = b.cells[2].textContent.trim();
                } else if (column === 3) {
                    // Coloana Urgență
                    aVal = a.cells[3].textContent.trim().toLowerCase();
                    bVal = b.cells[3].textContent.trim().toLowerCase();
                }
                
                if (order === 'asc') {
                    return aVal > bVal ? 1 : (aVal < bVal ? -1 : 0);
                } else {
                    return aVal < bVal ? 1 : (aVal > bVal ? -1 : 0);
                }
            });
            
            // Reordonează rândurile
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
            
            // Reinițializează iconițele Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });
})();
</script>
<?php require_once APP_ROOT . '/includes/incasari_dashboard_modal.php'; ?>
</body>
</html>
