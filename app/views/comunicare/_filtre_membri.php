<!-- Filtre Membri -->
<fieldset class="border border-slate-200 dark:border-gray-700 rounded-lg p-4">
    <legend class="text-sm font-medium text-slate-700 dark:text-gray-300 px-2">Filtre Membri</legend>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
        <!-- Status -->
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Status Dosar</label>
            <select id="status" name="status"
                    class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                <option value="" <?php echo empty($_POST['status'] ?? '') ? 'selected' : ''; ?>>Activ (implicit)</option>
                <option value="Activ" <?php echo ($_POST['status'] ?? '') === 'Activ' ? 'selected' : ''; ?>>Activ</option>
                <option value="Suspendat" <?php echo ($_POST['status'] ?? '') === 'Suspendat' ? 'selected' : ''; ?>>Suspendat</option>
                <option value="Expirat" <?php echo ($_POST['status'] ?? '') === 'Expirat' ? 'selected' : ''; ?>>Expirat</option>
                <option value="Decedat" <?php echo ($_POST['status'] ?? '') === 'Decedat' ? 'selected' : ''; ?>>Decedat</option>
            </select>
        </div>

        <!-- Localitate -->
        <div>
            <label for="localitate" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Localitate</label>
            <select id="localitate" name="localitate"
                    class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                <option value="">-- Toate --</option>
                <?php foreach ($localitati as $loc): ?>
                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($_POST['localitate'] ?? '') === $loc ? 'selected' : ''; ?>><?php echo htmlspecialchars($loc); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Sex -->
        <div>
            <label for="sex" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Sex</label>
            <select id="sex" name="sex"
                    class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                <option value="">-- Toate --</option>
                <option value="M" <?php echo ($_POST['sex'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculin</option>
                <option value="F" <?php echo ($_POST['sex'] ?? '') === 'F' ? 'selected' : ''; ?>>Feminin</option>
            </select>
        </div>

        <!-- Mediu -->
        <div>
            <label for="mediu" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Mediu</label>
            <select id="mediu" name="mediu"
                    class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                <option value="">-- Toate --</option>
                <option value="Urban" <?php echo ($_POST['mediu'] ?? '') === 'Urban' ? 'selected' : ''; ?>>Urban</option>
                <option value="Rural" <?php echo ($_POST['mediu'] ?? '') === 'Rural' ? 'selected' : ''; ?>>Rural</option>
            </select>
        </div>

        <!-- Grad Handicap -->
        <div>
            <label for="hgrad" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Grad Handicap</label>
            <select id="hgrad" name="hgrad"
                    class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
                <option value="">-- Toate --</option>
                <?php foreach ($graduri as $grad): ?>
                    <option value="<?php echo htmlspecialchars($grad); ?>" <?php echo ($_POST['hgrad'] ?? '') === $grad ? 'selected' : ''; ?>><?php echo htmlspecialchars($grad); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Data Nastere De La -->
        <div>
            <label for="data_nastere_de_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Data Nastere De La</label>
            <input type="date" id="data_nastere_de_la" name="data_nastere_de_la"
                   value="<?php echo htmlspecialchars($_POST['data_nastere_de_la'] ?? ''); ?>"
                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
        </div>

        <!-- Data Nastere Pana La -->
        <div>
            <label for="data_nastere_pana_la" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">Data Nastere Pana La</label>
            <input type="date" id="data_nastere_pana_la" name="data_nastere_pana_la"
                   value="<?php echo htmlspecialchars($_POST['data_nastere_pana_la'] ?? ''); ?>"
                   class="w-full rounded-lg border-slate-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm p-2">
        </div>

        <!-- Cotizatie Neachitata -->
        <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="cotizatie_neachitata" value="1"
                       <?php echo !empty($_POST['cotizatie_neachitata']) ? 'checked' : ''; ?>
                       class="rounded border-slate-300 dark:border-gray-600 text-amber-600 focus:ring-amber-500 dark:bg-gray-700">
                <span class="text-sm font-medium text-slate-700 dark:text-gray-300">Cotizatie Neachitata</span>
            </label>
        </div>
    </div>
</fieldset>
