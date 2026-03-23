<?php
/**
 * View: Registratura — Formular adaugare
 *
 * Variabile: $eroare, $redirect_param, $redirect_url
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Înregistrare document în Registratura</h1>
        <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Înapoi">← Înapoi</a>
    </header>

    <div class="p-6 overflow-y-auto flex-1 max-w-2xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="/registratura/adauga?redirect=<?php echo htmlspecialchars($redirect_param); ?>" id="form-registratura">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_registratura" value="1">
                <div class="space-y-4">
                    <!-- Butoane tip document -->
                    <div class="flex gap-3 mb-4">
                        <button type="button" id="btn-document-primit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-blue-500 transition-colors" aria-label="Document primit - completează automat destinatarul cu ANR Bihor">
                            Document primit
                        </button>
                        <button type="button" id="btn-document-emis" class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-green-500 transition-colors" aria-label="Document emis - completează automat proveniența cu ANR Bihor">
                            Document emis
                        </button>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-2" role="status" aria-live="polite">
                        Nr. înregistrare intern și data se alocă automat la salvare.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="reg-nr-document" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr document</label>
                            <input type="text" id="reg-nr-document" name="nr_document" value="<?php echo htmlspecialchars($_POST['nr_document'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   placeholder="Ex: 123/2025">
                        </div>
                        <div>
                            <label for="reg-data-document" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data document</label>
                            <input type="date" id="reg-data-document" name="data_document" value="<?php echo htmlspecialchars($_POST['data_document'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        </div>
                    </div>
                    <div>
                        <label for="reg-provine" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">De unde provine documentul</label>
                        <input type="text" id="reg-provine" name="provine_din" value="<?php echo htmlspecialchars($_POST['provine_din'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Ex: Primăria Oradea, Minister, Membru">
                    </div>
                    <div>
                        <label for="reg-continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Conținut document</label>
                        <textarea id="reg-continut" name="continut_document" rows="3"
                                  class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                  placeholder="Rezumat sau descriere"><?php echo htmlspecialchars($_POST['continut_document'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="reg-destinatar" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Destinatar document</label>
                        <input type="text" id="reg-destinatar" name="destinatar_document" value="<?php echo htmlspecialchars($_POST['destinatar_document'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Către cine se trimite">
                    </div>
                    <div class="text-sm text-slate-600 dark:text-gray-400">
                        Operator: <strong><?php echo htmlspecialchars($_SESSION['utilizator'] ?? 'Utilizator'); ?></strong>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="hidden" name="task_deschis" value="0">
                            <input type="checkbox" name="task_deschis" value="1" <?php echo isset($_POST['task_deschis']) ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
                                   aria-describedby="task-desc">
                            <span id="task-desc" class="ml-2 text-sm text-slate-800 dark:text-gray-200">Task deschis (se trimite către modulul Taskuri)</span>
                        </label>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulează">Anulare</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează înregistrarea">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    // Buton Document primit
    const btnDocumentPrimit = document.getElementById('btn-document-primit');
    if (btnDocumentPrimit) {
        btnDocumentPrimit.addEventListener('click', function() {
            const destinatarInput = document.getElementById('reg-destinatar');
            if (destinatarInput) {
                destinatarInput.value = 'ANR Bihor';
                // Marchează butonul ca activ
                btnDocumentPrimit.classList.add('ring-2', 'ring-blue-400');
                document.getElementById('btn-document-emis').classList.remove('ring-2', 'ring-green-400');
            }
        });
    }
    
    // Buton Document emis
    const btnDocumentEmis = document.getElementById('btn-document-emis');
    if (btnDocumentEmis) {
        btnDocumentEmis.addEventListener('click', function() {
            const provineInput = document.getElementById('reg-provine');
            if (provineInput) {
                provineInput.value = 'ANR Bihor';
                // Marchează butonul ca activ
                btnDocumentEmis.classList.add('ring-2', 'ring-green-400');
                document.getElementById('btn-document-primit').classList.remove('ring-2', 'ring-blue-400');
            }
        });
    }
});
</script>
</body>
</html>
