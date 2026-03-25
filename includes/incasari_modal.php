<?php
/**
 * Modal Încasează: tip încasare, mod plată, dată, sumă (donatie/taxă/altă), Tipărește chitanță / Salvează încasarea.
 * Datele membrului se setează la deschidere prin data-* pe butonul trigger (btn-deschide-incasari).
 */
?>
<dialog id="modal-incasari" role="dialog" aria-modal="true" aria-labelledby="modal-incasari-titlu" class="p-0 rounded-xl shadow-2xl max-w-lg w-[calc(100%-2rem)] border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800">
    <div class="p-6">
        <h2 id="modal-incasari-titlu" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Încasare pentru <span id="incasari-nume-membru" class="text-amber-600 dark:text-amber-400"></span></h2>
        <form id="form-incasari" class="space-y-4">
            <?php if (function_exists('csrf_field')) { echo csrf_field(); } ?>
            <input type="hidden" name="membru_id" id="incasari-membru-id" value="">
            <input type="hidden" name="valoare_cotizatie" id="incasari-valoare-cot" value="0">
            <input type="hidden" name="cotizatie_achitata" id="incasari-cot-achitata" value="0">

            <div>
                <span class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Tip încasare</span>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Tip încasare">
                    <span id="incasari-cot-achitata-afis" class="hidden inline-flex items-center px-3 py-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200 font-medium">Cotizație achitată</span>
                    <button type="button" class="incasari-tip-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-tip="cotizatie">Încasează cotizație</button>
                    <button type="button" class="incasari-tip-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-tip="donatie">Încasează Donație</button>
                    <button type="button" class="incasari-tip-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-tip="taxa_participare">Încasează taxă participare</button>
                    <button type="button" class="incasari-tip-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-tip="alte">Încasează alte venituri</button>
                </div>
                <input type="hidden" name="tip" id="incasari-tip" value="">
            </div>

            <div>
                <span class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Mod plată</span>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Mod plată">
                    <button type="button" class="incasari-mod-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-mod="numerar">Chitanta ERP</button>
                    <button type="button" class="incasari-mod-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-mod="chitanta_veche">Chitanta veche</button>
                    <button type="button" class="incasari-mod-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-mod="card_pos">POS</button>
                    <button type="button" class="incasari-mod-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-mod="transfer_bancar">Transfer bancar</button>
                    <button type="button" class="incasari-mod-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-mod="card_online">Plata online</button>
                    <button type="button" class="incasari-mod-btn px-3 py-2 rounded-lg border border-slate-700 dark:border-slate-500 bg-slate-700 dark:bg-slate-600 text-white hover:bg-amber-700 dark:hover:bg-amber-700 font-medium" data-mod="mandat_postal">Mandat postal</button>
                </div>
                <input type="hidden" name="mod_plata" id="incasari-mod" value="">
            </div>

            <div>
                <label for="incasari-data" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Data încasării</label>
                <input type="date" id="incasari-data" name="data_incasare" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
            </div>

            <div id="incasari-wrap-suma" class="hidden">
                <label for="incasari-suma" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1"><span id="incasari-label-suma">Sumă (RON)</span></label>
                <input type="text" id="incasari-suma" name="suma" placeholder="0.00" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" inputmode="decimal">
            </div>

            <div id="incasari-wrap-reprezentand" class="hidden">
                <label for="incasari-reprezentand" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Reprezentând</label>
                <input type="text" id="incasari-reprezentand" name="reprezentand" value="" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Donație">
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="button" id="incasari-btn-chitanta" class="hidden px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Tipărește chitanță</button>
                <button type="button" id="incasari-btn-salveaza" class="hidden px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg">Salvează încasarea</button>
                <button type="button" id="incasari-btn-inchide" class="px-4 py-2 border border-slate-700 dark:border-slate-500 rounded-lg bg-slate-700 dark:bg-slate-600 text-white hover:bg-slate-600 dark:hover:bg-slate-500">Închide</button>
            </div>
        </form>
    </div>
