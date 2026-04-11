(function () {
    function getAutoDismissMs(el) {
        var fromMs = Number(el.getAttribute('data-auto-dismiss-ms'));
        if (Number.isFinite(fromMs) && fromMs > 0) {
            return fromMs;
        }
        var fromSeconds = Number(el.getAttribute('data-auto-dismiss-seconds'));
        if (Number.isFinite(fromSeconds) && fromSeconds > 0) {
            return Math.round(fromSeconds * 1000);
        }
        return 15000;
    }

    function isAlertMessage(el) {
        if (!(el instanceof HTMLElement)) return false;
        if (el.hasAttribute('data-alert-message')) return true;

        var role = (el.getAttribute('role') || '').toLowerCase();
        if (role !== 'alert' && role !== 'status') return false;

        var cls = ' ' + (el.className || '') + ' ';
        return cls.indexOf(' border-l-4') !== -1 || cls.indexOf(' rounded') !== -1 || cls.indexOf(' bg-') !== -1;
    }

    function dismissAlert(el) {
        if (!el || el.dataset.alertClosed === '1') return;
        el.dataset.alertClosed = '1';
        el.setAttribute('aria-hidden', 'true');
        el.style.transition = 'opacity 180ms ease';
        el.style.opacity = '0';
        window.setTimeout(function () {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 180);
    }

    function enhanceAlert(el) {
        if (!isAlertMessage(el) || el.dataset.alertEnhanced === '1') return;
        el.dataset.alertEnhanced = '1';
        var closeBtn = el.querySelector('.js-alert-close');
        if (!closeBtn) {
            el.classList.add('relative', 'pr-12');
            closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded border border-current/30 text-current hover:bg-black/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 js-alert-close';
            closeBtn.setAttribute('aria-label', 'Inchide mesajul');
            closeBtn.innerHTML = '<span aria-hidden="true" class="text-lg leading-none">&times;</span>';
            el.appendChild(closeBtn);
        }
        closeBtn.addEventListener('click', function () {
            dismissAlert(el);
        });

        var timeoutMs = getAutoDismissMs(el);
        window.setTimeout(function () {
            dismissAlert(el);
        }, timeoutMs);
    }

    function enhanceAll(root) {
        var base = root || document;
        var elements = base.querySelectorAll('[data-alert-message], [role="alert"], [role="status"]');
        for (var i = 0; i < elements.length; i++) {
            enhanceAlert(elements[i]);
        }
    }

    function init() {
        enhanceAll(document);
        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var mutation = mutations[i];
                for (var j = 0; j < mutation.addedNodes.length; j++) {
                    var node = mutation.addedNodes[j];
                    if (!(node instanceof HTMLElement)) continue;
                    if (isAlertMessage(node)) {
                        enhanceAlert(node);
                    } else if (node.querySelectorAll) {
                        enhanceAll(node);
                    }
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
