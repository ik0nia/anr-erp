<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Voluntariat</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if ($eroare): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <?php if ($succes): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite"><?php echo htmlspecialchars($succes); ?></div>
        <?php endif; ?>

        <!-- Mesaj pentru voluntari -->
        <section class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4" aria-labelledby="mesaj-voluntari-heading">
            <h2 id="mesaj-voluntari-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Mesaj pentru voluntari</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">Acest mesaj este folosit ca șablon pentru invitații rapide (link WhatsApp).</p>
            <form method="post" action="voluntariat.php?tab=<?php echo urlencode($tab); ?>" class="flex flex-wrap gap-3 items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_mesaj_voluntari" value="1">
                <div class="flex-1 min-w-[200px]">
                    <label for="mesaj_voluntari" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Mesaj</label>
                    <textarea id="mesaj_voluntari" name="mesaj_voluntari" rows="2" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-describedby="mesaj-desc"><?php echo htmlspecialchars($mesaj_zilei); ?></textarea>
                    <p id="mesaj-desc" class="text-xs text-slate-500 dark:text-gray-400 mt-1">Opțional. Se precompletează la trimitere WhatsApp.</p>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Salvează mesajul pentru voluntari">Salvează Mesajul</button>
            </form>
            <?php if ($mesaj_zilei !== ''): ?>
            <form method="post" action="voluntariat.php?tab=<?php echo urlencode($tab); ?>" class="mt-2 inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="sterge_mesaj_voluntari" value="1">
                <button type="submit" class="px-3 py-1.5 text-sm border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Șterge mesajul pentru voluntari">Șterge</button>
            </form>
            <?php endif; ?>
        </section>

        <!-- Tab-uri -->
        <div class="mb-4 flex gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Secțiuni Voluntariat">
            <a href="voluntariat.php?tab=nomenclator" role="tab" aria-selected="<?php echo $tab === 'nomenclator' ? 'true' : 'false'; ?>" class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'nomenclator' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-900 dark:text-amber-200 border-b-2 border-amber-600' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">Nomenclator Voluntari</a>
            <a href="voluntariat.php?tab=activitati" role="tab" aria-selected="<?php echo $tab === 'activitati' ? 'true' : 'false'; ?>" class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'activitati' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-900 dark:text-amber-200 border-b-2 border-amber-600' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">Gestiune Activități</a>
            <a href="voluntariat.php?tab=registru" role="tab" aria-selected="<?php echo $tab === 'registru' ? 'true' : 'false'; ?>" class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'registru' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-900 dark:text-amber-200 border-b-2 border-amber-600' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">Registru Activități</a>
        </div>

        <?php if ($tab === 'nomenclator'): ?>
        <!-- Tab Nomenclator Voluntari -->
        <section aria-labelledby="nomenclator-heading">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 id="nomenclator-heading" class="text-lg font-semibold text-slate-900 dark:text-white">Nomenclator Voluntari</h2>
                <button type="button" onclick="document.getElementById('modal-adauga-voluntar').showModal()" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Adaugă voluntar nou">
                    <i data-lucide="user-plus" class="w-4 h-4 mr-2" aria-hidden="true"></i> Adaugă voluntar
                </button>
            </div>
            <?php if (!empty($templates_doc)): ?>
            <div class="mb-4 p-3 bg-slate-50 dark:bg-gray-700/50 rounded-lg">
                <form method="post" action="voluntariat.php?tab=nomenclator" class="flex flex-wrap items-center gap-2">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="setare_template_contract" value="1">
                    <label for="template_contract_id" class="text-sm font-medium text-slate-700 dark:text-gray-300">Șablon contract voluntariat:</label>
                    <select id="template_contract_id" name="template_contract_id" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Alege șablonul pentru contract">
                        <option value="">— Fără contract automat —</option>
                        <?php foreach ($templates_doc as $t): ?>
                        <option value="<?php echo (int)$t['id']; ?>" <?php echo (int)$t['id'] === (int)$template_contract_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['nume_afisare']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează șablonul contract">Salvează</button>
                </form>
            </div>
            <?php endif; ?>
            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista voluntari">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume / Prenume</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">CI / CNP</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Domiciliu</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nr. reg.</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <?php if (empty($lista_voluntari)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-600 dark:text-gray-400">Niciun voluntar înregistrat.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_voluntari as $v): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars(trim($v['nume'] . ' ' . ($v['prenume'] ?? ''))); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars(trim(($v['seria_ci'] ?? '') . ' ' . ($v['nr_ci'] ?? ''))); ?> <?php if (!empty($v['cnp'])): ?> / <?php echo htmlspecialchars($v['cnp']); endif; ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($v['domloc'] ?? '—'); ?><?php if (!empty($v['domstr'])): ?>, str. <?php echo htmlspecialchars($v['domstr']); endif; ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($v['nr_registratura'] ?? '—'); ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php
                                $tel = $v['telefon'] ?? '';
                                $email = $v['email'] ?? '';
                                $wa_url = $tel ? contacte_whatsapp_url_cu_mesaj($tel, $mesaj_zilei) : null;
                                ?>
                                <?php if ($wa_url): ?><a href="<?php echo htmlspecialchars($wa_url); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-2 py-1 text-green-700 dark:text-green-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Trimite WhatsApp către <?php echo htmlspecialchars($v['nume']); ?>"><i data-lucide="message-circle" class="w-4 h-4 mr-1" aria-hidden="true"></i> WhatsApp</a><?php endif; ?>
                                <?php if ($email): ?><a href="mailto:<?php echo htmlspecialchars($email); ?>" class="inline-flex items-center px-2 py-1 text-slate-700 dark:text-gray-300 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Trimite email către <?php echo htmlspecialchars($v['nume']); ?>"><i data-lucide="mail" class="w-4 h-4 mr-1" aria-hidden="true"></i> Email</a><?php endif; ?>
                                <button type="button" onclick="editeazaVoluntar(<?php echo (int)$v['id']; ?>)" class="inline-flex items-center px-2 py-1 text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Editează voluntar">Editează</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($tab === 'activitati'): ?>
        <!-- Tab Gestiune Activități -->
        <section aria-labelledby="activitati-heading">
            <h2 id="activitati-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Gestiune Activități</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6">
                <h3 class="font-medium text-slate-900 dark:text-white mb-3">Adaugă activitate</h3>
                <form method="post" action="voluntariat.php?tab=activitati">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_activitate" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="nume_activitate" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume activitate <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input type="text" id="nume_activitate" name="nume_activitate" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                        </div>
                        <div>
                            <label for="data_activitate" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Dată <span class="text-red-600" aria-hidden="true">*</span></label>
                            <input type="date" id="data_activitate" name="data_activitate" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                        </div>
                        <div>
                            <label for="ora_inceput" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Ora început</label>
                            <input type="time" id="ora_inceput" name="ora_inceput" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        </div>
                        <div>
                            <label for="ora_sfarsit" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Ora sfârșit</label>
                            <input type="time" id="ora_sfarsit" name="ora_sfarsit" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                        </div>
                    </div>
                    <button type="submit" class="mt-3 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează activitatea">Adaugă activitate</button>
                </form>
            </div>
            <div class="space-y-6">
                <?php foreach ($lista_activitati as $act): ?>
                <?php $participanti = voluntariat_get_participanti($pdo, $act['id']); ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4">
                    <h3 class="font-medium text-slate-900 dark:text-white mb-2"><?php echo htmlspecialchars($act['nume']); ?> — <?php echo date(DATE_FORMAT, strtotime($act['data_activitate'])); ?><?php if ($act['ora_inceput'] || $act['ora_sfarsit']): ?> (<?php echo $act['ora_inceput'] ? substr($act['ora_inceput'], 0, 5) : ''; ?> - <?php echo $act['ora_sfarsit'] ? substr($act['ora_sfarsit'], 0, 5) : ''; ?>)<?php endif; ?></h3>
                    <div class="flex flex-wrap gap-2 mb-3 items-start">
                        <div class="relative">
                            <label for="cauta-vol-<?php echo (int)$act['id']; ?>" class="sr-only">Caută voluntar pentru activitate</label>
                            <input type="text" id="cauta-vol-<?php echo (int)$act['id']; ?>" data-activitate-id="<?php echo (int)$act['id']; ?>" class="cauta-voluntar px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg w-64 text-slate-900 dark:text-white dark:bg-gray-700" placeholder="Caută voluntar..." autocomplete="off" aria-label="Caută voluntar pentru a adăuga în activitate" aria-autocomplete="list" aria-controls="sugestii-<?php echo (int)$act['id']; ?>" aria-expanded="false">
                            <div id="sugestii-<?php echo (int)$act['id']; ?>" class="sugestii-voluntari absolute left-0 top-full z-10 mt-1 w-64 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden" role="listbox" aria-label="Sugestii voluntari"></div>
                        </div>
                        <button type="button" class="btn-adauga-in-activitate px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm rounded-lg focus:ring-2 focus:ring-amber-500" data-activitate-id="<?php echo (int)$act['id']; ?>" aria-label="Adaugă voluntarul selectat în activitate">Adaugă în activitate</button>
                    </div>
                    <table class="min-w-full text-sm">
                        <thead><tr><th class="text-left py-2 text-slate-600 dark:text-gray-400">Voluntar</th><th class="text-left py-2 text-slate-600 dark:text-gray-400">Ore</th><th class="text-right py-2 text-slate-600 dark:text-gray-400">Acțiuni</th></tr></thead>
                        <tbody>
                            <?php foreach ($participanti as $p): ?>
                            <tr>
                                <td class="py-1"><?php echo htmlspecialchars(trim($p['nume'] . ' ' . ($p['prenume'] ?? ''))); ?></td>
                                <td class="py-1"><?php echo $p['ore_prestate'] !== null ? number_format($p['ore_prestate'], 2, ',', '.') : '—'; ?></td>
                                <td class="py-1 text-right">
                                    <?php $wa = $p['telefon'] ? contacte_whatsapp_url_cu_mesaj($p['telefon'], $mesaj_zilei) : null; ?>
                                    <?php if ($wa): ?><a href="<?php echo htmlspecialchars($wa); ?>" target="_blank" rel="noopener noreferrer" class="text-green-700 dark:text-green-400 hover:underline" aria-label="WhatsApp">WhatsApp</a><?php endif; ?>
                                    <?php if (!empty($p['email'])): ?> <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>" class="text-slate-600 dark:text-gray-400 hover:underline" aria-label="Email">Email</a><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($tab === 'registru'): ?>
        <!-- Tab Registru Activități -->
        <section aria-labelledby="registru-heading">
            <h2 id="registru-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Registru Activități (evidență ore pentru adeverințe)</h2>
            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Registru ore prestate">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Activitate</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Dată</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Voluntar</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Ore prestate</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Comunicare</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        <?php if (empty($registru_ore)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-600 dark:text-gray-400">Nicio înregistrare în registru.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($registru_ore as $r): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['activitate_nume']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo date(DATE_FORMAT, strtotime($r['data_activitate'])); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars(trim($r['voluntar_nume'] . ' ' . ($r['voluntar_prenume'] ?? ''))); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo $r['ore_prestate'] !== null ? number_format($r['ore_prestate'], 2, ',', '.') : '—'; ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php $wa = $r['telefon'] ? contacte_whatsapp_url_cu_mesaj($r['telefon'], $mesaj_zilei) : null; ?>
                                <?php if ($wa): ?><a href="<?php echo htmlspecialchars($wa); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center text-green-700 dark:text-green-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="WhatsApp"><i data-lucide="message-circle" class="w-4 h-4 mr-1" aria-hidden="true"></i> WhatsApp</a><?php endif; ?>
                                <?php if (!empty($r['email'])): ?><a href="mailto:<?php echo htmlspecialchars($r['email']); ?>" class="inline-flex items-center text-slate-600 dark:text-gray-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded ml-2" aria-label="Email"><i data-lucide="mail" class="w-4 h-4 mr-1" aria-hidden="true"></i> Email</a><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Adaugă voluntar -->
<dialog id="modal-adauga-voluntar" class="rounded-lg shadow-xl bg-white dark:bg-gray-800 p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto" aria-labelledby="modal-adauga-voluntar-title" aria-modal="true">
    <h3 id="modal-adauga-voluntar-title" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Adaugă voluntar</h3>
    <form method="post" action="voluntariat.php?tab=nomenclator" id="form-adauga-voluntar">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="adauga_voluntar" value="1">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label for="v_nume" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume <span class="text-red-600">*</span></label><input type="text" id="v_nume" name="nume" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_prenume" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Prenume</label><input type="text" id="v_prenume" name="prenume" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_cnp" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">CNP</label><input type="text" id="v_cnp" name="cnp" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_seria_ci" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Seria CI</label><input type="text" id="v_seria_ci" name="seria_ci" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_nr_ci" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nr. CI</label><input type="text" id="v_nr_ci" name="nr_ci" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_telefon" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Telefon</label><input type="text" id="v_telefon" name="telefon" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div class="md:col-span-2"><label for="v_email" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Email</label><input type="email" id="v_email" name="email" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_codpost" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Cod postal</label><input type="text" id="v_codpost" name="codpost" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_domloc" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Localitate</label><input type="text" id="v_domloc" name="domloc" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_judet_domiciliu" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Județ</label><input type="text" id="v_judet_domiciliu" name="judet_domiciliu" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_domstr" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Strada</label><input type="text" id="v_domstr" name="domstr" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_domnr" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nr.</label><input type="text" id="v_domnr" name="domnr" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_dombl" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Bloc</label><input type="text" id="v_dombl" name="dombl" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_domsc" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Scara</label><input type="text" id="v_domsc" name="domsc" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_domet" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Etaj</label><input type="text" id="v_domet" name="domet" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
            <div><label for="v_domap" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Apartament</label><input type="text" id="v_domap" name="domap" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează voluntar">Salvează</button>
            <button type="button" onclick="document.getElementById('modal-adauga-voluntar').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Închide">Anulare</button>
        </div>
    </form>
</dialog>

<!-- Modal Editează voluntar -->
<dialog id="modal-editeaza-voluntar" class="rounded-lg shadow-xl bg-white dark:bg-gray-800 p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto" aria-labelledby="modal-editeaza-voluntar-title" aria-modal="true">
    <h3 id="modal-editeaza-voluntar-title" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Editează voluntar</h3>
    <form method="post" action="voluntariat.php?tab=nomenclator" id="form-editeaza-voluntar">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="actualizeaza_voluntar" value="1">
        <input type="hidden" name="voluntar_id" id="edit_voluntar_id" value="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="edit-voluntar-fields"></div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează modificările">Salvează</button>
            <button type="button" onclick="document.getElementById('modal-editeaza-voluntar').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Închide">Anulare</button>
        </div>
    </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // Autocomplete voluntari pentru fiecare activitate
    var voluntariSelectat = {};
    document.querySelectorAll('.cauta-voluntar').forEach(function(inp) {
        var actId = inp.getAttribute('data-activitate-id');
        function doCautaVoluntar() {
            var q = inp.value.trim();
            var list = document.getElementById('sugestii-' + actId);
            if (q.length < 1) { if (list) { list.classList.add('hidden'); list.innerHTML = ''; } return; }
            fetch('api-cauta-voluntari.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    list.innerHTML = '';
                    list.classList.remove('hidden');
                    (data.voluntari || []).forEach(function(v) {
                        var opt = document.createElement('div');
                        opt.setAttribute('role', 'option');
                        opt.className = 'px-3 py-2 hover:bg-slate-100 dark:hover:bg-gray-700 cursor-pointer';
                        opt.textContent = (v.nume || '') + ' ' + (v.prenume || '');
                        opt.dataset.voluntarId = v.id;
                        opt.dataset.voluntarNume = (v.nume || '') + ' ' + (v.prenume || '');
                        opt.addEventListener('click', function() {
                            voluntariSelectat[actId] = { id: v.id, nume: opt.dataset.voluntarNume };
                            inp.value = opt.dataset.voluntarNume;
                            inp.dataset.voluntarId = v.id;
                            list.classList.add('hidden');
                            list.innerHTML = '';
                        });
                        list.appendChild(opt);
                    });
                    if (list.children.length === 0) {
                        var empty = document.createElement('div');
                        empty.className = 'px-3 py-2 text-slate-500 dark:text-gray-400';
                        empty.textContent = 'Niciun rezultat';
                        list.appendChild(empty);
                    }
                });
        }
        inp.addEventListener('input', doCautaVoluntar);
        inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') { e.preventDefault(); doCautaVoluntar(); } });
    });
    document.querySelectorAll('.btn-adauga-in-activitate').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var actId = this.getAttribute('data-activitate-id');
            var inp = document.querySelector('.cauta-voluntar[data-activitate-id="' + actId + '"]');
            var vid = inp && inp.dataset.voluntarId ? inp.dataset.voluntarId : (voluntariSelectat[actId] && voluntariSelectat[actId].id);
            if (!vid) { alert('Selectați un voluntar din listă.'); return; }
            var tok = document.querySelector('input[name="_csrf_token"]');
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'voluntariat.php?tab=activitati';
            form.innerHTML = '<input type="hidden" name="_csrf_token" value="' + (tok ? tok.value : '') + '">' +
                '<input type="hidden" name="adauga_participant" value="1">' +
                '<input type="hidden" name="activitate_id" value="' + actId + '">' +
                '<input type="hidden" name="voluntar_id" value="' + vid + '">';
            document.body.appendChild(form);
            form.submit();
        });
    });

    window.editeazaVoluntar = function(id) {
        window.location.href = 'voluntariat.php?tab=nomenclator&editeaza=' + id;
    };
});
</script>
<?php
// Dacă avem ?editeaza=ID în Nomenclator, afișăm modal editează cu datele încărcate
if ($tab === 'nomenclator' && $editVol) {
    $cid = (int)($editVol['contact_id'] ?? 0);
    $esc = function($x) { return htmlspecialchars($x ?? '', ENT_QUOTES, 'UTF-8'); };
    $editHtml = '<input type="hidden" name="contact_id" value="' . $cid . '"><div class="md:col-span-2"><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Nume *</label><input type="text" name="nume" value="' . $esc($editVol['nume']) . '" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div class="md:col-span-2"><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Prenume</label><input type="text" name="prenume" value="' . $esc($editVol['prenume']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">CNP</label><input type="text" name="cnp" value="' . $esc($editVol['cnp']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Seria / Nr. CI</label><input type="text" name="seria_ci" value="' . $esc($editVol['seria_ci']) . '" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 mb-1"><input type="text" name="nr_ci" value="' . $esc($editVol['nr_ci']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Telefon</label><input type="text" name="telefon" value="' . $esc($editVol['telefon']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Email</label><input type="email" name="email" value="' . $esc($editVol['email']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Cod postal</label><input type="text" name="codpost" value="' . $esc($editVol['codpost']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Localitate</label><input type="text" name="domloc" value="' . $esc($editVol['domloc']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Județ</label><input type="text" name="judet_domiciliu" value="' . $esc($editVol['judet_domiciliu']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Strada, Nr.</label><input type="text" name="domstr" value="' . $esc($editVol['domstr']) . '" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 mb-1"><input type="text" name="domnr" value="' . $esc($editVol['domnr']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div><div><label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300">Bloc, Scara, Etaj, Ap.</label><input type="text" name="dombl" value="' . $esc($editVol['dombl']) . '" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 mb-1"><input type="text" name="domsc" value="' . $esc($editVol['domsc']) . '" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 mb-1"><input type="text" name="domet" value="' . $esc($editVol['domet']) . '" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 mb-1"><input type="text" name="domap" value="' . $esc($editVol['domap']) . '" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700"></div>';
    echo '<script>document.addEventListener("DOMContentLoaded", function() { document.getElementById("edit_voluntar_id").value = ' . (int)$editVol['id'] . '; var div = document.getElementById("edit-voluntar-fields"); if (div) div.innerHTML = ' . json_encode($editHtml) . '; document.getElementById("modal-editeaza-voluntar").showModal(); });</script>';
}
?>
</body>
</html>
