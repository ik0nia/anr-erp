<?php
/**
 * View: Încasări > Setări serii chitanțe
 *
 * Variabile:
 * - $incasari_serie_donatii
 * - $incasari_serie_incasari
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white flex items-center gap-2">
            <i data-lucide="settings" class="w-6 h-6" aria-hidden="true"></i>
            Setări Încasări — Serii chitanțe
        </h1>
        <a href="/incasari"
           class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-700 hover:bg-slate-800 dark:bg-slate-600 dark:hover:bg-slate-500 text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-slate-500"
           aria-label="Înapoi la lista de încasări">
            <i data-lucide="arrow-left" class="w-4 h-4" aria-hidden="true"></i>
            Înapoi la Încasări
        </a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($succes)): ?>
            <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 dark:border-emerald-500 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
                <?php echo htmlspecialchars($succes); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($eroare)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 dark:border-red-500 text-red-800 dark:text-red-200 rounded-r" role="alert" aria-live="assertive">
                <?php echo htmlspecialchars($eroare); ?>
            </div>
        <?php endif; ?>

        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="incasari-serii-manager-heading">
            <h2 id="incasari-serii-manager-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                Manager date chitanțe
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Gestionați seria și intervalul de numere alocat pentru chitanțele de donații (inclusiv taxe și alte încasări) și pentru chitanțele de cotizații.
            </p>

            <form method="post" action="/incasari/setari" class="space-y-6">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_serii_incasari" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg">
                        <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3">Tip chitanță: Donație</h3>
                        <div class="space-y-2">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Serie chitanță</label>
                            <input type="text"
                                   name="serie_donatii"
                                   value="<?php echo htmlspecialchars($incasari_serie_donatii['serie'] ?? 'CEDON'); ?>"
                                   maxlength="20"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">

                            <label class="block text-sm text-slate-700 dark:text-gray-300">Număr început / curent / final</label>
                            <div class="flex gap-2 flex-wrap">
                                <input type="number"
                                       name="nr_start_donatii"
                                       value="<?php echo (int)($incasari_serie_donatii['nr_start'] ?? 1); ?>"
                                       min="1"
                                       class="w-28 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                <input type="number"
                                       name="nr_curent_donatii"
                                       value="<?php echo (int)($incasari_serie_donatii['nr_curent'] ?? 1); ?>"
                                       min="1"
                                       class="w-28 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                <input type="number"
                                       name="nr_final_donatii"
                                       value="<?php echo (int)($incasari_serie_donatii['nr_final'] ?? 1); ?>"
                                       min="1"
                                       class="w-28 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg">
                        <h3 class="font-medium text-slate-800 dark:text-gray-200 mb-3">Tip chitanță: Cotizație</h3>
                        <div class="space-y-2">
                            <label class="block text-sm text-slate-700 dark:text-gray-300">Serie chitanță</label>
                            <input type="text"
                                   name="serie_incasari"
                                   value="<?php echo htmlspecialchars($incasari_serie_incasari['serie'] ?? 'CECOT'); ?>"
                                   maxlength="20"
                                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">

                            <label class="block text-sm text-slate-700 dark:text-gray-300">Număr început / curent / final</label>
                            <div class="flex gap-2 flex-wrap">
                                <input type="number"
                                       name="nr_start_incasari"
                                       value="<?php echo (int)($incasari_serie_incasari['nr_start'] ?? 1); ?>"
                                       min="1"
                                       class="w-28 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                <input type="number"
                                       name="nr_curent_incasari"
                                       value="<?php echo (int)($incasari_serie_incasari['nr_curent'] ?? 1); ?>"
                                       min="1"
                                       class="w-28 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                                <input type="number"
                                       name="nr_final_incasari"
                                       value="<?php echo (int)($incasari_serie_incasari['nr_final'] ?? 1); ?>"
                                       min="1"
                                       class="w-28 px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                        Salvează setări chitanțe
                    </button>
                    <a href="/setari?tab=incasari"
                       class="px-4 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">
                        Setări avansate chitanțe
                    </a>
                </div>
            </form>
        </section>

        <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 mt-6" aria-labelledby="incasari-design-heading-local">
            <h2 id="incasari-design-heading-local" class="text-lg font-semibold text-slate-900 dark:text-white mb-2">
                Design chitanțe și notificări
            </h2>
            <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
                Configurați datele vizuale ale chitanței și adresa de email pentru notificarea ștergerii unei chitanțe.
            </p>
            <form method="post" action="/incasari/setari" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_design_chitante" value="1">

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

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">URL logo (chitanță)</label>
                    <input type="url" name="logo_chitanta" value="<?php echo htmlspecialchars($incasari_setari_design['logo_chitanta'] ?? ''); ?>" placeholder="https://..." class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Date asociație (stânga sus pe chitanță)</label>
                    <textarea name="date_asociatie" rows="5" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white" placeholder="Denumire, CUI, sediu, cont bancar..."><?php echo htmlspecialchars($incasari_setari_design['date_asociatie'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Email notificări ștergere chitanță</label>
                    <input type="email" name="email_notificari_stergere_chitanta" value="<?php echo htmlspecialchars($incasari_setari_design['email_notificari_stergere_chitanta'] ?? ''); ?>" placeholder="ex: notificari@asociatie.ro" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white">
                </div>

                <div class="p-4 border border-slate-200 dark:border-gray-600 rounded-lg" aria-labelledby="chitanta-info-suplimentara-heading">
                    <h3 id="chitanta-info-suplimentara-heading" class="text-base font-semibold text-slate-900 dark:text-white mb-2">
                        Informații suplimentare pe chitanță
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mb-3">
                        Încărcați o imagine tip carte de vizită (recomandat: 5,5 cm × 8,5 cm). Imaginea este integrată automat pe fiecare chitanță ERP, în partea stânga-jos, la ~2 cm de linia de tăiere.
                    </p>
                    <label for="chitanta_info_suplimentara_image" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">
                        Imagine informații suplimentare (PNG/JPG/WEBP/GIF, max 8 MB)
                    </label>
                    <input
                        type="file"
                        id="chitanta_info_suplimentara_image"
                        name="info_suplimentare_chitanta_imagine"
                        accept=".png,.jpg,.jpeg,.webp,.gif,image/png,image/jpeg,image/webp,image/gif"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-slate-900 dark:text-white"
                        aria-describedby="chitanta-info-suplimentara-help">
                    <p id="chitanta-info-suplimentara-help" class="text-xs text-slate-500 dark:text-gray-400 mt-2">
                        Dacă încărcați o nouă imagine, aceasta o va înlocui pe cea existentă.
                    </p>

                    <?php if (!empty($incasari_setari_design['info_suplimentare_chitanta_image_url'])): ?>
                        <div class="mt-3">
                            <p class="text-xs font-medium text-slate-700 dark:text-gray-300 mb-2">Previzualizare imagine activă:</p>
                            <img
                                src="<?php echo htmlspecialchars($incasari_setari_design['info_suplimentare_chitanta_image_url']); ?>"
                                alt="Imagine informații suplimentare chitanță"
                                class="max-w-[220px] h-auto border border-slate-300 dark:border-gray-600 rounded">
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500">
                        Salvează design și notificări
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>
