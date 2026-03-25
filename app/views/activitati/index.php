<?php
/**
 * View: Activitati — Calendar + liste prezenta + modal adaugare
 *
 * Variabile: $activitati, $eroare, $eroare_bd, $afiseaza_tot, $ziua_curenta,
 *            $liste_prezenta, $luni_ro, $utilizatori_platforma, $deschide_formular
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Calendar activități</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare_bd)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert">
                <?php echo htmlspecialchars($eroare_bd); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['succes'])): ?>
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="alert">
                Activitate adăugată cu succes.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['succes_status'])): ?>
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="alert">
                Status actualizat cu succes.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['succes_lista'])): ?>
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="alert">
                Lista de prezență a fost creată cu succes.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['succes_lista_stearsa'])): ?>
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="alert">
                Lista de prezență a fost ștearsă cu succes.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['succes_activitate_stearsa'])): ?>
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="alert">
                Activitatea a fost ștearsă cu succes.
            </div>
        <?php endif; ?>
        <?php if (!empty($eroare)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert">
                <?php echo htmlspecialchars($eroare); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Coloana stânga: Calendar listă -->
            <section class="flex flex-col gap-4" aria-labelledby="titlu-calendar">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <h2 id="titlu-calendar" class="text-lg font-semibold text-slate-900 dark:text-white">
                        <?php echo $afiseaza_tot ? 'Tot calendarul' : 'Activități Programate'; ?> — <?php echo date(DATE_FORMAT); ?>
                    </h2>
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="/activitati<?php echo $afiseaza_tot ? '' : '?afiseaza_tot=1'; ?>"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium <?php echo $afiseaza_tot ? 'bg-amber-600 text-white' : 'border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-600'; ?>">
                            <i data-lucide="calendar" class="w-5 h-5" aria-hidden="true"></i>
                            <?php echo $afiseaza_tot ? 'Afișează doar viitor' : 'Afișează tot calendarul'; ?>
                        </a>
                        <a href="/activitati/istoric"
                           class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-600 font-medium focus:ring-2 focus:ring-amber-500"
                           aria-label="Istoric activități trecute">
                            <i data-lucide="history" class="w-5 h-5" aria-hidden="true"></i>
                            Istoric activități
                        </a>
                        <button type="button"
                                onclick="document.getElementById('formular-activitate').showModal(); document.getElementById('formular-activitate').querySelector('form').action = '/activitati?redirect=' + encodeURIComponent(window.location.href);"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition"
                                aria-label="Adaugă activitate nouă"
                                aria-haspopup="dialog">
                            <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>
                            Adaugă activitate
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista activităților">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Ora</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume activitate</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Locație</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Responsabil</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Editare</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($activitati)): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">
                                        Nu există activități în perioada afișată.
                                    </td>
                                </tr>
                                <?php else:
                                    $luni_ro = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
                                    $ultima_luna = null;
                                    $ultima_saptamana = null;
                                    foreach ($activitati as $a):
                                    $dt = new DateTime($a['data_ora']);
                                    $este_azi = ($dt->format('Y-m-d') === $ziua_curenta);
                                    $id_original = $a['id'] ?? null;
                                    $luna_act = $dt->format('Y-m');
                                    $an_act = (int)$dt->format('Y');
                                    $nr_sapt = (int)$dt->format('W');
                                    $sapt_key = $dt->format('o') . '-W' . $nr_sapt;
                                    if ($luna_act !== $ultima_luna):
                                        $ultima_luna = $luna_act;
                                        $ultima_saptamana = null;
                                        $nume_luna = $luni_ro[(int)$dt->format('n') - 1] . ' ' . $dt->format('Y');
                                ?>
                                <tr class="bg-slate-200 dark:bg-gray-600">
                                    <td colspan="6" class="px-4 py-2 text-left font-bold text-slate-900 dark:text-white"><?php echo $nume_luna; ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($sapt_key !== $ultima_saptamana):
                                        $ultima_saptamana = $sapt_key;
                                        $start_week = (clone $dt)->setISODate($an_act, $nr_sapt, 1);
                                        $end_week = (clone $dt)->setISODate($an_act, $nr_sapt, 7);
                                        $nume_luna_sapt = $luni_ro[(int)$start_week->format('n') - 1];
                                ?>
                                <tr class="border-b border-slate-200 dark:border-gray-600">
                                    <td colspan="6" class="px-4 py-1.5 text-sm text-slate-600 dark:text-gray-400 text-left"><?php echo $nume_luna_sapt; ?> – Săptămâna [<?php echo $start_week->format('d'); ?> - <?php echo $end_week->format('d'); ?> <?php echo $luni_ro[(int)$end_week->format('n') - 1]; ?>]</td>
                                </tr>
                                <?php endif; ?>
                                <tr class="<?php echo $este_azi ? 'bg-amber-50 dark:bg-amber-900/20' : ''; ?>">
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                        <?php echo data_cu_ziua_ro($dt); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                        <?php 
                                        $ora_afisare = $dt->format(TIME_FORMAT);
                                        if (!empty($a['ora_finalizare'])) {
                                            $ora_fin = is_object($a['ora_finalizare']) ? $a['ora_finalizare'] : new DateTime($a['ora_finalizare']);
                                            if ($ora_fin instanceof DateTime) {
                                                $ora_afisare .= '-' . $ora_fin->format(TIME_FORMAT);
                                            }
                                        }
                                        echo $ora_afisare;
                                        ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($a['nume']); ?></span>
                                        <?php if (!empty($a['recurenta'])): ?>
                                            <span class="ml-1 text-xs text-slate-500">(<?php echo htmlspecialchars($a['recurenta']); ?>)</span>
                                        <?php endif; ?>
                                        <?php if ($a['status'] !== 'Planificata'): ?>
                                            <span class="ml-2 inline-flex px-2 py-0.5 text-xs rounded <?php 
                                                echo $a['status'] === 'Finalizata' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200' : 
                                                    ($a['status'] === 'Reprogramata' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200' : 'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-400'); 
                                            ?>"><?php echo htmlspecialchars($a['status']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($id_original && $a['status'] === 'Planificata'): ?>
                                        <div id="dropdown-status-<?php echo $id_original; ?>-<?php echo $dt->format('Ymd'); ?>" class="mt-2 hidden" role="menu">
                                            <form method="post" action="/activitati<?php echo $afiseaza_tot ? '?afiseaza_tot=1' : ''; ?>" class="flex items-center gap-2">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="actualizeaza_status" value="1">
                                                <input type="hidden" name="id" value="<?php echo $id_original; ?>">
                                                <label for="status-<?php echo $id_original; ?>" class="sr-only">Selectează status</label>
                                                <select name="status" class="px-3 py-1.5 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-amber-500"
                                                        onchange="this.form.submit()" aria-label="Schimbă status activitate">
                                                    <option value="">— Status —</option>
                                                    <option value="Finalizata">Finalizată</option>
                                                    <option value="Reprogramata">Reprogramată</option>
                                                    <option value="Anulata">Anulată</option>
                                                </select>
                                            </form>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars($a['locatie'] ?: '—'); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars($a['responsabili'] ?: '—'); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($id_original && $a['status'] === 'Planificata'): ?>
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    onclick="document.getElementById('dropdown-status-<?php echo $id_original; ?>-<?php echo $dt->format('Ymd'); ?>').classList.toggle('hidden')"
                                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-amber-400 dark:border-amber-500 bg-amber-100 dark:bg-amber-800/70 text-amber-900 dark:text-amber-100 hover:bg-amber-200 dark:hover:bg-amber-700 font-medium text-sm"
                                                    aria-label="Editează status activitate: <?php echo htmlspecialchars($a['nume']); ?>">
                                                <i data-lucide="edit" class="w-4 h-4" aria-hidden="true"></i>
                                                Editare
                                            </button>
                                            <form method="post" action="/activitati<?php echo $afiseaza_tot ? '?afiseaza_tot=1' : ''; ?>" onsubmit="return confirm('Sigur doriți să ștergeți activitatea <?php echo htmlspecialchars($a['nume']); ?>?');" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="sterge_activitate" value="1">
                                                <input type="hidden" name="activitate_id" value="<?php echo (int)$id_original; ?>">
                                                <?php if ($afiseaza_tot): ?>
                                                <input type="hidden" name="afiseaza_tot" value="1">
                                                <?php endif; ?>
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-500 bg-red-600 text-white hover:bg-red-700 font-medium text-sm" aria-label="Șterge activitatea: <?php echo htmlspecialchars($a['nume']); ?>">
                                                    <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                                                    Șterge
                                                </button>
                                            </form>
                                        </div>
                                        <?php elseif (!empty($a['lista_prezenta_id'])): ?>
                                        <a href="/liste-prezenta/edit?id=<?php echo $a['lista_prezenta_id']; ?>" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-400 bg-blue-100 dark:bg-blue-800/70 text-blue-900 dark:text-blue-100 hover:bg-blue-200 text-sm">
                                            <i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i> Listă
                                        </a>
                                        <?php else: ?>
                                        <span class="text-slate-400 dark:text-gray-500 text-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Coloana dreapta: Liste prezență + Rezumat -->
            <aside class="flex flex-col gap-4" aria-label="Liste prezență și rezumat">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Liste de prezență și tabele nominale</h2>
                    <button type="button"
                            onclick="window.location.href='/liste-prezenta/adauga'"
                            class="w-full mb-4 inline-flex items-center justify-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                        <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>
                        Creare listă nouă
                    </button>
                    <button type="button"
                            onclick="window.location.href='/liste-prezenta/adauga?preset=socializare'"
                            class="w-full mb-4 inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-green-500"
                            aria-label="Creează listă prezență socializare">
                        <i data-lucide="users" class="w-5 h-5" aria-hidden="true"></i>
                        Listă prezență socializare
                    </button>
                    <?php if (!empty($liste_prezenta)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm" role="table">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-gray-600">
                                    <th scope="col" class="text-left py-2 text-slate-800 dark:text-gray-200">ID</th>
                                    <th scope="col" class="text-left py-2 text-slate-800 dark:text-gray-200">Listă</th>
                                    <th scope="col" class="text-left py-2 text-slate-800 dark:text-gray-200">Data</th>
                                    <th scope="col" class="text-left py-2 text-slate-800 dark:text-gray-200">Utilizator</th>
                                    <th scope="col" class="text-left py-2 text-slate-800 dark:text-gray-200">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($liste_prezenta as $lp): ?>
                                <tr class="border-b border-slate-100 dark:border-gray-700">
                                    <td class="py-2 text-slate-600 dark:text-gray-400"><?php echo (int)$lp['id']; ?></td>
                                    <td class="py-2 text-left">
                                        <a href="/liste-prezenta/edit?id=<?php echo $lp['id']; ?>" class="text-amber-600 dark:text-amber-400 hover:underline font-medium">
                                            <?php echo htmlspecialchars($lp['tip_titlu'] . ($lp['detalii_activitate'] ? ': ' . mb_substr($lp['detalii_activitate'], 0, 30) : '')); ?>
                                        </a>
                                    </td>
                                    <td class="py-2 text-slate-600 dark:text-gray-400"><?php echo date(DATE_FORMAT, strtotime($lp['data_lista'])); ?></td>
                                    <td class="py-2 text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($lp['created_by'] ?? '-'); ?></td>
                                    <td class="py-2 flex gap-1 items-center">
                                        <a href="/liste-prezenta/edit?id=<?php echo $lp['id']; ?>" class="px-2 py-1 rounded bg-slate-200 dark:bg-gray-600 hover:bg-slate-300 dark:hover:bg-gray-500 text-slate-900 dark:text-white text-xs font-medium" aria-label="Modifică lista">Modifică</a>
                                        <a href="/util/lista-prezenta-print.php?id=<?php echo $lp['id']; ?>" target="_blank" class="px-2 py-1 rounded bg-slate-200 dark:bg-gray-600 hover:bg-slate-300 dark:hover:bg-gray-500 text-slate-900 dark:text-white text-xs font-medium" aria-label="Printează lista">Print</a>
                                        <a href="/util/lista-prezenta-pdf.php?id=<?php echo $lp['id']; ?>" class="px-2 py-1 rounded bg-slate-200 dark:bg-gray-600 hover:bg-slate-300 dark:hover:bg-gray-500 text-slate-900 dark:text-white text-xs font-medium" aria-label="Descarcă PDF">PDF</a>
                                        <form method="post" action="/activitati<?php echo $afiseaza_tot ? '?afiseaza_tot=1' : ''; ?>" onsubmit="return confirm('Sigur doriți să ștergeți această listă de prezență?');" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="sterge_lista_prezenta" value="1">
                                            <input type="hidden" name="lista_id" value="<?php echo (int)$lp['id']; ?>">
                                            <button type="submit" class="px-2 py-1 rounded bg-red-600 hover:bg-red-700 text-white text-xs font-medium" aria-label="Șterge lista de prezență ID <?php echo (int)$lp['id']; ?>">Șterge</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-slate-500 dark:text-gray-400 text-sm">Nu există liste create.</p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<!-- Modal adăugare activitate -->
<dialog id="formular-activitate" class="rounded-lg shadow-xl p-0 max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto"
        aria-labelledby="titlu-formular-activitate" aria-modal="true">
    <form method="post" action="/activitati<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="adauga_activitate" value="1">
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? $_SERVER['REQUEST_URI'] ?? '/activitati'); ?>">
        <div class="p-6">
            <h2 id="titlu-formular-activitate" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Adaugă activitate</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="activitate-data" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Data</label>
                        <input type="date" id="activitate-data" name="data" required
                               value="<?php echo htmlspecialchars($_POST['data'] ?? date('Y-m-d')); ?>"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="activitate-ora" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Ora de început</label>
                        <input type="time" id="activitate-ora" name="ora" required
                               value="<?php echo htmlspecialchars($_POST['ora'] ?? '09:00'); ?>"
                               class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               aria-label="Ora de început (format 24h)">
                    </div>
                </div>
                <div>
                    <label for="activitate-ora-finalizare" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Până la ora</label>
                    <input type="time" id="activitate-ora-finalizare" name="ora_finalizare"
                           value="<?php echo htmlspecialchars($_POST['ora_finalizare'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-label="Până la ora (format 24h, opțional)">
                </div>
                <div>
                    <label for="activitate-nume" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume activitate</label>
                    <input type="text" id="activitate-nume" name="nume" required
                           value="<?php echo htmlspecialchars($_POST['nume'] ?? ''); ?>"
                           placeholder="Ex: Întâlnire comitet"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div>
                    <label for="activitate-recurenta" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Recurență</label>
                    <select id="activitate-recurenta" name="recurenta" class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Selectează recurența activității (opțional)">
                        <option value="">— Fără recurență —</option>
                        <option value="zilnic">Zilnic</option>
                        <option value="saptamanal">Săptămânal (aceeași zi)</option>
                        <option value="lunar">Lunar</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div>
                    <label for="activitate-locatie" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Locație</label>
                    <input type="text" id="activitate-locatie" name="locatie"
                           value="<?php echo htmlspecialchars($_POST['locatie'] ?? ''); ?>"
                           placeholder="Ex: Sediu ANR"
                           class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                </div>
                <div>
                    <label for="activitate-responsabili" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Responsabili</label>
                    <select id="activitate-responsabili" name="responsabili[]" multiple size="4"
                            class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                            aria-label="Selectează responsabili pentru activitate (țineți Ctrl/Cmd pentru selecție multiplă)">
                        <?php foreach ($utilizatori_platforma as $u): ?>
                        <option value="<?php echo htmlspecialchars($u['nume_complet']); ?>" <?php echo (isset($_POST['responsabili']) && in_array($u['nume_complet'], (array)$_POST['responsabili'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['nume_complet']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Țineți Ctrl (Windows) sau Cmd (Mac) pentru selecție multiplă</p>
                </div>
                <div>
                    <label for="activitate-info" class="block text-sm font-medium text-slate-900 dark:text-white mb-1">Informații suplimentare</label>
                    <textarea id="activitate-info" name="info_suplimentare" rows="3"
                              placeholder="Detalii opționale"
                              class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($_POST['info_suplimentare'] ?? ''); ?></textarea>
                </div>
                <div>
                    <button type="button" id="btn-adauga-participanti"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-amber-500 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-50 dark:hover:bg-gray-700">
                        <i data-lucide="user-plus" class="w-4 h-4" aria-hidden="true"></i>
                        Adaugă participanți (creare listă prezență)
                    </button>
                    <p class="text-xs text-slate-500 mt-1">Deschide crearea listei cu datele activității. Se vor crea activitatea și lista, asociate.</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 bg-slate-50 dark:bg-gray-700/50 flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('formular-activitate').close()"
                    class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-600"
                    aria-label="Anulează adăugare activitate">
                Anulare
            </button>
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează activitatea">
                Salvează
            </button>
        </div>
    </form>
</dialog>

<script>
lucide.createIcons();
<?php if ($deschide_formular): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formular-activitate')?.showModal();
});
<?php endif; ?>
document.getElementById('btn-adauga-participanti')?.addEventListener('click', function() {
    var f = document.getElementById('formular-activitate').querySelector('form');
    var nume = (f.querySelector('[name=nume]')||{}).value || '';
    var data = (f.querySelector('[name=data]')||{}).value || '';
    var ora = (f.querySelector('[name=ora]')||{}).value || '';
    var locatie = (f.querySelector('[name=locatie]')||{}).value || '';
    var responsabili = (f.querySelector('[name=responsabili]')||{}).value || '';
    if (!nume || !data || !ora) {
        alert('Completați Data, Ora de început și Numele activității.');
        return;
    }
    document.getElementById('formular-activitate').close();
    var q = '/liste-prezenta/adauga?din_activitate=1&nume=' + encodeURIComponent(nume) + '&data=' + encodeURIComponent(data) + '&ora=' + encodeURIComponent(ora);
    if (locatie) q += '&locatie=' + encodeURIComponent(locatie);
    if (responsabili) q += '&responsabili=' + encodeURIComponent(responsabili);
    window.location.href = q;
});
</script>
</body>
</html>
