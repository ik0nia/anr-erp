<?php
/**
 * Meniu utilizator în header (dreapta sus): icon portret, submeniu cu Nume utilizator, Schimbă parolă, Logout.
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
<div id="header-user-menu-wrap" class="fixed top-0 right-0 z-50 pt-3 pr-2 sm:pr-4 flex items-center gap-2" role="region" aria-label="Meniu cont utilizator" style="min-width: 3rem; max-width: 4rem;">
    <div class="relative" id="user-menu-container">
        <button type="button" id="user-menu-toggle" 
                class="flex items-center justify-center w-11 h-11 sm:w-10 sm:h-10 rounded-full bg-slate-200 dark:bg-gray-600 text-slate-700 dark:text-gray-200 hover:bg-amber-100 dark:hover:bg-amber-900/40 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 transition touch-manipulation"
                aria-label="Deschide meniul contului"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="user-menu-dropdown">
            <i data-lucide="user" class="w-5 h-5" aria-hidden="true"></i>
        </button>
        <div id="user-menu-dropdown" 
             class="hidden absolute right-0 mt-2 w-64 sm:w-56 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 py-2 z-50"
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
            <a href="/logout" 
               class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-700 focus:bg-slate-100 dark:focus:bg-gray-700 focus:outline-none border-t border-slate-200 dark:border-gray-600 mt-1 pt-2"
               role="menuitem">
                <i data-lucide="log-out" class="w-4 h-4 flex-shrink-0" aria-hidden="true"></i>
                Logout
            </a>
        </div>
    </div>
</div>

<!-- Modal Schimbă parolă -->
<dialog id="modal-schimba-parola" role="dialog" aria-modal="true" aria-labelledby="modal-schimba-parola-title" aria-describedby="modal-schimba-parola-desc"
        class="p-0 rounded-lg shadow-xl max-w-md w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="modal-schimba-parola-title" class="text-lg font-bold text-slate-900 dark:text-white mb-2">Schimbă parola</h2>
        <p id="modal-schimba-parola-desc" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Introduceți parola actuală și parola nouă (minim 6 caractere).</p>
        <?php if ($schimba_eroare !== ''): ?>
        <div class="mb-4 p-3 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r text-sm" role="alert"><?php echo htmlspecialchars($schimba_eroare); ?></div>
        <?php endif; ?>
        <?php if ($schimba_succes !== ''): ?>
        <div class="mb-4 p-3 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-800 dark:text-emerald-200 rounded-r text-sm" role="status"><?php echo htmlspecialchars($schimba_succes); ?></div>
        <?php endif; ?>
        <form method="post" action="/schimba-parola" id="form-schimba-parola">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo $redirect_uri; ?>">
            <div class="space-y-4">
                <div>
                    <label for="parola_actuala" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parola actuală <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="parola_actuala" name="parola_actuala" required autocomplete="current-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="parola_noua" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parola nouă <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="parola_noua" name="parola_noua" required minlength="6" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true" aria-describedby="parola-noua-desc">
                    <p id="parola-noua-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Minim 6 caractere.</p>
                </div>
                <div>
                    <label for="parola_noua2" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parola nouă (confirmare) <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="parola_noua2" name="parola_noua2" required minlength="6" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-inchide-schimba-parola" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulare">Anulare</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Schimbă parola">Schimbă parola</button>
            </div>
        </form>
    </div>
</dialog>

<script>
(function() {
    var toggle = document.getElementById('user-menu-toggle');
    var dropdown = document.getElementById('user-menu-dropdown');
    var container = document.getElementById('user-menu-container');
    var btnSchimba = document.getElementById('user-menu-schimba-parola');
    var modalSchimba = document.getElementById('modal-schimba-parola');
    var btnInchideSchimba = document.getElementById('btn-inchide-schimba-parola');

    function closeDropdown() {
        if (dropdown) {
            dropdown.classList.add('hidden');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }
    }

    if (toggle && dropdown) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            var isOpen = !dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });
        document.addEventListener('click', function() { closeDropdown(); });
        container.addEventListener('click', function(e) { e.stopPropagation(); });
        dropdown.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDropdown();
        });
    }

    if (btnSchimba && modalSchimba) {
        btnSchimba.addEventListener('click', function() {
            closeDropdown();
            modalSchimba.showModal();
            document.getElementById('parola_actuala').focus();
        });
    }
    if (btnInchideSchimba && modalSchimba) {
        btnInchideSchimba.addEventListener('click', function() { modalSchimba.close(); });
    }
    if (modalSchimba) {
        modalSchimba.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') this.close();
        });
    }

    var deschideParam = new URLSearchParams(window.location.search).get('deschide_schimba_parola');
    if (deschideParam && modalSchimba) {
        modalSchimba.showModal();
        if (document.getElementById('parola_actuala')) document.getElementById('parola_actuala').focus();
    }
})();
</script>
