<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">Ajutoare BPA</h1>
        <nav class="flex gap-2 flex-wrap" role="tablist" aria-label="Tab-uri modul BPA">
            <a href="/ajutoare-bpa" role="tab" aria-selected="<?php echo $tab === 'management' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-lg font-medium <?php echo $tab === 'management' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                Management distributie
            </a>
            <a href="/ajutoare-bpa?tab=rapoarte" role="tab" aria-selected="<?php echo $tab === 'rapoarte' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-lg font-medium <?php echo $tab === 'rapoarte' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>">
                Rapoarte
            </a>
        </nav>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if ($eroare): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <?php if ($tab === 'rapoarte'): ?>
        <!-- Tab Rapoarte -->
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <label for="perioada-bpa" class="font-medium text-slate-800 dark:text-gray-200">Perioadă:</label>
            <select id="perioada-bpa" class="rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500" aria-label="Selectează perioada raport">
                <option value="an" <?php echo $perioada === 'an' ? 'selected' : ''; ?>>Anul curent</option>
                <option value="luna" <?php echo $perioada === 'luna' ? 'selected' : ''; ?>>Luna curentă</option>
                <option value="toata" <?php echo $perioada === 'toata' ? 'selected' : ''; ?>>Toată perioada</option>
            </select>
            <script>document.getElementById('perioada-bpa').onchange = function(){ window.location = '/ajutoare-bpa?tab=rapoarte&perioada=' + this.value; };</script>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-sm text-slate-600 dark:text-gray-400">Greutate totală preluată (kg)</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" aria-label="Greutate preluată"><?php echo number_format($indicatori_rap['total_preluat'], 1, ',', '.'); ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-sm text-slate-600 dark:text-gray-400">Greutate totală distribuită (kg)</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?php echo number_format($indicatori_rap['total_distribuit'], 1, ',', '.'); ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-sm text-slate-600 dark:text-gray-400">Stoc actual (kg)</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white"><?php echo number_format($stoc, 1, ',', '.'); ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-sm text-slate-600 dark:text-gray-400">Beneficiari unici</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white"><?php echo (int)$indicatori_rap['nr_beneficiari_unici']; ?></p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                <p class="text-sm text-slate-600 dark:text-gray-400">Număr pachete realizate</p>
                <p class="text-2xl font-bold text-slate-800 dark:text-white"><?php echo (int)$indicatori_rap['nr_pachete']; ?></p>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Evoluție cantități (<?php echo $perioada === 'toata' ? 'toată perioada' : ($perioada === 'an' ? 'anul curent' : 'luna curentă'); ?>)</h2>
                <canvas id="chart-evolutie-bpa" height="200" aria-label="Grafic evoluție preluat și distribuit"></canvas>
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
                <script>
                (function(){
                    var ctx = document.getElementById('chart-evolutie-bpa');
                    if (!ctx) return;
                    var data = <?php echo json_encode($evolutie_rap); ?>;
                    if (!data || data.length === 0) { ctx.parentNode.appendChild(document.createTextNode('Nu există date pentru perioada selectată.')); return; }
                    var labels = data.map(function(d){ return d.luna; });
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [
                                { label: 'Preluat (kg)', data: data.map(function(d){ return d.preluat; }), backgroundColor: 'rgba(245,158,11,0.7)' },
                                { label: 'Distribuit (kg)', data: data.map(function(d){ return d.distribuit; }), backgroundColor: 'rgba(34,197,94,0.7)' }
                            ]
                        },
                        options: { responsive: true, scales: { y: { beginAtZero: true } } }
                    });
                })();
                </script>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Vârste beneficiari</h2>
                <canvas id="chart-varste-bpa" height="200" aria-label="Grafic vârste beneficiari"></canvas>
                <script>
                (function(){
                    var ctx = document.getElementById('chart-varste-bpa');
                    if (!ctx) return;
                    var data = <?php echo json_encode($varste_rap); ?>;
                    var ordine = ['0-17','18-29','30-49','50-64','65+'];
                    var labels = ordine.filter(function(k){ return (data[k] || 0) > 0; });
                    var values = labels.map(function(k){ return data[k] || 0; });
                    var total = values.reduce(function(a,b){ return a+b; }, 0);
                    if (labels.length === 0) { ctx.parentNode.appendChild(document.createTextNode('Nu există date vârstă.')); return; }
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{ label: 'Număr', data: values, backgroundColor: 'rgba(99,102,241,0.7)' }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(ctx) {
                                            var v = ctx.raw;
                                            var p = total ? ((v/total)*100).toFixed(1) : 0;
                                            return v + ' (' + p + '%)';
                                        }
                                    }
                                }
                            },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                })();
                </script>
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sex beneficiari</h2>
                <canvas id="chart-sex-bpa" height="180" aria-label="Grafic sex beneficiari"></canvas>
                <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">
                    <?php
                    $total_sex = array_sum(array_column($sex_rap, 'nr'));
                    foreach ($sex_rap as $s) {
                        $p = $total_sex ? round($s['nr'] / $total_sex * 100, 1) : 0;
                        echo htmlspecialchars($s['sex'] ?? 'N/A') . ': ' . (int)$s['nr'] . ' (' . $p . '%) &nbsp; ';
                    }
                    if (empty($sex_rap)) echo 'Nu există date.';
                    ?>
                </p>
                <script>
                (function(){
                    var ctx = document.getElementById('chart-sex-bpa');
                    if (!ctx) return;
                    var data = <?php echo json_encode($sex_rap); ?>;
                    if (!data || data.length === 0) return;
                    var labels = data.map(function(d){ return d.sex || 'N/A'; });
                    var values = data.map(function(d){ return d.nr; });
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: { labels: labels, datasets: [{ data: values, backgroundColor: ['rgba(59,130,246,0.8)','rgba(236,72,153,0.8)'] }] },
                        options: {
                            responsive: true,
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(ctx) {
                                            var t = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                            var p = t ? ((ctx.raw/t)*100).toFixed(1) : 0;
                                            return ctx.label + ': ' + ctx.raw + ' (' + p + '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                })();
                </script>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Localități beneficiari</h2>
                <div class="overflow-x-auto max-h-64">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Localități">
                        <thead class="bg-slate-100 dark:bg-gray-700"><tr><th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200">Localitate</th><th scope="col" class="px-3 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200">Nr. (procent)</th></tr></thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php
                            $total_loc = array_sum(array_column($localitati_rap, 'nr'));
                            foreach ($localitati_rap as $loc):
                                $p = $total_loc ? round($loc['nr'] / $total_loc * 100, 1) : 0;
                            ?>
                            <tr><td class="px-3 py-2 text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars($loc['localitate']); ?></td><td class="px-3 py-2 text-right text-slate-900 dark:text-gray-100"><?php echo (int)$loc['nr']; ?> (<?php echo $p; ?>%)</td></tr>
                            <?php endforeach; ?>
                            <?php if (empty($localitati_rap)): ?>
                            <tr><td colspan="2" class="px-3 py-4 text-slate-500 dark:text-gray-400">Nu există date.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>
        <?php else: ?>
        <!-- Tab Management distributie -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coloana stânga: Tabele distributie -->
            <div class="lg:col-span-1 space-y-4">
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="titlu-tabele-bpa">
                    <h2 id="titlu-tabele-bpa" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Tabele de distributie</h2>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-3">Creați un tabel nou sau editați unul existent. După salvare, cantitatea se scade automat din stoc.</p>
                    <?php if ($tabel_edit): ?>
                    <p class="text-sm font-medium text-amber-700 dark:text-amber-300 mb-2">Editare: <?php echo htmlspecialchars($tabel_edit['nr_tabel']); ?> (<?php echo date(DATE_FORMAT, strtotime($tabel_edit['data_tabel'])); ?>)</p>
                    <?php endif; ?>
                    <form method="post" action="/ajutoare-bpa" id="form-tabel-bpa">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="salveaza_tabel" value="1">
                        <input type="hidden" name="tabel_id" value="<?php echo $tabel_edit ? (int)$tabel_edit['id'] : 0; ?>">
                        <div class="mb-3">
                            <label for="bpa-data-tabel" class="block text-sm font-medium text-slate-800 dark:text-gray-200 dark:text-white mb-1">Data <span class="text-red-600">*</span></label>
                            <input type="date" id="bpa-data-tabel" name="data_tabel" value="<?php echo $tabel_edit ? $tabel_edit['data_tabel'] : date('Y-m-d'); ?>" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500" aria-required="true">
                        </div>
                        <div class="mb-3">
                            <label for="bpa-nr-tabel" class="block text-sm font-medium text-slate-800 dark:text-gray-200 dark:text-white mb-1">Nr. tabel <span class="text-red-600">*</span></label>
                            <div class="flex gap-2 items-center">
                                <input type="text" id="bpa-nr-tabel" name="nr_tabel" value="<?php echo $tabel_edit ? htmlspecialchars($tabel_edit['nr_tabel']) : ''; ?>" required placeholder="ex. TD-001" class="flex-1 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500" aria-required="true">
                                <button type="button" id="btn-bpa-nr-registratura" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Adaugă număr de înregistrare din registratură">Adaugă număr de înregistrare</button>
                            </div>
                        </div>
                        <p class="text-sm font-medium text-slate-700 dark:text-gray-300 dark:text-white mt-2 mb-2">Cap tabel: Nr. crt. | Nume și prenume | Localitate domiciliu | Seria și nr. C.I. | Vârstă | Greutate pachet (Kg) | Semnătură</p>
                        <div class="mb-2">
                            <label for="cauta-membru-bpa" class="block text-sm text-slate-600 dark:text-gray-400 dark:text-white mb-1">Caută membru (adaugă rând):</label>
                            <div class="relative flex gap-2 w-full" id="wrap-cauta-bpa">
                                <input type="text" id="cauta-membru-bpa" placeholder="Nume sau prenume (min. 2 caractere)..." class="flex-1 min-w-0 rounded border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-2 py-1 text-sm placeholder-slate-500 dark:placeholder-gray-400" aria-label="Caută membru pentru a adăuga în listă" autocomplete="off">
                                <button type="button" id="btn-cauta-bpa" class="shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded border-0 focus:ring-2 focus:ring-amber-500" aria-label="Execută căutarea">Caută</button>
                                <div id="rezultate-cauta-bpa" class="absolute left-0 right-0 top-full z-[100] mt-1 w-full" role="region" aria-live="polite" aria-label="Rezultate căutare"></div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-slate-300 dark:border-gray-600 dark:border-gray-400" role="table" aria-label="Randuri tabel distributie">
                                <thead><tr class="bg-slate-100 dark:bg-gray-700">
                                    <th class="px-2 py-1 w-12 text-slate-800 dark:text-white">Nr.</th>
                                    <th class="px-2 py-1 text-slate-800 dark:text-white">Nume / Prenume</th>
                                    <th class="px-2 py-1 text-slate-800 dark:text-white">Localitate</th>
                                    <th class="px-2 py-1 text-slate-800 dark:text-white">Seria nr. CI</th>
                                    <th class="px-2 py-1 w-16 text-slate-800 dark:text-white">Vârstă</th>
                                    <th class="px-2 py-1 w-20 text-slate-800 dark:text-white">Greutate (kg)</th>
                                </tr></thead>
                                <tbody id="tbody-randuri-bpa">
                                    <?php
                                    if ($tabel_edit && !empty($tabel_edit['randuri'])):
                                        foreach ($tabel_edit['randuri'] as $i => $row):
                                            $nume = $row['nume'] ?? $row['nume_manual'] ?? '';
                                            $prenume = $row['prenume'] ?? $row['prenume_manual'] ?? '';
                                            $loc = $row['domloc'] ?? $row['localitate'] ?? '';
                                            $ci = ($row['ciseria'] ?? '') . ' ' . ($row['cinumar'] ?? '');
                                            if (empty($ci)) $ci = $row['seria_nr_ci'] ?? '';
                                            $varsta = '';
                                            if (!empty($row['datanastere']) || !empty($row['data_nastere'])) {
                                                $dn = $row['datanastere'] ?? $row['data_nastere'];
                                                $varsta = (string)calculeaza_varsta($dn);
                                            }
                                            $greutate = $row['greutate_pachet'] ?? 0;
                                    ?>
                                    <tr class="rand-bpa border-t border-slate-200 dark:border-gray-600">
                                        <td class="px-2 py-1 text-center nr-crt"><?php echo $i + 1; ?></td>
                                        <td class="px-2 py-1">
                                            <input type="hidden" name="randuri[<?php echo $i; ?>][membru_id]" value="<?php echo (int)($row['membru_id'] ?? 0); ?>">
                                            <input type="text" name="randuri[<?php echo $i; ?>][nume_manual]" value="<?php echo htmlspecialchars($nume); ?>" placeholder="Nume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100">
                                            <input type="text" name="randuri[<?php echo $i; ?>][prenume_manual]" value="<?php echo htmlspecialchars($prenume); ?>" placeholder="Prenume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm mt-0.5 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100">
                                        </td>
                                        <td class="px-2 py-1"><input type="text" name="randuri[<?php echo $i; ?>][localitate]" value="<?php echo htmlspecialchars($loc); ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>
                                        <td class="px-2 py-1"><input type="text" name="randuri[<?php echo $i; ?>][seria_nr_ci]" value="<?php echo htmlspecialchars($ci); ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>
                                        <td class="px-2 py-1"><input type="number" min="0" name="randuri[<?php echo $i; ?>][varsta_afis]" value="<?php echo htmlspecialchars($varsta); ?>" readonly class="w-12 rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-slate-50 dark:bg-gray-600 text-slate-900 dark:text-gray-100" aria-label="Vârstă"><input type="hidden" name="randuri[<?php echo $i; ?>][data_nastere]" value="<?php echo htmlspecialchars($row['datanastere'] ?? $row['data_nastere'] ?? ''); ?>"></td>
                                        <td class="px-2 py-1"><input type="number" step="0.01" min="0" name="randuri[<?php echo $i; ?>][greutate_pachet]" value="<?php echo htmlspecialchars($greutate); ?>" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr class="rand-bpa border-t border-slate-200 dark:border-gray-600">
                                        <td class="px-2 py-1 text-center nr-crt">1</td>
                                        <td class="px-2 py-1">
                                            <input type="hidden" name="randuri[0][membru_id]" value="">
                                            <input type="text" name="randuri[0][nume_manual]" placeholder="Nume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100">
                                            <input type="text" name="randuri[0][prenume_manual]" placeholder="Prenume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm mt-0.5 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100">
                                        </td>
                                        <td class="px-2 py-1"><input type="text" name="randuri[0][localitate]" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>
                                        <td class="px-2 py-1"><input type="text" name="randuri[0][seria_nr_ci]" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>
                                        <td class="px-2 py-1"><input type="hidden" name="randuri[0][data_nastere]"><input type="number" min="0" name="randuri[0][varsta_afis]" readonly class="w-12 rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-slate-50 dark:bg-gray-600 text-slate-900 dark:text-gray-100" aria-label="Vârstă"></td>
                                        <td class="px-2 py-1"><input type="number" step="0.01" min="0" name="randuri[0][greutate_pachet]" value="0" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <button type="button" id="btn-adauga-rand-bpa" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-sm rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Adaugă o altă persoană">+ Adaugă o altă persoană</button>
                        </div>
                        <div class="mt-4 p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Loc distributie (bifați cel puțin unul):</p>
                            <label class="inline-flex items-center gap-2 mr-4"><input type="checkbox" name="predare_sediul" value="1" <?php echo ($tabel_edit && $tabel_edit['predare_sediul']) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600 focus:ring-amber-500"> Predare la sediu</label>
                            <label class="inline-flex items-center gap-2 mr-4"><input type="checkbox" name="predare_centru" value="1" <?php echo ($tabel_edit && $tabel_edit['predare_centru']) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600 focus:ring-amber-500"> Predare la centru</label>
                            <label class="inline-flex items-center gap-2"><input type="checkbox" name="livrare_domiciliu" value="1" <?php echo ($tabel_edit && $tabel_edit['livrare_domiciliu']) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600 focus:ring-amber-500"> Livrare la domiciliu</label>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div><p class="font-medium text-slate-700 dark:text-gray-300">Mihai Merca, Președinte</p></div>
                            <div class="text-right"><p class="font-medium text-slate-700 dark:text-gray-300">Cristina Cociuba, Responsabil distributie</p></div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează tabelul">Salvează tabelul</button>
                            <?php if ($tabel_edit): ?>
                            <a href="/ajutoare-bpa" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">Anulare editare</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Tabele existente:</p>
                        <ul class="space-y-1 text-sm text-slate-900 dark:text-gray-100 border-t border-slate-200 dark:border-gray-600 pt-2">
                            <?php foreach ($lista_tabele as $t): ?>
                            <li class="flex items-center justify-between gap-2 py-1 border-b border-slate-200 dark:border-gray-600 last:border-0">
                                <span class="text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars($t['nr_tabel']); ?> – <?php echo date(DATE_FORMAT, strtotime($t['data_tabel'])); ?> (<?php echo number_format($t['cantitate_totala'], 1); ?> kg)</span>
                                <span class="flex gap-1">
                                    <a href="/ajutoare-bpa?edit=<?php echo (int)$t['id']; ?>" class="text-amber-600 dark:text-amber-400 hover:underline" aria-label="Editează tabel <?php echo htmlspecialchars($t['nr_tabel']); ?>">Editează</a>
                                    <a href="util/print-bpa-tabel.php?id=<?php echo (int)$t['id']; ?>" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-gray-400 hover:underline">Print</a>
                                    <a href="util/bpa-tabel-docx.php?id=<?php echo (int)$t['id']; ?>" class="text-slate-600 dark:text-gray-400 hover:underline">DOCX</a>
                                    <a href="util/bpa-tabel-pdf.php?id=<?php echo (int)$t['id']; ?>" target="_blank" rel="noopener noreferrer" class="text-slate-600 dark:text-gray-400 hover:underline">PDF</a>
                                </span>
                            </li>
                            <?php endforeach; ?>
                            <?php if (empty($lista_tabele)): ?>
                            <li class="text-slate-500 dark:text-gray-400 py-1">Niciun tabel încă.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </section>
            </div>

            <!-- Coloana dreaptă: Gestiune și indicatori -->
            <div class="lg:col-span-2 space-y-4">
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="titlu-indicatori-bpa">
                    <h2 id="titlu-indicatori-bpa" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Indicatori stoc</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-gray-400">Total preluat (kg)</p>
                            <p class="text-xl font-bold text-amber-700 dark:text-amber-300" aria-live="polite"><?php echo number_format($total_preluat, 1, ',', '.'); ?></p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-gray-400">Total distribuit (kg)</p>
                            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-300"><?php echo number_format($total_distribuit, 1, ',', '.'); ?></p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-gray-400">Beneficiari unici</p>
                            <p class="text-xl font-bold text-slate-800 dark:text-white"><?php echo (int)$nr_beneficiari_unici; ?></p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-gray-400">Pachete distribuite</p>
                            <p class="text-xl font-bold text-slate-800 dark:text-white"><?php echo (int)$nr_pachete; ?></p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-gray-400">Stoc (kg)</p>
                            <p class="text-xl font-bold text-blue-700 dark:text-blue-300"><?php echo number_format($stoc, 1, ',', '.'); ?></p>
                        </div>
                    </div>
                </section>
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-600 p-4">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Evidență gestiune</h2>
                        <button type="button" id="btn-deschide-document-bpa" class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Adaugă document (aviz sau tabel distributie)">+ Adaugă document</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-600" role="table" aria-label="Documente înregistrate gestiune BPA">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200">Nr. document</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200">Data document</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200">Tip document</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200">Greutatea (kg)</th>
                                    <th scope="col" class="px-3 py-2 text-center text-xs font-semibold text-slate-800 dark:text-gray-200">Butoane</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-600 bg-white dark:bg-gray-800">
                                <?php foreach ($lista_gestiune as $g): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50">
                                    <td class="px-3 py-2 text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars($g['nr_document']); ?></td>
                                    <td class="px-3 py-2 text-slate-700 dark:text-gray-300"><?php echo date(DATE_FORMAT, strtotime($g['data_document'])); ?></td>
                                    <td class="px-3 py-2 text-slate-700 dark:text-gray-300"><?php echo $g['tip_document'] === 'aviz' ? 'Aviz' : ($g['tip_document'] === 'tabel_cristal' ? 'Tabel Cristal' : 'Tabel distributie'); ?></td>
                                    <td class="px-3 py-2 text-right text-slate-900 dark:text-gray-100 font-medium"><?php echo $g['tip_document'] === 'aviz' ? '+' : ''; ?><?php echo number_format($g['cantitate'], 1, ',', '.'); ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" class="btn-editeaza-gestiune-bpa inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 hover:text-amber-800 dark:hover:text-amber-200 border border-amber-400 dark:border-amber-500 rounded focus:ring-2 focus:ring-amber-500" data-id="<?php echo (int)$g['id']; ?>" data-nr="<?php echo htmlspecialchars($g['nr_document']); ?>" data-data="<?php echo htmlspecialchars($g['data_document']); ?>" data-tip="<?php echo htmlspecialchars($g['tip_document']); ?>" data-cantitate="<?php echo number_format(abs((float)$g['cantitate']), 2, '.', ''); ?>" data-loc="<?php echo htmlspecialchars($g['loc_distributie'] ?? ''); ?>" data-nrbenef="<?php echo (int)($g['nr_beneficiari'] ?? 0); ?>" aria-label="Editează document <?php echo htmlspecialchars($g['nr_document']); ?>">Editează</button>
                                        <form method="post" action="/ajutoare-bpa" class="inline-block ml-1" onsubmit="return confirm('Ștergeți acest document din gestiune?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="sterge_gestiune_bpa" value="1">
                                            <input type="hidden" name="id_gestiune" value="<?php echo (int)$g['id']; ?>">
                                            <button type="submit" class="px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300 hover:text-red-800 dark:hover:text-red-200 border border-red-400 dark:border-red-500 rounded focus:ring-2 focus:ring-red-500" aria-label="Șterge document <?php echo htmlspecialchars($g['nr_document']); ?>">Șterge</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($lista_gestiune)): ?>
                                <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500 dark:text-gray-400">Nu există documente. Adăugați un aviz sau salvați un tabel de distributie.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal adaugă document -->
        <dialog id="modal-document-bpa" role="dialog" aria-modal="true" aria-labelledby="titlu-modal-document-bpa" class="p-0 rounded-lg shadow-xl max-w-md w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-700 dark:bg-gray-800">
            <div class="p-6">
                <h2 id="titlu-modal-document-bpa" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Adaugă document</h2>
                <form method="post" action="/ajutoare-bpa" id="form-document-bpa">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_document" value="1">
                    <div class="space-y-3">
                        <div>
                            <label for="bpa-tip-doc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip document <span class="text-red-600">*</span></label>
                            <select id="bpa-tip-doc" name="tip_document" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500" aria-required="true">
                                <option value="aviz">Aviz (încarcă stocul)</option>
                                <option value="tabel_distributie">Tabel distributie (scade stocul – completat pe hârtie)</option>
                                <option value="tabel_cristal">Tabel Cristal (scade stocul – pachete și greutate introduse manual)</option>
                            </select>
                        </div>
                        <div>
                            <label for="bpa-nr-doc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr document <span class="text-red-600">*</span></label>
                            <input type="text" id="bpa-nr-doc" name="nr_document" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500 placeholder-slate-500 dark:placeholder-gray-400" placeholder="ex. AVIZ-001">
                        </div>
                        <div>
                            <label for="bpa-data-doc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600">*</span></label>
                            <input type="date" id="bpa-data-doc" name="data_document" value="<?php echo date('Y-m-d'); ?>" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label for="bpa-cantitate" id="label-bpa-cantitate" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cantitate (kg) <span class="text-red-600">*</span></label>
                            <input type="number" id="bpa-cantitate" name="cantitate" step="0.01" min="0.01" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500 placeholder-slate-500 dark:placeholder-gray-400" placeholder="0">
                        </div>
                        <div id="bpa-extra-tabel" class="hidden space-y-3 border-t border-slate-200 dark:border-gray-600 pt-3">
                            <div>
                                <label for="bpa-loc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Loc distributie</label>
                                <input type="text" id="bpa-loc" name="loc_distributie" class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 placeholder-slate-500 dark:placeholder-gray-400" placeholder="ex. Sediu">
                            </div>
                            <div>
                                <label for="bpa-nr-benef" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr beneficiari</label>
                                <input type="number" id="bpa-nr-benef" name="nr_beneficiari" min="0" class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 placeholder-slate-500 dark:placeholder-gray-400" placeholder="0">
                            </div>
                        </div>
                        <div id="bpa-extra-cristal" class="hidden space-y-3 border-t border-slate-200 dark:border-gray-600 pt-3">
                            <div>
                                <label for="bpa-nr-pachete-cristal" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr pachete distribuite</label>
                                <input type="number" id="bpa-nr-pachete-cristal" name="nr_beneficiari" min="0" class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 placeholder-slate-500 dark:placeholder-gray-400" placeholder="0">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3 justify-end">
                        <button type="button" id="btn-inchide-modal-bpa" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">Salvează</button>
                    </div>
                </form>
            </div>
        </dialog>
        <!-- Modal editare document gestiune -->
        <dialog id="modal-edit-gestiune-bpa" role="dialog" aria-modal="true" aria-labelledby="titlu-edit-gestiune-bpa" class="p-0 rounded-lg shadow-xl max-w-md w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-600 dark:bg-gray-800">
            <div class="p-6 bg-white dark:bg-gray-800">
                <h2 id="titlu-edit-gestiune-bpa" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Editează document</h2>
                <form method="post" action="/ajutoare-bpa" id="form-edit-gestiune-bpa">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="editeaza_gestiune_bpa" value="1">
                    <input type="hidden" name="id_gestiune" id="edit-gestiune-id" value="">
                    <div class="space-y-3">
                        <div>
                            <label for="edit-bpa-tip-doc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip document <span class="text-red-600">*</span></label>
                            <select id="edit-bpa-tip-doc" name="tip_document" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500" aria-required="true">
                                <option value="aviz">Aviz</option>
                                <option value="tabel_distributie">Tabel distributie</option>
                                <option value="tabel_cristal">Tabel Cristal</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit-bpa-nr-doc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr document <span class="text-red-600">*</span></label>
                            <input type="text" id="edit-bpa-nr-doc" name="nr_document" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label for="edit-bpa-data-doc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600">*</span></label>
                            <input type="date" id="edit-bpa-data-doc" name="data_document" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label for="edit-bpa-cantitate" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Greutate (kg) <span class="text-red-600">*</span></label>
                            <input type="number" id="edit-bpa-cantitate" name="cantitate" step="0.01" min="0.01" required class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div id="edit-bpa-extra-tabel" class="hidden space-y-3 border-t border-slate-200 dark:border-gray-600 pt-3">
                            <div>
                                <label for="edit-bpa-loc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Loc distributie</label>
                                <input type="text" id="edit-bpa-loc" name="loc_distributie" class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            </div>
                            <div>
                                <label for="edit-bpa-nr-benef" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr beneficiari</label>
                                <input type="number" id="edit-bpa-nr-benef" name="nr_beneficiari" min="0" class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            </div>
                        </div>
                        <div id="edit-bpa-extra-cristal" class="hidden space-y-3 border-t border-slate-200 dark:border-gray-600 pt-3">
                            <div>
                                <label for="edit-bpa-nr-pachete-cristal" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr pachete distribuite</label>
                                <input type="number" id="edit-bpa-nr-pachete-cristal" name="nr_beneficiari" min="0" class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100 px-3 py-2">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3 justify-end">
                        <button type="button" id="btn-inchide-edit-gestiune-bpa" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</button>
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">Salvează</button>
                    </div>
                </form>
            </div>
        </dialog>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var modal = document.getElementById('modal-document-bpa');
    var btnOpen = document.getElementById('btn-deschide-document-bpa');
    var btnClose = document.getElementById('btn-inchide-modal-bpa');
    var tipDoc = document.getElementById('bpa-tip-doc');
    var extraTabel = document.getElementById('bpa-extra-tabel');
    if (btnOpen) btnOpen.addEventListener('click', function(){ if (modal) modal.showModal(); });
    if (btnClose) btnClose.addEventListener('click', function(){ if (modal) modal.close(); });
    if (modal) modal.addEventListener('keydown', function(e){ if (e.key === 'Escape') modal.close(); });
    var extraCristal = document.getElementById('bpa-extra-cristal');
    var labelCantitate = document.getElementById('label-bpa-cantitate');
    var nrBenefInput = document.getElementById('bpa-nr-benef');
    var nrPacheteCristalInput = document.getElementById('bpa-nr-pachete-cristal');
    function toggleExtra() {
        var tip = tipDoc ? tipDoc.value : '';
        if (extraTabel) extraTabel.classList.toggle('hidden', tip !== 'tabel_distributie');
        if (extraCristal) extraCristal.classList.toggle('hidden', tip !== 'tabel_cristal');
        if (labelCantitate) labelCantitate.innerHTML = tip === 'tabel_cristal' ? 'Greutate totală distribuită (kg) <span class="text-red-600">*</span>' : 'Cantitate (kg) <span class="text-red-600">*</span>';
        if (nrBenefInput) nrBenefInput.disabled = (tip !== 'tabel_distributie');
        if (nrPacheteCristalInput) nrPacheteCristalInput.disabled = (tip !== 'tabel_cristal');
    }
    if (tipDoc) { tipDoc.addEventListener('change', toggleExtra); toggleExtra(); }

    var modalEdit = document.getElementById('modal-edit-gestiune-bpa');
    var btnCloseEdit = document.getElementById('btn-inchide-edit-gestiune-bpa');
    var editTipDoc = document.getElementById('edit-bpa-tip-doc');
    var editExtraTabel = document.getElementById('edit-bpa-extra-tabel');
    var editExtraCristal = document.getElementById('edit-bpa-extra-cristal');
    var editNrBenef = document.getElementById('edit-bpa-nr-benef');
    var editNrPacheteCristal = document.getElementById('edit-bpa-nr-pachete-cristal');
    if (btnCloseEdit && modalEdit) btnCloseEdit.addEventListener('click', function(){ modalEdit.close(); });
    if (modalEdit) modalEdit.addEventListener('keydown', function(e){ if (e.key === 'Escape') modalEdit.close(); });
    function toggleEditExtra() {
        var tip = editTipDoc ? editTipDoc.value : '';
        if (editExtraTabel) editExtraTabel.classList.toggle('hidden', tip !== 'tabel_distributie');
        if (editExtraCristal) editExtraCristal.classList.toggle('hidden', tip !== 'tabel_cristal');
        if (editNrBenef) editNrBenef.disabled = (tip !== 'tabel_distributie');
        if (editNrPacheteCristal) editNrPacheteCristal.disabled = (tip !== 'tabel_cristal');
    }
    if (editTipDoc) { editTipDoc.addEventListener('change', toggleEditExtra); }
    document.querySelectorAll('.btn-editeaza-gestiune-bpa').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = this.getAttribute('data-id');
            var nr = this.getAttribute('data-nr') || '';
            var data = this.getAttribute('data-data') || '';
            var tip = this.getAttribute('data-tip') || 'aviz';
            var cantitate = this.getAttribute('data-cantitate') || '0';
            var loc = this.getAttribute('data-loc') || '';
            var nrbenef = this.getAttribute('data-nrbenef') || '0';
            document.getElementById('edit-gestiune-id').value = id;
            document.getElementById('edit-bpa-nr-doc').value = nr;
            document.getElementById('edit-bpa-data-doc').value = data;
            document.getElementById('edit-bpa-tip-doc').value = tip;
            document.getElementById('edit-bpa-cantitate').value = cantitate;
            document.getElementById('edit-bpa-loc').value = loc;
            document.getElementById('edit-bpa-nr-benef').value = nrbenef;
            if (editNrPacheteCristal) editNrPacheteCristal.value = nrbenef;
            toggleEditExtra();
            if (modalEdit) modalEdit.showModal();
        });
    });

    var tbody = document.getElementById('tbody-randuri-bpa');
    var btnAdauga = document.getElementById('btn-adauga-rand-bpa');
    if (tbody && btnAdauga) {
        btnAdauga.addEventListener('click', function(){
            var idx = tbody.querySelectorAll('tr.rand-bpa').length;
            var tr = document.createElement('tr');
            tr.className = 'rand-bpa border-t border-slate-200 dark:border-gray-600';
            tr.innerHTML = '<td class="px-2 py-1 text-center nr-crt">' + (idx + 1) + '</td>' +
                '<td class="px-2 py-1"><input type="hidden" name="randuri[' + idx + '][membru_id]" value=""><input type="text" name="randuri[' + idx + '][nume_manual]" placeholder="Nume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"><input type="text" name="randuri[' + idx + '][prenume_manual]" placeholder="Prenume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm mt-0.5 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>' +
                '<td class="px-2 py-1"><input type="text" name="randuri[' + idx + '][localitate]" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>' +
                '<td class="px-2 py-1"><input type="text" name="randuri[' + idx + '][seria_nr_ci]" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>' +
                '<td class="px-2 py-1"><input type="hidden" name="randuri[' + idx + '][data_nastere]"><input type="number" min="0" name="randuri[' + idx + '][varsta_afis]" readonly class="w-12 rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-slate-50 dark:bg-gray-600 text-slate-900 dark:text-gray-100" aria-label="Vârstă"></td>' +
                '<td class="px-2 py-1"><input type="number" step="0.01" min="0" name="randuri[' + idx + '][greutate_pachet]" value="0" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>';
            tbody.appendChild(tr);
            var rows = tbody.querySelectorAll('tr.rand-bpa');
            rows.forEach(function(r, i){ var nc = r.querySelector('.nr-crt'); if (nc) nc.textContent = i + 1; });
        });
    }

    var cautaInput = document.getElementById('cauta-membru-bpa');
    var rezultateDiv = document.getElementById('rezultate-cauta-bpa');
    var debounce = null;
    if (cautaInput && rezultateDiv && tbody) {
        function escAttr(s) {
            return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;').replace(/>/g,'&gt;');
        }
        function addPersoanaLaTabel(id, nume, prenume, domloc, datanastere, ci) {
            var idx = tbody.querySelectorAll('tr.rand-bpa').length;
            var tr = document.createElement('tr');
            tr.className = 'rand-bpa border-t border-slate-200 dark:border-gray-600';
            var numeSafe = (nume || '').replace(/"/g,'&quot;');
            var prenumeSafe = (prenume || '').replace(/"/g,'&quot;');
            var domlocSafe = (domloc || '').replace(/"/g,'&quot;');
            var ciSafe = (ci || '').replace(/"/g,'&quot;');
            tr.innerHTML = '<td class="px-2 py-1 text-center nr-crt">' + (idx + 1) + '</td><td class="px-2 py-1"><input type="hidden" name="randuri[' + idx + '][membru_id]" value="' + escAttr(id) + '"><input type="text" name="randuri[' + idx + '][nume_manual]" value="' + numeSafe + '" placeholder="Nume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"><input type="text" name="randuri[' + idx + '][prenume_manual]" value="' + prenumeSafe + '" placeholder="Prenume" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm mt-0.5 bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td><td class="px-2 py-1"><input type="text" name="randuri[' + idx + '][localitate]" value="' + domlocSafe + '" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td><td class="px-2 py-1"><input type="text" name="randuri[' + idx + '][seria_nr_ci]" value="' + ciSafe + '" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td><td class="px-2 py-1"><input type="hidden" name="randuri[' + idx + '][data_nastere]" value="' + (datanastere || '') + '"><input type="number" min="0" name="randuri[' + idx + '][varsta_afis]" readonly class="w-12 rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-slate-50 dark:bg-gray-600 text-slate-900 dark:text-gray-100" aria-label="Vârstă"></td><td class="px-2 py-1"><input type="number" step="0.01" min="0" name="randuri[' + idx + '][greutate_pachet]" value="0" class="w-full rounded border border-slate-300 dark:border-gray-600 px-1 py-0.5 text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-gray-100"></td>';
            tbody.appendChild(tr);
            if (datanastere) {
                var ro = tr.querySelector('input[name*="[varsta_afis]"]');
                if (ro) {
                    var b = new Date(datanastere);
                    var t = new Date();
                    var v = t.getFullYear() - b.getFullYear();
                    if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) v--;
                    ro.value = v >= 0 ? v : '';
                }
            }
            rezultateDiv.innerHTML = '';
            cautaInput.value = '';
            var rows = tbody.querySelectorAll('tr.rand-bpa');
            rows.forEach(function(r, i){ var nc = r.querySelector('.nr-crt'); if (nc) nc.textContent = i + 1; });
        }
        function showRezultate(list) {
            rezultateDiv.innerHTML = '';
            if (!list || list.length === 0) {
                rezultateDiv.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg shadow">Niciun rezultat</div>';
                return;
            }
            var ul = document.createElement('ul');
            ul.className = 'max-h-64 overflow-y-auto bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg shadow-lg text-sm';
            ul.setAttribute('role', 'listbox');
            ul.id = 'list-cauta-bpa';
            list.forEach(function(m){
                var numeComplet = (m.nume || '') + ' ' + (m.prenume || '');
                var nume = m.nume || '';
                var prenume = m.prenume || '';
                var domloc = m.domloc || '';
                var datanastere = m.datanastere || '';
                var ci = (m.ciseria || '') + ' ' + (m.cinumar || '');
                if (ci) ci = ci.trim();
                var id = m.id;
                var li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-2 px-3 py-2 border-b border-slate-100 dark:border-gray-700 last:border-0 hover:bg-amber-50 dark:hover:bg-amber-900/20';
                li.setAttribute('role', 'option');
                li.setAttribute('data-id', id);
                li.setAttribute('data-nume', nume);
                li.setAttribute('data-prenume', prenume);
                li.setAttribute('data-domloc', domloc);
                li.setAttribute('data-datanastere', datanastere);
                li.setAttribute('data-ci', ci);
                li.innerHTML = '<span class="flex-1 truncate">' + escAttr(numeComplet) + '</span><button type="button" class="btn-adauga-persoana-bpa shrink-0 px-2 py-1 text-xs font-medium bg-amber-600 hover:bg-amber-700 text-white rounded focus:ring-2 focus:ring-amber-500" data-id="' + escAttr(id) + '" data-nume="' + escAttr(nume) + '" data-prenume="' + escAttr(prenume) + '" data-domloc="' + escAttr(domloc) + '" data-datanastere="' + escAttr(datanastere) + '" data-ci="' + escAttr(ci) + '">Adauga persoana</button>';
                ul.appendChild(li);
            });
            rezultateDiv.appendChild(ul);
            rezultateDiv.querySelectorAll('li').forEach(function(li){
                li.style.cursor = 'pointer';
                li.addEventListener('click', function(e){
                    if (e.target && e.target.classList && e.target.classList.contains('btn-adauga-persoana-bpa')) return;
                    addPersoanaLaTabel(li.getAttribute('data-id'), li.getAttribute('data-nume'), li.getAttribute('data-prenume'), li.getAttribute('data-domloc'), li.getAttribute('data-datanastere'), li.getAttribute('data-ci'));
                });
            });
            rezultateDiv.querySelectorAll('.btn-adauga-persoana-bpa').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    addPersoanaLaTabel(btn.getAttribute('data-id'), btn.getAttribute('data-nume'), btn.getAttribute('data-prenume'), btn.getAttribute('data-domloc'), btn.getAttribute('data-datanastere'), btn.getAttribute('data-ci'));
                });
            });
        }
        function doCautaBpa() {
            var q = cautaInput.value.trim();
            rezultateDiv.innerHTML = '';
            if (q.length < 2) return;
            var url = '/api/cauta-membri?q=' + encodeURIComponent(q);
            fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function(r){ return r.text(); })
                .then(function(text){
                    try {
                        var data = JSON.parse(text);
                        showRezultate(data.membri || []);
                    } catch (err) {
                        showRezultate([]);
                        rezultateDiv.innerHTML = '<div class="px-3 py-2 text-sm text-red-500 bg-white dark:bg-gray-800 border border-slate-300 rounded-lg">Răspuns invalid</div>';
                    }
                })
                .catch(function(){
                    showRezultate([]);
                    rezultateDiv.innerHTML = '<div class="px-3 py-2 text-sm text-red-500 bg-white dark:bg-gray-800 border border-slate-300 rounded-lg">Eroare la căutare</div>';
                });
        }
        cautaInput.addEventListener('input', function(){
            clearTimeout(debounce);
            var q = this.value.trim();
            rezultateDiv.innerHTML = '';
            if (q.length < 2) return;
            debounce = setTimeout(doCautaBpa, 350);
        });
        cautaInput.addEventListener('keydown', function(e){
            if (e.key === 'Enter') { e.preventDefault(); doCautaBpa(); }
        });
        var btnCautaBpa = document.getElementById('btn-cauta-bpa');
        if (btnCautaBpa) btnCautaBpa.addEventListener('click', doCautaBpa);
        document.addEventListener('click', function(e){
            var wrap = document.getElementById('wrap-cauta-bpa');
            if (!cautaInput || !rezultateDiv || !wrap) return;
            if (wrap.contains(e.target)) return;
            rezultateDiv.innerHTML = '';
        });
    }

    var btnNrReg = document.getElementById('btn-bpa-nr-registratura');
    var nrTabelInput = document.getElementById('bpa-nr-tabel');
    if (btnNrReg && nrTabelInput) {
        btnNrReg.addEventListener('click', function(){
            var form = document.getElementById('form-tabel-bpa');
            var token = form ? form.querySelector('input[name="_csrf_token"]') : null;
            var fd = new FormData();
            if (token) fd.append('_csrf_token', token.value);
            fd.append('creaza_nr_registratura_bpa', '1');
            fetch('/api/bpa-nr-registratura', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if (data && data.nr_inregistrare !== undefined) {
                        nrTabelInput.value = data.nr_inregistrare;
                        nrTabelInput.focus();
                    } else {
                        alert(data && data.eroare ? data.eroare : 'Nu s-a putut aloca numărul.');
                    }
                })
                .catch(function(){ alert('Eroare la comunicare.'); });
        });
    }
});
</script>
<?php if (isset($_GET['succes']) && $_GET['succes'] === 'tabel' && !empty($_GET['id'])): ?>
<script>document.addEventListener('DOMContentLoaded', function(){ var m = document.getElementById('modal-document-bpa'); if (m) m.close(); });</script>
<?php endif; ?>
<?php include APP_ROOT . '/footer.php'; ?>
