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

                <fieldset class="border border-slate-200 dark:border-gray-700 rounded-lg p-4">
                    <legend class="text-sm font-medium text-slate-700 dark:text-gray-300 px-2">Tip etichete</legend>
                    <div>
                        <label for="tip_etichete" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Alege formatul de printare</label>
                        <select id="tip_etichete" name="tip_etichete" aria-controls="config-etichete-rola config-etichete-a4"
                                class="w-full sm:w-80 rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                            <option value="rola" <?php echo $tip_etichete_selectat === 'rola' ? 'selected' : ''; ?>>Etichete rola (imprimanta termica)</option>
                            <option value="a4" <?php echo $tip_etichete_selectat === 'a4' ? 'selected' : ''; ?>>Etichete A4 (coala adeziva)</option>
                        </select>
                    </div>
                </fieldset>

                <!-- Dimensiuni eticheta rola -->
                <fieldset id="config-etichete-rola" class="border border-slate-200 dark:border-gray-700 rounded-lg p-4 <?php echo $tip_etichete_selectat === 'rola' ? '' : 'hidden'; ?>">
                    <legend class="text-sm font-medium text-slate-700 dark:text-gray-300 px-2">Configurare etichete rola (mm)</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                        <div>
                            <label for="latime_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Latime (mm)</label>
                            <input type="number" id="latime_mm" name="latime_mm" value="<?php echo htmlspecialchars((string)$latime_mm_input); ?>" min="30" max="210" step="1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                        <div>
                            <label for="inaltime_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Inaltime (mm)</label>
                            <input type="number" id="inaltime_mm" name="inaltime_mm" value="<?php echo htmlspecialchars((string)$inaltime_mm_input); ?>" min="15" max="297" step="1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                    </div>
                </fieldset>

                <!-- Configurare etichete A4 -->
                <fieldset id="config-etichete-a4" class="border border-slate-200 dark:border-gray-700 rounded-lg p-4 <?php echo $tip_etichete_selectat === 'a4' ? '' : 'hidden'; ?>">
                    <legend class="text-sm font-medium text-slate-700 dark:text-gray-300 px-2">Configurare etichete A4</legend>
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1 mb-4">
                        Margini pagina in mm (sus/jos/stanga/dreapta), numar coloane/randuri.
                        Fiecare eticheta A4 pastreaza automat o margine interna neprintabila de 1.5 mm pe toate laturile.
                    </p>
                    <div class="mb-4">
                        <label for="a4_preset" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Format frecvent A4</label>
                        <select id="a4_preset" name="a4_preset" aria-describedby="a4-preset-help"
                                class="w-full sm:w-[28rem] rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                            <option value="custom" <?php echo $a4_preset_input === 'custom' ? 'selected' : ''; ?>>Personalizat (manual)</option>
                            <option value="a4_2x7" <?php echo $a4_preset_input === 'a4_2x7' ? 'selected' : ''; ?>>Plicuri mari - 2 coloane x 7 randuri</option>
                            <option value="a4_3x8" <?php echo $a4_preset_input === 'a4_3x8' ? 'selected' : ''; ?>>Plicuri standard - 3 coloane x 8 randuri</option>
                            <option value="a4_3x10" <?php echo $a4_preset_input === 'a4_3x10' ? 'selected' : ''; ?>>Etichete compacte - 3 coloane x 10 randuri</option>
                            <option value="a4_4x10" <?php echo $a4_preset_input === 'a4_4x10' ? 'selected' : ''; ?>>Etichete mici - 4 coloane x 10 randuri</option>
                        </select>
                        <p id="a4-preset-help" class="mt-1 text-xs text-slate-600 dark:text-gray-400">
                            Preseturile completeaza automat marginile si grila, apoi pot fi ajustate manual.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="a4_margin_top_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Margine sus (mm)</label>
                            <input type="number" id="a4_margin_top_mm" name="a4_margin_top_mm" value="<?php echo htmlspecialchars((string)$a4_margin_top_mm_input); ?>" min="0" max="40" step="0.1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                        <div>
                            <label for="a4_margin_bottom_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Margine jos (mm)</label>
                            <input type="number" id="a4_margin_bottom_mm" name="a4_margin_bottom_mm" value="<?php echo htmlspecialchars((string)$a4_margin_bottom_mm_input); ?>" min="0" max="40" step="0.1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                        <div>
                            <label for="a4_margin_left_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Margine stanga (mm)</label>
                            <input type="number" id="a4_margin_left_mm" name="a4_margin_left_mm" value="<?php echo htmlspecialchars((string)$a4_margin_left_mm_input); ?>" min="0" max="40" step="0.1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                        <div>
                            <label for="a4_margin_right_mm" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Margine dreapta (mm)</label>
                            <input type="number" id="a4_margin_right_mm" name="a4_margin_right_mm" value="<?php echo htmlspecialchars((string)$a4_margin_right_mm_input); ?>" min="0" max="40" step="0.1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="a4_cols" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Numar coloane</label>
                            <input type="number" id="a4_cols" name="a4_cols" value="<?php echo (int)$a4_cols_input; ?>" min="1" max="10" step="1"
                                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                        </div>
                        <div>
                            <label for="a4_rows" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Numar randuri</label>
                            <input type="number" id="a4_rows" name="a4_rows" value="<?php echo (int)$a4_rows_input; ?>" min="1" max="20" step="1"
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
    (function () {
        var tipSelect = document.getElementById('tip_etichete');
        var rolaConfig = document.getElementById('config-etichete-rola');
        var a4Config = document.getElementById('config-etichete-a4');
        var presetSelect = document.getElementById('a4_preset');
        var a4Top = document.getElementById('a4_margin_top_mm');
        var a4Bottom = document.getElementById('a4_margin_bottom_mm');
        var a4Left = document.getElementById('a4_margin_left_mm');
        var a4Right = document.getElementById('a4_margin_right_mm');
        var a4Cols = document.getElementById('a4_cols');
        var a4Rows = document.getElementById('a4_rows');
        if (!tipSelect || !rolaConfig || !a4Config) {
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        var presetMap = {
            a4_2x7: { top: 10, bottom: 10, left: 10, right: 10, cols: 2, rows: 7 },
            a4_3x8: { top: 8, bottom: 8, left: 7, right: 7, cols: 3, rows: 8 },
            a4_3x10: { top: 6, bottom: 6, left: 6, right: 6, cols: 3, rows: 10 },
            a4_4x10: { top: 6, bottom: 6, left: 5, right: 5, cols: 4, rows: 10 }
        };

        function applyPresetIfAvailable() {
            if (!presetSelect || !a4Top || !a4Bottom || !a4Left || !a4Right || !a4Cols || !a4Rows) {
                return;
            }
            var selectedPreset = presetSelect.value;
            if (!presetMap[selectedPreset]) {
                return;
            }
            var preset = presetMap[selectedPreset];
            a4Top.value = preset.top;
            a4Bottom.value = preset.bottom;
            a4Left.value = preset.left;
            a4Right.value = preset.right;
            a4Cols.value = preset.cols;
            a4Rows.value = preset.rows;
        }

        function markPresetAsCustom() {
            if (presetSelect && presetSelect.value !== 'custom') {
                presetSelect.value = 'custom';
            }
        }

        function updateTipEticheteUI() {
            var isA4 = tipSelect.value === 'a4';
            rolaConfig.classList.toggle('hidden', isA4);
            a4Config.classList.toggle('hidden', !isA4);
            rolaConfig.setAttribute('aria-hidden', isA4 ? 'true' : 'false');
            a4Config.setAttribute('aria-hidden', !isA4 ? 'true' : 'false');
        }

        if (presetSelect) {
            presetSelect.addEventListener('change', applyPresetIfAvailable);
        }
        [a4Top, a4Bottom, a4Left, a4Right, a4Cols, a4Rows].forEach(function (el) {
            if (el) {
                el.addEventListener('input', markPresetAsCustom);
            }
        });

        tipSelect.addEventListener('change', updateTipEticheteUI);
        applyPresetIfAvailable();
        updateTipEticheteUI();
    })();
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
