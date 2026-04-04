<?php
/**
 * View: Fundraising — Formular 230 + Setări modul
 *
 * Variabile:
 * $tab, $eroare, $succes, $warning, $setari_modul, $taguri_f230,
 * $lista_formulare, $manual_modal_open, $valori_formular
 */
?>
<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Fundraising</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if ($eroare !== ''): ?>
            <div class="mb-4 p-4 rounded-lg border-l-4 border-red-600 bg-red-100 dark:bg-red-900/30 text-red-900 dark:text-red-200" role="alert" aria-live="assertive">
                <?php echo htmlspecialchars($eroare); ?>
            </div>
        <?php endif; ?>
        <?php if ($succes !== ''): ?>
            <div class="mb-4 p-4 rounded-lg border-l-4 border-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-900 dark:text-emerald-200" role="status" aria-live="polite">
                <?php echo htmlspecialchars($succes); ?>
            </div>
        <?php endif; ?>
        <?php if ($warning !== ''): ?>
            <div class="mb-4 p-4 rounded-lg border-l-4 border-amber-600 bg-amber-100 dark:bg-amber-900/30 text-amber-900 dark:text-amber-200" role="status" aria-live="polite">
                <?php echo htmlspecialchars($warning); ?>
            </div>
        <?php endif; ?>

        <div class="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Tab-uri Fundraising">
            <a href="/fundraising?tab=formular230"
               role="tab"
               aria-selected="<?php echo $tab === 'formular230' ? 'true' : 'false'; ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'formular230' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700 border border-transparent'; ?>">
                <i data-lucide="file-stack" class="w-5 h-5" aria-hidden="true"></i>
                Formular 230
            </a>
            <a href="/fundraising?tab=setari"
               role="tab"
               aria-selected="<?php echo $tab === 'setari' ? 'true' : 'false'; ?>"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-t-lg font-medium <?php echo $tab === 'setari' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 border border-b-0 border-slate-200 dark:border-gray-700' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-100 dark:hover:bg-gray-700 border border-transparent'; ?>">
                <i data-lucide="settings" class="w-5 h-5" aria-hidden="true"></i>
                Setări
            </a>
        </div>

        <?php if ($tab === 'formular230'): ?>
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden" aria-label="Manager formulare 230">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Formulare 230 completate</h2>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">Date centralizate pentru export și raportare ANAF.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="/fundraising?tab=formular230&export=csv"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-slate-500"
                           aria-label="Exportă formularele în CSV">
                            <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                            Export
                        </a>
                        <button type="button"
                                id="btn-adauga-manual"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-amber-500"
                                aria-haspopup="dialog"
                                aria-controls="dialog-manual-f230">
                            <i data-lucide="file-plus-2" class="w-4 h-4" aria-hidden="true"></i>
                            Adauga formular manual
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Tabel formulare 230">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">ID</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume și Prenume</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">CNP</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Localitate / Județ</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Telefon</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Email</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Sursă</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Document PDF</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($lista_formulare)): ?>
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">
                                        Nu există formulare înregistrate.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lista_formulare as $f): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo (int)$f['id']; ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars(trim((string)$f['nume'] . ' ' . (string)$f['prenume'])); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars((string)$f['cnp']); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars((string)$f['localitate'] . ' / ' . (string)$f['judet']); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars((string)$f['telefon']); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars((string)$f['email']); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars((string)$f['sursa']); ?></td>
                                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-gray-100"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime((string)$f['created_at']))); ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="/util/f230-descarca.php?id=<?php echo (int)$f['id']; ?>"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium focus:ring-2 focus:ring-emerald-500"
                                               aria-label="Descarcă PDF pentru formularul <?php echo (int)$f['id']; ?>">
                                                <i data-lucide="file-down" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                                Descarcă PDF
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tab === 'setari'): ?>
            <section class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Setări modul Fundraising</h2>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">Centralizare template PDF, link public și mesaj confirmare.</p>
                </div>

                <form method="post" enctype="multipart/form-data" action="/fundraising?tab=setari"
                      class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 space-y-6">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="salveaza_setari_fundraising" value="1">

                    <section aria-labelledby="f230-template-heading" class="space-y-3">
                        <h3 id="f230-template-heading" class="text-base font-semibold text-slate-900 dark:text-white">Template PDF Formular 230</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400">Încarcă fișierul PDF, apoi mapează zonele câmpurilor într-o fereastră separată.</p>
                        <?php if (!empty($setari_modul['template_rel'])): ?>
                            <p class="text-xs text-slate-600 dark:text-gray-400">
                                Template curent:
                                <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-gray-700"><?php echo htmlspecialchars((string)$setari_modul['template_rel']); ?></code>
                                <?php if (empty($setari_modul['template_exists'])): ?>
                                    <span class="text-red-600 dark:text-red-400">(fișier lipsă)</span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($setari_modul['template_exists'])): ?>
                            <div class="text-xs">
                                <?php if (!empty($setari_modul['template_mapat'])): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                        Template mapat complet
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                        Template nemapat — formularul public este blocat până la mapare
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <label for="template_pdf_230" class="block text-sm font-medium text-slate-800 dark:text-gray-200">Template PDF (.pdf)</label>
                        <input id="template_pdf_230"
                               name="template_pdf_230"
                               type="file"
                               accept=".pdf,application/pdf"
                               class="block w-full text-sm text-slate-700 dark:text-gray-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-slate-100 dark:file:bg-gray-700 file:text-slate-800 dark:file:text-gray-100">
                        <div>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium focus:ring-2 focus:ring-amber-500">
                                <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i>
                                Salveaza
                            </button>
                        </div>
                        <div class="pt-1">
                            <a href="/util/f230-template-mapper.php"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium focus:ring-2 focus:ring-indigo-500 <?php echo empty($setari_modul['template_exists']) ? 'opacity-50 pointer-events-none' : ''; ?>"
                               aria-disabled="<?php echo empty($setari_modul['template_exists']) ? 'true' : 'false'; ?>">
                                <i data-lucide="crosshair" class="w-4 h-4" aria-hidden="true"></i>
                                Deschide fereastra de mapare
                            </a>
                        </div>
                        <?php if (!empty($setari_modul['template_exists']) && empty($setari_modul['template_mapat']) && !empty($setari_modul['template_map_missing_tags'])): ?>
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                Taguri nemapate:
                                <?php echo htmlspecialchars(implode(', ', array_map(static function ($t) { return '[' . (string)$t . ']'; }, (array)$setari_modul['template_map_missing_tags']))); ?>
                            </p>
                        <?php endif; ?>
                    </section>

                    <section aria-labelledby="f230-tags-heading" class="space-y-3">
                        <h3 id="f230-tags-heading" class="text-base font-semibold text-slate-900 dark:text-white">Taguri disponibile</h3>
                        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Taguri formular 230">
                                <thead class="bg-slate-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tag</th>
                                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Descriere</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-slate-200 dark:divide-gray-700">
                                    <?php foreach ($taguri_f230 as $t): ?>
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100"><code><?php echo htmlspecialchars((string)$t['tag']); ?></code></td>
                                            <td class="px-4 py-2 text-sm text-slate-700 dark:text-gray-300"><?php echo htmlspecialchars((string)$t['descriere']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section aria-labelledby="f230-link-heading" class="space-y-3">
                        <h3 id="f230-link-heading" class="text-base font-semibold text-slate-900 dark:text-white">Link public formular</h3>
                        <label for="f230-public-link" class="block text-sm font-medium text-slate-800 dark:text-gray-200">URL formular public</label>
                        <input id="f230-public-link"
                               type="text"
                               readonly
                               value="<?php echo htmlspecialchars((string)$setari_modul['public_url']); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 bg-slate-50 dark:bg-gray-700 text-slate-800 dark:text-gray-100"
                               aria-readonly="true">
                    </section>

                    <section aria-labelledby="f230-storage-heading" class="space-y-3">
                        <h3 id="f230-storage-heading" class="text-base font-semibold text-slate-900 dark:text-white">Stocare PDF</h3>
                        <p class="text-sm text-slate-700 dark:text-gray-300">
                            Documentele generate sunt salvate în folderul privat
                            <code class="px-1 py-0.5 rounded bg-slate-100 dark:bg-gray-700">F230PDF</code>,
                            cu acces blocat public (disponibil doar prin server/FTP).
                        </p>
                    </section>

                    <section aria-labelledby="f230-msg-heading" class="space-y-3">
                        <h3 id="f230-msg-heading" class="text-base font-semibold text-slate-900 dark:text-white">Mesaj confirmare</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400">Mesajul trimis automat pe email după completarea formularului public.</p>
                        <label for="mesaj-confirmare-editor" class="sr-only">Editor mesaj confirmare</label>
                        <textarea id="mesaj-confirmare-editor" class="min-h-[220px]"><?php echo htmlspecialchars((string)$setari_modul['confirm_html']); ?></textarea>
                        <input type="hidden" name="mesaj_confirmare_html" id="mesaj-confirmare-html" value="<?php echo htmlspecialchars((string)$setari_modul['confirm_html']); ?>">
                    </section>

                    <div class="pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-amber-500">
                            <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i>
                            Salvează setările
                        </button>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </div>
</main>

<dialog id="dialog-manual-f230" class="p-0 rounded-lg shadow-xl max-w-5xl w-[calc(100%-1.5rem)] mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/50">
    <div class="p-6">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Adaugă formular manual</h2>
            <button type="button"
                    id="btn-inchide-manual"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full text-slate-700 dark:text-gray-200 hover:bg-slate-100 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500"
                    aria-label="Închide fereastra de adăugare manuală">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>

        <form method="post" action="/fundraising?tab=formular230" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_formular_manual" value="1">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume <span class="text-red-600">*</span></label>
                    <input type="text" name="nume" required value="<?php echo htmlspecialchars((string)$valori_formular['nume']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Inițiala tatălui</label>
                    <input type="text" name="initiala_tatalui" maxlength="3" value="<?php echo htmlspecialchars((string)$valori_formular['initiala_tatalui']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume <span class="text-red-600">*</span></label>
                    <input type="text" name="prenume" required value="<?php echo htmlspecialchars((string)$valori_formular['prenume']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">CNP <span class="text-red-600">*</span></label>
                    <input type="text" name="cnp" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" required value="<?php echo htmlspecialchars((string)$valori_formular['cnp']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon <span class="text-red-600">*</span></label>
                    <input type="text" name="telefon" required value="<?php echo htmlspecialchars((string)$valori_formular['telefon']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email <span class="text-red-600">*</span></label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars((string)$valori_formular['email']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <fieldset class="rounded-lg border border-slate-200 dark:border-gray-700 p-3">
                <legend class="px-1 text-sm font-medium text-slate-800 dark:text-gray-200">Adresă</legend>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Localitate <span class="text-red-600">*</span></label>
                        <input type="text" name="localitate" required value="<?php echo htmlspecialchars((string)$valori_formular['localitate']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Județ <span class="text-red-600">*</span></label>
                        <input type="text" name="judet" required value="<?php echo htmlspecialchars((string)$valori_formular['judet']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cod poștal</label>
                        <input type="text" name="cod_postal" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" value="<?php echo htmlspecialchars((string)$valori_formular['cod_postal']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Strada <span class="text-red-600">*</span></label>
                        <input type="text" name="strada" required value="<?php echo htmlspecialchars((string)$valori_formular['strada']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr <span class="text-red-600">*</span></label>
                        <input type="text" name="numar" required value="<?php echo htmlspecialchars((string)$valori_formular['numar']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Bloc</label>
                        <input type="text" name="bloc" maxlength="10" value="<?php echo htmlspecialchars((string)$valori_formular['bloc']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Scară</label>
                        <input type="text" name="scara" maxlength="10" value="<?php echo htmlspecialchars((string)$valori_formular['scara']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Etaj</label>
                        <input type="text" name="etaj" maxlength="10" value="<?php echo htmlspecialchars((string)$valori_formular['etaj']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Apartament</label>
                        <input type="text" name="apartament" maxlength="10" value="<?php echo htmlspecialchars((string)$valori_formular['apartament']); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </fieldset>

            <div>
                <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Semnătura <span class="text-red-600">*</span></label>
                <p class="text-xs text-slate-600 dark:text-gray-400 mb-2">Semnați în zona de mai jos (culoare albastru închis, fundal transparent).</p>
                <div class="rounded-lg border border-slate-300 dark:border-gray-600 bg-white dark:bg-gray-700 p-2">
                    <canvas id="manual-signature-pad" class="w-full rounded bg-white" style="height: 180px;" aria-label="Zonă semnătură"></canvas>
                </div>
                <div class="mt-2 flex gap-2">
                    <button type="button" id="manual-signature-clear" class="px-3 py-1.5 text-sm bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-200 rounded hover:bg-slate-200 dark:hover:bg-gray-600">Șterge semnătura</button>
                </div>
                <input type="hidden" name="signature_data" id="manual-signature-data" value="<?php echo htmlspecialchars((string)$valori_formular['signature_data']); ?>">
            </div>

            <div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-800 dark:text-gray-200">
                    <input type="checkbox" name="gdpr_acord" value="1" <?php echo !empty($valori_formular['gdpr_acord']) ? 'checked' : ''; ?> class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                    Acord GDPR <span class="text-red-600">*</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-1">
                <button type="button" id="btn-anuleaza-manual" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-700">
                    Anulează
                </button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-medium focus:ring-2 focus:ring-amber-500">
                    Salvează formularul
                </button>
            </div>
        </form>
    </div>
</dialog>

<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    var tab = <?php echo json_encode($tab); ?>;
    if (tab === 'setari' && typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#mesaj-confirmare-editor',
            height: 260,
            menubar: true,
            plugins: 'link lists table code',
            toolbar: 'undo redo | formatselect | bold italic underline | bullist numlist | link | removeformat | code',
            content_style: 'body{font-family:Inter,Arial,sans-serif;font-size:14px;}',
            setup: function (editor) {
                var sync = function () {
                    var hidden = document.getElementById('mesaj-confirmare-html');
                    if (hidden) hidden.value = editor.getContent();
                };
                editor.on('init change keyup undo redo SetContent', sync);
            }
        });
    }

    var dialog = document.getElementById('dialog-manual-f230');
    var openBtn = document.getElementById('btn-adauga-manual');
    var closeBtn = document.getElementById('btn-inchide-manual');
    var cancelBtn = document.getElementById('btn-anuleaza-manual');

    function closeDialog() {
        if (dialog && dialog.open) dialog.close();
    }

    if (openBtn && dialog) {
        openBtn.addEventListener('click', function () {
            dialog.showModal();
        });
    }
    if (closeBtn) closeBtn.addEventListener('click', closeDialog);
    if (cancelBtn) cancelBtn.addEventListener('click', closeDialog);
    if (dialog) {
        dialog.addEventListener('click', function (e) {
            var rect = dialog.getBoundingClientRect();
            var inside = rect.top <= e.clientY && e.clientY <= rect.top + rect.height
                && rect.left <= e.clientX && e.clientX <= rect.left + rect.width;
            if (!inside) closeDialog();
        });
    }
    <?php if (!empty($manual_modal_open)): ?>
    if (dialog) dialog.showModal();
    <?php endif; ?>

    var canvas = document.getElementById('manual-signature-pad');
    var hiddenInput = document.getElementById('manual-signature-data');
    var clearBtn = document.getElementById('manual-signature-clear');
    if (canvas && hiddenInput) {
        var ctx = canvas.getContext('2d');
        var drawing = false;
        var hasStroke = false;
        var color = '#0f2a63';
        var ratio = Math.max(window.devicePixelRatio || 1, 1);

        function resizeCanvas() {
            var cssWidth = canvas.clientWidth || 600;
            var cssHeight = 180;
            canvas.width = Math.floor(cssWidth * ratio);
            canvas.height = Math.floor(cssHeight * ratio);
            canvas.style.height = cssHeight + 'px';
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = 2.2;
            ctx.strokeStyle = color;
            if (hiddenInput.value) {
                var img = new Image();
                img.onload = function () {
                    ctx.drawImage(img, 0, 0, cssWidth, cssHeight);
                };
                img.src = hiddenInput.value;
            }
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function pointFromEvent(e) {
            var rect = canvas.getBoundingClientRect();
            var x, y;
            if (e.touches && e.touches.length) {
                x = e.touches[0].clientX - rect.left;
                y = e.touches[0].clientY - rect.top;
            } else {
                x = e.clientX - rect.left;
                y = e.clientY - rect.top;
            }
            return { x: x, y: y };
        }

        function startDraw(e) {
            drawing = true;
            hasStroke = true;
            var p = pointFromEvent(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }
        function moveDraw(e) {
            if (!drawing) return;
            var p = pointFromEvent(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }
        function stopDraw() {
            if (!drawing) return;
            drawing = false;
            ctx.closePath();
            if (hasStroke) {
                hiddenInput.value = canvas.toDataURL('image/png');
            }
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', moveDraw);
        window.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', moveDraw, { passive: false });
        window.addEventListener('touchend', stopDraw);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasStroke = false;
                hiddenInput.value = '';
            });
        }

        var manualForm = dialog ? dialog.querySelector('form') : null;
        if (manualForm) {
            manualForm.addEventListener('submit', function (e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    alert('Semnatura este obligatorie.');
                }
            });
        }
    }
})();
</script>
</body>
</html>
