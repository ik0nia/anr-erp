<?php
/**
 * Contacte - Management contacte (membri, instituții, voluntari, donatori, etc.)
 */
require_once __DIR__ . '/config.php';
require_once 'includes/contacte_helper.php';
require_once 'includes/log_helper.php';

ensure_contacte_table($pdo);

// Procesare ștergere
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sterge_contact'])) {
    csrf_require_valid();
    $id = (int)($_POST['contact_id'] ?? 0);
    $redirect_tab = isset($_POST['redirect_tab']) && $_POST['redirect_tab'] !== '' ? $_POST['redirect_tab'] : null;
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT nume, prenume FROM contacte WHERE id = ?');
            $stmt->execute([$id]);
            $c = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($c) {
                $pdo->prepare('DELETE FROM contacte WHERE id = ?')->execute([$id]);
                log_activitate($pdo, 'contacte: Șters contact ' . trim($c['nume'] . ' ' . ($c['prenume'] ?? '')));
            }
        } catch (PDOException $e) {}
    }
    $url = 'contacte.php';
    if ($redirect_tab) $url .= '?tab=' . urlencode($redirect_tab);
    header('Location: ' . $url);
    exit;
}

$eroare_bd = '';
$contacte = [];
$total = 0;
$per_page = (int)($_GET['per_page'] ?? 25);
if (!in_array($per_page, [10, 25, 50])) $per_page = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$cautare = trim($_GET['cautare'] ?? '');
$tab = $_GET['tab'] ?? 'toate';
$tipuri = get_contacte_tipuri();

// Validare tab
if ($tab !== 'toate' && !isset($tipuri[$tab])) $tab = 'toate';

try {
    $where = [];
    $params = [];
    if (!empty($cautare)) {
        $term = '%' . $cautare . '%';
        $where[] = '(c.nume LIKE ? OR c.prenume LIKE ? OR c.companie LIKE ? OR c.telefon LIKE ? OR c.telefon_personal LIKE ? OR c.email LIKE ? OR c.email_personal LIKE ? OR c.notite LIKE ? OR c.referinta_contact LIKE ?)';
        for ($i = 0; $i < 9; $i++) $params[] = $term;
    }
    if ($tab !== 'toate') {
        $where[] = 'c.tip_contact = ?';
        $params[] = $tab;
    }
    $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) as n FROM contacte c" . $where_sql);
    $stmt->execute($params);
    $total = (int) $stmt->fetch()['n'];

    $offset = ($page - 1) * $per_page;
    $limit = (int) $per_page;
    $offset_int = (int) $offset;
    $stmt = $pdo->prepare("SELECT * FROM contacte c" . $where_sql . " ORDER BY c.nume, c.prenume LIMIT " . $limit . " OFFSET " . $offset_int);
    $stmt->execute($params);
    $contacte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eroare_bd = 'Eroare la încărcare.';
}

// Număr contacte per tip (pentru taburi)
$counts = ['toate' => 0];
try {
    $stmt = $pdo->query('SELECT COUNT(*) as n FROM contacte');
    $counts['toate'] = (int) $stmt->fetch()['n'];
    foreach ($tipuri as $k => $v) {
        $stmt = $pdo->prepare('SELECT COUNT(*) as n FROM contacte WHERE tip_contact = ?');
        $stmt->execute([$k]);
        $counts[$k] = (int) $stmt->fetch()['n'];
    }
} catch (PDOException $e) {}

$total_pages = $total > 0 ? (int) ceil($total / $per_page) : 1;
$tipuri_ordered = ['toate' => 'Toate'] + $tipuri;

