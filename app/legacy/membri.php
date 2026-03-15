<?php
/**
 * Modul Management Membri - CRM ANR
 * Vizualizare tabelară și adăugare membri noi
 */
// Activează output buffering pentru a preveni probleme cu redirect-uri
ob_start();
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 2));
require_once APP_ROOT . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once APP_ROOT . '/includes/log_helper.php';
require_once APP_ROOT . '/includes/cnp_validator.php';
require_once APP_ROOT . '/includes/file_helper.php';
require_once APP_ROOT . '/includes/membri_alerts.php';
require_once APP_ROOT . '/includes/cotizatii_helper.php';
require_once APP_ROOT . '/includes/incasari_helper.php';
require_once APP_ROOT . '/app/views/partials/membri_processing.php';

$eroare = '';
$succes = '';
$eroare_bd = '';
$membri = [];

// Procesare formular adăugare membru (ÎNAINTE de header.php pentru redirect)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_membru'])) {
    csrf_require_valid();
    $result = proceseaza_formular_membru($pdo, $_POST, $_FILES);
    
    if ($result['success']) {
        // Curăță buffer-ul înainte de redirect
        ob_clean();
        header('Location: /membri?succes=1');
        exit;
    } else {
        $eroare = $result['error'];
        // Curăță buffer-ul și continuă cu afișarea erorii
        ob_clean();
    }
}

// Reset mesaj precompletat când se schimbă afișarea prin butoanele centrale
if (isset($_GET['reset_mesaj']) && $_GET['reset_mesaj'] == '1') {
    unset($_SESSION['membri_mesaj_subiect'], $_SESSION['membri_mesaj_continut']);
}

// Salvare subiect și mesaj pentru precompletare WhatsApp/Email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mesaj_precompletat'])) {
    csrf_require_valid();
    $_SESSION['membri_mesaj_subiect'] = trim($_POST['mesaj_subiect'] ?? '');
    $_SESSION['membri_mesaj_continut'] = trim($_POST['mesaj_continut'] ?? '');
    $params = [];
    if (isset($_POST['redirect_status'])) $params['status'] = $_POST['redirect_status'];
    if (!empty($_POST['redirect_sort'])) $params['sort'] = $_POST['redirect_sort'];
    if (!empty($_POST['redirect_dir'])) $params['dir'] = $_POST['redirect_dir'];
    if (!empty($_POST['redirect_per_page'])) $params['per_page'] = $_POST['redirect_per_page'];
    if (!empty($_POST['redirect_page'])) $params['page'] = $_POST['redirect_page'];
    if (isset($_POST['redirect_cautare'])) $params['cautare'] = $_POST['redirect_cautare'];
    if (!empty($_POST['redirect_avertizari'])) $params['avertizari'] = $_POST['redirect_avertizari'];
    if (!empty($_POST['redirect_actualizare_cnp_ci'])) $params['actualizare_cnp_ci'] = $_POST['redirect_actualizare_cnp_ci'];
    if (!empty($_POST['redirect_aniversari_azi'])) $params['aniversari_azi'] = $_POST['redirect_aniversari_azi'];
    $redirect = 'membri.php' . (count($params) ? '?' . http_build_query($params) : '');
    ob_clean();
    header('Location: ' . $redirect);
    exit;
}

// Include header și sidebar DUPĂ procesarea POST (pentru a permite redirect-uri)
include APP_ROOT . '/app/views/layout/header.php';
include APP_ROOT . '/app/views/layout/sidebar.php';

// Afișare mesaj succes după redirect
if (isset($_GET['succes']) && $_GET['succes'] == '1') {
    $succes = 'Membrul a fost adăugat cu succes.';
}

// Parametri pentru sortare și căutare
$sort_col = $_GET['sort'] ?? 'dosarnr';
$sort_dir = $_GET['dir'] ?? 'asc';
$cautare = trim($_GET['cautare'] ?? '');
$per_page = (int)($_GET['per_page'] ?? 25);
$page = max(1, (int)($_GET['page'] ?? 1));
$status_filter = $_GET['status'] ?? 'activi'; // activi, suspendati, arhiva
$avertizari_filter = isset($_GET['avertizari']) && $_GET['avertizari'] == '1';
$aniversari_azi_filter = isset($_GET['aniversari_azi']) && $_GET['aniversari_azi'] == '1';
$actualizare_cnp_ci_filter = isset($_GET['actualizare_cnp_ci']) && $_GET['actualizare_cnp_ci'] == '1';

// Validare număr rezultate per pagină
if (!in_array($per_page, [10, 25, 50])) {
    $per_page = 25;
}

// Validare coloană de sortare
$allowed_sort_cols = ['dosarnr', 'nume', 'prenume', 'datanastere', 'ciseria', 'cinumar', 'telefonnev', 'hgrad'];
if (!in_array($sort_col, $allowed_sort_cols)) {
    $sort_col = 'dosarnr';
}
$sort_dir = strtolower($sort_dir) === 'desc' ? 'DESC' : 'ASC';

// Construire query cu filtrare după status
$where_parts = [];
$params = [];

