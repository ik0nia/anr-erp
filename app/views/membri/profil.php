<?php
/**
 * View: Membri — Profil membru
 *
 * Variabile disponibile (setate de controller):
 *   $membru, $eroare, $succes, $membru_id, $varsta,
 *   $scutire_cotizatie, $cotizatie_achitata_an_curent, $valoare_cotizatie_an,
 *   $alerts, $istoric_modificari, $lista_incasari,
 *   $tipuri_afisare, $moduri_plata_afisare
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <?php if (!$membru): ?>
    <div class="p-6">
        <div class="max-w-xl p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <p class="font-semibold">Membru negasit sau eroare la incarcarea datelor.</p>
            <?php if (!empty($eroare)): ?>
            <p class="mt-2 text-sm"><?php echo htmlspecialchars($eroare); ?></p>
            <?php endif; ?>
        </div>
        <p class="mt-4">
            <a href="/membri" class="inline-flex items-center gap-2 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 text-slate-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <i data-lucide="arrow-left" class="w-4 h-4" aria-hidden="true"></i>
                Inapoi la lista de membri
            </a>
        </p>
    </div>
    <?php else: ?>
    <?php if (!empty($eroare)): ?>
    <div id="banner-eroare-salvare" class="fixed top-0 left-0 right-0 z-[100] px-4 py-3 bg-red-600 text-white shadow-lg flex items-center justify-between gap-4" role="alert" aria-live="assertive">
        <div class="flex-1 min-w-0">
            <p class="font-semibold">Salvare esuata:</p>
            <p class="text-sm opacity-95 truncate" title="<?php echo htmlspecialchars($eroare); ?>"><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <button type="button" onclick="document.getElementById('banner-eroare-salvare').remove()" class="flex-shrink-0 px-3 py-1 bg-red-700 hover:bg-red-800 rounded focus:outline-none focus:ring-2 focus:ring-white" aria-label="Inchide mesajul">x</button>
    </div>
    <?php endif; ?>
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                Profil Membru: <?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>
            </h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-gray-400" aria-label="Numar si data dosar">
                <?php if (!empty($membru['dosarnr']) || !empty($membru['dosardata'])): ?>
                <span class="font-medium text-slate-800 dark:text-gray-200">Nr. dosar: <?php echo htmlspecialchars($membru['dosarnr'] ?? '-'); ?></span>
                <?php if (!empty($membru['dosardata'])): ?>
                <span class="mx-2 text-slate-400 dark:text-gray-500" aria-hidden="true">|</span>
                <span class="font-medium text-slate-800 dark:text-gray-200">Data dosar: <?php echo date(DATE_FORMAT, strtotime($membru['dosardata'])); ?></span>
                <?php endif; ?>
                <?php else: ?>
                <span class="text-slate-500 dark:text-gray-500">Nr. dosar: -- | Data dosar: --</span>
                <?php endif; ?>
            </p>
            <div class="flex flex-wrap items-center gap-4 mt-2">
                <div class="flex items-center gap-2">
                    <label for="status_dosar_header" class="text-sm text-slate-600 dark:text-gray-400">Status:</label>
                    <select id="status_dosar_header"
                            onchange="document.getElementById('status_dosar').value = this.value; document.getElementById('form-membru-profil').requestSubmit();"
                            class="px-2 py-1 text-sm border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                        <option value="Activ" <?php echo ($membru['status_dosar'] ?? 'Activ') === 'Activ' ? 'selected' : ''; ?>>Activ</option>
                        <option value="Expirat" <?php echo ($membru['status_dosar'] ?? '') === 'Expirat' ? 'selected' : ''; ?>>Expirat</option>
                        <option value="Suspendat" <?php echo ($membru['status_dosar'] ?? '') === 'Suspendat' ? 'selected' : ''; ?>>Suspendat</option>
                        <option value="Retras" <?php echo ($membru['status_dosar'] ?? '') === 'Retras' ? 'selected' : ''; ?>>Retras</option>
                        <option value="Decedat" <?php echo ($membru['status_dosar'] ?? '') === 'Decedat' ? 'selected' : ''; ?>>Decedat</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if (!empty($membru['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($membru['email']); ?>"
               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Trimite email">
                <i data-lucide="mail" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Email
            </a>
            <?php endif; ?>
            <?php if (!empty($membru['telefonnev'])): ?>
            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $membru['telefonnev']); ?>"
               target="_blank"
               class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Mesaj WhatsApp">
                <i data-lucide="message-circle" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                WhatsApp
            </a>
            <?php endif; ?>
            <button type="button"
                    data-action="generare-document"
                    data-membru-id="<?php echo $membru['id']; ?>"
                    class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    aria-label="Genereaza document pentru <?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>">
                <i data-lucide="file-text" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Genereaza Document
            </button>
            <?php if (!empty($scutire_cotizatie)): ?>
            <a href="/setari?tab=cotizatii#scutire-<?php echo (int)$scutire_cotizatie['id']; ?>"
               class="inline-flex items-center px-3 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Scutit de cotizatie - vezi detalii scutire">
                <i data-lucide="shield-check" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Scutit de cotizatie
            </a>
            <?php else: ?>
            <button type="button"
                    class="btn-deschide-incasari inline-flex items-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    data-membru-id="<?php echo (int)$membru['id']; ?>"
                    data-valoare-cot="<?php echo number_format($valoare_cotizatie_an, 2, '.', ''); ?>"
                    data-cot-achitata="<?php echo $cotizatie_achitata_an_curent ? '1' : '0'; ?>"
                    aria-label="Incaseaza">
                <i data-lucide="dollar-sign" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Incaseaza
            </button>
            <?php endif; ?>
            <button type="button"
                    id="btn-editeaza-datele"
                    class="inline-flex items-center px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    aria-pressed="false"
                    aria-label="Comuta in modul de editare a datelor membrului">
                <i data-lucide="edit-3" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Editeaza datele
            </button>
            <a href="/membri"
               class="inline-flex items-center px-3 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <i data-lucide="arrow-left" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Inapoi
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div id="mesaj-eroare-salvare" class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
            <p class="font-semibold">Salvare esuata:</p>
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($alerts)): ?>
        <div class="mb-3 py-2 px-3 rounded-r border-l-4 flex flex-wrap items-center gap-3 bg-slate-100 dark:bg-slate-700/50" role="alert" aria-live="polite">
            <?php
            foreach ($alerts as $alert):
                $alert_key = $alert['alert_key'] ?? '';
                $is_error = $alert['tip'] === 'error';
                $is_ci = $alert_key === 'ci';
                $is_ch = $alert_key === 'ch';
                $is_cotizatie = $alert_key === 'cotizatie';
                if ($is_error) {
                    $bg = 'bg-red-600';
                    $borderCls = 'border-red-800';
                    $textCls = 'text-white';
                } elseif ($is_ci) {
                    $bg = 'bg-orange-500';
                    $borderCls = 'border-orange-700';
                    $textCls = 'text-black';
                } elseif ($is_ch) {
                    $bg = 'bg-yellow-400';
                    $borderCls = 'border-yellow-600';
                    $textCls = 'text-black';
                } elseif ($is_cotizatie) {
                    $bg = 'bg-amber-500';
                    $borderCls = 'border-amber-700';
                    $textCls = 'text-black';
                } else {
                    $bg = 'bg-slate-500';
                    $borderCls = 'border-slate-700';
                    $textCls = 'text-white';
                }
            ?>
            <div class="flex items-center gap-2 <?php echo $bg; ?> <?php echo $textCls; ?> py-1.5 px-2.5 rounded border-l-4 <?php echo $borderCls; ?>">
                <i data-lucide="<?php echo $is_error ? 'alert-circle' : 'alert-triangle'; ?>" class="w-4 h-4 flex-shrink-0" aria-hidden="true"></i>
                <span class="text-sm font-medium"><?php echo htmlspecialchars($alert['mesaj']); ?></span>
                <?php if ($alert_key && in_array($alert_key, ['ci', 'ch', 'cotizatie'])): ?>
                <form method="post" action="/membru-profil?id=<?php echo $membru_id; ?>" class="inline ml-1">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="marcheaza_alert_informat" value="1">
                    <input type="hidden" name="membru_id" value="<?php echo $membru_id; ?>">
                    <input type="hidden" name="alert_tip" value="<?php echo htmlspecialchars($alert_key); ?>">
                    <label class="flex items-center gap-1 cursor-pointer" title="Bifeaza daca membrul a fost informat; debifeaza pentru a reseta (se salveaza la schimbare).">
                        <input type="checkbox" name="marcat_informat" value="1" onchange="(this.form.requestSubmit && this.form.requestSubmit()) || this.form.submit()" <?php echo !empty($alert['dismissed']) ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded border-2 border-current focus:ring-2 focus:ring-black"
                               aria-label="Membru informat; poti debifa pentru a reseta marcarea">
                        <span class="text-xs font-medium">Membru informat</span>
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <?php require_once APP_ROOT . '/app/views/membri/form.php'; ?>
            <?php render_formular_profil_membru($membru, $eroare, $istoric_modificari); ?>
        </div>

        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" id="sectiune-istoric-incasari">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2" id="titlu-istoric-incasari">
                <i data-lucide="history" class="w-5 h-5" aria-hidden="true"></i>
                Istoric incasari
            </h2>
            <?php if (empty($lista_incasari)): ?>
            <p class="text-slate-600 dark:text-gray-400 py-4">Nu exista incasari alocate acestui membru.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-labelledby="titlu-istoric-incasari" aria-describedby="desc-istoric-incasari">
                    <caption id="desc-istoric-incasari" class="sr-only">Lista tuturor incasarilor alocate membrului</caption>
                    <thead class="bg-slate-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Tip</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Suma (RON)</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Mod plata</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Serie / Nr. chitanta</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($lista_incasari as $inc): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo $inc['data_incasare'] ? date(DATE_FORMAT, strtotime($inc['data_incasare'])) : '-'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($tipuri_afisare[$inc['tip']] ?? $inc['tip']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo number_format((float)$inc['suma'], 2, ',', ' '); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($moduri_plata_afisare[$inc['mod_plata']] ?? $inc['mod_plata']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300">
                                <?php echo htmlspecialchars(($inc['seria_chitanta'] ?? '') . ' ' . ($inc['nr_chitanta'] ?? '')); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex flex-wrap gap-2">
                                    <a href="util/incasari-chitanta-print.php?id=<?php echo (int)$inc['id']; ?>"
                                       target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1.5 rounded border border-amber-500 dark:border-amber-400 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/30 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                       aria-label="Vizualizeaza chitanta <?php echo htmlspecialchars(($inc['seria_chitanta'] ?? '') . ' ' . ($inc['nr_chitanta'] ?? '')); ?>">
                                        <i data-lucide="eye" class="w-4 h-4" aria-hidden="true"></i>
                                        Vizualizeaza
                                    </a>
                                    <a href="util/incasari-chitanta-pdf.php?id=<?php echo (int)$inc['id']; ?>"
                                       class="inline-flex items-center gap-1 px-2 py-1.5 rounded border border-slate-400 dark:border-gray-500 text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                       aria-label="Descarca PDF chitanta <?php echo htmlspecialchars(($inc['seria_chitanta'] ?? '') . ' ' . ($inc['nr_chitanta'] ?? '')); ?>">
                                        <i data-lucide="file-down" class="w-4 h-4" aria-hidden="true"></i>
                                        Descarca PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php require_once APP_ROOT . '/includes/documente_modal.php'; ?>
<?php require_once APP_ROOT . '/includes/incasari_modal.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    var eroareEl = document.getElementById('mesaj-eroare-salvare');
    if (eroareEl) {
        eroareEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var text = (document.querySelector('#banner-eroare-salvare p.text-sm') || eroareEl).textContent.trim();
        if (text) { alert('Salvare esuata:\n\n' + text); }
    }

    var formProfil = document.getElementById('form-membru-profil');
    var btnEdit = document.getElementById('btn-editeaza-datele');
    var btnSave = document.getElementById('btn-salveaza-datele');

    if (formProfil && btnEdit && btnSave) {
        var inEditMode = false;

        function setEditMode(edit) {
            inEditMode = edit;

            var fields = formProfil.querySelectorAll('input, select, textarea');
            fields.forEach(function(el) {
                if (el.type === 'hidden') return;
                if (edit) {
                    el.removeAttribute('disabled');
                    el.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    el.setAttribute('disabled', 'disabled');
                    el.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });

            btnSave.classList.toggle('hidden', !edit);
            btnEdit.setAttribute('aria-pressed', edit ? 'true' : 'false');
            var labelSpan = btnEdit.querySelector('span');
            if (!labelSpan) {
                btnEdit.textContent = edit ? 'Salveaza datele' : 'Editeaza datele';
            } else {
                labelSpan.textContent = edit ? 'Salveaza datele' : 'Editeaza datele';
            }
        }

        setEditMode(false);

        btnEdit.addEventListener('click', function() {
            if (!inEditMode) {
                setEditMode(true);
                var firstInput = formProfil.querySelector('input:not([type="hidden"]), select, textarea');
                if (firstInput) {
                    firstInput.focus();
                }
            } else {
                btnSave.scrollIntoView({ behavior: 'smooth', block: 'center' });
                btnSave.focus();
            }
        });
    }
});
</script>
</body>
</html>
