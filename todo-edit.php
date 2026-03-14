<?php
/**
 * Editare task - CRM ANR Bihor
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';
require_once 'includes/notificari_helper.php';

$eroare = '';
$succes = '';
$task = null;

$task_id = (int)($_GET['id'] ?? 0);
if ($task_id <= 0) {
    header('Location: todo.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

// Verifică dacă există coloana utilizator_id
$cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
$has_user_id = in_array('utilizator_id', $cols);

// Încarcă taskul
try {
    if ($has_user_id && $user_id) {
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE id = ? AND (utilizator_id IS NULL OR utilizator_id = ?)');
        $stmt->execute([$task_id, $user_id]);
    } else {
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE id = ?');
        $stmt->execute([$task_id]);
    }
    $task = $stmt->fetch();
    if (!$task) {
        header('Location: todo.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: todo.php');
    exit;
}

// Procesare actualizare task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_task'])) {
    csrf_require_valid();
    $nume = trim($_POST['nume'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $ora = trim($_POST['ora'] ?? '');
    $detalii = trim($_POST['detalii'] ?? '');
    $nivel_urgenta = in_array($_POST['nivel_urgenta'] ?? '', ['normal', 'important', 'reprogramat']) ? $_POST['nivel_urgenta'] : 'normal';

    if (empty($nume)) {
        $eroare = 'Numele taskului este obligatoriu.';
    } elseif (empty($data)) {
        $eroare = 'Data este obligatorie.';
    } else {
        $ora_val = trim($ora);
        if (empty($ora_val)) $ora_val = '09:00';
        if (strlen($ora_val) === 5) $ora_val .= ':00';
        $data_ora = $data . ' ' . $ora_val;
        try {
            if ($has_user_id && $user_id) {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND (utilizator_id IS NULL OR utilizator_id = ?)');
                $stmt->execute([$task_id, $user_id]);
            } else {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ?');
                $stmt->execute([$task_id]);
            }
            $old = $stmt->fetch();
            if ($old) {
                $stmt = $pdo->prepare('UPDATE taskuri SET nume = ?, data_ora = ?, detalii = ?, nivel_urgenta = ? WHERE id = ?');
                $stmt->execute([$nume, $data_ora, $detalii ?: null, $nivel_urgenta, $task_id]);
                log_activitate($pdo, "Sarcină actualizată: {$nume} (nivel: {$nivel_urgenta})");
                $succes = 'Taskul a fost actualizat cu succes.';
                // Reîncarcă taskul actualizat
                if ($has_user_id && $user_id) {
                    $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE id = ? AND (utilizator_id IS NULL OR utilizator_id = ?)');
                    $stmt->execute([$task_id, $user_id]);
                } else {
                    $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE id = ?');
                    $stmt->execute([$task_id]);
                }
                $task = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la actualizare: ' . $e->getMessage();
        }
    }
}

// Procesare trimitere notificare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trimite_notificare'])) {
    csrf_require_valid();
    notificari_ensure_tables($pdo);
    
    // Creează notificarea cu datele din task
    $titlu = 'Task: ' . htmlspecialchars($task['nume']);
    $continut = "Task: " . htmlspecialchars($task['nume']) . "\n\n";
    $continut .= "Data și ora: " . date(DATETIME_FORMAT, strtotime($task['data_ora'])) . "\n";
    if (!empty($task['detalii'])) {
        $continut .= "\nDetalii:\n" . htmlspecialchars($task['detalii']) . "\n";
    }
    $continut .= "\nNivel urgență: " . ucfirst($task['nivel_urgenta']);
    
    // Determină importanța notificării pe baza nivelului de urgență
    $importanta = 'Normal';
    if ($task['nivel_urgenta'] === 'important') {
        $importanta = 'Important';
    } elseif ($task['nivel_urgenta'] === 'reprogramat') {
        $importanta = 'Informativ';
    }
    
    $notif_id = notificari_adauga($pdo, [
        'titlu' => $titlu,
        'importanta' => $importanta,
        'continut' => $continut,
        'trimite_email' => 0, // Nu trimite email automat
    ], null, $user_id);
    
    if ($notif_id > 0) {
        log_activitate($pdo, "Notificare creată din task: {$task['nume']} (ID notificare: {$notif_id})");
        $succes = 'Notificarea a fost creată cu succes pentru toți utilizatorii.';
    } else {
        $eroare = 'Eroare la crearea notificării.';
    }
}

include 'header.php';
include 'sidebar.php';
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Editează task</h1>
        <a href="todo.php" class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700" aria-label="Înapoi la Taskuri">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-2" aria-hidden="true"></i>
            Înapoi la Taskuri
        </a>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 p-6 max-w-2xl">
            <form method="post" action="todo-edit.php?id=<?php echo (int)$task_id; ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="actualizeaza_task" value="1">
                
                <div>
                    <label for="nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="nume" name="nume" required value="<?php echo htmlspecialchars($task['nume']); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" id="data" name="data" required value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($task['data_ora']))); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="ora" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora</label>
                        <input type="time" id="ora" name="ora" value="<?php echo htmlspecialchars(date('H:i', strtotime($task['data_ora']))); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                </div>
                
                <div>
                    <label for="detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="detalii" name="detalii" rows="4"
                              class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($task['detalii'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label for="nivel_urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                    <select id="nivel_urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Selectează nivelul de urgență">
                        <option value="normal" <?php echo $task['nivel_urgenta'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="important" <?php echo $task['nivel_urgenta'] === 'important' ? 'selected' : ''; ?>>Important</option>
                        <option value="reprogramat" <?php echo $task['nivel_urgenta'] === 'reprogramat' ? 'selected' : ''; ?>>Reprogramat</option>
                    </select>
                </div>
                
                <div class="flex gap-3 justify-end pt-4 border-t border-slate-200 dark:border-gray-700">
                    <a href="todo.php" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulează">Anulează</a>
                    <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează modificările">Salvează</button>
                </div>
            </form>
            
            <!-- Buton pentru trimitere notificare -->
            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-gray-700">
                <form method="post" action="todo-edit.php?id=<?php echo (int)$task_id; ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="trimite_notificare" value="1">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-violet-500 transition"
                            aria-label="Trimite notificare pentru toți utilizatorii"
                            onclick="return confirm('Sunteți sigur că doriți să creați o notificare pentru toți utilizatorii platformei cu datele acestui task?');">
                        <i data-lucide="bell" class="w-4 h-4 mr-2" aria-hidden="true"></i>
                        Trimite notificare
                    </button>
                </form>
                <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">Creează o notificare pentru toți utilizatorii platformei cu datele acestui task.</p>
            </div>
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
