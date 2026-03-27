/**
 * Accessibility Enhancements - CRM ANR Bihor
 * Optimizări pentru compatibilitate 100% cu screen readers
 */

(function() {
    'use strict';

    // Inițializare la încărcare DOM
    document.addEventListener('DOMContentLoaded', function() {
        enhanceKeyboardNavigation();
        enhanceFormLabels();
        enhanceTableAccessibility();
        enhanceModalAccessibility();
        announceDynamicContent();
        addSkipLinks();
    });

    /**
     * Îmbunătățire navigare cu tastatura
     */
    function enhanceKeyboardNavigation() {
        if (!document.body) return;
        // Skip to main content link (pentru screen readers)
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-amber-600 focus:text-white focus:rounded-lg';
        skipLink.textContent = 'Sari la conținut principal';
        document.body.insertBefore(skipLink, document.body.firstChild);

        // Verifică ID pentru main (deja adăugat în PHP, dar verificăm pentru siguranță)
        const main = document.querySelector('main[role="main"]');
        if (main && !main.id) {
            main.id = 'main-content';
        }

        // Trap focus în modals
        const modals = document.querySelectorAll('dialog[aria-modal="true"]');
        modals.forEach(modal => {
            modal.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    const focusableElements = modal.querySelectorAll(
                        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    );
                    const firstElement = focusableElements[0];
                    const lastElement = focusableElements[focusableElements.length - 1];

                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
        });
    }

    /**
     * Verifică și adaugă label-uri pentru input-uri fără label asociat
     */
    function enhanceFormLabels() {
        const inputs = document.querySelectorAll('input:not([type="hidden"]), textarea, select');
        inputs.forEach(input => {
            // Verifică dacă are label asociat
            const id = input.id;
            const name = input.name;
            
            if (id) {
                const label = document.querySelector(`label[for="${id}"]`);
                if (!label && !input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
                    // Încearcă să găsească label în apropiere
                    const parent = input.parentElement;
                    const nearbyLabel = parent.querySelector('label');
                    if (nearbyLabel && !nearbyLabel.getAttribute('for')) {
                        nearbyLabel.setAttribute('for', id);
                    } else if (!input.getAttribute('aria-label')) {
                        // Adaugă aria-label bazat pe name sau placeholder
                        const placeholder = input.getAttribute('placeholder');
                        const nameAttr = input.getAttribute('name');
                        if (placeholder) {
                            input.setAttribute('aria-label', placeholder);
                        } else if (nameAttr) {
                            const labelText = nameAttr.replace(/_/g, ' ').replace(/([A-Z])/g, ' $1').trim();
                            input.setAttribute('aria-label', labelText);
                        }
                    }
                }
            }
        });
    }

    /**
     * Îmbunătățire accesibilitate tabele
     */
    function enhanceTableAccessibility() {
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            // Adaugă caption dacă nu există
            if (!table.querySelector('caption') && !table.getAttribute('aria-label') && !table.getAttribute('aria-labelledby')) {
                const role = table.getAttribute('aria-label') || 'Tabel de date';
                table.setAttribute('aria-label', role);
            }

            // Verifică header cells
            const headers = table.querySelectorAll('th');
            headers.forEach((th, index) => {
                if (!th.getAttribute('scope') && th.closest('thead')) {
                    th.setAttribute('scope', 'col');
                }
            });

            // Adaugă aria-sort pentru coloane sortabile
            const sortableHeaders = table.querySelectorAll('th a[href*="sort="]');
            sortableHeaders.forEach(link => {
                const th = link.closest('th');
                if (th && !th.getAttribute('aria-sort')) {
                    const urlParams = new URLSearchParams(link.href.split('?')[1]);
                    const currentSort = urlParams.get('sort');
                    const currentDir = urlParams.get('dir') || 'asc';
                    if (link.textContent.includes('↑') || link.textContent.includes('↓')) {
                        th.setAttribute('aria-sort', currentDir === 'asc' ? 'ascending' : 'descending');
                    } else {
                        th.setAttribute('aria-sort', 'none');
                    }
                }
            });
        });
    }

    /**
     * Îmbunătățire accesibilitate modals
     */
    function enhanceModalAccessibility() {
        const modals = document.querySelectorAll('dialog[aria-modal="true"]');
        modals.forEach(modal => {
            // Asigură focus pe primul element când se deschide
            modal.addEventListener('show', function() {
                setTimeout(() => {
                    const firstFocusable = modal.querySelector(
                        'input:not([disabled]), textarea:not([disabled]), button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    );
                    if (firstFocusable) {
                        firstFocusable.focus();
                    }
                }, 100);
            });

            // Anunță screen reader când se închide
            modal.addEventListener('close', function() {
                const trigger = document.activeElement;
                if (trigger && trigger.getAttribute('aria-haspopup') === 'dialog') {
                    trigger.focus();
                }
            });
        });
    }

    /**
     * Anunță modificări dinamice de conținut
     */
    function announceDynamicContent() {
        // Creează aria-live region pentru anunțuri
        let liveRegion = document.getElementById('aria-live-announcements');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'aria-live-announcements';
            liveRegion.setAttribute('role', 'status');
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }

        // Funcție helper pentru anunțuri
        window.announceToScreenReader = function(message) {
            liveRegion.textContent = message;
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        };

        // Anunță mesaje de succes/eroare
        const alerts = document.querySelectorAll('[role="alert"], [role="status"]');
        alerts.forEach(alert => {
            if (alert.textContent.trim()) {
                announceToScreenReader(alert.textContent.trim());
            }
        });
    }

    /**
     * Adaugă skip links pentru navigare rapidă
     */
    function addSkipLinks() {
        if (!document.body) return;
        const skipLinksContainer = document.createElement('nav');
        skipLinksContainer.setAttribute('aria-label', 'Link-uri de navigare rapidă');
        skipLinksContainer.className = 'sr-only focus-within:not-sr-only focus-within:absolute focus-within:top-4 focus-within:left-4 focus-within:z-50 focus-within:bg-white focus-within:dark:bg-gray-800 focus-within:p-4 focus-within:rounded-lg focus-within:shadow-lg';
        
        const skipLinks = [
            { href: '#main-content', text: 'Sari la conținut principal' },
            { href: '#navigation', text: 'Sari la navigare' },
            { href: '#mobile-notifications-link', text: 'Sari la notificări' },
            { href: '#mobile-menu-btn', text: 'Sari la meniul principal' },
        ];

        skipLinks.forEach(link => {
            const a = document.createElement('a');
            a.href = link.href;
            a.textContent = link.text;
            a.className = 'block px-4 py-2 text-amber-600 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500';
            skipLinksContainer.appendChild(a);
        });

        document.body.insertBefore(skipLinksContainer, document.body.firstChild);
    }

    // Expune funcții globale
    window.accessibilityEnhancements = {
        announce: window.announceToScreenReader || function() {}
    };

})();
