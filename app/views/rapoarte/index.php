<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Rapoarte</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <nav class="mb-6 flex gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Tab-uri rapoarte">
            <a href="/rapoarte" role="tab" aria-selected="<?php echo $tab_rapoarte === 'membri' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'membri' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Indicatori Membri
            </a>
            <a href="/rapoarte?tab=interactiuni" role="tab" aria-selected="<?php echo $tab_rapoarte === 'interactiuni' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'interactiuni' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Registru Interacțiuni
            </a>
            <a href="/rapoarte?tab=newsletter" role="tab" aria-selected="<?php echo $tab_rapoarte === 'newsletter' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'newsletter' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Newsletter
            </a>
            <a href="/rapoarte?tab=statistici" role="tab" aria-selected="<?php echo $tab_rapoarte === 'statistici' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'statistici' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Statistici
            </a>
            <a href="/rapoarte?tab=socializare" role="tab" aria-selected="<?php echo $tab_rapoarte === 'socializare' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'socializare' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Socializare
            </a>
            <a href="/rapoarte?tab=borderou-legitimatii" role="tab" aria-selected="<?php echo $tab_rapoarte === 'borderou-legitimatii' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_rapoarte === 'borderou-legitimatii' ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Borderou legitimatii
            </a>
        </nav>

        <?php if ($tab_rapoarte === 'membri'): ?>
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Indicatori Membri</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Membri Activi -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Membri Activi</p>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2"><?php echo $membri_activi; ?></p>
                        </div>
                        <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <i data-lucide="user-check" class="w-8 h-8 text-green-600 dark:text-green-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Membri Suspendati/Expirati -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Membri Suspendati/Expirati</p>
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2"><?php echo $membri_suspendati; ?></p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <i data-lucide="user-x" class="w-8 h-8 text-yellow-600 dark:text-yellow-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Arhiva Membri -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Arhiva Membri</p>
                            <p class="text-3xl font-bold text-slate-600 dark:text-gray-400 mt-2"><?php echo $membri_arhiva; ?></p>
                        </div>
                        <div class="p-3 bg-slate-100 dark:bg-gray-700 rounded-lg">
                            <i data-lucide="archive" class="w-8 h-8 text-slate-600 dark:text-gray-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Gradul Grav -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Gradul Grav</p>
                            <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2"><?php echo $grad_grav; ?></p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <i data-lucide="alert-circle" class="w-8 h-8 text-red-600 dark:text-red-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Grad Grav cu Asistent Personal -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Grad Grav cu Asistent Personal</p>
                            <p class="text-3xl font-bold text-red-800 dark:text-red-300 mt-2"><?php echo $grad_grav_cu_asistent; ?></p>
                        </div>
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <i data-lucide="heart-handshake" class="w-8 h-8 text-red-800 dark:text-red-300" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Gradul Accentuat -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Gradul Accentuat</p>
                            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2"><?php echo $grad_accentuat; ?></p>
                        </div>
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-orange-600 dark:text-orange-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Gradul Mediu -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Gradul Mediu</p>
                            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2"><?php echo $grad_mediu; ?></p>
                        </div>
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <i data-lucide="info" class="w-8 h-8 text-yellow-600 dark:text-yellow-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Femei -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Femei</p>
                            <p class="text-3xl font-bold text-pink-600 dark:text-pink-400 mt-2"><?php echo $femei; ?></p>
                        </div>
                        <div class="p-3 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                            <i data-lucide="user" class="w-8 h-8 text-pink-600 dark:text-pink-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <!-- Bărbați -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Bărbați</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo $barbati; ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <i data-lucide="user" class="w-8 h-8 text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Cazuri sociale</p>
                            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?php echo (int)$cazuri_sociale; ?></p>
                        </div>
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                            <i data-lucide="shield-alert" class="w-8 h-8 text-indigo-600 dark:text-indigo-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'interactiuni'): ?>
        <div class="mb-6" role="region" aria-labelledby="raport-interactiuni-heading">
            <h2 id="raport-interactiuni-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Registru Interacțiuni – Totaluri și categorii</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total apeluri</p>
                            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo (int)$raport_interactiuni['total_apeluri']; ?></p>
                        </div>
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <i data-lucide="phone" class="w-8 h-8 text-blue-600 dark:text-blue-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total vizite</p>
                            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?php echo (int)$raport_interactiuni['total_vizite']; ?></p>
                        </div>
                        <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                            <i data-lucide="building" class="w-8 h-8 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-600 dark:text-gray-400">Total interacțiuni</p>
                            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2"><?php echo (int)$raport_interactiuni['total_general']; ?></p>
                        </div>
                        <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                            <i data-lucide="phone-call" class="w-8 h-8 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white px-6 py-3 border-b border-slate-200 dark:border-gray-700">Categorii după subiect (număr interacțiuni)</h3>
                <?php if (empty($raport_interactiuni['categorii'])): ?>
                <p class="p-6 text-slate-600 dark:text-gray-400">Nu există încă interacțiuni înregistrate pe subiecte.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Categorii registru interacțiuni">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Categorie / Subiect</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr interacțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($raport_interactiuni['categorii'] as $nume_cat => $nr): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-3 font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($nume_cat); ?></td>
                                <td class="px-6 py-3 text-right font-semibold text-amber-600 dark:text-amber-400"><?php echo (int)$nr; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'newsletter'): ?>
        <div class="mb-6" role="region" aria-labelledby="raport-newsletter-heading">
            <h2 id="raport-newsletter-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Raport Newsletter</h2>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <?php if (empty($lista_newsletter_rapoarte)): ?>
                <p class="p-6 text-slate-600 dark:text-gray-400">Niciun newsletter trimis încă.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista newslettere trimise">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Subiect</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Număr contacte</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Categoria contacte</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data trimiterii</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($lista_newsletter_rapoarte as $nl): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($nl['subiect']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$nl['nr_recipienti']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($nl['categoria_contacte']); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $nl['data_trimiterii'] ? date('d.m.Y H:i', strtotime($nl['data_trimiterii'])) : '-'; ?></td>
                                <td class="px-4 py-3">
                                    <a href="/newsletter-view?id=<?php echo (int)$nl['id']; ?>" class="text-amber-600 dark:text-amber-400 hover:underline" aria-label="Vizualizează newsletter <?php echo htmlspecialchars($nl['subiect']); ?>">Vizualizează</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'statistici' && $statistici_membri !== null): ?>
        <div class="mb-6" role="region" aria-labelledby="statistici-heading">
            <h2 id="statistici-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Statistici Membri Activi</h2>

            <!-- Total Membri Activi -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4">Număr Membri Activi: <?php echo $statistici_membri['total']; ?></h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Femei/Bărbați -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">După gen:</h4>
                        <ul class="space-y-2">
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Femei:</span>
                                <span class="font-semibold text-pink-600 dark:text-pink-400"><?php echo $statistici_membri['femei']; ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Bărbați:</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400"><?php echo $statistici_membri['barbati']; ?></span>
                            </li>
                        </ul>
                    </div>

                    <!-- Urban/Rural -->
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">După mediu:</h4>
                        <ul class="space-y-2">
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Urban:</span>
                                <span class="font-semibold text-slate-900 dark:text-white"><?php echo $statistici_membri['urban']; ?></span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span class="text-slate-700 dark:text-gray-300">Rural:</span>
                                <span class="font-semibold text-slate-900 dark:text-white"><?php echo $statistici_membri['rural']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Grupe de vârstă -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-4">Grupe de vârstă:</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Statistici grupe de vârstă">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Grupa de vârstă</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Total</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Femei</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Bărbați</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Urban</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Rural</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php foreach ($statistici_membri['grupe_varsta'] as $label => $data): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($label); ?> ani</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $data['total']; ?></td>
                                    <td class="px-4 py-3 text-sm text-pink-600 dark:text-pink-400"><?php echo $data['femei']; ?></td>
                                    <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400"><?php echo $data['barbati']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $data['urban']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo $data['rural']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Statistici pe localități -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mt-6">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4">Statistici pe Localități</h3>

                <?php if (empty($statistici_localitati)): ?>
                <p class="text-slate-600 dark:text-gray-400">Nu există date disponibile pentru localități.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Statistici pe localități">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitate</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Total</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Femei</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Bărbați</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">0-18 ani</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">18-35 ani</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">35-65 ani</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">65+ ani</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($statistici_localitati as $loc): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                    <?php
                                    echo htmlspecialchars($loc['localitate']);
                                    if (!empty($loc['primaria']) || !empty($loc['judet'])) {
                                        echo ' (';
                                        $parts = [];
                                        if (!empty($loc['primaria'])) {
                                            $parts[] = 'Primaria ' . htmlspecialchars($loc['primaria']);
                                        }
                                        if (!empty($loc['judet'])) {
                                            $parts[] = htmlspecialchars($loc['judet']);
                                        }
                                        echo implode(', ', $parts);
                                        echo ')';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['total']; ?></td>
                                <td class="px-4 py-3 text-sm text-pink-600 dark:text-pink-400"><?php echo (int)$loc['femei']; ?></td>
                                <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400"><?php echo (int)$loc['barbati']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_0_18']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_18_35']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_35_65']; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo (int)$loc['varsta_65_plus']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'socializare' && $socializare_raport !== null): ?>
        <div class="mb-6" role="region" aria-labelledby="raport-socializare-heading">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 id="raport-socializare-heading" class="text-lg font-semibold text-slate-900 dark:text-white">Raport Socializare</h2>
                <form method="get" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="socializare">
                    <label for="raport-socializare-an" class="text-sm font-medium text-slate-700 dark:text-gray-300">An</label>
                    <select id="raport-socializare-an" name="an" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                        <?php foreach (($socializare_raport['ani_disponibili'] ?? []) as $an_opt): ?>
                        <option value="<?php echo (int)$an_opt; ?>" <?php echo ((int)$an_opt === (int)($socializare_raport['an_selectat'] ?? 0)) ? 'selected' : ''; ?>>
                            <?php echo (int)$an_opt; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-slate-600 dark:text-gray-400">Număr activități</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo (int)($socializare_raport['total_activitati'] ?? 0); ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-slate-600 dark:text-gray-400">Total participanți</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2"><?php echo (int)($socializare_raport['total_participanti'] ?? 0); ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-slate-600 dark:text-gray-400">Bărbați</p>
                    <p class="text-3xl font-bold text-blue-700 dark:text-blue-300 mt-2"><?php echo (int)($socializare_raport['total_barbati'] ?? 0); ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-slate-600 dark:text-gray-400">Femei</p>
                    <p class="text-3xl font-bold text-pink-600 dark:text-pink-400 mt-2"><?php echo (int)($socializare_raport['total_femei'] ?? 0); ?></p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-slate-600 dark:text-gray-400">An raport selectat</p>
                    <p class="text-3xl font-bold text-slate-600 dark:text-gray-300 mt-2"><?php echo (int)($socializare_raport['an_selectat'] ?? date('Y')); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-3">Grupe de vârstă (participanți unici)</h3>
                    <?php if (empty($socializare_raport['grupe_varsta'])): ?>
                    <p class="text-slate-600 dark:text-gray-400 text-sm">Nu există participanți cu data nașterii disponibilă.</p>
                    <?php else: ?>
                    <ul class="space-y-2">
                        <?php foreach ($socializare_raport['grupe_varsta'] as $grupa): ?>
                        <li class="flex justify-between items-center">
                            <span class="text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($grupa['label'] ?? '-'); ?></span>
                            <span class="font-semibold text-slate-900 dark:text-white"><?php echo (int)($grupa['total'] ?? 0); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-3">Participanți per activitate</h3>
                    <div class="h-64">
                        <canvas id="chart-socializare" aria-label="Grafic participanți la activități de socializare"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <h3 class="text-md font-semibold text-slate-900 dark:text-white px-5 py-3 border-b border-slate-200 dark:border-gray-700">Liste Socializare (<?php echo (int)($socializare_raport['an_selectat'] ?? date('Y')); ?>)</h3>
                <?php if (empty($socializare_raport['activitati'])): ?>
                <p class="p-5 text-slate-600 dark:text-gray-400">Nu există liste de socializare în anul selectat.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Activitate</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Participanți</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php foreach ($socializare_raport['activitati'] as $ls): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($ls['data'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars(LISTA_SOCIALIZARE_ACTIVITATE); ?></td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-blue-600 dark:text-blue-400"><?php echo (int)($ls['participanti'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tab_rapoarte === 'borderou-legitimatii' && $borderou_legitimatii !== null): ?>
        <div class="mb-6" role="region" aria-labelledby="raport-borderou-legitimatii-heading">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 id="raport-borderou-legitimatii-heading" class="text-lg font-semibold text-slate-900 dark:text-white">Borderou legitimatii de membru</h2>
                <a href="/util/rapoarte-borderou-legitimatii-print.php?data_de_la=<?php echo urlencode($borderou_legitimatii['data_de_la'] ?? date('Y-01-01')); ?>&data_pana_la=<?php echo urlencode($borderou_legitimatii['data_pana_la'] ?? date('Y-m-d')); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500"
                   aria-label="Print borderou legitimatii">
                    <i data-lucide="printer" class="w-4 h-4" aria-hidden="true"></i>
                    Print
                </a>
            </div>

            <form method="get" class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-4 mb-5 flex flex-wrap items-end gap-3" aria-label="Filtru interval borderou legitimatii">
                <input type="hidden" name="tab" value="borderou-legitimatii">
                <div>
                    <label for="borderou-legitimatii-de-la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">De la data</label>
                    <input type="date" id="borderou-legitimatii-de-la" name="de_la" value="<?php echo htmlspecialchars($borderou_legitimatii_data_de_la ?? date('Y-01-01')); ?>"
                           class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label for="borderou-legitimatii-pana-la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Pana la data</label>
                    <input type="date" id="borderou-legitimatii-pana-la" name="pana_la" value="<?php echo htmlspecialchars($borderou_legitimatii_data_pana_la ?? date('Y-m-d')); ?>"
                           class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-indigo-500">Confirma</button>
            </form>

            <div class="grid grid-cols-1 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-5">
                    <p class="text-sm text-slate-600 dark:text-gray-400">Numar legitimatii utilizate</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2"><?php echo (int)(($borderou_legitimatii['statistici']['total'] ?? 0)); ?></p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Borderou legitimatii membru">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Numar dosar</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Numele membrului</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tip actiune</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Operator (utilizatorul)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($borderou_legitimatii['operatiuni'] ?? [])): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu exista operatiuni in intervalul selectat.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach (($borderou_legitimatii['operatiuni'] ?? []) as $idx => $row): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo !empty($row['data_actiune']) ? htmlspecialchars(date(DATE_FORMAT, strtotime((string)$row['data_actiune']))) : '-'; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars((string)($row['dosarnr'] ?? '-')); ?></td>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars((string)($row['membru_nume'] ?? trim((string)($row['nume'] ?? '') . ' ' . (string)($row['prenume'] ?? '')) ?: '-')); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars((string)($row['actiune_label'] ?? (membri_legitimatii_tipuri_actiune()[$row['actiune'] ?? ($row['tip_actiune'] ?? '')] ?? ($row['actiune'] ?? ($row['tip_actiune'] ?? '-'))))); ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars((string)($row['utilizator'] ?? 'Sistem')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    <?php if ($tab_rapoarte === 'socializare' && $socializare_raport !== null): ?>
    if (typeof Chart !== 'undefined') {
        var socializareLabels = <?php echo json_encode(array_map(function($x){ return $x['data'] ?? ''; }, $socializare_raport['activitati'])); ?>;
        var socializareData = <?php echo json_encode(array_map(function($x){ return (int)($x['participanti'] ?? 0); }, $socializare_raport['activitati'])); ?>;
        var ctxSocializare = document.getElementById('chart-socializare');
        if (ctxSocializare) {
            new Chart(ctxSocializare, {
                type: 'bar',
                data: {
                    labels: socializareLabels,
                    datasets: [{
                        label: 'Participanți',
                        data: socializareData,
                        backgroundColor: '#3b82f6',
                        borderColor: '#2563eb',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }
    <?php endif; ?>
});
</script>
<?php if ($tab_rapoarte === 'socializare'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<?php endif; ?>
</body>
</html>
