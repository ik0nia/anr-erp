<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Comunicare - Printing</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 rounded-lg" role="status">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <?php if ($rezultat_generare && !empty($rezultat_generare['filename'])): ?>
        <div class="mb-4 p-4 bg-blue-100 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-700 text-blue-800 dark:text-blue-200 rounded-lg">
            <div class="flex items-center gap-3">
                <i data-lucide="file-down" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
                <a href="/uploads/comunicare/<?php echo htmlspecialchars($rezultat_generare['filename']); ?>"
                   target="_blank"
                   class="font-medium underline hover:no-underline">
                    Descarca: <?php echo htmlspecialchars($rezultat_generare['filename']); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs -->
        <nav class="mb-6 flex gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Tab-uri comunicare">
            <a href="/comunicare" role="tab" aria-selected="<?php echo $tab === 'etichete' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'etichete' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                <i data-lucide="tag" class="w-4 h-4 inline mr-1" aria-hidden="true"></i> Etichete
            </a>
            <a href="/comunicare?tab=scrisori" role="tab" aria-selected="<?php echo $tab === 'scrisori' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'scrisori' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                <i data-lucide="mail" class="w-4 h-4 inline mr-1" aria-hidden="true"></i> Scrisori
            </a>
        </nav>

        <?php if ($tab === 'etichete'): ?>
        <!-- Tab Etichete -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Generare Etichete</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Genereaza etichete PDF cu adresa membrilor pentru corespondenta postala.</p>

            <form method="POST" action="/comunicare" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="genereaza_etichete" value="1">

                <!-- Dimensiuni eticheta -->
                <fieldset class="border border-slate-200 dark:border-gray-700 rounded-lg p-4">
                    <legend class="text-sm font-medium text-slate-700 dark:text-gray-300 px-2">Dimensiuni Eticheta (mm)</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                        <div>
                            <label for="latime_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Latime (mm)</label>
                            <input type="number" id="latime_mm" name="latime_mm" value="89" min="30" max="210" step="1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                        <div>
                            <label for="inaltime_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Inaltime (mm)</label>
                            <input type="number" id="inaltime_mm" name="inaltime_mm" value="36" min="15" max="297" step="1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                    </div>
                </fieldset>

                <!-- Filtre -->
                <?php include __DIR__ . '/_filtre_membri.php'; ?>

                <!-- Preview count -->
                <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                    <i data-lucide="users" class="w-5 h-5 text-slate-500 dark:text-gray-400 shrink-0" aria-hidden="true"></i>
                    <span class="text-sm text-slate-700 dark:text-gray-300">
                        Membri care corespund filtrelor: <strong class="text-amber-600 dark:text-amber-400"><?php echo $preview_count; ?></strong>
                    </span>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <i data-lucide="printer" class="w-5 h-5" aria-hidden="true"></i>
                        Genereaza Etichete PDF
                    </button>
                </div>
            </form>
        </div>

        <?php elseif ($tab === 'scrisori'): ?>
        <!-- Tab Scrisori -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Generare Scrisori</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Genereaza scrisori personalizate din template pentru fiecare membru. Tagurile din template (ex: [nume], [prenume], [adresa_completa]) vor fi inlocuite automat.</p>

            <form method="POST" action="/comunicare?tab=scrisori" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="genereaza_scrisori" value="1">

                <!-- Template selection -->
                <div>
                    <label for="template_id" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Template Scrisoare</label>
                    <?php if (empty($templates)): ?>
                        <p class="text-sm text-red-600 dark:text-red-400">Nu exista template-uri active. Adaugati template-uri din <a href="/librarie-documente" class="underline">Librarie Documente</a>.</p>
                    <?php else: ?>
                        <select id="template_id" name="template_id" required
                                class="w-full sm:w-1/2 rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                            <option value="">-- Selecteaza template --</option>
                            <?php foreach ($templates as $tpl): ?>
                                <option value="<?php echo (int)$tpl['id']; ?>"><?php echo htmlspecialchars($tpl['nume_afisare']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- Filtre -->
                <?php include __DIR__ . '/_filtre_membri.php'; ?>

                <!-- Preview count -->
                <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                    <i data-lucide="users" class="w-5 h-5 text-slate-500 dark:text-gray-400 shrink-0" aria-hidden="true"></i>
                    <span class="text-sm text-slate-700 dark:text-gray-300">
                        Membri care corespund filtrelor: <strong class="text-amber-600 dark:text-amber-400"><?php echo $preview_count; ?></strong>
                    </span>
                </div>

                <div class="flex justify-end">
                    <button type="submit" <?php echo empty($templates) ? 'disabled' : ''; ?>
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="file-text" class="w-5 h-5" aria-hidden="true"></i>
                        Genereaza Scrisori PDF
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>
<script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
