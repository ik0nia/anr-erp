<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Actualizare membri din CSV</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Incarca un CSV, mapeaza coloanele si actualizeaza membrii existenti dupa Nr. Dosar.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/membri"
               class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                &larr; Inapoi la Membri
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-5xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($step === 'upload'): ?>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="upload-heading">
            <h2 id="upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">1. Incarca fisierul CSV</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Fisierul trebuie sa contina o coloana cu Nr. Dosar (DosarNr), care va fi folosita pentru potrivirea cu membrii existenti. Dupa mapare, se actualizeaza toate datele mapate pentru membrii gasiti.
            </p>
            <form method="post" action="/actualizeaza-csv" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizare_upload" value="1">
                <div>
                    <label for="fisier_csv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Fisier CSV <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="file" id="fisier_csv" name="fisier_csv" accept=".csv" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">CSV separat cu virgula, maxim 10 MB.</p>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="/membri" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anuleaza</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">Continua la mapare</button>
                </div>
            </form>
        </section>

        <?php elseif ($step === 'map' && $excel_data): ?>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="map-heading">
            <h2 id="map-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">2. Mapare coloane CSV &rarr; campuri Membri</h2>
            <p class="text-sm text-slate-700 dark:text-gray-300 mb-2">
                <strong><?php echo count($excel_data['rows']); ?></strong> randuri gasite. <strong>Mapati obligatoriu coloana Nr. Dosar (dosarnr)</strong> – ea determina ce membru se actualizeaza.
            </p>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Pentru fiecare coloana din CSV alegeti campul corespunzator. Coloanele nemapate sunt ignorate.
            </p>

            <form method="post" action="/actualizeaza-csv" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizare_execute" value="1">
                <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded p-3 space-y-2">
                    <?php foreach ($excel_data['headers'] as $index => $header): ?>
                    <div class="flex items-center gap-3">
                        <label class="flex-1 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($header); ?></label>
                        <select name="mapare_coloane[<?php echo (int)$index; ?>]" class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm dark:bg-gray-700 dark:text-white">
                            <option value="ignora">-- Ignora --</option>
                            <?php foreach ($campuri_membri as $db_field => $label): ?>
                            <option value="<?php echo htmlspecialchars($db_field); ?>" <?php echo ($db_field === 'dosarnr' && (stripos($header, 'dosar') !== false || stripos($header, 'nr') !== false)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?><?php echo $db_field === 'dosarnr' ? ' (obligatoriu)' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" onclick="return confirm('Executati actualizarea? Membrii cu Nr. Dosar gasit vor fi actualizati conform CSV.');">Actualizeaza membri</button>
                </div>
            </form>
        </section>

        <?php else: ?>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Actualizare finalizata</h2>
            <p class="text-sm text-slate-700 dark:text-gray-300 mb-4">Poti reveni la membri sau porni o noua actualizare.</p>
            <div class="flex flex-wrap gap-3">
                <a href="/membri" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">&larr; Inapoi la Membri</a>
                <a href="/actualizeaza-csv" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Porneste o noua actualizare</a>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
