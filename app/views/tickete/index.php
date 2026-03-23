<?php
/**
 * View: Tickete — Lista tickete + modal adaugare
 *
 * Variabile: $tickete, $departamente, $eroare, $succes, $eroare_bd, $filters
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Tickete</h1>
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
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lista Tickete</h2>
            <button type="button" onclick="document.getElementById('modal-adauga-ticket').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition"
                    aria-label="Adauga ticket nou" aria-haspopup="dialog" id="btn-adauga-ticket">
                <i data-lucide="plus" class="mr-2 w-5 h-5" aria-hidden="true"></i> Adauga Ticket Nou
            </button>
        </header>

        <!-- Filtre -->
        <form method="get" action="/tickete" class="mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label for="filter-search" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cauta</label>
                <input type="text" id="filter-search" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Titlu, solicitant, nr..."
                       class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
            </div>
            <div>
                <label for="filter-status" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Status</label>
                <select id="filter-status" name="status" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    <option value="">Toate</option>
                    <?php foreach (['Nou','Deschis','In lucru','Finalizat favorabil','Finalizat respins','Irelevant'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo ($filters['status'] ?? '') === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter-prioritate" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prioritate</label>
                <select id="filter-prioritate" name="prioritate" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    <option value="">Toate</option>
                    <?php foreach (['Urgent','Normal','Optional'] as $p): ?>
                    <option value="<?php echo $p; ?>" <?php echo ($filters['prioritate'] ?? '') === $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter-tip" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip</label>
                <select id="filter-tip" name="tip" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    <option value="">Toate</option>
                    <?php foreach (['Solicitare','Sugestie','Reclamatie','Alt tip'] as $tp): ?>
                    <option value="<?php echo $tp; ?>" <?php echo ($filters['tip'] ?? '') === $tp ? 'selected' : ''; ?>><?php echo $tp; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 transition">
                <i data-lucide="search" class="w-4 h-4 inline mr-1" aria-hidden="true"></i> Filtreaza
            </button>
            <?php if (!empty($filters)): ?>
            <a href="/tickete" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 transition">Reseteaza</a>
            <?php endif; ?>
        </form>

        <!-- Tabel tickete -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista tickete">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nr.</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Solicitant</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Titlu</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Prioritate</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tip</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($tickete)): ?>
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu exista tickete.</td></tr>
                        <?php else: foreach ($tickete as $t): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['nr_inregistrare'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php
                                    $solicitant = $t['nume_solicitant'] ?: '';
                                    if (!empty($t['membru_id']) && !empty($t['membru_nume'])) {
                                        $solicitant = trim($t['membru_nume'] . ' ' . ($t['membru_prenume'] ?? ''));
                                    }
                                    echo htmlspecialchars($solicitant ?: '-');
                                ?>
                            </td>
                            <td class="px-4 py-3 text-left">
                                <a href="/tickete/edit?id=<?php echo (int)$t['id']; ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded"
                                   aria-label="Editeaza ticketul <?php echo htmlspecialchars($t['titlu']); ?>">
                                    <?php echo htmlspecialchars($t['titlu']); ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-left">
                                <?php
                                    $prio_class = match($t['prioritate']) {
                                        'Urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
                                        'Normal' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
                                        'Optional' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                    };
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $prio_class; ?>"><?php echo htmlspecialchars($t['prioritate']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($t['tip']); ?></td>
                            <td class="px-4 py-3 text-left">
                                <?php
                                    $status_class = match($t['status']) {
                                        'Nou' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-200',
                                        'Deschis' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
                                        'In lucru' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
                                        'Finalizat favorabil' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
                                        'Finalizat respins' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
                                        'Irelevant' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
                                        default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                    };
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $status_class; ?>"><?php echo htmlspecialchars($t['status']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo date(DATETIME_FORMAT, strtotime($t['data_creare'])); ?></td>
                            <td class="px-4 py-3 text-left">
                                <a href="/tickete/edit?id=<?php echo (int)$t['id']; ?>" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500">
                                    <i data-lucide="edit" class="w-4 h-4" aria-hidden="true"></i> Editeaza
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal adaugare ticket -->
<dialog id="modal-adauga-ticket" role="dialog" aria-modal="true" aria-labelledby="titlu-form-ticket" aria-describedby="desc-form-ticket"
        class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-form-ticket" class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adauga Ticket Nou</h2>
        <p id="desc-form-ticket" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completati campurile pentru noul ticket.</p>
        <form method="post" action="/tickete" id="form-adauga-ticket">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_ticket" value="1">
            <div class="space-y-4">
                <div>
                    <label for="ticket-titlu" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Titlu <span class="text-red-600">*</span></label>
                    <input type="text" id="ticket-titlu" name="titlu" required value="<?php echo htmlspecialchars($_POST['titlu'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="ticket-departament" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Departament</label>
                        <select id="ticket-departament" name="departament" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                            <option value="">-- Selecteaza --</option>
                            <?php foreach ($departamente as $dep): ?>
                            <option value="<?php echo htmlspecialchars($dep['nume']); ?>" <?php echo ($_POST['departament'] ?? '') === $dep['nume'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['nume']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="ticket-prioritate" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prioritate</label>
                        <select id="ticket-prioritate" name="prioritate" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                            <option value="Normal" <?php echo ($_POST['prioritate'] ?? '') === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="Urgent" <?php echo ($_POST['prioritate'] ?? '') === 'Urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="Optional" <?php echo ($_POST['prioritate'] ?? '') === 'Optional' ? 'selected' : ''; ?>>Optional</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="ticket-tip" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip</label>
                    <select id="ticket-tip" name="tip" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        <option value="Solicitare" <?php echo ($_POST['tip'] ?? '') === 'Solicitare' ? 'selected' : ''; ?>>Solicitare</option>
                        <option value="Sugestie" <?php echo ($_POST['tip'] ?? '') === 'Sugestie' ? 'selected' : ''; ?>>Sugestie</option>
                        <option value="Reclamatie" <?php echo ($_POST['tip'] ?? '') === 'Reclamatie' ? 'selected' : ''; ?>>Reclamatie</option>
                        <option value="Alt tip" <?php echo ($_POST['tip'] ?? '') === 'Alt tip' ? 'selected' : ''; ?>>Alt tip</option>
                    </select>
                </div>
                <div>
                    <label for="ticket-membru-search" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Membru (optional)</label>
                    <div class="relative">
                        <input type="text" id="ticket-membru-search" placeholder="Cauta membru dupa nume..."
                               autocomplete="off"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        <input type="hidden" name="membru_id" id="ticket-membru-id" value="<?php echo htmlspecialchars($_POST['membru_id'] ?? ''); ?>">
                        <div id="ticket-membru-results" class="absolute z-50 w-full bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto hidden"></div>
                    </div>
                    <p id="ticket-membru-selected" class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 hidden"></p>
                </div>
                <div>
                    <label for="ticket-solicitant" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume solicitant</label>
                    <input type="text" id="ticket-solicitant" name="nume_solicitant" value="<?php echo htmlspecialchars($_POST['nume_solicitant'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div>
                    <label for="ticket-note" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Note</label>
                    <textarea id="ticket-note" name="note" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300">
                        <input type="hidden" name="creare_task" value="0">
                        <input type="checkbox" name="creare_task" value="1" class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        Creeaza task automat
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300">
                        <input type="hidden" name="notifica_utilizatori" value="0">
                        <input type="checkbox" name="notifica_utilizatori" value="1" class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        Notifica utilizatorii platformei
                    </label>
                </div>
            </div>
            <?php if (!empty($eroare) && isset($_POST['adauga_ticket'])): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 rounded text-red-800 text-sm" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
            <?php endif; ?>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('modal-adauga-ticket').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anuleaza</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salveaza</button>
            </div>
        </form>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    <?php if (!empty($eroare) && isset($_POST['adauga_ticket'])): ?>
    document.getElementById('modal-adauga-ticket').showModal();
    <?php endif; ?>

    // AJAX search for member
    var searchInput = document.getElementById('ticket-membru-search');
    var resultsDiv = document.getElementById('ticket-membru-results');
    var membruIdInput = document.getElementById('ticket-membru-id');
    var selectedP = document.getElementById('ticket-membru-selected');
    var searchTimeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.trim();
            if (query.length < 2) {
                resultsDiv.classList.add('hidden');
                resultsDiv.innerHTML = '';
                return;
            }
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                fetch('/api/cauta-membri?q=' + encodeURIComponent(query))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        resultsDiv.innerHTML = '';
                        var membri = data.membri || [];
                        if (membri.length === 0) {
                            resultsDiv.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500 dark:text-gray-400">Niciun rezultat</div>';
                            resultsDiv.classList.remove('hidden');
                            return;
                        }
                        membri.forEach(function(m) {
                            var div = document.createElement('div');
                            div.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-amber-50 dark:hover:bg-gray-700 text-slate-900 dark:text-white';
                            div.textContent = m.nume + ' ' + (m.prenume || '') + (m.domloc ? ' (' + m.domloc + ')' : '');
                            div.setAttribute('data-id', m.id);
                            div.setAttribute('data-nume', m.nume + ' ' + (m.prenume || ''));
                            div.addEventListener('click', function() {
                                membruIdInput.value = this.getAttribute('data-id');
                                searchInput.value = this.getAttribute('data-nume');
                                selectedP.textContent = 'Selectat: ' + this.getAttribute('data-nume');
                                selectedP.classList.remove('hidden');
                                resultsDiv.classList.add('hidden');
                                resultsDiv.innerHTML = '';
                            });
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.classList.remove('hidden');
                    })
                    .catch(function() {
                        resultsDiv.classList.add('hidden');
                    });
            }, 300);
        });

        // Close results on click outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.classList.add('hidden');
            }
        });
    }

    // Close modal on escape
    var modal = document.getElementById('modal-adauga-ticket');
    if (modal) {
        modal.addEventListener('keydown', function(e) { if (e.key === 'Escape') this.close(); });
    }
});
</script>
</body>
</html>
