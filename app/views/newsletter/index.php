<?php
/**
 * View: Newsletter — Lista campanii + modal campanie noua
 *
 * Variabile disponibile (setate de controller):
 *   $newslettere — lista toate newsletterele
 *   $liste_predefinite — listele predefinite de destinatari
 *   $eroare — mesaj eroare (optional)
 *   $succes — mesaj succes (optional)
 *   $edit_nl — newsletter de editat (optional, doar draft)
 */
$status_labels = [
    'draft' => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'],
    'trimis' => ['label' => 'Trimis', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'],
    'programat' => ['label' => 'Programat', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'],
];

// Map lista keys back to display names
$liste_display = [];
foreach ($liste_predefinite as $key => $info) {
    $liste_display[$key] = $info['name'];
}
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <div class="flex items-center gap-3">
            <button id="mobile-menu-btn" class="lg:hidden p-2 rounded hover:bg-slate-100 dark:hover:bg-gray-700" aria-label="Meniu">
                <i data-lucide="menu" class="w-5 h-5" aria-hidden="true"></i>
            </button>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <i data-lucide="mail" class="w-6 h-6" aria-hidden="true"></i>
                Newsletter
            </h1>
        </div>
        <button type="button" id="btn-campanie-noua" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-500">
            <i data-lucide="plus" class="w-4 h-4 mr-2" aria-hidden="true"></i>
            Campanie Noua
        </button>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300" role="alert">
            <i data-lucide="alert-circle" class="w-4 h-4 inline mr-1" aria-hidden="true"></i>
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-300" role="alert">
            <i data-lucide="check-circle" class="w-4 h-4 inline mr-1" aria-hidden="true"></i>
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <!-- Table of campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                    <thead class="bg-slate-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-gray-300 uppercase tracking-wider">Subiect</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-gray-300 uppercase tracking-wider">Lista destinatari</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-gray-300 uppercase tracking-wider">Nr. recipienti</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-gray-300 uppercase tracking-wider">Data</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 dark:text-gray-300 uppercase tracking-wider">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($newslettere)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-50" aria-hidden="true"></i>
                                <p>Nu exista newslettere. Creati prima campanie.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($newslettere as $nl): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-750">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white font-medium">
                                <?php echo htmlspecialchars($nl['subiect']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-300">
                                <?php
                                $cat = $nl['categoria_contacte'] ?? '';
                                echo htmlspecialchars($liste_display[$cat] ?? $cat);
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-slate-600 dark:text-gray-300">
                                <?php echo (int)$nl['nr_recipienti']; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $st = $nl['status'] ?? 'draft';
                                $si = $status_labels[$st] ?? $status_labels['draft'];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $si['class']; ?>">
                                    <?php echo $si['label']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-slate-600 dark:text-gray-300">
                                <?php
                                if ($nl['status'] === 'trimis' && $nl['data_trimiterii']) {
                                    echo date(DATETIME_FORMAT, strtotime($nl['data_trimiterii']));
                                } elseif ($nl['status'] === 'programat' && $nl['data_programata']) {
                                    echo date(DATETIME_FORMAT, strtotime($nl['data_programata']));
                                } elseif ($nl['created_at']) {
                                    echo date(DATETIME_FORMAT, strtotime($nl['created_at']));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($nl['status'] === 'draft'): ?>
                                    <a href="/newsletter?edit=<?php echo $nl['id']; ?>" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 transition" title="Editeaza">
                                        <i data-lucide="edit-3" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i> Editeaza
                                    </a>
                                    <form method="post" class="inline" onsubmit="return confirm('Sigur doriti sa stergeti acest draft?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="newsletter_id" value="<?php echo $nl['id']; ?>">
                                        <button type="submit" name="sterge_newsletter" value="1" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition" title="Sterge">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i> Sterge
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <a href="/newsletter-view?id=<?php echo $nl['id']; ?>" class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-slate-50 text-slate-700 hover:bg-slate-100 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600 transition" title="Vizualizeaza">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 mr-1" aria-hidden="true"></i> Vizualizeaza
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal: Campanie Noua / Editare -->
<div id="modal-newsletter" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="fixed inset-0 bg-black/50 transition-opacity" id="modal-overlay"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto border border-slate-200 dark:border-gray-700">
            <form method="post" action="/newsletter" enctype="multipart/form-data" id="form-newsletter">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_newsletter" value="1">
                <input type="hidden" name="actiune" value="draft" id="input-actiune">
                <input type="hidden" name="newsletter_id" value="<?php echo $edit_nl ? (int)$edit_nl['id'] : 0; ?>" id="input-newsletter-id">

                <!-- Header -->
                <div class="flex justify-between items-center p-5 border-b border-slate-200 dark:border-gray-700">
                    <h2 id="modal-title" class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="mail-plus" class="w-5 h-5" aria-hidden="true"></i>
                        <?php echo $edit_nl ? 'Editeaza Campanie' : 'Campanie Noua'; ?>
                    </h2>
                    <button type="button" id="btn-close-modal" class="p-2 hover:bg-slate-100 dark:hover:bg-gray-700 rounded-lg transition" aria-label="Inchide">
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-5 space-y-4">
                    <div>
                        <label for="nl_subiect" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Subiect <span class="text-red-500">*</span></label>
                        <input type="text" id="nl_subiect" name="subiect" required
                               value="<?php echo $edit_nl ? htmlspecialchars($edit_nl['subiect']) : ''; ?>"
                               class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="Subiectul emailului">
                    </div>

                    <div>
                        <label for="nl_nume_expeditor" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume expeditor</label>
                        <input type="text" id="nl_nume_expeditor" name="nume_expeditor"
                               value="<?php echo $edit_nl ? htmlspecialchars($edit_nl['nume_expeditor'] ?? '') : ''; ?>"
                               class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition"
                               placeholder="Numele afisat ca expeditor (optional)">
                    </div>

                    <div>
                        <label for="nl_lista" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Lista destinatari <span class="text-red-500">*</span></label>
                        <select id="nl_lista" name="lista_destinatari" required
                                class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                            <option value="">-- Selectati lista --</option>
                            <?php foreach ($liste_predefinite as $key => $info): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"
                                <?php echo ($edit_nl && ($edit_nl['categoria_contacte'] ?? '') === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($info['name']); ?> (<?php echo $info['count']; ?> destinatari)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="nl_continut" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Continut <span class="text-red-500">*</span></label>
                        <div class="border border-slate-300 dark:border-gray-600 rounded-lg overflow-hidden">
                            <div class="bg-slate-50 dark:bg-gray-700 px-3 py-2 border-b border-slate-300 dark:border-gray-600 flex flex-wrap gap-1" id="toolbar">
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition font-bold" data-cmd="bold" title="Bold">B</button>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition italic" data-cmd="italic" title="Italic">I</button>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition underline" data-cmd="underline" title="Underline">U</button>
                                <span class="border-l border-slate-300 dark:border-gray-500 mx-1"></span>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition" data-cmd="insertUnorderedList" title="Lista">
                                    <i data-lucide="list" class="w-3.5 h-3.5 inline" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition" data-cmd="insertOrderedList" title="Lista numerotata">
                                    <i data-lucide="list-ordered" class="w-3.5 h-3.5 inline" aria-hidden="true"></i>
                                </button>
                                <span class="border-l border-slate-300 dark:border-gray-500 mx-1"></span>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition" data-cmd="formatBlock" data-value="h2" title="Titlu">H2</button>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition" data-cmd="formatBlock" data-value="h3" title="Subtitlu">H3</button>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition" data-cmd="formatBlock" data-value="p" title="Paragraf">P</button>
                                <span class="border-l border-slate-300 dark:border-gray-500 mx-1"></span>
                                <button type="button" class="toolbar-btn px-2 py-1 text-xs rounded hover:bg-slate-200 dark:hover:bg-gray-600 transition" id="btn-add-link" title="Adauga link">
                                    <i data-lucide="link" class="w-3.5 h-3.5 inline" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="nl_editor" contenteditable="true"
                                 class="min-h-[200px] p-4 bg-white dark:bg-gray-800 text-slate-900 dark:text-white prose dark:prose-invert max-w-none focus:outline-none"
                                 style="min-height: 200px;"><?php echo $edit_nl ? $edit_nl['continut'] : ''; ?></div>
                        </div>
                        <textarea name="continut" id="nl_continut_hidden" class="hidden" aria-hidden="true"><?php echo $edit_nl ? htmlspecialchars($edit_nl['continut']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label for="nl_atasament" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Atasament (optional, max <?php echo NEWSLETTER_ATASAMENT_MAX_MB; ?> MB)</label>
                        <input type="file" id="nl_atasament" name="atasament"
                               class="w-full text-sm text-slate-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-300 transition">
                    </div>

                    <div>
                        <label for="nl_data_programata" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Data programata (optional — daca e completat, se programeaza; daca nu, se trimite imediat)</label>
                        <input type="datetime-local" id="nl_data_programata" name="data_programata"
                               value="<?php echo ($edit_nl && !empty($edit_nl['data_programata'])) ? date('Y-m-d\TH:i', strtotime($edit_nl['data_programata'])) : ''; ?>"
                               class="w-full rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-slate-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition">
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex flex-wrap gap-2 p-5 border-t border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-750 rounded-b-xl">
                    <button type="button" id="btn-save-draft" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-100 dark:hover:bg-gray-700 font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <i data-lucide="save" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                        Salvare Draft
                    </button>
                    <button type="button" id="btn-send" class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <i data-lucide="send" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                        <span id="btn-send-label">Trimite</span>
                    </button>
                    <button type="button" id="btn-close-modal-footer" class="ml-auto inline-flex items-center px-4 py-2 text-slate-500 dark:text-gray-400 hover:text-slate-700 dark:hover:text-gray-200 rounded-lg transition">
                        Anulare
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    var modal = document.getElementById('modal-newsletter');
    var overlay = document.getElementById('modal-overlay');
    var btnNew = document.getElementById('btn-campanie-noua');
    var btnClose = document.getElementById('btn-close-modal');
    var btnCloseFooter = document.getElementById('btn-close-modal-footer');
    var form = document.getElementById('form-newsletter');
    var inputActiune = document.getElementById('input-actiune');
    var editor = document.getElementById('nl_editor');
    var hidden = document.getElementById('nl_continut_hidden');
    var btnSaveDraft = document.getElementById('btn-save-draft');
    var btnSend = document.getElementById('btn-send');
    var btnSendLabel = document.getElementById('btn-send-label');
    var dataProgramata = document.getElementById('nl_data_programata');

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    btnNew.addEventListener('click', function() {
        // Reset form if not editing
        <?php if (!$edit_nl): ?>
        form.reset();
        editor.innerHTML = '';
        document.getElementById('input-newsletter-id').value = '0';
        <?php endif; ?>
        openModal();
    });

    btnClose.addEventListener('click', closeModal);
    btnCloseFooter.addEventListener('click', closeModal);
    overlay.addEventListener('click', closeModal);

    // Keyboard: Escape closes modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    // Update send button label based on scheduled date
    function updateSendLabel() {
        if (dataProgramata.value) {
            btnSendLabel.textContent = 'Programeaza';
        } else {
            btnSendLabel.textContent = 'Trimite';
        }
    }
    dataProgramata.addEventListener('change', updateSendLabel);
    updateSendLabel();

    // Sync editor content to hidden textarea before submit
    function syncContent() {
        hidden.value = editor.innerHTML;
    }

    btnSaveDraft.addEventListener('click', function() {
        syncContent();
        inputActiune.value = 'draft';
        form.submit();
    });

    btnSend.addEventListener('click', function() {
        syncContent();
        if (dataProgramata.value) {
            inputActiune.value = 'programeaza';
        } else {
            inputActiune.value = 'trimite';
            if (!confirm('Sigur doriti sa trimiteti acest newsletter acum?')) return;
        }
        form.submit();
    });

    // Toolbar commands
    document.querySelectorAll('.toolbar-btn[data-cmd]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var cmd = this.getAttribute('data-cmd');
            var val = this.getAttribute('data-value') || null;
            if (cmd === 'formatBlock' && val) {
                document.execCommand(cmd, false, '<' + val + '>');
            } else {
                document.execCommand(cmd, false, val);
            }
            editor.focus();
        });
    });

    // Link button
    var btnLink = document.getElementById('btn-add-link');
    if (btnLink) {
        btnLink.addEventListener('click', function(e) {
            e.preventDefault();
            var url = prompt('URL-ul linkului:', 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
            }
            editor.focus();
        });
    }

    // Auto-open modal if editing
    <?php if ($edit_nl): ?>
    openModal();
    <?php endif; ?>

    // Auto-open modal if there was an error (to show form again)
    <?php if (!empty($eroare)): ?>
    openModal();
    <?php endif; ?>
});
</script>
</body>
</html>
