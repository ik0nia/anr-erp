<?php
/**
 * View: Todo — Lista taskuri active + istoric + modale add/edit
 *
 * Variabile: $taskuri_active, $taskuri_istoric, $eroare, $succes, $eroare_bd
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Taskuri</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 text-amber-900 dark:text-amber-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare_bd); ?></div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <header class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Taskuri</h2>
            <button type="button" onclick="document.getElementById('formular-task').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition"
                    aria-label="Adaugă task nou" aria-haspopup="dialog" id="btn-adauga-task">
                <i data-lucide="plus" class="mr-2 w-5 h-5" aria-hidden="true"></i> Adaugă task
            </button>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sarcini actuale -->
            <section class="flex flex-col" aria-labelledby="titlu-sarcini">
                <h3 id="titlu-sarcini" class="text-base font-semibold text-slate-900 dark:text-white mb-4">Sarcini actuale</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden flex-1">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Taskuri active">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-3 text-left"><span class="sr-only">Finalizat</span></th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data și oră</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Urgență</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($taskuri_active)): ?>
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu există taskuri active.</td></tr>
                                <?php else: foreach ($taskuri_active as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <form method="post" action="todo.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="finalizeaza_task" value="1">
                                            <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="marca_finalizat" onchange="(this.form.requestSubmit && this.form.requestSubmit()) || this.form.submit()"
                                                       class="w-5 h-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                                       aria-label="Marchează taskul <?php echo htmlspecialchars($t['nume']); ?> ca finalizat">
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-left">
                                        <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded"
                                           aria-label="Editează taskul <?php echo htmlspecialchars($t['nume']); ?>">
                                            <?php echo htmlspecialchars($t['nume']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo date(DATETIME_FORMAT, strtotime($t['data_ora'])); ?></td>
                                    <td class="px-4 py-3 text-left">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo task_badge_class($t['nivel_urgenta']); ?>"><?php echo htmlspecialchars(ucfirst($t['nivel_urgenta'])); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-left">
                                        <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500">
                                            <i data-lucide="edit" class="w-4 h-4" aria-hidden="true"></i> Editează
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Istoric -->
            <section class="flex flex-col" aria-labelledby="titlu-istoric">
                <h3 id="titlu-istoric" class="text-base font-semibold text-slate-900 dark:text-white mb-4">Istoric taskuri</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden flex-1">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Istoric taskuri finalizate">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-3 text-left"></th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data și oră</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Urgență</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($taskuri_istoric)): ?>
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu există taskuri finalizate.</td></tr>
                                <?php else: foreach ($taskuri_istoric as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-left"><i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i></td>
                                    <td class="px-4 py-3 text-left">
                                        <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>" class="font-medium text-slate-700 dark:text-gray-300 hover:underline focus:ring-2 focus:ring-amber-500 rounded">
                                            <?php echo htmlspecialchars($t['nume']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo $t['data_finalizare'] ? date(DATETIME_FORMAT, strtotime($t['data_finalizare'])) : '-'; ?></td>
                                    <td class="px-4 py-3 text-left">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo task_badge_class($t['nivel_urgenta']); ?>"><?php echo htmlspecialchars(ucfirst($t['nivel_urgenta'])); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-left">
                                        <div class="flex items-center justify-start gap-2">
                                            <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500">
                                                <i data-lucide="edit" class="w-4 h-4" aria-hidden="true"></i> Editează
                                            </a>
                                            <form method="post" action="todo.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="reactivare_task" value="1">
                                                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500"
                                                        aria-label="Reactivează taskul <?php echo htmlspecialchars($t['nume']); ?>">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4" aria-hidden="true"></i> Reactivează
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Modal adaugare task -->
<dialog id="formular-task" role="dialog" aria-modal="true" aria-labelledby="titlu-form-task" aria-describedby="desc-form-task"
        class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-form-task" class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adaugă task</h2>
        <p id="desc-form-task" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completați câmpurile pentru noul task.</p>
        <form method="post" action="todo.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_task" value="1">
            <div class="space-y-4">
                <div>
                    <label for="nume-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600">*</span></label>
                    <input type="text" id="nume-task" name="nume" required value="<?php echo htmlspecialchars($_POST['nume'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="data-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600">*</span></label>
                        <input type="date" id="data-task" name="data" required value="<?php echo htmlspecialchars($_POST['data'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="ora-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora <span class="text-red-600">*</span></label>
                        <input type="time" id="ora-task" name="ora" required value="<?php echo htmlspecialchars($_POST['ora'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                </div>
                <div>
                    <label for="detalii-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="detalii-task" name="detalii" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($_POST['detalii'] ?? ''); ?></textarea>
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
            <?php if (!empty($eroare) && isset($_POST['adauga_task'])): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 rounded text-red-800 text-sm" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
            <?php endif; ?>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('formular-task').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulează</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Modal editare task -->
<dialog id="detalii-task" role="dialog" aria-modal="true" aria-labelledby="titlu-detalii" class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-detalii" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Editează task</h2>
        <form method="post" action="todo.php" id="form-edit-task">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="actualizeaza_task" value="1">
            <input type="hidden" name="task_id" id="edit-task-id" value="">
            <input type="hidden" name="redirect_after" id="edit-redirect" value="todo.php">
            <div class="space-y-4">
                <div>
                    <label for="edit-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600">*</span></label>
                    <input type="text" id="edit-nume" name="nume" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600">*</span></label>
                        <input type="date" id="edit-data" name="data" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="edit-ora" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora</label>
                        <input type="time" id="edit-ora" name="ora" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                </div>
                <div>
                    <label for="edit-detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="edit-detalii" name="detalii" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></textarea>
                </div>
                <div>
                    <label for="edit-urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                    <select id="edit-urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="reprogramat">Reprogramat</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-renunta-task" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Renunță</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează</button>
            </div>
        </form>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    <?php if (!empty($eroare) && isset($_POST['adauga_task'])): ?>
    document.getElementById('formular-task').showModal();
    <?php endif; ?>
});
function deschideEditare(id, nume, data, ora, detalii, urgenta) {
    document.getElementById('edit-task-id').value = id;
    document.getElementById('edit-nume').value = nume || '';
    document.getElementById('edit-data').value = data || '';
    document.getElementById('edit-ora').value = ora || '09:00';
    document.getElementById('edit-detalii').value = detalii || '';
    document.getElementById('edit-urgenta').value = urgenta || 'normal';
    document.getElementById('edit-redirect').value = window.location.pathname.indexOf('index') >= 0 ? 'index.php' : 'todo.php';
    document.getElementById('detalii-task').showModal();
    document.getElementById('edit-nume').focus();
}
var dlgTask = document.getElementById('detalii-task');
if (dlgTask) {
    dlgTask.addEventListener('keydown', function(e) { if (e.key === 'Escape') this.close(); });
    document.getElementById('btn-renunta-task')?.addEventListener('click', function() { dlgTask.close(); });
}
</script>
</body>
</html>
