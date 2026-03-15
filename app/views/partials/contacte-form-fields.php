<?php
/**
 * Câmpuri formular contact (partajat între adaugă și edit)
 * Variabile așteptate: $tipuri, $contact (opțional, pentru edit)
 */
$c = $contact ?? [];
$tipuri = $tipuri ?? [];
?>
<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="contact-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume <span class="text-red-600">*</span></label>
            <input type="text" id="contact-nume" name="nume" required value="<?php echo htmlspecialchars($c['nume'] ?? $_POST['nume'] ?? ''); ?>"
                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
        <div>
            <label for="contact-prenume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume</label>
            <input type="text" id="contact-prenume" name="prenume" value="<?php echo htmlspecialchars($c['prenume'] ?? $_POST['prenume'] ?? ''); ?>"
                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
    </div>
    <div>
        <label for="contact-companie" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Companie</label>
        <input type="text" id="contact-companie" name="companie" value="<?php echo htmlspecialchars($c['companie'] ?? $_POST['companie'] ?? ''); ?>"
               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
    </div>
    <div>
        <label for="contact-tip" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip contact</label>
        <select id="contact-tip" name="tip_contact" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Tip contact">
            <?php 
            $tip_curent = $c['tip_contact'] ?? $_POST['tip_contact'] ?? 'alte contacte';
            foreach ($tipuri as $k => $v): ?>
            <option value="<?php echo htmlspecialchars($k); ?>" <?php echo $tip_curent === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($v); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="contact-telefon" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr telefon mobil</label>
            <input type="tel" id="contact-telefon" name="telefon" value="<?php echo htmlspecialchars($c['telefon'] ?? $_POST['telefon'] ?? ''); ?>"
                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" placeholder="07xx xxx xxx">
        </div>
        <div>
            <label for="contact-telefon-personal" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr telefon mobil personal</label>
            <input type="tel" id="contact-telefon-personal" name="telefon_personal" value="<?php echo htmlspecialchars($c['telefon_personal'] ?? $_POST['telefon_personal'] ?? ''); ?>"
                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="contact-email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email</label>
            <input type="email" id="contact-email" name="email" value="<?php echo htmlspecialchars($c['email'] ?? $_POST['email'] ?? ''); ?>"
                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
        <div>
            <label for="contact-email-personal" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email personal</label>
            <input type="email" id="contact-email-personal" name="email_personal" value="<?php echo htmlspecialchars($c['email_personal'] ?? $_POST['email_personal'] ?? ''); ?>"
                   class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
        </div>
    </div>
    <div>
        <label for="contact-website" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Website</label>
        <input type="url" id="contact-website" name="website" value="<?php echo htmlspecialchars($c['website'] ?? $_POST['website'] ?? ''); ?>"
               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" placeholder="https://">
    </div>
    <div>
        <label for="contact-data-nasterii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data nașterii</label>
        <input type="date" id="contact-data-nasterii" name="data_nasterii" value="<?php echo htmlspecialchars($c['data_nasterii'] ?? $_POST['data_nasterii'] ?? ''); ?>"
               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
    </div>
    <div>
        <label for="contact-notite" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Notițe contact</label>
        <textarea id="contact-notite" name="notite" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($c['notite'] ?? $_POST['notite'] ?? ''); ?></textarea>
    </div>
    <div>
        <label for="contact-referinta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Referință / Contact comun</label>
        <input type="text" id="contact-referinta" name="referinta_contact" value="<?php echo htmlspecialchars($c['referinta_contact'] ?? $_POST['referinta_contact'] ?? ''); ?>"
               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
    </div>
</div>
