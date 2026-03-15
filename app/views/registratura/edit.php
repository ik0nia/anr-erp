<?php
/**
 * View: Registratura — Formular editare
 *
 * Variabile: $eroare, $r, $id
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Editează înregistrare nr. <?php echo htmlspecialchars($r['nr_inregistrare']); ?></h1>
        <a href="/registratura" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Înapoi la management">← Înapoi</a>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-2xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="/registratura/edit?id=<?php echo (int)$id; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizeaza_registratura" value="1">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                <div class="space-y-4">
                    <div class="text-sm text-slate-600 dark:text-gray-400">
                        Nr. înregistrare: <strong><?php echo htmlspecialchars($r['nr_inregistrare']); ?></strong> (imutabil)
                    </div>
                    <div>
                        <label for="reg-data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" id="reg-data" name="data" required value="<?php echo htmlspecialchars($_POST['data'] ?? date('Y-m-d', strtotime($r['data_ora']))); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-nr-document" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr document</label>
                            <input type="text" id="reg-nr-document" name="nr_document" value="<?php echo htmlspecialchars($_POST['nr_document'] ?? $r['nr_document'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   placeholder="Ex: 123/2025">
                        </div>
                        <div>
                            <label for="reg-data-document" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data document</label>
                            <input type="date" id="reg-data-document" name="data_document" value="<?php echo htmlspecialchars($_POST['data_document'] ?? ($r['data_document'] ?? '')); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        </div>
                    </div>
                    <div>
                        <label for="reg-provine" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">De unde provine documentul</label>
                        <input type="text" id="reg-provine" name="provine_din" value="<?php echo htmlspecialchars($_POST['provine_din'] ?? $r['provine_din'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Ex: Primăria Oradea, Minister, Membru">
                    </div>
                    <div>
                        <label for="reg-continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Conținut document</label>
                        <textarea id="reg-continut" name="continut_document" rows="3"
                                  class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                  placeholder="Rezumat sau descriere"><?php echo htmlspecialchars($_POST['continut_document'] ?? $r['continut_document'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="reg-destinatar" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Destinatar document</label>
                        <input type="text" id="reg-destinatar" name="destinatar_document" value="<?php echo htmlspecialchars($_POST['destinatar_document'] ?? $r['destinatar_document'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Către cine se trimite">
                    </div>
                    <div class="text-sm text-slate-600 dark:text-gray-400">
                        Operator: <strong><?php echo htmlspecialchars($r['utilizator'] ?? 'Necunoscut'); ?></strong>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="task_deschis" value="1" <?php echo (!empty($r['task_deschis']) || isset($_POST['task_deschis'])) ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
                                   aria-describedby="task-desc">
                            <span id="task-desc" class="ml-2 text-sm text-slate-800 dark:text-gray-200">Task deschis (în modulul Taskuri)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <a href="/registratura" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulează">Anulare</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează modificările">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
