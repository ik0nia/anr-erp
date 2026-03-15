<?php
/**
 * View: Fundraising — Formular 230 si Lista donatori
 *
 * Variabile disponibile (setate de controller):
 *   $tab, $lista_donatori
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Fundraising</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <div class="mb-4" role="tablist" aria-label="Tab-uri Fundraising">
            <a href="/fundraising?tab=formular230"
               role="tab"
               aria-selected="<?php echo $tab === 'formular230' ? 'true' : 'false'; ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'formular230' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700 border border-transparent'; ?>">
                <i data-lucide="percent" class="w-5 h-5" aria-hidden="true"></i>
                Formularul 230
            </a>
            <a href="/fundraising?tab=donatori"
               role="tab"
               aria-selected="<?php echo $tab === 'donatori' ? 'true' : 'false'; ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'donatori' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700 border border-transparent'; ?>">
                <i data-lucide="users" class="w-5 h-5" aria-hidden="true"></i>
                Lista donatori
            </a>
        </div>

        <?php if ($tab === 'formular230'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <p class="text-slate-700 dark:text-gray-300 mb-4">Gestioneaza formularele 230 din modulul dedicat.</p>
            <a href="/formular-230" class="inline-flex items-center gap-2 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                <i data-lucide="external-link" class="w-5 h-5" aria-hidden="true"></i>
                Deschide Formular 230
            </a>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'donatori'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lista donatori</h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">Persoane care au facut donatii (date din modulul Incasari).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista donatori">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Nume si Prenume</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Telefon</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Nr. donatii</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Total donat (RON)</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Ultima donatie</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($lista_donatori)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-gray-400">Nu exista donatori inregistrati.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_donatori as $d):
                            $nume_complet = trim(($d['nume'] ?? '') . ' ' . ($d['prenume'] ?? ''));
                            $ultima = !empty($d['ultima_donatie']) ? date('d.m.Y', strtotime($d['ultima_donatie'])) : '—';
                            $membru_id = !empty($d['membru_id']) ? (int)$d['membru_id'] : null;
                            $contact_id = !empty($d['contact_id']) ? (int)$d['contact_id'] : null;
                            $link_profil = null;
                            if ($membru_id) {
                                $link_profil = '/membru-profil?id=' . $membru_id;
                            } elseif ($contact_id) {
                                $link_profil = '/contacte/edit?id=' . $contact_id;
                            }
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                <?php if ($link_profil): ?>
                                <a href="<?php echo htmlspecialchars($link_profil); ?>"
                                   class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 hover:underline">
                                    <?php echo htmlspecialchars($nume_complet ?: '—'); ?>
                                </a>
                                <?php else: ?>
                                <?php echo htmlspecialchars($nume_complet ?: '—'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($d['telefon'] ?? '—'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($d['email'] ?? '—'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300 text-right"><?php echo (int)$d['nr_donatii']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white text-right"><?php echo number_format((float)$d['total_donat'], 2, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($ultima); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<script>
if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
