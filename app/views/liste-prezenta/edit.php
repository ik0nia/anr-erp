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
            <a href="/util/lista-prezenta-print.php?id=<?php echo $id; ?>" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Print</a>
            <a href="/util/lista-prezenta-pdf.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm">Descarcă PDF</a>
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
                    <div class="relative">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500 pointer-events-none" aria-hidden="true"></i>
                                <input type="text" id="cauta-membru" placeholder="Nume, prenume, CNP, telefon..." class="w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white text-slate-900 focus:ring-2 focus:ring-amber-500" autocomplete="off">
                            </div>
                            <button type="button" id="btn-cauta" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">Caută</button>
                        </div>
                        <div id="rezultate-cautare" class="absolute left-0 right-0 top-full z-20 mt-1 border border-slate-200 dark:border-gray-600 rounded-lg p-2 max-h-48 overflow-y-auto hidden bg-white dark:bg-gray-800 text-slate-900 dark:text-white shadow-lg"></div>
                    </div>
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
                <a href="/activitati" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-white hover:bg-slate-100 dark:hover:bg-gray-700">Renunță</a>
            </div>
        </form>
    </div>
</main>
<script>
const participantiSelectati = <?php echo json_encode(array_map(function($p){
    if (!empty($p['membru_id'])) {
        return [
            'key' => 'membru:' . (int)$p['membru_id'],
            'tip' => 'membru',
            'id' => (int)$p['membru_id'],
            'numeComplet' => trim((string)($p['nume'] ?? '') . ' ' . (string)($p['prenume'] ?? ''))
        ];
    }
    return [
        'key' => 'manual:' . (int)($p['ordine'] ?? 0),
        'tip' => 'manual',
        'numeManual' => (string)($p['nume_manual'] ?? '')
    ];
}, $participanti)); ?>;

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizeName(row) {
    return String(row.nume_complet || '').trim();
}

