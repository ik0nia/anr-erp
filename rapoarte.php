<?php
/**
 * Pagină Rapoarte - CRM ANR Bihor
 * Afișează indicatori și statistici despre membri, Newsletter și Registru Interacțiuni
 */
require_once __DIR__ . '/config.php';
require_once 'includes/newsletter_helper.php';
require_once 'includes/registru_interactiuni_v2_helper.php';
require_once 'includes/liste_helper.php';
include 'header.php';
include 'sidebar.php';

$tab_rapoarte = 'membri';
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'newsletter') $tab_rapoarte = 'newsletter';
    elseif ($_GET['tab'] === 'interactiuni') $tab_rapoarte = 'interactiuni';
    elseif ($_GET['tab'] === 'statistici') $tab_rapoarte = 'statistici';
}
$lista_newsletter_rapoarte = [];
$raport_interactiuni = ['total_apeluri' => 0, 'total_vizite' => 0, 'total_general' => 0, 'categorii' => []];
if ($tab_rapoarte === 'newsletter') {
    $lista_newsletter_rapoarte = newsletter_lista_rapoarte($pdo);
}
if ($tab_rapoarte === 'interactiuni') {
    // Raport interacțiuni - folosind modulul v2
    try {
        ensure_registru_v2_tables($pdo);
        $stmt = $pdo->query("SELECT tip, COUNT(*) as n FROM registru_interactiuni_v2 GROUP BY tip");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_apeluri = 0;
        $total_vizite = 0;
        foreach ($results as $r) {
            if ($r['tip'] === 'apel') $total_apeluri = (int)$r['n'];
            if ($r['tip'] === 'vizita') $total_vizite = (int)$r['n'];
        }
        $raport_interactiuni = [
            'total_apeluri' => $total_apeluri,
            'total_vizite' => $total_vizite,
            'total_general' => $total_apeluri + $total_vizite,
            'categorii' => []
        ];
    } catch (PDOException $e) {
        error_log('Eroare raport interacțiuni: ' . $e->getMessage());
    }
}

// Calculare indicatori
try {
    // Total membri (toți membrii din baza de date)
    $stmt_total = $pdo->query("SELECT COUNT(*) as total FROM membri");
    $result_total = $stmt_total->fetch(PDO::FETCH_ASSOC);
    $total_activi = (int)($result_total['total'] ?? 0);
    
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
    
    // Membri activi (doar status_dosar = 'Activ')
    $stmt_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ'");
    $result_activi = $stmt_activi->fetch(PDO::FETCH_ASSOC);
    $membri_activi = (int)($result_activi['total'] ?? 0);
    
    // Membri suspendați/expirați
    $stmt_suspendati = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar IN ('Suspendat', 'Expirat')");
    $result_suspendati = $stmt_suspendati->fetch(PDO::FETCH_ASSOC);
    $membri_suspendati = (int)($result_suspendati['total'] ?? 0);
    
    // Membri în arhivă (decedați)
    $stmt_arhiva = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Decedat'");
    $result_arhiva = $stmt_arhiva->fetch(PDO::FETCH_ASSOC);
    $membri_arhiva = (int)($result_arhiva['total'] ?? 0);
} catch (PDOException $e) {
    $total_activi = 0;
    $grad_grav = 0;
    $grad_accentuat = 0;
    $grad_mediu = 0;
    $femei = 0;
    $barbati = 0;
    $membri_activi = 0;
    $membri_suspendati = 0;
    $membri_arhiva = 0;
}

// calculeaza_varsta() este furnizată de includes/liste_helper.php

// Calculare statistici detaliate pentru membri activi (doar dacă tab-ul este "statistici")
$statistici_membri = null;
$statistici_localitati = null;

