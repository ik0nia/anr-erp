<?php
/**
 * View: Librărie documente — Lista
 *
 * Variabile disponibile (setate de controller):
 *   $eroare, $succes_msg, $lista, $base_url
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Librărie documente</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>
        <?php if ($succes_msg): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes_msg); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Coloana stânga: Tabel documente -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700" aria-labelledby="titlu-lista">
                <h2 id="titlu-lista" class="text-lg font-semibold text-slate-900 dark:text-white p-4 border-b border-slate-200 dark:border-gray-700">Documente disponibile</h2>
                <div class="overflow-x-auto overflow-y-visible">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista documente librărie">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Instituția</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume document</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($lista)): ?>
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-slate-600 dark:text-gray-400">Niciun document încărcat. Încărcați un document în coloana din dreapta.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($lista as $d): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700 librarie-doc-row" data-doc-id="<?php echo (int)$d['id']; ?>" draggable="true" role="row">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($d['institutie']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-800 dark:text-gray-200"><?php echo htmlspecialchars($d['nume_document']); ?></td>
                                <td class="px-4 py-3 text-right" style="overflow: visible; position: relative;">
                                    <span class="inline-flex items-center gap-2 justify-end">
                                        <a href="util/print-librarie-document.php?id=<?php echo (int)$d['id']; ?>" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center px-2 py-1 text-sm text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 focus:ring-2 focus:ring-amber-500 rounded"
                                           aria-label="Tipărește <?php echo htmlspecialchars($d['nume_document']); ?>">
                                            <i data-lucide="printer" class="w-4 h-4 mr-1" aria-hidden="true"></i> Print
                                        </a>
                                        <a href="util/descarca-librarie-document.php?id=<?php echo (int)$d['id']; ?>"
                                           class="inline-flex items-center px-2 py-1 text-sm text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 focus:ring-2 focus:ring-amber-500 rounded"
                                           aria-label="Descarcă <?php echo htmlspecialchars($d['nume_document']); ?>">
                                            <i data-lucide="download" class="w-4 h-4 mr-1" aria-hidden="true"></i> Descarcă
                                        </a>
                                        <button type="button"
                                                onclick="deschideConfirmareStergere(<?php echo (int)$d['id']; ?>, <?php echo htmlspecialchars(json_encode($d['nume_document']), ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="inline-flex items-center px-2 py-1 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 focus:ring-2 focus:ring-red-500 rounded transition"
                                                aria-label="Șterge documentul <?php echo htmlspecialchars($d['nume_document']); ?>">
                                            <i data-lucide="trash-2" class="w-4 h-4 mr-1" aria-hidden="true"></i>
                                            Șterge documentul
                                        </button>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Coloana dreapta: Formular încărcare -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="titlu-incarcare">
                <h2 id="titlu-incarcare" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Încarcă document nou</h2>
                <form method="post" action="/librarie-documente" enctype="multipart/form-data" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="incarca_document" value="1">
                    <div>
                        <label for="document" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Fișier document <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="file" id="document" name="document" required
                               accept=".pdf,.doc,.docx,.xls,.xlsx"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               aria-describedby="document-desc">
                        <p id="document-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Word, Excel sau PDF. Maxim <?php echo LIBRARIE_DOC_MAX_MB; ?> MB.</p>
                    </div>
                    <div>
                        <label for="institutie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Instituția <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="text" id="institutie" name="institutie" required
                               value="<?php echo htmlspecialchars($_POST['institutie'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Ex: DSP Bihor">
                    </div>
                    <div>
                        <label for="nume_document" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Numele documentului <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="text" id="nume_document" name="nume_document" required
                               value="<?php echo htmlspecialchars($_POST['nume_document'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="Ex: Referat comisie evaluare">
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Încarcă documentul în librărie">
                        <i data-lucide="upload" class="w-4 h-4 inline mr-2" aria-hidden="true"></i>
                        Încarcă documentul în librărie
                    </button>
                </form>
            </section>
        </div>

        <!-- Modal confirmare ștergere -->
        <dialog id="modal-sterge-doc" role="dialog" aria-modal="true" aria-labelledby="modal-sterge-titlu" class="p-0 rounded-lg shadow-xl max-w-md w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-700 dark:bg-gray-800">
            <div class="p-6">
                <h2 id="modal-sterge-titlu" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Confirmare ștergere</h2>
                <p class="text-sm text-slate-700 dark:text-gray-300 mb-6">Sunteți sigur că doriți să ștergeți documentul <strong id="sterge-nume-doc" class="text-slate-900 dark:text-white"></strong>?</p>
                <form method="post" action="/librarie-documente" id="form-sterge-doc">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="sterge_document" value="1">
                    <input type="hidden" name="id" id="sterge-id" value="">
                    <div class="flex gap-2 justify-end">
                        <button type="button" onclick="document.getElementById('modal-sterge-doc').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Renunță">Renunță</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg" aria-label="Șterge documentul">Șterge</button>
                    </div>
                </form>
            </div>
        </dialog>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    var tbody = document.querySelector('.librarie-doc-row') && document.querySelector('.librarie-doc-row').closest('tbody');
    if (tbody) {
        var rows = tbody.querySelectorAll('.librarie-doc-row');
        var dragged = null;
        rows.forEach(function(row) {
            row.setAttribute('aria-label', 'Document: ' + (row.querySelector('td:nth-child(2)') ? row.querySelector('td:nth-child(2)').textContent : ''));
            row.addEventListener('dragstart', function(e) {
                dragged = row;
                e.dataTransfer.setData('text/plain', row.getAttribute('data-doc-id'));
                row.classList.add('opacity-50');
            });
            row.addEventListener('dragend', function() {
                row.classList.remove('opacity-50');
                dragged = null;
            });
            row.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (dragged && dragged !== row) row.classList.add('border-t-2', 'border-amber-500');
            });
            row.addEventListener('dragleave', function() {
                row.classList.remove('border-t-2', 'border-amber-500');
            });
            row.addEventListener('drop', function(e) {
                e.preventDefault();
                row.classList.remove('border-t-2', 'border-amber-500');
                if (!dragged || dragged === row) return;
                var next = row.nextElementSibling;
                tbody.insertBefore(dragged, next);
                salveazaOrdineLibrarie();
            });
        });
        function salveazaOrdineLibrarie() {
            var ids = [];
            tbody.querySelectorAll('.librarie-doc-row').forEach(function(r) { ids.push(r.getAttribute('data-doc-id')); });
            var formData = new FormData();
            formData.append('_csrf_token', document.querySelector('input[name="_csrf_token"]') ? document.querySelector('input[name="_csrf_token"]').value : '');
            ids.forEach(function(id, i) { formData.append('reordoneaza_ids[]', id); });
            fetch('/librarie-documente', { method: 'POST', body: formData, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(d) { if (d && d.ok) {} })
                .catch(function() {});
        }
    }
});
function deschideConfirmareStergere(id, nume) {
    var modal = document.getElementById('modal-sterge-doc');
    var idInput = document.getElementById('sterge-id');
    var numeSpan = document.getElementById('sterge-nume-doc');

    if (!modal || !idInput || !numeSpan) {
        console.error('Elemente modal lipsă');
        return;
    }

    idInput.value = id;
    numeSpan.textContent = nume;
    modal.showModal();

    // Reinițializează iconițele după deschiderea modalului
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}
</script>
</body>
</html>
