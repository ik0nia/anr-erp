<?php
/**
 * Afișare newsletter trimis - CRM ANR Bihor
 */
require_once __DIR__ . '/config.php';
require_once 'includes/newsletter_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: rapoarte.php');
    exit;
}

$nl = newsletter_get_by_id($pdo, $id);
if (!$nl) {
    header('Location: rapoarte.php');
    exit;
}

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Newsletter: <?php echo htmlspecialchars($nl['subiect']); ?></h1>
        <a href="rapoarte.php" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Înapoi la Rapoarte">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2" aria-hidden="true"></i>
            Înapoi la Rapoarte
        </a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-4xl">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6 text-sm">
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Subiect</dt>
                    <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($nl['subiect']); ?></dd>
                </div>
                <?php if (!empty($nl['nume_expeditor'])): ?>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Expeditor</dt>
                    <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($nl['nume_expeditor']); ?></dd>
                </div>
                <?php endif; ?>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Categoria contacte</dt>
                    <dd class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($nl['categoria_contacte']); ?></dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Număr destinatari</dt>
                    <dd class="text-slate-900 dark:text-white"><?php echo (int)$nl['nr_recipienti']; ?></dd>
                </div>
                <?php if ($nl['data_trimiterii']): ?>
                <div>
                    <dt class="font-medium text-slate-500 dark:text-gray-400">Data trimiterii</dt>
                    <dd class="text-slate-900 dark:text-white"><?php echo date(DATETIME_FORMAT, strtotime($nl['data_trimiterii'])); ?></dd>
                </div>
                <?php endif; ?>
            </dl>
            <div class="border-t border-slate-200 dark:border-gray-600 pt-4">
                <h2 class="sr-only">Conținut newsletter</h2>
                <div class="prose dark:prose-invert max-w-none newsletter-content">
                    <?php echo $nl['continut']; ?>
                </div>
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
