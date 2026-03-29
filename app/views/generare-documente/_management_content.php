<?php
$doc_success_code = $_GET['succes_doc_tpl'] ?? ($_GET['succes'] ?? null);
$doc_error_message = $eroare_documente ?? $eroare ?? '';
?>

<?php if ($doc_success_code !== null && $doc_success_code !== ''): ?>
<div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-lg" role="status">
    <?php
    if ((string)$doc_success_code === '2') echo 'Template actualizat cu succes.';
    elseif ((string)$doc_success_code === '3') echo 'Template sters cu succes.';
    elseif ((string)$doc_success_code === '4') echo 'Maparile manuale PDF au fost salvate cu succes.';
    else echo 'Template incarcat cu succes.';
    ?>
</div>
<?php endif; ?>

<?php if (!empty($doc_error_message)): ?>
<div class="p-4 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded-lg" role="alert">
    <?php echo htmlspecialchars($doc_error_message); ?>
</div>
<?php endif; ?>

<!-- Upload template -->
<section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="upload-heading">
    <h2 id="upload-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
        <i data-lucide="upload" class="inline w-5 h-5 mr-2" aria-hidden="true"></i>
        Incarcare template
    </h2>
    <form method="post" enctype="multipart/form-data" class="flex flex-wrap gap-4 items-end">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="upload_template" value="1">
        <div>
            <label for="nume_afisare" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Nume afisat</label>
            <input type="text" id="nume_afisare" name="nume_afisare" required
                   placeholder="Ex: Cerere pentru loc de parcare"
                   class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg w-64 text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
        <div>
            <label for="fisier_template" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Fisier .docx / .pdf</label>
            <input type="file" id="fisier_template" name="fisier_template" accept=".docx,.pdf" required
                   class="px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium" aria-label="Incarca template-ul selectat">
            Incarca
        </button>
    </form>
</section>

