<?php
/**
 * View: Notificari — Lista + Formular adaugare
 *
 * Variabile disponibile (setate de controller):
 *   $lista, $eroare, $succes, $istoric, $ordine, $user_id
 */
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Notificări</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="notificari.php<?php echo $istoric ? '' : '?istoric=1'; ?>"
               class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 <?php echo $istoric ? 'bg-slate-100 dark:bg-gray-700' : ''; ?>"
               aria-label="<?php echo $istoric ? 'Ascunde istoric' : 'Afișează istoric notificări'; ?>">
                <i data-lucide="history" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                <?php echo $istoric ? 'Ascunde istoric' : 'Istoric notificări'; ?>
            </a>
        </div>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($succes) || isset($_GET['succes'])): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes ?: 'Notificarea a fost salvată.'); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_GET['necitit'])): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            Notificarea a fost marcată ca necitită.
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Coloana stânga - Lista notificări -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden" aria-labelledby="lista-notificari-heading">
                <h2 id="lista-notificari-heading" class="text-lg font-semibold text-slate-900 dark:text-white p-6 pb-4">Lista notificări</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Notificări">
                        <thead class="bg-slate-100 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">ID</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Titlu</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Importanță</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Status</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <?php if (empty($lista)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-600 dark:text-gray-400"><?php echo $istoric ? 'Niciun element în istoric.' : 'Niciună notificare.'; ?></td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($lista as $n): ?>
                            <tr class="<?php echo $n['status'] === 'nou' ? 'bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/40 border-l-4 border-amber-500 dark:border-amber-400' : 'hover:bg-slate-50 dark:hover:bg-gray-700'; ?>" <?php echo $n['status'] === 'nou' ? 'aria-label="Notificare necitită"' : ''; ?>>
                                <td class="px-4 py-3 text-sm <?php echo $n['status'] === 'nou' ? 'text-slate-800 dark:text-gray-100 font-medium' : 'text-slate-600 dark:text-gray-400'; ?>"><?php echo (int)$n['id']; ?></td>
                                <td class="px-4 py-3 text-left">
                                    <a href="notificare-view.php?id=<?php echo (int)$n['id']; ?>" class="<?php echo $n['status'] === 'nou' ? 'text-amber-700 dark:text-amber-300 hover:text-amber-800 dark:hover:text-amber-200 font-bold' : 'text-amber-600 dark:text-amber-400 hover:underline font-medium'; ?> hover:underline"><?php echo htmlspecialchars($n['titlu']); ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm <?php echo $n['status'] === 'nou' ? 'text-slate-800 dark:text-gray-100 font-medium' : 'text-slate-700 dark:text-gray-300'; ?>"><?php echo htmlspecialchars($n['importanta']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php
                                        echo $n['status'] === 'nou' ? 'bg-amber-200 dark:bg-amber-800/50 text-amber-900 dark:text-amber-100 font-semibold' : ($n['status'] === 'citit' ? 'bg-slate-100 dark:bg-gray-600 text-slate-700 dark:text-gray-300' : 'bg-slate-200 dark:bg-gray-600 text-slate-600 dark:text-gray-400');
                                    ?>"><?php echo $n['status'] === 'nou' ? 'Nou' : ($n['status'] === 'citit' ? 'Citit' : 'Arhivat'); ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm <?php echo $n['status'] === 'nou' ? 'text-slate-800 dark:text-gray-100 font-medium' : 'text-slate-600 dark:text-gray-400'; ?>"><?php echo date(DATETIME_FORMAT, strtotime($n['created_at'])); ?></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($n['status'] !== 'arhivat' && $n['status'] !== 'nou'): ?>
                                        <form method="post" action="notificari.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="marcheaza_necitit" value="1">
                                            <input type="hidden" name="notif_id" value="<?php echo (int)$n['id']; ?>">
                                            <button type="submit" class="text-slate-600 dark:text-gray-400 hover:text-amber-600 text-sm" aria-label="Marchează notificarea ca necitită">Marchează ca necitit</button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($n['status'] !== 'arhivat'): ?>
                                        <form method="post" action="notificare-view.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="arhiveaza" value="1">
                                            <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                                            <input type="hidden" name="redirect" value="notificari.php<?php echo $istoric ? '?istoric=1' : ''; ?>">
                                            <button type="submit" class="text-slate-600 dark:text-gray-400 hover:text-amber-600 text-sm" aria-label="Arhivează notificarea">Arhivează</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Coloana dreapta - Formular adăugare notificare -->
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6" aria-labelledby="form-notificare-heading">
                <h2 id="form-notificare-heading" class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i data-lucide="bell-plus" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                    Adaugă notificare
                </h2>
                <form method="post" action="notificari.php" enctype="multipart/form-data" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="adauga_notificare" value="1">
                    <div>
                        <label for="titlu" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Titlu notificare <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="text" id="titlu" name="titlu" required
                               value="<?php echo htmlspecialchars($_POST['titlu'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               aria-required="true">
                    </div>
                    <div>
                        <label for="importanta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Importanță</label>
                        <select id="importanta" name="importanta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                            <?php foreach (NOTIFICARI_IMPORTANTE as $k => $v): ?>
                            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo (isset($_POST['importanta']) && $_POST['importanta'] === $k) ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="continut" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Conținutul notificării <span class="text-red-600" aria-hidden="true">*</span></label>
                        <textarea id="continut" name="continut" rows="5" required
                                  class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                  aria-required="true"><?php echo htmlspecialchars($_POST['continut'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="link_extern" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Link extern</label>
                        <textarea id="link_extern" name="link_extern" rows="2"
                                  class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                                  placeholder="https://..."><?php echo htmlspecialchars($_POST['link_extern'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="atasament" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Atașare document (imagine, document, max. <?php echo NOTIFICARI_ATAŞAMENT_MAX_MB; ?> MB)</label>
                        <input type="file" id="atasament" name="atasament"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"
                               aria-describedby="atasament-desc">
                        <p id="atasament-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Opțional. Maxim <?php echo NOTIFICARI_ATAŞAMENT_MAX_MB; ?> MB.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="trimite_email" name="trimite_email" value="1" <?php echo isset($_POST['trimite_email']) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
                               aria-describedby="trimite-email-desc">
                        <label for="trimite_email" class="text-sm font-medium text-slate-800 dark:text-gray-200">Notifică utilizatorii prin email</label>
                    </div>
                    <p id="trimite-email-desc" class="text-xs text-slate-600 dark:text-gray-400">Toți utilizatorii platformei vor primi notificarea pe email (subiect: Notificare ANR Bihor: [titlu], conținut + link + atașament).</p>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500 focus:ring-offset-2" aria-label="Salvează notificarea">
                        <i data-lucide="save" class="w-4 h-4 inline mr-2" aria-hidden="true"></i>
                        Salvează notificarea
                    </button>
                </form>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
</body>
</html>
