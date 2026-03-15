<?php
/**
 * View: Liste Prezenta — Editare lista de prezenta
 *
 * Variabile: $eroare, $id, $lista, $participanti, $coloane
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Modificare listă: <?php echo htmlspecialchars($lista['tip_titlu']); ?></h1>
        <div class="flex gap-2">
            <a href="lista-prezenta-print.php?id=<?php echo $id; ?>" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Print</a>
            <a href="lista-prezenta-pdf.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">Descarcă PDF</a>
            <a href="/activitati" class="px-4 py-2 border rounded-lg text-sm">Înapoi</a>
        </div>
    </header>
    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?><div class="mb-4 p-4 bg-red-100 rounded-lg"><?php echo htmlspecialchars($eroare); ?></div><?php endif; ?>
        <form method="post" id="form-lista" class="space-y-6 max-w-4xl">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="salveaza_lista" value="1">
            <input type="hidden" name="membri_ids" id="membri_ids_json" value="<?php echo htmlspecialchars(json_encode(array_filter(array_column($participanti, 'membru_id')))); ?>">
            <input type="hidden" name="participanti_manuali" id="participanti_manuali_json" value="<?php echo htmlspecialchars(json_encode(array_map(function($p) { return ['nume' => $p['nume_manual'] ?? '', 'ordine' => $p['ordine']]; }, array_filter($participanti, function($p) { return empty($p['membru_id']) && !empty($p['nume_manual']); })))); ?>">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Titlu</label>
                        <select name="tip_titlu" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" required aria-label="Selectează tipul titlului listei" aria-required="true">
                            <option value="Lista prezenta" <?php echo $lista['tip_titlu']==='Lista prezenta'?'selected':''; ?>>Listă prezență</option>
                            <option value="Tabel nominal" <?php echo $lista['tip_titlu']==='Tabel nominal'?'selected':''; ?>>Tabel nominal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Data</label>
                        <input type="date" name="data_lista" required value="<?php echo htmlspecialchars($lista['data_lista']); ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Activitate:</label>
                        <input type="text" name="detalii_activitate" value="<?php echo htmlspecialchars($lista['detalii_activitate'] ?? ''); ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-slate-900 dark:text-white">Detalii suplimentare (sus)</label>
                        <textarea name="detalii_suplimentare_sus" rows="2" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600"><?php echo htmlspecialchars($lista['detalii_suplimentare_sus'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Participanți</h2>
                <div class="mb-4">
                    <div class="flex gap-2">
                        <input type="text" id="cauta-membru" placeholder="Caută membri..." class="flex-1 px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        <button type="button" id="btn-cauta" class="px-4 py-2 bg-amber-600 text-white rounded-lg">Caută</button>
                    </div>
                    <div id="rezultate-cautare" class="mt-2 border border-slate-200 dark:border-gray-600 rounded-lg p-2 max-h-48 overflow-y-auto hidden bg-white dark:bg-gray-700/50 text-slate-900 dark:text-white"></div>
                </div>
                <div class="flex flex-wrap gap-4 mb-4">
                    <?php foreach (LISTE_COLOANE as $k => $l): ?>
                    <label class="flex items-center gap-2 text-slate-900 dark:text-white">
                        <input type="checkbox" name="coloane[]" value="<?php echo $k; ?>" <?php echo in_array($k, $coloane) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($l); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="mb-4">
                    <button type="button" id="btn-adauga-manual" class="px-4 py-2 bg-slate-600 dark:bg-gray-600 hover:bg-slate-700 dark:hover:bg-gray-700 text-white rounded-lg text-sm font-medium">
                        <i data-lucide="user-plus" class="w-4 h-4 inline-block mr-2" aria-hidden="true"></i>
                        Adaugă participant manual
                    </button>
                </div>
                <div id="lista-participanti" class="border border-slate-200 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800"></div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <label class="block text-sm font-medium mb-2 text-slate-900 dark:text-white">Detalii suplimentare (jos)</label>
                <textarea name="detalii_suplimentare_jos" rows="3" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600"><?php echo htmlspecialchars($lista['detalii_suplimentare_jos'] ?? ''); ?></textarea>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border p-6">
                <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Semnături (3 coloane)</h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Numele și funcția apar deasupra liniei de semnătură. În documentul generat, linia se afișează doar dacă sunt completate.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php
                    $semn = [['stanga',$lista['semnatura_stanga_nume']??'',$lista['semnatura_stanga_functie']??''],['centru',$lista['semnatura_centru_nume']??'',$lista['semnatura_centru_functie']??''],['dreapta',$lista['semnatura_dreapta_nume']??'',$lista['semnatura_dreapta_functie']??'']];
                    $lbl = ['Stânga','Centru','Dreapta'];
                    foreach ($semn as $i => $s): ?>
                    <div class="border border-slate-200 dark:border-gray-600 rounded-lg p-4">
                        <label class="block text-sm font-medium mb-1 text-slate-700 dark:text-gray-300"><?php echo $lbl[$i]; ?> - Nume</label>
                        <input type="text" name="semn_<?php echo $s[0]; ?>_nume" value="<?php echo htmlspecialchars($s[1]); ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <label class="block text-sm font-medium mt-2 mb-1 text-slate-700 dark:text-gray-300">Funcție</label>
                        <input type="text" name="semn_<?php echo $s[0]; ?>_functie" value="<?php echo htmlspecialchars($s[2]); ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        <div class="mt-3 border-b border-slate-300 dark:border-gray-500 h-10" aria-hidden="true" title="Linie semnătură"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează modificările listei de prezență">Salvează</button>
                <a href="/activitati" class="px-4 py-2 border rounded-lg">Renunță</a>
            </div>
        </form>
    </div>
</main>
<script>
let contorManual = <?php echo max(array_map(function($p) { return $p['ordine'] ?? 0; }, array_filter($participanti, function($p) { return empty($p['membru_id']); }))) + 1; ?>;
const membriSelectati = <?php echo json_encode(array_map(function($p){
    if (!empty($p['membru_id'])) {
        return ['id'=>$p['membru_id'],'nume'=>$p['nume'],'prenume'=>$p['prenume']];
    } else {
        return ['id'=>null,'numeManual'=>$p['nume_manual']??'','ordine'=>$p['ordine']];
    }
}, $participanti)); ?>;
function renderLista(){const c=document.getElementById('lista-participanti');const j=document.getElementById('membri_ids_json');const jManual=document.getElementById('participanti_manuali_json');j.value=JSON.stringify(membriSelectati.filter(m=>m.id).map(m=>m.id));jManual.value=JSON.stringify(membriSelectati.filter(m=>!m.id&&m.numeManual).map(m=>({nume:m.numeManual,ordine:m.ordine})));if(membriSelectati.length===0){c.innerHTML='<p class="text-slate-500 dark:text-gray-400 text-sm">Niciun participant.</p>';return;}c.innerHTML='<table class="min-w-full text-sm border border-slate-200 dark:border-gray-600"><thead class="bg-slate-100 dark:bg-gray-600"><tr><th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Nr.</th><th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Nume</th><th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Acțiune</th></tr></thead><tbody class="bg-white dark:bg-gray-800">'+membriSelectati.map((m,i)=>{const numeAfisat=m.id?(m.nume+' '+m.prenume):(m.numeManual||'');const idUnic=m.id||('manual_'+m.ordine);return '<tr class="border-b border-slate-200 dark:border-gray-600"><td class="py-2 px-3 text-slate-900 dark:text-white">'+(i+1)+'</td><td class="py-2 px-3 text-slate-900 dark:text-white">'+(m.id?numeAfisat:'<input type="text" value="'+(m.numeManual||'')+'" onchange="actualizeazaNumeManual('+m.ordine+', this.value)" placeholder="Nume participant" class="w-full px-2 py-1 border border-slate-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white text-slate-900">')+'</td><td class="py-2 px-3"><button type="button" onclick="stergeParticipant('+(m.id||0)+','+(m.ordine||0)+')" class="text-red-600 dark:text-red-400 hover:underline text-xs">Șterge</button></td></tr>';}).join('')+'</tbody></table>';}
function stergeParticipant(id,ordineManual){let i;if(id){i=membriSelectati.findIndex(m=>m.id==id);}else if(ordineManual){i=membriSelectati.findIndex(m=>!m.id&&m.ordine==ordineManual);}else{return;}if(i>=0){membriSelectati.splice(i,1);renderLista();}}
function adaugaParticipant(m){if(m&&m.id&&membriSelectati.some(x=>x.id==m.id))return;if(m){membriSelectati.push(m);}else{contorManual++;membriSelectati.push({id:null,numeManual:'',ordine:contorManual});}renderLista();}
function actualizeazaNumeManual(ordine,nume){const m=membriSelectati.find(x=>!x.id&&x.ordine==ordine);if(m){m.numeManual=nume.trim();const jManual=document.getElementById('participanti_manuali_json');jManual.value=JSON.stringify(membriSelectati.filter(m=>!m.id&&m.numeManual).map(m=>({nume:m.numeManual,ordine:m.ordine})));}}
function executaCautareEdit(){var q=document.getElementById('cauta-membru').value.trim();if(q.length<2)return;fetch('/api/cauta-membri?q='+encodeURIComponent(q)).then(r=>r.json()).then(d=>{var div=document.getElementById('rezultate-cautare');div.classList.remove('hidden');var membri=d.membri||[];div.innerHTML=membri.map(m=>'<div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-gray-600"><span>'+(m.nume||'')+' '+(m.prenume||'')+'</span><button type="button" class="btn-add px-2 py-1 bg-amber-600 text-white rounded text-xs" data-id="'+m.id+'" data-nume="'+(m.nume||'')+'" data-prenume="'+(m.prenume||'')+'">Adaugă</button></div>').join('')||'<p class="text-slate-500 dark:text-gray-400">Niciun rezultat.</p>';div.querySelectorAll('.btn-add').forEach(btn=>{btn.onclick=()=>adaugaParticipant({id:parseInt(btn.dataset.id),nume:btn.dataset.nume||'',prenume:btn.dataset.prenume||''});});});}
document.getElementById('btn-cauta').onclick=executaCautareEdit;
document.getElementById('cauta-membru').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();executaCautareEdit();}});
document.getElementById('btn-adauga-manual').onclick=function(){adaugaParticipant();};
document.getElementById('form-lista').onsubmit=function(){document.getElementById('membri_ids_json').value=JSON.stringify(membriSelectati.filter(m=>m.id).map(m=>m.id));document.getElementById('participanti_manuali_json').value=JSON.stringify(membriSelectati.filter(m=>!m.id&&m.numeManual).map(m=>({nume:m.numeManual,ordine:m.ordine})));return true;};
renderLista();
</script>
</body>
</html>
