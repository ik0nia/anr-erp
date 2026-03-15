<?php
/**
 * View: Activitati — Istoric activitati trecute
 *
 * Variabile: $activitati, $eroare_bd, $an, $luni_ro
 */
?>
<main class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div class="flex items-center gap-4">
            <a href="activitati.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded inline-flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-5 h-5" aria-hidden="true"></i>
                Înapoi
            </a>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Istoric activități (doar trecute) — <?php echo $an; ?></h1>
        </div>
        <form method="get" action="activitati-istoric.php" class="flex items-center gap-2">
            <label for="an-select" class="text-sm text-slate-700 dark:text-gray-300">An:</label>
            <select id="an-select" name="an" onchange="this.form.submit()"
                    class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500"
                    aria-label="Selectează anul">
                <?php for ($a = date('Y'); $a >= 2020; $a--): ?>
                <option value="<?php echo $a; ?>" <?php echo $an === $a ? 'selected' : ''; ?>><?php echo $a; ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare_bd)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert">
                <?php echo htmlspecialchars($eroare_bd); ?>
            </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Istoric activități anul <?php echo $an; ?>">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Ora</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume activitate</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Locație</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Responsabil</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($activitati)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">
                                Nu există activități trecute pentru anul <?php echo $an; ?>.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($activitati as $a): 
                            $dt = new DateTime($a['data_ora']);
                        ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo data_cu_ziua_ro($dt); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo $dt->format(TIME_FORMAT); ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($a['nume']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($a['locatie'] ?: '—'); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($a['responsabili'] ?: '—'); ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs rounded font-medium <?php 
                                    echo $a['status'] === 'Planificata' ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200' : 
                                        ($a['status'] === 'Finalizata' ? 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200' : 
                                        ($a['status'] === 'Reprogramata' ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200' : 'bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-gray-400')); 
                                ?>"><?php echo htmlspecialchars($a['status']); ?></span>
                            </td>
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

<script>lucide.createIcons();</script>
</body>
</html>