</dialog>
<script>
(function(){
    var dialog = document.getElementById('modal-incasari');
    var form = document.getElementById('form-incasari');
    var mid = document.getElementById('incasari-membru-id');
    var valCot = document.getElementById('incasari-valoare-cot');
    var cotAchitata = document.getElementById('incasari-cot-achitata');
    var cotAchitataAfis = document.getElementById('incasari-cot-achitata-afis');
    var tipInput = document.getElementById('incasari-tip');
    var modInput = document.getElementById('incasari-mod');
    var wrapSuma = document.getElementById('incasari-wrap-suma');
    var labelSuma = document.getElementById('incasari-label-suma');
    var inputSuma = document.getElementById('incasari-suma');
    var btnChitanta = document.getElementById('incasari-btn-chitanta');
    var btnSalveaza = document.getElementById('incasari-btn-salveaza');
    var dataInput = document.getElementById('incasari-data');
    var wrapReprezentand = document.getElementById('incasari-wrap-reprezentand');
    var inputReprezentand = document.getElementById('incasari-reprezentand');

    function resetModal() {
        tipInput.value = '';
        modInput.value = '';
        document.querySelectorAll('.incasari-tip-btn').forEach(function(b){ b.classList.remove('bg-amber-200', 'dark:bg-amber-800/50', 'border-amber-500'); });
        document.querySelectorAll('.incasari-mod-btn').forEach(function(b){ b.classList.remove('bg-amber-200', 'dark:bg-amber-800/50', 'border-amber-500'); });
        wrapSuma.classList.add('hidden');
        inputSuma.value = '';
        wrapReprezentand.classList.add('hidden');
        inputReprezentand.value = '';
        btnChitanta.classList.add('hidden');
        btnSalveaza.classList.add('hidden');
        dataInput.value = new Date().toISOString().slice(0,10);
    }

    // Functie globala pentru deschidere modal incasari
    var numeSpan = document.getElementById('incasari-nume-membru');

    window.deschideIncasari = function(btn) {
        if (!btn || !dialog) return;
        mid.value = btn.getAttribute('data-membru-id') || '';
        if (numeSpan) numeSpan.textContent = btn.getAttribute('data-membru-nume') || '';
        valCot.value = btn.getAttribute('data-valoare-cot') || '0';
        var ach = btn.getAttribute('data-cot-achitata');
        cotAchitata.value = (ach === '1' || ach === 'true') ? '1' : '0';
        if (cotAchitata.value === '1') {
            cotAchitataAfis.classList.remove('hidden');
            document.querySelector('.incasari-tip-btn[data-tip="cotizatie"]').classList.add('hidden');
        } else {
            cotAchitataAfis.classList.add('hidden');
            document.querySelector('.incasari-tip-btn[data-tip="cotizatie"]').classList.remove('hidden');
        }
        resetModal();
        dialog.showModal();
    };

    document.addEventListener('click', function(e){
        if (e.target.closest('a[href]')) return;
        var btn = e.target.closest('.btn-deschide-incasari');
        if (btn && dialog) {
            e.preventDefault();
            e.stopPropagation();
            window.deschideIncasari(btn);
        }
    });

    document.querySelectorAll('.incasari-tip-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var t = this.getAttribute('data-tip');
            tipInput.value = t;
            document.querySelectorAll('.incasari-tip-btn').forEach(function(b){ b.classList.remove('bg-amber-200', 'dark:bg-amber-800/50', 'border-amber-500'); });
            this.classList.add('bg-amber-200', 'dark:bg-amber-800/50', 'border-amber-500');
            if (t === 'donatie') { wrapSuma.classList.remove('hidden'); labelSuma.textContent = 'Donație (RON)'; inputSuma.value = ''; inputSuma.readOnly = false; wrapReprezentand.classList.remove('hidden'); inputReprezentand.value = 'Donație'; }
            else if (t === 'taxa_participare') { wrapSuma.classList.remove('hidden'); labelSuma.textContent = 'Taxă participare (RON)'; inputSuma.value = ''; inputSuma.readOnly = false; wrapReprezentand.classList.add('hidden'); inputReprezentand.value = ''; }
            else if (t === 'alte') { wrapSuma.classList.remove('hidden'); labelSuma.textContent = 'Încasare (RON)'; inputSuma.value = ''; inputSuma.readOnly = false; wrapReprezentand.classList.add('hidden'); inputReprezentand.value = ''; }
            else { wrapSuma.classList.remove('hidden'); labelSuma.textContent = 'Cotizație (RON)'; inputSuma.value = valCot.value || '0'; inputSuma.readOnly = true; wrapReprezentand.classList.add('hidden'); inputReprezentand.value = ''; }
            if (modInput.value) { if (modInput.value === 'numerar' || modInput.value === 'chitanta_veche') { btnChitanta.classList.remove('hidden'); btnSalveaza.classList.add('hidden'); } else { btnChitanta.classList.add('hidden'); btnSalveaza.classList.remove('hidden'); } }
        });
    });

    document.querySelectorAll('.incasari-mod-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var m = this.getAttribute('data-mod');
            modInput.value = m;
            document.querySelectorAll('.incasari-mod-btn').forEach(function(b){ b.classList.remove('bg-amber-200', 'dark:bg-amber-800/50', 'border-amber-500'); });
            this.classList.add('bg-amber-200', 'dark:bg-amber-800/50', 'border-amber-500');
            if (m === 'numerar' || m === 'chitanta_veche') { btnChitanta.classList.remove('hidden'); btnSalveaza.classList.add('hidden'); }
            else { btnChitanta.classList.add('hidden'); btnSalveaza.classList.remove('hidden'); }
        });
    });

    function getSuma() {
        var tip = tipInput.value;
        if (tip === 'cotizatie') return parseFloat(valCot.value) || 0;
        return parseFloat(String(inputSuma.value).replace(',','.')) || 0;
    }

    function salveazaIncasare(cb) {
        if (!mid.value) { alert('Membru neselectat.'); return; }
        if (!tipInput.value) { alert('Selectați tipul de încasare.'); return; }
        if (!modInput.value) { alert('Selectați modul de plată.'); return; }
        var s = getSuma();
        if (['donatie','taxa_participare','alte'].indexOf(tipInput.value) >= 0 && s <= 0) { alert('Introduceți suma.'); return; }
        var fd = new FormData(form);
        fd.set('suma', s);
        fetch('/api/incasari-salveaza', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            })
            .then(function(data){
                if (data.ok && cb) cb(data);
                else alert(data.eroare || 'Eroare la salvare.');
            })
            .catch(function(err){ alert('Eroare: ' + (err.message || 'Eroare de rețea. Reîncărcați pagina.')); });
    }

    btnChitanta.addEventListener('click', function(){
        salveazaIncasare(function(data){
            window.open('util/incasari-chitanta-print.php?id=' + data.id, '_blank', 'width=800,height=600');
            dialog.close();
        });
    });
    btnSalveaza.addEventListener('click', function(){
        salveazaIncasare(function(){
            alert('Încasarea a fost salvată.');
            dialog.close();
            if (typeof window.location.reload === 'function') window.location.reload();
        });
    });
    document.getElementById('incasari-btn-inchide').addEventListener('click', function(){ dialog.close(); });
    dialog.addEventListener('click', function(e){ if (e.target === dialog) dialog.close(); });
})();
</script>
