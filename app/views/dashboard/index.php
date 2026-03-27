<?php
/**
 * View: Dashboard — Pagina principala
 *
 * Variabile disponibile (setate de controller):
 *   $eroare, $succes, $eroare_bd, $taskuri_active, $taskuri_istoric_count,
 *   $membri_cu_avertizari, $librarie_cautare, $librarie_lista,
 *   $subiecte_interactiuni_v2, $interactiuni_v2_azi,
 *   $cautare_membru, $rezultate_membri, $eroare_cautare_membri
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Dashboard</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare_bd); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Coloana stanga - Butoane rapide, Cauta membru si Librarie -->
            <aside class="lg:order-1 lg:col-span-1 flex flex-col gap-4" aria-label="Zona informatii">
                <!-- Bloc butoane actiuni rapide -->
                <nav class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 w-full" aria-label="Actiuni rapide">
                    <a href="/activitati?adauga=1&redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard'); ?>" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Creaza activitate noua">
                        <i data-lucide="calendar-plus" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Creaza activitate</span>
                    </a>
                    <a href="/liste-prezenta/adauga" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Creaza lista noua">
                        <i data-lucide="list" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Creaza lista</span>
                    </a>
                    <a href="/membri?avertizari=1" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Actualizare date membri<?php echo $membri_cu_avertizari > 0 ? '. ' . $membri_cu_avertizari . ' membri cu avertizari' : ''; ?>"
                       title="<?php echo $ci_de_actualizat > 0 || $ch_de_actualizat > 0 ? 'CI: ' . $ci_de_actualizat . ' | CH: ' . $ch_de_actualizat : ''; ?>">
                        <?php if ($membri_cu_avertizari > 0): ?>
                        <span class="absolute top-2 right-2 flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-bold text-white bg-amber-600 rounded-full z-10" aria-hidden="true"><?php echo $membri_cu_avertizari; ?></span>
                        <?php endif; ?>
                        <i data-lucide="users" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Actualizare membri</span>
                        <?php if ($ci_de_actualizat > 0 || $ch_de_actualizat > 0): ?>
                        <span class="text-[10px] text-slate-500 dark:text-gray-400 hidden lg:block">CI: <?php echo $ci_de_actualizat; ?> | CH: <?php echo $ch_de_actualizat; ?></span>
                        <?php endif; ?>
                    </a>
                    <button type="button" onclick="document.getElementById('modal-task-nou').showModal()" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Adauga task nou">
                        <i data-lucide="plus-circle" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Adauga task</span>
                    </button>
                    <a href="/registratura/adauga?redirect=dashboard" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Inregistrare document in registratura">
                        <i data-lucide="book-open" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Registratura</span>
                    </a>
                    <a href="/ajutoare-bpa" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Distributie BPA - modul Ajutoare BPA">
                        <i data-lucide="package" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Distributie BPA</span>
                    </a>
                    <button type="button" class="btn-deschide-incasari-dashboard aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                            aria-label="Incaseaza Donatie - deschide fereastra pentru incasare donatii sau cotizatii">
                        <i data-lucide="banknote" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Incaseaza Donatie</span>
                    </button>
                    <a href="/voluntariat?tab=activitati&from=dashboard" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Activitate voluntari - adauga activitate in registrul de activitati voluntari">
                        <i data-lucide="hand-heart" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Activitate voluntari</span>
                    </a>
                    <a href="/aniversari" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Aniversarile zilei<?php echo $aniversari_azi_count > 0 ? '. ' . $aniversari_azi_count . ' aniversari azi' : ''; ?>">
                        <?php if ($aniversari_azi_count > 0): ?>
                        <span class="absolute top-2 right-2 flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-bold text-white bg-amber-600 rounded-full z-10" aria-hidden="true"><?php echo $aniversari_azi_count; ?></span>
                        <?php endif; ?>
                        <i data-lucide="cake" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Aniversarile zilei</span>
                    </a>
                    <a href="/tickete" class="aspect-square flex flex-col items-center justify-center bg-white dark:bg-gray-800 border-2 border-slate-200 dark:border-gray-600 rounded-xl shadow-sm hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition relative"
                       aria-label="Tickete<?php echo $tickete_deschise_count > 0 ? '. ' . $tickete_deschise_count . ' tickete deschise' : ''; ?>">
                        <?php if ($tickete_deschise_count > 0): ?>
                        <span class="absolute top-2 right-2 flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full z-10" aria-hidden="true"><?php echo $tickete_deschise_count; ?></span>
                        <?php endif; ?>
                        <i data-lucide="ticket" class="w-10 h-10 text-amber-600 dark:text-amber-400 mb-1" aria-hidden="true"></i>
                        <span class="text-xs font-medium text-slate-800 dark:text-gray-200 text-center px-1">Tickete</span>
                    </a>
                </nav>
                <!-- Bloc Cauta membru -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 lg:hidden">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Cauta Membru</h2>
                    <form method="get" action="/dashboard" id="form-cautare-membru" class="mb-4">
                        <div class="relative">
                            <input type="search"
                                   name="cautare_membru"
                                   id="cautare_membru"
                                   value="<?php echo htmlspecialchars($cautare_membru); ?>"
                                   placeholder="Nume, prenume sau CNP..."
                                   class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   autocomplete="off"
                                   aria-label="Cauta membru">
                            <button type="submit"
                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400"
                                    aria-label="Cauta">
                                <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                            </button>
                            <div id="cautare-membru-live" class="absolute left-0 right-0 top-full mt-1 z-50 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-lg shadow-lg max-h-72 overflow-y-auto hidden" role="listbox" aria-label="Rezultate cautare membru"></div>
                        </div>
                    </form>
                    <div id="rezultate-cautare" class="space-y-2 max-h-64 overflow-y-auto">
                        <?php if (!empty($cautare_membru)): ?>
                            <?php if (!empty($eroare_cautare_membri)): ?>
                            <p class="text-sm text-red-600 dark:text-red-400"><?php echo htmlspecialchars($eroare_cautare_membri); ?></p>
                            <?php elseif (empty($rezultate_membri)): ?>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Nu s-au gasit membri.</p>
                            <?php else: ?>
                                <?php foreach ($rezultate_membri as $m): ?>
                                <a href="/membru-profil?id=<?php echo $m['id']; ?>"
                                   class="block p-2 rounded hover:bg-slate-100 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500 transition">
                                    <div class="font-medium text-slate-900 dark:text-white text-sm">
                                        <?php echo htmlspecialchars(trim($m['nume'] . ' ' . $m['prenume'])); ?>
                                    </div>
                                    <?php if ($m['dosarnr']): ?>
                                    <div class="text-xs text-slate-600 dark:text-gray-400">Dosar: <?php echo htmlspecialchars($m['dosarnr']); ?></div>
                                    <?php endif; ?>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <!-- Coloana mijloc - Taskuri -->
            <section class="order-2 lg:order-2 lg:col-span-1 flex flex-col gap-4 min-w-0 overflow-hidden" aria-labelledby="titlu-todo">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                    <header class="flex flex-wrap justify-between items-center gap-4 p-6 pb-4">
                        <h2 id="titlu-todo" class="text-lg font-semibold text-slate-900 dark:text-white">
                            <a href="/todo" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">Taskuri</a>
                        </h2>
                        <button type="button" onclick="document.getElementById('modal-task-nou').showModal()"
                           class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white dark:text-white bg-orange-500 dark:bg-orange-600 border border-orange-600 dark:border-orange-700 rounded-lg hover:bg-orange-600 dark:hover:bg-orange-700 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition"
                           aria-label="Adauga task nou">
                            <i data-lucide="plus" class="w-4 h-4" aria-hidden="true"></i>
                            <span>Task Nou</span>
                        </button>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Taskuri active" id="todo-table-dashboard">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-2 text-left"><span class="sr-only">Finalizat</span></th>
                                    <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">
                                        <button type="button" class="sort-header-btn text-left hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" data-column="1" data-order="asc" aria-label="Sorteaza dupa nume">
                                            Nume
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">
                                        <button type="button" class="sort-header-btn text-left hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" data-column="2" data-order="asc" aria-label="Sorteaza dupa data si ora">
                                            Data si ora
                                        </button>
                                    </th>
                                    <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">
                                        <button type="button" class="sort-header-btn text-right hover:text-amber-600 dark:hover:text-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 rounded" data-column="3" data-order="asc" aria-label="Sorteaza dupa urgenta">
                                            Urgenta
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($taskuri_active)): ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu exista taskuri active. <button type="button" onclick="document.getElementById('modal-task-nou').showModal()" class="text-amber-600 dark:text-amber-400 hover:underline">Adauga un task</button></td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($taskuri_active as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <form method="post" action="/dashboard" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="finalizeaza_task" value="1">
                                            <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="marca_finalizat" onchange="(this.form.requestSubmit && this.form.requestSubmit()) || this.form.submit()"
                                                       class="w-5 h-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                                       aria-label="Marcheaza taskul <?php echo htmlspecialchars($t['nume']); ?> ca finalizat">
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button"
                                                onclick='deschideEditare(<?php echo json_encode((int)$t['id']); ?>, <?php echo json_encode($t['nume']); ?>, <?php echo json_encode(date('Y-m-d', strtotime($t['data_ora']))); ?>, <?php echo json_encode(date('H:i', strtotime($t['data_ora']))); ?>, <?php echo json_encode($t['detalii'] ?? ''); ?>, <?php echo json_encode($t['nivel_urgenta']); ?>)'
                                                class="text-left font-medium text-amber-600 dark:text-amber-400 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 rounded"
                                                aria-label="Vezi detaliile taskului <?php echo htmlspecialchars($t['nume']); ?>">
                                            <?php echo htmlspecialchars($t['nume']); ?>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo date(DATETIME_FORMAT, strtotime($t['data_ora'])); ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <?php
                                        $badge = ['normal' => 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200', 'important' => 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100', 'reprogramat' => 'bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100'][$t['nivel_urgenta']] ?? 'bg-slate-200 dark:bg-slate-600';
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($t['nivel_urgenta'])); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Coloana dreapta - Registru Interactiuni si Interactiuni zilnice -->
            <aside class="order-3 lg:order-3 lg:col-span-1 flex flex-col gap-4" aria-label="Registru Interactiuni">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        <a href="/registru-interactiuni" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">Registru Interactiuni</a>
                    </h2>
                    <form method="post" action="/dashboard" id="form-interactiuni-v2">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="adauga_interactiune_v2" value="1">
                        <input type="hidden" name="tip_interactiune_v2" id="tip-interact-v2-input" value="apel">
                        <div class="flex gap-2 mb-4">
                            <button type="button" id="btn-apel-v2" onclick="setTipInteractiuneV2('apel')" aria-pressed="true" aria-label="Apel telefonic"
                                    class="flex-1 px-4 py-3 rounded-lg font-medium border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200">
                                <i data-lucide="phone" class="w-5 h-5 inline-block mr-1" aria-hidden="true"></i> Apel Telefonic
                            </button>
                            <button type="button" id="btn-vizita-v2" onclick="setTipInteractiuneV2('vizita')" aria-pressed="false" aria-label="Vizita sediu"
                                    class="flex-1 px-4 py-3 rounded-lg font-medium border-2 border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:border-amber-500">
                                <i data-lucide="building" class="w-5 h-5 inline-block mr-1" aria-hidden="true"></i> Vizita Sediu
                            </button>
                        </div>
                        <div class="mb-3">
                            <div class="flex items-end gap-2">
                                <div class="flex-1 relative">
                                    <label for="interact-persoana-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Persoana <span class="text-xs text-slate-500 dark:text-gray-400">(optional)</span></label>
                                    <input type="text" id="interact-persoana-v2" name="persoana_v2"
                                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                           placeholder="Cauta membru sau scrie un nume..."
                                           autocomplete="off">
                                    <div id="persoana-autocomplete-list" class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden" role="listbox" aria-label="Rezultate cautare membri"></div>
                                </div>
                                <div class="flex-1">
                                    <label for="interact-telefon-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr. telefon <span class="text-xs text-slate-500 dark:text-gray-400">(optional)</span></label>
                                    <input type="tel" id="interact-telefon-v2" name="telefon_v2"
                                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                           placeholder="07xx xxx xxx (optional)"
                                           aria-describedby="interact-telefon-v2-desc">
                                    <span id="interact-telefon-v2-desc" class="sr-only">Camp optional; nu este obligatoriu.</span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="interact-subiect-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Subiectul discutat <span class="text-red-600" aria-hidden="true">*</span></label>
                            <select id="interact-subiect-v2" name="subiect_id_v2" aria-label="Selecteaza subiectul interactiunii"
                                    class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                                <option value="">-- Selectati --</option>
                                <?php foreach ($subiecte_interactiuni_v2 as $s): ?>
                                <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['nume']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="interact-subiect-alt-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Alt subiect</label>
                            <input type="text" name="subiect_alt_v2" id="interact-subiect-alt-v2"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   placeholder="Specificati alt subiect daca nu e in lista">
                        </div>
                        <div class="mb-3">
                            <label for="interact-informatii-suplimentare-v2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Informatii suplimentare</label>
                            <textarea id="interact-informatii-suplimentare-v2" name="informatii_suplimentare_v2" rows="3"
                                      class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                      placeholder="Detalii despre interactiune..."></textarea>
                        </div>
                        <div class="mb-4">
                            <button type="button" id="btn-task-activ-v2" onclick="toggleTaskActivV2()"
                                    class="w-full px-4 py-2 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center gap-2 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400"
                                    aria-pressed="false" aria-label="Creaza task in Taskuri">
                                <input type="checkbox" name="task_activ_v2" value="1" id="task-activ-v2" class="sr-only" aria-hidden="true">
                                <i data-lucide="check-square" id="icon-task-v2" class="w-5 h-5 text-slate-400 dark:text-gray-500" aria-hidden="true"></i>
                                <span id="text-task-v2" class="text-slate-700 dark:text-gray-300">Creaza task in Taskuri</span>
                            </button>
                            <p class="text-xs text-slate-600 dark:text-gray-400 mt-1 text-center">Apasa butonul pentru a crea automat un task in modulul Taskuri.</p>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salveaza interactiunea">Salveaza</button>
                    </form>
                </div>
                <!-- Bloc afisare interactiuni zilnice -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Interactiuni zilnice</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="counter-apel-v2-azi" aria-live="polite" aria-label="Numar apeluri telefonice azi"><?php echo $interactiuni_v2_azi['apel']; ?></p>
                            <p class="text-xs text-slate-600 dark:text-gray-400">Apeluri telefonice azi</p>
                        </div>
                        <div class="text-center p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" id="counter-vizita-v2-azi" aria-live="polite" aria-label="Numar vizite la sediu azi"><?php echo $interactiuni_v2_azi['vizita']; ?></p>
                            <p class="text-xs text-slate-600 dark:text-gray-400">Vizite la sediu azi</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Mobile-first: Librarie documente dupa Registru Interactiuni -->
            <section class="order-4 lg:order-1 lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">
                        <a href="/librarie-documente" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">Librarie documente</a>
                    </h2>
                    <form method="get" action="/dashboard" id="form-cautare-librarie" class="mb-4">
                        <input type="hidden" name="cautare_membru" value="<?php echo htmlspecialchars($cautare_membru); ?>">
                        <div class="relative">
                            <input type="search" name="cautare_librarie" id="cautare_librarie"
                                   value="<?php echo htmlspecialchars($librarie_cautare); ?>"
                                   placeholder="Cauta document in librarie..."
                                   class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                   aria-label="Cauta document in librarie">
                            <button type="submit" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-500 dark:text-gray-400 hover:text-amber-600" aria-label="Cauta">
                                <i data-lucide="search" class="w-5 h-5" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php if (empty($librarie_lista)): ?>
                        <p class="text-sm text-slate-600 dark:text-gray-400"><?php echo $librarie_cautare !== '' ? 'Nu s-au gasit documente.' : 'Niciun document incarcat. <a href="/librarie-documente" class="text-amber-600 dark:text-amber-400 hover:underline">Librarie documente</a>'; ?></p>
                        <?php else: ?>
                        <?php foreach ($librarie_lista as $ld): ?>
                        <div class="flex items-center justify-between gap-2 p-2 rounded hover:bg-slate-50 dark:hover:bg-gray-700 border-b border-slate-100 dark:border-gray-600 last:border-0">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-slate-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($ld['institutie']); ?></p>
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($ld['nume_document']); ?></p>
                            </div>
                            <span class="flex items-center gap-2 flex-shrink-0">
                                <a href="util/descarca-librarie-document.php?id=<?php echo (int)$ld['id']; ?>&amp;print=1" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-slate-500 transition"
                                   aria-label="Print <?php echo htmlspecialchars($ld['nume_document']); ?>">
                                    <i data-lucide="printer" class="w-4 h-4" aria-hidden="true"></i>
                                    <span>Print</span>
                                </a>
                                <a href="util/descarca-librarie-document.php?id=<?php echo (int)$ld['id']; ?>"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-blue-500 transition"
                                   aria-label="Descarca <?php echo htmlspecialchars($ld['nume_document']); ?>">
                                    <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                                    <span>Descarca</span>
                                </a>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

        </div>
    </div>
</main>

<!-- Modal editare task -->
<dialog id="detalii-task" role="dialog" aria-modal="true" aria-labelledby="titlu-detalii" class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-detalii" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Editeaza task</h2>
        <form method="post" action="/todo" id="form-edit-task">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="actualizeaza_task" value="1">
            <input type="hidden" name="task_id" id="edit-task-id" value="">
            <input type="hidden" name="redirect_after" id="edit-redirect" value="/dashboard">
            <div class="space-y-4">
                <div>
                    <label for="edit-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="edit-nume" name="nume" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" id="edit-data" name="data" required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="edit-ora" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora</label>
                        <input type="time" id="edit-ora" name="ora"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-describedby="edit-ora-desc">
                        <span id="edit-ora-desc" class="sr-only">Format 24 de ore</span>
                    </div>
                </div>
                <div>
                    <label for="edit-detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="edit-detalii" name="detalii" rows="3"
                              class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></textarea>
                </div>
                <div>
                    <label for="edit-urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgenta</label>
                    <select id="edit-urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Selecteaza nivelul de urgenta">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="reprogramat">Reprogramat</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-renunta-task" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Renunta (Esc)">Renunta</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salveaza (Enter)">Salveaza</button>
            </div>
        </form>
    </div>
</dialog>

<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
function setTipInteractiuneV2(tip) {
    document.getElementById('tip-interact-v2-input').value = tip;
    var btnApel = document.getElementById('btn-apel-v2');
    var btnVizita = document.getElementById('btn-vizita-v2');
    if (tip === 'apel') {
        btnApel.setAttribute('aria-pressed', 'true');
        btnApel.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200';
        btnVizita.setAttribute('aria-pressed', 'false');
        btnVizita.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:border-amber-500';
    } else {
        btnVizita.setAttribute('aria-pressed', 'true');
        btnVizita.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-amber-500 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200';
        btnApel.setAttribute('aria-pressed', 'false');
        btnApel.className = 'flex-1 px-4 py-3 rounded-lg font-medium border-2 border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:border-amber-500';
    }
}
// Toggle buton task activ v2
function toggleTaskActivV2() {
    var checkbox = document.getElementById('task-activ-v2');
    var btn = document.getElementById('btn-task-activ-v2');
    var icon = document.getElementById('icon-task-v2');
    var textSpan = document.getElementById('text-task-v2');
    var isChecked = checkbox.checked;

    checkbox.checked = !isChecked;
    var newChecked = checkbox.checked;

    if (newChecked) {
        btn.setAttribute('aria-pressed', 'true');
        btn.className = 'w-full px-4 py-2 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white';
        if (typeof lucide !== 'undefined') {
            icon.setAttribute('data-lucide', 'check-square');
            lucide.createIcons();
        }
        icon.className = 'w-5 h-5 text-white';
        if (textSpan) textSpan.className = 'text-white font-semibold';
    } else {
        btn.setAttribute('aria-pressed', 'false');
        btn.className = 'w-full px-4 py-2 rounded-lg font-medium transition focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex items-center justify-center gap-2 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400';
        if (typeof lucide !== 'undefined') {
            icon.setAttribute('data-lucide', 'check-square');
            lucide.createIcons();
        }
        icon.className = 'w-5 h-5 text-slate-400 dark:text-gray-500';
        if (textSpan) textSpan.className = 'text-slate-700 dark:text-gray-300';
    }
}

// Actualizare automata contoare Registru Interactiuni v2
(function() {
    function actualizeazaContoareV2() {
        fetch('/api/registru-v2-stats')
            .then(response => response.json())
            .then(data => {
                if (data.apel !== undefined) {
                    var el = document.getElementById('counter-apel-v2-azi');
                    if (el) el.textContent = data.apel;
                }
                if (data.vizita !== undefined) {
                    var el = document.getElementById('counter-vizita-v2-azi');
                    if (el) el.textContent = data.vizita;
                }
            })
            .catch(error => {
                console.error('Eroare actualizare statistici v2:', error);
            });
    }
    // Actualizeaza la fiecare 30 secunde
    setInterval(actualizeazaContoareV2, 30000);
})();
// AJAX autocomplete pentru campul Persoana
(function() {
    var inputPersoana = document.getElementById('interact-persoana-v2');
    var listaAutocomplete = document.getElementById('persoana-autocomplete-list');
    var timerAutocomplete = null;

    if (!inputPersoana || !listaAutocomplete) return;

    inputPersoana.addEventListener('input', function() {
        var val = this.value.trim();
        clearTimeout(timerAutocomplete);
        if (val.length < 2) {
            listaAutocomplete.innerHTML = '';
            listaAutocomplete.classList.add('hidden');
            return;
        }
        timerAutocomplete = setTimeout(function() {
            fetch('/api/cauta-membri?q=' + encodeURIComponent(val))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    listaAutocomplete.innerHTML = '';
                    if (!data.membri || data.membri.length === 0) {
                        listaAutocomplete.classList.add('hidden');
                        return;
                    }
                    data.membri.forEach(function(m) {
                        var numeFull = (m.nume || '') + ' ' + (m.prenume || '');
                        numeFull = numeFull.trim();
                        var div = document.createElement('div');
                        div.className = 'px-3 py-2 cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 text-sm text-slate-900 dark:text-white border-b border-slate-100 dark:border-gray-700 last:border-0';
                        div.setAttribute('role', 'option');
                        div.textContent = numeFull;
                        if (m.domloc) {
                            var span = document.createElement('span');
                            span.className = 'text-xs text-slate-500 dark:text-gray-400 ml-2';
                            span.textContent = '(' + m.domloc + ')';
                            div.appendChild(span);
                        }
                        div.addEventListener('click', function() {
                            inputPersoana.value = numeFull;
                            listaAutocomplete.innerHTML = '';
                            listaAutocomplete.classList.add('hidden');
                        });
                        listaAutocomplete.appendChild(div);
                    });
                    listaAutocomplete.classList.remove('hidden');
                })
                .catch(function() {
                    listaAutocomplete.classList.add('hidden');
                });
        }, 300);
    });

    // Inchide lista la click in afara
    document.addEventListener('click', function(e) {
        if (!inputPersoana.contains(e.target) && !listaAutocomplete.contains(e.target)) {
            listaAutocomplete.classList.add('hidden');
        }
    });

    // Inchide lista la Escape
    inputPersoana.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            listaAutocomplete.classList.add('hidden');
        }
    });
})();

// Validare formular: subiect sau alt subiect obligatoriu
(function() {
    var form = document.getElementById('form-interactiuni-v2');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        var subiectId = form.querySelector('[name="subiect_id_v2"]').value;
        var subiectAlt = form.querySelector('[name="subiect_alt_v2"]').value.trim();
        if (!subiectId && !subiectAlt) {
            e.preventDefault();
            alert('Selectati un subiect din lista sau completati campul "Alt subiect".');
            form.querySelector('[name="subiect_id_v2"]').focus();
        }
    });
})();

// Campul "Alt subiect" este afisat permanent; nu mai e nevoie de toggle.
function deschideEditare(id, nume, data, ora, detalii, urgenta) {
    document.getElementById('edit-task-id').value = id;
    document.getElementById('edit-nume').value = nume || '';
    document.getElementById('edit-data').value = data || '';
    document.getElementById('edit-ora').value = ora || '09:00';
    document.getElementById('edit-detalii').value = detalii || '';
    document.getElementById('edit-urgenta').value = urgenta || 'normal';
    document.getElementById('edit-redirect').value = '/dashboard';
    document.getElementById('detalii-task').showModal();
    document.getElementById('edit-nume').focus();
}
var dlgTask = document.getElementById('detalii-task');
if (dlgTask) {
    dlgTask.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { this.close(); }
    });
    document.getElementById('btn-renunta-task')?.addEventListener('click', function() { dlgTask.close(); });
}

// Sortare tabel Taskuri
(function() {
    const table = document.getElementById('todo-table-dashboard');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    document.querySelectorAll('.sort-header-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const column = parseInt(this.getAttribute('data-column'));
            let order = this.getAttribute('data-order'); // 'asc' sau 'desc'

            // Toggle order: daca e 'asc', devine 'desc', altfel devine 'asc'
            order = order === 'asc' ? 'desc' : 'asc';
            this.setAttribute('data-order', order);

            // Reseteaza ordinea pentru celelalte coloane
            document.querySelectorAll('.sort-header-btn').forEach(function(otherBtn) {
                if (otherBtn !== btn) {
                    otherBtn.setAttribute('data-order', 'asc');
                }
            });

            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort(function(a, b) {
                let aVal, bVal;

                if (column === 1) {
                    // Coloana Nume
                    aVal = a.cells[1].textContent.trim().toLowerCase();
                    bVal = b.cells[1].textContent.trim().toLowerCase();
                } else if (column === 2) {
                    // Coloana Data si ora
                    aVal = a.cells[2].textContent.trim();
                    bVal = b.cells[2].textContent.trim();
                } else if (column === 3) {
                    // Coloana Urgenta
                    aVal = a.cells[3].textContent.trim().toLowerCase();
                    bVal = b.cells[3].textContent.trim().toLowerCase();
                }

                if (order === 'asc') {
                    return aVal > bVal ? 1 : (aVal < bVal ? -1 : 0);
                } else {
                    return aVal < bVal ? 1 : (aVal > bVal ? -1 : 0);
                }
            });

            // Reordoneaza randurile
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });

            // Reinitializeaza iconitele Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    });
})();

// AJAX live search pentru Cauta Membru
(function() {
    var inputCautare = document.getElementById('cautare_membru');
    var listaLive = document.getElementById('cautare-membru-live');
    var timerCautare = null;
    if (!inputCautare || !listaLive) return;

    inputCautare.addEventListener('input', function() {
        var val = this.value.trim();
        clearTimeout(timerCautare);
        if (val.length < 2) { listaLive.innerHTML = ''; listaLive.classList.add('hidden'); return; }
        timerCautare = setTimeout(function() {
            fetch('/api/cauta-membri?q=' + encodeURIComponent(val))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    listaLive.innerHTML = '';
                    if (!data.membri || data.membri.length === 0) {
                        listaLive.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500 dark:text-gray-400">Nu s-au gasit membri.</div>';
                        listaLive.classList.remove('hidden');
                        return;
                    }
                    data.membri.forEach(function(m) {
                        var a = document.createElement('a');
                        a.href = '/membru-profil?id=' + m.id;
                        a.className = 'block px-3 py-2 cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-900/20 border-b border-slate-100 dark:border-gray-700 last:border-0 transition';
                        var numeDiv = document.createElement('div');
                        numeDiv.className = 'font-medium text-sm text-slate-900 dark:text-white';
                        numeDiv.textContent = ((m.nume || '') + ' ' + (m.prenume || '')).trim();
                        a.appendChild(numeDiv);
                        var parts = [];
                        if (m.dosarnr) parts.push('Dosar: ' + m.dosarnr);
                        if (m.domloc) parts.push(m.domloc);
                        if (parts.length) {
                            var d = document.createElement('div');
                            d.className = 'text-xs text-slate-500 dark:text-gray-400';
                            d.textContent = parts.join(' \u2022 ');
                            a.appendChild(d);
                        }
                        listaLive.appendChild(a);
                    });
                    listaLive.classList.remove('hidden');
                }).catch(function() { listaLive.classList.add('hidden'); });
        }, 300);
    });
    document.addEventListener('click', function(e) {
        if (!inputCautare.contains(e.target) && !listaLive.contains(e.target)) listaLive.classList.add('hidden');
    });
    inputCautare.addEventListener('keydown', function(e) { if (e.key === 'Escape') listaLive.classList.add('hidden'); });
})();
</script>
<?php require_once APP_ROOT . '/includes/incasari_dashboard_modal.php'; ?>

<!-- Modal Task Nou -->
<dialog id="modal-task-nou" class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30" aria-labelledby="modal-task-titlu" aria-modal="true">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 id="modal-task-titlu" class="text-lg font-bold text-slate-900 dark:text-white">Task Nou</h2>
            <button type="button" onclick="document.getElementById('modal-task-nou').close()" class="text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200" aria-label="Inchide">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
        <form method="post" action="/dashboard" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_task_rapid" value="1">
            <div>
                <label for="task_nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600">*</span></label>
                <input type="text" id="task_nume" name="task_nume" required
                       class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                       placeholder="Ex: Suna membrul X pentru certificat">
            </div>
            <div>
                <label for="task_data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data / Termen</label>
                <input type="datetime-local" id="task_data" name="task_data"
                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
            </div>
            <div>
                <label for="task_detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                <textarea id="task_detalii" name="task_detalii" rows="3"
                          class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                          placeholder="Detalii suplimentare (optional)"></textarea>
            </div>
            <div>
                <label for="task_urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Urgenta</label>
                <select id="task_urgenta" name="task_urgenta"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    <option value="normal">Normal</option>
                    <option value="important">Important</option>
                </select>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">Salveaza task</button>
                <button type="button" onclick="document.getElementById('modal-task-nou').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</button>
            </div>
        </form>
    </div>
</dialog>
</body>
</html>