// Filtrare după status
if ($status_filter === 'suspendati') {
    // Membri Suspendați/Expirați
    $where_parts[] = "status_dosar IN ('Suspendat', 'Expirat')";
} elseif ($status_filter === 'arhiva') {
    // Arhivă: doar Decedați
    $where_parts[] = "status_dosar = 'Decedat'";
} else {
    // Membri activi: doar cu status_dosar = 'Activ'
    $where_parts[] = "status_dosar = 'Activ'";
}

// Filtrare membri cu avertizări (doar CI sau certificat expiră în <= 60 zile, excluzând cei notificați, doar dosare active)
if ($avertizari_filter) {
    $where_parts[] = "(
        status_dosar = 'Activ'
        AND (
            (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND cidataexp > CURDATE() AND (expira_ci_notificat IS NULL OR expira_ci_notificat = 0))
            OR (ceexp IS NOT NULL AND ceexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND ceexp > CURDATE() AND (expira_ch_notificat IS NULL OR expira_ch_notificat = 0))
        )
    )";
}

// Filtrare aniversari azi (ziua de naștere azi)
if ($aniversari_azi_filter) {
    $where_parts[] = "datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE()) AND DAY(datanastere) = DAY(CURDATE())";
}

// Filtrare membri care necesită actualizare CNP/CI
// - erori CNP (gol / lungime != 13)
// - lipsă date CI (cidataelib / cielib / cidataexp)
// - sau CI care expiră în <= 30 zile
if ($actualizare_cnp_ci_filter) {
    $where_parts[] = "(
        status_dosar = 'Activ'
        AND (
            cidataelib IS NULL 
            OR cielib IS NULL OR cielib = ''
            OR cidataexp IS NULL 
            OR cnp IS NULL OR cnp = '' OR LENGTH(cnp) != 13
            OR (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
        )
    )";
}

// Căutare
if (!empty($cautare)) {
    $search_condition = "nume LIKE ? OR prenume LIKE ? OR cnp LIKE ? OR dosarnr LIKE ?";
    $search_term = '%' . $cautare . '%';
    $search_params = [$search_term, $search_term, $search_term, $search_term];
    
    if (!empty($where_parts)) {
        $where_parts[] = "($search_condition)";
        $params = array_merge($params, $search_params);
    } else {
        $where_parts[] = $search_condition;
        $params = $search_params;
    }
}

$where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

// Calculare total membri pentru paginare
try {
    $count_sql = "SELECT COUNT(*) as total FROM membri $where";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_membri = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_membri / $per_page);
    $page = min($page, max(1, $total_pages));
    $offset = ($page - 1) * $per_page;
} catch (PDOException $e) {
    // Dacă tabelul nu există, setăm valori default
    $error_msg = $e->getMessage();
    if (strpos($error_msg, "doesn't exist") !== false || strpos($error_msg, "Unknown column") !== false) {
        if (empty($eroare_bd)) {
            $eroare_bd = 'Tabelul membri sau o coloană necesară nu există. Rulați schema.sql în baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . ' (panou MySQL / phpMyAdmin).';
        }
    }
    $total_membri = 0;
    $total_pages = 0;
    $offset = 0;
}

// Calculare indicatori
try {
    // Total membri (toți membrii din baza de date)
    $stmt_total = $pdo->query("SELECT COUNT(*) as total FROM membri");
    $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_activi = (int)($result_total['total'] ?? 0);
    
    // Membri activi (doar status_dosar = 'Activ')
    $stmt_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ'");
    $result_activi = $stmt_activi->fetch(PDO::FETCH_ASSOC);
    $membri_activi_count = (int)($result_activi['total'] ?? 0);
    
    // Membri Suspendați/Expirați
    $stmt_susp_exp = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar IN ('Suspendat', 'Expirat')");
    $result_susp_exp = $stmt_susp_exp->fetch(PDO::FETCH_ASSOC);
    $membri_suspendati_expirati_count = (int)($result_susp_exp['total'] ?? 0);
    
    // Gradul Grav
    $stmt_grav = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Grav'");
    $result_grav = $stmt_grav->fetch(PDO::FETCH_ASSOC);
    $grad_grav = (int)($result_grav['total'] ?? 0);
    
    // Gradul Accentuat
    $stmt_accentuat = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Accentuat'");
    $result_accentuat = $stmt_accentuat->fetch(PDO::FETCH_ASSOC);
    $grad_accentuat = (int)($result_accentuat['total'] ?? 0);
    
    // Gradul Mediu
    $stmt_mediu = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE hgrad = 'Mediu'");
    $result_mediu = $stmt_mediu->fetch(PDO::FETCH_ASSOC);
    $grad_mediu = (int)($result_mediu['total'] ?? 0);
    
    // Femei
    $stmt_femei = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Feminin'");
    $result_femei = $stmt_femei->fetch(PDO::FETCH_ASSOC);
    $femei = (int)($result_femei['total'] ?? 0);
    
    // Bărbați
    $stmt_barbati = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE sex = 'Masculin'");
    $result_barbati = $stmt_barbati->fetch(PDO::FETCH_ASSOC);
    $barbati = (int)($result_barbati['total'] ?? 0);
} catch (PDOException $e) {
    // Dacă există eroare, încercăm să calculăm totalul tuturor membrilor
    try {
        $stmt_all = $pdo->query("SELECT COUNT(*) as total FROM membri");
        $result_all = $stmt_all->fetch(PDO::FETCH_ASSOC);
        $total_activi = (int)($result_all['total'] ?? 0);
    } catch (PDOException $e2) {
        $total_activi = 0;
    }
    $membri_activi_count = 0;
    $grad_grav = 0;
    $grad_accentuat = 0;
    $grad_mediu = 0;
    $femei = 0;
    $barbati = 0;
    $membri_suspendati_expirati_count = 0;
}

