<?php
/**
 * Log activitate utilizatori - CRM ANR
 * Afișează istoricul acțiunilor: modificări date, mesaje, documente, sarcini
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';
include 'header.php';
include 'sidebar.php';

$logs = [];
$eroare_bd = '';

try {
    $stmt = $pdo->query('SELECT id, data_ora, utilizator, actiune FROM log_activitate ORDER BY data_ora DESC LIMIT 500');
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $eroare_bd = 'Tabelul log_activitate nu există. Rulați schema_log_activitate.sql în baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '.';
}
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Log activitate</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 dark:border-amber-500 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <p><?php echo htmlspecialchars($eroare_bd); ?></p>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Log activitate utilizatori">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Data / Ora</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Utilizator</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Acțiune</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-600 dark:text-gray-400">Nu există înregistrări în log.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-gray-300">
                                <?php echo date(DATETIME_FORMAT, strtotime($log['data_ora'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-gray-100">
                                <?php echo htmlspecialchars($log['utilizator']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($log['actiune']); ?>
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