function build_contacte_url($params = []) {
    $p = array_merge($_GET, $params);
    $p['page'] = $p['page'] ?? 1;
    return 'contacte.php?' . http_build_query($p);
}

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Contacte</h1>
        <div class="flex items-center gap-3">
            <a href="contacte-adauga.php" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Adaugă contact">
                <i data-lucide="plus" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                Adaugă
            </a>
            <a href="contacte-import.php" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Import din Excel/CSV">
                <i data-lucide="upload" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                Import
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (isset($_GET['succes'])): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            Contactul a fost salvat cu succes.
        </div>
        <?php endif; ?>
        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert"><?php echo htmlspecialchars($eroare_bd); ?></div>
        <?php else: ?>

        <!-- Taburi -->
        <div class="mb-4 overflow-x-auto" role="tablist" aria-label="Categorii contacte">
            <div class="flex gap-1 border-b border-slate-200 dark:border-gray-700 pb-2 min-w-max">
                <?php foreach ($tipuri_ordered as $k => $label): ?>
                <a href="<?php echo build_contacte_url(['tab' => $k, 'page' => 1]); ?>"
                   class="px-4 py-2 rounded-t-lg text-sm font-medium whitespace-nowrap <?php echo $tab === $k ? 'bg-amber-600 text-white' : 'bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-gray-300 hover:bg-slate-200 dark:hover:bg-gray-600'; ?>"
                   role="tab" aria-selected="<?php echo $tab === $k ? 'true' : 'false'; ?>"
                   aria-label="<?php echo htmlspecialchars($label); ?> (<?php echo $counts[$k] ?? 0; ?>)">
                    <?php echo htmlspecialchars($label); ?>
                    <span class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 text-xs rounded-full <?php echo $tab === $k ? 'bg-amber-500/80' : 'bg-slate-300 dark:bg-gray-600'; ?>"><?php echo $counts[$k] ?? 0; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Căutare -->
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <form method="get" class="flex items-center gap-2 flex-1 min-w-0">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                <input type="hidden" name="per_page" value="<?php echo $per_page; ?>">
                <label for="cautare-contacte" class="sr-only">Caută contacte</label>
                <input type="search" id="cautare-contacte" name="cautare" value="<?php echo htmlspecialchars($cautare); ?>"
                       placeholder="Caută în contacte..."
                       class="flex-1 min-w-0 max-w-xs px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-amber-500">
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Caută">
                    <i data-lucide="search" class="w-4 h-4 inline" aria-hidden="true"></i>
                </button>
            </form>
            <span class="text-sm text-slate-600 dark:text-gray-400">Total: <?php echo $total; ?> contacte</span>
        </div>

        <!-- Tabel -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table id="tabel-contacte" class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Listă contacte">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="nume_complet">
                                <div class="flex items-center justify-between">
                                    <span>Nume complet</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="companie">
                                <div class="flex items-center justify-between">
                                    <span>Companie</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="telefon">
                                <div class="flex items-center justify-between">
                                    <span>Telefon</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="telefon_personal">
                                <div class="flex items-center justify-between">
                                    <span>Telefon personal</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="email">
                                <div class="flex items-center justify-between">
                                    <span>Email</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="email_personal">
                                <div class="flex items-center justify-between">
                                    <span>Email personal</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase resizable-th" data-col="actiuni">
                                <div class="flex items-center justify-between">
                                    <span>Acțiuni</span>
                                    <div class="resize-handle cursor-col-resize w-1 h-full bg-transparent hover:bg-amber-400" aria-hidden="true" role="presentation"></div>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($contacte)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">
                                Nu există contacte. <a href="contacte-adauga.php" class="text-amber-600 dark:text-amber-400 hover:underline">Adaugă primul contact</a>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($contacte as $c): 
                            $nume_complet = trim(($c['nume'] ?? '') . ' ' . ($c['prenume'] ?? ''));
                            $email_princ = $c['email'] ?? $c['email_personal'] ?? '';
                            $whatsapp_url = contacte_whatsapp_url($c['telefon'] ?? $c['telefon_personal'] ?? '');
                        ?>
                        <?php $edit_url = 'contacte-edit.php?id=' . (int)$c['id']; ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-left">
                                <a href="<?php echo htmlspecialchars($edit_url); ?>" class="font-medium text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">
                                    <?php echo htmlspecialchars($nume_complet); ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-left">
                                <a href="<?php echo htmlspecialchars($edit_url); ?>" class="text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:underline"><?php echo htmlspecialchars($c['companie'] ?? '-'); ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-left">
                                <a href="<?php echo htmlspecialchars($edit_url); ?>" class="text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:underline"><?php echo htmlspecialchars($c['telefon'] ?? '-'); ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-left">
                                <a href="<?php echo htmlspecialchars($edit_url); ?>" class="text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:underline"><?php echo htmlspecialchars($c['telefon_personal'] ?? '-'); ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-left">
                                <a href="<?php echo htmlspecialchars($edit_url); ?>" class="text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:underline"><?php echo htmlspecialchars($c['email'] ?? '-'); ?></a>
                            </td>
                            <td class="px-4 py-3 text-sm text-left">
                                <a href="<?php echo htmlspecialchars($edit_url); ?>" class="text-slate-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 hover:underline"><?php echo htmlspecialchars($c['email_personal'] ?? '-'); ?></a>
                            </td>
                            <td class="px-4 py-3 text-left">
                                <div class="flex items-center justify-start gap-2 flex-nowrap whitespace-nowrap">
                                    <?php if (!empty($email_princ)): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($email_princ); ?>" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-100 dark:bg-blue-900/50 hover:bg-blue-200 dark:hover:bg-blue-800/70 text-blue-800 dark:text-blue-200 text-sm shrink-0 font-medium" aria-label="Trimite email către <?php echo htmlspecialchars($nume_complet); ?>">
                                        <i data-lucide="mail" class="w-4 h-4" aria-hidden="true"></i>
                                        <span>Email</span>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($whatsapp_url): ?>
                                    <a href="<?php echo htmlspecialchars($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-green-100 dark:bg-green-900/50 hover:bg-green-200 dark:hover:bg-green-800/70 text-green-800 dark:text-green-200 text-sm shrink-0 font-medium" aria-label="Mesaj WhatsApp către <?php echo htmlspecialchars($nume_complet); ?>">
                                        <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                                        <span>WhatsApp</span>
                                    </a>
                                    <?php endif; ?>
                                    <div class="relative inline-block contacte-dropdown shrink-0">
                                        <button type="button" class="p-2 rounded hover:bg-slate-200 dark:hover:bg-gray-600 text-slate-600 dark:text-gray-300 contacte-menu-btn" aria-haspopup="true" aria-expanded="false" aria-label="Mai multe opțiuni" data-contact-id="<?php echo (int)$c['id']; ?>">
                                            <i data-lucide="more-vertical" class="w-4 h-4" aria-hidden="true"></i>
                                        </button>
                                        <div class="hidden absolute right-0 mt-1 w-48 py-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-slate-200 dark:border-gray-700 z-10 contacte-menu-panel" role="menu">
                                            <form method="post" action="contacte.php" onsubmit="return confirm('Ștergeți acest contact?');" class="block">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="sterge_contact" value="1">
                                                <input type="hidden" name="contact_id" value="<?php echo (int)$c['id']; ?>">
                                                <input type="hidden" name="redirect_tab" value="<?php echo htmlspecialchars($tab); ?>">
                                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-gray-700" role="menuitem" aria-label="Șterge contactul: <?php echo htmlspecialchars(trim($c['nume'] . ' ' . ($c['prenume'] ?? ''))); ?>">
                                                    <i data-lucide="trash-2" class="w-4 h-4 inline mr-2" aria-hidden="true"></i>
                                                    Șterge contactul
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginare și mod afișare - partea de jos -->
            <div class="px-4 py-3 border-t border-slate-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <nav class="flex items-center gap-2" aria-label="Paginare">
                        <?php if ($total_pages > 1): ?>
                        <?php if ($page > 1): ?>
                        <a href="<?php echo build_contacte_url(['page' => $page - 1]); ?>" class="px-3 py-1 rounded border border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300" aria-label="Pagina anterioară">←</a>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2), $end = min($total_pages, $page + 2); $i <= $end; $i++): ?>
                        <a href="<?php echo build_contacte_url(['page' => $i]); ?>" class="px-3 py-1 rounded <?php echo $i === $page ? 'bg-amber-600 text-white' : 'border border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300'; ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <a href="<?php echo build_contacte_url(['page' => $page + 1]); ?>" class="px-3 py-1 rounded border border-slate-300 dark:border-gray-600 hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300" aria-label="Pagina următoare">→</a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </nav>
                    <span class="text-sm text-slate-600 dark:text-gray-400">Pagina <?php echo $page; ?> din <?php echo $total_pages; ?></span>
                </div>
                <form method="get" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <input type="hidden" name="cautare" value="<?php echo htmlspecialchars($cautare); ?>">
                    <label for="per-page-contacte" class="text-sm font-medium text-slate-800 dark:text-gray-200">Afișează</label>
                    <select id="per-page-contacte" name="per_page" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" aria-label="Număr contacte per pagină">
                        <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50</option>
                    </select>
                    <span class="text-sm text-slate-600 dark:text-gray-400">per pagină</span>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<style>
