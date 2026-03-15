<?php
/**
 * View: Contacte — Formular editare
 *
 * Variabile disponibile: $eroare, $tipuri, $contact, $id
 */
$c = $contact ?? [];
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Editează contact</h1>
        <a href="contacte.php" class="text-amber-600 dark:text-amber-400 hover:underline focus:ring-2 focus:ring-amber-500 rounded">← Înapoi</a>
    </header>
    <div class="p-6 overflow-y-auto flex-1 max-w-2xl">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
        <?php endif; ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6">
            <form method="post" action="contacte-edit.php?id=<?php echo (int)$id; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizeaza_contact" value="1">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                <?php include APP_ROOT . '/contacte-form-fields.php'; ?>
                <div class="mt-6 flex gap-3">
                    <a href="contacte.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700">Anulare</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg" aria-label="Salvează modificările contactului">Salvează</button>
                </div>
            </form>
        </div>
    </div>
</main>
<script>lucide.createIcons();</script>
</body>
</html>
