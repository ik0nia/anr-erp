<?php
/**
 * Modal generare documente - include în membri.php și membru-profil.php
 * Necesită variabila $templates_active (array) și funcționează cu data-membru-id pe butoane
 */
if (!isset($templates_active)) {
    $templates_active = [];
    try {
        if (isset($pdo)) {
            $stmt = $pdo->query("SELECT id, nume_afisare, foloseste_antet_platforma_erp FROM documente_template WHERE activ = 1 ORDER BY nume_afisare");
            $templates_active = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}
}
?>
<?php
$doc_api_base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($doc_api_base === '' || $doc_api_base === '.') $doc_api_base = '';
?>
<style>
#modal-generare-document::backdrop {
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
</style>
<dialog id="modal-generare-document" class="rounded-lg shadow-xl p-0 max-w-2xl w-full bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700"
        data-document-api-base="<?php echo htmlspecialchars($doc_api_base); ?>"
        aria-labelledby="modal-doc-titlu" aria-modal="true">
    <div class="p-6 relative">
        <button type="button"
                id="doc-btn-close-x"
                class="absolute top-3 right-3 inline-flex items-center justify-center w-8 h-8 rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                aria-label="Închide fereastra de generare document">
            <span aria-hidden="true">×</span>
        </button>
        <h2 id="modal-doc-titlu" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 pr-10">Generează document</h2>
        <input type="hidden" id="doc-membru-id" value="">
        <input type="hidden" id="doc-member-phone" value="">
        <input type="hidden" id="doc-member-email" value="">
        <input type="hidden" id="doc-csrf-token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div id="doc-etapa-1">
            <label for="doc-template-select" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">Selectați templateul</label>
            <select id="doc-template-select"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white mb-4"
                    aria-label="Selectează template-ul pentru generare document"
                    aria-required="true">
                <option value="">— Alegeți template —</option>
                <?php foreach ($templates_active as $t): ?>
                <option value="<?php echo $t['id']; ?>"
                        data-erp-header="<?php echo !empty($t['foloseste_antet_platforma_erp']) ? '1' : '0'; ?>">
                    <?php echo htmlspecialchars($t['nume_afisare']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($templates_active)): ?>
            <p class="text-amber-600 dark:text-amber-400 text-sm mb-4">Nu există templateuri active. Adăugați templateuri în <a href="/setari?tab=generare-documente" class="underline">Setări &gt; Generare documente</a>.</p>
            <?php endif; ?>
            <p id="doc-template-erp-header-indicator" class="hidden mt-2 text-xs text-amber-700 dark:text-amber-300" aria-live="polite">
                Pentru acest template este activată opțiunea „Folosește antetul platformei ERP”.
            </p>
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
                <button type="button" id="doc-btn-renunta-1" class="px-4 py-2 border border-slate-700 dark:border-slate-500 rounded-lg bg-slate-700 dark:bg-slate-600 text-white hover:bg-slate-600 dark:hover:bg-slate-500" aria-label="Renunță la generare document">
                    Renunță
                </button>
            </div>
        </div>
        <div id="doc-etapa-2" class="hidden">
            <p id="doc-rezultat-msg" class="text-green-600 dark:text-green-400 mb-4"></p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-4">
                <a id="doc-link-download-pdf" href="#" target="_blank" class="inline-flex w-full items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg" aria-label="Descarcă documentul în format PDF">
                    <i data-lucide="file-down" class="w-4 h-4 mr-2" aria-hidden="true"></i> Descarcă PDF
                </a>
                <a id="doc-link-download-docx" href="#" target="_blank" class="inline-flex w-full items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg" aria-label="Descarcă documentul în format Word DOCX">
                    <i data-lucide="file-down" class="w-4 h-4 mr-2" aria-hidden="true"></i> Descarcă DOCX
                </a>
                <a id="doc-btn-whatsapp" href="#" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg hidden" aria-label="Trimite documentul pe WhatsApp">
                    <i data-lucide="message-circle" class="w-4 h-4 mr-2" aria-hidden="true"></i> Trimite pe WhatsApp
                </a>
                <button type="button" id="doc-btn-email" class="inline-flex w-full items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg" aria-label="Trimite documentul pe email">
                    <i data-lucide="mail" class="w-4 h-4 mr-2" aria-hidden="true"></i> Trimite Email
                </button>
                <button type="button" id="doc-btn-print" class="inline-flex w-full items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg" aria-label="Printează documentul PDF">
                    <i data-lucide="printer" class="w-4 h-4 mr-2" aria-hidden="true"></i> Print
                </button>
                <button type="button" id="doc-btn-renunta-2" class="inline-flex w-full items-center justify-center px-4 py-2 border border-slate-700 dark:border-slate-500 rounded-lg bg-slate-700 dark:bg-slate-600 text-white hover:bg-slate-600 dark:hover:bg-slate-500" aria-label="Renunță și închide fereastra">
                    Renunță
                </button>
            </div>
        </div>
        <div id="doc-etapa-email" class="hidden mt-4 pt-4 border-t border-slate-200 dark:border-gray-600">
            <h3 class="font-medium text-slate-900 dark:text-white mb-3">Trimite email</h3>
            <form id="form-email-document" class="space-y-3">
                <input type="hidden" name="pdf_token" id="email-pdf-token">
                <input type="hidden" name="membru_id" id="email-membru-id">
                <input type="hidden" name="document_generat_id" id="email-document-generat-id">
                <input type="hidden" name="_csrf_token" id="email-csrf-token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
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
                <p class="text-xs text-slate-500 dark:text-gray-400">Documentul se trimite ca atașament PDF.</p>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg" aria-label="Trimite email cu documentul atașat">Trimite</button>
                    <button type="button" id="doc-btn-ascunde-email" class="px-4 py-2 border border-slate-700 dark:border-slate-500 rounded-lg bg-slate-700 dark:bg-slate-600 text-white hover:bg-slate-600 dark:hover:bg-slate-500" aria-label="Anulează trimitere email">Anulare</button>
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
    const erpHeaderIndicator = document.getElementById('doc-template-erp-header-indicator');
    const rezultatMsg = document.getElementById('doc-rezultat-msg');
    const linkDocx = document.getElementById('doc-link-download-docx');
    const linkPdf = document.getElementById('doc-link-download-pdf');
    const btnWhatsapp = document.getElementById('doc-btn-whatsapp');
    const btnEmail = document.getElementById('doc-btn-email');
    
    let currentDocxToken = '';
    let currentPdfToken = null;
    let currentDocumentGeneratId = 0;
    let currentMembruId = 0;
    let currentTemplateId = 0;
    let currentTemplateNume = '';
    let currentMembruNume = '';
    let currentPhone = '';
    let currentEmail = '';

    function updateErpHeaderIndicator() {
        if (!erpHeaderIndicator || !templateSelect) return;
        const opt = templateSelect.options[templateSelect.selectedIndex];
        const isErpHeader = !!(opt && opt.getAttribute('data-erp-header') === '1');
        erpHeaderIndicator.classList.toggle('hidden', !isErpHeader);
    }

    document.querySelectorAll('[data-action="generare-document"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-membru-id');
            membruIdInput.value = id;
            currentMembruId = id;
            document.getElementById('email-membru-id').value = id;
            const memberPhone = (this.getAttribute('data-membru-telefon') || '').trim();
            const memberEmail = (this.getAttribute('data-membru-email') || '').trim();
            currentMembruNume = (this.getAttribute('data-membru-nume') || '').trim();
            currentPhone = memberPhone;
            currentEmail = memberEmail;
            document.getElementById('doc-member-phone').value = memberPhone;
            document.getElementById('doc-member-email').value = memberEmail;
            const emailInput = document.querySelector('#form-email-document input[name="email"]');
            if (emailInput) emailInput.value = memberEmail;
            etapa1.classList.remove('hidden');
            etapa2.classList.add('hidden');
            etapaEmail.classList.add('hidden');
            loading.classList.add('hidden');
            errorDiv.classList.add('hidden');
            templateSelect.value = '';
            updateErpHeaderIndicator();
            modal.showModal();
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });

    if (templateSelect) {
        templateSelect.addEventListener('change', updateErpHeaderIndicator);
    }

    function resetModal() {
        modal.close();
        etapa1.classList.remove('hidden');
        etapa2.classList.add('hidden');
        etapaEmail.classList.add('hidden');
        updateErpHeaderIndicator();
        currentDocumentGeneratId = 0;
        if (btnWhatsapp) btnWhatsapp.classList.add('hidden');
        if (btnEmail) btnEmail.classList.remove('hidden');
    }

    document.getElementById('doc-btn-renunta-1').addEventListener('click', resetModal);
    document.getElementById('doc-btn-renunta-2').addEventListener('click', resetModal);
    document.getElementById('doc-btn-close-x').addEventListener('click', resetModal);

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
                    currentDocumentGeneratId = parseInt(data.document_generat_id || 0, 10) || 0;
                    currentMembruId = mid;
                    currentTemplateId = tid;
                    // Încarcă numele template-ului și membrului pentru logging
                    const templateOption = templateSelect.options[templateSelect.selectedIndex];
                    currentTemplateNume = templateOption ? templateOption.text : '';
                    // Încarcă numele membrului din DOM sau din răspuns
                    const membruNameEl = document.querySelector('[data-membru-id="' + mid + '"]');
                    currentMembruNume = membruNameEl ? (membruNameEl.getAttribute('data-membru-nume') || membruNameEl.textContent.trim()) : '';
                    document.getElementById('email-pdf-token').value = data.pdf_token || '';
                    document.getElementById('email-membru-id').value = mid;
                    document.getElementById('email-document-generat-id').value = currentDocumentGeneratId ? String(currentDocumentGeneratId) : '';
                    const memberPhoneResp = (data.member_phone || '').trim();
                    const memberEmailResp = (data.member_email || '').trim();
                    if (memberPhoneResp) currentPhone = memberPhoneResp;
                    if (memberEmailResp) currentEmail = memberEmailResp;
                    const emailInput = document.querySelector('#form-email-document input[name="email"]');
                    if (emailInput && !emailInput.value) emailInput.value = currentEmail;
                    rezultatMsg.textContent = 'Document generat. Nr. înregistrare: ' + (data.nr_inregistrare || '-');
                    if (data.pdf_token) {
                        linkPdf.href = 'util/descarca-document.php?token=' + encodeURIComponent(data.pdf_token) + '&type=pdf';
                        linkPdf.classList.remove('hidden');
                    } else {
                        linkPdf.classList.add('hidden');
                    }
                    if (data.docx_token) {
                        linkDocx.href = 'util/descarca-document.php?token=' + encodeURIComponent(data.docx_token) + '&type=docx';
                        linkDocx.classList.remove('hidden');
                    } else {
                        linkDocx.classList.add('hidden');
                    }
                    var waText = 'Documentul solicitat il gasiti atasat acestui mesaj. Pentru informatii suplimentare, va stam la dispozitie cu drag.';
                    if (currentPhone && data.pdf_token) {
                        var phoneDigits = currentPhone.replace(/\D/g, '');
                        if (phoneDigits.length > 0) {
                            var pdfAbs = window.location.origin + '/util/descarca-document.php?token=' + encodeURIComponent(data.pdf_token || '') + '&type=pdf';
                            var waFullText = waText + ' Link document: ' + pdfAbs;
                            btnWhatsapp.href = 'https://wa.me/' + phoneDigits + '?text=' + encodeURIComponent(waFullText);
                            btnWhatsapp.setAttribute('data-doc-id', currentDocumentGeneratId ? String(currentDocumentGeneratId) : '');
                            btnWhatsapp.classList.remove('hidden');
                        } else {
                            btnWhatsapp.classList.add('hidden');
                        }
                    } else {
                        btnWhatsapp.classList.add('hidden');
                    }
                    if (currentEmail) {
                        btnEmail.classList.remove('hidden');
                    } else {
                        btnEmail.classList.add('hidden');
                    }
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
        if (!currentEmail) return;
        etapaEmail.classList.toggle('hidden');
        if (!etapaEmail.classList.contains('hidden')) {
            var d = new Date();
            var dd = ('0' + d.getDate()).slice(-2);
            var mm = ('0' + (d.getMonth() + 1)).slice(-2);
            var yy = d.getFullYear();
            var subiectDefault = 'Documentul a fost generat';
            var subEl = document.getElementById('email-subiect');
            if (subEl && !subEl.value) subEl.value = subiectDefault.trim();
            var msgEl = document.querySelector('#form-email-document textarea[name="mesaj"]');
            if (msgEl && !msgEl.value) msgEl.value = 'Buna ziua, va trimitem atasat documentul completat.';
        }
    });

    document.getElementById('doc-btn-ascunde-email').addEventListener('click', function() {
        etapaEmail.classList.add('hidden');
    });

    document.getElementById('doc-btn-print').addEventListener('click', function() {
        if (linkPdf && linkPdf.href) {
            if (currentTemplateId > 0 && currentTemplateNume) {
                const logFd = new FormData();
                logFd.append('membru_id', currentMembruId || '0');
                logFd.append('template_id', currentTemplateId);
                logFd.append('template_nume', currentTemplateNume);
                logFd.append('membru_nume', currentMembruNume || '');
                fetch('/api/log-print-document', { method: 'POST', body: logFd })
                    .catch(function() { /* Ignoră erorile de logging */ });
            }
            var printUrl = linkPdf.href + (linkPdf.href.indexOf('?') !== -1 ? '&' : '?') + 'inline=1';
            window.open(printUrl, '_blank', 'width=800,height=600');
        }
    });

    if (btnWhatsapp) {
        btnWhatsapp.addEventListener('click', function() {
            if (!currentMembruId) return;
            fetch('/api/log-actiune-membru', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ membru_id: currentMembruId, actiune: 'documente: WhatsApp deschis pentru document generat' })
            }).catch(function() {});

            var docId = parseInt(this.getAttribute('data-doc-id') || '0', 10) || 0;
            if (docId > 0) {
                var fd = new FormData();
                fd.append('_csrf_token', document.getElementById('doc-csrf-token').value || '');
                fd.append('document_generat_id', String(docId));
                fd.append('actiune', 'whatsapp');
                fd.append('membru_id', String(currentMembruId || '0'));
                fetch('/api/marcheaza-actiune-document-generat', { method: 'POST', body: fd }).catch(function() {});
            }
        });
    }

    document.getElementById('form-email-document').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('pdf_token', document.getElementById('email-pdf-token').value);
        fd.append('membru_id', document.getElementById('email-membru-id').value);
        fd.append('document_generat_id', document.getElementById('email-document-generat-id').value || '');
        fd.append('_csrf_token', document.getElementById('email-csrf-token').value || '');
        fetch('/api/trimite-email-document', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(function(data) {
                if (data.success) {
                    alert('Email trimis cu succes.');
                    if (currentMembruId) {
                        fetch('/api/log-actiune-membru', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ membru_id: currentMembruId, actiune: 'documente: Email trimis cu document generat' })
                        }).catch(function() {});
                    }
                    etapaEmail.classList.add('hidden');
                } else {
                    alert('Eroare: ' + (data.error || 'Nu s-a putut trimite.'));
                }
            })
            .catch(function() { alert('Eroare de rețea.'); });
    });
})();
</script>
