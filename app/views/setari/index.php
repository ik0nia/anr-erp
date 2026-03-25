<?php
/**
 * View: Setari — Full settings page with all tabs
 *
 * Variables expected from controller:
 *   $eroare, $succes, $tab_setari, $lista_utilizatori,
 *   $logo_url_actual, $nume_platforma_actual, $email_asociatie,
 *   $cale_libreoffice, $registratura_nr_pornire, $newsletter_email,
 *   $antet_asociatie_docx, $subiecte_dashboard_v2, $settings_email,
 *   $lista_cotizatii_anuale, $lista_scutiri_cotizatii,
 *   $edit_cotizatie_anuala, $edit_scutire_cotizatie,
 *   $graduri_handicap, $asistent_personal_opts,
 *   $incasari_serie_donatii, $incasari_serie_incasari,
 *   $incasari_setari_design, $lista_donatii_incasate,
 *   $import_result, $excel_data, $mapare_coloane
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex justify-between items-center flex-wrap gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Setări</h1>
            <p class="text-sm text-slate-500 dark:text-gray-400 mt-0.5" aria-label="Versiune CRM">Versiune CRM: <?php echo htmlspecialchars(function_exists('get_platform_version') ? get_platform_version() : (defined('PLATFORM_VERSION') ? PLATFORM_VERSION : '1')); ?></p>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
            <p><?php echo htmlspecialchars($eroare); ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <p><?php echo htmlspecialchars($succes); ?></p>
        </div>
        <?php endif; ?>

        <nav class="mb-6 flex gap-2 border-b border-slate-200 dark:border-gray-700" role="tablist" aria-label="Tab-uri setări">
            <a href="/setari" role="tab" aria-selected="<?php echo $tab_setari === 'general' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'general' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                General
            </a>
            <a href="/setari?tab=dashboard" role="tab" aria-selected="<?php echo $tab_setari === 'dashboard' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'dashboard' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Dashboard
            </a>
            <a href="/setari?tab=email" role="tab" aria-selected="<?php echo $tab_setari === 'email' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'email' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Email
            </a>
            <a href="/setari?tab=cotizatii" role="tab" aria-selected="<?php echo $tab_setari === 'cotizatii' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'cotizatii' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Cotizații
            </a>
            <a href="/setari?tab=incasari" role="tab" aria-selected="<?php echo $tab_setari === 'incasari' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'incasari' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Încasări
            </a>
            <a href="/setari?tab=tickete" role="tab" aria-selected="<?php echo $tab_setari === 'tickete' ? 'true' : 'false'; ?>"
               class="px-4 py-2 rounded-t-lg font-medium <?php echo $tab_setari === 'tickete' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700'; ?>">
                Tickete
            </a>
        </nav>

        <?php if ($tab_setari === 'incasari'): ?>
        <!-- Tab Încasări: administrare modul (serii chitanțe, design, FGO.ro API) -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="incasari-serii-heading">
            <h2 id="incasari-serii-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Serii chitanțe</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Definiți seria și intervalul de numerotare pentru chitanțe. Seria Donații (ex. CEDON) se folosește pentru donații, iar seria Cotizații (ex. CECOT) pentru cotizații, taxe participare și alte încasări.</p>
            <form method="post" action="/setari?tab=incasari">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_serii_incasari" value="1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg">
                        <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3">Chitanțe Donații (tip: Donație)</h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mb-2">Ex. serie: CEDON</p>
                        <div class="space-y-2">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Serie</label>
                            <input type="text" name="serie_donatii" value="<?php echo htmlspecialchars($incasari_serie_donatii['serie'] ?? 'CEDON'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" maxlength="20">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Nr. start / Nr. curent / Nr. final</label>
                            <div class="flex gap-2 flex-wrap">
                                <input type="number" name="nr_start_donatii" value="<?php echo (int)($incasari_serie_donatii['nr_start'] ?? 1); ?>" min="1" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="number" name="nr_curent_donatii" value="<?php echo (int)($incasari_serie_donatii['nr_curent'] ?? 1); ?>" min="0" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="number" name="nr_final_donatii" value="<?php echo (int)($incasari_serie_donatii['nr_final'] ?? 0); ?>" min="1" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg">
                        <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3">Chitanțe Cotizații (tip: Cotizație, taxe, alte)</h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mb-2">Ex. serie: CECOT</p>
                        <div class="space-y-2">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Serie</label>
                            <input type="text" name="serie_incasari" value="<?php echo htmlspecialchars($incasari_serie_incasari['serie'] ?? 'CECOT'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" maxlength="20">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Nr. start / Nr. curent / Nr. final</label>
                            <div class="flex gap-2 flex-wrap">
                                <input type="number" name="nr_start_incasari" value="<?php echo (int)($incasari_serie_incasari['nr_start'] ?? 1); ?>" min="1" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="number" name="nr_curent_incasari" value="<?php echo (int)($incasari_serie_incasari['nr_curent'] ?? 1); ?>" min="0" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                <input type="number" name="nr_final_incasari" value="<?php echo (int)($incasari_serie_incasari['nr_final'] ?? 0); ?>" min="1" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează serii</button>
            </form>
        </section>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="incasari-design-heading">
            <h2 id="incasari-design-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Design chitanțe</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Configurați template-ul chitanței, dimensiunea tipăririi, logo-ul și datele asociației.</p>
            <form method="post" action="/setari?tab=incasari">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_design_chitante" value="1">
                <div class="space-y-3 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Template chitanță</label>
                            <select name="template_chitanta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                <option value="standard" <?php echo ($incasari_setari_design['template_chitanta'] ?? 'standard') === 'standard' ? 'selected' : ''; ?>>Standard</option>
                                <option value="minimal" <?php echo ($incasari_setari_design['template_chitanta'] ?? '') === 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Dimensiune chitanță</label>
                            <select name="dimensiune_chitanta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                <option value="a5" <?php echo ($incasari_setari_design['dimensiune_chitanta'] ?? 'a5') === 'a5' ? 'selected' : ''; ?>>A5 (recomandat)</option>
                                <option value="a4" <?php echo ($incasari_setari_design['dimensiune_chitanta'] ?? '') === 'a4' ? 'selected' : ''; ?>>A4</option>
                            </select>
                        </div>
                    </div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">URL logo (chitanță)</label>
                    <input type="url" name="logo_chitanta" value="<?php echo htmlspecialchars($incasari_setari_design['logo_chitanta'] ?? ''); ?>" placeholder="https://..." class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Date asociație (stânga sus pe chitanță)</label>
                    <textarea name="date_asociatie" rows="6" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Denumire, CUI, sediu, cont bancar..."><?php echo htmlspecialchars($incasari_setari_design['date_asociatie'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează design</button>
            </form>
        </section>
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="incasari-fgo-heading">
            <h2 id="incasari-fgo-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Integrare FGO.ro (API)</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Conectare la platforma FGO.ro pentru transmiterea documentelor de încasare. Consultați <a href="https://www.fgo.ro" target="_blank" rel="noopener noreferrer" class="text-amber-600 dark:text-amber-400 hover:underline">FGO.ro</a> și documentația API (PDF) pentru parametrii exacti.</p>
            <form method="post" action="/setari?tab=incasari">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_fgo_api" value="1">
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Cheie API</label>
                        <input type="text" name="fgo_api_key" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_api_key'] ?? ''); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Cheie API FGO">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Nume comerciant</label>
                        <input type="text" name="fgo_merchant_name" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_merchant_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">CUI / ID fiscal comerciant</label>
                        <input type="text" name="fgo_merchant_tax_id" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_merchant_tax_id'] ?? ''); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">URL API</label>
                        <input type="url" name="fgo_api_url" value="<?php echo htmlspecialchars($incasari_setari_design['fgo_api_url'] ?? 'https://api.fgo.ro'); ?>" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-gray-300">Mediu</label>
                        <select name="fgo_mediu" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                            <option value="test" <?php echo ($incasari_setari_design['fgo_mediu'] ?? '') === 'test' ? 'selected' : ''; ?>>Test</option>
                            <option value="productie" <?php echo ($incasari_setari_design['fgo_mediu'] ?? '') === 'productie' ? 'selected' : ''; ?>>Producție</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg">Salvează parametri FGO</button>
            </form>
        </section>
        <!-- Donațiile încasate -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="incasari-donatii-heading">
            <h2 id="incasari-donatii-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Donațiile încasate</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Lista donațiilor încasate (de la membri și donatori externi).</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-labelledby="incasari-donatii-heading">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Donator / Membru</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Sumă (RON)</th>
                            <th scope="col" class="px-4 py-2 text-center text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Seria / Nr.</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($lista_donatii_incasate)): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu există donații încasate.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_donatii_incasate as $don):
                            $nume_don = trim(($don['nume'] ?? '') . ' ' . ($don['prenume'] ?? ''));
                            $data_fmt = !empty($don['data_incasare']) ? date('d.m.Y', strtotime($don['data_incasare'])) : '—';
                            $seria = $don['seria_chitanta'] ?? '—';
                            $nr = $don['nr_chitanta'] ?? '—';
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($data_fmt); ?></td>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($nume_don ?: '—'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 text-right"><?php echo number_format((float)$don['suma'], 2, ',', '.'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300 text-center"><?php echo htmlspecialchars($seria); ?> / <?php echo htmlspecialchars($nr); ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="util/incasari-chitanta-print.php?id=<?php echo (int)$don['id']; ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Tipărește chitanța <?php echo htmlspecialchars($seria . ' ' . $nr); ?>">Tipărește</a>
                                <a href="util/incasari-chitanta-pdf.php?id=<?php echo (int)$don['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-medium rounded-lg focus:ring-2 focus:ring-slate-500" aria-label="Descarcă PDF chitanța <?php echo htmlspecialchars($seria . ' ' . $nr); ?>">PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php elseif ($tab_setari === 'cotizatii'): ?>
        <!-- Tab Cotizații: valori anuale și membri scutiți -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mb-6" aria-labelledby="cotizatii-anuale-heading">
            <h2 id="cotizatii-anuale-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Valori cotizații anuale</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Setați valoarea cotizației pe an, grad de handicap și asistent personal. Fiecare combinație an + grad + asistent poate exista o singură dată.</p>
            <form method="post" action="/setari?tab=cotizatii" class="mb-4 flex flex-wrap gap-4 items-end">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_cotizatie_anuala" value="1">
                <input type="hidden" name="id_cotizatie_anuala" value="<?php echo $edit_cotizatie_anuala ? (int)$edit_cotizatie_anuala['id'] : 0; ?>">
                <div>
                    <label for="cot-anul" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Anul</label>
                    <input type="number" id="cot-anul" name="anul" min="1900" max="2100" value="<?php echo $edit_cotizatie_anuala ? (int)$edit_cotizatie_anuala['anul'] : date('Y'); ?>" class="w-24 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Anul">
                </div>
                <div>
                    <label for="cot-grad" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Grad handicap</label>
                    <select id="cot-grad" name="grad_handicap" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Grad handicap">
                        <?php foreach ($graduri_handicap as $val => $lbl): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($edit_cotizatie_anuala && ($edit_cotizatie_anuala['grad_handicap'] ?? '') === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="cot-asistent" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Asistent personal</label>
                    <select id="cot-asistent" name="asistent_personal" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Asistent personal">
                        <?php foreach ($asistent_personal_opts as $val => $lbl): ?>
                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($edit_cotizatie_anuala && ($edit_cotizatie_anuala['asistent_personal'] ?? '') === $val) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="cot-valoare" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Valoare cotizație (lei)</label>
                    <input type="text" id="cot-valoare" name="valoare_cotizatie" value="<?php echo $edit_cotizatie_anuala ? htmlspecialchars($edit_cotizatie_anuala['valoare_cotizatie']) : ''; ?>" placeholder="0.00" class="w-32 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Valoare cotizație">
                </div>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg"><?php echo $edit_cotizatie_anuala ? 'Actualizează' : 'Adaugă'; ?></button>
                <?php if ($edit_cotizatie_anuala): ?>
                <a href="/setari?tab=cotizatii" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300">Anulare</a>
                <?php endif; ?>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700 text-sm [&_th]:min-h-[1.5em] [&_th]:leading-[1.5] [&_td]:min-h-[1.5em] [&_td]:leading-[1.5] [&_th]:align-middle [&_td]:align-middle [&_th]:py-0 [&_td]:py-0" role="table" aria-label="Lista cotizații anuale">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Anul</th>
                            <th class="px-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Grad handicap</th>
                            <th class="px-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Asistent personal</th>
                            <th class="px-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Valoare (lei)</th>
                            <th class="px-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($lista_cotizatii_anuale as $c): ?>
                        <tr>
                            <td class="px-3 text-slate-900 dark:text-white"><?php echo (int)$c['anul']; ?></td>
                            <td class="px-3 text-slate-900 dark:text-white"><?php echo htmlspecialchars($graduri_handicap[$c['grad_handicap']] ?? $c['grad_handicap']); ?></td>
                            <td class="px-3 text-slate-900 dark:text-white"><?php echo htmlspecialchars($asistent_personal_opts[$c['asistent_personal'] ?? ''] ?? ($c['asistent_personal'] ?? '')); ?></td>
                            <td class="px-3 text-right text-slate-900 dark:text-white"><?php echo number_format((float)$c['valoare_cotizatie'], 2, ',', '.'); ?></td>
                            <td class="px-3 text-right">
                                <a href="/setari?tab=cotizatii&edit_cotizatie=<?php echo (int)$c['id']; ?>" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">Modificare</a>
                                <form method="post" action="/setari?tab=cotizatii" class="inline ml-2" onsubmit="return confirm('Ștergeți această cotizație?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="sterge_cotizatie_anuala" value="1">
                                    <input type="hidden" name="id_cotizatie_anuala" value="<?php echo (int)$c['id']; ?>">
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Șterge</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista_cotizatii_anuale)): ?>
                        <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500 dark:text-gray-400">Nicio cotizație. Folosiți formularul de mai sus.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="cotizatii-scutiri-heading">
            <h2 id="cotizatii-scutiri-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Membri scutiți de la plata cotizației</h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Adăugați membri care sunt scutiți (permanent sau până la o dată) și motivul scutirii.</p>
            <div class="mb-4 p-4 bg-slate-50 dark:bg-gray-700/50 rounded-lg border border-slate-200 dark:border-gray-600">
                <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3"><?php echo $edit_scutire_cotizatie ? 'Modifică scutire' : 'Adaugă membru scutit'; ?></h3>
                <form method="post" action="/setari?tab=cotizatii" id="form-scutire-cotizatie">
                    <?php echo csrf_field(); ?>
                    <?php if ($edit_scutire_cotizatie): ?>
                    <input type="hidden" name="actualizeaza_scutire_cotizatie" value="1">
                    <input type="hidden" name="id_scutire" value="<?php echo (int)$edit_scutire_cotizatie['id']; ?>">
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">Membru: <strong><?php echo htmlspecialchars(trim(($edit_scutire_cotizatie['nume'] ?? '') . ' ' . ($edit_scutire_cotizatie['prenume'] ?? ''))); ?></strong></p>
                    <?php else: ?>
                    <input type="hidden" name="adauga_scutire_cotizatie" value="1">
                    <div class="mb-3">
                        <label for="cautare-membru-scutire" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Caută membru</label>
                        <div class="flex gap-2">
                            <input type="text" id="cautare-membru-scutire" placeholder="Nume sau prenume (min. 2 caractere)..." class="flex-1 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Caută membru" autocomplete="off">
                            <button type="button" id="btn-selecteaza-membru-scutire" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium">Selectează membru</button>
                        </div>
                        <div id="rezultate-cautare-scutire" class="mt-1 border border-slate-200 dark:border-gray-600 rounded-lg max-h-48 overflow-y-auto hidden bg-white dark:bg-gray-700" role="region" aria-live="polite"></div>
                        <input type="hidden" name="membru_id_scutire" id="membru_id_scutire" value="">
                        <p id="membru-selectat-afis" class="text-sm text-slate-600 dark:text-gray-400 mt-1"></p>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="flex items-center gap-2 text-slate-700 dark:text-gray-300">
                            <input type="hidden" name="scutire_permanenta" value="0">
                            <input type="checkbox" name="scutire_permanenta" value="1" id="scutire-permanenta" <?php echo ($edit_scutire_cotizatie && !empty($edit_scutire_cotizatie['scutire_permanenta'])) ? 'checked' : ''; ?> class="rounded border-slate-300 dark:border-gray-500 text-amber-600">
                            Scutire permanentă
                        </label>
                    </div>
                    <div class="mb-3" id="wrap-data-pana-la">
                        <label for="data-scutire-pana-la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Perioada de scutire: până la data</label>
                        <input type="date" id="data-scutire-pana-la" name="data_scutire_pana_la" value="<?php echo $edit_scutire_cotizatie && !empty($edit_scutire_cotizatie['data_scutire_pana_la']) ? htmlspecialchars($edit_scutire_cotizatie['data_scutire_pana_la']) : ''; ?>" class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" aria-label="Data până la care este valabilă scutirea">
                    </div>
                    <div class="mb-3">
                        <label for="motiv-scutire" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Motivul scutirii</label>
                        <textarea id="motiv-scutire" name="motiv_scutire" rows="2" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Ex: handicap grav, situație socială"><?php echo $edit_scutire_cotizatie ? htmlspecialchars($edit_scutire_cotizatie['motiv'] ?? '') : ''; ?></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg"><?php echo $edit_scutire_cotizatie ? 'Actualizează scutire' : 'Adaugă scutire'; ?></button>
                    <?php if ($edit_scutire_cotizatie): ?>
                    <a href="/setari?tab=cotizatii" class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 ml-2">Anulare</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista scutiri cotizație">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Membru</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Scutire până la / Permanentă</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Motiv</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php foreach ($lista_scutiri_cotizatii as $s): ?>
                        <tr id="scutire-<?php echo (int)$s['id']; ?>">
                            <td class="px-4 py-3 text-slate-900 dark:text-white"><?php echo htmlspecialchars(trim(($s['nume'] ?? '') . ' ' . ($s['prenume'] ?? ''))); ?></td>
                            <td class="px-4 py-3 text-slate-900 dark:text-white"><?php echo !empty($s['scutire_permanenta']) ? 'Permanentă' : ($s['data_scutire_pana_la'] ? date('d.m.Y', strtotime($s['data_scutire_pana_la'])) : '—'); ?></td>
                            <td class="px-4 py-3 text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars(mb_substr($s['motiv'] ?? '', 0, 80)); ?><?php echo mb_strlen($s['motiv'] ?? '') > 80 ? '...' : ''; ?></td>
                            <td class="px-4 py-3 text-right">
                                <a href="/setari?tab=cotizatii&edit_scutire=<?php echo (int)$s['id']; ?>#form-scutire-cotizatie" class="text-amber-600 dark:text-amber-400 hover:underline text-sm">Editează</a>
                                <form method="post" action="/setari?tab=cotizatii" class="inline ml-2" onsubmit="return confirm('Ștergeți această scutire?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="sterge_scutire_cotizatie" value="1">
                                    <input type="hidden" name="id_scutire" value="<?php echo (int)$s['id']; ?>">
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline text-sm">Șterge</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lista_scutiri_cotizatii)): ?>
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500 dark:text-gray-400">Nicio scutire. Adăugați un membru scutit cu formularul de mai sus.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <script>
        (function(){
            var permanenta = document.getElementById('scutire-permanenta');
            var wrapData = document.getElementById('wrap-data-pana-la');
            if (permanenta && wrapData) {
                function toggle() { wrapData.style.display = permanenta.checked ? 'none' : 'block'; }
                permanenta.addEventListener('change', toggle);
                toggle();
            }
            var cautare = document.getElementById('cautare-membru-scutire');
            var rezultate = document.getElementById('rezultate-cautare-scutire');
            var membruId = document.getElementById('membru_id_scutire');
            var membruAfis = document.getElementById('membru-selectat-afis');
            var btnSelect = document.getElementById('btn-selecteaza-membru-scutire');
            if (cautare && rezultate && membruId && membruAfis) {
                var selectat = { id: 0, nume: '', prenume: '' };
                function afiseazaRezultate(membri) {
                    rezultate.classList.remove('hidden');
                    rezultate.innerHTML = membri.length ? membri.map(function(m){
                        return '<button type="button" class="block w-full text-left px-3 py-2 hover:bg-amber-100 dark:hover:bg-gray-600 border-b border-slate-200 dark:border-gray-600 last:border-0" data-id="'+m.id+'" data-nume="'+(m.nume||'')+'" data-prenume="'+(m.prenume||'')+'">'+(m.nume||'')+' '+(m.prenume||'')+'</button>';
                    }).join('') : '<p class="px-3 py-2 text-slate-500 dark:text-gray-400">Niciun rezultat.</p>';
                    rezultate.querySelectorAll('button').forEach(function(btn){
                        btn.addEventListener('click', function(){
                            selectat = { id: parseInt(btn.dataset.id), nume: btn.dataset.nume||'', prenume: btn.dataset.prenume||'' };
                            membruId.value = selectat.id;
                            membruAfis.textContent = 'Selectat: ' + selectat.nume + ' ' + selectat.prenume;
                            rezultate.classList.add('hidden');
                        });
                    });
                }
                var timer;
                cautare.addEventListener('input', function(){
                    clearTimeout(timer);
                    var q = cautare.value.trim();
                    if (q.length < 2) { rezultate.classList.add('hidden'); return; }
                    timer = setTimeout(function(){
                        fetch('/api/cauta-membri?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(d){ afiseazaRezultate(d.membri||[]); });
                    }, 200);
                });
                function executaCautareScutire() { var q = cautare.value.trim(); if (q.length < 2) return; fetch('/api/cauta-membri?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(d){ afiseazaRezultate(d.membri||[]); }); }
                if (btnSelect) btnSelect.addEventListener('click', executaCautareScutire);
                cautare.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); executaCautareScutire(); } });
            }
        })();
        </script>

        <?php elseif ($tab_setari === 'email'): ?>
        <!-- Tab Email: setări SMTP și expeditor -->
        <section class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border-2 border-slate-200 dark:border-gray-600 p-6 max-w-2xl" aria-labelledby="setari-email-heading">
            <h2 id="setari-email-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i data-lucide="mail" class="mr-2 w-5 h-5 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                Setări Email (EMAILCRM)
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">Configurare trimitere emailuri automate din platformă. Folosit de notificări și alte module.</p>

            <form method="post" action="/setari?tab=email" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_setari_email" value="1">

                <div class="rounded-lg border-2 border-slate-200 dark:border-gray-600 p-4 bg-slate-50 dark:bg-gray-700/30">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">Server SMTP</h3>
                    <div class="space-y-3">
                        <label for="smtp_host" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Host SMTP</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($settings_email['smtp_host'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: smtp.gmail.com" aria-label="Adresa server SMTP">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="smtp_port" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Port</label>
                                <input type="number" id="smtp_port" name="smtp_port" value="<?php echo (int)($settings_email['smtp_port'] ?? 587); ?>"
                                       min="1" max="65535" class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                       aria-label="Port SMTP (ex: 587)">
                            </div>
                            <div>
                                <label for="smtp_encryption" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Criptare</label>
                                <select id="smtp_encryption" name="smtp_encryption" class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                        aria-label="Criptare SMTP (TLS sau SSL)">
                                    <option value="tls" <?php echo ($settings_email['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($settings_email['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo ($settings_email['smtp_encryption'] ?? '') === '' ? 'selected' : ''; ?>>Niciuna</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border-2 border-slate-200 dark:border-gray-600 p-4 bg-slate-50 dark:bg-gray-700/30">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">Autentificare</h3>
                    <div class="space-y-3">
                        <label for="smtp_user" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Utilizator SMTP</label>
                        <input type="text" id="smtp_user" name="smtp_user" value="<?php echo htmlspecialchars($settings_email['smtp_user'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               autocomplete="username" aria-label="Utilizator pentru autentificare SMTP">
                        <label for="smtp_pass" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Parolă SMTP</label>
                        <input type="password" id="smtp_pass" name="smtp_pass" value=""
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               autocomplete="current-password" placeholder="Lăsați gol pentru a păstra parola existentă" aria-label="Parolă SMTP (lăsați gol pentru a nu schimba)">
                    </div>
                </div>

                <div class="rounded-lg border-2 border-slate-200 dark:border-gray-600 p-4 bg-slate-50 dark:bg-gray-700/30">
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-3">Personalizare Expeditor</h3>
                    <div class="space-y-3">
                        <label for="from_name" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Nume expeditor</label>
                        <input type="text" id="from_name" name="from_name" value="<?php echo htmlspecialchars($settings_email['from_name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: ERP ANR BIHOR" aria-label="Numele afișat ca expeditor">
                        <label for="from_email" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Email expeditor</label>
                        <input type="email" id="from_email" name="from_email" value="<?php echo htmlspecialchars($settings_email['from_email'] ?? ''); ?>"
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: noreply@anrbihor.ro" aria-label="Adresa de email a expeditorului">
                        <label for="quill-signature-container" class="block text-sm font-medium text-slate-700 dark:text-gray-300">Semnătură globală (editor vizual)</label>
                        <input type="hidden" name="email_signature" id="email_signature_hidden" value="">
                        <div id="quill-signature-container" class="quill-editor-wrap border-2 border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 min-h-[120px]" role="textbox" aria-label="Semnătura adăugată la sfârșitul fiecărui email automat. Editor rich text: Bold, Italic, Link, Image.">
                            <?php echo $settings_email['email_signature'] ?? ''; ?>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mt-1"><strong>Logo în emailuri:</strong> Pentru ca logoul să apară în emailuri, folosiți butonul Imagine și introduceți fie URL-ul public al logoului (ex: din <code class="bg-slate-200 dark:bg-gray-600 px-1 rounded">assets/img/logo.png</code> pe server), fie un link Base64 (generat cu un tool online). Varianta Base64 încorporează imaginea în HTML și funcționează și când clientul de email blochează imaginile externe.</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Salvează setările email">Salvează setări</button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-gray-600">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-gray-200 mb-2">Verificare setări</h3>
                <form method="post" action="/setari?tab=email" class="flex flex-wrap items-end gap-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="trimite_email_test" value="1">
                    <div class="flex-1 min-w-[200px]">
                        <label for="email_test_destinatar" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Trimite test la adresa</label>
                        <input type="email" id="email_test_destinatar" name="email_test_destinatar" value=""
                               class="w-full px-3 py-2 border-2 border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="ex: email@domeniu.ro (opțional)" aria-label="Adresă email la care să se trimită emailul de test; lăsați gol pentru emailul utilizatorului logat">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-slate-600 hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-600 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Trimite un email de test">Trimite Email de Test</button>
                </form>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-2">Dacă nu completați adresa, se folosește emailul utilizatorului logat.</p>
            </div>
        </section>

        <!-- Quill.js pentru Semnătură (doar pe tab Email) -->
        <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
        <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
        <script>
        (function() {
            var container = document.getElementById('quill-signature-container');
            if (!container) return;
            var existingHtml = container.innerHTML.trim();
            container.innerHTML = '';
            var quill = new Quill(container, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic'],
                        ['link', 'image']
                    ]
                }
            });
            if (existingHtml) quill.root.innerHTML = existingHtml;
            container.setAttribute('aria-label', 'Semnătură email: editor rich text cu Bold, Italic, Link, Imagine.');
            var form = container.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    var hidden = document.getElementById('email_signature_hidden');
                    if (hidden) hidden.value = quill.root.innerHTML;
                });
            }
        })();
        </script>

        <?php elseif ($tab_setari === 'dashboard'): ?>
        <!-- Tab Dashboard: administrare subiecte Registru Interacțiuni -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-2xl" aria-labelledby="dashboard-interactiuni-heading">
            <h2 id="dashboard-interactiuni-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i data-lucide="phone-call" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Registru Interacțiuni – Subiecte dropdown
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Creați și activați sau dezactivați subiectele afișate în meniul dropdown din formularul de interacțiuni (Dashboard și pagina Registru Interacțiuni). Subiectele dezactivate nu apar în dropdown.</p>
            <form method="post" action="/setari?tab=dashboard" class="mb-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="adauga_subiect_interactiune_v2" value="1">
                <div class="flex gap-2">
                    <input type="text" name="subiect_nou_v2" id="subiect_nou_v2"
                           placeholder="Ex: Cerere documente"
                           class="flex-1 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-label="Nume subiect nou">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Adaugă subiect">Adaugă</button>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista subiecte registru interacțiuni">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Subiect</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiune</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($subiecte_dashboard_v2)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-slate-500 dark:text-gray-400">Niciun subiect. Adăugați un subiect cu formularul de mai sus.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($subiecte_dashboard_v2 as $s): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($s['nume']); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $s['activ'] ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200' : 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-gray-300'; ?>">
                                    <?php echo $s['activ'] ? 'Activ' : 'Dezactivat'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="post" action="/setari?tab=dashboard" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="toggle_subiect_activ_v2" value="1">
                                    <input type="hidden" name="subiect_id_v2" value="<?php echo (int)$s['id']; ?>">
                                    <button type="submit" class="text-sm font-medium <?php echo $s['activ'] ? 'text-amber-600 dark:text-amber-400 hover:underline' : 'text-emerald-600 dark:text-emerald-400 hover:underline'; ?>"
                                            aria-label="<?php echo $s['activ'] ? 'Dezactivează' : 'Activează'; ?> <?php echo htmlspecialchars($s['nume']); ?>">
                                        <?php echo $s['activ'] ? 'Dezactivează' : 'Activează'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php elseif ($tab_setari === 'tickete'): ?>
        <!-- Tab Tickete: administrare departamente -->
        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-2xl" aria-labelledby="tickete-departamente-heading">
            <h2 id="tickete-departamente-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i data-lucide="ticket" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Departamente Tickete
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Creati si activati sau dezactivati departamentele disponibile in modulul Tickete. Departamentele dezactivate nu apar in dropdown-ul de selectie.</p>
            <form method="post" action="/setari?tab=tickete" class="mb-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="adauga_departament_ticket" value="1">
                <div class="flex gap-2">
                    <input type="text" name="nume_departament_ticket" id="nume_departament_ticket"
                           placeholder="Ex: Resurse Umane"
                           class="flex-1 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           required aria-label="Nume departament nou">
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 transition">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-1" aria-hidden="true"></i> Adauga
                    </button>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista departamente tickete">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Departament</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($lista_departamente_tickete)): ?>
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu exista departamente definite.</td></tr>
                        <?php else: foreach ($lista_departamente_tickete as $dep): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white font-medium"><?php echo htmlspecialchars($dep['nume']); ?></td>
                            <td class="px-4 py-3">
                                <?php if ($dep['activ']): ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">Activ</span>
                                <?php else: ?>
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400">Inactiv</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <form method="post" action="/setari?tab=tickete" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="toggle_departament_ticket" value="1">
                                    <input type="hidden" name="departament_id_ticket" value="<?php echo (int)$dep['id']; ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium <?php echo $dep['activ'] ? 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/50 hover:bg-red-200 dark:hover:bg-red-800' : 'text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-900/50 hover:bg-emerald-200 dark:hover:bg-emerald-800'; ?> rounded focus:ring-2 focus:ring-amber-500"
                                            aria-label="<?php echo $dep['activ'] ? 'Dezactiveaza' : 'Activeaza'; ?> departamentul <?php echo htmlspecialchars($dep['nume']); ?>">
                                        <i data-lucide="<?php echo $dep['activ'] ? 'x' : 'check'; ?>" class="w-4 h-4" aria-hidden="true"></i>
                                        <?php echo $dep['activ'] ? 'Dezactiveaza' : 'Activeaza'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php else: ?>
        <!-- Tab General: setări platformă (3 coloane) -->
        <!-- Buton Management Generare Documente -->
        <div class="mb-6">
            <a href="/generare-documente"
               class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
               aria-label="Deschide Management Generare Documente">
                <i data-lucide="file-text" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Management Generare Documente
            </a>
        </div>

        <!-- Secțiune Antet asociație (DOCX) -->
        <section class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="antet-asociatie-heading">
            <h2 id="antet-asociatie-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-2 flex items-center">
                <i data-lucide="file-type" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Antet asociație
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Încărcați un document DOCX care conține antetul asociației. Acest antet se va folosi la toate documentele generate în platformă, cu excepția modulului <strong>Generare documente</strong> precompletate cu datele membrului și a modulului <strong>Încasări</strong>.</p>
            <form method="post" action="/setari" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="incarca_antet_asociatie" value="1">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="antet_docx" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Fișier DOCX <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="file" id="antet_docx" name="antet_docx" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-amber-50 file:text-amber-800 dark:file:bg-amber-900/30 dark:file:text-amber-200"
                               aria-required="true" aria-describedby="antet-docx-desc">
                        <p id="antet-docx-desc" class="text-xs text-slate-500 dark:text-gray-400 mt-1">Doar format DOCX, maxim 10 MB.</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Încarcă antet asociație">Încarcă antet</button>
                </div>
                <?php if (!empty($antet_asociatie_docx) && file_exists(APP_ROOT . '/' . $antet_asociatie_docx)): ?>
                <div class="pt-2 border-t border-slate-200 dark:border-gray-600">
                    <p class="text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Antet curent</p>
                    <a href="<?php echo htmlspecialchars($antet_asociatie_docx); ?>" download class="inline-flex items-center gap-2 text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded" aria-label="Descarcă antetul curent">
                        <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                        <?php echo htmlspecialchars(basename($antet_asociatie_docx)); ?>
                    </a>
                </div>
                <?php elseif (!empty($antet_asociatie_docx)): ?>
                <p class="text-sm text-amber-700 dark:text-amber-300">Antet setat, dar fișierul lipsește pe server. Încărcați din nou un DOCX.</p>
                <?php endif; ?>
            </form>
        </section>

        <!-- Secțiune Management utilizatori (doar administrator) -->
        <?php if (!empty($_SESSION['user_id']) && is_admin()): ?>
        <section class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="utilizatori-heading">
            <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
                <h2 id="utilizatori-heading" class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                    <i data-lucide="users" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Management utilizatori
                </h2>
                <button type="button" id="btn-adauga-utilizator"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                        aria-label="Adaugă utilizator" aria-haspopup="dialog" aria-expanded="false" aria-controls="modal-adauga-utilizator">
                    <i data-lucide="user-plus" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                    Adaugă utilizator
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Lista utilizatori">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume complet</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Email</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Funcție</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume utilizator</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Rol</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Email notif.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($lista_utilizatori)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-600 dark:text-gray-400">Niciun utilizator. Adăugați un utilizator cu butonul de mai sus.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($lista_utilizatori as $u): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($u['nume_complet']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($u['functie'] ?? '-'); ?></td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $u['rol'] === 'administrator' ? 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100' : 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200'; ?>"><?php echo htmlspecialchars($u['rol']); ?></span>
                            </td>
                            <td class="px-4 py-3 text-sm <?php echo $u['activ'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-gray-400'; ?>"><?php echo $u['activ'] ? 'Activ' : 'Dezactivat'; ?></td>
                            <td class="px-4 py-3">
                                <form method="post" action="/setari" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="toggle_email_notif" value="1">
                                    <input type="hidden" name="utilizator_id" value="<?php echo (int)$u['id']; ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium <?php echo !empty($u['primeste_notificari_email']) ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-600' : 'bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-gray-400 border border-slate-300 dark:border-gray-600'; ?>"
                                            aria-label="<?php echo !empty($u['primeste_notificari_email']) ? 'Dezactivează notificări email' : 'Activează notificări email'; ?>">
                                        <i data-lucide="<?php echo !empty($u['primeste_notificari_email']) ? 'bell' : 'bell-off'; ?>" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                        <?php echo !empty($u['primeste_notificari_email']) ? 'Da' : 'Nu'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- Grid cu 3 coloane pentru setări -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Secțiune 1: Setare Logo Platformă -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="logo-heading">
                <h2 id="logo-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="image" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Logo Platformă
                </h2>

                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_logo" value="1">

                    <div>
                        <label for="logo_url" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">
                            URL Logo <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                        </label>
                        <input type="url"
                               id="logo_url"
                               name="logo_url"
                               value="<?php echo htmlspecialchars($logo_url_actual); ?>"
                               required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="https://exemplu.com/logo.png"
                               aria-required="true"
                               aria-describedby="logo-desc">
                        <p id="logo-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Introduceți URL-ul complet al logo-ului platformei
                        </p>
                    </div>

                    <div class="flex items-center gap-3 mb-4">
                        <?php if (!empty($logo_url_actual)): ?>
                        <div class="flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($logo_url_actual); ?>"
                                 alt="Logo actual"
                                 class="h-16 w-auto object-contain border border-slate-200 dark:border-gray-600 rounded p-2 bg-white dark:bg-gray-700"
                                 onerror="this.style.display='none'">
                        </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                            aria-label="Salvează setările logo-ului">
                        <i data-lucide="save" class="inline-block mr-2 w-4 h-4" aria-hidden="true"></i>
                        Salvează Logo
                    </button>
                </form>
            </section>

            <!-- Secțiune: Nume Platformă (doar administrator) -->
            <?php if (is_admin()): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="nume-platforma-heading">
                <h2 id="nume-platforma-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="type" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Nume Platformă
                </h2>

                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_nume_platforma" value="1">

                    <div>
                        <label for="nume_platforma" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">
                            Nume Platformă <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="nume_platforma"
                               name="nume_platforma"
                               value="<?php echo htmlspecialchars($nume_platforma_actual); ?>"
                               required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="CRM ANR Bihor"
                               aria-required="true"
                               aria-describedby="nume-platforma-desc">
                        <p id="nume-platforma-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Numele afișat în header și în titlurile paginilor
                        </p>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition"
                            aria-label="Salvează numele platformei">
                        <i data-lucide="save" class="inline-block mr-2 w-4 h-4" aria-hidden="true"></i>
                        Salvează Nume
                    </button>
                </form>
            </section>
            <?php endif; ?>

            <!-- Secțiune: Generare Documente -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="documente-heading">
                <h2 id="documente-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="file-text" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Generare Documente
                </h2>
                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_documente" value="1">
                    <div>
                        <label for="email_asociatie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Email asociație (Cc la trimitere documente)</label>
                        <input type="email" id="email_asociatie" name="email_asociatie"
                               value="<?php echo htmlspecialchars($email_asociatie); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="contact@anrbihor.ro">
                    </div>
                    <div>
                        <label for="cale_libreoffice" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Cale LibreOffice (pentru conversie PDF)</label>
                        <input type="text" id="cale_libreoffice" name="cale_libreoffice"
                               value="<?php echo htmlspecialchars($cale_libreoffice); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="C:\Program Files\LibreOffice\program\soffice.exe">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Lăsați gol dacă nu doriți conversie PDF. Se va oferi doar descărcare DOCX.</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează setările logo-ului">Salvează</button>
                </form>
            </section>

            <!-- Secțiune: Newsletter -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="newsletter-heading">
                <h2 id="newsletter-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="mail" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Newsletter
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Emailul de pe care se trimit newsletterele către contacte. Numele expeditorului se setează în formularul de trimitere.</p>
                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_newsletter" value="1">
                    <div>
                        <label for="newsletter_email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-2">Email expeditor newsletter</label>
                        <input type="email" id="newsletter_email" name="newsletter_email"
                               value="<?php echo htmlspecialchars($newsletter_email); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               placeholder="newsletter@anrbihor.ro">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Dacă este gol, se folosește emailul asociației (Generare Documente).</p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează setările newsletter">Salvează</button>
                </form>
            </section>

            <!-- Secțiune: Registratura -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="registratura-heading">
                <h2 id="registratura-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="book-open" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Registratura
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">Numărul de pornire pentru numerotarea automată a înregistrărilor în registratură.</p>
                <?php
                // Calculează următorul număr care va fi alocat
                require_once APP_ROOT . '/includes/registratura_helper.php';
                $urmatorul_nr = registratura_urmatorul_nr($pdo);
                ?>
                <form method="post" action="/setari" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="actualizeaza_registratura" value="1">
                    <div>
                        <label for="registratura_nr_pornire" class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Număr pornire înregistrări</label>
                        <input type="number" id="registratura_nr_pornire" name="registratura_nr_pornire" min="1" step="1"
                               value="<?php echo (int)$registratura_nr_pornire; ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               aria-describedby="registratura-nr-desc">
                        <p id="registratura-nr-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                            Primul număr alocat la o înregistrare nouă (incremental de aici).
                            <?php if ($urmatorul_nr != $registratura_nr_pornire): ?>
                            <br><strong class="text-amber-600 dark:text-amber-400">Următorul număr care va fi alocat: <?php echo $urmatorul_nr; ?></strong>
                            <?php else: ?>
                            <br><span class="text-slate-500 dark:text-gray-400">Următorul număr care va fi alocat: <strong><?php echo $urmatorul_nr; ?></strong></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Salvează setările registraturii">Salvează</button>
                </form>
            </section>

            <!-- Secțiune: Import Membri (redirijare către modulul nou CSV) -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="import-heading">
                <h2 id="import-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="upload" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Import membri
                </h2>
                <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                    Pentru importul avansat al membrilor din fișiere CSV (cu mapare de coloane),
                    folosește modulul dedicat de import. Aici poți și exporta lista actuală de membri.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="util/export_membri.php"
                       class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        <i data-lucide="download" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                        Export membri în CSV
                    </a>
                    <a href="/import-membri-csv"
                       class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition">
                        <i data-lucide="upload" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                        Deschide modul Import membri CSV
                    </a>
                </div>
            </section>

        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal Adaugă utilizator -->
<dialog id="modal-adauga-utilizator" role="dialog" aria-modal="true" aria-labelledby="modal-utilizator-title" aria-describedby="modal-utilizator-desc"
        class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="modal-utilizator-title" class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adaugă utilizator</h2>
        <p id="modal-utilizator-desc" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completați datele. După salvare, utilizatorul primește un email de confirmare (fără parolă) și link către platformă.</p>
        <form method="post" action="/setari" id="form-adauga-utilizator">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_utilizator" value="1">
            <div class="space-y-4">
                <div>
                    <label for="util-nume_complet" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Numele complet <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="util-nume_complet" name="nume_complet" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="util-email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="email" id="util-email" name="email" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="util-functie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Funcția din cadrul organizației</label>
                    <input type="text" id="util-functie" name="functie"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           placeholder="Ex: Secretar, Contabil">
                </div>
                <div>
                    <label for="util-username" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume utilizator <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="util-username" name="username" required autocomplete="username"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true">
                </div>
                <div>
                    <label for="util-parola" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Parolă <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="password" id="util-parola" name="parola" required minlength="6" autocomplete="new-password"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                           aria-required="true" aria-describedby="util-parola-desc">
                    <p id="util-parola-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Minim 6 caractere. Nu se afișează în emailul de confirmare.</p>
                </div>
                <div>
                    <label for="util-rol" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Rol <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="util-rol" name="rol" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                        <option value="operator">Operator</option>
                        <option value="administrator">Administrator</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-inchide-modal-utilizator" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulare (închide fereastra)">Anulare</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează și trimite email confirmare">Salvează și trimite email</button>
            </div>
        </form>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    var modalUtil = document.getElementById('modal-adauga-utilizator');
    var btnDeschide = document.getElementById('btn-adauga-utilizator');
    var btnInchide = document.getElementById('btn-inchide-modal-utilizator');
    if (btnDeschide && modalUtil) {
        btnDeschide.addEventListener('click', function() {
            modalUtil.showModal();
            btnDeschide.setAttribute('aria-expanded', 'true');
            document.getElementById('util-nume_complet').focus();
        });
    }
    if (btnInchide && modalUtil) {
        btnInchide.addEventListener('click', function() {
            modalUtil.close();
            if (btnDeschide) btnDeschide.setAttribute('aria-expanded', 'false');
        });
    }
    if (modalUtil) {
        modalUtil.addEventListener('close', function() {
            if (btnDeschide) btnDeschide.setAttribute('aria-expanded', 'false');
        });
        modalUtil.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') this.close();
        });
    }
});
</script>
</body>
</html>
