<?php
/**
 * View: Tickete — Editare ticket
 *
 * Variabile: $ticket, $departamente, $eroare, $succes
 */
$t = $ticket;
$membru_telefon = $t['membru_telefon'] ?? '';
$membru_email = $t['membru_email'] ?? '';
$membru_nume_complet = trim(($t['membru_nume'] ?? '') . ' ' . ($t['membru_prenume'] ?? ''));
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Editare Ticket #<?php echo htmlspecialchars($t['nr_inregistrare'] ?? $t['id']); ?></h1>
            <p class="text-sm text-slate-500 dark:text-gray-400 mt-0.5"><?php echo htmlspecialchars($t['titlu']); ?></p>
        </div>
        <a href="/tickete" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 transition">
            <i data-lucide="arrow-left" class="mr-2 w-4 h-4" aria-hidden="true"></i> Inapoi la lista
        </a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formular editare -->
            <div class="lg:col-span-2">
                <form method="post" action="/tickete/edit?id=<?php echo (int)$t['id']; ?>" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                    <input type="hidden" name="actualizeaza_ticket" value="1">

                    <div class="space-y-4">
                        <div>
                            <label for="edit-titlu" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Titlu <span class="text-red-600">*</span></label>
                            <input type="text" id="edit-titlu" name="titlu" required value="<?php echo htmlspecialchars($t['titlu']); ?>"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="edit-departament" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Departament</label>
                                <select id="edit-departament" name="departament" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                    <option value="">-- Selecteaza --</option>
                                    <?php foreach ($departamente as $dep): ?>
                                    <option value="<?php echo htmlspecialchars($dep['nume']); ?>" <?php echo ($t['departament'] ?? '') === $dep['nume'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['nume']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="edit-prioritate" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prioritate</label>
                                <select id="edit-prioritate" name="prioritate" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                    <?php foreach (['Urgent','Normal','Optional'] as $p): ?>
                                    <option value="<?php echo $p; ?>" <?php echo $t['prioritate'] === $p ? 'selected' : ''; ?>><?php echo $p; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="edit-tip" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip</label>
                                <select id="edit-tip" name="tip" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                    <?php foreach (['Solicitare','Sugestie','Reclamatie','Alt tip'] as $tp): ?>
                                    <option value="<?php echo $tp; ?>" <?php echo $t['tip'] === $tp ? 'selected' : ''; ?>><?php echo $tp; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="edit-status" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Status</label>
                            <select id="edit-status" name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                <?php foreach (['Nou','Deschis','In lucru','Finalizat favorabil','Finalizat respins','Irelevant'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $t['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="edit-solicitant" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume solicitant</label>
                                <input type="text" id="edit-solicitant" name="nume_solicitant" value="<?php echo htmlspecialchars($t['nume_solicitant'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                            </div>
                            <div>
                                <label for="edit-membru-search" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Membru asociat</label>
                                <div class="relative">
                                    <input type="text" id="edit-membru-search" placeholder="Cauta membru..."
                                           value="<?php echo htmlspecialchars($membru_nume_complet); ?>"
                                           autocomplete="off"
                                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                    <input type="hidden" name="membru_id" id="edit-membru-id" value="<?php echo (int)($t['membru_id'] ?? 0); ?>">
                                    <div id="edit-membru-results" class="absolute z-50 w-full bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto hidden"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="edit-note" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Note</label>
                            <textarea id="edit-note" name="note" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($t['note'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="edit-raspuns" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Raspuns final</label>
                            <textarea id="edit-raspuns" name="raspuns_final" rows="4" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($t['raspuns_final'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 transition">
                            <i data-lucide="save" class="w-4 h-4 inline mr-1" aria-hidden="true"></i> Salvare
                        </button>
                    </div>
                </form>

                <!-- Sectiune Trimite raspuns -->
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Trimite raspuns</h3>
                    <form method="post" action="/tickete/edit?id=<?php echo (int)$t['id']; ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                        <input type="hidden" name="trimite_raspuns" value="1">
                        <div class="mb-4">
                            <label for="raspuns-trimite" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Raspuns</label>
                            <textarea id="raspuns-trimite" name="raspuns_final" rows="3" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($t['raspuns_final'] ?? ''); ?></textarea>
                        </div>
                        <?php if (!empty($t['nr_inregistrare_raspuns'])): ?>
                        <p class="text-sm text-emerald-600 dark:text-emerald-400 mb-3">Nr. inregistrare raspuns: <?php echo htmlspecialchars($t['nr_inregistrare_raspuns']); ?></p>
                        <?php endif; ?>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-emerald-500 transition">
                                <i data-lucide="send" class="w-4 h-4 inline mr-1" aria-hidden="true"></i> Inregistreaza raspuns
                            </button>
                            <?php if (!empty($membru_telefon)): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $membru_telefon); ?>?text=<?php echo urlencode('Raspuns la ticketul #' . ($t['nr_inregistrare'] ?? $t['id']) . ': ' . ($t['raspuns_final'] ?? '')); ?>"
                               target="_blank" rel="noopener"
                               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-green-500 transition inline-flex items-center">
                                <i data-lucide="message-circle" class="w-4 h-4 mr-1" aria-hidden="true"></i> Trimite WhatsApp
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($membru_email)): ?>
                            <a href="mailto:<?php echo htmlspecialchars($membru_email); ?>?subject=<?php echo urlencode('Raspuns ticket #' . ($t['nr_inregistrare'] ?? $t['id'])); ?>&body=<?php echo urlencode($t['raspuns_final'] ?? ''); ?>"
                               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-blue-500 transition inline-flex items-center">
                                <i data-lucide="mail" class="w-4 h-4 mr-1" aria-hidden="true"></i> Trimite Email
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar informatii -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 space-y-4">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Informatii ticket</h3>

                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Nr. inregistrare</p>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['nr_inregistrare'] ?? '-'); ?></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Status</p>
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
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Prioritate</p>
                        <?php
                            $prio_class = match($t['prioritate']) {
                                'Urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200',
                                'Normal' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
                                'Optional' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                            };
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $prio_class; ?>"><?php echo htmlspecialchars($t['prioritate']); ?></span>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Data creare</p>
                        <p class="text-sm text-slate-900 dark:text-white"><?php echo date(DATETIME_FORMAT, strtotime($t['data_creare'])); ?></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Ultima actualizare</p>
                        <p class="text-sm text-slate-900 dark:text-white"><?php echo date(DATETIME_FORMAT, strtotime($t['data_actualizare'])); ?></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Operator</p>
                        <p class="text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['creat_de'] ?? '-'); ?></p>
                    </div>

                    <?php if (!empty($t['membru_id'])): ?>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Membru asociat</p>
                        <a href="/membru-profil?id=<?php echo (int)$t['membru_id']; ?>" class="text-sm text-amber-600 dark:text-amber-400 hover:underline">
                            <?php echo htmlspecialchars($membru_nume_complet ?: 'ID: ' . $t['membru_id']); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($membru_telefon)): ?>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Telefon membru</p>
                        <p class="text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($membru_telefon); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($membru_email)): ?>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Email membru</p>
                        <p class="text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($membru_email); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($t['nr_inregistrare_raspuns'])): ?>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400 uppercase">Nr. inregistrare raspuns</p>
                        <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400"><?php echo htmlspecialchars($t['nr_inregistrare_raspuns']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();

    // AJAX search for member in edit form
    var searchInput = document.getElementById('edit-membru-search');
    var resultsDiv = document.getElementById('edit-membru-results');
    var membruIdInput = document.getElementById('edit-membru-id');
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

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.classList.add('hidden');
            }
        });
    }
});
</script>
</body>
</html>
