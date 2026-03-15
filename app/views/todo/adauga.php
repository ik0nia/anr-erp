<?php
/**
 * View: Todo — Formular adaugare standalone (din Dashboard)
 *
 * Variabile: $eroare
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Adaugă task</h1>
        <a href="/dashboard" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">← Înapoi la Dashboard</a>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-2xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Date task nou</h2>
            <form method="post" action="/todo/adauga">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="adauga_task" value="1">
                <div class="space-y-4">
                    <div>
                        <label for="nume-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600">*</span></label>
                        <input type="text" id="nume-task" name="nume" required value="<?php echo htmlspecialchars($_POST['nume'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Ex: Întâlnire comitet">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="data-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600">*</span></label>
                            <input type="date" id="data-task" name="data" required value="<?php echo htmlspecialchars($_POST['data'] ?? date('Y-m-d')); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        </div>
                        <div>
                            <label for="ora-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora <span class="text-slate-500 text-xs">(opțional)</span></label>
                            <input type="time" id="ora-task" name="ora" value="<?php echo htmlspecialchars($_POST['ora'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                            <span class="text-xs text-slate-500 dark:text-gray-400">Lăsați gol pentru ora implicită (09:00)</span>
                        </div>
                    </div>
                    <div>
                        <label for="detalii-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                        <textarea id="detalii-task" name="detalii" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                  placeholder="Detalii opționale"><?php echo htmlspecialchars($_POST['detalii'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="nivel-urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                        <select id="nivel-urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                            <option value="normal" <?php echo ($_POST['nivel_urgenta'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="important" <?php echo ($_POST['nivel_urgenta'] ?? '') === 'important' ? 'selected' : ''; ?>>Important</option>
                            <option value="reprogramat" <?php echo ($_POST['nivel_urgenta'] ?? '') === 'reprogramat' ? 'selected' : ''; ?>>Reprogramat</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <a href="/dashboard" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
