<?php
/**
 * View: Încasări — Lista tuturor încasărilor cu filtrare, paginare, edit și ștergere
 *
 * Variabile disponibile (setate de controller):
 *   $incasari, $total, $total_pages, $page, $per_page,
 *   $tip_filtru, $data_de_la, $data_pana_la, $cautare,
 *   $tipuri_afisare, $moduri_plata_afisare
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2">
            <i data-lucide="receipt" class="w-6 h-6" aria-hidden="true"></i>
            Încasări
        </h1>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="/incasari/setari" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-700 hover:bg-slate-800 dark:bg-slate-600 dark:hover:bg-slate-500 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-slate-500"
               aria-label="Deschide setările încasărilor într-o fereastră nouă">
                <i data-lucide="settings" class="w-4 h-4" aria-hidden="true"></i>
                Setări
            </a>
            <a href="/util/incasari-borderou-print.php?<?php echo htmlspecialchars(http_build_query(['tip' => $tip_filtru, 'serie' => $serie_filtru, 'data_de_la' => $data_de_la, 'data_pana_la' => $data_pana_la, 'q' => $cautare, 'per_page' => $per_page, 'page' => $page])); ?>" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500"
               aria-label="Tipărește borderoul de chitanțe pentru tabelul afișat">
                <i data-lucide="printer" class="w-4 h-4" aria-hidden="true"></i>
                Print
            </a>
            <span class="text-sm text-slate-500 dark:text-gray-400"><?php echo number_format($total); ?> încasări</span>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <nav class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Taburi modul încasări">
            <a href="/incasari"
               role="tab"
               aria-selected="<?php echo $tab === 'lista' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'lista' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Lista încasări
            </a>
            <a href="/incasari?tab=import_csv"
               role="tab"
               aria-selected="<?php echo $tab === 'import_csv' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'import_csv' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Import Încasări CSV
            </a>
        </nav>

        <?php if ($tab === 'import_csv'): ?>
            <?php if (!empty($import_eroare)): ?>
                <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg" role="alert">
                    <?php echo htmlspecialchars($import_eroare); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($import_succes)): ?>
                <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200 rounded-lg" role="status">
                    <?php echo htmlspecialchars($import_succes); ?>
                </div>
            <?php endif; ?>

            <?php if ($import_step === 'upload'): ?>
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="inc-import-upload-heading">
                    <h2 id="inc-import-upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        1. Încarcă fișierul CSV cu încasări istorice
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                        Import pentru încasări anterioare utilizării ERP-ului (mod plată: <strong>Chitanță veche</strong>).
                    </p>
                    <form method="post" action="/incasari?tab=import_csv" enctype="multipart/form-data" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="incasari_import_upload" value="1">
                        <div>
                            <label for="fisier_csv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Fișier CSV</label>
                            <input type="file" id="fisier_csv" name="fisier_csv" accept=".csv" required
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                            <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Se acceptă CSV delimitat cu virgulă sau punct și virgulă, maxim 10 MB.</p>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                                Continuă la mapare
                            </button>
                        </div>
                    </form>
                </section>
            <?php elseif ($import_step === 'map' && $import_csv_data): ?>
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="inc-import-map-heading">
                    <h2 id="inc-import-map-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">
                        2. Mapare coloane CSV
                    </h2>
                    <p class="text-sm text-slate-700 dark:text-gray-300 mb-2">
                        <strong><?php echo count($import_csv_data['rows']); ?></strong> rânduri detectate. Maparea <strong>Nr. dosar</strong> este obligatorie.
                    </p>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                        Câmpuri relevante: Nr. dosar, Data încasării, Tip încasare (cotizație/donație), An cotizație (2025/2026), Valoare încasată.
                    </p>

                    <form method="post" action="/incasari?tab=import_csv" class="space-y-4">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="incasari_import_execute" value="1">

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="tip_implicit" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip încasare implicit (opțional)</label>
                                <select id="tip_implicit" name="tip_implicit"
                                        class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                    <option value="">Din coloana mapată</option>
                                    <option value="cotizatie" <?php echo $tip_import_implicit === INCASARI_TIP_COTIZATIE ? 'selected' : ''; ?>>Cotizație</option>
                                    <option value="donatie" <?php echo $tip_import_implicit === INCASARI_TIP_DONATIE ? 'selected' : ''; ?>>Donație</option>
                                </select>
                            </div>
                            <div>
                                <label for="an_cotizatie_implicit" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">An cotizație implicit</label>
                                <select id="an_cotizatie_implicit" name="an_cotizatie_implicit"
                                        class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                    <option value="2025" <?php echo (int)$an_cotizatie_implicit === 2025 ? 'selected' : ''; ?>>2025</option>
                                    <option value="2026" <?php echo (int)$an_cotizatie_implicit === 2026 ? 'selected' : ''; ?>>2026</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-800 dark:text-gray-200">
                                    <input type="hidden" name="skip_cotizatii_achitate" value="0">
                                    <input type="checkbox" name="skip_cotizatii_achitate" value="1" <?php echo !empty($skip_cotizatii_achitate) ? 'checked' : ''; ?>
                                           class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                    Sare cotizațiile deja achitate
                                </label>
                            </div>
                        </div>

                        <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded-lg p-3 space-y-2" aria-label="Mapare coloane CSV către câmpuri import încasări">
                            <?php foreach ($import_csv_data['headers'] as $index => $header): ?>
                                <?php
                                $header_l = mb_strtolower((string)$header);
                                $suggested = 'ignora';
                                if (strpos($header_l, 'dosar') !== false) {
                                    $suggested = 'dosarnr';
                                } elseif (strpos($header_l, 'data') !== false) {
                                    $suggested = 'data_incasare';
                                } elseif (strpos($header_l, 'tip') !== false) {
                                    $suggested = 'tip_incasare';
                                } elseif (strpos($header_l, 'an') !== false && strpos($header_l, 'cot') !== false) {
                                    $suggested = 'an_cotizatie';
                                } elseif (strpos($header_l, 'suma') !== false || strpos($header_l, 'valoare') !== false) {
                                    $suggested = 'suma';
                                } elseif (strpos($header_l, 'observ') !== false) {
                                    $suggested = 'observatii';
                                } elseif (strpos($header_l, 'chit') !== false && strpos($header_l, 'veche') !== false) {
                                    $suggested = 'nr_chitanta_veche';
                                }
                                ?>
                                <div class="flex items-center gap-3">
                                    <label class="flex-1 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($header); ?></label>
                                    <select name="mapare_coloane[<?php echo (int)$index; ?>]"
                                            class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                        <option value="ignora">-- Ignoră --</option>
                                        <?php foreach ($campuri_import_incasari as $db_field => $label): ?>
                                            <option value="<?php echo htmlspecialchars($db_field); ?>" <?php echo $suggested === $db_field ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="/incasari?tab=import_csv" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                                Reia upload
                            </a>
                            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500"
                                    onclick="return confirm('Continuați cu importul încasărilor din CSV?');">
                                Importă încasări
                            </button>
                        </div>
                    </form>
                </section>
            <?php else: ?>
                <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="inc-import-done-heading">
                    <h2 id="inc-import-done-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Import finalizat</h2>
                    <p class="text-sm text-slate-700 dark:text-gray-300 mb-4">
                        Importul CSV pentru încasări istorice s-a încheiat. Pentru cotizații, înregistrările importate marchează cotizația ca achitată pentru anul selectat.
                    </p>
                    <a href="/incasari?tab=import_csv" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                        Import nou
                    </a>
                </section>
            <?php endif; ?>
        <?php else: ?>
        <!-- Filtre -->
        <form method="get" action="/incasari" class="mb-4 flex flex-wrap gap-3 items-end">
            <input type="hidden" name="tab" value="lista">
            <div>
                <label for="q" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Caută</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($cautare); ?>" placeholder="Nume, serie..."
                       class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm w-44">
            </div>
            <div>
                <label for="tip" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Tip</label>
                <select id="tip" name="tip" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                    <option value="">Toate</option>
                    <?php foreach ($tipuri_afisare as $k => $v): ?>
                    <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $tip_filtru === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="serie" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Serie chitanță</label>
                <select id="serie" name="serie" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                    <option value="">Toate seriile</option>
                    <?php foreach ($serie_options as $serie_opt): ?>
                    <option value="<?php echo htmlspecialchars($serie_opt); ?>" <?php echo $serie_filtru === $serie_opt ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($serie_opt); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="data_de_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">De la</label>
                <input type="date" id="data_de_la" name="data_de_la" value="<?php echo htmlspecialchars($data_de_la); ?>"
                       class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label for="data_pana_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Până la</label>
                <input type="date" id="data_pana_la" name="data_pana_la" value="<?php echo htmlspecialchars($data_pana_la); ?>"
                       class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label for="per_page" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Pe pagină</label>
                <select id="per_page" name="per_page" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                    <?php foreach ([25, 50, 100] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $per_page === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                Filtrează
            </button>
            <?php if (!empty($afiseaza_resetare_filtre)): ?>
            <a href="/incasari" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                Resetează
            </a>
            <?php endif; ?>
        </form>

        <?php if (empty($incasari)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-slate-200 dark:border-gray-700">
            <p class="text-slate-500 dark:text-gray-400">Nu există încasări.</p>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista încasări">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Tip</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Persoană</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Sumă</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Mod plată</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Chitanță</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Reprezentând</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Înregistrat de</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($incasari as $inc): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700/50" id="row-inc-<?php echo (int)$inc['id']; ?>">
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap"><?php echo date('d.m.Y', strtotime($inc['data_incasare'])); ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $tip_colors = [
                                    'cotizatie' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
                                    'donatie' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200',
                                    'taxa_participare' => 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200',
                                    'alte' => 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
                                ];
                                $tip_c = $tip_colors[$inc['tip']] ?? $tip_colors['alte'];
                                ?>
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded <?php echo $tip_c; ?>"><?php echo htmlspecialchars($tipuri_afisare[$inc['tip']] ?? $inc['tip']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                <?php
                                $nume_persoana = trim(($inc['nume'] ?? '') . ' ' . ($inc['prenume'] ?? ''));
                                if (!empty($inc['membru_id'])) {
                                    echo '<a href="/membru-profil?id=' . (int)$inc['membru_id'] . '" class="text-amber-600 dark:text-amber-400 hover:underline">' . htmlspecialchars($nume_persoana) . '</a>';
                                } else {
                                    echo htmlspecialchars($nume_persoana ?: '-');
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap"><?php echo number_format((float)$inc['suma'], 2, ',', '.'); ?> RON</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($moduri_plata_afisare[$inc['mod_plata']] ?? $inc['mod_plata']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400 whitespace-nowrap">
                                <?php if (!empty($inc['seria_chitanta'])): ?>
                                    <?php echo htmlspecialchars($inc['seria_chitanta']); ?> nr. <?php echo (int)$inc['nr_chitanta']; ?>
                                <?php else: ?>
                                    <span class="text-slate-400 dark:text-gray-500">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400" id="repr-<?php echo (int)$inc['id']; ?>"><?php echo htmlspecialchars($inc['reprezentand'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($inc['created_by'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if (!empty($inc['seria_chitanta'])): ?>
                                    <a href="/util/incasari-chitanta-print.php?id=<?php echo (int)$inc['id']; ?>" target="_blank"
                                       class="inline-flex items-center px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded hover:bg-amber-100 dark:hover:bg-amber-900/50"
                                       title="Printează chitanța">
                                        <i data-lucide="printer" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i>Print
                                    </a>
                                    <a href="/util/incasari-chitanta-pdf.php?id=<?php echo (int)$inc['id']; ?>"
                                       class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded hover:bg-blue-100 dark:hover:bg-blue-900/50"
                                       title="Descarcă PDF A4 (2 chitanțe)">
                                        <i data-lucide="file-down" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i>A4
                                    </a>
                                    <a href="/util/incasari-chitanta-pdf.php?id=<?php echo (int)$inc['id']; ?>&format=a5"
                                       class="inline-flex items-center px-2 py-1 text-xs font-medium text-violet-700 dark:text-white bg-violet-50 dark:bg-violet-900/30 border border-violet-200 dark:border-violet-700 rounded hover:bg-violet-100 dark:hover:bg-violet-900/50"
                                       title="Descarcă PDF A5 (1 chitanță)">
                                        <i data-lucide="file-down" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i>A5
                                    </a>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="btn-edit-incasare inline-flex items-center px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 rounded hover:bg-emerald-100 dark:hover:bg-emerald-900/50"
                                            data-id="<?php echo (int)$inc['id']; ?>"
                                            data-reprezentand="<?php echo htmlspecialchars($inc['reprezentand'] ?? ''); ?>"
                                            data-observatii="<?php echo htmlspecialchars($inc['observatii'] ?? ''); ?>"
                                            title="Editează">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i>Edit
                                    </button>
                                    <button type="button"
                                            class="btn-sterge-incasare inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded hover:bg-red-100 dark:hover:bg-red-900/50"
                                            data-id="<?php echo (int)$inc['id']; ?>"
                                            data-info="<?php echo htmlspecialchars(number_format((float)$inc['suma'], 2, ',', '.') . ' RON – ' . $nume_persoana . (!empty($inc['seria_chitanta']) ? ' – Chitanță ' . $inc['seria_chitanta'] . ' nr. ' . $inc['nr_chitanta'] : '')); ?>"
                                            title="Șterge">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i>Șterge
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="row" colspan="3" class="px-4 py-3 text-left text-sm font-semibold text-slate-900 dark:text-white">Total chitanțe (tabel afișat)</th>
                            <td class="px-4 py-3 text-right text-sm font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <?php echo number_format((float)$total_suma_afisata, 2, ',', '.'); ?> RON
                            </td>
                            <td colspan="5" class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo (int)$total_chitante_afisate; ?> chitanțe numerotate
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <!-- Paginare -->
            <div class="px-4 py-3 bg-slate-50 dark:bg-gray-700 border-t border-slate-200 dark:border-gray-600 flex flex-wrap justify-between items-center gap-2">
                <p class="text-sm text-slate-600 dark:text-gray-400">
                    Pagina <?php echo $page; ?> din <?php echo $total_pages; ?> (<?php echo number_format($total); ?> încasări)
                </p>
                <div class="flex gap-1">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo htmlspecialchars(build_incasari_url(['page' => $page - 1])); ?>"
                       class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300">&laquo; Anterior</a>
                    <?php endif; ?>
                    <?php
                    $start_p = max(1, $page - 2);
                    $end_p = min($total_pages, $page + 2);
                    for ($p = $start_p; $p <= $end_p; $p++):
                    ?>
                    <a href="<?php echo htmlspecialchars(build_incasari_url(['page' => $p])); ?>"
                       class="px-3 py-1 text-sm border rounded <?php echo $p === $page ? 'bg-amber-600 text-white border-amber-600' : 'border-slate-300 dark:border-gray-600 hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300'; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo htmlspecialchars(build_incasari_url(['page' => $page + 1])); ?>"
                       class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300">Următor &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Editare Încasare -->
<dialog id="modal-edit-incasare" class="p-0 rounded-xl shadow-2xl max-w-md w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800">
    <div class="p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Editare încasare</h2>
        <form id="form-edit-incasare" class="space-y-4">
            <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
            <input type="hidden" name="id" id="edit-inc-id" value="">
            <div>
                <label for="edit-inc-reprezentand" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Reprezentând</label>
                <input type="text" id="edit-inc-reprezentand" name="reprezentand" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
            </div>
            <div>
                <label for="edit-inc-observatii" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Observații</label>
                <textarea id="edit-inc-observatii" name="observatii" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white"></textarea>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg">Salvează</button>
                <button type="button" id="edit-inc-inchide" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300">Închide</button>
            </div>
        </form>
    </div>
</dialog>

<script>
(function(){
    var dialog = document.getElementById('modal-edit-incasare');
    var form = document.getElementById('form-edit-incasare');

    // Edit
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.btn-edit-incasare');
        if (btn && dialog) {
            document.getElementById('edit-inc-id').value = btn.getAttribute('data-id');
            document.getElementById('edit-inc-reprezentand').value = btn.getAttribute('data-reprezentand') || '';
            document.getElementById('edit-inc-observatii').value = btn.getAttribute('data-observatii') || '';
            dialog.showModal();
        }
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(form);
        fetch('/api/incasari-update', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.ok) {
                    dialog.close();
                    // Actualizăm celula din tabel
                    var reprCell = document.getElementById('repr-' + fd.get('id'));
                    if (reprCell) reprCell.textContent = fd.get('reprezentand') || '-';
                    // Actualizăm data- pe buton
                    var editBtn = document.querySelector('.btn-edit-incasare[data-id="' + fd.get('id') + '"]');
                    if (editBtn) {
                        editBtn.setAttribute('data-reprezentand', fd.get('reprezentand') || '');
                        editBtn.setAttribute('data-observatii', fd.get('observatii') || '');
                    }
                } else {
                    alert(data.eroare || 'Eroare la salvare.');
                }
            })
            .catch(function(){ alert('Eroare de rețea.'); });
    });

    document.getElementById('edit-inc-inchide').addEventListener('click', function(){ dialog.close(); });
    dialog.addEventListener('click', function(e){ if (e.target === dialog) dialog.close(); });

    // Ștergere
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.btn-sterge-incasare');
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        var info = btn.getAttribute('data-info');
        if (!confirm('Sigur doriți să ștergeți încasarea?\n\n' + info + '\n\nNumerotarea chitanțelor va fi recalculată.')) return;

        var fd = new FormData();
        fd.append('id', id);
        // Get CSRF token
        var csrfInput = document.querySelector('#form-edit-incasare input[name="_csrf_token"]');
        if (csrfInput) fd.append('_csrf_token', csrfInput.value);

        fetch('/api/incasari-sterge', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.ok) {
                    var row = document.getElementById('row-inc-' + id);
                    if (row) row.remove();
                    window.location.reload();
                } else {
                    alert(data.eroare || 'Eroare la ștergere.');
                }
            })
            .catch(function(){ alert('Eroare de rețea.'); });
    });
})();
</script>
