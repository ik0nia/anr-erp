/**
 * Îmbunătățiri UX pentru formulare și căutare
 * - Enter pentru confirmare în căutare
 * - ESC pentru închidere cu confirmare modificări
 * - Verificare modificări înainte de închidere
 */

(function() {
    'use strict';

    // Track modificări în formulare
    const formChanges = new Map();
    
    /**
     * Inițializează tracking pentru modificări în formulare
     */
    function initFormChangeTracking() {
        document.querySelectorAll('form').forEach(function(form) {
            // Formularul profil membru: fără tracking (evită orice interacțiune cu state-ul formularului)
            if (form.id === 'form-membru-profil') {
                return;
            }
            const formId = form.id || 'form-' + Math.random().toString(36).substr(2, 9);
            form.id = formId;
            formChanges.set(formId, false);
            
            // Track modificări în toate input-urile, select-urile și textarea-urile
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(function(input) {
                // Ignoră hidden inputs și butoane
                if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button' || input.type === 'reset') {
                    return;
                }
                
                const originalValue = input.value;
                
                input.addEventListener('input', function() {
                    formChanges.set(formId, true);
                });
                
                input.addEventListener('change', function() {
                    formChanges.set(formId, true);
                });
            });
            
            // La submit nu mai afișăm "Leave site?" – marchem că navigarea e din cauza formularului
            form.addEventListener('submit', function() {
                window.__formSubmitting = true;
                formChanges.set(formId, false);
            });
        });
    }
    
    /**
     * Verifică dacă există modificări ne salvate
     */
    function hasUnsavedChanges(formId) {
        return formChanges.get(formId) === true;
    }
    
    /**
     * Enter pentru confirmare în câmpurile de căutare
     */
    function initSearchEnterSubmit() {
        document.querySelectorAll('input[type="search"], input[name="cautare"]').forEach(function(searchInput) {
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    // Găsește formularul părinte sau cel mai apropiat formular
                    const form = this.closest('form');
                    if (form) {
                        form.submit();
                    } else {
                        // Dacă nu există formular, caută butonul de submit din apropiere
                        const submitBtn = this.parentElement.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.click();
                        }
                    }
                }
            });
        });
    }
    
    /**
     * ESC pentru închidere cu confirmare modificări
     */
    function initEscCloseWithConfirm() {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !e.defaultPrevented) {
                // Verifică dacă există un dialog/modal deschis
                const openDialog = document.querySelector('dialog[open]');
                if (openDialog) {
                    const form = openDialog.querySelector('form');
                    if (form && hasUnsavedChanges(form.id)) {
                        e.preventDefault();
                        if (confirm('Ați făcut modificări. Sigur doriți să închideți fereastra fără să salvați?')) {
                            openDialog.close();
                            formChanges.set(form.id, false);
                        }
                    } else {
                        // Închide fără confirmare dacă nu există modificări
                        openDialog.close();
                    }
                    return;
                }
                
                // Verifică dacă există un formular activ pe pagină
                const activeForm = document.activeElement?.closest('form');
                if (activeForm && hasUnsavedChanges(activeForm.id)) {
                    e.preventDefault();
                    if (confirm('Ați făcut modificări. Sigur doriți să părăsiți pagina fără să salvați?')) {
                        // Poate redirecționa sau închide
                        const cancelBtn = activeForm.querySelector('button[type="button"], a[href]');
                        if (cancelBtn) {
                            cancelBtn.click();
                        }
                        formChanges.set(activeForm.id, false);
                    }
                }
            }
        });
    }
    
    /**
     * Adaugă confirmare la butoanele de renunță/anulare
     */
    function initCancelButtons() {
        document.querySelectorAll('button[type="button"], a[href]').forEach(function(btn) {
            const text = btn.textContent?.toLowerCase() || '';
            const ariaLabel = btn.getAttribute('aria-label')?.toLowerCase() || '';
            
            if (text.includes('renunță') || text.includes('anulează') || text.includes('cancel') || 
                ariaLabel.includes('renunță') || ariaLabel.includes('anulează') || ariaLabel.includes('cancel')) {
                
                btn.addEventListener('click', function(e) {
                    const form = this.closest('form');
                    if (form && hasUnsavedChanges(form.id)) {
                        if (!confirm('Ați făcut modificări. Sigur doriți să renunțați?')) {
                            e.preventDefault();
                            return false;
                        }
                        formChanges.set(form.id, false);
                    }
                });
            }
        });
    }
    
    /**
     * Adaugă confirmare la închiderea paginii cu modificări ne salvate
     */
    function initBeforeUnload() {
        window.addEventListener('beforeunload', function(e) {
            // Nu afișa dialog dacă utilizatorul tocmai a trimis un formular (ex. bifare task)
            if (window.__formSubmitting) {
                return;
            }
            // Dialog dezactivat: provoca „Leave site?” la bifare task / submit și confunda utilizatorul.
            // Pentru a reactiva: decomentați blocul de mai jos și setați data-warn-unsaved pe formularele lungi.
            // let hasChanges = false;
            // formChanges.forEach(function(changed) { if (changed) hasChanges = true; });
            // if (hasChanges) {
            //     e.preventDefault();
            //     e.returnValue = 'Ați făcut modificări. Sigur doriți să părăsiți pagina?';
            //     return e.returnValue;
            // }
        });
    }
    
    // Inițializare când DOM-ul este gata
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initFormChangeTracking();
            initSearchEnterSubmit();
            initEscCloseWithConfirm();
            initCancelButtons();
            initBeforeUnload();
        });
    } else {
        initFormChangeTracking();
        initSearchEnterSubmit();
        initEscCloseWithConfirm();
        initCancelButtons();
        initBeforeUnload();
    }
})();
