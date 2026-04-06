<?php
/**
 * Modal Dashboard: Încasează Donație (donator extern). Fereastră exclusiv pentru donații.
 */
if (!function_exists('csrf_field')) { function csrf_field() { return ''; } }
?>
<style>
    #modal-incasari-dashboard::backdrop {
        background: rgba(0, 0, 0, 0.55);
        -webkit-backdrop-filter: blur(5px);
        backdrop-filter: blur(5px);
    }
</style>
<dialog id="modal-incasari-dashboard" role="dialog" aria-modal="true" aria-labelledby="modal-incasari-dashboard-titlu" class="p-0 rounded-xl shadow-2xl max-w-2xl w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800">
    <div class="p-6 relative">
        <button type="button" id="inc-dash-btn-inchide-x" class="absolute top-3 right-3 inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-200 hover:bg-slate-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" aria-label="Închide fereastra de încasare donație" title="Închide">
            <span aria-hidden="true" class="text-lg leading-none">&times;</span>
        </button>
        <h2 id="modal-incasari-dashboard-titlu" class="text-lg font-bold text-slate-900 dark:text-white mb-4 pr-10">Încasează Donație</h2>
        <form id="form-incasari-dashboard" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tip_form" value="donatie">

            <!-- Rând 1: Mod plată și dată -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <span class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Mod plată <span class="text-red-600">*</span></span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Mod plată">
                        <button type="button" role="radio" aria-checked="false" class="inc-dash-mod-btn px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" data-mod="numerar">Chitanta ERP</button>
                        <button type="button" role="radio" aria-checked="false" class="inc-dash-mod-btn px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" data-mod="chitanta_veche">Chitanta veche</button>
                        <button type="button" role="radio" aria-checked="false" class="inc-dash-mod-btn px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" data-mod="card_pos">POS</button>
                        <button type="button" role="radio" aria-checked="false" class="inc-dash-mod-btn px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" data-mod="transfer_bancar">Transfer bancar</button>
                        <button type="button" role="radio" aria-checked="false" class="inc-dash-mod-btn px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" data-mod="card_online">Plata online</button>
                        <button type="button" role="radio" aria-checked="false" class="inc-dash-mod-btn px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-700 dark:text-gray-300 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800" data-mod="mandat_postal">Mandat postal</button>
                    </div>
                    <input type="hidden" name="mod_plata" id="inc-dash-mod" value="">
                </div>
                <div>
                    <label for="inc-dash-data" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Data încasării</label>
                    <input type="date" id="inc-dash-data" name="data_incasare" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                </div>
            </div>

            <div class="space-y-4">
                <p id="inc-dash-hint-date-personale" class="text-sm text-slate-600 dark:text-gray-400 hidden" aria-live="polite">La Card POS, Card online sau Transfer bancar, datele personale ale donatorului nu sunt obligatorii.</p>
                <p id="inc-dash-feedback" class="text-sm font-medium text-rose-700 dark:text-rose-300 hidden" aria-live="assertive"></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inc-dash-nume" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume donator <span id="inc-dash-asterisc-nume" class="text-red-600">*</span></label>
                        <input type="text" id="inc-dash-nume" name="nume_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-required="true">
                    </div>
                    <div>
                        <label for="inc-dash-prenume" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Prenume donator <span id="inc-dash-asterisc-prenume" class="text-red-600">*</span></label>
                        <input type="text" id="inc-dash-prenume" name="prenume_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-required="true">
                    </div>
                </div>
                <div>
                    <label for="inc-dash-cnp" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">CNP</label>
                    <input type="text" id="inc-dash-cnp" name="cnp_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" maxlength="13" inputmode="numeric">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inc-dash-telefon" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Telefon</label>
                        <input type="tel" id="inc-dash-telefon" name="telefon_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label for="inc-dash-email" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" id="inc-dash-email" name="email_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inc-dash-localitate" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Localitate</label>
                        <input type="text" id="inc-dash-localitate" name="localitate_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label for="inc-dash-judet" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Județ</label>
                        <input type="text" id="inc-dash-judet" name="judet_donator" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="inc-dash-valoare" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Valoarea (RON) <span class="text-red-600">*</span></label>
                        <input type="text" id="inc-dash-valoare" name="valoare" placeholder="0.00" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" inputmode="decimal" aria-required="true">
                    </div>
                    <div>
                        <label for="inc-dash-suma-litere" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Suma în litere</label>
                        <input type="text" id="inc-dash-suma-litere" name="suma_litere" readonly class="w-full px-3 py-2 border border-slate-200 dark:border-gray-600 rounded-lg bg-slate-50 dark:bg-gray-700/50 text-slate-600 dark:text-gray-400" aria-readonly="true">
                    </div>
                </div>
                <div>
                    <label for="inc-dash-reprezentand" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Reprezentând</label>
                    <input type="text" id="inc-dash-reprezentand" name="reprezentand" value="Donație" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Donație">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" id="inc-dash-btn-salveaza-print" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">Salvează și Printează</button>
                <button type="button" id="inc-dash-btn-inchide" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">Închide</button>
            </div>
        </form>
    </div>
