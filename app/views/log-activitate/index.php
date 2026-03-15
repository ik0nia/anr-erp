<?php
/**
 * View: Log Activitate — Lista cu paginare si filtrare
 *
 * Variabile disponibile (setate de controller):
 *   $logs, $total, $total_pages, $page, $per_page, $data_de_la, $data_pana_la, $eroare_bd
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Log activitate</h1>
        <span class="text-sm text-slate-500 dark:text-gray-400"><?php echo number_format($total); ?> inregistrari</span>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <!-- Filtre data -->
        <form method="get" action="/log-activitate" class="mb-4 flex flex-wrap gap-3 items-end">
            <div>
                <label for="data_de_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">De la</label>
                <input type="date" id="data_de_la" name="data_de_la" value="<?php echo htmlspecialchars($data_de_la); ?>"
                       class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label for="data_pana_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Pana la</label>
                <input type="date" id="data_pana_la" name="data_pana_la" value="<?php echo htmlspecialchars($data_pana_la); ?>"
                       class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
            </div>
            <div>
                <label for="per_page" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Pe pagina</label>
                <select id="per_page" name="per_page" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm">
                    <?php foreach ([25, 50, 100] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $per_page === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                Filtreaza
            </button>
            <?php if ($data_de_la !== '' || $data_pana_la !== ''): ?>
            <a href="/log-activitate" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                Reseteaza
            </a>
            <?php endif; ?>
        </form>

        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 dark:border-amber-500 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <p><?php echo htmlspecialchars($eroare_bd); ?></p>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Log activitate utilizatori">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Data / Ora</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Utilizator</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Actiune</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-600 dark:text-gray-400">Nu exista inregistrari in log.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php echo date(DATETIME_FORMAT, strtotime($log['data_ora'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($log['utilizator']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($log['actiune']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="mt-4 flex flex-wrap justify-between items-center gap-2" aria-label="Paginare log activitate">
            <p class="text-sm text-slate-600 dark:text-gray-400">
                Pagina <?php echo $page; ?> din <?php echo $total_pages; ?> (<?php echo number_format($total); ?> total)
            </p>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                <a href="<?php echo htmlspecialchars(build_log_url(['page' => $page - 1])); ?>"
                   class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700"
                   aria-label="Pagina anterioara">Ant.</a>
                <?php endif; ?>
                <?php
                $start_p = max(1, $page - 2);
                $end_p = min($total_pages, $page + 2);
                for ($p = $start_p; $p <= $end_p; $p++):
                ?>
                <a href="<?php echo htmlspecialchars(build_log_url(['page' => $p])); ?>"
                   class="px-3 py-1.5 border rounded text-sm <?php echo $p === $page ? 'bg-amber-600 text-white border-amber-600' : 'border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700'; ?>"
                   <?php echo $p === $page ? 'aria-current="page"' : ''; ?>><?php echo $p; ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                <a href="<?php echo htmlspecialchars(build_log_url(['page' => $page + 1])); ?>"
                   class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700"
                   aria-label="Pagina urmatoare">Urm.</a>
                <?php endif; ?>
            </div>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
