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
                    <div id="inc-dash-wrap-mandat" class="hidden mt-3 rounded-lg border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-3">
                        <label for="inc-dash-mandat-numerar" class="flex items-start gap-2 text-sm text-slate-800 dark:text-gray-200">
                            <input type="checkbox" id="inc-dash-mandat-numerar" name="mandat_numerar" value="1" class="mt-1 h-4 w-4 rounded border-slate-400 dark:border-gray-500">
                            <span>
                                <span class="font-medium">Încasare numerar</span>
                                <span class="block text-xs text-slate-700 dark:text-gray-300">Bifat: banii au intrat în casă, se emite chitanță ERP și încasarea apare în evidența încasărilor în numerar. Nebifat: mandatul a fost încasat în contul bancar, fără chitanță.</span>
                            </span>
                        </label>
                    </div>
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
        <div id="inc-dash-succes" class="hidden mt-4 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 p-4" role="region" aria-labelledby="inc-dash-succes-titlu">
            <h3 id="inc-dash-succes-titlu" class="font-semibold text-emerald-900 dark:text-emerald-200 mb-1">Chitanța a fost emisă</h3>
            <p id="inc-dash-succes-detalii" class="text-sm text-slate-800 dark:text-gray-200 mb-3"></p>
            <div class="space-y-3">
                <div id="inc-dash-wrap-email-trimite">
                    <label for="inc-dash-email-trimite" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Trimite chitanța pe email</label>
                    <div class="flex flex-wrap gap-2">
                        <input type="email" id="inc-dash-email-trimite" class="flex-1 min-w-[12rem] px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="adresa@exemplu.ro" autocomplete="email">
                        <button type="button" id="inc-dash-btn-email" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">Trimite pe email</button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="inc-dash-btn-whatsapp" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">Trimite pe WhatsApp</button>
                    <button type="button" id="inc-dash-btn-tipareste" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">Deschide chitanța</button>
                    <button type="button" id="inc-dash-btn-gata" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">Gata</button>
                </div>
                <p id="inc-dash-stare-trimitere" class="text-sm font-medium" role="status" aria-live="polite"></p>
            </div>
        </div>
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
    var panouSucces = document.getElementById('inc-dash-succes');
    var detaliiSucces = document.getElementById('inc-dash-succes-detalii');
    var wrapEmailTrimite = document.getElementById('inc-dash-wrap-email-trimite');
    var inputEmailTrimite = document.getElementById('inc-dash-email-trimite');
    var btnEmailTrimite = document.getElementById('inc-dash-btn-email');
    var btnWhatsApp = document.getElementById('inc-dash-btn-whatsapp');
    var btnTipareste = document.getElementById('inc-dash-btn-tipareste');
    var btnGata = document.getElementById('inc-dash-btn-gata');
    var stareTrimitere = document.getElementById('inc-dash-stare-trimitere');
    var incasareEmisa = null; // { id, seria, nr, telefon }
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
        var wrapMandatReset = document.getElementById('inc-dash-wrap-mandat');
        var bifaMandatReset = document.getElementById('inc-dash-mandat-numerar');
        if (wrapMandatReset) wrapMandatReset.classList.add('hidden');
        if (bifaMandatReset) bifaMandatReset.checked = false;
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
        ascundePanouSucces();
    }

    function ascundePanouSucces() {
        incasareEmisa = null;
        if (panouSucces) panouSucces.classList.add('hidden');
        if (stareTrimitere) { stareTrimitere.textContent = ''; stareTrimitere.className = 'text-sm font-medium'; }
        if (form) form.classList.remove('hidden');
    }

    function arataStareTrimitere(text, tip) {
        if (!stareTrimitere) return;
        stareTrimitere.textContent = text;
        var culoare = tip === 'ok' ? ' text-emerald-800 dark:text-emerald-200' : (tip === 'eroare' ? ' text-rose-700 dark:text-rose-300' : ' text-slate-700 dark:text-gray-300');
        stareTrimitere.className = 'text-sm font-medium' + culoare;
    }

    function tokenCsrf() {
        var inp = form ? form.querySelector('input[name="_csrf_token"]') : null;
        return inp ? inp.value : '';
    }

    function arataPanouSucces(data) {
        var telefon = (data.telefon_destinatar || '') || (document.getElementById('inc-dash-telefon') || {}).value || '';
        var email = (data.email_destinatar || '') || (document.getElementById('inc-dash-email') || {}).value || '';
        incasareEmisa = {
            id: data.id,
            seria: data.seria_chitanta || '-',
            nr: data.nr_chitanta || '-',
            telefon: telefon,
            mesaj: data.mesaj_partajare || '',
            numePdf: data.nume_pdf || 'chitanta.pdf'
        };
        if (detaliiSucces) {
            detaliiSucces.textContent = 'Chitanța ' + incasareEmisa.seria + ' nr. ' + incasareEmisa.nr + ' a fost înregistrată. O puteți trimite persoanei care a donat.';
        }
        if (inputEmailTrimite) inputEmailTrimite.value = (email || '').trim();
        if (btnWhatsApp) {
            // Fără telefon completat, butonul are rost doar dacă dispozitivul poate partaja nativ.
            var potPartaja = (telefon || '').trim() !== '' || typeof navigator.share === 'function';
            btnWhatsApp.classList.toggle('hidden', !potPartaja);
        }
        arataStareTrimitere('');
        if (form) form.classList.add('hidden');
        if (panouSucces) panouSucces.classList.remove('hidden');
        if (inputEmailTrimite && (email || '').trim() !== '') {
            inputEmailTrimite.focus();
        } else if (btnWhatsApp) {
            btnWhatsApp.focus();
        }
    }

    function inchideCuReincarcare() {
        var trebuieReincarcat = incasareEmisa !== null;
        resetModalDashboard();
        dialog.close();
        if (trebuieReincarcat && typeof window.location.reload === 'function') window.location.reload();
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
            var wrapMandatDash = document.getElementById('inc-dash-wrap-mandat');
            var bifaMandatDash = document.getElementById('inc-dash-mandat-numerar');
            if (wrapMandatDash && bifaMandatDash) {
                var esteMandatDash = m === 'mandat_postal';
                wrapMandatDash.classList.toggle('hidden', !esteMandatDash);
                if (!esteMandatDash) {
                    bifaMandatDash.checked = false;
                }
            }
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
                    if (data.seria_chitanta) {
                        window.open('util/incasari-chitanta-print.php?id=' + data.id, '_blank', 'width=800,height=600');
                        arataPanouSucces(data);
                    } else {
                        dialog.close();
                        alert('Încasarea a fost înregistrată cu succes.');
                        resetModalDashboard();
                        if (typeof window.location.reload === 'function') window.location.reload();
                    }
                } else {
                    afiseazaMesaj(data.eroare || 'Eroare la salvare.');
                }
            })
            .catch(function(){ afiseazaMesaj('Eroare de rețea.'); });
    });

    document.getElementById('inc-dash-btn-inchide-x').addEventListener('click', inchideCuReincarcare);
    document.getElementById('inc-dash-btn-inchide').addEventListener('click', inchideCuReincarcare);
    if (btnGata) btnGata.addEventListener('click', inchideCuReincarcare);
    dialog.addEventListener('click', function(e){ if (e.target === dialog) { inchideCuReincarcare(); } });

    if (btnTipareste) {
        btnTipareste.addEventListener('click', function(){
            if (!incasareEmisa) return;
            window.open('util/incasari-chitanta-print.php?id=' + incasareEmisa.id, '_blank', 'width=800,height=600');
        });
    }

    if (btnEmailTrimite) {
        btnEmailTrimite.addEventListener('click', function(){
            if (!incasareEmisa) return;
            var email = (inputEmailTrimite.value || '').trim();
            if (email === '' || email.indexOf('@') < 1) {
                arataStareTrimitere('Completați o adresă de email validă.', 'eroare');
                inputEmailTrimite.focus();
                return;
            }
            btnEmailTrimite.disabled = true;
            arataStareTrimitere('Se trimite chitanța pe email…');
            var fd = new FormData();
            fd.append('_csrf_token', tokenCsrf());
            fd.append('id', String(incasareEmisa.id));
            fd.append('email', email);
            fetch('/api/incasari-trimite-email', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    btnEmailTrimite.disabled = false;
                    if (d && d.ok) {
                        arataStareTrimitere('Chitanța a fost trimisă pe email către ' + email + '.', 'ok');
                    } else {
                        arataStareTrimitere((d && d.eroare) ? d.eroare : 'Emailul nu a putut fi trimis.', 'eroare');
                    }
                })
                .catch(function(){
                    btnEmailTrimite.disabled = false;
                    arataStareTrimitere('Eroare de rețea la trimiterea emailului.', 'eroare');
                });
        });
    }

    if (btnWhatsApp) {
        btnWhatsApp.addEventListener('click', function(){
            if (!incasareEmisa) return;
            btnWhatsApp.disabled = true;
            arataStareTrimitere('Se pregătește chitanța pentru partajare…');
            fetch('util/incasari-chitanta-pdf.php?id=' + incasareEmisa.id + '&partaj=1', { credentials: 'same-origin' })
                .then(function(r){ if (!r.ok) throw new Error('pdf'); return r.blob(); })
                .then(function(blob){
                    var fisier = null;
                    try { fisier = new File([blob], incasareEmisa.numePdf, { type: 'application/pdf' }); } catch (e) { fisier = null; }
                    var mesaj = incasareEmisa.mesaj || ('Chitanța ' + incasareEmisa.seria + ' nr. ' + incasareEmisa.nr + ' – Asociația Nevăzătorilor Bihor');
                    if (fisier && navigator.share && navigator.canShare && navigator.canShare({ files: [fisier] })) {
                        return navigator.share({ files: [fisier], title: 'Chitanță', text: mesaj })
                            .then(function(){ arataStareTrimitere('Chitanța a fost trimisă către aplicația aleasă.', 'ok'); });
                    }
                    var urlBlob = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = urlBlob;
                    link.download = incasareEmisa.numePdf;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function(){ URL.revokeObjectURL(urlBlob); }, 60000);
                    window.open('https://wa.me/' + (incasareEmisa.telefon || '') + '?text=' + encodeURIComponent(mesaj), '_blank', 'noopener');
                    arataStareTrimitere('PDF-ul chitanței a fost descărcat; atașați-l în conversația WhatsApp deschisă.', 'ok');
                })
                .catch(function(){ arataStareTrimitere('Chitanța nu a putut fi pregătită pentru partajare.', 'eroare'); })
                .then(function(){ btnWhatsApp.disabled = false; });
        });
    }

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
