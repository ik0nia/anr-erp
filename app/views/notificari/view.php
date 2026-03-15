<?php
/**
 * View: Notificare View — Vizualizare notificare individuala
 *
 * Variabile disponibile (setate de controller):
 *   $notif, $id, $user_id, $eroare, $succes
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($notif['titlu']); ?> - ID: <?php echo (int)$notif['id']; ?></h1>
        </div>
        <a href="notificari.php" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Înapoi la Notificări">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2" aria-hidden="true"></i>
            Înapoi la Notificări
        </a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-4xl">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Importanță</dt>
                    <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($notif['importanta']); ?></dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Data</dt>
                    <dd class="text-slate-900 dark:text-white">
                        <?php echo date(DATETIME_FORMAT, strtotime($notif['created_at'])); ?>
                        <?php if (!empty($notif['creator_username']) || !empty($notif['creator_nume'])): ?>
                        <span class="text-slate-600 dark:text-gray-400"> - Creat de: <?php echo htmlspecialchars($notif['creator_nume'] ?: $notif['creator_username']); ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
            <div class="border-t border-slate-200 dark:border-gray-600 pt-4 mb-4">
                <h2 class="sr-only">Conținut</h2>
                <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-slate-800 dark:text-gray-200"><?php echo nl2br(htmlspecialchars($notif['continut'])); ?></div>
            </div>
            <?php if (!empty(trim($notif['link_extern'] ?? ''))): ?>
            <div class="mb-4">
                <p class="text-sm font-medium text-slate-600 dark:text-gray-400">Link extern</p>
                <a href="<?php echo htmlspecialchars($notif['link_extern']); ?>" target="_blank" rel="noopener noreferrer" class="text-amber-600 dark:text-amber-400 hover:underline break-all"><?php echo htmlspecialchars($notif['link_extern']); ?></a>
            </div>
            <?php endif; ?>
            <?php if (!empty($notif['atasament_nume']) && !empty($notif['atasament_path']) && is_readable($notif['atasament_path'])): ?>
            <div class="mb-4">
                <p class="text-sm font-medium text-slate-600 dark:text-gray-400 mb-1">Atașament</p>
                <a href="descarca-notificare-atasament.php?id=<?php echo (int)$id; ?>" class="inline-flex items-center text-amber-600 dark:text-amber-400 hover:underline" aria-label="Descarcă <?php echo htmlspecialchars($notif['atasament_nume']); ?>">
                    <i data-lucide="download" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($notif['atasament_nume']); ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-200 dark:border-gray-600">
                <form method="post" action="notificare-view.php" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                    <input type="hidden" name="adauga_task" value="1">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Adaugă la taskuri">
                        <i data-lucide="list-checks" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                        Adaugă la taskuri
                    </button>
                </form>
                <?php if (($notif['user_status'] ?? '') !== 'arhivat' && ($notif['user_status'] ?? '') !== 'nou'): ?>
                <form method="post" action="notificare-view.php" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="marcheaza_necitit" value="1">
                    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Marchează notificarea ca necitită">
                        <i data-lucide="bell-off" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                        Marchează ca necitită
                    </button>
                </form>
                <?php endif; ?>
                <?php if (($notif['user_status'] ?? '') !== 'arhivat'): ?>
                <form method="post" action="notificare-view.php" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="arhiveaza" value="1">
                    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                    <input type="hidden" name="redirect" value="notificari.php">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Arhivează notificarea">
                        <i data-lucide="archive" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                        Arhivează Notificarea
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
