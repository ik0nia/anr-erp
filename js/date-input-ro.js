/**
 * Introducere date in format romanesc (zz/ll/aaaa) in campurile de tip data.
 *
 * Campurile <input type="date"> sunt native (accesibile cu NVDA si cu
 * calendarul browserului), iar separatorul afisat este impus de limba
 * browserului. Acest script permite in plus ca operatorul sa lipeasca sau sa
 * scrie o data in format zz/ll/aaaa, zz.ll.aaaa, zz-ll-aaaa sau aaaa-ll-zz,
 * normalizand-o automat la valoarea interna aaaa-ll-zz.
 */

(function () {
    'use strict';

    /**
     * Normalizeaza un text la formatul aaaa-ll-zz (sau null daca nu e o data).
     */
    function normalizeazaData(text) {
        if (typeof text !== 'string') return null;
        var v = text.trim();
        if (v === '') return null;

        var zz = null, ll = null, aaaa = null;
        var m = v.match(/^(\d{1,2})[.\/\-\s](\d{1,2})[.\/\-\s](\d{2}|\d{4})$/);
        if (m) {
            zz = parseInt(m[1], 10);
            ll = parseInt(m[2], 10);
            aaaa = parseInt(m[3], 10);
            if (m[3].length === 2) {
                aaaa += (aaaa <= 69 ? 2000 : 1900);
            }
        } else {
            m = v.match(/^(\d{4})[.\/\-](\d{1,2})[.\/\-](\d{1,2})$/);
            if (m) {
                aaaa = parseInt(m[1], 10);
                ll = parseInt(m[2], 10);
                zz = parseInt(m[3], 10);
            } else {
                m = v.match(/^(\d{2})(\d{2})(\d{4})$/); // 22042026
                if (m) {
                    zz = parseInt(m[1], 10);
                    ll = parseInt(m[2], 10);
                    aaaa = parseInt(m[3], 10);
                }
            }
        }

        if (zz === null || ll === null || aaaa === null) return null;
        if (ll < 1 || ll > 12 || zz < 1 || zz > 31) return null;
        if (aaaa < 1900 || aaaa > 2200) return null;

        var d = new Date(Date.UTC(aaaa, ll - 1, zz));
        if (d.getUTCFullYear() !== aaaa || d.getUTCMonth() !== ll - 1 || d.getUTCDate() !== zz) {
            return null; // data inexistenta (ex. 31/02)
        }

        var p = function (n) { return (n < 10 ? '0' : '') + n; };
        return aaaa + '-' + p(ll) + '-' + p(zz);
    }

    function esteCampData(el) {
        return el && el.tagName === 'INPUT' && (el.type === 'date' || el.getAttribute('type') === 'date');
    }

    function aplicaValoare(input, iso) {
        input.value = iso;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Lipire (Ctrl+V) intr-un camp de tip data: accepta orice format uzual.
    document.addEventListener('paste', function (e) {
        var input = e.target;
        if (!esteCampData(input)) return;
        var cb = e.clipboardData || window.clipboardData;
        if (!cb) return;
        var iso = normalizeazaData(cb.getData('text'));
        if (iso) {
            e.preventDefault();
            aplicaValoare(input, iso);
        }
    }, true);

    // Campuri text marcate explicit ca date (data-data-ro): normalizare la iesire.
    document.addEventListener('blur', function (e) {
        var input = e.target;
        if (!input || input.tagName !== 'INPUT') return;
        if (!input.hasAttribute('data-data-ro')) return;
        var iso = normalizeazaData(input.value);
        if (iso) {
            var p = iso.split('-');
            input.value = p[2] + '/' + p[1] + '/' + p[0];
            var ascuns = input.getAttribute('data-data-ro');
            if (ascuns) {
                var tinta = document.getElementById(ascuns);
                if (tinta) aplicaValoare(tinta, iso);
            }
        }
    }, true);

    window.anrNormalizeazaData = normalizeazaData;
})();
