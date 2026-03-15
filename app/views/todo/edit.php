<?php
/**
 * View: Todo — Formular editare + trimitere notificare
 *
 * Variabile: $eroare, $succes, $task, $task_id
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Editează task</h1>
        <a href="todo.php" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2" aria-hidden="true"></i> Înapoi la Taskuri
        </a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-2xl">
            <form method="post" action="todo-edit.php?id=<?php echo (int)$task_id; ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizeaza_task" value="1">

                <div>
                    <label for="nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600">*</span></label>
                    <input type="text" id="nume" name="nume" required value="<?php echo htmlspecialchars($task['nume']); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600">*</span></label>
                        <input type="date" id="data" name="data" required value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($task['data_ora']))); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="ora" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora</label>
                        <input type="time" id="ora" name="ora" value="<?php echo htmlspecialchars(date('H:i', strtotime($task['data_ora']))); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                </div>

                <div>
                    <label for="detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="detalii" name="detalii" rows="4"
                              class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($task['detalii'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="nivel_urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                    <select id="nivel_urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        <option value="normal" <?php echo $task['nivel_urgenta'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="important" <?php echo $task['nivel_urgenta'] === 'important' ? 'selected' : ''; ?>>Important</option>
                        <option value="reprogramat" <?php echo $task['nivel_urgenta'] === 'reprogramat' ? 'selected' : ''; ?>>Reprogramat</option>
                    </select>
                </div>

                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200 dark:border-gray-700">
                    <a href="todo.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulează</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează</button>
                </div>
            </form>

            <!-- Buton notificare -->
            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-gray-700">
                <form method="post" action="todo-edit.php?id=<?php echo (int)$task_id; ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="trimite_notificare" value="1">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-violet-500 transition"
                            onclick="return confirm('Sunteți sigur că doriți să creați o notificare pentru toți utilizatorii platformei cu datele acestui task?');">
                        <i data-lucide="bell" class="w-4 h-4 mr-2" aria-hidden="true"></i> Trimite notificare
                    </button>
                </form>
                <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">Creează o notificare pentru toți utilizatorii platformei cu datele acestui task.</p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
