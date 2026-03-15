<?php
/**
 * Modal generare documente - include în membri.php și membru-profil.php
 * Necesită variabila $templates_active (array) și funcționează cu data-membru-id pe butoane
 */
if (!isset($templates_active)) {
    $templates_active = [];
    try {
        if (isset($pdo)) {
            $stmt = $pdo->query("SELECT id, nume_afisare FROM documente_template WHERE activ = 1 ORDER BY nume_afisare");
            $templates_active = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}
?>
<?php
$doc_api_base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($doc_api_base === '' || $doc_api_base === '.') $doc_api_base = '';
?>
<dialog id="modal-generare-document" class="rounded-lg shadow-xl p-0 max-w-2xl w-full bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700"
        data-document-api-base="<?php echo htmlspecialchars($doc_api_base); ?>"
        aria-labelledby="modal-doc-titlu" aria-modal="true">
    <div class="p-6">
        <h2 id="modal-doc-titlu" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Generează document</h2>
        <input type="hidden" id="doc-membru-id" value="">
        <input type="hidden" id="doc-csrf-token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div id="doc-etapa-1">
            <label for="doc-template-select" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Selectați templateul</label>
            <select id="doc-template-select"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white mb-4"
                    aria-label="Selectează template-ul pentru generare document"
                    aria-required="true">
                <option value="">— Alegeți template —</option>
                <?php foreach ($templates_active as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nume_afisare']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($templates_active)): ?>
            <p class="text-amber-600 dark:text-amber-400 text-sm mb-4">Nu există templateuri active. Adăugați templateuri în <a href="/generare-documente" class="underline">Management Generare Documente</a>.</p>
            <?php endif; ?>
            <div class="mb-4">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="doc-include-data-generare" name="include_data_generare" value="1"
                           class="w-4 h-4 rounded border-slate-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500"
                           aria-describedby="doc-include-data-generare-desc">
                    <span id="doc-include-data-generare-desc" class="text-sm text-slate-700 dark:text-gray-300">Pune data pe document (DD.MM.YYYY) – completează tagul [datagenerare]</span>
                </label>
            </div>
            <div class="flex gap-2">
                <button type="button" id="doc-btn-genereaza"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium disabled:opacity-50"
                        <?php echo empty($templates_active) ? 'disabled' : ''; ?>
                        aria-label="Generează document cu template-ul selectat">
                    Generează
                </button>
                <button type="button" id="doc-btn-renunta-1" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-100 dark:hover:bg-gray-700" aria-label="Renunță la generare document">
                    Renunță
                </button>
            </div>
        </div>
        <div id="doc-etapa-2" class="hidden">
            <p id="doc-rezultat-msg" class="text-green-600 dark:text-green-400 mb-4"></p>
            <div class="flex flex-wrap gap-2 mb-4">
                <a id="doc-link-download-docx" href="#" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg" aria-label="Descarcă documentul în format Word DOCX">
                    <i data-lucide="file-down" class="w-4 h-4 mr-2" aria-hidden="true"></i> Descarcă DOCX
                </a>
                <button type="button" id="doc-btn-email" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg" aria-label="Trimite documentul pe email">
                    <i data-lucide="mail" class="w-4 h-4 mr-2" aria-hidden="true"></i> Trimite Email
                </button>
                <button type="button" id="doc-btn-print" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg" aria-label="Printează documentul">
                    <i data-lucide="printer" class="w-4 h-4 mr-2" aria-hidden="true"></i> Print
                </button>
                <button type="button" id="doc-btn-renunta-2" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300" aria-label="Renunță și închide fereastra">
                    Renunță
                </button>
            </div>
        </div>
        <div id="doc-etapa-email" class="hidden mt-4 pt-4 border-t border-slate-200 dark:border-gray-600">
            <h3 class="font-medium text-slate-900 dark:text-white mb-3">Trimite email</h3>
            <form id="form-email-document" class="space-y-3">
                <input type="hidden" name="docx_token" id="email-docx-token">
                <input type="hidden" name="pdf_token" id="email-pdf-token">
                <input type="hidden" name="membru_id" id="email-membru-id">
                <div>
                    <label class="block text-sm font-medium mb-1">Destinatar (email)</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Subiect</label>
                    <input type="text" name="subiect" id="email-subiect" placeholder="Document ERP ANR BIHOR" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Mesaj</label>
                    <textarea name="mesaj" rows="3" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="attach_docx" id="email-attach-docx" value="1" checked class="rounded border-slate-300 dark:border-gray-600">
                        <span>Atașează DOCX</span>
                    </label>
                    <label class="flex items-center gap-2" id="label-attach-pdf-wrap">
                        <input type="checkbox" name="attach_pdf" id="email-attach-pdf" value="1" class="rounded border-slate-300 dark:border-gray-600">
                        <span>Atașează PDF</span>
                    </label>
                </div>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="attach_ci" value="1" class="rounded border-slate-300 dark:border-gray-600"> Atașează act de identitate
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="attach_ch" value="1" class="rounded border-slate-300 dark:border-gray-600"> Atașează certificat handicap
                    </label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg" aria-label="Trimite email cu documentul atașat">Trimite</button>
                    <button type="button" id="doc-btn-ascunde-email" class="px-4 py-2 border rounded-lg" aria-label="Anulează trimitere email">Anulare</button>
                </div>
            </form>
        </div>
        <div id="doc-loading" class="hidden text-center py-8">
            <p class="text-slate-600 dark:text-gray-400">Se generează documentul...</p>
        </div>
        <div id="doc-error" class="hidden p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg mb-4"></div>
    </div>
</dialog>
<script>
(function() {
    const modal = document.getElementById('modal-generare-document');
    const membruIdInput = document.getElementById('doc-membru-id');
    const templateSelect = document.getElementById('doc-template-select');
    const etapa1 = document.getElementById('doc-etapa-1');
    const etapa2 = document.getElementById('doc-etapa-2');
    const etapaEmail = document.getElementById('doc-etapa-email');
    const loading = document.getElementById('doc-loading');
    const errorDiv = document.getElementById('doc-error');
    const rezultatMsg = document.getElementById('doc-rezultat-msg');
    const linkDocx = document.getElementById('doc-link-download-docx');
    
    let currentDocxToken = '';
    let currentPdfToken = null;
    let currentMembruId = 0;
    let currentTemplateId = 0;
    let currentTemplateNume = '';
    let currentMembruNume = '';

    document.querySelectorAll('[data-action="generare-document"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-membru-id');
            membruIdInput.value = id;
            currentMembruId = id;
            document.getElementById('email-membru-id').value = id;
            etapa1.classList.remove('hidden');
            etapa2.classList.add('hidden');
            etapaEmail.classList.add('hidden');
            loading.classList.add('hidden');
            errorDiv.classList.add('hidden');
            templateSelect.value = '';
            modal.showModal();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });

    function resetModal() {
        modal.close();
        etapa1.classList.remove('hidden');
        etapa2.classList.add('hidden');
        etapaEmail.classList.add('hidden');
    }

    document.getElementById('doc-btn-renunta-1').addEventListener('click', resetModal);
    document.getElementById('doc-btn-renunta-2').addEventListener('click', resetModal);

    document.getElementById('doc-btn-genereaza').addEventListener('click', function() {
        const mid = membruIdInput.value;
        const tid = templateSelect.value;
        if (!mid || !tid) return;
        loading.classList.remove('hidden');
        etapa1.classList.add('hidden');
        errorDiv.classList.add('hidden');
        const fd = new FormData();
        fd.append('membru_id', mid);
        fd.append('template_id', tid);
        var csrfEl = document.getElementById('doc-csrf-token');
        if (csrfEl && csrfEl.value) fd.append('_csrf_token', csrfEl.value);
        fd.append('include_data_generare', document.getElementById('doc-include-data-generare').checked ? '1' : '0');
        var urlGenereaza = '/api/genereaza-document';
        fetch(urlGenereaza, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) {
                return r.text().then(function(text) {
                    var data = null;
                    try { data = text ? JSON.parse(text) : null; } catch (e) {}
                    if (!data && r.ok) return Promise.reject(new Error('Răspuns invalid de la server.'));
                    if (!r.ok) {
                        var err = (data && data.error) ? data.error : ('Eroare ' + r.status + (r.status === 403 ? ': Token invalid. Reîncărcați pagina.' : ''));
                        return Promise.reject(new Error(err));
                    }
                    return data;
                });
            })
            .then(function(data) {
                loading.classList.add('hidden');
                if (data && data.success) {
                    currentDocxToken = data.docx_token;
                    currentPdfToken = data.pdf_token;
                    currentMembruId = mid;
                    currentTemplateId = tid;
                    // Încarcă numele template-ului și membrului pentru logging
                    const templateOption = templateSelect.options[templateSelect.selectedIndex];
                    currentTemplateNume = templateOption ? templateOption.text : '';
                    // Încarcă numele membrului din DOM sau din răspuns
                    const membruNameEl = document.querySelector('[data-membru-id="' + mid + '"]');
                    currentMembruNume = membruNameEl ? (membruNameEl.getAttribute('data-membru-nume') || membruNameEl.textContent.trim()) : '';
                    document.getElementById('email-docx-token').value = data.docx_token;
                    document.getElementById('email-pdf-token').value = data.pdf_token || '';
                    document.getElementById('email-membru-id').value = mid;
                    var pdfAvailable = !!(data.pdf_token);
                    var attachPdfCb = document.getElementById('email-attach-pdf');
                    var labelPdfWrap = document.getElementById('label-attach-pdf-wrap');
                    if (attachPdfCb && labelPdfWrap) {
                        attachPdfCb.checked = pdfAvailable;
                        attachPdfCb.disabled = !pdfAvailable;
                        labelPdfWrap.style.opacity = pdfAvailable ? '1' : '0.6';
                    }
                    rezultatMsg.textContent = 'Document generat. Nr. înregistrare: ' + (data.nr_inregistrare || '-');
                    linkDocx.href = 'util/descarca-document.php?token=' + encodeURIComponent(data.docx_token) + '&type=docx';
                    linkDocx.classList.remove('hidden');
                    etapa2.classList.remove('hidden');
                } else {
                    errorDiv.textContent = (data && data.error) ? data.error : 'Eroare la generare.';
                    errorDiv.classList.remove('hidden');
                    etapa1.classList.remove('hidden');
                }
                if (typeof lucide !== 'undefined') lucide.createIcons();
            })
            .catch(function(e) {
                loading.classList.add('hidden');
                errorDiv.textContent = (e && e.message) ? e.message : 'Eroare de rețea. Verificați conexiunea și reîncărcați pagina.';
                errorDiv.classList.remove('hidden');
                etapa1.classList.remove('hidden');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
    });

    document.getElementById('doc-btn-email').addEventListener('click', function() {
        etapaEmail.classList.toggle('hidden');
        if (!etapaEmail.classList.contains('hidden')) {
            var d = new Date();
            var dd = ('0' + d.getDate()).slice(-2);
            var mm = ('0' + (d.getMonth() + 1)).slice(-2);
            var yy = d.getFullYear();
            var subiectDefault = (currentMembruNume || '').trim() + (currentTemplateNume ? ' - ' + currentTemplateNume : '') + ' ' + dd + '.' + mm + '.' + yy;
            var subEl = document.getElementById('email-subiect');
            if (subEl && !subEl.value) subEl.value = subiectDefault.trim();
        }
    });

    document.getElementById('doc-btn-ascunde-email').addEventListener('click', function() {
        etapaEmail.classList.add('hidden');
    });

    document.getElementById('doc-btn-print').addEventListener('click', function() {
        if (linkDocx.href) {
            if (currentTemplateId > 0 && currentTemplateNume) {
                const logFd = new FormData();
                logFd.append('membru_id', currentMembruId || '0');
                logFd.append('template_id', currentTemplateId);
                logFd.append('template_nume', currentTemplateNume);
                logFd.append('membru_nume', currentMembruNume || '');
                fetch('/api/log-print-document', { method: 'POST', body: logFd })
                    .catch(function() { /* Ignoră erorile de logging */ });
            }
            var printUrl = linkDocx.href + (linkDocx.href.indexOf('?') !== -1 ? '&' : '?') + 'inline=1';
            window.open(printUrl, '_blank', 'width=800,height=600');
        }
    });

    document.getElementById('form-email-document').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('docx_token', document.getElementById('email-docx-token').value);
        fd.append('pdf_token', document.getElementById('email-pdf-token').value);
        fd.append('membru_id', document.getElementById('email-membru-id').value);
        fd.append('attach_docx', document.getElementById('email-attach-docx').checked ? '1' : '0');
        fd.append('attach_pdf', document.getElementById('email-attach-pdf').checked ? '1' : '0');
        fetch('/api/trimite-email-document', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(function(data) {
                if (data.success) {
                    alert('Email trimis cu succes.');
                    etapaEmail.classList.add('hidden');
                } else {
                    alert('Eroare: ' + (data.error || 'Nu s-a putut trimite.'));
                }
            })
            .catch(function() { alert('Eroare de rețea.'); });
    });
})();
</script>