.resizable-th {
    position: relative;
    user-select: none;
}
.resize-handle {
    position: absolute;
    right: 0;
    top: 0;
    width: 4px;
    height: 100%;
    cursor: col-resize;
    z-index: 10;
}
.resize-handle:hover {
    background-color: #f59e0b;
}
.resizable-th:last-child .resize-handle {
    display: none;
}
/* Forțează alinierea la stânga pentru toate celulele din tabelul de contacte */
#tabel-contacte td {
    text-align: left !important;
}
#tabel-contacte td a {
    text-align: left !important;
    display: inline-block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    document.querySelectorAll('.contacte-menu-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var panel = this.closest('.contacte-dropdown').querySelector('.contacte-menu-panel');
            document.querySelectorAll('.contacte-menu-panel').forEach(function(p) {
                if (p !== panel) p.classList.add('hidden');
            });
            panel.classList.toggle('hidden');
            this.setAttribute('aria-expanded', panel.classList.contains('hidden') ? 'false' : 'true');
        });
    });
    document.addEventListener('click', function() {
        document.querySelectorAll('.contacte-menu-panel').forEach(function(p) { p.classList.add('hidden'); });
        document.querySelectorAll('.contacte-menu-btn').forEach(function(b) { b.setAttribute('aria-expanded', 'false'); });
    });
    
    // Drag and drop pentru redimensionarea coloanelor
    const table = document.getElementById('tabel-contacte');
    if (table) {
        const headers = table.querySelectorAll('.resizable-th');
        let currentResize = null;
        
        headers.forEach((header, index) => {
            const handle = header.querySelector('.resize-handle');
            if (!handle) return;
            
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                currentResize = {
                    header: header,
                    startX: e.clientX,
                    startWidth: header.offsetWidth,
                    index: index
                };
                document.addEventListener('mousemove', resizeColumn);
                document.addEventListener('mouseup', stopResize);
                handle.style.backgroundColor = '#f59e0b';
            });
        });
        
        function resizeColumn(e) {
            if (!currentResize) return;
            
            const diff = e.clientX - currentResize.startX;
            const newWidth = Math.max(50, currentResize.startWidth + diff);
            currentResize.header.style.width = newWidth + 'px';
            currentResize.header.style.minWidth = newWidth + 'px';
            
            // Salvează lățimea în localStorage
            const colName = currentResize.header.getAttribute('data-col');
            localStorage.setItem('contacte_col_width_' + colName, newWidth);
        }
        
        function stopResize() {
            if (currentResize) {
                const handle = currentResize.header.querySelector('.resize-handle');
                if (handle) handle.style.backgroundColor = '';
            }
            currentResize = null;
            document.removeEventListener('mousemove', resizeColumn);
            document.removeEventListener('mouseup', stopResize);
        }
        
        // Restaurează lățimile salvate
        headers.forEach(header => {
            const colName = header.getAttribute('data-col');
            const savedWidth = localStorage.getItem('contacte_col_width_' + colName);
            if (savedWidth) {
                header.style.width = savedWidth + 'px';
                header.style.minWidth = savedWidth + 'px';
            }
        });
    }
});
</script>
</body>
</html>
