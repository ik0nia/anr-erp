<main class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Import contacte din Excel/CSV</h1>
        <a href="/contacte" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">&larr; Inapoi</a>
    </header>
    <div class="p-6 overflow-y-auto flex-1 max-w-4xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <?php if ($excel_data === null): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="/contacte/import" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="import_contacte" value="1">
                <div>
                    <label for="fisier_excel" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Fisier Excel/CSV</label>
                    <input type="file" id="fisier_excel" name="fisier_excel" accept=".csv,.xlsx,.xls" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Maxim 10 MB. Formate: CSV, Excel (.xlsx, .xls)</p>
                </div>
                <button type="submit" class="mt-4 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Incarca si mapeaza</button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="/contacte/import" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="import_contacte" value="1">
                <input type="hidden" name="executa_import" value="1">
                <p class="mb-4"><strong><?php echo count($excel_data['rows']); ?></strong> randuri gasite. Mapeaza coloanele:</p>
                <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded p-3 space-y-2 mb-4">
                    <?php foreach ($excel_data['headers'] as $index => $header): ?>
                    <div class="flex items-center gap-3">
                        <label class="flex-1 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($header); ?></label>
                        <select name="mapare_coloane[<?php echo $index; ?>]" class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white">
                            <option value="ignora">-- Ignora --</option>
                            <?php foreach ($campuri_contacte as $db_field => $label): ?>
                            <option value="<?php echo htmlspecialchars($db_field); ?>" <?php echo (isset($mapare_coloane[$index]) && $mapare_coloane[$index] === $db_field) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="location.reload()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg">Anuleaza</button>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Importa contacte</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
