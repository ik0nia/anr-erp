<?php
/**
 * View: Registru Interacțiuni v2 — Vizualizare statistici și interacțiuni recente
 *
 * Variabile: $eroare, $succes, $an_selectat, $statistici_lunare, $statistici_subiecte,
 *            $interactiuni_recente_30_zile, $interactiuni_azi, $luni
 */
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
                                <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Procesat de</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($interactiuni_recente_30_zile)): ?>
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-600 dark:text-gray-400 text-sm">Nu există interacțiuni în ultimele 30 de zile.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($interactiuni_recente_30_zile as $r): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700 cursor-pointer btn-detalii-interactiune"
                                data-tip="<?php echo htmlspecialchars($r['tip']); ?>"
                                data-persoana="<?php echo htmlspecialchars($r['persoana']); ?>"
                                data-telefon="<?php echo htmlspecialchars($r['telefon'] ?? ''); ?>"
                                data-subiect="<?php echo htmlspecialchars($r['tip'] === 'incasare' ? ($r['notite'] ?? '-') : ($r['subiect_alt'] ?: ($r['subiect_nume'] ?? '-'))); ?>"
                                data-notite="<?php echo htmlspecialchars($r['notite'] ?? ''); ?>"
                                data-info="<?php echo htmlspecialchars($r['informatii_suplimentare'] ?? ''); ?>"
                                data-utilizator="<?php echo htmlspecialchars($r['utilizator'] ?? ''); ?>"
                                data-task-id="<?php echo (int)($r['task_id'] ?? 0); ?>"
                                data-data="<?php echo date('d.m.Y H:i', strtotime($r['data_ora'])); ?>">
                                <td class="px-3 py-2 text-xs text-slate-700 dark:text-gray-300"><?php echo date('d.m.Y H:i', strtotime($r['data_ora'])); ?></td>
                                <td class="px-3 py-2">
                                    <?php
                                    $tip_class = 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200';
                                    $tip_label = 'Vizită';
                                    if ($r['tip'] === 'apel') { $tip_class = 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200'; $tip_label = 'Apel'; }
                                    elseif ($r['tip'] === 'incasare') { $tip_class = 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200'; $tip_label = 'Încasare'; }
                                    ?>
                                    <span class="inline-flex px-1.5 py-0.5 text-xs font-medium rounded <?php echo $tip_class; ?>">
                                        <?php echo $tip_label; ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['persoana']); ?></td>
                                <td class="px-3 py-2 text-xs text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($r['tip'] === 'incasare' ? ($r['notite'] ?? '-') : ($r['subiect_alt'] ?: ($r['subiect_nume'] ?? '-'))); ?></td>
                                <td class="px-3 py-2 text-xs text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($r['utilizator'] ?? '-'); ?></td>
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
        fetch('/api/registru-v2-stats')
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

    // Popup detalii interacțiune
    var dialog = document.getElementById('modal-detalii-interactiune');
    document.addEventListener('click', function(e) {
        var row = e.target.closest('.btn-detalii-interactiune');
        if (!row || !dialog) return;
        var tip = row.getAttribute('data-tip');
        var tipLabel = tip === 'apel' ? 'Apel telefonic' : (tip === 'incasare' ? 'Încasare' : 'Vizită la sediu');
        document.getElementById('det-tip').textContent = tipLabel;
        document.getElementById('det-data').textContent = row.getAttribute('data-data') || '-';
        document.getElementById('det-persoana').textContent = row.getAttribute('data-persoana') || '-';
        document.getElementById('det-telefon').textContent = row.getAttribute('data-telefon') || '-';
        document.getElementById('det-telefon-row').style.display = row.getAttribute('data-telefon') ? '' : 'none';
        document.getElementById('det-subiect').textContent = row.getAttribute('data-subiect') || '-';
        document.getElementById('det-notite').textContent = row.getAttribute('data-notite') || '-';
        document.getElementById('det-notite-row').style.display = row.getAttribute('data-notite') ? '' : 'none';
        document.getElementById('det-info').textContent = row.getAttribute('data-info') || '-';
        document.getElementById('det-info-row').style.display = row.getAttribute('data-info') ? '' : 'none';
        document.getElementById('det-utilizator').textContent = row.getAttribute('data-utilizator') || '-';
        var taskId = parseInt(row.getAttribute('data-task-id')) || 0;
        var taskRow = document.getElementById('det-task-row');
        var taskLink = document.getElementById('det-task-link');
        if (taskId > 0) {
            taskRow.style.display = '';
            taskLink.href = '/todo/edit?id=' + taskId;
        } else {
            taskRow.style.display = 'none';
        }
        dialog.showModal();
    });
    if (dialog) {
        document.getElementById('det-inchide').addEventListener('click', function(){ dialog.close(); });
        dialog.addEventListener('click', function(e){ if (e.target === dialog) dialog.close(); });
        // Permite click pe linkul task-ului să funcționeze normal
        document.getElementById('det-task-link').addEventListener('click', function(e){
            e.stopPropagation();
            dialog.close();
        });
    }
});
</script>

<!-- Modal Detalii Interacțiune -->
<dialog id="modal-detalii-interactiune" class="p-0 rounded-xl shadow-2xl max-w-md w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800">
    <div class="p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <i data-lucide="info" class="w-5 h-5" aria-hidden="true"></i>
            Detalii interacțiune
        </h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Tip</dt>
                <dd id="det-tip" class="text-slate-900 dark:text-white font-semibold"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Data / Ora</dt>
                <dd id="det-data" class="text-slate-900 dark:text-white"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Persoană</dt>
                <dd id="det-persoana" class="text-slate-900 dark:text-white font-semibold"></dd>
            </div>
            <div class="flex justify-between" id="det-telefon-row">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Telefon</dt>
                <dd id="det-telefon" class="text-slate-900 dark:text-white"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Subiect</dt>
                <dd id="det-subiect" class="text-slate-900 dark:text-white"></dd>
            </div>
            <div id="det-notite-row">
                <dt class="font-medium text-slate-500 dark:text-gray-400 mb-1">Notițe</dt>
                <dd id="det-notite" class="text-slate-900 dark:text-white bg-slate-50 dark:bg-gray-700 rounded p-2 whitespace-pre-wrap"></dd>
            </div>
            <div id="det-info-row">
                <dt class="font-medium text-slate-500 dark:text-gray-400 mb-1">Informații suplimentare</dt>
                <dd id="det-info" class="text-slate-900 dark:text-white bg-slate-50 dark:bg-gray-700 rounded p-2 whitespace-pre-wrap"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Procesat de</dt>
                <dd id="det-utilizator" class="text-slate-900 dark:text-white"></dd>
            </div>
            <div class="flex justify-between items-center" id="det-task-row">
                <dt class="font-medium text-slate-500 dark:text-gray-400">Task asociat</dt>
                <dd><a id="det-task-link" href="#" target="_blank" class="inline-flex items-center px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50"><i data-lucide="external-link" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i>Deschide task</a></dd>
            </div>
        </dl>
        <div class="mt-4 pt-3 border-t border-slate-200 dark:border-gray-700">
            <button type="button" id="det-inchide" class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">Închide</button>
        </div>
    </div>
</dialog>
</body>
</html>