// Calculare număr membri cu avertizări (doar CI sau certificat care expiră în <= 60 zile, excluzând cei notificați)
$membri_cu_avertizari = 0;
$membri_suspendati_expirati_count = $membri_suspendati_expirati_count ?? 0;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('cidataexp', $cols) && in_array('ceexp', $cols)) {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE
            status_dosar = 'Activ'
            AND (
                (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND cidataexp > CURDATE() AND (expira_ci_notificat IS NULL OR expira_ci_notificat = 0))
                OR (ceexp IS NOT NULL AND ceexp <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND ceexp > CURDATE() AND (expira_ch_notificat IS NULL OR expira_ch_notificat = 0))
            )");
        $membri_cu_avertizari = (int) $stmt->fetch()['n'];
    }
} catch (PDOException $e) {
    $membri_cu_avertizari = 0;
}

// Calculare număr membri care necesită actualizare CNP/CI
$membri_actualizare_cnp_ci = 0;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM membri")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('cidataelib', $cols) && in_array('cielib', $cols) && in_array('cidataexp', $cols) && in_array('cnp', $cols)) {
        $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE
            status_dosar = 'Activ'
            AND (
                cidataelib IS NULL 
                OR cielib IS NULL OR cielib = ''
                OR cidataexp IS NULL 
                OR cnp IS NULL OR cnp = '' OR LENGTH(cnp) != 13
                OR (cidataexp IS NOT NULL AND cidataexp <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            )");
        $membri_actualizare_cnp_ci = (int) $stmt->fetch()['n'];
    }
} catch (PDOException $e) {
    $membri_actualizare_cnp_ci = 0;
}

// Număr membri cu ziua de naștere azi (pentru butonul Aniversari azi)
$membri_aniversari_azi_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as n FROM membri WHERE datanastere IS NOT NULL AND MONTH(datanastere) = MONTH(CURDATE()) AND DAY(datanastere) = DAY(CURDATE())");
    $membri_aniversari_azi_count = (int) $stmt->fetch()['n'];
} catch (PDOException $e) {}

// Încărcare membri cu paginare
try {
    // Dacă există căutare, prioritizăm membrii activi
    $order_by = $sort_col . ' ' . $sort_dir;
    if (!empty($cautare)) {
        // Membrii activi apar primii, apoi ceilalți
        $order_by = "(CASE WHEN status_dosar = 'Activ' OR status_dosar IS NULL THEN 0 ELSE 1 END), " . $order_by;
    }
    $order_by .= ", nume ASC, prenume ASC";
    
    $sql = "SELECT id, dosarnr, status_dosar, nume, prenume, datanastere, ciseria, cinumar, telefonnev, email, cidataelib, cidataexp, ceexp, gdpr, cnp, sex, hgrad, expira_ci_notificat, expira_ch_notificat, cielib 
            FROM membri 
            $where 
            ORDER BY $order_by
            LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $membri = $stmt->fetchAll();
} catch (PDOException $e) {
    // Verifică dacă eroarea este legată de inexistența tabelului sau a coloanei
    $error_msg = $e->getMessage();
    if (strpos($error_msg, "doesn't exist") !== false || strpos($error_msg, "Unknown column") !== false) {
        $eroare_bd = 'Tabelul membri sau o coloană necesară nu există în baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '. Rulați schema.sql și, dacă e cazul, schema_update.sql. Eroare: ' . htmlspecialchars($error_msg);
    } else {
        $eroare_bd = 'Eroare la încărcarea membrilor: ' . htmlspecialchars($error_msg);
    }
    $membri = [];
}

// ID-uri membri scutiți de cotizație (pentru afișare buton "Scutit de cotizatie")
$membri_scutiti_cotizatie_ids = [];
try {
    $membri_scutiti_cotizatie_ids = cotizatii_membri_scutiti_ids($pdo);
} catch (PDOException $e) {}

