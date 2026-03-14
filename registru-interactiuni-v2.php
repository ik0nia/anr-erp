<?php
/**
 * Pagină Registru Interacțiuni v2 - CRM ANR Bihor
 * Manager pentru înregistrarea apelurilor și vizitelor; listare, statistici lunare, statistici pe subiecte.
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';
require_once 'includes/registru_interactiuni_v2_helper.php';

$eroare = '';
$succes = '';

ensure_registru_v2_tables($pdo);

// Obține anul pentru statistici (default: anul curent)
$an_selectat = isset($_GET['an']) ? (int)$_GET['an'] : date('Y');
if ($an_selectat < 2020 || $an_selectat > 2100) {
    $an_selectat = date('Y');
}

// Obține statistici
$statistici_lunare = registru_v2_statistici_lunare($pdo, $an_selectat);
$statistici_subiecte = registru_v2_statistici_subiecte($pdo);
$interactiuni_recente_30_zile = registru_v2_interactiuni_recente($pdo, 100, 30);
$interactiuni_azi = registru_v2_interactiuni_azi($pdo);
$subiecte_interactiuni = get_subiecte_interactiuni_v2($pdo);

// Debug: verifică dacă există interacțiuni în baza de date
if (empty($interactiuni_recente_30_zile)) {
    try {
        $stmt_test = $pdo->query('SELECT COUNT(*) as total FROM registru_interactiuni_v2');
        $total_test = $stmt_test->fetch();
        if ($total_test && $total_test['total'] > 0) {
            // Există interacțiuni dar nu se încarcă - posibilă problemă cu query-ul
            error_log('Registru v2: Există ' . $total_test['total'] . ' interacțiuni în baza de date dar nu se încarcă în listă');
        }
    } catch (PDOException $e) {
        error_log('Registru v2 debug: ' . $e->getMessage());
    }
}

// Nume lunilor în română
$luni = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Registru Interacțiuni v2</h1>
        <div class="flex gap-2 items-center">
            <label for="select-an" class="text-sm text-slate-700 dark:text-gray-300 sr-only">Selectează anul</label>
            <select id="select-an" name="an" onchange="window.location.href='?an=' + this.value"
                    class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                    aria-label="Selectează anul pentru statistici">
                <?php for ($a = date('Y'); $a >= 2020; $a--): ?>
                <option value="<?php echo $a; ?>" <?php echo $an_selectat == $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <!-- Indicatori în timp real -->
        <div class="mb-6 grid grid-cols-2 gap-4" aria-label="Rezumat interacțiuni azi">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mb-1">Apeluri telefonice azi</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400" id="counter-apel-azi" aria-live="polite"><?php echo $interactiuni_azi['apel']; ?></p>
                    </div>
                    <i data-lucide="phone" class="w-10 h-10 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mb-1">Vizite la sediu azi</p>
                        <p class="text-3xl font-bold text-amber-600 dark:text-amber-400" id="counter-vizita-azi" aria-live="polite"><?php echo $interactiuni_azi['vizita']; ?></p>
                    </div>
                    <i data-lucide="building" class="w-10 h-10 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <!-- Layout 3 coloane -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coloana 1: Interacțiuni recente (30 de zile) -->
            <div class="lg:order-1 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Interacțiuni recente (30 zile)</h2>
                </div>
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista interacțiuni recente">
                        <thead class="bg-slate-100 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data/Ora</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tip</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Persoană</th>
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Subiect</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($interactiuni_recente_30_zile)): ?>
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-600 dark:text-gray-400 text-sm">Nu există interacțiuni în ultimele 30 de zile.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($interactiuni_recente_30_zile as $r): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300"><?php echo date('d.m.Y H:i', strtotime($r['data_ora'])); ?></td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded <?php echo $r['tip'] === 'apel' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200' : 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200'; ?>">
                                        <?php echo $r['tip'] === 'apel' ? 'Apel' : 'Vizită'; ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['persoana']); ?></td>
                                <td class="px-3 py-2 text-xs text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($r['subiect_alt'] ?: ($r['subiect_nume'] ?? '-')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Coloana 2: Tabel statistici lunare (mai comasat) -->
            <div class="lg:order-2 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="p-3 border-b border-slate-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Statistici lunare - <?php echo $an_selectat; ?></h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Statistici interacțiuni pe lunile anului">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-2 py-1.5 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Luna</th>
                                <th scope="col" class="px-2 py-1.5 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Apeluri</th>
                                <th scope="col" class="px-2 py-1.5 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Vizite</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php for ($luna = 1; $luna <= 12; $luna++): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-2 py-1.5 text-xs font-medium text-slate-900 dark:text-white"><?php echo substr($luni[$luna], 0, 3); ?></td>
                                <td class="px-2 py-1.5 text-xs text-center text-slate-700 dark:text-gray-300"><?php echo $statistici_lunare[$luna]['apel'] ?? 0; ?></td>
                                <td class="px-2 py-1.5 text-xs text-center text-slate-700 dark:text-gray-300"><?php echo $statistici_lunare[$luna]['vizita'] ?? 0; ?></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                        <tfoot class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <td class="px-2 py-1.5 text-xs font-semibold text-slate-900 dark:text-white">Total</td>
                                <td class="px-2 py-1.5 text-xs text-center font-semibold text-slate-900 dark:text-white">
                                    <?php 
                                    $total_apel = 0;
                                    foreach ($statistici_lunare as $stat) {
                                        $total_apel += $stat['apel'] ?? 0;
                                    }
                                    echo $total_apel;
                                    ?>
                                </td>
                                <td class="px-2 py-1.5 text-xs text-center font-semibold text-slate-900 dark:text-white">
                                    <?php 
                                    $total_vizita = 0;
                                    foreach ($statistici_lunare as $stat) {
                                        $total_vizita += $stat['vizita'] ?? 0;
                                    }
                                    echo $total_vizita;
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Coloana 3: Statistici pe subiecte -->
            <div class="lg:order-3 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Statistici pe subiecte</h2>
                </div>
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Statistici interacțiuni pe subiecte">
                        <thead class="bg-slate-100 dark:bg-gray-700 sticky top-0">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Subiect</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($statistici_subiecte)): ?>
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu există interacțiuni înregistrate.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($statistici_subiecte as $subiect => $numar): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($subiect); ?></td>
                                <td class="px-4 py-3 text-sm text-center text-slate-700 dark:text-gray-300"><?php echo $numar; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    // Actualizare automată a contoarelor la fiecare 30 secunde
    setInterval(function() {
        fetch('api-registru-v2-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.apel !== undefined) {
                    document.getElementById('counter-apel-azi').textContent = data.apel;
                }
                if (data.vizita !== undefined) {
                    document.getElementById('counter-vizita-azi').textContent = data.vizita;
                }
            })
            .catch(error => {
                console.error('Eroare actualizare statistici:', error);
            });
    }, 30000); // 30 secunde
});
</script>
</body>
</html>
