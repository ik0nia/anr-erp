<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Import membri CSV</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Importa membri dintr-un fisier CSV, cu mapare flexibila a coloanelor catre campurile bazei de date.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/util/export_membri.php"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Exporta membri existenti in CSV">
                <i data-lucide="download" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Export membri in CSV
            </a>
            <a href="/setari"
               class="inline-flex items-center px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                &larr; Inapoi la Setari
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-5xl">
        <?php if (!empty($eroare)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
                <p><?php echo htmlspecialchars($eroare); ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
            <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
                <p><?php echo htmlspecialchars($succes); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($step === 'upload'): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="upload-heading">
                <h2 id="upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    1. Incarca fisierul CSV
                </h2>
                <form method="post" action="/import-membri-csv" enctype="multipart/form-data" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="membri_import_upload" value="1">

                    <div>
                        <label for="fisier_csv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">
                            Fisier CSV <span class="text-red-600" aria-hidden="true">*</span>
                        </label>
                        <input type="file"
                               id="fisier_csv"
                               name="fisier_csv"
                               accept=".csv"
                               required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Se accepta doar fisiere CSV (delimitate prin virgula sau punct si virgula), maxim 10 MB.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="/setari"
                           class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Anuleaza
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Continua la mapare
                        </button>
                    </div>
                </form>
            </section>
        <?php elseif ($step === 'map' && $excel_data): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="map-heading">
                <h2 id="map-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    2. Mapare coloane CSV &rarr; campuri Membri
                </h2>
                <p class="text-sm text-slate-700 dark:text-gray-300 mb-2">
                    <strong><?php echo count($excel_data['rows']); ?></strong> randuri gasite in fisier.
                </p>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">
                    Alege pentru fiecare coloana din CSV campul corespunzator din baza de date. Coloanele pe care nu vrei sa le importi lasa-le la <strong>-- Ignora --</strong>.
                </p>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                    Poti <strong>adauga membri noi</strong> sau <strong>actualiza membri existenti dupa Nr. Dosar</strong>.
                </p>

                <form method="post" action="/import-membri-csv" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="membri_import_execute" value="1">

                    <fieldset class="border border-slate-200 dark:border-gray-600 rounded p-3">
                        <legend class="px-2 text-sm font-medium text-slate-800 dark:text-gray-200">Tip actiune import</legend>
                        <div class="mt-2 space-y-1 text-sm text-slate-700 dark:text-gray-300">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="actiune_import" value="adauga" checked
                                       class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                <span>Adauga membri noi (optional sare CNP-urile existente)</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="actiune_import" value="actualizeaza_dosarnr"
                                       class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                <span>Actualizeaza membri existenti dupa <strong>Nr. Dosar</strong> (cauta dupa campul "Nr. Dosar")</span>
                            </label>
                        </div>
                    </fieldset>

                    <div class="max-h-80 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded p-3 space-y-2" aria-label="Mapare coloane CSV catre campuri membri">
                        <?php foreach ($excel_data['headers'] as $index => $header): ?>
                            <div class="flex items-center gap-3">
                                <label class="flex-1 text-sm text-slate-700 dark:text-gray-300">
                                    <?php echo htmlspecialchars($header); ?>
                                </label>
                                <select name="mapare_coloane[<?php echo (int)$index; ?>]"
                                        class="flex-1 px-2 py-1 border border-slate-300 dark:border-gray-600 rounded text-sm text-slate-900 dark:text-white dark:bg-gray-700">
                                    <option value="ignora">-- Ignora --</option>
                                    <?php foreach ($campuri_membri as $db_field => $label): ?>
                                        <option value="<?php echo htmlspecialchars($db_field); ?>">
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="skip_duplicates" value="1" checked
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-slate-800 dark:text-gray-200">
                                Sari membri duplicati (CNP existent in baza de date)
                            </span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Importa
                        </button>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="done-heading">
                <h2 id="done-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    Import finalizat
                </h2>
                <p class="text-sm text-slate-700 dark:text-gray-300 mb-4">
                    Poti reveni la setari sau poti porni un nou import CSV.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="/setari"
                       class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        &larr; Inapoi la Setari
                    </a>
                    <a href="/import-membri-csv"
                       class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Porneste un nou import
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