<!-- Tabel templateuri -->
<section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden" aria-labelledby="tabel-heading">
    <h2 id="tabel-heading" class="text-lg font-semibold text-slate-900 dark:text-white p-4">Templateuri incarcate</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table">
            <thead class="bg-slate-100 dark:bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Nume</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Fisier</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Tip</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Activ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Actiuni</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                <?php if (empty($templates)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-gray-400">Nu exista templateuri.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td class="px-4 py-3 text-sm text-slate-900 dark:text-white"><?php echo htmlspecialchars($t['nume_afisare']); ?></td>
                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($t['nume_fisier']); ?></td>
                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300"><?php echo strtoupper(pathinfo((string)$t['nume_fisier'], PATHINFO_EXTENSION)); ?></td>
                    <td class="px-4 py-3">
                        <form method="post" class="inline">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="actualizeaza_template" value="1">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <input type="hidden" name="nume_afisare" value="<?php echo htmlspecialchars($t['nume_afisare']); ?>">
                            <label class="inline-flex items-center">
                                <input type="hidden" name="activ" value="0">
                                <input type="checkbox" name="activ" value="1" <?php echo $t['activ'] ? 'checked' : ''; ?>
                                       onchange="this.form.submit()"
                                       aria-label="Template activ">
                            </label>
                        </form>
                    </td>
                    <td class="px-4 py-3 flex flex-wrap gap-2 items-center">
                        <button type="button" onclick="document.getElementById('edit-<?php echo $t['id']; ?>').showModal()"
                                class="px-3 py-1.5 text-sm bg-amber-100 dark:bg-amber-800/70 text-amber-900 dark:text-amber-100 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-700"
                                aria-label="Editeaza template: <?php echo htmlspecialchars($t['nume_afisare']); ?>">
                            <i data-lucide="edit" class="w-4 h-4 inline" aria-hidden="true"></i> Editeaza
                        </button>
                        <form method="post" class="inline" onsubmit="return confirm('Stergeti acest template? Fisierul va fi sters de pe server.');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="sterge_template" value="1">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="px-3 py-1.5 text-sm bg-red-100 dark:bg-red-800/70 text-red-900 dark:text-red-100 rounded-lg hover:bg-red-200 dark:hover:bg-red-700"
                                    aria-label="Sterge template: <?php echo htmlspecialchars($t['nume_afisare']); ?>">
                                <i data-lucide="trash-2" class="w-4 h-4 inline" aria-hidden="true"></i> Sterge documentul
                            </button>
                        </form>
                        <dialog id="edit-<?php echo $t['id']; ?>" class="rounded-lg shadow-xl p-0 max-w-md w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto">
                            <form method="post" class="p-6">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="actualizeaza_template" value="1">
                                <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                <h3 class="text-lg font-semibold mb-4">Editeaza template</h3>
                                <label class="block text-sm font-medium mb-2">Nume afisat</label>
                                <input type="text" name="nume_afisare" value="<?php echo htmlspecialchars($t['nume_afisare']); ?>" required
                                       class="w-full px-3 py-2 border rounded-lg mb-4 dark:bg-gray-700 dark:text-white">
                                <label class="flex items-center gap-2 mb-4">
                                    <input type="hidden" name="activ" value="0">
                                    <input type="checkbox" name="activ" value="1" <?php echo $t['activ'] ? 'checked' : ''; ?>>
                                    <span>Activ (apare in lista)</span>
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 border rounded-lg" aria-label="Anuleaza editare template">Anulare</button>
                                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg" aria-label="Salveaza modificarile template">Salveaza</button>
                                </div>
                            </form>
                        </dialog>
                        <?php if (strtolower((string)pathinfo((string)$t['nume_fisier'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                        <button type="button" onclick="document.getElementById('map-<?php echo $t['id']; ?>').showModal()"
                                class="px-3 py-1.5 text-sm bg-indigo-100 dark:bg-indigo-800/70 text-indigo-900 dark:text-indigo-100 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-700"
                                aria-label="Configureaza mapari PDF pentru template: <?php echo htmlspecialchars($t['nume_afisare']); ?>">
                            <i data-lucide="map-pinned" class="w-4 h-4 inline" aria-hidden="true"></i> Mapari PDF
                        </button>
                        <dialog id="map-<?php echo $t['id']; ?>" class="rounded-lg shadow-xl p-0 max-w-2xl w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto">
                            <form method="post" class="p-6">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="salveaza_mapari_pdf" value="1">
                                <input type="hidden" name="template_id_map" value="<?php echo (int)$t['id']; ?>">
                                <h3 class="text-lg font-semibold mb-3">Fallback coordonat PDF: <?php echo htmlspecialchars($t['nume_afisare']); ?></h3>
                                <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">Format linie: <code>[tag]|pagina|x_mm|y_mm|font_pt</code></p>
                                <p class="text-sm text-slate-600 dark:text-gray-400 mb-3">Exemplu: <code>[nume]|1|35|78|11</code></p>
                                <textarea name="mapari_pdf" rows="10"
                                          class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg font-mono text-sm dark:bg-gray-700 dark:text-white"
                                          placeholder="[nume]|1|35|78|11&#10;[prenume]|1|70|78|11"><?php echo htmlspecialchars((string)($t['mapari_pdf'] ?? '')); ?></textarea>
                                <div class="flex gap-2 mt-4">
                                    <button type="button" onclick="this.closest('dialog').close()" class="px-4 py-2 border rounded-lg" aria-label="Anuleaza mapare PDF">Anulare</button>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg" aria-label="Salveaza mapari PDF">Salveaza mapari</button>
                                </div>
                            </form>
                        </dialog>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Taguri disponibile -->
<section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="taguri-heading">
    <h2 id="taguri-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
        <i data-lucide="tag" class="inline w-5 h-5 mr-2" aria-hidden="true"></i>
        Taguri disponibile
    </h2>
    <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">
        Templateurile se stocheaza in folderul <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">uploads/documente_template</code>. Sunt acceptate fisiere <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">.docx</code> si <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">.pdf</code>. Folositi tagurile sub forma <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[nume_tag]</code>. Ex: <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[nume]</code>, <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[prenume]</code>, <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[datagenerare]</code>.
    </p>
    <p class="text-sm text-slate-600 dark:text-gray-400 mb-2">
        Tagurile fara date in profilul membrului si <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[datagenerare]</code> (daca nu este bifata optiunea la generare) vor aparea ca spatiu in documentul generat; nu se afiseaza textul <code class="bg-slate-100 dark:bg-gray-700 px-1 rounded">[tag]</code>.
    </p>
    <p class="text-sm text-slate-600 dark:text-gray-400 mb-4">
        Pentru template-urile PDF: sistemul incearca detectia automata a tagurilor in streamul PDF. Pentru layout-uri complexe, folositi butonul <strong>Mapari PDF</strong> si configurati coordonate manuale per tag.
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-96 overflow-y-auto">
        <?php foreach ($taguri as $tag): ?>
        <div class="flex items-center gap-2 text-sm py-1">
            <code class="bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded font-mono text-xs">[<?php echo htmlspecialchars($tag['tag']); ?>]</code>
            <span class="text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($tag['desc']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>
