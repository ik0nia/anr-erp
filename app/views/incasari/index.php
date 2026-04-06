<?php
/**
 * View: Încasări — taburi „Încasări numerar” și „Toate încasările”
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
            <span class="text-sm text-slate-500 dark:text-gray-400">
                <?php echo number_format($tab === 'toate' ? $total_toate : $total); ?> încasări
            </span>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <div class="mb-5 border-b border-slate-200 dark:border-gray-700">
            <nav class="-mb-px flex flex-wrap gap-2" role="tablist" aria-label="Taburi modul încasări">
                <a href="/incasari?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['tab' => 'numerar', 'page' => 1]))); ?>"
                   role="tab"
                   aria-selected="<?php echo $tab === 'numerar' ? 'true' : 'false'; ?>"
                   aria-controls="tabpanel-incasari-numerar"
                   id="tab-incasari-numerar"
                   class="inline-flex items-center gap-2 px-4 py-2 border-b-2 text-sm font-medium <?php echo $tab === 'numerar' ? 'border-amber-600 text-amber-700 dark:text-amber-400' : 'border-transparent text-slate-600 dark:text-gray-300 hover:text-slate-800 dark:hover:text-white hover:border-slate-300 dark:hover:border-gray-500'; ?>">
                    <i data-lucide="wallet" class="w-4 h-4" aria-hidden="true"></i>
                    Încasări numerar
                </a>
                <a href="/incasari?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['tab' => 'toate', 'page' => 1]))); ?>"
                   role="tab"
                   aria-selected="<?php echo $tab === 'toate' ? 'true' : 'false'; ?>"
                   aria-controls="tabpanel-incasari-toate"
                   id="tab-incasari-toate"
                   class="inline-flex items-center gap-2 px-4 py-2 border-b-2 text-sm font-medium <?php echo $tab === 'toate' ? 'border-amber-600 text-amber-700 dark:text-amber-400' : 'border-transparent text-slate-600 dark:text-gray-300 hover:text-slate-800 dark:hover:text-white hover:border-slate-300 dark:hover:border-gray-500'; ?>">
                    <i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i>
                    Toate încasările
                </a>
            </nav>
        </div>

        <?php if ($tab === 'numerar'): ?>
            <section id="tabpanel-incasari-numerar" role="tabpanel" aria-labelledby="tab-incasari-numerar">
                <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
                    <a href="/util/incasari-borderou-print.php?<?php echo htmlspecialchars(http_build_query(['tip' => $tip_filtru, 'serie' => $serie_filtru, 'data_de_la' => $data_de_la, 'data_pana_la' => $data_pana_la, 'q' => $cautare, 'per_page' => $per_page, 'page' => $page])); ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500"
                       aria-label="Tipărește borderoul de chitanțe ERP pentru filtrele curente">
                        <i data-lucide="printer" class="w-4 h-4" aria-hidden="true"></i>
                        Print
                    </a>
                </div>

                <form method="get" action="/incasari" class="mb-4 flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="tab" value="numerar">
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
                        <label for="serie" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Serie chitanță ERP</label>
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
                    <a href="/incasari?tab=numerar" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                        Resetează
                    </a>
                    <?php endif; ?>
                </form>

                <?php if (empty($incasari)): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-slate-200 dark:border-gray-700">
                        <p class="text-slate-500 dark:text-gray-400">Nu există încasări numerar.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista încasări numerar">
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
                                        <th scope="row" colspan="3" class="px-4 py-3 text-left text-sm font-semibold text-slate-900 dark:text-white">Total încasări (tabel afișat)</th>
                                        <td class="px-4 py-3 text-right text-sm font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                            <?php echo number_format((float)$total_suma_afisata, 2, ',', '.'); ?> RON
                                        </td>
                                        <td colspan="5" class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                            <?php echo (int)$total_chitante_afisate; ?> documente numerotate
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <div class="px-4 py-3 bg-slate-50 dark:bg-gray-700 border-t border-slate-200 dark:border-gray-600 flex flex-wrap justify-between items-center gap-2">
                            <p class="text-sm text-slate-600 dark:text-gray-400">
                                Pagina <?php echo $page; ?> din <?php echo $total_pages; ?> (<?php echo number_format($total); ?> încasări)
                            </p>
                            <div class="flex gap-1">
                                <?php if ($page > 1): ?>
                                <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'numerar', 'page' => $page - 1])); ?>"
                                   class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300">&laquo; Anterior</a>
                                <?php endif; ?>
                                <?php
                                $start_p = max(1, $page - 2);
                                $end_p = min($total_pages, $page + 2);
                                for ($p = $start_p; $p <= $end_p; $p++):
                                ?>
                                <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'numerar', 'page' => $p])); ?>"
                                   class="px-3 py-1 text-sm border rounded <?php echo $p === $page ? 'bg-amber-600 text-white border-amber-600' : 'border-slate-300 dark:border-gray-600 hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300'; ?>"><?php echo $p; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'numerar', 'page' => $page + 1])); ?>"
                                   class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300">Următor &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section id="tabpanel-incasari-toate" role="tabpanel" aria-labelledby="tab-incasari-toate">
                <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
                    <button type="button"
                            onclick="window.print();"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500"
                            aria-label="Deschide fereastra de tipărire">
                        <i data-lucide="printer" class="w-4 h-4" aria-hidden="true"></i>
                        Print
                    </button>
                    <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'toate', 'export' => 'csv', 'page' => 1])); ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-emerald-500"
                       aria-label="Exportă datele filtrate în fișier CSV">
                        <i data-lucide="file-down" class="w-4 h-4" aria-hidden="true"></i>
                        Export
                    </a>
                </div>

                <form method="get" action="/incasari" class="mb-4 flex flex-wrap gap-3 items-end">
                    <input type="hidden" name="tab" value="toate">
                    <div>
                        <label for="all_tip" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Tip încasare</label>
                        <select id="all_tip" name="all_tip" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                            <option value="">Toate tipurile</option>
                            <?php foreach ($tipuri_afisare as $k => $v): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $all_tip_filtru === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="all_user" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Utilizator</label>
                        <select id="all_user" name="all_user" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                            <option value="">Toți utilizatorii</option>
                            <?php foreach ($utilizator_options as $usr): ?>
                            <option value="<?php echo htmlspecialchars($usr); ?>" <?php echo $all_user_filtru === $usr ? 'selected' : ''; ?>><?php echo htmlspecialchars($usr); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="all_mod" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Metodă plată</label>
                        <select id="all_mod" name="all_mod" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                            <option value="">Toate metodele</option>
                            <?php foreach ($moduri_plata_afisare as $mod_k => $mod_v): ?>
                            <option value="<?php echo htmlspecialchars($mod_k); ?>" <?php echo $all_mod_filtru === $mod_k ? 'selected' : ''; ?>><?php echo htmlspecialchars($mod_v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="all_data_de_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">De la data</label>
                        <input type="date" id="all_data_de_la" name="all_data_de_la" value="<?php echo htmlspecialchars($all_data_de_la); ?>"
                               class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label for="all_data_pana_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Până la data</label>
                        <input type="date" id="all_data_pana_la" name="all_data_pana_la" value="<?php echo htmlspecialchars($all_data_pana_la); ?>"
                               class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label for="per_page_all" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Pe pagină</label>
                        <select id="per_page_all" name="per_page" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                            <?php foreach ([25, 50, 100] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $per_page === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                        Filtrează
                    </button>
                    <?php if (!empty($afiseaza_resetare_filtre_toate)): ?>
                    <a href="/incasari?tab=toate" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                        Resetează
                    </a>
                    <?php endif; ?>
                </form>

                <?php if (empty($incasari_toate)): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center border border-slate-200 dark:border-gray-700">
                        <p class="text-slate-500 dark:text-gray-400">Nu există încasări pentru filtrele selectate.</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista tuturor încasărilor">
                                <thead class="bg-slate-100 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Data</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Tip</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Persoană</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Sumă</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Metodă plată</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Chitanță</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Reprezentând</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Înregistrat de</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                    <?php foreach ($incasari_toate as $inc): ?>
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
                                        <th scope="row" colspan="3" class="px-4 py-3 text-left text-sm font-semibold text-slate-900 dark:text-white">Total încasări (tabel afișat)</th>
                                        <td class="px-4 py-3 text-right text-sm font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                            <?php echo number_format((float)$total_suma_toate, 2, ',', '.'); ?> RON
                                        </td>
                                        <td colspan="5" class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                            <?php echo (int)$total_documente_toate; ?> documente numerotate
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($total_pages_toate > 1): ?>
                        <div class="px-4 py-3 bg-slate-50 dark:bg-gray-700 border-t border-slate-200 dark:border-gray-600 flex flex-wrap justify-between items-center gap-2">
                            <p class="text-sm text-slate-600 dark:text-gray-400">
                                Pagina <?php echo $page; ?> din <?php echo $total_pages_toate; ?> (<?php echo number_format($total_toate); ?> încasări)
                            </p>
                            <div class="flex gap-1">
                                <?php if ($page > 1): ?>
                                <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'toate', 'page' => $page - 1])); ?>"
                                   class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300">&laquo; Anterior</a>
                                <?php endif; ?>
                                <?php
                                $start_p = max(1, $page - 2);
                                $end_p = min($total_pages_toate, $page + 2);
                                for ($p = $start_p; $p <= $end_p; $p++):
                                ?>
                                <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'toate', 'page' => $p])); ?>"
                                   class="px-3 py-1 text-sm border rounded <?php echo $p === $page ? 'bg-amber-600 text-white border-amber-600' : 'border-slate-300 dark:border-gray-600 hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300'; ?>"><?php echo $p; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $total_pages_toate): ?>
                                <a href="<?php echo htmlspecialchars(build_incasari_url(['tab' => 'toate', 'page' => $page + 1])); ?>"
                                   class="px-3 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-100 dark:hover:bg-gray-600 text-slate-700 dark:text-gray-300">Următor &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
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
    var csrfTokenIncasari = "<?php echo htmlspecialchars(function_exists('csrf_token') ? csrf_token() : ''); ?>";

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
        if (!id) {
            alert('ID încasare invalid.');
            return;
        }

        var fd = new FormData();
        fd.append('id', id);
        if (csrfTokenIncasari) {
            fd.append('_csrf_token', csrfTokenIncasari);
        } else {
            // Fallback la tokenul din formularul de editare (dacă există în DOM).
            var csrfInput = document.querySelector('#form-edit-incasare input[name="_csrf_token"]');
            if (csrfInput && csrfInput.value) fd.append('_csrf_token', csrfInput.value);
        }

        fetch('/api/incasari-sterge', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){
                return r.text().then(function(text){
                    if (!r.ok) {
                        throw new Error(text || ('HTTP ' + r.status));
                    }
                    try { return JSON.parse(text); } catch (err) {
                        throw new Error('Răspuns invalid de la server.');
                    }
                });
            })
            .then(function(data){
                if (data.ok) {
                    var row = document.getElementById('row-inc-' + id);
                    if (row) row.remove();
                    window.location.reload();
                } else {
                    alert(data.eroare || 'Eroare la ștergere.');
                }
            })
            .catch(function(err){ alert((err && err.message) ? err.message : 'Eroare de rețea.'); });
    });
})();
</script>
