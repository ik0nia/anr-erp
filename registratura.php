<?php
/**
 * Registratura - Management înregistrări documente
 * Tabel cu paginare, dropdown per-page, rânduri clickable -> edit
 */
require_once __DIR__ . '/config.php';
require_once 'includes/registratura_helper.php';

$eroare_bd = '';
$inregistrari = [];
$total = 0;
$per_page = (int)($_GET['per_page'] ?? 25);
if (!in_array($per_page, [10, 25, 50])) $per_page = 25;

$page = max(1, (int)($_GET['page'] ?? 1));

try {
    ensure_registratura_table($pdo);

    $stmt = $pdo->query('SELECT COUNT(*) as n FROM registratura');
    $total = (int) $stmt->fetch()['n'];

    $offset = ($page - 1) * $per_page;
    $stmt = $pdo->prepare('SELECT * FROM registratura ORDER BY data_ora DESC, id DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $inregistrari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eroare_bd = 'Eroare la încărcarea înregistrărilor. Verificați dacă tabelul registratura există în baza de date.';
} catch (Throwable $e) {
    $eroare_bd = 'Eroare la inițializare. Verificați configurația bazei de date.';
}

$total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Management Registratura</h1>
        <div class="flex items-center gap-4">
            <a href="registratura-adauga.php?redirect=registratura" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Adaugă înregistrare în registratură">
                <i data-lucide="plus" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                Adaugă înregistrare
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (isset($_GET['succes'])): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            Înregistrarea a fost actualizată cu succes.
        </div>
        <?php endif; ?>
        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert">
            <?php echo htmlspecialchars($eroare_bd); ?>
        </div>
        <?php else: ?>

        <div class="mb-4 flex flex-wrap items-center gap-4">
            <form method="get" class="flex items-center gap-2">
                <label for="per-page" class="text-sm font-medium text-slate-800 dark:text-gray-200">Afișează</label>
                <select id="per-page" name="per_page" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500" aria-label="Număr înregistrări per pagină">
                    <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50</option>
                </select>
                <span class="text-sm text-slate-600 dark:text-gray-400">per pagină</span>
            </form>
            <span class="text-sm text-slate-600 dark:text-gray-400">Total: <?php echo $total; ?> înregistrări</span>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Registratura documente – înregistrări primite și generate">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nr. înregistrare intern</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nr. și data doc.</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Provine din</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Conținut</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Destinatar</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Operator</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Task</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($inregistrari)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu există înregistrări. <a href="registratura-adauga.php?redirect=registratura" class="text-amber-600 dark:text-amber-400 hover:underline">Adaugă prima înregistrare</a></td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($inregistrari as $r): ?>
                        <tr role="button" tabindex="0" onclick="window.location.href='registratura-edit.php?id=<?php echo (int)$r['id']; ?>'" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); window.location.href='registratura-edit.php?id=<?php echo (int)$r['id']; ?>'; }"
                            class="hover:bg-slate-50 dark:hover:bg-gray-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset"
                            aria-label="Editează înregistrarea nr. <?php echo htmlspecialchars($r['nr_inregistrare']); ?>">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($r['nr_inregistrare'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo date(DATE_FORMAT, strtotime($r['data_ora'])); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($r['nr_document'] ?? '-'); ?> <?php echo !empty($r['data_document']) ? '/ ' . date(DATE_FORMAT, strtotime($r['data_document'])) : ''; ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($r['provine_din'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 max-w-xs truncate" title="<?php echo htmlspecialchars($r['continut_document'] ?? ''); ?>"><?php echo htmlspecialchars($r['continut_document'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($r['destinatar_document'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($r['utilizator'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm"><?php echo !empty($r['task_deschis']) ? '<span class="inline-flex px-2 py-0.5 text-xs rounded bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200">Da</span>' : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="px-4 py-3 border-t border-slate-200 dark:border-gray-700 flex items-center justify-between" aria-label="Paginare">
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&per_page=<?php echo $per_page; ?>" class="px-3 py-1 rounded border border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300" aria-label="Pagina anterioară">←</a>
                    <?php endif; ?>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>" class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-amber-600 text-white' : 'border border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300'; ?>" aria-label="Pagina <?php echo $i; ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&per_page=<?php echo $per_page; ?>" class="px-3 py-1 rounded border border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300" aria-label="Pagina următoare">→</a>
                    <?php endif; ?>
                </div>
                <span class="text-sm text-slate-600 dark:text-gray-400">Pagina <?php echo $page; ?> din <?php echo $total_pages; ?></span>
            </nav>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>lucide.createIcons();</script>
</body>
</html>
