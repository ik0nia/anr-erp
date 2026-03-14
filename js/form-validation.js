/**
 * Validare client-side pentru formulare CRM ANR Bihor
 * Adaugă validare în timp real și feedback vizual
 */

(function() {
    'use strict';

    // Inițializare la încărcare DOM
    document.addEventListener('DOMContentLoaded', function() {
        initFormValidation();
        initLoadingIndicators();
        initConfirmDialogs();
    });

    /**
     * Validare formulare – doar pentru formularele care optează (data-validate="strict").
     * Toate celelalte formulare se trimit la server fără blocare; validarea se face pe server.
     * Evită blocarea accidentală a butoanelor de salvare (task, interacțiuni, etc.).
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form[method="post"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Formularul profil membru: fără validare JS și fără showFormLoading (evită orice efect secundar)
                if (this.id === 'form-membru-profil') {
                    return;
                }
                // Validare blocantă DOAR dacă formularul are data-validate="strict"
                const strictValidation = this.getAttribute('data-validate') === 'strict';
                if (strictValidation && !validateForm(this)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                // Adaugă loading indicator (nu blochează formulare fără buton submit)
                showFormLoading(this);
            });

            // Validare în timp real pentru câmpuri critice
            const criticalFields = form.querySelectorAll('input[required], select[required], textarea[required]');
            criticalFields.forEach(field => {
                field.addEventListener('blur', function() {
                    validateField(this);
                });
                
                field.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        validateField(this);
                    }
                });
            });
        });
    }

    /**
     * Validează un formular complet
     */
    function validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });

        // Validare specifică pentru CNP (sărită pentru formularul de profil membru – serverul validează)
        const isMemberProfileForm = form.id === 'form-membru-profil' || form.querySelector('input[name="actualizeaza_membru"]');
        if (!isMemberProfileForm) {
            const cnpField = form.querySelector('input[name="cnp"]');
            if (cnpField && cnpField.value) {
                if (!validateCNP(cnpField.value)) {
                    showFieldError(cnpField, 'CNP-ul nu este valid.');
                    isValid = false;
                }
            }
        }

        // Validare email
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !validateEmail(field.value)) {
                showFieldError(field, 'Adresa de email nu este validă.');
                isValid = false;
            }
        });

        // Validare parolă (dacă există)
        const passwordField = form.querySelector('input[name="parola"]');
        const passwordConfirmField = form.querySelector('input[name="parola_noua2"], input[name="parola2"]');
        if (passwordField && passwordConfirmField && passwordField.value && passwordConfirmField.value) {
            if (passwordField.value !== passwordConfirmField.value) {
                showFieldError(passwordConfirmField, 'Parolele nu coincid.');
                isValid = false;
            }
            if (passwordField.value.length < 6) {
                showFieldError(passwordField, 'Parola trebuie să aibă minim 6 caractere.');
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Validează un câmp individual
     */
    function validateField(field) {
        // Elimină clasele de eroare anterioare
        clearFieldError(field);

        // Verificare required
        if (field.hasAttribute('required') && !field.value.trim()) {
            showFieldError(field, 'Acest câmp este obligatoriu.');
            return false;
        }

        // Validare tipuri specifice
        if (field.type === 'email' && field.value && !validateEmail(field.value)) {
            showFieldError(field, 'Adresa de email nu este validă.');
            return false;
        }

        if (field.type === 'tel' && field.value && !validatePhone(field.value)) {
            showFieldError(field, 'Numărul de telefon nu este valid.');
            return false;
        }

        if (field.type === 'url' && field.value && !validateURL(field.value)) {
            showFieldError(field, 'URL-ul nu este valid.');
            return false;
        }

        // Validare CNP
        if (field.name === 'cnp' && field.value && !validateCNP(field.value)) {
            showFieldError(field, 'CNP-ul nu este valid.');
            return false;
        }

        // Dacă ajunge aici, câmpul este valid
        showFieldSuccess(field);
        return true;
    }

    /**
     * Validare CNP (simplificată - doar format, nu cifră control)
     */
    function validateCNP(cnp) {
        // Elimină spații și caractere non-numerice
        const cleanCNP = cnp.replace(/\D/g, '');
        // CNP trebuie să aibă exact 13 cifre
        return cleanCNP.length === 13 && /^\d{13}$/.test(cleanCNP);
    }

    /**
     * Validare email
     */
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Validare telefon
     */
    function validatePhone(phone) {
        const cleanPhone = phone.replace(/\D/g, '');
        return cleanPhone.length >= 10;
    }

    /**
     * Validare URL
     */
    function validateURL(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Afișează eroare pentru un câmp
     */
    function showFieldError(field, message) {
        field.classList.add('is-invalid', 'border-red-500');
        field.classList.remove('is-valid', 'border-emerald-500');
        
        // Elimină mesajul de eroare anterior
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Adaugă mesajul de eroare
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-sm text-red-600 dark:text-red-400 mt-1';
        errorDiv.textContent = message;
        field.parentElement.appendChild(errorDiv);
    }

    /**
     * Afișează succes pentru un câmp
     */
    function showFieldSuccess(field) {
        field.classList.add('is-valid', 'border-emerald-500');
        field.classList.remove('is-invalid', 'border-red-500');
        
        // Elimină mesajul de eroare dacă există
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    /**
     * Elimină eroarea pentru un câmp
     */
    function clearFieldError(field) {
        field.classList.remove('is-invalid', 'is-valid', 'border-red-500', 'border-emerald-500');
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    /**
     * Loading indicators pentru formulare
     */
    function initLoadingIndicators() {
        const forms = document.querySelectorAll('form[method="post"]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                if (this.id === 'form-membru-profil') return;
                showFormLoading(this);
            });
        });
    }

    /**
     * Afișează loading indicator pentru un formular
     */
    function showFormLoading(form) {
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!submitButton) return;

        // Salvează textul original
        if (!submitButton.dataset.originalText) {
            submitButton.dataset.originalText = submitButton.textContent || submitButton.value;
        }

        // Dezactivează doar butonul de submit (NU și celelalte câmpuri – câmpurile disabled nu se trimit la server)
        submitButton.disabled = true;
        submitButton.classList.add('opacity-75', 'cursor-not-allowed');

        // Adaugă spinner
        const spinner = document.createElement('span');
        spinner.className = 'inline-block animate-spin mr-2';
        spinner.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4"></i>';
        
        if (submitButton.tagName === 'BUTTON') {
            submitButton.insertBefore(spinner, submitButton.firstChild);
            submitButton.innerHTML = spinner.outerHTML + ' Se procesează...';
        } else {
            submitButton.value = 'Se procesează...';
        }
    }

    /**
     * Confirmări pentru acțiuni critice
     */
    function initConfirmDialogs() {
        // Confirmare pentru ștergere
        const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const confirmMessage = this.getAttribute('data-confirm') || 
                                     this.querySelector('input[type="hidden"][name*="sterge"]') ? 
                                     'Sunteți sigur că doriți să ștergeți această înregistrare?' : 
                                     'Sunteți sigur?';
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    }

    // Expune funcțiile globale dacă este necesar
    window.formValidation = {
        validateForm: validateForm,
        validateField: validateField,
        validateCNP: validateCNP,
        validateEmail: validateEmail
    };

})();