// ID-uri membri care au achitat cotizația pentru anul curent + valori cotizație pe grad (pentru modal Încasări)
$membri_cotizatie_achitata_an_curent = [];
$valori_cotizatie_an_curent = [];
try {
    cotizatii_ensure_tables($pdo);
    $an_curent = (int)date('Y');
    $membri_cotizatie_achitata_an_curent = incasari_membri_cotizatie_achitata_an($pdo, $an_curent);
    $rows_cot = $pdo->query("SELECT grad_handicap, valoare_cotizatie FROM cotizatii_anuale WHERE anul = " . $an_curent)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows_cot as $r) { $valori_cotizatie_an_curent[$r['grad_handicap']] = (float)$r['valoare_cotizatie']; }
} catch (PDOException $e) {}

// Funcție pentru calcularea vârstei
function calculeaza_varsta($data_nastere) {
    if (empty($data_nastere)) return '-';
    $birth = new DateTime($data_nastere);
    $today = new DateTime();
    return $today->diff($birth)->y;
}

// Funcție pentru generarea link-ului de sortare
function sort_link($col, $label, $current_col, $current_dir) {
    global $status_filter, $cautare, $per_page, $avertizari_filter, $aniversari_azi_filter, $actualizare_cnp_ci_filter;
    $new_dir = ($current_col === $col && $current_dir === 'ASC') ? 'desc' : 'asc';
    $icon = '';
    if ($current_col === $col) {
        $icon = $current_dir === 'ASC' ? ' <i data-lucide="chevron-up" class="inline w-3 h-3"></i>' : ' <i data-lucide="chevron-down" class="inline w-3 h-3"></i>';
    }
    $cautare_encoded = urlencode($cautare ?? '');
    $url = "membri.php?sort=$col&dir=$new_dir&per_page=$per_page&status=$status_filter" . (!empty($cautare) ? "&cautare=$cautare_encoded" : '') . ($avertizari_filter ? '&avertizari=1' : '') . ($actualizare_cnp_ci_filter ? '&actualizare_cnp_ci=1' : '') . ($aniversari_azi_filter ? '&aniversari_azi=1' : '');
    return "<a href=\"$url\" class=\"hover:text-amber-600 dark:hover:text-amber-400\">$label$icon</a>";
}

