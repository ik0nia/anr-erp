<?php
/**
 * View: Registratura — Sumar inregistrare
 *
 * Variabile disponibile (setate de controller):
 *   $r — datele inregistrarii din registratura
 *   $redirect_url — URL-ul de redirect (dashboard sau registratura)
 */
?>

<main class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Sumar inregistrare Registratura</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1 flex flex-col items-center">
        <div class="w-full max-w-2xl">
            <div class="mb-8 p-6 bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-500 rounded-xl text-center" role="status" aria-live="polite">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">Numar inregistrare alocat</p>
                <p class="text-3xl font-bold text-amber-700 dark:text-amber-300" aria-label="Numar inregistrare <?php echo htmlspecialchars($r['nr_inregistrare']); ?>"><?php echo htmlspecialchars($r['nr_inregistrare']); ?></p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-8">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Detalii inregistrare</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">Data</dt>
                        <dd class="text-slate-900 dark:text-white"><?php echo date(DATETIME_FORMAT, strtotime($r['data_ora'])); ?></dd>
                    </div>
                    <?php if (!empty($r['nr_document']) || !empty($r['data_document'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">Numar si data document</dt>
                        <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['nr_document'] ?? '-'); ?> <?php echo !empty($r['data_document']) ? ' / ' . date(DATE_FORMAT, strtotime($r['data_document'])) : ''; ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($r['provine_din'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">De unde provine documentul</dt>
                        <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['provine_din']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($r['continut_document'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">Continut document</dt>
                        <dd class="text-slate-900 dark:text-white"><?php echo nl2br(htmlspecialchars($r['continut_document'])); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($r['destinatar_document'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">Destinatar document</dt>
                        <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['destinatar_document']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">Operator</dt>
                        <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['utilizator']); ?></dd>
                    </div>
                    <?php if (!empty($r['task_deschis'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-slate-500 dark:text-gray-400">Task deschis</dt>
                        <dd class="text-slate-900 dark:text-white">Da</dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>

            <div class="flex justify-center">
                <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="px-8 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="OK - revino la pagina anterioara">OK</a>
            </div>
        </div>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
