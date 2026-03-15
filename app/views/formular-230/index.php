<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Formular 230</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-gray-400">
                Evidenta persoane care redirectioneaza 3.5% din impozit catre asociatie.
            </p>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
                <?php echo htmlspecialchars($eroare); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
            <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status">
                <?php echo htmlspecialchars($succes); ?>
            </div>
        <?php endif; ?>

        <div class="mb-6 flex flex-wrap justify-between items-center gap-3">
            <form method="post" action="/formular-230" class="flex items-center gap-2">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="toggle_hide_bifat" value="1">
                <label class="flex items-center text-sm text-slate-700 dark:text-gray-200">
                    <input type="checkbox" name="hide_bifat" value="1" <?php echo $hide_bifat ? 'checked' : ''; ?>
                           class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2">
                        Ascunde persoanele bifate pentru anul curent<?php echo $an_curent_form ? ' (' . (int)$an_curent_form . ')' : ''; ?>
                    </span>
                </label>
                <button type="submit"
                        class="px-3 py-1.5 bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-200 rounded-lg text-xs font-medium hover:bg-slate-200 dark:hover:bg-gray-600">
                    Aplica
                </button>
            </form>

            <button type="button"
                    onclick="document.getElementById('dialog-f230').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                <i data-lucide="user-plus" class="mr-2 w-4 h-4" aria-hidden="true"></i>
                Adauga persoana
            </button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table">
                    <thead class="bg-slate-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Nume si Prenume</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Telefon</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Email</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Varsta</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 dark:text-gray-200">Localitatea</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-700 dark:text-gray-200">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <?php if (empty($persoane)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-sm text-slate-500 dark:text-gray-400">
                                    Nu exista inregistrari in acest moment.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($persoane as $p): ?>
                                <?php $varsta = f230_calc_varsta_din_cnp($p['cnp'] ?? ''); ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars(trim(($p['nume'] ?? '') . ' ' . ($p['prenume'] ?? ''))); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($p['telefon'] ?? ''); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo htmlspecialchars($p['email'] ?? ''); ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php echo $varsta !== null ? $varsta . ' ani' : '—'; ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-gray-100">
                                        <?php
                                        $loc = trim(($p['localitatea'] ?? '') . ($p['judet'] ? ', ' . $p['judet'] : ''));
                                        echo htmlspecialchars($loc ?: '—');
                                        ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right space-x-2">
                                        <button type="button"
                                                class="inline-flex items-center px-2 py-1 text-xs bg-slate-100 dark:bg-gray-700 text-slate-800 dark:text-gray-100 rounded hover:bg-slate-200 dark:hover:bg-gray-600"
                                                onclick="window.location.href='/formular-230?edit=<?php echo (int)$p['id']; ?>'">
                                            Editeaza
                                        </button>
                                        <?php if (!empty($p['telefon'])): ?>
                                            <a href="<?php echo htmlspecialchars(contacte_whatsapp_url($p['telefon'])); ?>"
                                               target="_blank"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                                                WhatsApp
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($p['email'])): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>"
                                               class="inline-flex items-center px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                Email
                                            </a>
                                        <?php endif; ?>
                                        <form method="post" action="/formular-230" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="persoana_id" value="<?php echo (int)$p['id']; ?>">
                                            <button type="submit" name="arhiveaza_persoana_230" value="1"
                                                    class="inline-flex items-center px-2 py-1 text-xs bg-slate-200 dark:bg-gray-700 text-slate-700 dark:text-gray-200 rounded hover:bg-slate-300 dark:hover:bg-gray-600">
                                                Arhiveaza
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="px-4 py-3 border-t border-slate-200 dark:border-gray-700 flex justify-between items-center text-sm text-slate-600 dark:text-gray-300">
                    <span>Pagina <?php echo $page; ?> din <?php echo $total_pages; ?></span>
                    <div class="space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="/formular-230?page=<?php echo $page - 1; ?><?php echo $hide_bifat ? '&hide_bifat=1' : ''; ?>"
                               class="px-3 py-1 border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-700">Inapoi</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="/formular-230?page=<?php echo $page + 1; ?><?php echo $hide_bifat ? '&hide_bifat=1' : ''; ?>"
                               class="px-3 py-1 border border-slate-300 dark:border-gray-600 rounded hover:bg-slate-50 dark:hover:bg-gray-700">Inainte</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<dialog id="dialog-f230" class="p-0 rounded-lg shadow-xl max-w-3xl w-[calc(100%-2rem)] mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adauga persoana – Formular 230</h2>
        <form method="post" action="/formular-230" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_persoana_230" value="1">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume</label>
                    <input type="text" name="nume" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Initiala tatalui</label>
                    <input type="text" name="initiala_tatalui" maxlength="5" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume</label>
                    <input type="text" name="prenume" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">CNP</label>
                    <input type="text" name="cnp" maxlength="13" required class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon</label>
                    <input type="text" name="telefon" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <fieldset class="border border-slate-200 dark:border-gray-700 rounded-lg p-3">
                <legend class="px-1 text-sm font-medium text-slate-800 dark:text-gray-200">Adresa</legend>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Strada</label>
                        <input type="text" name="strada" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr.</label>
                        <input type="text" name="nr" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Bl.</label>
                        <input type="text" name="bl" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Sc.</label>
                        <input type="text" name="sc" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Et.</label>
                        <input type="text" name="et" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ap.</label>
                        <input type="text" name="ap" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Localitatea</label>
                        <input type="text" name="localitatea" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Judet</label>
                        <input type="text" name="judet" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cod postal</label>
                        <input type="text" name="cod_postal" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </fieldset>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <option value="Activ">Activ</option>
                        <option value="Inactiv">Inactiv</option>
                    </select>
                </div>
                <div>
                    <span class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Canal formular</span>
                    <div class="space-y-1 text-sm text-slate-700 dark:text-gray-200">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_tiparit" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Tiparit
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_online" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Formular online
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_campanie" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Campanie
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="canal_altele" value="1" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            Altele
                        </label>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ani in care a trimis formulare</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($ani as $an): ?>
                        <label class="inline-flex items-center gap-1 text-sm text-slate-700 dark:text-gray-200">
                            <input type="checkbox" name="ani_formular[]" value="<?php echo (int)$an; ?>"
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            <?php echo (int)$an; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($an_curent_form): ?>
                <div>
                    <label class="flex items-center gap-2 text-sm text-slate-800 dark:text-gray-200">
                        <input type="checkbox" name="bifat_an_recent" value="1"
                               class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                        Formular depus pentru anul curent (<?php echo (int)$an_curent_form; ?>)
                    </label>
                </div>
            <?php endif; ?>

            <div class="mt-4 flex justify-end gap-3">
                <button type="button"
                        onclick="document.getElementById('dialog-f230').close()"
                        class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">
                    Anuleaza
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    Salveaza
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>lucide.createIcons();</script>
</body>
</html>
