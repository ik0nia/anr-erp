/**
 * Mobile Navigation - CRM ANR Bihor
 * Gestionează sidebar-ul și navigația pentru dispozitive mobile
 */

(function() {
    'use strict';

    // Inițializare la încărcare DOM
    document.addEventListener('DOMContentLoaded', function() {
        initMobileSidebar();
        initTouchOptimizations();
        initResponsiveTables();
    });

    /**
     * Inițializare sidebar mobile cu buton hamburger
     */
    function initMobileSidebar() {
        const sidebar = document.querySelector('aside[role="navigation"]');
        if (!sidebar) return;

        // Creează butonul hamburger dacă nu există
        let hamburgerBtn = document.getElementById('mobile-menu-toggle');
        if (!hamburgerBtn) {
            hamburgerBtn = document.createElement('button');
            hamburgerBtn.id = 'mobile-menu-toggle';
            hamburgerBtn.className = 'lg:hidden fixed top-4 left-4 z-50 p-2 bg-slate-900 dark:bg-slate-800 text-white rounded-lg shadow-lg hover:bg-slate-800 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 transition';
            hamburgerBtn.setAttribute('aria-label', 'Deschide meniul');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            hamburgerBtn.innerHTML = '<i data-lucide="menu" class="w-6 h-6"></i>';
            document.body.appendChild(hamburgerBtn);
            
            // Inițializează icon-ul
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
        }

        // Adaugă overlay pentru mobile
        let overlay = document.getElementById('mobile-sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'mobile-sidebar-overlay';
            overlay.className = 'lg:hidden fixed inset-0 bg-black/50 z-40 hidden';
            overlay.setAttribute('aria-hidden', 'true');
            document.body.appendChild(overlay);
        }

        // Modifică sidebar-ul pentru mobile
        sidebar.classList.add('lg:translate-x-0', '-translate-x-full', 'lg:static', 'fixed', 'inset-y-0', 'left-0', 'z-40', 'transition-transform', 'duration-300', 'ease-in-out');

        // Funcții toggle
        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
            hamburgerBtn.setAttribute('aria-expanded', 'true');
            hamburgerBtn.innerHTML = '<i data-lucide="x" class="w-6 h-6"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('translate-x-0');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            hamburgerBtn.innerHTML = '<i data-lucide="menu" class="w-6 h-6"></i>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            document.body.style.overflow = '';
        }

        // Event listeners
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('-translate-x-full')) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });

        overlay.addEventListener('click', closeSidebar);

        // Închide sidebar la click pe link
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Delay pentru a permite navigarea
                setTimeout(closeSidebar, 100);
            });
        });

        // Închide sidebar la Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                closeSidebar();
            }
        });

        // Ajustare layout main pentru mobile
        const main = document.querySelector('main');
        if (main) {
            main.classList.add('lg:ml-0', 'ml-0', 'pt-16', 'lg:pt-0');
        }
    }

    /**
     * Optimizări pentru touch
     */
    function initTouchOptimizations() {
        // Mărește touch targets pentru butoane mici
        const smallButtons = document.querySelectorAll('button, a, input[type="submit"], input[type="button"]');
        smallButtons.forEach(btn => {
            const rect = btn.getBoundingClientRect();
            if (rect.width < 44 || rect.height < 44) {
                btn.classList.add('min-h-[44px]', 'min-w-[44px]', 'flex', 'items-center', 'justify-center');
            }
        });

        // Adaugă padding suplimentar pentru input-uri pe mobile
        if (window.innerWidth < 768) {
            const inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.classList.add('text-base'); // Previne zoom pe iOS
            });
        }

        // Previne double-tap zoom pe iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function(e) {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    }

    /**
     * Tabele responsive cu scroll orizontal
     */
    function initResponsiveTables() {
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            // Verifică dacă tabelul are deja wrapper
            if (table.parentElement.classList.contains('overflow-x-auto')) {
                return;
            }

            // Creează wrapper dacă nu există
            const wrapper = document.createElement('div');
            wrapper.className = 'overflow-x-auto -mx-4 sm:mx-0';
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', 'Tabel cu scroll orizontal');
            
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);

            // Adaugă indicator de scroll pe mobile
            if (window.innerWidth < 768) {
                const scrollIndicator = document.createElement('div');
                scrollIndicator.className = 'text-xs text-slate-500 dark:text-gray-400 text-center py-2 lg:hidden';
                scrollIndicator.innerHTML = '<i data-lucide="move-horizontal" class="w-4 h-4 inline mr-1"></i> Derulați orizontal pentru a vedea toate coloanele';
                wrapper.insertBefore(scrollIndicator, table);
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        });
    }

    // Reinițializare la resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            initTouchOptimizations();
            initResponsiveTables();
        }, 250);
    });

    // Expune funcții globale dacă este necesar
    window.mobileNavigation = {
        openSidebar: function() {
            const sidebar = document.querySelector('aside[role="navigation"]');
            const btn = document.getElementById('mobile-menu-toggle');
            if (sidebar && btn && sidebar.classList.contains('-translate-x-full')) {
                btn.click();
            }
        },
        closeSidebar: function() {
            const sidebar = document.querySelector('aside[role="navigation"]');
            const btn = document.getElementById('mobile-menu-toggle');
            if (sidebar && btn && !sidebar.classList.contains('-translate-x-full')) {
                btn.click();
            }
        }
    };

})();
