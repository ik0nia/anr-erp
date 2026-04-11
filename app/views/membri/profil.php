<?php
/**
 * View: Membri — Profil membru (card-based, view-only by default, edit per card)
 *
 * Variabile disponibile (setate de controller):
 *   $membru, $eroare, $succes, $membru_id, $varsta,
 *   $scutire_cotizatie, $cotizatie_achitata_an_curent, $valoare_cotizatie_an, $an_cotizatie,
 *   $alerts, $istoric_modificari, $lista_incasari,
 *   $tipuri_afisare, $moduri_plata_afisare, $jurnal, $documente_generate
 */

// Helper: display value or dash
$dv = function($field, $format = null) use ($membru) {
    $v = $membru[$field] ?? '';
    if ($v === '' || $v === null) return '<span class="text-slate-400 dark:text-gray-500">—</span>';
    if ($format === 'date' && $v) {
        $ts = strtotime($v);
        return $ts ? htmlspecialchars(date(DATE_FORMAT, $ts)) : htmlspecialchars($v);
    }
    return htmlspecialchars($v);
};

// Helper: value for form field
$val = function($field, $default = '') use ($membru) {
    $value = $membru[$field] ?? $default;
    if ($value === null || $value === '') return htmlspecialchars($default);
    if (is_bool($value)) return $value ? '1' : '0';
    return htmlspecialchars($value);
};

$selected = function($field, $value) use ($membru) {
    return (isset($membru[$field]) && $membru[$field] == $value) ? 'selected' : '';
};

$checked = function($field, $value) use ($membru) {
    return (isset($membru[$field]) && $membru[$field] == $value) ? 'checked' : '';
};

$input_class = 'w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700';
$btn_edit_class = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-600 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors';
$btn_save_class = 'inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors';
$btn_cancel_class = 'inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-slate-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-slate-300 dark:border-gray-600 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-colors';

$edit_card = trim((string)($_GET['edit_card'] ?? ''));
$carduri_editabile = [
    'date-personale',
    'date-contact',
    'domiciliu',
    'date-handicap',
    'certificat-handicap',
    'act-identitate',
    'dosar',
    'observatii',
    'biblioteca-online',
    'atasamente-docs',
];
if (!in_array($edit_card, $carduri_editabile, true)) {
    $edit_card = '';
}
$is_card_in_edit = function($card_name) use ($edit_card) {
    return $edit_card === $card_name;
};

