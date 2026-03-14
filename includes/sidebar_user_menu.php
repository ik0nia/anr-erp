<?php
/**
 * Bloc meniu utilizator în sidebar (jos, stânga): buton utilizator cu submeniu (Schimbă parolă, Logout).
 * Include și modalul pentru formularul Schimbă parolă.
 */
if (empty($_SESSION['user_id']) || empty($_SESSION['utilizator'])) {
    return;
}
$utilizator_nume = $_SESSION['utilizator'];
$username = $_SESSION['username'] ?? $utilizator_nume;
$schimba_eroare = $_SESSION['schimba_parola_eroare'] ?? '';
$schimba_succes = $_SESSION['schimba_parola_succes'] ?? '';
unset($_SESSION['schimba_parola_eroare'], $_SESSION['schimba_parola_succes']);
$redirect_uri = htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php');
?>
<div class="<?php echo empty($sidebar_user_in_topbar) && empty($sidebar_user_icon_only) ? 'border-t border-slate-700 dark:border-slate-600 pt-3 mt-2' : ''; ?><?php echo !empty($sidebar_user_icon_only) ? ' flex-1 min-w-0' : ''; ?>" role="region" aria-label="Meniu cont utilizator">
    <div class="relative w-full" id="user-menu-container">
        <button type="button" id="user-menu-toggle" 
                class="<?php echo !empty($sidebar_user_icon_only) ? 'flex items-center justify-center w-full p-3 rounded-lg hover:bg-slate-800 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 dark:focus:ring-offset-slate-800 transition' : 'flex items-center justify-center w-full gap-3 p-3 rounded-lg bg-slate-800 dark:bg-slate-700 text-white hover:bg-slate-700 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-amber-500 transition'; ?>"
                aria-label="Deschide meniul contului (<?php echo htmlspecialchars($utilizator_nume); ?>)"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="user-menu-dropdown">
            <i data-lucide="user" class="w-5 h-5 flex-shrink-0" aria-hidden="true"></i>
            <?php if (empty($sidebar_user_icon_only)): ?>
            <span class="text-sm font-medium truncate"><?php echo htmlspecialchars($utilizator_nume); ?></span>
            <i data-lucide="chevron-down" class="w-4 h-4 ml-auto flex-shrink-0" aria-hidden="true"></i>
            <?php endif; ?>
        </button>
        <div id="user-menu-dropdown" 
             class="hidden absolute bottom-full left-0 right-0 mb-1 w-full rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 py-2 z-50"
             role="menu"
             aria-orientation="vertical"
             aria-labelledby="user-menu-toggle">
            <div class="px-4 py-2 border-b border-slate-200 dark:border-gray-600" role="none">
                <p class="text-sm font-medium text-slate-900 dark:text-white truncate" id="user-menu-username"><?php echo htmlspecialchars($username); ?></p>
                <p class="text-xs text-slate-500 dark:text-gray-400 truncate" aria-hidden="true"><?php echo htmlspecialchars($utilizator_nume); ?></p>
            </div>
            <button type="button" id="user-menu-schimba-parola" 
                    class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-700 focus:bg-slate-100 dark:focus:bg-gray-700 focus:outline-none flex items-center gap-2"
                    role="menuitem">
                <i data-lucide="key" class="w-4 h-4 flex-shrink-0" aria-hidden="true"></i>
                Schimbă parola
            </button>
            <a href="logout.php" 
               class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-700 focus:bg-slate-100 dark:focus:bg-gray-700 focus:outline-none border-t border-slate-200 dark:border-gray-600 mt-1 pt-2"
               role="menuitem">
                <i data-lucide="log-out" class="w-4 h-4 flex-shrink-0" aria-hidden="true"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/header_user_menu_modal.php'; ?>