if ($tab_rapoarte === 'statistici') {
    try {
        // Membri activi - total
        $stmt_total_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ'");
        $total_activi_stat = (int)$stmt_total_activi->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Membri activi - Femei/Bărbați
        $stmt_femei_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND sex = 'Feminin'");
        $femei_activi = (int)$stmt_femei_activi->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt_barbati_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND sex = 'Masculin'");
        $barbati_activi = (int)$stmt_barbati_activi->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Membri activi - Urban/Rural
        $stmt_urban_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND tipmediuur = 'Urban'");
        $urban_activi = (int)$stmt_urban_activi->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt_rural_activi = $pdo->query("SELECT COUNT(*) as total FROM membri WHERE status_dosar = 'Activ' AND tipmediuur = 'Rural'");
        $rural_activi = (int)$stmt_rural_activi->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Grupe de vârstă pentru membri activi
        $grupe_varsta = [];
        $grupe_varsta_labels = [
            '0-18' => [0, 18],
            '18-35' => [18, 35],
            '35-45' => [35, 45],
            '45-55' => [45, 55],
            '55-65' => [55, 65],
            '65-75' => [65, 75],
            '75-85' => [75, 85],
            '85+' => [85, 200]
        ];
        
        foreach ($grupe_varsta_labels as $label => $range) {
            $min_age = $range[0];
            $max_age = $range[1] === 200 ? 200 : $range[1];
            
            if ($max_age === 200) {
                // 85+ ani
                $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN sex = 'Feminin' THEN 1 ELSE 0 END) as femei,
                    SUM(CASE WHEN sex = 'Masculin' THEN 1 ELSE 0 END) as barbati,
                    SUM(CASE WHEN tipmediuur = 'Urban' THEN 1 ELSE 0 END) as urban,
                    SUM(CASE WHEN tipmediuur = 'Rural' THEN 1 ELSE 0 END) as rural
                    FROM membri 
                    WHERE status_dosar = 'Activ'
                    AND datanastere IS NOT NULL
                    AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$min_age]);
            } else {
                // Grupe normale
                $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN sex = 'Feminin' THEN 1 ELSE 0 END) as femei,
                    SUM(CASE WHEN sex = 'Masculin' THEN 1 ELSE 0 END) as barbati,
                    SUM(CASE WHEN tipmediuur = 'Urban' THEN 1 ELSE 0 END) as urban,
                    SUM(CASE WHEN tipmediuur = 'Rural' THEN 1 ELSE 0 END) as rural
                    FROM membri 
                    WHERE status_dosar = 'Activ'
                    AND datanastere IS NOT NULL
                    AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= ?
                    AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$min_age, $max_age]);
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $grupe_varsta[$label] = [
                'total' => (int)$result['total'],
                'femei' => (int)$result['femei'],
                'barbati' => (int)$result['barbati'],
                'urban' => (int)$result['urban'],
                'rural' => (int)$result['rural']
            ];
        }
        
        $statistici_membri = [
            'total' => $total_activi_stat,
            'femei' => $femei_activi,
            'barbati' => $barbati_activi,
            'urban' => $urban_activi,
            'rural' => $rural_activi,
            'grupe_varsta' => $grupe_varsta
        ];
        
        // Statistici pe localități
        $stmt_localitati = $pdo->query("
            SELECT 
                domloc as localitate,
                judet_domiciliu as judet,
                primaria,
                COUNT(*) as total,
                SUM(CASE WHEN sex = 'Feminin' THEN 1 ELSE 0 END) as femei,
                SUM(CASE WHEN sex = 'Masculin' THEN 1 ELSE 0 END) as barbati,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 0 AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < 18 THEN 1 ELSE 0 END) as varsta_0_18,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 18 AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < 35 THEN 1 ELSE 0 END) as varsta_18_35,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 35 AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) < 65 THEN 1 ELSE 0 END) as varsta_35_65,
                SUM(CASE WHEN datanastere IS NOT NULL AND TIMESTAMPDIFF(YEAR, datanastere, CURDATE()) >= 65 THEN 1 ELSE 0 END) as varsta_65_plus
            FROM membri
            WHERE status_dosar = 'Activ'
            AND domloc IS NOT NULL AND domloc != ''
            GROUP BY domloc, judet_domiciliu, primaria
            ORDER BY domloc ASC, judet_domiciliu ASC
        ");
        
        $statistici_localitati = $stmt_localitati->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Eroare calculare statistici: ' . $e->getMessage());
        $statistici_membri = [
            'total' => 0,
            'femei' => 0,
            'barbati' => 0,
            'urban' => 0,
            'rural' => 0,
            'grupe_varsta' => []
        ];
        $statistici_localitati = [];
    }
}
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Rapoarte</h1>
    </header>
    
    <div class="p-6 overflow-y-auto flex-1">
        <nav class="mb-6 flex gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Tab-uri rapoarte">
            <a href="rapoarte.php" role="tab" aria-selected="<?php echo $tab_rapoarte === 'membri' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'membri' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Indicatori Membri
            </a>
            <a href="rapoarte.php?tab=interactiuni" role="tab" aria-selected="<?php echo $tab_rapoarte === 'interactiuni' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'interactiuni' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Registru Interacțiuni
            </a>
            <a href="rapoarte.php?tab=newsletter" role="tab" aria-selected="<?php echo $tab_rapoarte === 'newsletter' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'newsletter' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Newsletter
            </a>
            <a href="rapoarte.php?tab=statistici" role="tab" aria-selected="<?php echo $tab_rapoarte === 'statistici' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'statistici' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Statistici
            </a>
        </nav>

        <?php if ($tab_rapoarte === 'membri'): ?>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Indicatori Membri</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Total Membri -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total Membri</p>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white mt-2"><?php echo $total_activi; ?></p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-lg">
                            <i data-lucide="users" class="w-8 h-8 text-slate-600 dark:text-gray-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Membri Activi -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Membri Activi</p>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2"><?php echo $membri_activi; ?></p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <i data-lucide="user-check" class="w-8 h-8 text-green-600 dark:text-green-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Membri Suspendati/Expirati -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Membri Suspendati/Expirati</p>
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2"><?php echo $membri_suspendati; ?></p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <i data-lucide="user-x" class="w-8 h-8 text-yellow-600 dark:text-yellow-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Arhiva Membri -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Arhiva Membri</p>
                            <p class="text-3xl font-bold text-slate-600 dark:text-gray-400 mt-2"><?php echo $membri_arhiva; ?></p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-lg">
                            <i data-lucide="archive" class="w-8 h-8 text-slate-600 dark:text-gray-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Gradul Grav -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Gradul Grav</p>
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2"><?php echo $grad_grav; ?></p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <i data-lucide="alert-circle" class="w-8 h-8 text-red-600 dark:text-red-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Gradul Accentuat -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Gradul Accentuat</p>
                            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2"><?php echo $grad_accentuat; ?></p>
                        </div>
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-orange-600 dark:text-orange-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Gradul Mediu -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Gradul Mediu</p>
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2"><?php echo $grad_mediu; ?></p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <i data-lucide="info" class="w-8 h-8 text-yellow-600 dark:text-yellow-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Femei -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Femei</p>
                            <p class="text-3xl font-bold text-pink-600 dark:text-pink-400 mt-2"><?php echo $femei; ?></p>
                        </div>
                        <div class="p-3 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                            <i data-lucide="user" class="w-8 h-8 text-pink-600 dark:text-pink-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Bărbați -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Bărbați</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo $barbati; ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <i data-lucide="user" class="w-8 h-8 text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'interactiuni'): ?>
        <div class="mb-6" role="region" aria-labelledby="raport-interactiuni-heading">
            <h2 id="raport-interactiuni-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Registru Interacțiuni – Totaluri și categorii</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total apeluri</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo (int)$raport_interactiuni['total_apeluri']; ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <i data-lucide="phone" class="w-8 h-8 text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total vizite</p>
                            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?php echo (int)$raport_interactiuni['total_vizite']; ?></p>
                        </div>
                        <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                            <i data-lucide="building" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total interacțiuni</p>
                            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2"><?php echo (int)$raport_interactiuni['total_general']; ?></p>
                        </div>
                        <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                            <i data-lucide="phone-call" class="w-8 h-8 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white px-6 py-3 border-b border-slate-200 dark:border-gray-700">Categorii după subiect (număr interacțiuni)</h3>
                <?php if (empty($raport_interactiuni['categorii'])): ?>
                <p class="p-6 text-slate-600 dark:text-gray-400">Nu există încă interacțiuni înregistrate pe subiecte.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Categorii registru interacțiuni">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Categorie / Subiect</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr interacțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($raport_interactiuni['categorii'] as $nume_cat => $nr): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-3 font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($nume_cat); ?></td>
                                <td class="px-6 py-3 text-right font-semibold text-amber-600 dark:text-amber-400"><?php echo (int)$nr; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'newsletter'): ?>
        <div class="mb-6" role="region" aria-labelledby="raport-newsletter-heading">
            <h2 id="raport-newsletter-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Raport Newsletter</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($lista_newsletter_rapoarte)): ?>
                <p class="p-6 text-slate-600 dark:text-gray-400">Niciun newsletter trimis încă.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista newslettere trimise">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Subiect</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr contacte</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Categoria contacte</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data trimiterii</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($lista_newsletter_rapoarte as $nl): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($nl['subiect']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$nl['nr_recipienti']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($nl['categoria_contacte']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $nl['data_trimiterii'] ? date('d.m.Y H:i', strtotime($nl['data_trimiterii'])) : '-'; ?></td>
                                <td class="px-4 py-3">
                                    <a href="newsletter-view.php?id=<?php echo (int)$nl['id']; ?>" class="text-amber-600 dark:text-amber-400 hover:underline" aria-label="Vizualizează newsletter <?php echo htmlspecialchars($nl['subiect']); ?>">Vizualizează</a>
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

        <?php if ($tab_rapoarte === 'statistici' && $statistici_membri !== null): ?>
        <div class="mb-6" role="region" aria-labelledby="statistici-heading">
            <h2 id="statistici-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Statistici Membri Activi</h2>
            
            <!-- Total Membri Activi -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4">Număr Membri Activi: <?php echo $statistici_membri['total']; ?></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Femei/Bărbați -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">După gen:</h4>
                        <ul class="space-y-2">
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Femei:</span>
                                <span class="font-semibold text-pink-600 dark:text-pink-400"><?php echo $statistici_membri['femei']; ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Bărbați:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400"><?php echo $statistici_membri['barbati']; ?></span>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Urban/Rural -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">După mediu:</h4>
                        <ul class="space-y-2">
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Urban:</span>
                                <span class="font-semibold text-slate-900 dark:text-white"><?php echo $statistici_membri['urban']; ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Rural:</span>
                                <span class="font-semibold text-slate-900 dark:text-white"><?php echo $statistici_membri['rural']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Grupe de vârstă -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-4">Grupe de vârstă:</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Statistici grupe de vârstă">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Grupa de vârstă</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Total</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Femei</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Bărbați</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Urban</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Rural</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php foreach ($statistici_membri['grupe_varsta'] as $label => $data): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($label); ?> ani</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $data['total']; ?></td>
                                    <td class="px-4 py-3 text-sm text-pink-600 dark:text-pink-400"><?php echo $data['femei']; ?></td>
                                    <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400"><?php echo $data['barbati']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $data['urban']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $data['rural']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Statistici pe localități -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mt-6">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4">Statistici pe Localități</h3>
                
                <?php if (empty($statistici_localitati)): ?>
                <p class="text-slate-600 dark:text-gray-400">Nu există date disponibile pentru localități.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Statistici pe localități">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitate</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Total</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Femei</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Bărbați</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">0-18 ani</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">18-35 ani</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">35-65 ani</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">65+ ani</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($statistici_localitati as $loc): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    <?php 
                                    echo htmlspecialchars($loc['localitate']);
                                    if (!empty($loc['primaria']) || !empty($loc['judet'])) {
                                        echo ' (';
                                        $parts = [];
                                        if (!empty($loc['primaria'])) {
                                            $parts[] = 'Primaria ' . htmlspecialchars($loc['primaria']);
                                        }
                                        if (!empty($loc['judet'])) {
                                            $parts[] = htmlspecialchars($loc['judet']);
                                        }
                                        echo implode(', ', $parts);
                                        echo ')';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['total']; ?></td>
                                <td class="px-4 py-3 text-sm text-pink-600 dark:text-pink-400"><?php echo (int)$loc['femei']; ?></td>
                                <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400"><?php echo (int)$loc['barbati']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_0_18']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_18_35']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_35_65']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_65_plus']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
</body>
</html>