</dialog>
<script>
(function(){
    var dialog = document.getElementById('modal-incasari-dashboard');
    var form = document.getElementById('form-incasari-dashboard');
    var valoareInput = document.getElementById('inc-dash-valoare');
    var sumaLitereInput = document.getElementById('inc-dash-suma-litere');
    var reprezentandInput = document.getElementById('inc-dash-reprezentand');
    var modInput = document.getElementById('inc-dash-mod');
    var feedback = document.getElementById('inc-dash-feedback');
    var defaultData = new Date().toISOString().slice(0,10);

    function afiseazaMesaj(text) {
        if (!feedback) return;
        feedback.textContent = text || '';
        feedback.classList.toggle('hidden', !text);
    }

    function resetModalDashboard() {
        afiseazaMesaj('');
        modInput.value = '';
        document.querySelectorAll('.inc-dash-mod-btn').forEach(function(b){
            b.classList.remove('border-amber-500', 'bg-amber-200', 'dark:bg-amber-400', 'text-slate-900');
            b.classList.add('border-slate-300', 'dark:border-gray-600', 'bg-white', 'dark:bg-gray-700');
            b.setAttribute('aria-checked', 'false');
        });
        form.reset();
        document.getElementById('inc-dash-data').value = defaultData;
        reprezentandInput.value = 'Donație';
        if (localitateInput) localitateInput.value = '';
        if (judetInput) judetInput.value = '';
        sumaLitereInput.value = '';
        numeInput.required = true;
        prenumeInput.required = true;
        numeInput.setAttribute('aria-required', 'true');
        prenumeInput.setAttribute('aria-required', 'true');
        asteriscNume.style.display = 'inline';
        asteriscPrenume.style.display = 'inline';
        hintDatePersonale.classList.add('hidden');
    }

    function necesitaDatePersonale(modPlata) {
        return ['card_pos', 'card_online', 'transfer_bancar'].indexOf(modPlata) === -1;
    }

    function numarInLitereRo(n) {
        n = Math.floor(n);
        if (n === 0) return 'zero';
        var unitati = ['', 'unu', 'doi', 'trei', 'patru', 'cinci', 'șase', 'șapte', 'opt', 'nouă'];
        var zeciSpec = ['zece', 'unsprezece', 'doisprezece', 'treisprezece', 'patrusprezece', 'cincisprezece', 'șaisprezece', 'șaptesprezece', 'optsprezece', 'nouăsprezece'];
        var zeci = ['', '', 'douăzeci', 'treizeci', 'patruzeci', 'cincizeci', 'șaizeci', 'șaptezeci', 'optzeci', 'nouăzeci'];
        var sute = ['', 'o sută', 'două sute', 'trei sute', 'patru sute', 'cinci sute', 'șase sute', 'șapte sute', 'opt sute', 'nouă sute'];
        function panaLa99(x) {
            if (x === 0) return '';
            if (x < 10) return unitati[x];
            if (x < 20) return zeciSpec[x - 10];
            var z = Math.floor(x / 10);
            var u = x % 10;
            if (u === 0) return zeci[z];
            return zeci[z] + ' și ' + unitati[u];
        }
        function panaLa999(x) {
            if (x === 0) return '';
            var s = Math.floor(x / 100);
            var r = x % 100;
            if (s === 0) return panaLa99(r);
            return (sute[s] + (r ? ' ' : '') + panaLa99(r)).trim();
        }
        if (n >= 1000000) {
            var mil = Math.floor(n / 1000000);
            var rest = n % 1000000;
            var ms = mil === 1 ? 'un milion' : (panaLa999(mil) + ' milioane');
            return (ms + (rest ? ' ' + panaLa999(rest) : '')).trim();
        }
        if (n >= 1000) {
            var mii = Math.floor(n / 1000);
            var rest = n % 1000;
            var ms = mii === 1 ? 'o mie' : (panaLa999(mii) + ' mii');
            return (ms + (rest ? ' ' + panaLa999(rest) : '')).trim();
        }
        return panaLa999(n);
    }
    function sumaInLitere(suma) {
        suma = parseFloat(String(suma).replace(',','.')) || 0;
        suma = Math.round(suma * 100) / 100;
        var intPart = Math.floor(suma);
        var decPart = Math.round((suma - intPart) * 100);
        var parteLei = numarInLitereRo(intPart);
        if (decPart === 0) return parteLei + ' lei';
        var parteBani = numarInLitereRo(decPart);
        return parteLei + ' lei și ' + parteBani + ' bani';
    }

    function actualizeazaSumaLitere() {
        var v = parseFloat(String(valoareInput.value).replace(',','.')) || 0;
        sumaLitereInput.value = v > 0 ? sumaInLitere(v) : '';
    }
    valoareInput.addEventListener('input', function(){
        afiseazaMesaj('');
        this.removeAttribute('aria-invalid');
        actualizeazaSumaLitere();
    });

    var numeInput = document.getElementById('inc-dash-nume');
    var prenumeInput = document.getElementById('inc-dash-prenume');
    var localitateInput = document.getElementById('inc-dash-localitate');
    var judetInput = document.getElementById('inc-dash-judet');
    var asteriscNume = document.getElementById('inc-dash-asterisc-nume');
    var asteriscPrenume = document.getElementById('inc-dash-asterisc-prenume');
    var hintDatePersonale = document.getElementById('inc-dash-hint-date-personale');

    function actualizeazaObligatoriuDateDonator() {
        var obligatoriu = necesitaDatePersonale(modInput.value);
        numeInput.required = obligatoriu;
        numeInput.setAttribute('aria-required', obligatoriu ? 'true' : 'false');
        prenumeInput.required = obligatoriu;
        prenumeInput.setAttribute('aria-required', obligatoriu ? 'true' : 'false');
        asteriscNume.style.display = obligatoriu ? 'inline' : 'none';
        asteriscPrenume.style.display = obligatoriu ? 'inline' : 'none';
        hintDatePersonale.classList.toggle('hidden', obligatoriu || !modInput.value);
    }

    document.querySelectorAll('.inc-dash-mod-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var m = this.getAttribute('data-mod');
            modInput.value = m;
            afiseazaMesaj('');
            document.querySelectorAll('.inc-dash-mod-btn').forEach(function(b){
                b.classList.remove('border-amber-500', 'bg-amber-200', 'dark:bg-amber-400', 'text-slate-900');
                b.classList.add('border-slate-300', 'dark:border-gray-600', 'bg-white', 'dark:bg-gray-700');
                b.setAttribute('aria-checked', 'false');
            });
            this.classList.add('border-amber-500', 'bg-amber-200', 'dark:bg-amber-400', 'text-slate-900');
            this.classList.remove('border-slate-300', 'dark:border-gray-600', 'bg-white', 'dark:bg-gray-700');
            this.setAttribute('aria-checked', 'true');
            actualizeazaObligatoriuDateDonator();
        });
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        afiseazaMesaj('');
        valoareInput.removeAttribute('aria-invalid');
        numeInput.removeAttribute('aria-invalid');
        prenumeInput.removeAttribute('aria-invalid');
        if (!modInput.value) {
            afiseazaMesaj('Selectați modul de plată.');
            var firstModBtn = document.querySelector('.inc-dash-mod-btn');
            if (firstModBtn) firstModBtn.focus();
            return;
        }
        if (necesitaDatePersonale(modInput.value)) {
            if (!numeInput.value.trim()) { numeInput.setAttribute('aria-invalid', 'true'); afiseazaMesaj('Completați numele donatorului.'); numeInput.focus(); return; }
            if (!prenumeInput.value.trim()) { prenumeInput.setAttribute('aria-invalid', 'true'); afiseazaMesaj('Completați prenumele donatorului.'); prenumeInput.focus(); return; }
        }
        var v = parseFloat(String(valoareInput.value).replace(',','.')) || 0;
        if (v <= 0) { valoareInput.setAttribute('aria-invalid', 'true'); afiseazaMesaj('Introduceți valoarea donației.'); valoareInput.focus(); return; }
        var fd = new FormData(form);
        fd.set('tip_form', 'donatie');
        fd.set('valoare', valoareInput.value.replace(',', '.'));
        fetch('/api/incasari-dashboard-salveaza', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.ok) {
                    dialog.close();
                    if (data.seria_chitanta) {
                        window.open('util/incasari-chitanta-print.php?id=' + data.id, '_blank', 'width=800,height=600');
                    } else {
                        alert('Încasarea a fost înregistrată cu succes.');
                    }
                    resetModalDashboard();
                    if (typeof window.location.reload === 'function') window.location.reload();
                } else {
                    afiseazaMesaj(data.eroare || 'Eroare la salvare.');
                }
            })
            .catch(function(){ afiseazaMesaj('Eroare de rețea.'); });
    });

    document.getElementById('inc-dash-btn-inchide-x').addEventListener('click', function(){ resetModalDashboard(); dialog.close(); });
    document.getElementById('inc-dash-btn-inchide').addEventListener('click', function(){ resetModalDashboard(); dialog.close(); });
    dialog.addEventListener('click', function(e){ if (e.target === dialog) { resetModalDashboard(); dialog.close(); } });

    document.addEventListener('click', function(e){
        var btn = e.target.closest('.btn-deschide-incasari-dashboard');
        if (btn && dialog) {
            e.preventDefault();
            resetModalDashboard();
            dialog.showModal();
        }
    });
})();
</script>