$scutire_config = [];
if (!empty($scutire_cotizatie_membru) && is_array($scutire_cotizatie_membru)) {
    $scutire_config = $scutire_cotizatie_membru;
} elseif (!empty($scutire_cotizatie) && is_array($scutire_cotizatie)) {
    $scutire_config = $scutire_cotizatie;
}
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

    <!-- Header -->
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
                    <span class="text-sm text-slate-600 dark:text-gray-400">Status:</span>
                    <?php
                    $status_val = $membru['status_dosar'] ?? 'Activ';
                    $status_styles = [
                        'Activ' => 'background:#059669;color:#fff;',
                        'Suspendat' => 'background:#d97706;color:#fff;',
                        'Expirat' => 'background:#dc2626;color:#fff;',
                        'Transferat' => 'background:#7c3aed;color:#fff;',
                        'Retras' => 'background:#be185d;color:#fff;',
                        'Decedat' => 'background:#374151;color:#fff;',
                        'Arhiva' => 'background:#6b7280;color:#fff;',
                    ];
                    $s_style = $status_styles[$status_val] ?? 'background:#94a3b8;color:#fff;';
                    ?>
                    <span class="px-2.5 py-1 text-sm font-semibold rounded-full" style="<?php echo $s_style; ?>">
                        <?php echo htmlspecialchars($status_val); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if (!empty($membru['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($membru['email']); ?>"
               onclick="logActiuneMembru(<?php echo $membru['id']; ?>, 'Mesaj Email')"
               class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Trimite email">
                <i data-lucide="mail" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Email
            </a>
            <?php endif; ?>
            <?php if (!empty($membru['telefonnev'])): ?>
            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $membru['telefonnev']); ?>"
               target="_blank"
               onclick="logActiuneMembru(<?php echo $membru['id']; ?>, 'Mesaj WhatsApp')"
               class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Mesaj WhatsApp">
                <i data-lucide="message-circle" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                WhatsApp
            </a>
            <?php endif; ?>
            <?php if (!empty($membru['telefonapartinator'])): ?>
            <a href="https://wa.me/<?php echo preg_replace('/\D/', '', $membru['telefonapartinator']); ?>"
               target="_blank"
               onclick="logActiuneMembru(<?php echo $membru['id']; ?>, 'Mesaj WhatsApp Apartinator')"
               class="inline-flex items-center px-3 py-2 bg-green-800 hover:bg-green-900 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
               aria-label="Mesaj WhatsApp Apartinator">
                <i data-lucide="message-circle" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                WhatsApp Apartinator
            </a>
            <?php endif; ?>
            <button type="button"
                    data-action="generare-document"
                    data-membru-id="<?php echo $membru['id']; ?>"
                    data-membru-nume="<?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>"
                    data-membru-telefon="<?php echo htmlspecialchars((string)($membru['telefonnev'] ?? '')); ?>"
                    data-membru-email="<?php echo htmlspecialchars((string)($membru['email'] ?? '')); ?>"
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
                    data-membru-nume="<?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>"
                    data-valoare-cot="<?php echo number_format($valoare_cotizatie_an, 2, '.', ''); ?>"
                    data-cotizatie-an="<?php echo (int)$an_cotizatie; ?>"
                    data-cot-achitata="<?php echo $cotizatie_achitata_an_curent ? '1' : '0'; ?>"
                    aria-label="Incaseaza <?php echo htmlspecialchars(trim($membru['nume'] . ' ' . $membru['prenume'])); ?>">
                <i data-lucide="dollar-sign" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Incaseaza
            </button>
            <?php endif; ?>
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
                        <input type="hidden" name="marcat_informat" value="0">
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

        <!-- Cards grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: Date Personale -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="user" class="w-5 h-5" aria-hidden="true"></i>
                        Date Personale
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=date-personale" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="date-personale">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <!-- View mode -->
                <div class="card-view p-4 <?php echo $is_card_in_edit('date-personale') ? 'hidden' : ''; ?>" data-card="date-personale">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Nume</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('nume'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Prenume</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('prenume'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">CNP</dt>
                            <dd class="font-medium text-slate-900 dark:text-white font-mono"><?php echo $dv('cnp'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Sex</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('sex'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Data nasterii</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('datanastere', 'date'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Varsta</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $varsta !== null ? $varsta . ' ani' : '<span class="text-slate-400 dark:text-gray-500">—</span>'; ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Loc. nasterii</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('locnastere'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Jud. nasterii</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('judnastere'); ?></dd>
                        </div>
                    </dl>
                </div>
                <!-- Edit mode -->
                <div class="card-edit p-4 <?php echo $is_card_in_edit('date-personale') ? '' : 'hidden'; ?>" data-card="date-personale">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="date-personale">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ep_nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume <span class="text-red-600">*</span></label>
                                    <input type="text" id="ep_nume" name="nume" value="<?php echo $val('nume'); ?>" required class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ep_prenume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume <span class="text-red-600">*</span></label>
                                    <input type="text" id="ep_prenume" name="prenume" value="<?php echo $val('prenume'); ?>" required class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ep_cnp" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">CNP</label>
                                    <input type="text" id="ep_cnp" name="cnp" value="<?php echo $val('cnp'); ?>" maxlength="13" pattern="[0-9]{13}" inputmode="numeric" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ep_sex" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Sex</label>
                                    <select id="ep_sex" name="sex" class="<?php echo $input_class; ?>">
                                        <option value="">Selecteaza</option>
                                        <option value="Masculin" <?php echo $selected('sex', 'Masculin'); ?>>Masculin</option>
                                        <option value="Feminin" <?php echo $selected('sex', 'Feminin'); ?>>Feminin</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="ep_datanastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data nasterii</label>
                                    <input type="date" id="ep_datanastere" name="datanastere" value="<?php echo $val('datanastere'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ep_locnastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Loc. nasterii</label>
                                    <input type="text" id="ep_locnastere" name="locnastere" value="<?php echo $val('locnastere'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ep_judnastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Jud. nasterii</label>
                                    <input type="text" id="ep_judnastere" name="judnastere" value="<?php echo $val('judnastere'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 2: Date Contact -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="phone" class="w-5 h-5" aria-hidden="true"></i>
                        Date Contact
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=date-contact" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="date-contact">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('date-contact') ? 'hidden' : ''; ?>" data-card="date-contact">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Telefon</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('telefonnev'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Email</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('email'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Telefon apartinator</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('telefonapartinator'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Nume apartinator</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo trim(($membru['nume_apartinator'] ?? '') . ' ' . ($membru['prenume_apartinator'] ?? '')) ?: '<span class="text-slate-400 dark:text-gray-500">—</span>'; ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Primeste newsletter</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo (!isset($membru['newsletter_opt_in']) || $membru['newsletter_opt_in']) ? 'Da' : 'Nu'; ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('date-contact') ? '' : 'hidden'; ?>" data-card="date-contact">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="date-contact">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ec_telefonnev" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon</label>
                                    <input type="tel" id="ec_telefonnev" name="telefonnev" value="<?php echo $val('telefonnev'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ec_email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email</label>
                                    <input type="email" id="ec_email" name="email" value="<?php echo $val('email'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="ec_telefonapartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon apartinator</label>
                                    <input type="tel" id="ec_telefonapartinator" name="telefonapartinator" value="<?php echo $val('telefonapartinator'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ec_nume_apartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume apartinator</label>
                                    <input type="text" id="ec_nume_apartinator" name="nume_apartinator" value="<?php echo $val('nume_apartinator'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ec_prenume_apartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume apartinator</label>
                                    <input type="text" id="ec_prenume_apartinator" name="prenume_apartinator" value="<?php echo $val('prenume_apartinator'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-3">
                                <input type="hidden" name="newsletter_opt_in" value="0">
                                <input type="checkbox" id="ec_newsletter_opt_in" name="newsletter_opt_in" value="1"
                                       <?php echo (!isset($membru['newsletter_opt_in']) || $membru['newsletter_opt_in']) ? 'checked' : ''; ?>
                                       class="rounded border-slate-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500">
                                <label for="ec_newsletter_opt_in" class="text-sm font-medium text-slate-800 dark:text-gray-200">Primeste newsletter</label>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 3: Domiciliu -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="home" class="w-5 h-5" aria-hidden="true"></i>
                        Domiciliu
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=domiciliu" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="domiciliu">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('domiciliu') ? 'hidden' : ''; ?>" data-card="domiciliu">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Localitatea</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('domloc'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Judet</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('judet_domiciliu'); ?></dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Adresa</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">
                                <?php
                                $adresa_parts = [];
                                if (!empty($membru['domstr'])) $adresa_parts[] = 'Str. ' . htmlspecialchars($membru['domstr']);
                                if (!empty($membru['domnr'])) $adresa_parts[] = 'Nr. ' . htmlspecialchars($membru['domnr']);
                                if (!empty($membru['dombl'])) $adresa_parts[] = 'Bl. ' . htmlspecialchars($membru['dombl']);
                                if (!empty($membru['domsc'])) $adresa_parts[] = 'Sc. ' . htmlspecialchars($membru['domsc']);
                                if (!empty($membru['domet'])) $adresa_parts[] = 'Et. ' . htmlspecialchars($membru['domet']);
                                if (!empty($membru['domap'])) $adresa_parts[] = 'Ap. ' . htmlspecialchars($membru['domap']);
                                echo $adresa_parts ? implode(', ', $adresa_parts) : '<span class="text-slate-400 dark:text-gray-500">—</span>';
                                ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Cod postal</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('codpost'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Mediu</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('tipmediuur'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Primaria</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('primaria'); ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('domiciliu') ? '' : 'hidden'; ?>" data-card="domiciliu">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="domiciliu">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="ed_domloc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Localitatea</label>
                                    <input type="text" id="ed_domloc" name="domloc" value="<?php echo $val('domloc'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ed_judet_domiciliu" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Judet</label>
                                    <input type="text" id="ed_judet_domiciliu" name="judet_domiciliu" value="<?php echo $val('judet_domiciliu'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ed_codpost" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cod postal</label>
                                    <input type="text" id="ed_codpost" name="codpost" value="<?php echo $val('codpost'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-5 gap-2">
                                <div class="col-span-2">
                                    <label for="ed_domstr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Str.</label>
                                    <input type="text" id="ed_domstr" name="domstr" value="<?php echo $val('domstr'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ed_domnr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr.</label>
                                    <input type="text" id="ed_domnr" name="domnr" value="<?php echo $val('domnr'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ed_dombl" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Bl.</label>
                                    <input type="text" id="ed_dombl" name="dombl" value="<?php echo $val('dombl'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ed_domsc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Sc.</label>
                                    <input type="text" id="ed_domsc" name="domsc" value="<?php echo $val('domsc'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-5 gap-2">
                                <div>
                                    <label for="ed_domet" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Et.</label>
                                    <input type="text" id="ed_domet" name="domet" value="<?php echo $val('domet'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ed_domap" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ap.</label>
                                    <input type="text" id="ed_domap" name="domap" value="<?php echo $val('domap'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ed_tipmediuur" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip mediu</label>
                                    <select id="ed_tipmediuur" name="tipmediuur" class="<?php echo $input_class; ?>">
                                        <option value="">Selecteaza</option>
                                        <option value="Urban" <?php echo $selected('tipmediuur', 'Urban'); ?>>Urban</option>
                                        <option value="Rural" <?php echo $selected('tipmediuur', 'Rural'); ?>>Rural</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="ed_primaria" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Primaria de domiciliu</label>
                                    <input type="text" id="ed_primaria" name="primaria" value="<?php echo $val('primaria'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 4a: Date despre handicap -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="heart" class="w-5 h-5" aria-hidden="true"></i>
                        Date despre handicap
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=date-handicap" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="date-handicap">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('date-handicap') ? 'hidden' : ''; ?>" data-card="date-handicap">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Motiv handicap</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('hmotiv'); ?></dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Diagnostic</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('diagnostic'); ?></dd>
                        </div>
                    </dl>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('date-handicap') ? '' : 'hidden'; ?>" data-card="date-handicap">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="handicap">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div>
                                <label for="eh_hmotiv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Motiv handicap</label>
                                <input type="text" id="eh_hmotiv" name="hmotiv" value="<?php echo $val('hmotiv'); ?>" class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="eh_diagnostic" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Diagnostic</label>
                                <textarea id="eh_diagnostic" name="diagnostic" rows="3" class="<?php echo $input_class; ?>"><?php echo $val('diagnostic'); ?></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 4b: Certificat handicap -->
            <?php
            // Determine valabilitate display
            $ceexp_val = $membru['ceexp'] ?? '';
            $hdur_val = $membru['hdur'] ?? '';
            $is_permanent = ($hdur_val === 'Permanent') || ($ceexp_val === '9999-12-31') || (strtotime($ceexp_val) > strtotime('2099-01-01'));
            if ($is_permanent) {
                $valabilitate_display = 'Permanent';
            } elseif ($hdur_val === 'Revizuibil' && !empty($ceexp_val)) {
                $ts_exp = strtotime($ceexp_val);
                $valabilitate_display = 'Revizuibil — ' . ($ts_exp ? date(DATE_FORMAT, $ts_exp) : htmlspecialchars($ceexp_val));
            } elseif (!empty($ceexp_val)) {
                $ts_exp = strtotime($ceexp_val);
                $valabilitate_display = $ts_exp ? date(DATE_FORMAT, $ts_exp) : htmlspecialchars($ceexp_val);
            } else {
                $valabilitate_display = '<span class="text-slate-400 dark:text-gray-500">—</span>';
            }

            // Asistent personal display
            $insotitor_labels = [
                'INDEMNIZATIE INSOTITOR' => 'Indemnizatie insotitor',
                'ASISTENT PERSONAL' => 'Asistent personal',
                'FARA' => 'Fara',
                'NESPECIFICAT' => 'Nespecificat',
                '0' => 'Fara',
            ];
            $insotitor_raw = $membru['insotitor'] ?? '';
            $insotitor_display = $insotitor_labels[$insotitor_raw] ?? ($insotitor_raw ?: '<span class="text-slate-400 dark:text-gray-500">—</span>');
            ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="file-badge" class="w-5 h-5" aria-hidden="true"></i>
                        Certificat handicap
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=certificat-handicap" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="certificat-handicap">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('certificat-handicap') ? 'hidden' : ''; ?>" data-card="certificat-handicap">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Nr. certificat</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('cenr'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Data eliberare</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('cedata', 'date'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Grad handicap</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('hgrad'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Asistent personal</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $insotitor_display; ?></dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Valabilitate</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $valabilitate_display; ?></dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Status Membru</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">
                                <?php
                                $sv = $membru['status_dosar'] ?? 'Activ';
                                $sc = [
                                    'Activ' => 'background:#059669;color:#fff;',
                                    'Expirat' => 'background:#dc2626;color:#fff;',
                                    'Suspendat' => 'background:#d97706;color:#fff;',
                                    'Retras' => 'background:#be185d;color:#fff;',
                                    'Decedat' => 'background:#374151;color:#fff;',
                                    'Transferat' => 'background:#7c3aed;color:#fff;',
                                    'Arhiva' => 'background:#6b7280;color:#fff;',
                                ][$sv] ?? 'background:#94a3b8;color:#fff;';
                                ?>
                                <span class="px-2.5 py-1 text-sm font-semibold rounded-full" style="<?php echo $sc; ?>">
                                    <?php echo htmlspecialchars($sv); ?>
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('certificat-handicap') ? '' : 'hidden'; ?>" data-card="certificat-handicap">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="handicap">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ech_cenr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr. certificat</label>
                                    <input type="text" id="ech_cenr" name="cenr" value="<?php echo $val('cenr'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="ech_cedata" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data eliberare</label>
                                    <input type="date" id="ech_cedata" name="cedata" value="<?php echo $val('cedata'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ech_hgrad" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Grad handicap</label>
                                    <select id="ech_hgrad" name="hgrad" class="<?php echo $input_class; ?>">
                                        <option value="">Selecteaza</option>
                                        <option value="Grav cu insotitor" <?php echo $selected('hgrad', 'Grav cu insotitor'); ?>>Grav cu insotitor</option>
                                        <option value="Grav" <?php echo $selected('hgrad', 'Grav'); ?>>Grav</option>
                                        <option value="Accentuat" <?php echo $selected('hgrad', 'Accentuat'); ?>>Accentuat</option>
                                        <option value="Mediu" <?php echo $selected('hgrad', 'Mediu'); ?>>Mediu</option>
                                        <option value="Usor" <?php echo $selected('hgrad', 'Usor'); ?>>Usor</option>
                                        <option value="Alt handicap" <?php echo $selected('hgrad', 'Alt handicap'); ?>>Alt handicap</option>
                                        <option value="Asociat" <?php echo $selected('hgrad', 'Asociat'); ?>>Asociat</option>
                                        <option value="Fara handicap" <?php echo $selected('hgrad', 'Fara handicap'); ?>>Fara handicap</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="ech_insotitor" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Asistent personal</label>
                                    <select id="ech_insotitor" name="insotitor" class="<?php echo $input_class; ?>">
                                        <option value="">Selecteaza</option>
                                        <option value="INDEMNIZATIE INSOTITOR" <?php echo $selected('insotitor', 'INDEMNIZATIE INSOTITOR'); ?>>Indemnizatie insotitor</option>
                                        <option value="ASISTENT PERSONAL" <?php echo $selected('insotitor', 'ASISTENT PERSONAL'); ?>>Asistent personal</option>
                                        <option value="FARA" <?php echo $selected('insotitor', 'FARA'); ?>>Fara</option>
                                        <option value="NESPECIFICAT" <?php echo $selected('insotitor', 'NESPECIFICAT'); ?>>Nespecificat</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ech_hdur" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Valabilitate</label>
                                    <select id="ech_hdur" name="hdur" class="<?php echo $input_class; ?>">
                                        <option value="">Selecteaza</option>
                                        <option value="Permanent" <?php echo $selected('hdur', 'Permanent'); ?>>Permanent</option>
                                        <option value="Revizuibil" <?php echo $selected('hdur', 'Revizuibil'); ?>>Revizuibil</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="ech_ceexp" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data expirare (daca revizuibil)</label>
                                    <input type="date" id="ech_ceexp" name="ceexp" value="<?php echo $val('ceexp'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ech_status_dosar" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Status Membru</label>
                                    <select id="ech_status_dosar" name="status_dosar" class="<?php echo $input_class; ?>">
                                        <option value="Activ" <?php echo $selected('status_dosar', 'Activ'); ?>>Activ</option>
                                        <option value="Suspendat" <?php echo $selected('status_dosar', 'Suspendat'); ?>>Suspendat</option>
                                        <option value="Expirat" <?php echo $selected('status_dosar', 'Expirat'); ?>>Expirat</option>
                                        <option value="Retras" <?php echo $selected('status_dosar', 'Retras'); ?>>Retras</option>
                                        <option value="Decedat" <?php echo $selected('status_dosar', 'Decedat'); ?>>Decedat</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 5: Act de Identitate -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="id-card" class="w-5 h-5" aria-hidden="true"></i>
                        Act de Identitate
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=act-identitate" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="act-identitate">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('act-identitate') ? 'hidden' : ''; ?>" data-card="act-identitate">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Seria C.I.</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('ciseria'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Numar C.I.</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('cinumar'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Eliberat de</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('cielib'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Data eliberarii</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('cidataelib', 'date'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Data expirarii</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('cidataexp', 'date'); ?></dd>
                        </div>
                        <?php if (!empty($membru['doc_ci'])): ?>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Atasament</dt>
                            <dd>
                                <a href="uploads/ci/<?php echo htmlspecialchars($membru['doc_ci']); ?>" target="_blank"
                                   class="inline-flex items-center gap-1 text-sm text-amber-700 dark:text-amber-300 hover:underline">
                                    <i data-lucide="download" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                    Descarca
                                </a>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                    <?php if (!empty($atasamente_ci)): ?>
                    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-gray-300 mb-2">Istoric atasamente Act Identitate</h4>
                        <div class="space-y-2">
                            <?php foreach ($atasamente_ci as $at_ci): ?>
                            <div class="flex flex-wrap items-center gap-2 p-2 rounded bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600">
                                <div class="flex-1 min-w-0">
                                    <a href="uploads/membri_atasamente/<?php echo htmlspecialchars($at_ci['fisier']); ?>" target="_blank"
                                       class="text-sm font-medium text-amber-700 dark:text-amber-300 hover:underline truncate block">
                                        <i data-lucide="file" class="w-3.5 h-3.5 inline" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($at_ci['fisier']); ?>
                                    </a>
                                    <?php if (!empty($at_ci['nota'])): ?>
                                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-0.5"><?php echo htmlspecialchars($at_ci['nota']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-slate-500 dark:text-gray-500 mt-0.5">
                                        <?php echo $at_ci['created_at'] ? date('d.m.Y H:i', strtotime($at_ci['created_at'])) : '-'; ?>
                                        <?php if (!empty($at_ci['uploaded_by'])): ?>
                                        — <?php echo htmlspecialchars($at_ci['uploaded_by']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="uploads/membri_atasamente/<?php echo htmlspecialchars($at_ci['fisier']); ?>" target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-amber-500 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                        <i data-lucide="download" class="w-3 h-3" aria-hidden="true"></i>
                                        Descarca
                                    </a>
                                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>" class="inline"
                                          onsubmit="return confirm('Sigur doriti sa stergeti acest atasament?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="sterge_atasament" value="1">
                                        <input type="hidden" name="atasament_id" value="<?php echo (int)$at_ci['id']; ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-red-400 dark:border-red-500 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                            <i data-lucide="trash-2" class="w-3 h-3" aria-hidden="true"></i>
                                            Sterge
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('act-identitate') ? '' : 'hidden'; ?>" data-card="act-identitate">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>" enctype="multipart/form-data">
                        <input type="hidden" name="card" value="act-identitate">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="eai_ciseria" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Seria C.I.</label>
                                    <input type="text" id="eai_ciseria" name="ciseria" value="<?php echo $val('ciseria'); ?>" maxlength="2" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="eai_cinumar" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Numar C.I.</label>
                                    <input type="text" id="eai_cinumar" name="cinumar" value="<?php echo $val('cinumar'); ?>" maxlength="7" inputmode="numeric" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label for="eai_cielib" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Eliberat de</label>
                                    <input type="text" id="eai_cielib" name="cielib" value="<?php echo $val('cielib'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="eai_cidataelib" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data eliberarii</label>
                                    <input type="date" id="eai_cidataelib" name="cidataelib" value="<?php echo $val('cidataelib'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="eai_cidataexp" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data expirarii</label>
                                    <input type="date" id="eai_cidataexp" name="cidataexp" value="<?php echo $val('cidataexp'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div>
                                <?php if (!empty($membru['doc_ci'])): ?>
                                <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">Fisier actual: <?php echo htmlspecialchars($membru['doc_ci']); ?></p>
                                <?php endif; ?>
                                <label for="eai_doc_ci" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Incarca fisier nou</label>
                                <input type="file" id="eai_doc_ci" name="doc_ci" accept="image/*,.pdf" class="<?php echo $input_class; ?>">
                                <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Maxim 5 MB (JPG, PNG, GIF, PDF)</p>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 6: Dosar -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="folder" class="w-5 h-5" aria-hidden="true"></i>
                        Dosar
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=dosar" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="dosar">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('dosar') ? 'hidden' : ''; ?>" data-card="dosar">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3">
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Nr. dosar</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('dosarnr'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Data dosar</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo $dv('dosardata', 'date'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-slate-500 dark:text-gray-400">Acord GDPR</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">
                                <?php echo !empty($membru['gdpr']) ? '<span class="text-emerald-600 dark:text-emerald-400">Da</span>' : '<span class="text-red-600 dark:text-red-400">Nu</span>'; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('dosar') ? '' : 'hidden'; ?>" data-card="dosar">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="dosar">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edd_dosarnr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr. dosar</label>
                                    <input type="text" id="edd_dosarnr" name="dosarnr" value="<?php echo $val('dosarnr'); ?>" maxlength="6" class="<?php echo $input_class; ?>">
                                </div>
                                <div>
                                    <label for="edd_dosardata" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data dosar</label>
                                    <input type="date" id="edd_dosardata" name="dosardata" value="<?php echo $val('dosardata'); ?>" class="<?php echo $input_class; ?>">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-end pb-2">
                                    <label class="flex items-center">
                                        <input type="hidden" name="gdpr" value="0">
                                        <input type="checkbox" name="gdpr" value="1" <?php echo $checked('gdpr', '1'); ?>
                                               class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                        <span class="ml-2 text-sm text-slate-800 dark:text-gray-200">Acord GDPR</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 6b: Legitimatie membru -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="badge-check" class="w-5 h-5" aria-hidden="true"></i>
                        Legitimatie membru
                    </h3>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-300 dark:border-emerald-600 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                        data-legitimatie-open="1"
                        aria-haspopup="dialog"
                        aria-controls="modal-legitimatie-membru">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Modificare legitimatie
                    </button>
                </div>
                <div class="p-4">
                    <?php if (empty($legitimatii_membru)): ?>
                    <p class="text-sm text-slate-500 dark:text-gray-400">Nu exista operatiuni inregistrate pentru legitimatie.</p>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" aria-label="Istoric legitimatie membru">
                            <thead class="bg-slate-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Actiune</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Utilizator</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php foreach ($legitimatii_membru as $leg): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-3 py-2 text-sm text-slate-700 dark:text-gray-300">
                                        <?php
                                        $dl = !empty($leg['data_actiune']) ? strtotime((string)$leg['data_actiune']) : false;
                                        echo $dl ? htmlspecialchars(date(DATE_FORMAT, $dl)) : '<span class="text-slate-400 dark:text-gray-500">—</span>';
                                        ?>
                                    </td>
                                    <td class="px-3 py-2 text-sm font-medium text-slate-900 dark:text-white">
                                        <?php
                                        $lblAct = membri_legitimatii_tipuri_actiune()[($leg['tip_actiune'] ?? '')] ?? ($leg['tip_actiune'] ?? '-');
                                        echo htmlspecialchars($lblAct);
                                        ?>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-slate-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars((string)($leg['utilizator'] ?? 'Sistem')); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Card 7: Observatii -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="file-text" class="w-5 h-5" aria-hidden="true"></i>
                        Observatii
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=observatii" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="observatii">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('observatii') ? 'hidden' : ''; ?>" data-card="observatii">
                    <?php
                    $tip_scutire_obs = trim((string)($scutire_config['tip_scutire'] ?? ''));
                    if (!in_array($tip_scutire_obs, ['temporar', 'permanent'], true)) {
                        $tip_scutire_obs = 'nu';
                    }
                    $tip_labels_obs = ['nu' => 'Nu', 'temporar' => 'Da - temporar', 'permanent' => 'Da - permanent'];
                    ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <span class="block text-xs text-slate-500 dark:text-gray-400 mb-1">Scutire plata cotizatie</span>
                            <span class="text-slate-900 dark:text-white font-medium"><?php echo htmlspecialchars($tip_labels_obs[$tip_scutire_obs] ?? 'Nu'); ?></span>
                        </div>
                        <?php if ($tip_scutire_obs === 'temporar'): ?>
                        <div>
                            <span class="block text-xs text-slate-500 dark:text-gray-400 mb-1">Perioada scutire</span>
                            <span class="text-slate-900 dark:text-white">
                                <?php
                                $de_la = !empty($scutire_config['data_scutire_de_la']) ? date(DATE_FORMAT, strtotime((string)$scutire_config['data_scutire_de_la'])) : '—';
                                $pana = !empty($scutire_config['data_scutire_pana_la']) ? date(DATE_FORMAT, strtotime((string)$scutire_config['data_scutire_pana_la'])) : '—';
                                echo htmlspecialchars($de_la . ' - ' . $pana);
                                ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <span class="block text-xs text-slate-500 dark:text-gray-400 mb-1">Motiv scutire</span>
                            <span class="text-slate-900 dark:text-white"><?php echo !empty($scutire_config['motiv']) ? htmlspecialchars((string)$scutire_config['motiv']) : '<span class="text-slate-400 dark:text-gray-500">—</span>'; ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500 dark:text-gray-400 mb-1">Caz social</span>
                            <span class="text-slate-900 dark:text-white"><?php echo !empty($membru['caz_social']) ? 'Da' : 'Nu'; ?></span>
                        </div>
                    </div>
                    <div class="text-slate-900 dark:text-white">
                        <?php echo !empty($membru['notamembru']) ? nl2br(htmlspecialchars($membru['notamembru'])) : '<span class="text-slate-400 dark:text-gray-500">Nicio observatie</span>'; ?>
                    </div>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('observatii') ? '' : 'hidden'; ?>" data-card="observatii">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="observatii">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <?php
                        $tip_scutire_form = trim((string)($scutire_config['tip_scutire'] ?? ''));
                        if (!in_array($tip_scutire_form, ['nu', 'temporar', 'permanent'], true)) {
                            $tip_scutire_form = !empty($scutire_config) ? 'temporar' : 'nu';
                        }
                        $scutire_de_la_form = (string)($scutire_config['data_scutire_de_la'] ?? '');
                        $scutire_pana_form = (string)($scutire_config['data_scutire_pana_la'] ?? '');
                        $motiv_scutire_form = (string)($scutire_config['motiv'] ?? '');
                        ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="obs-tip-scutire" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Scutire plata cotizatie</label>
                                <select id="obs-tip-scutire" name="tip_scutire_cotizatie" class="<?php echo $input_class; ?>">
                                    <option value="nu" <?php echo $tip_scutire_form === 'nu' ? 'selected' : ''; ?>>Nu</option>
                                    <option value="temporar" <?php echo $tip_scutire_form === 'temporar' ? 'selected' : ''; ?>>Da - temporar</option>
                                    <option value="permanent" <?php echo $tip_scutire_form === 'permanent' ? 'selected' : ''; ?>>Da - permanent</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2 mt-1 md:mt-7">
                                <input type="hidden" name="caz_social" value="0">
                                <input type="checkbox" id="obs-caz-social" name="caz_social" value="1" <?php echo !empty($membru['caz_social']) ? 'checked' : ''; ?> class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                                <label for="obs-caz-social" class="text-sm font-medium text-slate-700 dark:text-gray-300">Caz social</label>
                            </div>
                            <div id="obs-wrap-scutire-de-la">
                                <label for="obs-scutire-de-la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Scutit de la data</label>
                                <input type="date" id="obs-scutire-de-la" name="data_scutire_de_la" value="<?php echo htmlspecialchars($scutire_de_la_form); ?>" class="<?php echo $input_class; ?>">
                            </div>
                            <div id="obs-wrap-scutire-pana-la">
                                <label for="obs-scutire-pana-la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Scutit pana la data</label>
                                <input type="date" id="obs-scutire-pana-la" name="data_scutire_pana_la" value="<?php echo htmlspecialchars($scutire_pana_form); ?>" class="<?php echo $input_class; ?>">
                            </div>
                            <div class="md:col-span-2">
                                <label for="obs-motiv-scutire" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Motiv scutire</label>
                                <input type="text" id="obs-motiv-scutire" name="motiv_scutire" value="<?php echo htmlspecialchars($motiv_scutire_form); ?>" class="<?php echo $input_class; ?>" maxlength="255">
                            </div>
                        </div>
                        <div>
                            <textarea name="notamembru" rows="5" class="<?php echo $input_class; ?>"><?php echo $val('notamembru'); ?></textarea>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card: Acces Biblioteca Online -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="book-open" class="w-5 h-5" aria-hidden="true"></i>
                        Acces Biblioteca Online
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=biblioteca-online" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="biblioteca-online">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Editeaza
                    </a>
                </div>
                <div class="card-view p-4 <?php echo $is_card_in_edit('biblioteca-online') ? 'hidden' : ''; ?>" data-card="biblioteca-online">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs text-slate-500 dark:text-gray-400 mb-1">Utilizator</span>
                            <span class="text-slate-900 dark:text-white"><?php echo !empty($membru['biblioteca_online_username']) ? htmlspecialchars($membru['biblioteca_online_username']) : '<span class="text-slate-400 dark:text-gray-500">—</span>'; ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-500 dark:text-gray-400 mb-1">Parola</span>
                            <span class="text-slate-900 dark:text-white"><?php echo !empty($membru['biblioteca_online_parola']) ? htmlspecialchars($membru['biblioteca_online_parola']) : '<span class="text-slate-400 dark:text-gray-500">—</span>'; ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-edit p-4 <?php echo $is_card_in_edit('biblioteca-online') ? '' : 'hidden'; ?>" data-card="biblioteca-online">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>">
                        <input type="hidden" name="card" value="biblioteca-online">
                        <input type="hidden" name="actualizeaza_membru" value="1">
                        <?php echo csrf_field(); ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Utilizator</label>
                                <input type="text" name="biblioteca_online_username" value="<?php echo $val('biblioteca_online_username'); ?>" class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Parola</label>
                                <input type="text" name="biblioteca_online_parola" value="<?php echo $val('biblioteca_online_parola'); ?>" class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i> Salveaza
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Card 8: Atasamente Documente -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="paperclip" class="w-5 h-5" aria-hidden="true"></i>
                        Atasamente Documente
                    </h3>
                    <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>&edit_card=atasamente-docs" class="btn-edit-card <?php echo $btn_edit_class; ?>" data-card="atasamente-docs">
                        <i data-lucide="edit-3" class="w-4 h-4" aria-hidden="true"></i>
                        Incarca
                    </a>
                </div>
                <!-- View mode: list of uploaded files -->
                <div class="card-view p-4 <?php echo $is_card_in_edit('atasamente-docs') ? 'hidden' : ''; ?>" data-card="atasamente-docs">
                    <?php
                    $toate_atasamentele = array_merge($atasamente_ch ?? [], $atasamente_ci ?? [], $atasamente_alt ?? []);
                    // Also include legacy doc_ch
                    if (!empty($membru['doc_ch']) && empty($atasamente_ch)): ?>
                    <div class="mb-3">
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-gray-300 mb-2">Certificat Handicap (legacy)</h4>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($membru['doc_ch']); ?></span>
                            <a href="uploads/ch/<?php echo htmlspecialchars($membru['doc_ch']); ?>" target="_blank"
                               class="inline-flex items-center gap-1 px-2 py-1 text-sm rounded border border-amber-500 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                <i data-lucide="download" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                Descarca
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($toate_atasamentele) && empty($membru['doc_ch'])): ?>
                    <p class="text-slate-400 dark:text-gray-500">Niciun fisier incarcat</p>
                    <?php endif; ?>

                    <?php if (!empty($toate_atasamentele)): ?>
                    <?php
                    $tip_labels = ['certificat_handicap' => 'Certificat Handicap', 'act_identitate' => 'Act Identitate', 'alt_document' => 'Alt Document'];
                    $grouped = [];
                    foreach ($toate_atasamentele as $at) {
                        $grouped[$at['tip']][] = $at;
                    }
                    foreach ($grouped as $tip_atas => $fisiere):
                    ?>
                    <div class="mb-4">
                        <h4 class="text-sm font-semibold text-slate-700 dark:text-gray-300 mb-2"><?php echo htmlspecialchars($tip_labels[$tip_atas] ?? $tip_atas); ?></h4>
                        <div class="space-y-2">
                            <?php foreach ($fisiere as $at): ?>
                            <div class="flex flex-wrap items-center gap-2 p-2 rounded bg-slate-50 dark:bg-gray-700/50 border border-slate-200 dark:border-gray-600">
                                <div class="flex-1 min-w-0">
                                    <a href="uploads/membri_atasamente/<?php echo htmlspecialchars($at['fisier']); ?>" target="_blank"
                                       class="text-sm font-medium text-amber-700 dark:text-amber-300 hover:underline truncate block">
                                        <i data-lucide="file" class="w-3.5 h-3.5 inline" aria-hidden="true"></i>
                                        <?php echo htmlspecialchars($at['fisier']); ?>
                                    </a>
                                    <?php if (!empty($at['nota'])): ?>
                                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-0.5"><?php echo htmlspecialchars($at['nota']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-slate-500 dark:text-gray-500 mt-0.5">
                                        <?php echo $at['created_at'] ? date('d.m.Y H:i', strtotime($at['created_at'])) : '-'; ?>
                                        <?php if (!empty($at['uploaded_by'])): ?>
                                        — <?php echo htmlspecialchars($at['uploaded_by']); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a href="uploads/membri_atasamente/<?php echo htmlspecialchars($at['fisier']); ?>" target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-amber-500 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                        <i data-lucide="download" class="w-3 h-3" aria-hidden="true"></i>
                                        Descarca
                                    </a>
                                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>" class="inline"
                                          onsubmit="return confirm('Sigur doriti sa stergeti acest atasament?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="sterge_atasament" value="1">
                                        <input type="hidden" name="atasament_id" value="<?php echo (int)$at['id']; ?>">
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-red-400 dark:border-red-500 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                            <i data-lucide="trash-2" class="w-3 h-3" aria-hidden="true"></i>
                                            Sterge
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <!-- Edit mode: upload form -->
                <div class="card-edit p-4 <?php echo $is_card_in_edit('atasamente-docs') ? '' : 'hidden'; ?>" data-card="atasamente-docs">
                    <form method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="upload_atasament" value="1">
                        <div class="space-y-4">
                            <div>
                                <label for="ea_tip" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip document</label>
                                <select id="ea_tip" name="tip_atasament" class="<?php echo $input_class; ?>">
                                    <option value="certificat_handicap">Certificat handicap</option>
                                    <option value="act_identitate">Act identitate</option>
                                    <option value="alt_document">Alt document</option>
                                </select>
                            </div>
                            <div>
                                <label for="ea_fisier" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Fisier</label>
                                <input type="file" id="ea_fisier" name="fisier_atasament" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required class="<?php echo $input_class; ?>">
                                <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Maxim 5 MB (PDF, JPG, PNG, DOC, DOCX)</p>
                            </div>
                            <div>
                                <label for="ea_nota" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nota / Detalii</label>
                                <textarea id="ea_nota" name="nota_atasament" rows="2" placeholder="Ex: Certificat nou valabil 2026-2028" class="<?php echo $input_class; ?>"></textarea>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-gray-700">
                            <button type="submit" class="<?php echo $btn_save_class; ?>">
                                <i data-lucide="upload" class="w-4 h-4" aria-hidden="true"></i> Incarca
                            </button>
                            <a href="/membru-profil?id=<?php echo (int)$membru['id']; ?>" class="btn-cancel-card <?php echo $btn_cancel_class; ?>">Anulare</a>
                        </div>
                    </form>
                </div>
            </section>

        </div>
        <!-- End cards grid -->

        <!-- Documente generate -->
        <section class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" id="sectiune-documente-generate">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2" id="titlu-documente-generate">
                <i data-lucide="file-text" class="w-5 h-5" aria-hidden="true"></i>
                Documente generate
            </h2>
            <?php if (empty($documente_generate)): ?>
                <p class="text-sm text-slate-600 dark:text-gray-400">Nu exista documente generate pentru acest membru.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-labelledby="titlu-documente-generate">
                    <thead class="bg-slate-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Document</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider whitespace-nowrap">Data generare</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider whitespace-nowrap">Nr. inregistrare</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider whitespace-nowrap">Utilizator</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($documente_generate as $doc):
                            $doc_ts = !empty($doc['data']) ? strtotime($doc['data']) : false;
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                                <span class="truncate block max-w-xs" title="<?php echo htmlspecialchars($doc['nume']); ?>">
                                    <?php echo htmlspecialchars($doc['nume']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo $doc_ts ? date('d.m.Y H:i', $doc_ts) : '—'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo htmlspecialchars((string)($doc['nr_inregistrare'] ?? '—')); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo htmlspecialchars($doc['utilizator'] ?? ''); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if (!empty($doc['url'])): ?>
                                <?php
                                    $doc_url = (string)$doc['url'];
                                    $doc_view_url = $doc_url . (strpos($doc_url, '?') !== false ? '&' : '?') . 'inline=1';
                                    $doc_print_url = '/util/print-document-generat.php?url=' . rawurlencode($doc_view_url);
                                    $doc_file_hint = trim((string)($doc['fisier_pdf'] ?? ''));
                                    if ($doc_file_hint === '' && $doc_url !== '') {
                                        $parts = @parse_url($doc_url);
                                        if (!empty($parts['query'])) {
                                            $q = [];
                                            parse_str((string)$parts['query'], $q);
                                            $tok = (string)($q['token'] ?? '');
                                            if ($tok !== '') {
                                                $decoded = base64_decode($tok, true);
                                                if (is_string($decoded) && $decoded !== '') {
                                                    $doc_file_hint = basename($decoded);
                                                }
                                            }
                                        }
                                    }
                                ?>
                                <div class="flex flex-wrap gap-2">
                                    <a href="<?php echo htmlspecialchars($doc_view_url); ?>" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-blue-500 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                       aria-label="Vezi documentul <?php echo htmlspecialchars($doc['nume']); ?>">
                                        <i data-lucide="eye" class="w-3 h-3" aria-hidden="true"></i>
                                        Vezi documentul
                                    </a>
                                    <a href="<?php echo htmlspecialchars($doc_print_url); ?>" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-orange-500 text-orange-700 dark:text-orange-300 hover:bg-orange-50 dark:hover:bg-orange-900/30"
                                       aria-label="Print documentul <?php echo htmlspecialchars($doc['nume']); ?>">
                                        <i data-lucide="printer" class="w-3 h-3" aria-hidden="true"></i>
                                        Print
                                    </a>
                                    <button type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded border border-red-500 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 btn-sterge-document-generat"
                                            data-id="<?php echo (int)($doc['id'] ?? 0); ?>"
                                            data-fisier="<?php echo htmlspecialchars($doc_file_hint); ?>"
                                            data-nume="<?php echo htmlspecialchars((string)$doc['nume']); ?>"
                                            aria-label="Sterge documentul <?php echo htmlspecialchars($doc['nume']); ?>">
                                        <i data-lucide="trash-2" class="w-3 h-3" aria-hidden="true"></i>
                                        Sterge
                                    </button>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-slate-400 dark:text-gray-500">Fisier indisponibil</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <!-- Istoric incasari -->
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
                                    <button type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1.5 rounded border border-red-500 dark:border-red-400 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500 btn-sterge-incasare-membru"
                                            data-id="<?php echo (int)$inc['id']; ?>"
                                            data-info="<?php echo htmlspecialchars(($tipuri_afisare[$inc['tip']] ?? $inc['tip']) . ' / ' . number_format((float)$inc['suma'], 2, ',', ' ') . ' RON / ' . ($inc['data_incasare'] ? date(DATE_FORMAT, strtotime($inc['data_incasare'])) : '-')); ?>"
                                            aria-label="Sterge incasarea <?php echo htmlspecialchars((string)$inc['id']); ?>">
                                        <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                                        Sterge incasarea
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Jurnal Activitate -->
        <section class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" id="sectiune-jurnal-activitate">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2" id="titlu-jurnal-activitate">
                <i data-lucide="scroll-text" class="w-5 h-5" aria-hidden="true"></i>
                Istoric activitate
            </h2>
            <?php if (empty($jurnal)): ?>
            <p class="text-slate-600 dark:text-gray-400 py-4">Nu exista inregistrari in jurnalul de activitate pentru acest membru.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-labelledby="titlu-jurnal-activitate">
                    <thead class="bg-slate-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider whitespace-nowrap">Data</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider">Actiune</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase tracking-wider whitespace-nowrap">Utilizator</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($jurnal as $log):
                            $sursa = $log['sursa'] ?? 'log';
                            $dt = $log['data_ora'] ?? $log['created_at'] ?? '';
                            $ts = $dt ? strtotime($dt) : false;

                            // Iconița și badge-ul după sursă
                            if ($sursa === 'interactiune') {
                                $icon = strpos($log['actiune'] ?? '', 'APEL') === 0 ? 'phone' : 'building';
                                $badge_class = 'bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200';
                                $badge_text = 'Interactiune';
                            } elseif ($sursa === 'document') {
                                $icon = 'file-text';
                                $badge_class = 'bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200';
                                $badge_text = 'Document';
                            } else {
                                $icon = 'activity';
                                $badge_class = 'bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-100';
                                $badge_text = 'Activitate';
                            }
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo $ts ? date('d.m.Y H:i', $ts) : '—'; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium <?php echo $badge_class; ?> mb-1">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-3 h-3" aria-hidden="true"></i>
                                    <?php echo $badge_text; ?>
                                </span>
                                <span class="text-slate-900 dark:text-white block">
                                    <?php echo nl2br(htmlspecialchars($log['actiune'] ?? '')); ?>
                                </span>
                                <?php if (!empty($log['detalii_extra'])): ?>
                                <span class="text-xs text-slate-500 dark:text-gray-400 block mt-1">
                                    <?php echo nl2br(htmlspecialchars($log['detalii_extra'])); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 whitespace-nowrap">
                                <?php echo htmlspecialchars($log['utilizator'] ?? ''); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

    </div>
    <?php endif; ?>
</main>

<?php require_once APP_ROOT . '/includes/documente_modal.php'; ?>
<?php require_once APP_ROOT . '/includes/incasari_modal.php'; ?>

<dialog id="modal-legitimatie-membru" class="w-full max-w-xl rounded-xl border border-slate-200 dark:border-gray-700 p-0 bg-white dark:bg-gray-800 text-slate-900 dark:text-white">
    <form method="dialog" class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-gray-700">
        <h2 class="text-base font-semibold">Modificare legitimatie</h2>
        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-slate-100 dark:hover:bg-gray-700" aria-label="Inchide fereastra">
            <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </form>
    <form method="post" action="/membru-profil?id=<?php echo (int)$membru_id; ?>" class="p-4 space-y-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="salveaza_legitimatie_membru" value="1">
        <div>
            <label for="legitimatie-data-actiune" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data</label>
            <input
                type="date"
                id="legitimatie-data-actiune"
                name="legitimatie_data"
                value="<?php echo htmlspecialchars(date('Y-m-d')); ?>"
                required
                class="<?php echo $input_class; ?>">
        </div>
        <div>
            <label for="legitimatie-tip-actiune" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Actiune</label>
            <select id="legitimatie-tip-actiune" name="legitimatie_actiune" required class="<?php echo $input_class; ?>">
                <?php foreach (membri_legitimatii_tipuri_actiune() as $aVal => $aLabel): ?>
                <option value="<?php echo htmlspecialchars($aVal); ?>"><?php echo htmlspecialchars($aLabel); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-gray-700">
            <button type="submit" class="<?php echo $btn_save_class; ?>">
                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i>
                Salveaza
            </button>
            <button type="button" id="legitimatie-renunta" class="<?php echo $btn_cancel_class; ?>">Renunta</button>
        </div>
    </form>
</dialog>

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

    // Card edit/cancel toggle (scoped per section, works for every card)
    function closeAllCardsInSection(section) {
        if (!section) return;
        section.querySelectorAll('.card-view').forEach(function(v) { v.classList.remove('hidden'); });
        section.querySelectorAll('.card-edit').forEach(function(e) { e.classList.add('hidden'); });
        section.querySelectorAll('.btn-edit-card').forEach(function(b) { b.classList.remove('hidden'); });
    }

    function openEditCard(editBtn) {
        var cardName = (editBtn && editBtn.dataset && editBtn.dataset.card) ? editBtn.dataset.card : '';
        if (!cardName) return;

        var section = editBtn.closest('section');
        var viewCard = section ? section.querySelector('.card-view[data-card="' + cardName + '"]') : null;
        var editCard = section ? section.querySelector('.card-edit[data-card="' + cardName + '"]') : null;

        if (!viewCard || !editCard) {
            return;
        }

        closeAllCardsInSection(section);
        viewCard.classList.add('hidden');
        editCard.classList.remove('hidden');
        editBtn.classList.add('hidden');

        var firstInput = editCard.querySelector('input:not([type="hidden"]), select, textarea');
        if (firstInput) firstInput.focus();

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeEditCard(cancelBtn) {
        var section = cancelBtn.closest('section');
        if (!section) return;
        closeAllCardsInSection(section);
    }

    document.addEventListener('click', function(e) {
        var editBtn = e.target.closest('.btn-edit-card');
        if (editBtn) {
            e.preventDefault();
            openEditCard(editBtn);
            return;
        }

        var cancelBtn = e.target.closest('.btn-cancel-card');
        if (cancelBtn) {
            e.preventDefault();
            closeEditCard(cancelBtn);
        }
    });

    // File size validation
    document.querySelectorAll('input[type="file"]').forEach(function(field) {
        field.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                if (this.files[0].size > 5 * 1024 * 1024) {
                    alert('Fisierul depaseste 5 MB.');
                    this.value = '';
                }
            }
        });
    });

    var tipScutireObs = document.getElementById('obs-tip-scutire');
    var wrapScutireDeLaObs = document.getElementById('obs-wrap-scutire-de-la');
    var wrapScutirePanaObs = document.getElementById('obs-wrap-scutire-pana-la');
    var inputScutireDeLaObs = document.getElementById('obs-scutire-de-la');
    var inputScutirePanaObs = document.getElementById('obs-scutire-pana-la');
    if (tipScutireObs) {
        var toggleScutireTemporara = function() {
            var temporar = tipScutireObs.value === 'temporar';
            if (wrapScutireDeLaObs) {
                wrapScutireDeLaObs.style.display = temporar ? '' : 'none';
            }
            if (wrapScutirePanaObs) {
                wrapScutirePanaObs.style.display = temporar ? '' : 'none';
            }
            if (inputScutireDeLaObs) {
                if (temporar) inputScutireDeLaObs.setAttribute('required', 'required');
                else inputScutireDeLaObs.removeAttribute('required');
            }
            if (inputScutirePanaObs) {
                if (temporar) inputScutirePanaObs.setAttribute('required', 'required');
                else inputScutirePanaObs.removeAttribute('required');
            }
        };
        tipScutireObs.addEventListener('change', toggleScutireTemporara);
        toggleScutireTemporara();
    }

    var legitDialog = document.getElementById('modal-legitimatie-membru');
    if (legitDialog) {
        document.querySelectorAll('[data-legitimatie-open="1"]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (typeof legitDialog.showModal === 'function') {
                    legitDialog.showModal();
                    var first = legitDialog.querySelector('input, select, button');
                    if (first) first.focus();
                }
            });
        });
        var legitCancel = document.getElementById('legitimatie-renunta');
        if (legitCancel) {
            legitCancel.addEventListener('click', function() {
                legitDialog.close();
            });
        }
    }

    var csrfInput = document.querySelector('input[name="_csrf_token"]');
    var csrfToken = csrfInput && csrfInput.value ? csrfInput.value : '';

    document.addEventListener('click', function (e) {
        var btnDoc = e.target.closest('.btn-sterge-document-generat');
        if (btnDoc) {
            e.preventDefault();
            var docId = btnDoc.getAttribute('data-id');
            var docNume = btnDoc.getAttribute('data-nume') || 'document';
            if (!docId || docId === '0') {
                alert('Documentul nu poate fi sters (ID invalid).');
                return;
            }
            if (!confirm('Sigur doresti sa stergi documentul?\n\n' + docNume + '\n\nAceasta actiune nu poate fi anulata.')) {
                return;
            }

            var fdDoc = new FormData();
            fdDoc.append('document_id', docId);
            fdDoc.append('fisier_pdf', btnDoc.getAttribute('data-fisier') || '');
            fdDoc.append('membru_id', '<?php echo (int)$membru_id; ?>');
            if (csrfToken) fdDoc.append('_csrf_token', csrfToken);

            fetch('/api/membri-documente-sterge', { method: 'POST', body: fdDoc, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        window.location.reload();
                    } else {
                        alert((data && data.eroare) ? data.eroare : 'Eroare la stergere document.');
                    }
                })
                .catch(function () { alert('Eroare de retea la stergerea documentului.'); });
            return;
        }

        var btnInc = e.target.closest('.btn-sterge-incasare-membru');
        if (btnInc) {
            e.preventDefault();
            var incId = btnInc.getAttribute('data-id');
            var info = btnInc.getAttribute('data-info') || '';
            if (!incId) {
                alert('ID incasare invalid.');
                return;
            }
            if (!confirm('Sigur doresti sa stergi incasarea?\n\n' + info + '\n\nNumerotarea chitantelor va fi recalculata.')) {
                return;
            }

            var fdInc = new FormData();
            fdInc.append('id', incId);
            fdInc.append('_csrf_token', csrfToken);
            fdInc.append('context_membru_id', '<?php echo (int)$membru_id; ?>');

            fetch('/api/incasari-sterge', { method: 'POST', body: fdInc, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        window.location.reload();
                    } else {
                        alert((data && data.eroare) ? data.eroare : 'Eroare la stergerea incasarii.');
                    }
                })
                .catch(function () { alert('Eroare de retea la stergerea incasarii.'); });
        }
    });
});
</script>
<script>
function logActiuneMembru(membruId, actiune) {
    fetch('/api/log-actiune-membru', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ membru_id: membruId, actiune: actiune })
    }).catch(function() {});
}
</script>
</body>
</html>