$deschide_formular = !empty($eroare) && $_SERVER['REQUEST_METHOD'] === 'POST';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Management Membri</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 dark:border-amber-500 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <p class="font-semibold mb-2"><?php echo htmlspecialchars($eroare_bd); ?></p>
            <p class="text-sm mt-2">
                <strong>Pași pentru rezolvare:</strong><br>
                1. Deschideți panoul MySQL (cPanel → phpMyAdmin sau MySQL de pe server)<br>
                2. Selectați baza de date <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded"><?php echo htmlspecialchars(defined('DB_NAME') ? DB_NAME : ''); ?></code><br>
                3. Rulați scriptul <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded">schema.sql</code>; dacă tabelul există deja, rulați <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded">schema_update.sql</code> sau <code class="bg-amber-200 dark:bg-amber-800 px-1 rounded">schema_update_simplu.sql</code>
            </p>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <!-- Stânga: câmp căutare + butoane -->
            <form method="get" action="/membri" class="flex items-center gap-2 shrink-0">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_col); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars(strtolower($sort_dir)); ?>">
                <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php if ($avertizari_filter): ?><input type="hidden" name="avertizari" value="1"><?php endif; ?>
                <?php if ($actualizare_cnp_ci_filter): ?><input type="hidden" name="actualizare_cnp_ci" value="1"><?php endif; ?>
                <?php if ($aniversari_azi_filter): ?><input type="hidden" name="aniversari_azi" value="1"><?php endif; ?>
                <div class="relative">
                    <input type="search" 
                           name="cautare" 
                           value="<?php echo htmlspecialchars($cautare); ?>" 
                           placeholder="Caută membri..." 
                           class="w-64 pl-10 pr-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-label="Caută membri">
                    <button type="submit" 
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400"
                            aria-label="Caută">
                        <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>
                <button type="submit" 
                        name="reset"
                        value="1"
                        onclick="this.form.querySelector('input[name=cautare]').value='';"
                        class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 shrink-0"
                        aria-label="Resetează căutarea">
                    <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                </button>
            </form>

            <!-- Centru: butoane tipuri membri -->
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <a href="membri.php?status=activi&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $status_filter === 'activi' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                    Membri Activi (<?php echo $membri_activi_count; ?>)
                </a>
                <a href="membri.php?status=suspendati&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $status_filter === 'suspendati' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                    Membri Suspendati/Expirati (<?php echo $membri_suspendati_expirati_count; ?>)
                </a>
                <a href="membri.php?status=arhiva&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors <?php echo $status_filter === 'arhiva' ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                    Arhiva Membri
                </a>
                <a href="membri.php?status=activi&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?>&avertizari=<?php echo $avertizari_filter ? '0' : '1'; ?><?php echo $actualizare_cnp_ci_filter ? '&actualizare_cnp_ci=1' : ''; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2 <?php echo $avertizari_filter ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   aria-label="<?php echo $avertizari_filter ? 'Dezactivează filtrarea după actualizare date' : 'Afișează doar membrii cu date de actualizat'; ?>">
                    <i data-lucide="alert-triangle" class="w-4 h-4" aria-hidden="true"></i>
                    Actualizare date (<?php echo $membri_cu_avertizari; ?>)
                </a>
                <?php if ($membri_actualizare_cnp_ci > 0): ?>
                <a href="membri.php?status=<?php echo urlencode($status_filter); ?>&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?>&actualizare_cnp_ci=<?php echo $actualizare_cnp_ci_filter ? '0' : '1'; ?><?php echo $aniversari_azi_filter ? '&aniversari_azi=1' : ''; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2 <?php echo $actualizare_cnp_ci_filter ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   aria-label="<?php echo $actualizare_cnp_ci_filter ? 'Dezactivează filtrarea după actualizare CNP/CI' : 'Afișează doar membrii care necesită actualizare CNP/CI'; ?>">
                    <i data-lucide="file-edit" class="w-4 h-4" aria-hidden="true"></i>
                    Actualizare CNP/CI (<?php echo $membri_actualizare_cnp_ci; ?>)
                </a>
                <?php endif; ?>
                <a href="membri.php?status=<?php echo urlencode($status_filter); ?>&amp;reset_mesaj=1<?php echo !empty($cautare) ? '&cautare=' . urlencode($cautare) : ''; ?>&per_page=<?php echo $per_page; ?><?php echo $avertizari_filter ? '&avertizari=1' : ''; ?><?php echo $actualizare_cnp_ci_filter ? '&actualizare_cnp_ci=1' : ''; ?>&aniversari_azi=<?php echo $aniversari_azi_filter ? '0' : '1'; ?>"
                   class="px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2 <?php echo $aniversari_azi_filter ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   aria-label="<?php echo $aniversari_azi_filter ? 'Dezactivează filtrarea aniversări azi' : 'Afișează doar membrii care își serbează ziua de naștere azi'; ?>">
                    <i data-lucide="cake" class="w-4 h-4" aria-hidden="true"></i>
                    Aniversari azi (<?php echo $membri_aniversari_azi_count; ?>)
                </a>
            </div>

            <!-- Dreapta: buton adaugă membru -->
            <button type="button"
                    onclick="document.getElementById('formular-membru').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition shrink-0"
                    aria-label="Deschide formular pentru adăugare membru nou"
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    id="btn-adauga-membru">
                <i data-lucide="user-plus" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Adaugă Membru Nou
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table id="tabel-membri" class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista membrilor asociației">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="dosarnr">
                                <div class="flex items-center justify-between">
                                    <span><?php echo sort_link('dosarnr', 'Nr. Dosar', $sort_col, strtolower($sort_dir)); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="nume">
                                <div class="flex items-center justify-between">
                                    <span><?php echo sort_link('nume', 'Nume și Prenume', $sort_col, strtolower($sort_dir)); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="datanastere">
                                <div class="flex items-center justify-between">
                                    <span><?php echo sort_link('datanastere', 'Data Nașterii', $sort_col, strtolower($sort_dir)); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="varsta">
                                <div class="flex items-center justify-between">
                                    <span>Vârstă</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="ci">
                                <div class="flex items-center justify-between">
                                    <span><?php echo sort_link('ciseria', 'Seria și Nr. C.I.', $sort_col, strtolower($sort_dir)); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="hgrad">
                                <div class="flex items-center justify-between">
                                    <span><?php echo sort_link('hgrad', 'Grad Handicap', $sort_col, strtolower($sort_dir)); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="telefon">
                                <div class="flex items-center justify-between">
                                    <span><?php echo sort_link('telefonnev', 'Telefon', $sort_col, strtolower($sort_dir)); ?></span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="avertizari">
                                <div class="flex items-center justify-between">
                                    <span>Actualizare date</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider resizable-th" data-col="actiuni">
                                <div class="flex items-center justify-between">
                                    <span>Acțiuni</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($membri)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-600 dark:text-gray-400">
                                <?php 
                                if (!empty($cautare)) {
                                    echo 'Nu s-au găsit membri care să corespundă căutării.';
                                } elseif ($status_filter === 'activi') {
                                    echo 'Nu există membri activi.';
                                } elseif ($status_filter === 'suspendati') {
                                    echo 'Nu există membri suspendați sau expirați.';
                                } elseif ($status_filter === 'arhiva') {
                                    echo 'Nu există membri în arhivă (decedați).';
                                } else {
                                    echo 'Nu există membri înregistrați.';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($membri as $m): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700 membri-table-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php 
                                $dosarnr = htmlspecialchars($m['dosarnr'] ?? '-');
                                $status_afisat = $m['status_dosar'] ?? 'Activ';
                                $status_colors = [
                                    'Activ' => 'text-green-600 dark:text-green-400',
                                    'Expirat' => 'text-orange-600 dark:text-orange-400',
                                    'Suspendat' => 'text-yellow-600 dark:text-yellow-400',
                                    'Retras' => 'text-slate-600 dark:text-gray-400',
                                    'Decedat' => 'text-red-600 dark:text-red-400'
                                ];
                                $color_class = $status_colors[$status_afisat] ?? 'text-slate-600 dark:text-gray-400';
                                ?>
                                <a href="membru-profil.php?id=<?php echo $m['id']; ?>" 
                                   class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 hover:underline font-medium">
                                    <?php echo $dosarnr; ?> - <span class="<?php echo $color_class; ?>"><?php echo htmlspecialchars($status_afisat); ?></span>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php $m_id = (int)($m['id'] ?? 0); $nume_complet = trim($m['nume'] . ' ' . $m['prenume']); ?>
                                <?php if ($m_id > 0): ?>
                                <a href="membru-profil.php?id=<?php echo $m_id; ?>"
                                   class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 rounded"
                                   aria-label="Vezi profilul lui <?php echo htmlspecialchars($nume_complet); ?>">
                                    <?php echo htmlspecialchars($nume_complet); ?>
                                </a>
                                <?php else: ?>
                                <span><?php echo htmlspecialchars($nume_complet); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php echo $m['datanastere'] ? date(DATE_FORMAT, strtotime($m['datanastere'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php 
                                $varsta = calculeaza_varsta($m['datanastere']);
                                echo $varsta !== '-' ? ((int)$varsta . ' ani') : '-';
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php 
                                $ci = '';
                                if (!empty($m['ciseria'])) $ci .= $m['ciseria'];
                                if (!empty($m['cinumar'])) $ci .= ($ci ? ' ' : '') . $m['cinumar'];
                                echo htmlspecialchars($ci ?: '-'); 
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($m['hgrad'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($m['telefonnev'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo render_alerts_badge($m, $m['id'], $pdo); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="membru-profil.php?id=<?php echo (int)($m['id'] ?? 0); ?>"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-amber-400 dark:border-amber-500 bg-amber-100 dark:bg-amber-800/70 text-amber-900 dark:text-amber-100 hover:bg-amber-200 dark:hover:bg-amber-700 font-medium"
                                       aria-label="Editează membru">
                                        <i data-lucide="edit" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Editează</span>
                                    </a>
                                    <button type="button"
                                            data-action="generare-document"
                                            data-membru-id="<?php echo $m['id']; ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-400 dark:border-blue-500 bg-blue-100 dark:bg-blue-800/70 text-blue-900 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-700 font-medium"
                                            aria-label="Generează document pentru <?php echo htmlspecialchars($m['nume'] . ' ' . $m['prenume']); ?>">
                                        <i data-lucide="file-text" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Generează Document</span>
                                    </button>
                                    <?php
                                    $cot_achitata_incasari = in_array($m['id'], $membri_cotizatie_achitata_an_curent) || in_array($m['id'], $membri_scutiti_cotizatie_ids);
                                    $val_cot = $valori_cotizatie_an_curent[$m['hgrad'] ?? 'Fara handicap'] ?? 0;
                                    if (in_array($m['id'], $membri_scutiti_cotizatie_ids)): ?>
                                    <a href="setari.php?tab=cotizatii"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-400 dark:border-gray-500 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 font-medium"
                                       aria-label="Scutit de cotizație – vezi detalii">
                                        <i data-lucide="shield-check" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Scutit de cotizatie</span>
                                    </a>
                                    <?php else: ?>
                                    <button type="button"
                                            class="btn-deschide-incasari inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-purple-400 dark:border-purple-500 bg-purple-100 dark:bg-purple-800/70 text-purple-900 dark:text-purple-100 hover:bg-purple-200 dark:hover:bg-purple-700 font-medium"
                                            data-membru-id="<?php echo (int)$m['id']; ?>"
                                            data-valoare-cot="<?php echo number_format($val_cot, 2, '.', ''); ?>"
                                            data-cot-achitata="<?php echo $cot_achitata_incasari ? '1' : '0'; ?>"
                                            aria-label="Încasează">
                                        <i data-lucide="dollar-sign" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Încasează</span>
                                    </button>
                                    <?php endif; ?>
                                    <?php 
                                    $mesaj_subiect = $_SESSION['membri_mesaj_subiect'] ?? '';
                                    $mesaj_continut = $_SESSION['membri_mesaj_continut'] ?? '';
                                    if (!empty($m['email'])): 
                                        $mailto = 'mailto:' . htmlspecialchars($m['email']);
                                        if ($mesaj_subiect !== '' || $mesaj_continut !== '') {
                                            $mailto .= '?';
                                            if ($mesaj_subiect !== '') $mailto .= 'subject=' . rawurlencode($mesaj_subiect);
                                            if ($mesaj_continut !== '') $mailto .= ($mesaj_subiect !== '' ? '&' : '') . 'body=' . rawurlencode(str_replace(["\r\n", "\n"], "\n", $mesaj_continut));
                                        }
                                    ?>
                                    <a href="<?php echo $mailto; ?>" 
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-400 dark:border-emerald-500 bg-emerald-100 dark:bg-emerald-800/70 text-emerald-900 dark:text-emerald-100 hover:bg-emerald-200 dark:hover:bg-emerald-700 font-medium"
                                       aria-label="Trimite email">
                                        <i data-lucide="mail" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>Email</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($m['telefonnev'])): 
                                        $wa_url = 'https://wa.me/' . preg_replace('/\D/', '', $m['telefonnev']);
                                        if ($mesaj_continut !== '') $wa_url .= '?text=' . rawurlencode($mesaj_continut);
                                    ?>
                                    <a href="<?php echo htmlspecialchars($wa_url); ?>" 
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-green-400 dark:border-green-500 bg-green-100 dark:bg-green-800/70 text-green-900 dark:text-green-100 hover:bg-green-200 dark:hover:bg-green-700 font-medium"
                                       aria-label="Mesaj WhatsApp">
                                        <i data-lucide="message-circle" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                                        <span>WhatsApp</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginare și selector număr rezultate -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <label for="per_page_select" class="text-sm text-slate-700 dark:text-gray-300">Rezultate pe pagină:</label>
                    <select id="per_page_select" 
                            onchange="window.location.href = '<?php 
                                $url_params = array_merge($_GET, ['per_page' => '', 'page' => '1']);
                                if (!isset($url_params['status'])) $url_params['status'] = $status_filter;
                                echo 'membri.php?' . http_build_query($url_params) . 'per_page='; 
                            ?>' + this.value"
                            class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500"
                            aria-label="Selectează numărul de rezultate afișate pe pagină">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                </div>
                
                <div class="flex items-center gap-2">
                    <?php if ($total_pages > 1): ?>
                    <span class="text-sm text-slate-700 dark:text-gray-300">
                        Pagina <?php echo $page; ?> din <?php echo $total_pages; ?> 
                        (<?php echo $total_membri; ?> membri)
                    </span>
                    
                    <div class="flex gap-1">
                        <?php
                        $query_params = array_merge($_GET, []);
                        unset($query_params['page']);
                        // Asigură-te că status este inclus în URL
                        if (!isset($query_params['status'])) {
                            $query_params['status'] = $status_filter;
                        }
                        $base_url = 'membri.php?' . http_build_query($query_params) . '&page=';
                        
                        // Buton Pagina Anterioară
                        if ($page > 1):
                        ?>
                        <a href="<?php echo $base_url . ($page - 1); ?>" 
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">
                            <i data-lucide="chevron-left" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        // Afișare pagini (maxim 5 pagini în jurul paginii curente)
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1):
                        ?>
                        <a href="<?php echo $base_url . '1'; ?>" 
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">1</a>
                        <?php if ($start_page > 2): ?>
                        <span class="px-3 py-1.5 text-slate-500 dark:text-gray-400">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="<?php echo $base_url . $i; ?>" 
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg <?php echo $i == $page ? 'bg-amber-600 text-white border-amber-600' : 'hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                        <span class="px-3 py-1.5 text-slate-500 dark:text-gray-400">...</span>
                        <?php endif; ?>
                        <a href="<?php echo $base_url . $total_pages; ?>" 
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">
                            <?php echo $total_pages; ?>
                        </a>
                        <?php endif; ?>
                        
                        <?php
                        // Buton Pagina Următoare
                        if ($page < $total_pages):
                        ?>
                        <a href="<?php echo $base_url . ($page + 1); ?>" 
                           class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300">
                            <i data-lucide="chevron-right" class="w-4 h-4" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="text-sm text-slate-700 dark:text-gray-300">
                        <?php echo $total_membri; ?> membri
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Secțiune dreapta jos: precompletare subiect și mesaj pentru WhatsApp/Email (1/2 pagină) -->
        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6" aria-label="Mesaj pentru trimitere către membri">
            <div class="hidden lg:block" aria-hidden="true"></div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <i data-lucide="message-square" class="w-5 h-5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                    Mesaj pentru WhatsApp / Email
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-3">Subiectul și mesajul se precompletează la linkurile Email și WhatsApp din tabel. Se resetează la schimbarea afișării (butoanele de filtrare).</p>
                <form method="post" action="/membri" id="form-mesaj-precompletat">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="save_mesaj_precompletat" value="1">
                    <input type="hidden" name="redirect_status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <input type="hidden" name="redirect_sort" value="<?php echo htmlspecialchars($sort_col); ?>">
                    <input type="hidden" name="redirect_dir" value="<?php echo htmlspecialchars(strtolower($sort_dir)); ?>">
                    <input type="hidden" name="redirect_per_page" value="<?php echo (int)$per_page; ?>">
                    <input type="hidden" name="redirect_page" value="<?php echo (int)$page; ?>">
                    <input type="hidden" name="redirect_cautare" value="<?php echo htmlspecialchars($cautare); ?>">
                    <input type="hidden" name="redirect_avertizari" value="<?php echo $avertizari_filter ? '1' : ''; ?>">
                    <input type="hidden" name="redirect_actualizare_cnp_ci" value="<?php echo $actualizare_cnp_ci_filter ? '1' : ''; ?>">
                    <input type="hidden" name="redirect_aniversari_azi" value="<?php echo $aniversari_azi_filter ? '1' : ''; ?>">
                    <div class="space-y-3">
                        <div>
                            <label for="mesaj_subiect" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Subiect (pentru email)</label>
                            <input type="text" id="mesaj_subiect" name="mesaj_subiect" value="<?php echo htmlspecialchars($_SESSION['membri_mesaj_subiect'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   placeholder="Subiect email">
                        </div>
                        <div>
                            <label for="mesaj_continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Mesaj (WhatsApp și conținut email)</label>
                            <textarea id="mesaj_continut" name="mesaj_continut" rows="4"
                                      class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                      placeholder="Text mesaj"><?php echo htmlspecialchars($_SESSION['membri_mesaj_continut'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează mesajul pentru precompletare">Salvează mesaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- Dialog formular adăugare membru -->
<dialog id="formular-membru"
        role="dialog"
        aria-modal="true"
        aria-labelledby="titlu-formular"
        aria-describedby="desc-formular"
        class="p-0 rounded-lg shadow-xl max-w-4xl w-[calc(100%-1rem)] sm:w-full mx-2 sm:mx-auto max-h-[90vh] overflow-y-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 id="titlu-formular" class="text-lg font-bold text-slate-900 dark:text-white">Adaugă Membru Nou</h2>
            <button type="button"
                    onclick="document.getElementById('formular-membru').close()"
                    class="text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200"
                    aria-label="Închide">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
        <p id="desc-formular" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completați câmpurile de mai jos. Câmpurile marcate cu * sunt obligatorii.</p>

        <?php require_once APP_ROOT . '/app/views/partials/membri_form.php'; ?>
        <?php render_formular_membru(null, $eroare); ?>
    </div>
</dialog>

<?php require_once APP_ROOT . '/includes/documente_modal.php'; ?>
<?php require_once APP_ROOT . '/includes/incasari_modal.php'; ?>

<style>
.resizable-th {
    position: relative;
    user-select: none;
}
.resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    width: 4px;
    height: 100%;
    cursor: col-resize;
    z-index: 10;
}
.resize-handle:hover {
    background-color: #f59e0b;
}
.resizable-th:last-child .resize-handle {
    display: none;
}
/* Setează înălțimea rândului = înălțimea butoanelor + 10px sus/jos */
.membri-table-row {
    min-height: calc(2.5rem + 20px); /* py-2 (0.5rem) + text-sm (~1rem) + py-2 (0.5rem) + 10px sus/jos = ~2.5rem + 20px */
}
.membri-table-row td {
    padding-top: calc(0.5rem + 5px);
    padding-bottom: calc(0.5rem + 5px);
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    <?php if (!empty($deschide_formular)): ?>
    document.getElementById('formular-membru').showModal();
    document.getElementById('btn-adauga-membru').setAttribute('aria-expanded', 'true');
    <?php endif; ?>

    // Gestionează deschiderea dialog
    var btnAdauga = document.getElementById('btn-adauga-membru');
    var dialog = document.getElementById('formular-membru');

    if (dialog) {
        dialog.addEventListener('close', function() {
            if (btnAdauga) btnAdauga.setAttribute('aria-expanded', 'false');
        });

        if (btnAdauga) {
            btnAdauga.addEventListener('click', function() {
                btnAdauga.setAttribute('aria-expanded', 'true');
            });
        }
    }
    
    // Inițializează iconițele pentru badge-urile de avertizare
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Drag and drop pentru redimensionarea coloanelor
    const table = document.getElementById('tabel-membri');
    if (table) {
        const headers = table.querySelectorAll('.resizable-th');
        let currentResize = null;
        
        headers.forEach((header, index) => {
            const handle = header.querySelector('.resize-handle');
            if (!handle) return;
            
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                currentResize = {
                    header: header,
                    startX: e.clientX,
                    startWidth: header.offsetWidth,
                    index: index
                };
                document.addEventListener('mousemove', resizeColumn);
                document.addEventListener('mouseup', stopResize);
                handle.style.backgroundColor = '#f59e0b';
            });
        });
        
        function resizeColumn(e) {
            if (!currentResize) return;
            
            const diff = e.clientX - currentResize.startX;
            const newWidth = Math.max(50, currentResize.startWidth + diff);
            currentResize.header.style.width = newWidth + 'px';
            currentResize.header.style.minWidth = newWidth + 'px';
            
            // Salvează lățimea în localStorage
            const colName = currentResize.header.getAttribute('data-col');
            localStorage.setItem('col_width_' + colName, newWidth);
        }
        
        function stopResize() {
            if (currentResize) {
                const handle = currentResize.header.querySelector('.resize-handle');
                if (handle) handle.style.backgroundColor = '';
            }
            currentResize = null;
            document.removeEventListener('mousemove', resizeColumn);
            document.removeEventListener('mouseup', stopResize);
        }
        
        // Restaurează lățimile salvate
        headers.forEach(header => {
            const colName = header.getAttribute('data-col');
            const savedWidth = localStorage.getItem('col_width_' + colName);
            if (savedWidth) {
                header.style.width = savedWidth + 'px';
                header.style.minWidth = savedWidth + 'px';
            }
        });
    }
});
</script>
</body>
</html>
