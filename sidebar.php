<?php
$notificari_necitate_count = 0;
if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/includes/notificari_helper.php';
    $notificari_necitate_count = notificari_count_necitate($pdo, (int)$_SESSION['user_id']);
}
?>
<aside id="navigation" class="w-64 lg:w-64 bg-slate-900 dark:bg-slate-800 text-white flex flex-col shrink-0" role="navigation" aria-label="Navigație principală">
    <div class="p-4 flex flex-col items-center border-b border-slate-700 dark:border-slate-600">
        <a href="index.php" class="block focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 rounded" aria-label="Mergi la Dashboard">
            <img src="<?php echo defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''; ?>" 
                 alt="Logo <?php echo htmlspecialchars(get_platform_name()); ?>" 
                 class="h-16 w-auto object-contain mb-3"
                 width="128"
                 height="64">
        </a>
        <h2 class="text-xl font-bold text-center mb-2 w-full"><?php echo htmlspecialchars(get_platform_name()); ?></h2>
        <?php if ($notificari_necitate_count > 0): ?>
        <div class="w-full mb-2" role="region" aria-label="Notificări necitite">
            <a href="notificari.php" class="flex items-center justify-center gap-2 w-full px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900"
               aria-label="Notificări necitite! Mergi la Notificări">
                <i data-lucide="bell" class="w-5 h-5 flex-shrink-0" aria-hidden="true"></i>
                <span class="whitespace-nowrap">Notificări necitite! (<?php echo $notificari_necitate_count; ?>)</span>
            </a>
        </div>
        <?php else: ?>
        <div class="w-full mb-2">
            <a href="notificari.php" class="flex items-center justify-center w-full px-3 py-1.5 rounded-lg shrink-0 hover:bg-slate-800 dark:hover:bg-slate-700 transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900"
               aria-label="Mergi la Notificări">
                <i data-lucide="bell" class="w-5 h-5" aria-hidden="true"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <nav class="flex-1 p-4 space-y-0.5 overflow-y-auto text-center" aria-label="Meniu module">
        <a href="index.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Dashboard">
            <i data-lucide="layout-dashboard" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Dashboard
        </a>
        <a href="membri.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Management Membri">
            <i data-lucide="users" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Membri
        </a>
        <a href="registru-interactiuni-v2.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Registru Interacțiuni">
            <i data-lucide="phone-call" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Registru Interacțiuni
        </a>
        <a href="registratura.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Registratura">
            <i data-lucide="file-text" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Registratura
        </a>
        <a href="voluntariat.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Voluntariat">
            <i data-lucide="heart-handshake" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Voluntariat
        </a>
        <a href="ajutoare-bpa.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Ajutoare BPA">
            <i data-lucide="package" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Ajutoare BPA
        </a>
        <a href="activitati.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Activități">
            <i data-lucide="activity" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Activitati
        </a>
        <a href="fundraising.php" class="flex items-center justify-center py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" aria-label="Mergi la Fundraising">
            <i data-lucide="hand-coins" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Fundraising
        </a>
        <div class="sidebar-submenu" role="group" aria-label="Meniu Administrativ">
            <button type="button" class="sidebar-submenu-trigger flex items-center justify-center w-full py-2.5 px-3 hover:bg-slate-700 dark:hover:bg-slate-600 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800 text-center" aria-expanded="false" aria-controls="sidebar-submenu-panel" id="sidebar-submenu-btn" aria-label="Deschide meniul Administrativ">
                <span class="flex items-center">
                    <i data-lucide="briefcase" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Administrativ
                </span>
                <i data-lucide="chevron-down" class="w-5 h-5 shrink-0 sidebar-submenu-chevron transition-transform ml-1" aria-hidden="true"></i>
            </button>
            <div id="sidebar-submenu-panel" class="sidebar-submenu-panel hidden overflow-hidden" role="region" aria-labelledby="sidebar-submenu-btn">
                <div class="pl-8 pr-2 py-1 space-y-0.5 border-l-2 border-slate-600 dark:border-slate-500 ml-3 text-center">
                    <a href="administrativ.php" class="flex items-center justify-center py-2 px-2 hover:bg-slate-700 dark:hover:bg-slate-600 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset" aria-label="Mergi la Modul Administrativ"><i data-lucide="briefcase" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Administrativ</a>
                    <a href="todo.php" class="flex items-center justify-center py-2 px-2 hover:bg-slate-700 dark:hover:bg-slate-600 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset" aria-label="Mergi la Taskuri"><i data-lucide="list-checks" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Taskuri</a>
                    <a href="librarie-documente.php" class="flex items-center justify-center py-2 px-2 hover:bg-slate-700 dark:hover:bg-slate-600 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset" aria-label="Mergi la Librărie documente"><i data-lucide="library" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Librărie documente</a>
                    <a href="contacte.php" class="flex items-center justify-center py-2 px-2 hover:bg-slate-700 dark:hover:bg-slate-600 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset" aria-label="Mergi la Contacte"><i data-lucide="book-open" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Contacte</a>
                    <a href="formular-230.php" class="flex items-center justify-center py-2 px-2 hover:bg-slate-700 dark:hover:bg-slate-600 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset" aria-label="Mergi la Formular 230"><i data-lucide="percent" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Formular 230</a>
                    <a href="rapoarte.php" class="flex items-center justify-center py-2 px-2 hover:bg-slate-700 dark:hover:bg-slate-600 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset" aria-label="Mergi la Rapoarte"><i data-lucide="bar-chart-2" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Rapoarte</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="p-4 border-t border-slate-700 dark:border-slate-600">
        <div class="flex gap-2">
        <button id="theme-toggle" 
                class="flex items-center justify-center py-2.5 px-3 flex-1 hover:bg-slate-800 dark:hover:bg-slate-700 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" 
                aria-label="Schimbă tema">
            <i data-lucide="lightbulb" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
        </button>
        <a href="log-activitate.php" 
           class="flex items-center justify-center py-2.5 px-3 flex-1 hover:bg-slate-800 dark:hover:bg-slate-700 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" 
           aria-label="Vizualizează log activitate">
            <i data-lucide="scroll-text" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
        </a>
        <a href="setari.php" 
           class="flex items-center justify-center py-2.5 px-3 flex-1 hover:bg-slate-800 dark:hover:bg-slate-700 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800" 
           aria-label="Mergi la Setări">
            <i data-lucide="settings" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
        </a>
        <?php if (!empty($_SESSION['user_id'])) { $sidebar_user_icon_only = true; include __DIR__ . '/includes/sidebar_user_menu.php'; } ?>
        </div>
    </div>
    <script>
    (function() {
        var btn = document.getElementById('sidebar-submenu-btn');
        var panel = document.getElementById('sidebar-submenu-panel');
        var chevron = document.querySelector('.sidebar-submenu-chevron');
        if (btn && panel) {
            btn.addEventListener('click', function() {
                var open = panel.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', open ? 'false' : 'true');
                if (chevron) chevron.style.transform = open ? '' : 'rotate(180deg)';
            });
        }
    })();
    </script>
</aside>