function renderLista() {
    const container = document.getElementById('lista-participanti');
    const membriHidden = document.getElementById('membri_ids_json');
    const manualHidden = document.getElementById('participanti_manuali_json');

    const membriIds = participantiSelectati
        .filter(function(p) { return p.tip === 'membru' && p.id; })
        .map(function(p) { return p.id; });

    const manuali = participantiSelectati
        .filter(function(p) { return p.tip !== 'membru'; })
        .map(function(p, idx) {
            return {
                nume: (p.tip === 'manual' ? (p.numeManual || '') : (p.numeComplet || '')).trim(),
                ordine: idx + 1
            };
        })
        .filter(function(p) { return p.nume !== ''; });

    membriHidden.value = JSON.stringify(membriIds);
    manualHidden.value = JSON.stringify(manuali);

    if (participantiSelectati.length === 0) {
        container.innerHTML = '<p class="text-slate-500 dark:text-gray-400 text-sm">Niciun participant.</p>';
        return;
    }

    const rows = participantiSelectati.map(function(p, i) {
        const tipLabel = p.tip === 'contact' ? 'Contact' : (p.tip === 'manual' ? 'Manual' : 'Membru');
        const key = p.key || ('manual:' + i);
        const nume = p.tip === 'manual' ? (p.numeManual || '') : (p.numeComplet || '');
        const escapedNume = escapeHtml(nume);
        const escapedKey = escapeHtml(key).replace(/'/g, "\\'");
        const numeCell = p.tip === 'manual'
            ? '<input type="text" value="' + escapedNume + '" onchange="actualizeazaNumeManual(\'' + escapedKey + '\', this.value)" placeholder="Nume participant" class="w-full px-2 py-1 border border-slate-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white text-slate-900">'
            : '<span>' + escapedNume + '</span><span class="ml-2 text-xs text-slate-500 dark:text-gray-400">(' + tipLabel + ')</span>';
        return '' +
            '<tr class="border-b border-slate-200 dark:border-gray-600">' +
                '<td class="py-2 px-3 text-slate-900 dark:text-white">' + (i + 1) + '</td>' +
                '<td class="py-2 px-3 text-slate-900 dark:text-white">' + numeCell + '</td>' +
                '<td class="py-2 px-3"><button type="button" onclick="stergeParticipant(\'' + escapedKey + '\')" class="text-red-600 dark:text-red-400 hover:underline text-xs">Șterge</button></td>' +
            '</tr>';
    }).join('');

    container.innerHTML =
        '<table class="min-w-full text-sm border border-slate-200 dark:border-gray-600">' +
            '<thead class="bg-slate-100 dark:bg-gray-600">' +
                '<tr>' +
                    '<th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Nr.</th>' +
                    '<th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Nume</th>' +
                    '<th class="text-left py-2 px-3 border-b border-slate-200 dark:border-gray-500 text-slate-900 dark:text-white">Acțiune</th>' +
                '</tr>' +
            '</thead>' +
            '<tbody class="bg-white dark:bg-gray-800">' + rows + '</tbody>' +
        '</table>';
}

function stergeParticipant(key) {
    const idx = participantiSelectati.findIndex(function(p) { return p.key === key; });
    if (idx >= 0) {
        participantiSelectati.splice(idx, 1);
        renderLista();
    }
}

function adaugaParticipant(p) {
    if (!p || !p.key) return;
    if (participantiSelectati.some(function(x) { return x.key === p.key; })) return;
    participantiSelectati.push(p);
    renderLista();
}

function actualizeazaNumeManual(key, nume) {
    const item = participantiSelectati.find(function(p) { return p.key === key; });
    if (!item) return;
    item.numeManual = (nume || '').trim();
    renderLista();
}

function adaugaParticipantManual() {
    const uniq = 'manual:' + Date.now() + ':' + Math.floor(Math.random() * 10000);
    adaugaParticipant({ key: uniq, tip: 'manual', numeManual: '' });
}

function executaCautareParticipanti() {
    const input = document.getElementById('cauta-membru');
    const div = document.getElementById('rezultate-cautare');
    const q = input.value.trim();
    if (q.length < 2) {
        div.classList.add('hidden');
        return;
    }

    div.classList.remove('hidden');
    div.innerHTML = '<p class="text-slate-500 dark:text-gray-400 py-2">Se caută…</p>';

    fetch('/api/cauta-participanti-liste?q=' + encodeURIComponent(q))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            const rezultate = (d && d.participanti) ? d.participanti : [];
            if (!rezultate.length) {
                div.innerHTML = '<p class="text-slate-500 dark:text-gray-400 py-2">Niciun rezultat.</p>';
                return;
            }

            div.innerHTML = rezultate.map(function(r) {
                const tip = r.tip === 'contact' ? 'Contact' : 'Membru';
                return (
                    '<div class="flex items-center justify-between py-2 border-b border-slate-200 dark:border-gray-600">' +
                        '<button type="button" class="btn-adauga-participant px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-xs mr-3" data-tip="' + escapeHtml(r.tip || '') + '" data-id="' + (parseInt(r.id || 0, 10) || 0) + '" data-nume="' + escapeHtml(normalizeName(r)) + '">Adaugă</button>' +
                        '<span class="text-slate-900 dark:text-white flex-1">' + escapeHtml(normalizeName(r)) + '</span>' +
                        '<span class="ml-2 text-xs text-slate-500 dark:text-gray-400">(' + tip + ')</span>' +
                    '</div>'
                );
            }).join('');

            div.querySelectorAll('.btn-adauga-participant').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const tip = btn.dataset.tip === 'contact' ? 'contact' : 'membru';
                    const id = parseInt(btn.dataset.id || '0', 10);
                    const key = tip + ':' + id;
                    adaugaParticipant({
                        key: key,
                        tip: tip,
                        id: id,
                        numeComplet: btn.dataset.nume || ''
                    });
                });
            });
        })
        .catch(function() {
            div.innerHTML = '<p class="text-red-600 dark:text-red-400 py-2">Eroare la căutare. Încercați din nou.</p>';
        });
}

document.getElementById('btn-cauta').addEventListener('click', executaCautareParticipanti);
document.getElementById('btn-adauga-manual').addEventListener('click', adaugaParticipantManual);

document.getElementById('cauta-membru').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        executaCautareParticipanti();
    }
});

(function() {
    var deb = null;
    document.getElementById('cauta-membru').addEventListener('input', function() {
        clearTimeout(deb);
        var self = this;
        deb = setTimeout(function() {
            if (self.value.trim().length >= 2) executaCautareParticipanti();
            else document.getElementById('rezultate-cautare').classList.add('hidden');
        }, 300);
    });
})();

document.getElementById('form-lista').addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    var target = e.target;
    if (!target) return;
    if (target.id === 'cauta-membru') return;
    if (target.tagName === 'TEXTAREA') return;
    if (target.tagName === 'BUTTON') return;
    e.preventDefault();
});

document.getElementById('form-lista').onsubmit = function() {
    renderLista();
    return true;
};

renderLista();
</script>
</body>
</html>
