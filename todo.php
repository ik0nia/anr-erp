<?php
/**
 * Pagină Taskuri - CRM ANR
 * Sarcini curente și istoric finalizate
 */
require_once __DIR__ . '/config.php';
require_once 'includes/log_helper.php';
require_once 'includes/task_helper.php';

$eroare = '';
$succes = '';
$eroare_bd = '';
$taskuri_active = [];
$taskuri_istoric = [];

// Procesare adăugare task (înainte de output pentru redirecționare)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_task'])) {
    csrf_require_valid();
    $nume = trim($_POST['nume'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $ora = trim($_POST['ora'] ?? '');
    $detalii = trim($_POST['detalii'] ?? '');
    $nivel_urgenta = in_array($_POST['nivel_urgenta'] ?? '', ['normal', 'important', 'reprogramat']) ? $_POST['nivel_urgenta'] : 'normal';

    if (empty($nume)) {
        $eroare = 'Numele taskului este obligatoriu.';
    } elseif (empty($data) || empty($ora)) {
        $eroare = 'Data și ora sunt obligatorii.';
    } else {
        $data_ora = $data . ' ' . $ora . ':00';
        try {
            task_create($pdo, [
                'nume' => $nume,
                'data_ora' => $data_ora,
                'detalii' => $detalii ?: null,
                'nivel_urgenta' => $nivel_urgenta,
                'utilizator_id' => $_SESSION['user_id'] ?? null,
            ]);
            log_activitate($pdo, "Sarcină creată: {$nume} (nivel: {$nivel_urgenta})");
            $succes = 'Taskul a fost adăugat.';
            header('Location: todo.php?succes=1');
            exit;
        } catch (PDOException $e) {
            $eroare = 'Eroare la salvare: ' . $e->getMessage();
        }
    }
}

// Procesare finalizare task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizeaza_task'])) {
    csrf_require_valid();
    $id = (int) ($_POST['task_id'] ?? 0);
    if ($id > 0) {
        try {
            // Verifică dacă există coloana utilizator_id și filtrează pe utilizator
            $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
            $has_user_id = in_array('utilizator_id', $cols);
            $user_id = $_SESSION['user_id'] ?? null;
            
            if ($has_user_id && $user_id) {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND finalizat = 0 AND (utilizator_id IS NULL OR utilizator_id = ?)');
                $stmt->execute([$id, $user_id]);
            } else {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND finalizat = 0');
                $stmt->execute([$id]);
            }
            $task = $stmt->fetch();
            if ($task) {
                // IMPORTANT: Finalizarea unui task NU afectează interacțiunile din registru_interactiuni_v2
                // Taskurile sunt independente - doar se marchează ca finalizate în tabelul taskuri
                // Interacțiunile rămân permanent înregistrate în registru_interactiuni_v2
                $stmt = $pdo->prepare('UPDATE taskuri SET finalizat = 1, data_finalizare = NOW() WHERE id = ?');
                $stmt->execute([$id]);
                log_activitate($pdo, "Sarcină finalizată: {$task['nume']}");
                $succes = 'Taskul a fost marcat ca finalizat.';
                header('Location: todo.php?succes=2');
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la finalizare.';
        }
    }
}

// Procesare actualizare task (editare din modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizeaza_task'])) {
    csrf_require_valid();
    $id = (int) ($_POST['task_id'] ?? 0);
    $nume = trim($_POST['nume'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $ora = trim($_POST['ora'] ?? '');
    $detalii = trim($_POST['detalii'] ?? '');
    $nivel_urgenta = in_array($_POST['nivel_urgenta'] ?? '', ['normal', 'important', 'reprogramat']) ? $_POST['nivel_urgenta'] : 'normal';

    if ($id <= 0 || empty($nume)) {
        $eroare = 'ID invalid sau nume gol.';
    } elseif (empty($data)) {
        $eroare = 'Data este obligatorie.';
    } else {
        $ora_val = trim($ora);
        if (empty($ora_val)) $ora_val = '09:00';
        if (strlen($ora_val) === 5) $ora_val .= ':00';
        $data_ora = $data . ' ' . $ora_val;
        try {
            // Verifică dacă există coloana utilizator_id și filtrează pe utilizator
            $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
            $has_user_id = in_array('utilizator_id', $cols);
            $user_id = $_SESSION['user_id'] ?? null;
            
            if ($has_user_id && $user_id) {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND (utilizator_id IS NULL OR utilizator_id = ?)');
                $stmt->execute([$id, $user_id]);
            } else {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ?');
                $stmt->execute([$id]);
            }
            $old = $stmt->fetch();
            if ($old) {
                $stmt = $pdo->prepare('UPDATE taskuri SET nume = ?, data_ora = ?, detalii = ?, nivel_urgenta = ? WHERE id = ?');
                $stmt->execute([$nume, $data_ora, $detalii ?: null, $nivel_urgenta, $id]);
                log_activitate($pdo, "Sarcină actualizată: {$nume} (nivel: {$nivel_urgenta})");
                $redirect = $_POST['redirect_after'] ?? 'todo.php';
                header('Location: ' . (strpos($redirect, 'index') !== false ? 'index.php' : 'todo.php') . '?succes=4');
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la actualizare: ' . $e->getMessage();
        }
    }
}

// Procesare reactivare task (din istoric)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reactivare_task'])) {
    csrf_require_valid();
    $id = (int) ($_POST['task_id'] ?? 0);
    if ($id > 0) {
        try {
            // Verifică dacă există coloana utilizator_id și filtrează pe utilizator
            $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
            $has_user_id = in_array('utilizator_id', $cols);
            $user_id = $_SESSION['user_id'] ?? null;
            
            if ($has_user_id && $user_id) {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND finalizat = 1 AND (utilizator_id IS NULL OR utilizator_id = ?)');
                $stmt->execute([$id, $user_id]);
            } else {
                $stmt = $pdo->prepare('SELECT nume FROM taskuri WHERE id = ? AND finalizat = 1');
                $stmt->execute([$id]);
            }
            $task = $stmt->fetch();
            if ($task) {
                $stmt = $pdo->prepare('UPDATE taskuri SET finalizat = 0, data_finalizare = NULL WHERE id = ?');
                $stmt->execute([$id]);
                log_activitate($pdo, "Sarcină reactivată: {$task['nume']}");
                $succes = 'Taskul a fost reactivat și trecut la sarcini active.';
                header('Location: todo.php?succes=3');
                exit;
            }
        } catch (PDOException $e) {
            $eroare = 'Eroare la reactivare.';
        }
    }
}

include 'header.php';
include 'sidebar.php';

if (isset($_GET['succes'])) {
    $msg = ['1' => 'Taskul a fost adăugat.', '2' => 'Taskul a fost marcat ca finalizat.', '3' => 'Taskul a fost reactivat și trecut la sarcini active.', '4' => 'Taskul a fost actualizat cu succes.'];
    $succes = $msg[$_GET['succes']] ?? 'Operațiune reușită.';
}

try {
    // Verifică dacă există coloana utilizator_id
    $cols = $pdo->query("SHOW COLUMNS FROM taskuri")->fetchAll(PDO::FETCH_COLUMN);
    $has_user_id = in_array('utilizator_id', $cols);
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($has_user_id && $user_id) {
        // Filtrează taskurile pe utilizator
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 AND (utilizator_id IS NULL OR utilizator_id = ?) ORDER BY data_ora ASC');
        $stmt->execute([$user_id]);
        $taskuri_active = $stmt->fetchAll();
        $stmt = $pdo->prepare('SELECT id, nume, data_ora, detalii, nivel_urgenta, data_finalizare FROM taskuri WHERE finalizat = 1 AND (utilizator_id IS NULL OR utilizator_id = ?) ORDER BY data_finalizare DESC');
        $stmt->execute([$user_id]);
        $taskuri_istoric = $stmt->fetchAll();
    } else {
        // Comportament vechi dacă nu există coloana utilizator_id
        $stmt = $pdo->query('SELECT id, nume, data_ora, detalii, nivel_urgenta FROM taskuri WHERE finalizat = 0 ORDER BY data_ora ASC');
        $taskuri_active = $stmt->fetchAll();
        $stmt = $pdo->query('SELECT id, nume, data_ora, detalii, nivel_urgenta, data_finalizare FROM taskuri WHERE finalizat = 1 ORDER BY data_finalizare DESC');
        $taskuri_istoric = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $eroare_bd = 'Tabelul taskuri nu există. Rulați schema.sql sau schema_taskuri.sql în baza de date ' . (defined('DB_NAME') ? DB_NAME : '') . '.';
}
?>

<main id="main-content" class="flex-1 flex flex-col overflow-hidden" role="main">
    <header class="bg-white dark:bg-gray-800 shadow p-4 flex flex-wrap justify-between items-center gap-2"><meta charset="utf-8">
        <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Taskuri</h1>
    </header>

    <div class="p-6 overflow-y-auto flex-1">
        <?php if (!empty($eroare)): ?>
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 dark:text-red-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($eroare_bd)): ?>
        <div class="mb-4 p-4 bg-amber-100 dark:bg-amber-900/30 border-l-4 border-amber-600 text-amber-900 dark:text-amber-200 rounded-r" role="alert">
            <?php echo htmlspecialchars($eroare_bd); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
        <div class="mb-4 p-4 bg-emerald-100 dark:bg-emerald-900/30 border-l-4 border-emerald-600 text-emerald-900 dark:text-emerald-200 rounded-r" role="status" aria-live="polite">
            <?php echo htmlspecialchars($succes); ?>
        </div>
        <?php endif; ?>

        <!-- Header secțiune: titlu stânga, buton dreapta -->
        <header class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Taskuri</h2>
            <button type="button"
                    onclick="document.getElementById('formular-task').showModal()"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition"
                    aria-label="Adaugă task nou"
                    aria-haspopup="dialog"
                    id="btn-adauga-task">
                <i data-lucide="plus" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                Adaugă task
            </button>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Coloana stânga: Sarcini actuale -->
            <section class="flex flex-col" aria-labelledby="titlu-sarcini">
                <h3 id="titlu-sarcini" class="text-base font-semibold text-slate-900 dark:text-white mb-4">Sarcini actuale</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden flex-1">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Taskuri active">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-3 text-left" style="text-align: left !important;"><span class="sr-only">Finalizat</span></th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase" style="text-align: left !important;">Nume</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase" style="text-align: left !important;">Data și oră</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase" style="text-align: left !important;">Urgență</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase" style="text-align: left !important;">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($taskuri_active)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu există taskuri active.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($taskuri_active as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3" style="text-align: left !important;">
                                        <form method="post" action="todo.php" class="inline">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="finalizeaza_task" value="1">
                                            <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="marca_finalizat" onchange="(this.form.requestSubmit && this.form.requestSubmit()) || this.form.submit()"
                                                       class="w-5 h-5 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
                                                       aria-label="Marchează taskul <?php echo htmlspecialchars($t['nume']); ?> ca finalizat">
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-left" style="text-align: left !important;">
                                        <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>"
                                           class="inline-block text-left font-medium text-amber-600 dark:text-amber-400 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 rounded"
                                           aria-label="Editează taskul <?php echo htmlspecialchars($t['nume']); ?>"
                                           style="text-align: left !important;">
                                            <?php echo htmlspecialchars($t['nume']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-gray-300" style="text-align: left !important;"><?php echo date(DATETIME_FORMAT, strtotime($t['data_ora'])); ?></td>
                                    <td class="px-4 py-3 text-left" style="text-align: left !important;">
                                        <?php
                                        $badge = ['normal' => 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200', 'important' => 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100', 'reprogramat' => 'bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100'][$t['nivel_urgenta']] ?? 'bg-slate-200 dark:bg-slate-600';
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($t['nivel_urgenta'])); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-left" style="text-align: left !important;">
                                        <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>"
                                           class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500"
                                           aria-label="Editează taskul <?php echo htmlspecialchars($t['nume']); ?>">
                                            <i data-lucide="edit" class="w-4 h-4" aria-hidden="true"></i>
                                            Editează
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Coloana dreaptă: Istoric -->
            <section class="flex flex-col" aria-labelledby="titlu-istoric">
                <h3 id="titlu-istoric" class="text-base font-semibold text-slate-900 dark:text-white mb-4">Istoric taskuri</h3>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-slate-200 dark:border-gray-700 overflow-hidden flex-1">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700" role="table" aria-label="Istoric taskuri finalizate">
                            <thead class="bg-slate-100 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-3 text-left"></th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase" style="text-align: left !important;">Nume</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Data și oră</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Urgență</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-slate-800 dark:text-gray-200 uppercase">Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                                <?php if (empty($taskuri_istoric)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-600 dark:text-gray-400">Nu există taskuri finalizate.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($taskuri_istoric as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 text-left"><i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i></td>
                                    <td class="px-4 py-3 text-left" style="text-align: left !important;">
                                        <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>"
                                           class="inline-block text-left font-medium text-slate-700 dark:text-gray-300 hover:underline focus:outline-none focus:ring-2 focus:ring-amber-500 rounded"
                                           aria-label="Editează taskul <?php echo htmlspecialchars($t['nume']); ?>"
                                           style="text-align: left !important;">
                                            <?php echo htmlspecialchars($t['nume']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 dark:text-gray-400"><?php echo $t['data_finalizare'] ? date(DATETIME_FORMAT, strtotime($t['data_finalizare'])) : '-'; ?></td>
                                    <td class="px-4 py-3 text-left">
                                        <?php
                                        $badge = ['normal' => 'bg-slate-200 dark:bg-slate-600 text-slate-800 dark:text-slate-200', 'important' => 'bg-amber-200 dark:bg-amber-800 text-amber-900 dark:text-amber-100', 'reprogramat' => 'bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-100'][$t['nivel_urgenta']] ?? 'bg-slate-200 dark:bg-slate-600';
                                        ?>
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded <?php echo $badge; ?>"><?php echo htmlspecialchars(ucfirst($t['nivel_urgenta'])); ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-left">
                                        <div class="flex items-center justify-start gap-2">
                                            <a href="todo-edit.php?id=<?php echo (int)$t['id']; ?>"
                                               class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500"
                                               aria-label="Editează taskul <?php echo htmlspecialchars($t['nume']); ?>">
                                                <i data-lucide="edit" class="w-4 h-4" aria-hidden="true"></i>
                                                Editează
                                            </a>
                                            <form method="post" action="todo.php" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="reactivare_task" value="1">
                                                <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/50 hover:bg-amber-200 dark:hover:bg-amber-800 rounded focus:ring-2 focus:ring-amber-500"
                                                        aria-label="Reactivează taskul <?php echo htmlspecialchars($t['nume']); ?>">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4" aria-hidden="true"></i>
                                                    Reactivează
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<!-- Modal adăugare task -->
<dialog id="formular-task" role="dialog" aria-modal="true" aria-labelledby="titlu-form-task" aria-describedby="desc-form-task"
        class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-form-task" class="text-lg font-bold text-slate-900 dark:text-white mb-2">Adaugă task</h2>
        <p id="desc-form-task" class="text-sm text-slate-600 dark:text-gray-400 mb-4">Completați câmpurile pentru noul task.</p>
        <form method="post" action="todo.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="adauga_task" value="1">
            <div class="space-y-4">
                <div>
                    <label for="nume-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="nume-task" name="nume" required value="<?php echo htmlspecialchars($_POST['nume'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="data-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" id="data-task" name="data" required value="<?php echo htmlspecialchars($_POST['data'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="ora-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="time" id="ora-task" name="ora" required value="<?php echo htmlspecialchars($_POST['ora'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-describedby="ora-24h">
                        <span id="ora-24h" class="sr-only">Format 24 de ore</span>
                    </div>
                </div>
                <div>
                    <label for="detalii-task" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="detalii-task" name="detalii" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"><?php echo htmlspecialchars($_POST['detalii'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="nivel-urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                    <select id="nivel-urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Selectează nivelul de urgență">
                        <option value="normal" <?php echo ($_POST['nivel_urgenta'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="important" <?php echo ($_POST['nivel_urgenta'] ?? '') === 'important' ? 'selected' : ''; ?>>Important</option>
                        <option value="reprogramat" <?php echo ($_POST['nivel_urgenta'] ?? '') === 'reprogramat' ? 'selected' : ''; ?>>Reprogramat</option>
                    </select>
                </div>
            </div>
            <?php if (!empty($eroare)): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 rounded text-red-800 text-sm" role="alert"><?php echo htmlspecialchars($eroare); ?></div>
            <?php endif; ?>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('formular-task').close()" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Anulează">Anulează</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează task">Salvează</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Modal editare task -->
<dialog id="detalii-task" role="dialog" aria-modal="true" aria-labelledby="titlu-detalii" class="p-0 rounded-lg shadow-xl max-w-lg w-[calc(100%-2rem)] sm:w-full mx-4 sm:mx-auto border border-slate-200 dark:border-gray-700 dark:bg-gray-800 backdrop:bg-black/30">
    <div class="p-6">
        <h2 id="titlu-detalii" class="text-lg font-bold text-slate-900 dark:text-white mb-4">Editează task</h2>
        <form method="post" action="todo.php" id="form-edit-task">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="actualizeaza_task" value="1">
            <input type="hidden" name="task_id" id="edit-task-id" value="">
            <input type="hidden" name="redirect_after" id="edit-redirect" value="todo.php">
            <div class="space-y-4">
                <div>
                    <label for="edit-nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume task <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="edit-nume" name="nume" required
                           class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-required="true">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-data" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="date" id="edit-data" name="data" required
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700">
                    </div>
                    <div>
                        <label for="edit-ora" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ora</label>
                        <input type="time" id="edit-ora" name="ora"
                               class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-describedby="edit-ora-desc">
                        <span id="edit-ora-desc" class="sr-only">Format 24 de ore</span>
                    </div>
                </div>
                <div>
                    <label for="edit-detalii" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Detalii</label>
                    <textarea id="edit-detalii" name="detalii" rows="3"
                              class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700"></textarea>
                </div>
                <div>
                    <label for="edit-urgenta" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nivel urgență</label>
                    <select id="edit-urgenta" name="nivel_urgenta" class="w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 text-slate-900 dark:text-white dark:bg-gray-700" aria-label="Selectează nivelul de urgență">
                        <option value="normal">Normal</option>
                        <option value="important">Important</option>
                        <option value="reprogramat">Reprogramat</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" id="btn-renunta-task" class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:ring-2 focus:ring-amber-500" aria-label="Renunță (Esc)">Renunță</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:ring-2 focus:ring-amber-500" aria-label="Salvează (Enter)">Salvează</button>
            </div>
        </form>
    </div>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    <?php if (!empty($eroare) && isset($_POST['adauga_task'])): ?>
    document.getElementById('formular-task').showModal();
    <?php endif; ?>
});
function deschideEditare(id, nume, data, ora, detalii, urgenta) {
    document.getElementById('edit-task-id').value = id;
    document.getElementById('edit-nume').value = nume || '';
    document.getElementById('edit-data').value = data || '';
    document.getElementById('edit-ora').value = ora || '09:00';
    document.getElementById('edit-detalii').value = detalii || '';
    document.getElementById('edit-urgenta').value = urgenta || 'normal';
    document.getElementById('edit-redirect').value = window.location.pathname.indexOf('index') >= 0 ? 'index.php' : 'todo.php';
    document.getElementById('detalii-task').showModal();
    document.getElementById('edit-nume').focus();
}
var dlgTask = document.getElementById('detalii-task');
if (dlgTask) {
    dlgTask.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { this.close(); }
    });
    document.getElementById('btn-renunta-task')?.addEventListener('click', function() { dlgTask.close(); });
}
</script>
</body>
</html>
