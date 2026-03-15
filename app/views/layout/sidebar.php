<?php
$notificari_necitate_count = 0;
if (!empty($_SESSION['user_id'])) {
    require_once APP_ROOT . '/includes/notificari_helper.php';
    $notificari_necitate_count = notificari_count_necitate($pdo, (int)$_SESSION['user_id']);
}

// Detectare pagina curenta pentru active state
$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$current_path = '/' . trim($current_path, '/');

function sidebar_link_class($path, $current) {
    $is_active = ($current === $path) || ($path !== '/' && $path !== '/dashboard' && strpos($current, $path) === 0);
    $base = 'flex items-center justify-center py-2.5 px-3 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800';
    if ($is_active) {
        return $base . ' bg-amber-600 text-white';
    }
    return $base . ' hover:bg-slate-700 dark:hover:bg-slate-600';
}

function sidebar_sub_link_class($path, $current) {
    $is_active = ($current === $path) || strpos($current, $path) === 0;
    $base = 'flex items-center justify-center py-2 px-2 rounded text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-inset';
    if ($is_active) {
        return $base . ' bg-amber-600 text-white';
    }
    return $base . ' hover:bg-slate-700 dark:hover:bg-slate-600';
}

// Submeniu deschis daca una din paginile sale e activa
$submenu_pages = ['/administrativ', '/todo', '/librarie-documente', '/contacte', '/formular-230', '/rapoarte'];
$submenu_active = false;
foreach ($submenu_pages as $sp) {
    if ($current_path === $sp || strpos($current_path, $sp) === 0) {
        $submenu_active = true;
        break;
    }
}
?>
<aside id="navigation" class="w-64 lg:w-64 bg-slate-900 dark:bg-slate-800 text-white flex flex-col shrink-0 fixed lg:static inset-y-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-200" role="navigation" aria-label="Navigație principală">
    <div class="p-4 flex flex-col items-center border-b border-slate-700 dark:border-slate-600">
        <a href="/dashboard" class="block focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 rounded" aria-label="Mergi la Dashboard">
            <img src="<?php echo defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''; ?>"
                 alt="Logo <?php echo htmlspecialchars(get_platform_name()); ?>"
                 class="h-16 w-auto object-contain mb-3"
                 width="128"
                 height="64">
        </a>
        <h2 class="text-xl font-bold text-center mb-2 w-full"><?php echo htmlspecialchars(get_platform_name()); ?></h2>
        <?php if ($notificari_necitate_count > 0): ?>
        <div class="w-full mb-2" role="region" aria-label="Notificări necitite">
            <a href="/notificari" class="flex items-center justify-center gap-2 w-full px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white font-bold text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900"
               aria-label="Notificări necitite! Mergi la Notificări">
                <i data-lucide="bell" class="w-5 h-5 flex-shrink-0" aria-hidden="true"></i>
                <span class="whitespace-nowrap">Notificări necitite! (<?php echo $notificari_necitate_count; ?>)</span>
            </a>
        </div>
        <?php else: ?>
        <div class="w-full mb-2">
            <a href="/notificari" class="flex items-center justify-center w-full px-3 py-1.5 rounded-lg shrink-0 hover:bg-slate-800 dark:hover:bg-slate-700 transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900"
               aria-label="Mergi la Notificări">
                <i data-lucide="bell" class="w-5 h-5" aria-hidden="true"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <nav class="flex-1 p-4 space-y-0.5 overflow-y-auto text-center" aria-label="Meniu module">
        <a href="/dashboard" class="<?php echo sidebar_link_class('/dashboard', $current_path === '/' ? '/dashboard' : $current_path); ?>" aria-label="Dashboard">
            <i data-lucide="layout-dashboard" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Dashboard
        </a>
        <a href="/membri" class="<?php echo sidebar_link_class('/membri', $current_path); ?>" aria-label="Membri">
            <i data-lucide="users" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Membri
        </a>
        <a href="/registru-interactiuni" class="<?php echo sidebar_link_class('/registru-interactiuni', $current_path); ?>" aria-label="Registru Interacțiuni">
            <i data-lucide="phone-call" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Registru Interacțiuni
        </a>
        <a href="/registratura" class="<?php echo sidebar_link_class('/registratura', $current_path); ?>" aria-label="Registratura">
            <i data-lucide="file-text" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Registratura
        </a>
        <a href="/voluntariat" class="<?php echo sidebar_link_class('/voluntariat', $current_path); ?>" aria-label="Voluntariat">
            <i data-lucide="heart-handshake" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Voluntariat
        </a>
        <a href="/ajutoare-bpa" class="<?php echo sidebar_link_class('/ajutoare-bpa', $current_path); ?>" aria-label="Ajutoare BPA">
            <i data-lucide="package" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Ajutoare BPA
        </a>
        <a href="/activitati" class="<?php echo sidebar_link_class('/activitati', $current_path); ?>" aria-label="Activități">
            <i data-lucide="activity" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Activitati
        </a>
        <a href="/fundraising" class="<?php echo sidebar_link_class('/fundraising', $current_path); ?>" aria-label="Fundraising">
            <i data-lucide="hand-coins" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Fundraising
        </a>
        <div class="sidebar-submenu" role="group" aria-label="Meniu Administrativ">
            <button type="button" class="sidebar-submenu-trigger flex items-center justify-center w-full py-2.5 px-3 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800 text-center <?php echo $submenu_active ? 'bg-slate-700 dark:bg-slate-600' : 'hover:bg-slate-700 dark:hover:bg-slate-600'; ?>" aria-expanded="<?php echo $submenu_active ? 'true' : 'false'; ?>" aria-controls="sidebar-submenu-panel" id="sidebar-submenu-btn" aria-label="Meniul Administrativ">
                <span class="flex items-center">
                    <i data-lucide="briefcase" class="mr-3 w-5 h-5 shrink-0" aria-hidden="true"></i> Administrativ
                </span>
                <i data-lucide="chevron-down" class="w-5 h-5 shrink-0 sidebar-submenu-chevron transition-transform ml-1" aria-hidden="true" style="<?php echo $submenu_active ? 'transform: rotate(180deg)' : ''; ?>"></i>
            </button>
            <div id="sidebar-submenu-panel" class="sidebar-submenu-panel <?php echo $submenu_active ? '' : 'hidden'; ?> overflow-hidden" role="region" aria-labelledby="sidebar-submenu-btn">
                <div class="pl-8 pr-2 py-1 space-y-0.5 border-l-2 border-slate-600 dark:border-slate-500 ml-3 text-center">
                    <a href="/administrativ" class="<?php echo sidebar_sub_link_class('/administrativ', $current_path); ?>" aria-label="Modul Administrativ"><i data-lucide="briefcase" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Administrativ</a>
                    <a href="/todo" class="<?php echo sidebar_sub_link_class('/todo', $current_path); ?>" aria-label="Taskuri"><i data-lucide="list-checks" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Taskuri</a>
                    <a href="/librarie-documente" class="<?php echo sidebar_sub_link_class('/librarie-documente', $current_path); ?>" aria-label="Librărie documente"><i data-lucide="library" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Librărie documente</a>
                    <a href="/contacte" class="<?php echo sidebar_sub_link_class('/contacte', $current_path); ?>" aria-label="Contacte"><i data-lucide="book-open" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Contacte</a>
                    <a href="/formular-230" class="<?php echo sidebar_sub_link_class('/formular-230', $current_path); ?>" aria-label="Formular 230"><i data-lucide="percent" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Formular 230</a>
                    <a href="/rapoarte" class="<?php echo sidebar_sub_link_class('/rapoarte', $current_path); ?>" aria-label="Rapoarte"><i data-lucide="bar-chart-2" class="mr-2 w-4 h-4 shrink-0" aria-hidden="true"></i>Rapoarte</a>
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
        <a href="/log-activitate"
           class="flex items-center justify-center py-2.5 px-3 flex-1 hover:bg-slate-800 dark:hover:bg-slate-700 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800"
           aria-label="Vizualizează log activitate">
            <i data-lucide="scroll-text" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
        </a>
        <a href="/setari"
           class="flex items-center justify-center py-2.5 px-3 flex-1 hover:bg-slate-800 dark:hover:bg-slate-700 rounded transition focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800"
           aria-label="Mergi la Setări">
            <i data-lucide="settings" class="w-5 h-5 shrink-0" aria-hidden="true"></i>
        </a>
        <?php if (!empty($_SESSION['user_id'])) { $sidebar_user_icon_only = true; include APP_ROOT . '/includes/sidebar_user_menu.php'; } ?>
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

        // Mobile: hamburger toggle
        var sidebar = document.getElementById('navigation');
        var overlay = document.getElementById('mobile-sidebar-overlay');
        var hamburger = document.getElementById('mobile-menu-btn');

        if (hamburger && sidebar) {
            hamburger.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
                if (overlay) overlay.classList.toggle('hidden');
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
    })();
    </script>
</aside>
<!-- Mobile overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>
